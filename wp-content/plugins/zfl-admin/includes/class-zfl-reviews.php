<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ZFL_Reviews {

    const OPT = 'zfl_reviews';

    public static function init() {
        add_action( 'rest_api_init', function () {
            register_rest_route( 'zfl-review/v1', '/submit', array(
                'methods'             => 'POST',
                'permission_callback' => '__return_true',
                'callback'            => function ( $request ) {
                    $name = sanitize_text_field( $request->get_param( 'name' ) );
                    $from = sanitize_text_field( $request->get_param( 'from' ) );
                    $text = sanitize_textarea_field( $request->get_param( 'text' ) );
                    $stars = max( 1, min( 5, round( (float) $request->get_param( 'stars' ) * 2 ) / 2 ) );

                    if ( strlen( $name ) < 2 || strlen( $text ) < 10 ) {
                        wp_send_json_error( array( 'message' => 'Nombre y reseña son obligatorios.' ), 422 );
                    }

                    $reviews   = get_option( self::OPT, array() );
                    $reviews[] = array(
                        'text'      => $text,
                        'name'      => $name,
                        'from'      => $from,
                        'stars'     => $stars,
                        'is_active' => false,
                    );

                    update_option( self::OPT, $reviews );
                    wp_send_json_success();
                },
            ) );
        } );
    }

    public static function get_reviews( $active_only = true ) {
        $reviews = get_option( self::OPT, array() );
        if ( ! is_array( $reviews ) ) {
            return array();
        }
        if ( $active_only ) {
            return array_values( array_filter( $reviews, function ( $r ) {
                return ! empty( $r['is_active'] );
            } ) );
        }
        return $reviews;
    }

    // Estrellas con soporte de MEDIA estrella (escala 1-10, cada estrella vale 2)
    public static function render_stars( $rating ) {
        $rating = (float) $rating;
        $out    = '';
        for ( $i = 1; $i <= 5; $i++ ) {
            if ( $rating >= $i ) {
                $out .= '<span class="zfl-star-full">★</span>';
            } elseif ( $rating >= $i - 0.5 ) {
                $out .= '<span class="zfl-star-half">★</span>';
            } else {
                $out .= '<span class="zfl-star-empty">★</span>';
            }
        }
        return $out;
    }

    public static function handle_request() {
        if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'zfl_forbidden', 'No tienes permisos.' );
        }

        $action   = isset( $_POST['zfl_rev_action'] ) ? sanitize_key( $_POST['zfl_rev_action'] ) : '';
        $nonce_ok = isset( $_POST['zfl_rev_nonce'] ) && wp_verify_nonce( $_POST['zfl_rev_nonce'], 'zfl_rev_action' );

        if ( ! $nonce_ok || ! in_array( $action, array( 'activate', 'deactivate', 'delete' ), true ) ) {
            return null;
        }

        $index = isset( $_POST['zfl_rev_index'] ) ? (int) $_POST['zfl_rev_index'] : -1;
        $reviews = get_option( self::OPT, array() );

        if ( ! is_array( $reviews ) || $index < 0 || $index >= count( $reviews ) ) {
            return new WP_Error( 'zfl_not_found', 'Reseña no encontrada.' );
        }

        if ( 'delete' === $action ) {
            array_splice( $reviews, $index, 1 );
            update_option( self::OPT, $reviews );
            return array( 'msg' => 'Reseña eliminada.' );
        }

        if ( 'activate' === $action ) {
            $reviews[ $index ]['is_active'] = true;
        } elseif ( 'deactivate' === $action ) {
            $reviews[ $index ]['is_active'] = false;
        }

        update_option( self::OPT, $reviews );

        $label = 'activate' === $action ? 'aprobada' : 'ocultada';
        return array( 'msg' => 'Reseña ' . $label . '.' );
    }
}
