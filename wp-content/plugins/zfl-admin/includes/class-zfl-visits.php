<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ZFL_Visits {

    public static function init() {
        add_action( 'rest_api_init', function () {
            register_rest_route( 'zfl-visit/v1', '/track', array(
                'methods'             => 'POST',
                'permission_callback' => '__return_true',
                'callback'            => function ( $request ) {
                    $type = sanitize_key( (string) $request->get_param( 'type' ) );
                    if ( 'visit' !== $type ) {
                        wp_send_json_error( array( 'message' => 'Tipo inválido.' ), 422 );
                    }
                    self::track( 'visit' );
                    wp_send_json_success();
                },
            ) );
        } );
    }

    public static function session_hash() {
        $ua = substr( (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' ), 0, 255 );
        $ip = (string) ( $_SERVER['REMOTE_ADDR'] ?? '' );
        return hash( 'sha256', $ip . '|' . $ua );
    }

    public static function track( $event = 'visit', $value = 0, $tip = 0 ) {
        global $wpdb;

        $sid = self::session_hash();
        $ip  = (string) ( $_SERVER['REMOTE_ADDR'] ?? '' );
        $ua  = substr( (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' ), 0, 255 );
        $now = current_time( 'mysql' );

        $v = $wpdb->prefix . 'zfl_visits';
        $e = $wpdb->prefix . 'zfl_visit_events';

        $wpdb->insert( $v, array(
            'session_id' => $sid,
            'ip_hash'    => substr( hash( 'sha256', $ip . '|zfl' ), 0, 32 ),
            'user_agent' => $ua,
            'visited_at' => $now,
        ), array( '%s', '%s', '%s', '%s' ) );

        $wpdb->insert( $e, array(
            'session_id' => $sid,
            'event_type' => $event,
            'value'      => (float) $value,
            'tip'        => (float) $tip,
            'visited_at' => $now,
        ), array( '%s', '%s', '%f', '%f', '%s' ) );
    }

    public static function stats() {
        global $wpdb;
        $v = $wpdb->prefix . 'zfl_visits';
        $e = $wpdb->prefix . 'zfl_visit_events';
        $today = current_time( 'Y-m-d' );

        $visits_total  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $v" );
        $uniques_total = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT session_id) FROM $v" );
        $initiated     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $e WHERE event_type = 'order_initiated'" );
        $completed     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $e WHERE event_type = 'order_completed'" );

        return array(
            'visits_today'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $v WHERE visited_at >= '$today 00:00:00'" ),
            'uniques_today' => (int) $wpdb->get_var( "SELECT COUNT(DISTINCT session_id) FROM $v WHERE visited_at >= '$today 00:00:00'" ),
            'visits_total'  => $visits_total,
            'uniques_total' => $uniques_total,
            'initiated'     => $initiated,
            'completed'     => $completed,
            'conversion'    => $initiated > 0 ? round( $completed * 100 / $initiated, 1 ) : 0,
        );
    }

    public static function daily( $days = 7 ) {
        global $wpdb;
        $v = $wpdb->prefix . 'zfl_visits';
        $e = $wpdb->prefix . 'zfl_visit_events';
        $days = max( 1, (int) $days );

        $rows = $wpdb->get_results(
            "SELECT DATE(visited_at) AS d, COUNT(*) AS visitas, COUNT(DISTINCT session_id) AS unicos
             FROM $v WHERE visited_at >= DATE_SUB(NOW(), INTERVAL $days DAY)
             GROUP BY DATE(visited_at) ORDER BY d ASC",
            ARRAY_A
        );

        $out = array();
        foreach ( (array) $rows as $r ) {
            $out[ $r['d'] ] = array(
                'visitas' => (int) $r['visitas'],
                'unicos'  => (int) $r['unicos'],
                'inicios' => 0,
                'completados' => 0,
            );
        }

        foreach ( array( 'order_initiated' => 'inicios', 'order_completed' => 'completados' ) as $type => $key ) {
            $rows2 = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT DATE(visited_at) AS d, COUNT(*) AS c FROM $e
                     WHERE event_type = %s AND visited_at >= DATE_SUB(NOW(), INTERVAL $days DAY)
                     GROUP BY DATE(visited_at) ORDER BY d ASC",
                    $type
                ),
                ARRAY_A
            );
            foreach ( (array) $rows2 as $r ) {
                if ( ! isset( $out[ $r['d'] ] ) ) {
                    $out[ $r['d'] ] = array( 'visitas' => 0, 'unicos' => 0, 'inicios' => 0, 'completados' => 0 );
                }
                $out[ $r['d'] ][ $key ] = (int) $r['c'];
            }
        }

        ksort( $out );
        return $out;
    }

    public static function recent( $limit = 15 ) {
        global $wpdb;
        $v = $wpdb->prefix . 'zfl_visits';
        return $wpdb->get_results(
            $wpdb->prepare( "SELECT visited_at, user_agent FROM $v ORDER BY visited_at DESC, id DESC LIMIT %d", (int) $limit ),
            ARRAY_A
        );
    }
}

ZFL_Visits::init();
