<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ZFL_Install {

    public static function activate() {
        self::create_tables();
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $zelle = $wpdb->prefix . 'zfl_zelle_accounts';
        $visits = $wpdb->prefix . 'zfl_visits';
        $events = $wpdb->prefix . 'zfl_visit_events';
        $promos = $wpdb->prefix . 'zfl_promos';

        dbDelta( "CREATE TABLE $zelle (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            label VARCHAR(190) NOT NULL,
            phone_or_email VARCHAR(190) NOT NULL,
            holder_name VARCHAR(190) NOT NULL DEFAULT '',
            payment_note VARCHAR(255) NOT NULL DEFAULT '',
            is_active TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY is_active (is_active)
        ) $charset_collate;" );

        dbDelta( "CREATE TABLE $visits (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(64) NOT NULL,
            ip_hash VARCHAR(64) NOT NULL,
            user_agent VARCHAR(255) NOT NULL DEFAULT '',
            visited_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY session_id (session_id),
            KEY visited_at (visited_at)
        ) $charset_collate;" );

        dbDelta( "CREATE TABLE $events (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(64) NOT NULL,
            event_type VARCHAR(40) NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            order_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            value DECIMAL(12,2) NOT NULL DEFAULT 0,
            tip DECIMAL(12,2) NOT NULL DEFAULT 0,
            visited_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY session_event (session_id, event_type),
            KEY event_type (event_type),
            KEY visited_at (visited_at)
        ) $charset_collate;" );

        dbDelta( "CREATE TABLE $promos (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(190) NOT NULL DEFAULT '',
            image_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            link VARCHAR(255) NOT NULL DEFAULT '',
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY is_active (is_active),
            KEY sort_order (sort_order)
        ) $charset_collate;" );
    }
}
