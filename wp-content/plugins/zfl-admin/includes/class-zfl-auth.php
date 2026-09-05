<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ZFL_Auth {

    const SESSION_USER = 'zfl_panel_user';
    const SESSION_TIME = 'zfl_panel_time';
    const COOKIE_NAME  = 'zfl_panel_auth';
    const META_TOKEN   = 'zfl_panel_token';
    const COOKIE_DAYS  = 90;

    public static function start_session() {
        if ( session_id() ) {
            return;
        }
        if ( session_status() === PHP_SESSION_NONE ) {
            session_set_cookie_params( array(
                'lifetime' => self::COOKIE_DAYS * DAY_IN_SECONDS,
                'path'     => '/',
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ) );
            @session_start();
        }
    }

    public static function is_allowed_user() {
        $user = wp_get_current_user();
        if ( empty( $user->ID ) ) {
            return false;
        }

        $allowed = apply_filters( 'zfl_allowed_roles', ZFL_ALLOWED_ROLES );

        if ( array_intersect( (array) $user->roles, $allowed ) ) {
            return true;
        }

        global $wp_roles;
        if ( $wp_roles ) {
            foreach ( (array) $user->roles as $role_slug ) {
                if ( ! isset( $wp_roles->roles[ $role_slug ]['name'] ) ) {
                    continue;
                }
                $role_name = trim( (string) $wp_roles->roles[ $role_slug ]['name'] );
                if ( 0 === strcasecmp( $role_name, 'Gestor de la tienda' )
                    || 0 === strcasecmp( $role_name, 'Administrador 2' ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function login( $username, $password, $remember = false ) {
        if ( '' === $username || '' === $password ) {
            return new WP_Error( 'zfl_empty', 'Introduce usuario y contraseña.' );
        }

        $creds = array(
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => $remember,
        );

        $user = wp_signon( $creds, $remember );

        if ( is_wp_error( $user ) ) {
            return new WP_Error( 'zfl_invalid', 'Usuario o contraseña incorrectos.' );
        }

        wp_set_current_user( $user->ID );

        if ( ! self::is_allowed_user() ) {
            wp_logout();
            return new WP_Error( 'zfl_forbidden', 'Tu rol no tiene acceso a este panel.' );
        }

        self::set_logged_in( $user->ID );
        self::issue_token( $user->ID );

        return $user;
    }

    public static function logout() {
        self::start_session();
        unset( $_SESSION[ self::SESSION_USER ], $_SESSION[ self::SESSION_TIME ] );

        if ( isset( $_COOKIE[ self::COOKIE_NAME ] ) ) {
            $uid = get_current_user_id();
            if ( $uid ) {
                delete_user_meta( $uid, self::META_TOKEN );
            }
            setcookie( self::COOKIE_NAME, '', array(
                'expires'  => time() - 3600,
                'path'     => '/',
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ) );
        }

        wp_logout();
        wp_safe_redirect( home_url( ZFL_SLUG . '/login/' ) );
        exit;
    }

    public static function is_logged_in() {
        self::start_session();
        return ! empty( $_SESSION[ self::SESSION_USER ] );
    }

    public static function set_logged_in( $user_id ) {
        self::start_session();
        $_SESSION[ self::SESSION_USER ] = (int) $user_id;
        $_SESSION[ self::SESSION_TIME ] = time();
    }

    public static function require_access() {
        self::start_session();

        // 1) Sesión de WordPress activa
        if ( is_user_logged_in() && self::is_allowed_user() ) {
            self::set_logged_in( get_current_user_id() );
            return true;
        }

        // 2) Sesión PHP del panel
        if ( self::is_logged_in() ) {
            wp_set_current_user( (int) $_SESSION[ self::SESSION_USER ] );
            if ( self::is_allowed_user() ) {
                return true;
            }
            unset( $_SESSION[ self::SESSION_USER ], $_SESSION[ self::SESSION_TIME ] );
        }

        // 3) Token persistente (90 días — sobrevive al cierre de la app)
        if ( isset( $_COOKIE[ self::COOKIE_NAME ] ) ) {
            $token = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) );
            $uid   = self::user_by_token( $token );

            if ( $uid ) {
                wp_set_current_user( $uid );
                if ( self::is_allowed_user() ) {
                    self::set_logged_in( $uid );
                    self::issue_token( $uid ); // rotación
                    return true;
                }
                delete_user_meta( $uid, self::META_TOKEN );
            }
        }

        wp_safe_redirect( home_url( ZFL_SLUG . '/login/' ) );
        exit;
    }

    /* ── Token persistente ── */

    public static function issue_token( $user_id ) {
        $token = wp_generate_password( 64, false, false );
        $exp   = time() + self::COOKIE_DAYS * DAY_IN_SECONDS;

        setcookie( self::COOKIE_NAME, $token, array(
            'expires'  => $exp,
            'path'     => '/',
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ) );

        update_user_meta( $user_id, self::META_TOKEN, array(
            'hash'    => hash( 'sha256', $token ),
            'expires' => $exp,
        ) );
    }

    private static function user_by_token( $token ) {
        $hash = hash( 'sha256', (string) $token );
        $now  = time();

        $users = get_users( array(
            'meta_key' => self::META_TOKEN,
            'number'   => 10,
        ) );

        foreach ( $users as $u ) {
            $meta = get_user_meta( $u->ID, self::META_TOKEN, true );
            if ( is_array( $meta ) && ! empty( $meta['hash'] ) && hash_equals( $meta['hash'], $hash ) ) {
                if ( empty( $meta['expires'] ) || (int) $meta['expires'] > $now ) {
                    return (int) $u->ID;
                }
                delete_user_meta( $u->ID, self::META_TOKEN );
            }
        }

        return 0;
    }
}
