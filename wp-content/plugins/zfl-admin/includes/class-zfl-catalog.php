<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ZFL_Catalog {

    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_taxonomy' ) );
    }

    public static function register_taxonomy() {
        if ( taxonomy_exists( 'zfl_localidad' ) ) {
            return;
        }
        register_taxonomy( 'zfl_localidad', array( 'product' ), array(
            'labels' => array(
                'name'          => 'Localidades',
                'singular_name' => 'Localidad',
                'add_new_item'  => 'Agregar localidad',
                'edit_item'     => 'Editar localidad',
            ),
            'hierarchical'      => false,
            'public'            => true,
            'show_in_nav_menus' => false,
            'show_admin_column' => false,
            'show_in_rest'      => true,
            'rewrite'           => false,
        ) );
    }

    /* ── Categorías ─────────────────────────────── */

    public static function get_categories_detailed() {
        $terms = get_terms( array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
        ) );

        if ( is_wp_error( $terms ) ) {
            return array();
        }

        $out = array();
        foreach ( $terms as $t ) {
            $thumb_id = (int) get_term_meta( $t->term_id, 'thumbnail_id', true );
            $out[] = array(
                'id'     => (int) $t->term_id,
                'name'   => $t->name,
                'count'  => (int) $t->count,
                'thumb'  => $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'thumbnail' ) : '',
                'large'  => $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'large' ) : '',
            );
        }
        return $out;
    }

    public static function get_category( $id ) {
        $term = get_term( (int) $id, 'product_cat' );
        if ( ! $term || is_wp_error( $term ) ) {
            return null;
        }
        $thumb_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
        return array(
            'id'    => (int) $term->term_id,
            'name'  => $term->name,
            'count' => (int) $term->count,
            'thumb' => $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'thumbnail' ) : '',
        );
    }

    private static function upload_term_image() {
        if ( empty( $_FILES['term_image']['name'] ) || $_FILES['term_image']['error'] !== UPLOAD_ERR_OK ) {
            return 0;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $file = wp_handle_upload( $_FILES['term_image'], array( 'test_form' => false ) );
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

    private static function delete_term_image( $term_id ) {
        $thumb_id = (int) get_term_meta( (int) $term_id, 'thumbnail_id', true );
        if ( ! $thumb_id ) {
            return;
        }
        delete_term_meta( (int) $term_id, 'thumbnail_id' );
        wp_delete_attachment( $thumb_id, true );
    }

    /* ── Localidades ────────────────────────────── */

    public static function get_localidades() {
        $terms = get_terms( array(
            'taxonomy'   => 'zfl_localidad',
            'hide_empty' => false,
        ) );

        if ( is_wp_error( $terms ) ) {
            return array();
        }

        return array_map( function ( $t ) {
            return array(
                'id'    => (int) $t->term_id,
                'name'  => $t->name,
                'note'  => $t->description,
                'count' => (int) $t->count,
            );
        }, $terms );
    }

    public static function get_localidad( $id ) {
        $term = get_term( (int) $id, 'zfl_localidad' );
        if ( ! $term || is_wp_error( $term ) ) {
            return null;
        }
        return array(
            'id'    => (int) $term->term_id,
            'name'  => $term->name,
            'note'  => $term->description,
            'count' => (int) $term->count,
        );
    }

    /* ── Request handling ───────────────────────── */

    public static function handle_request() {
        if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'zfl_forbidden', 'No tienes permisos.' );
        }

        $action   = isset( $_POST['zfl_catalog_action'] ) ? sanitize_key( $_POST['zfl_catalog_action'] ) : '';
        $nonce_ok = isset( $_POST['zfl_cat_nonce'] ) && wp_verify_nonce( $_POST['zfl_cat_nonce'], 'zfl_catalog_action' );

        if ( '' === $action || ! $nonce_ok ) {
            return null;
        }

        switch ( $action ) {

            case 'cat_create':
                $name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
                if ( '' === $name ) {
                    return new WP_Error( 'zfl_cat_name', 'El nombre de la categoría es obligatorio.' );
                }
                $term = wp_insert_term( $name, 'product_cat' );
                if ( is_wp_error( $term ) ) {
                    return $term;
                }
                $attach = self::upload_term_image();
                if ( $attach ) {
                    update_term_meta( $term['term_id'], 'thumbnail_id', $attach );
                }
                return array( 'cat_created' => (int) $term['term_id'] );

            case 'cat_update':
                $id = isset( $_POST['cat_id'] ) ? (int) $_POST['cat_id'] : 0;
                if ( ! $id || get_term( $id, 'product_cat' ) === null || is_wp_error( get_term( $id, 'product_cat' ) ) ) {
                    return new WP_Error( 'zfl_cat_not_found', 'Categoría no encontrada.' );
                }
                $name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
                if ( '' !== $name ) {
                    wp_update_term( $id, 'product_cat', array( 'name' => $name ) );
                }
                if ( ! empty( $_POST['remove_image'] ) ) {
                    self::delete_term_image( $id );
                }
                $attach = self::upload_term_image();
                if ( $attach ) {
                    self::delete_term_image( $id );
                    update_term_meta( $id, 'thumbnail_id', $attach );
                }
                return array( 'cat_updated' => $id );

            case 'cat_delete':
                $id = isset( $_POST['cat_id'] ) ? (int) $_POST['cat_id'] : 0;
                if ( ! $id ) {
                    return new WP_Error( 'zfl_cat_not_found', 'Categoría no encontrada.' );
                }
                self::delete_term_image( $id );
                $result = wp_delete_term( $id, 'product_cat' );
                if ( is_wp_error( $result ) ) {
                    return $result;
                }
                return array( 'cat_deleted' => $id );

            case 'loc_create':
                $name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
                if ( '' === $name ) {
                    return new WP_Error( 'zfl_loc_name', 'El nombre de la localidad es obligatorio.' );
                }
                $note = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
                $term = wp_insert_term( $name, 'zfl_localidad', array( 'description' => $note ) );
                if ( is_wp_error( $term ) ) {
                    return $term;
                }
                return array( 'loc_created' => (int) $term['term_id'] );

            case 'loc_update':
                $id = isset( $_POST['loc_id'] ) ? (int) $_POST['loc_id'] : 0;
                if ( ! $id ) {
                    return new WP_Error( 'zfl_loc_not_found', 'Localidad no encontrada.' );
                }
                $name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
                $note = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
                $args = array( 'description' => $note );
                if ( '' !== $name ) {
                    $args['name'] = $name;
                }
                wp_update_term( $id, 'zfl_localidad', $args );
                return array( 'loc_updated' => $id );

            case 'loc_delete':
                $id = isset( $_POST['loc_id'] ) ? (int) $_POST['loc_id'] : 0;
                if ( ! $id ) {
                    return new WP_Error( 'zfl_loc_not_found', 'Localidad no encontrada.' );
                }
                $result = wp_delete_term( $id, 'zfl_localidad' );
                if ( is_wp_error( $result ) ) {
                    return $result;
                }
                return array( 'loc_deleted' => $id );
        }

        return null;
    }
}

ZFL_Catalog::init();
