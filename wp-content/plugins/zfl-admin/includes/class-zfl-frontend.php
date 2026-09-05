<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ZFL_Frontend {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', array( $this, 'add_rewrite' ) );
        add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
        add_action( 'template_redirect', array( $this, 'route' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_filter( 'show_admin_bar', array( $this, 'maybe_hide_admin_bar' ) );
    }

    public function add_rewrite() {
        $base = ZFL_SLUG;
        add_rewrite_rule( '^' . $base . '/login/?$', 'index.php?zfl_route=login', 'top' );
        add_rewrite_rule( '^' . $base . '/logout/?$', 'index.php?zfl_route=logout', 'top' );
        add_rewrite_rule( '^' . $base . '/dashboard/?$', 'index.php?zfl_route=dashboard', 'top' );
        add_rewrite_rule( '^' . $base . '/finance/?$', 'index.php?zfl_route=finance', 'top' );
        add_rewrite_rule( '^' . $base . '/orders/?$', 'index.php?zfl_route=orders', 'top' );
        add_rewrite_rule( '^' . $base . '/nomina/?$', 'index.php?zfl_route=nomina', 'top' );
        add_rewrite_rule( '^' . $base . '/zelle/?$', 'index.php?zfl_route=zelle', 'top' );
        add_rewrite_rule( '^' . $base . '/catalogo/page/([0-9]+)/?$', 'index.php?zfl_route=catalogo&zfl_page=$matches[1]', 'top' );
        add_rewrite_rule( '^' . $base . '/catalogo/?$', 'index.php?zfl_route=catalogo', 'top' );
        add_rewrite_rule( '^' . $base . '/products/page/([0-9]+)/?$', 'index.php?zfl_route=products&zfl_page=$matches[1]', 'top' );
        add_rewrite_rule( '^' . $base . '/products/?$', 'index.php?zfl_route=products', 'top' );
        add_rewrite_rule( '^' . $base . '/visits/?$', 'index.php?zfl_route=visits', 'top' );
        add_rewrite_rule( '^' . $base . '/?$', 'index.php?zfl_route=landing', 'top' );
    }

    public function add_query_vars( $vars ) {
        $vars[] = 'zfl_route';
        $vars[] = 'zfl_page';
        return $vars;
    }

    public function route() {
        $route = get_query_var( 'zfl_route' );
        if ( empty( $route ) ) {
            return;
        }

        if ( 'logout' === $route ) {
            ZFL_Auth::logout();
        }

        if ( 'login' === $route ) {
            $this->render_login();
            exit;
        }

        ZFL_Auth::require_access();

        switch ( $route ) {
            case 'landing':
                wp_safe_redirect( home_url( ZFL_SLUG . '/dashboard/' ) );
                exit;
            case 'dashboard':
                $this->render_view( 'dashboard', 'Resumen general' );
                exit;
            case 'finance':
                $this->render_view( 'finance', 'Control financiero' );
                exit;
            case 'orders':
                $this->render_view( 'orders', 'Pedidos' );
                exit;
            case 'nomina':
                if ( ! current_user_can( 'manage_options' ) ) {
                    wp_safe_redirect( home_url( ZFL_SLUG . '/dashboard/' ) );
                    exit;
                }
                $this->render_view( 'nomina', 'Nómina' );
                exit;
            case 'zelle':
                wp_safe_redirect( home_url( ZFL_SLUG . '/catalogo/?tab=zelle' ) );
                exit;
            case 'catalogo':
                $this->render_view( 'catalogo', 'Catálogo' );
                exit;
            case 'products':
                wp_safe_redirect( home_url( ZFL_SLUG . '/catalogo/' ) );
                exit;
            case 'visits':
                $this->render_view( 'visits', 'Visitas' );
                exit;
        }
    }

    private function render_login() {
        $error = '';

        ZFL_Auth::start_session();

        if ( ZFL_Auth::is_logged_in() ) {
            wp_set_current_user( (int) $_SESSION[ ZFL_Auth::SESSION_USER ] );
            if ( ZFL_Auth::is_allowed_user() ) {
                wp_safe_redirect( home_url( ZFL_SLUG . '/dashboard/' ) );
                exit;
            }
            unset( $_SESSION[ ZFL_Auth::SESSION_USER ], $_SESSION[ ZFL_Auth::SESSION_TIME ] );
        }

        if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
            check_admin_referer( 'zfl_login', 'zfl_login_nonce' );
            $username = isset( $_POST['log'] ) ? sanitize_user( wp_unslash( $_POST['log'] ) ) : '';
            $password = isset( $_POST['pwd'] ) ? wp_unslash( $_POST['pwd'] ) : '';
            $remember = ! empty( $_POST['rememberme'] );

            $result = ZFL_Auth::login( $username, $password, $remember );

            if ( is_wp_error( $result ) ) {
                $error = $result->get_error_message();
            } else {
                wp_safe_redirect( home_url( ZFL_SLUG . '/dashboard/' ) );
                exit;
            }
        }

        nocache_headers();
        wp_enqueue_style( 'zfl-login', ZFL_URL . 'frontend/assets/login.css', array(), ZFL_VERSION );
        include ZFL_PATH . 'frontend/views/login.php';
        exit;
    }

    private function render_view( $template, $title ) {
        nocache_headers();
        wp_enqueue_style( 'zfl-panel', ZFL_URL . 'frontend/assets/panel.css', array(), ZFL_VERSION );

        $GLOBALS['zfl_page_title'] = $title;
        include ZFL_PATH . 'frontend/views/header.php';
        include ZFL_PATH . 'frontend/views/' . $template . '.php';
        include ZFL_PATH . 'frontend/views/footer.php';
    }

    public function enqueue_assets() {
        if ( ! get_query_var( 'zfl_route' ) ) {
            return;
        }
    }

    public function maybe_hide_admin_bar( $show ) {
        if ( get_query_var( 'zfl_route' ) ) {
            return false;
        }
        return $show;
    }
}
