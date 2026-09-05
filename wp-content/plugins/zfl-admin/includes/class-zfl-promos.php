<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ZFL_Promos {

    private static function table() {
        global $wpdb;
        return $wpdb->prefix . 'zfl_promos';
    }

    public static function get_all() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM " . self::table() . " ORDER BY sort_order ASC, id ASC",
            ARRAY_A
        );
    }

    public static function get_active() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM " . self::table() . " WHERE is_active = 1 ORDER BY sort_order ASC, id ASC",
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

    public static function create( $data, $image_id ) {
        global $wpdb;

        $max = (int) $wpdb->get_var( "SELECT COALESCE(MAX(sort_order), 0) FROM " . self::table() );

        $result = $wpdb->insert(
            self::table(),
            array(
                'title'      => sanitize_text_field( $data['title'] ),
                'image_id'   => (int) $image_id,
                'link'       => esc_url_raw( $data['link'] ),
                'is_active'  => ! empty( $data['is_active'] ) ? 1 : 0,
                'sort_order' => $max + 1,
            ),
            array( '%s', '%d', '%s', '%d', '%d' )
        );

        return $result ? $wpdb->insert_id : false;
    }

    public static function update( $id, $data, $image_id = 0 ) {
        global $wpdb;

        $fields = array();
        $format = array();

        if ( isset( $data['title'] ) ) {
            $fields['title'] = sanitize_text_field( $data['title'] );
            $format[] = '%s';
        }
        if ( isset( $data['link'] ) ) {
            $fields['link'] = esc_url_raw( $data['link'] );
            $format[] = '%s';
        }
        if ( isset( $data['is_active'] ) ) {
            $fields['is_active'] = $data['is_active'] ? 1 : 0;
            $format[] = '%d';
        }
        if ( $image_id > 0 ) {
            $fields['image_id'] = (int) $image_id;
            $format[] = '%d';
        }

        if ( empty( $fields ) ) {
            return false;
        }

        return $wpdb->update(
            self::table(),
            $fields,
            array( 'id' => (int) $id ),
            $format,
            array( '%d' )
        );
    }

    public static function delete( $id ) {
        global $wpdb;
        $row = self::get( $id );
        if ( $row && (int) $row['image_id'] > 0 ) {
            wp_delete_attachment( (int) $row['image_id'], true );
        }
        return $wpdb->delete( self::table(), array( 'id' => (int) $id ), array( '%d' ) );
    }

    public static function move( $id, $direction ) {
        $rows  = self::get_all();
        $index = null;

        foreach ( $rows as $i => $row ) {
            if ( (int) $row['id'] === (int) $id ) {
                $index = $i;
                break;
            }
        }

        if ( null === $index ) {
            return false;
        }

        $target = 'up' === $direction ? $index - 1 : $index + 1;
        if ( $target < 0 || $target >= count( $rows ) ) {
            return false;
        }

        $tmp = $rows[ $index ];
        $rows[ $index ] = $rows[ $target ];
        $rows[ $target ] = $tmp;

        global $wpdb;
        foreach ( $rows as $i => $row ) {
            $wpdb->update(
                self::table(),
                array( 'sort_order' => $i + 1 ),
                array( 'id' => (int) $row['id'] ),
                array( '%d' ),
                array( '%d' )
            );
        }

        return true;
    }

    private static function upload_image() {
        if ( empty( $_FILES['promo_image']['name'] ) || $_FILES['promo_image']['error'] !== UPLOAD_ERR_OK ) {
            return 0;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $file = wp_handle_upload( $_FILES['promo_image'], array( 'test_form' => false ) );
        if ( isset( $file['error'] ) ) {
            return 0;
        }

        $attach_id = wp_insert_attachment( array(
            'guid'           => $file['url'],
            'post_mime_type' => $file['type'],
            'post_title'     => sanitize_file_name( basename( $file['file'] ) ),
            'post_status'    => 'inherit',
        ), $file['file'] );

        if ( is_wp_error( $attach_id ) ) {
            return 0;
        }

        wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $file['file'] ) );
        return (int) $attach_id;
    }

    public static function handle_request() {
        if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'zfl_forbidden', 'No tienes permisos.' );
        }

        $action   = isset( $_POST['zfl_promo_action'] ) ? sanitize_key( $_POST['zfl_promo_action'] ) : '';
        $nonce_ok = isset( $_POST['zfl_promo_nonce'] ) && wp_verify_nonce( $_POST['zfl_promo_nonce'], 'zfl_promo_action' );

        if ( '' === $action ) {
            return null;
        }

        if ( ! $nonce_ok ) {
            return new WP_Error( 'zfl_promo_nonce', 'La página quedó desactualizada y la acción no se pudo aplicar. Recarga e inténtalo de nuevo.' );
        }

        switch ( $action ) {

            case 'promo_create':
                $image_id = self::upload_image();
                if ( ! $image_id ) {
                    return new WP_Error( 'zfl_promo_image', 'Debes subir una imagen para la promo.' );
                }
                $id = self::create( $_POST, $image_id );
                return $id ? array( 'promo_created' => $id ) : new WP_Error( 'zfl_promo_fail', 'No se pudo crear la promo.' );

            case 'promo_update':
                $id = isset( $_POST['promo_id'] ) ? (int) $_POST['promo_id'] : 0;
                if ( ! $id || ! self::get( $id ) ) {
                    return new WP_Error( 'zfl_promo_not_found', 'Promo no encontrada.' );
                }
                $image_id = self::upload_image();
                self::update( $id, $_POST, $image_id );
                return array( 'promo_updated' => $id );

            case 'promo_move':
                $id  = isset( $_POST['promo_id'] ) ? (int) $_POST['promo_id'] : 0;
                $dir = isset( $_POST['direction'] ) ? sanitize_key( $_POST['direction'] ) : '';
                self::move( $id, $dir );
                return array( 'promo_moved' => $id );

            case 'promo_delete':
                $id = isset( $_POST['promo_id'] ) ? (int) $_POST['promo_id'] : 0;
                if ( ! $id ) {
                    return new WP_Error( 'zfl_promo_not_found', 'Promo no encontrada.' );
                }
                self::delete( $id );
                return array( 'promo_deleted' => $id );
        }

        return null;
    }
}
