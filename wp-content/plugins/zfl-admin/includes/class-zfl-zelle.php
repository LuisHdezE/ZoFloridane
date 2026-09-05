<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ZFL_Zelle {

    private static function table() {
        global $wpdb;
        return $wpdb->prefix . 'zfl_zelle_accounts';
    }

    public static function get_all() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM " . self::table() . " ORDER BY is_active DESC, label ASC",
            ARRAY_A
        );
    }

    public static function get( $id ) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM " . self::table() . " WHERE id = %d", (int) $id ),
            ARRAY_A
        );
    }

    public static function get_active() {
        global $wpdb;
        $row = $wpdb->get_row(
            "SELECT * FROM " . self::table() . " WHERE is_active = 1 LIMIT 1",
            ARRAY_A
        );
        if ( null === $row && '' !== $wpdb->last_error ) {
            error_log( 'ZFL Zelle get_active SQL error: ' . $wpdb->last_error );
        }
        return $row;
    }

    public static function create( $data ) {
        global $wpdb;
        $result = $wpdb->insert(
            self::table(),
            array(
                'label'           => sanitize_text_field( $data['label'] ),
                'phone_or_email'  => sanitize_text_field( $data['phone_or_email'] ),
                'holder_name'     => sanitize_text_field( $data['holder_name'] ),
                'payment_note'    => sanitize_textarea_field( $data['payment_note'] ),
                'is_active'       => ! empty( $data['is_active'] ) ? 1 : 0,
            ),
            array( '%s', '%s', '%s', '%s', '%d' )
        );

        if ( $result && ! empty( $data['is_active'] ) ) {
            self::deactivate_others( $wpdb->insert_id );
        }

        return $wpdb->insert_id;
    }

    public static function update( $id, $data ) {
        global $wpdb;
        $fields = array();
        $format = array();

        if ( isset( $data['label'] ) ) {
            $fields['label'] = sanitize_text_field( $data['label'] );
            $format[] = '%s';
        }
        if ( isset( $data['phone_or_email'] ) ) {
            $fields['phone_or_email'] = sanitize_text_field( $data['phone_or_email'] );
            $format[] = '%s';
        }
        if ( isset( $data['holder_name'] ) ) {
            $fields['holder_name'] = sanitize_text_field( $data['holder_name'] );
            $format[] = '%s';
        }
        if ( isset( $data['payment_note'] ) ) {
            $fields['payment_note'] = sanitize_textarea_field( $data['payment_note'] );
            $format[] = '%s';
        }
        if ( isset( $data['is_active'] ) ) {
            $fields['is_active'] = $data['is_active'] ? 1 : 0;
            $format[] = '%d';
        }

        if ( empty( $fields ) ) {
            return false;
        }

        $result = $wpdb->update(
            self::table(),
            $fields,
            array( 'id' => (int) $id ),
            $format,
            array( '%d' )
        );

        if ( $result !== false && ! empty( $data['is_active'] ) ) {
            self::deactivate_others( (int) $id );
        }

        return $result;
    }

    public static function delete( $id ) {
        global $wpdb;
        return $wpdb->delete(
            self::table(),
            array( 'id' => (int) $id ),
            array( '%d' )
        );
    }

    public static function set_active( $id ) {
        global $wpdb;
        self::deactivate_others( (int) $id );
        return $wpdb->update(
            self::table(),
            array( 'is_active' => 1 ),
            array( 'id' => (int) $id ),
            array( '%d' ),
            array( '%d' )
        );
    }

    private static function deactivate_others( $except_id ) {
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE " . self::table() . " SET is_active = 0 WHERE id != %d",
                (int) $except_id
            )
        );
    }

    public static function handle_request() {
        if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'zfl_forbidden', 'No tienes permisos.' );
        }

        $action   = isset( $_POST['zfl_zelle_action'] ) ? sanitize_key( $_POST['zfl_zelle_action'] ) : '';
        $nonce_ok = isset( $_POST['zfl_zelle_nonce'] ) && wp_verify_nonce( $_POST['zfl_zelle_nonce'], 'zfl_zelle_action' );

        if ( '' === $action ) {
            return null;
        }

        if ( ! $nonce_ok ) {
            return new WP_Error( 'zfl_zelle_nonce', 'La página quedó desactualizada y la acción no se pudo aplicar. Recarga e inténtalo de nuevo.' );
        }

        switch ( $action ) {
            case 'create':
                if ( empty( $_POST['label'] ) || empty( $_POST['phone_or_email'] ) ) {
                    return new WP_Error( 'zfl_zelle_required', 'El nombre y el teléfono/email son obligatorios.' );
                }
                $id = self::create( $_POST );
                return $id ? array( 'created' => $id ) : new WP_Error( 'zfl_zelle_fail', 'Error al crear la cuenta.' );

            case 'update':
                $id = isset( $_POST['account_id'] ) ? (int) $_POST['account_id'] : 0;
                if ( ! $id ) {
                    return new WP_Error( 'zfl_zelle_not_found', 'Cuenta no encontrada.' );
                }
                self::update( $id, $_POST );
                return array( 'updated' => $id );

            case 'activate':
                $id = isset( $_POST['account_id'] ) ? (int) $_POST['account_id'] : 0;
                if ( ! $id ) {
                    return new WP_Error( 'zfl_zelle_not_found', 'Cuenta no encontrada.' );
                }
                self::set_active( $id );
                return array( 'activated' => $id );

            case 'delete':
                $id = isset( $_POST['account_id'] ) ? (int) $_POST['account_id'] : 0;
                if ( ! $id ) {
                    return new WP_Error( 'zfl_zelle_not_found', 'Cuenta no encontrada.' );
                }
                self::delete( $id );
                return array( 'deleted' => $id );
        }

        return null;
    }
}
