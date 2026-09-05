<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ZFL_Products {

    public static function list_products( $search = '', $paged = 1, $per_page = 20, $category = 0, $localidad = 0 ) {
        $args = array(
            'post_type'      => 'product',
            'post_status'    => array( 'publish', 'draft', 'pending' ),
            'posts_per_page' => (int) $per_page,
            'paged'          => max( 1, (int) $paged ),
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        if ( '' !== $search ) {
            $args['s'] = sanitize_text_field( $search );
        }

        $category  = (int) $category;
        $localidad = (int) $localidad;

        $tax_query = array();
        if ( $category > 0 ) {
            $tax_query[] = array(
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $category,
            );
        }
        if ( $localidad > 0 ) {
            $tax_query[] = array(
                'taxonomy' => 'zfl_localidad',
                'field'    => 'term_id',
                'terms'    => $localidad,
            );
        }
        if ( count( $tax_query ) > 1 ) {
            $tax_query['relation'] = 'AND';
        }
        if ( ! empty( $tax_query ) ) {
            $args['tax_query'] = $tax_query;
        }

        $query = new WP_Query( $args );
        $items = array();

        foreach ( $query->posts as $post ) {
            $product = wc_get_product( $post->ID );
            if ( ! $product ) {
                continue;
            }
            $items[] = self::format_product( $product );
        }

        return array(
            'items'       => $items,
            'total'       => (int) $query->found_posts,
            'total_pages' => (int) $query->max_num_pages,
            'page'        => max( 1, (int) $paged ),
            'per_page'    => (int) $per_page,
            'category'    => $category,
            'localidad'   => $localidad,
        );
    }

    public static function format_product( WC_Product $product ) {
        $categories = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) );
        $loc_ids    = wp_get_post_terms( $product->get_id(), 'zfl_localidad', array( 'fields' => 'ids' ) );
        $loc_names  = wp_get_post_terms( $product->get_id(), 'zfl_localidad', array( 'fields' => 'names' ) );
        $thumb_id   = (int) $product->get_image_id();
        $thumb_url  = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'thumbnail' ) : '';
        $large_url  = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'large' ) : '';

        return array(
            'id'          => $product->get_id(),
            'name'        => $product->get_name(),
            'sku'         => $product->get_sku(),
            'price'       => $product->get_price(),
            'sale_price'  => $product->get_sale_price(),
            'regular_price' => $product->get_regular_price(),
            'stock'       => $product->get_stock_quantity(),
            'status'      => $product->get_status(),
            'permalink'   => get_permalink( $product->get_id() ),
            'categories'  => is_array( $categories ) ? implode( ', ', $categories ) : '',
            'localidad'   => is_array( $loc_names ) && ! empty( $loc_names ) ? implode( ', ', $loc_names ) : '',
            'loc_ids'     => is_array( $loc_ids ) ? array_map( 'intval', $loc_ids ) : array(),
            'thumb'       => $thumb_url,
            'large'       => $large_url,
            'thumb_id'    => $thumb_id,
            'created'     => $product->get_date_created() ? $product->get_date_created()->date( 'Y-m-d' ) : '',
            'cost_price'  => (float) $product->get_meta( '_zfl_cost_price' ),
            'cost_rate'   => (float) $product->get_meta( '_zfl_cost_rate' ),
        );
    }

    public static function quick_update( $product_id, $fields ) {
        $product = wc_get_product( (int) $product_id );
        if ( ! $product ) {
            return new WP_Error( 'zfl_product_not_found', 'Producto no encontrado.' );
        }

        if ( ! empty( $fields['name'] ) ) {
            $product->set_name( sanitize_text_field( $fields['name'] ) );
        }
        if ( isset( $fields['regular_price'] ) ) {
            $product->set_regular_price( self::clean_price( $fields['regular_price'] ) );
        }
        if ( isset( $fields['sale_price'] ) ) {
            $product->set_sale_price( self::clean_price( $fields['sale_price'] ) );
        }
        if ( isset( $fields['stock_quantity'] ) ) {
            $product->set_manage_stock( true );
            $product->set_stock_quantity( (int) $fields['stock_quantity'] );
            $product->set_stock_status( (int) $fields['stock_quantity'] > 0 ? 'instock' : 'outofstock' );
        }
        if ( isset( $fields['sku'] ) ) {
            $new_sku = sanitize_text_field( $fields['sku'] );
            if ( '' === $new_sku ) {
                $new_sku = self::generate_sku( $product );
            }
            $product->set_sku( $new_sku );
        }
        if ( isset( $fields['status'] ) ) {
            $allowed = array( 'publish', 'draft', 'pending' );
            $status  = sanitize_key( $fields['status'] );
            if ( in_array( $status, $allowed, true ) ) {
                $product->set_status( $status );
            }
        }

        // Costo: opcional. Vacío = se elimina (no se tiene en cuenta en finanzas)
        if ( isset( $fields['cost_price'] ) ) {
            if ( '' !== $fields['cost_price'] ) {
                $product->update_meta_data( '_zfl_cost_price', (float) $fields['cost_price'] );
            } else {
                $product->delete_meta_data( '_zfl_cost_price' );
            }
        }
        if ( isset( $fields['cost_rate'] ) ) {
            if ( '' !== $fields['cost_rate'] ) {
                $product->update_meta_data( '_zfl_cost_rate', (float) $fields['cost_rate'] );
            } else {
                $product->delete_meta_data( '_zfl_cost_rate' );
            }
        }

        $product->save();

        if ( isset( $fields['categories'] ) && is_array( $fields['categories'] ) ) {
            $cat_ids = array_filter( array_map( 'intval', $fields['categories'] ) );
            wp_set_object_terms( $product->get_id(), $cat_ids, 'product_cat' );
        }

        if ( isset( $fields['localidad'] ) ) {
            $loc = (int) $fields['localidad'];
            wp_set_object_terms( $product->get_id(), $loc > 0 ? array( $loc ) : array(), 'zfl_localidad' );
        }

        return $product;
    }

    public static function create_product( $data ) {
        if ( empty( $data['name'] ) ) {
            return new WP_Error( 'zfl_product_name_required', 'El nombre es obligatorio.' );
        }

        $product = new WC_Product_Simple();
        $product->set_name( sanitize_text_field( $data['name'] ) );

        $sku = ! empty( $data['sku'] ) ? sanitize_text_field( $data['sku'] ) : '';
        if ( '' === $sku ) {
            $sku = self::build_unique_sku( sanitize_text_field( $data['name'] ) );
        }
        $product->set_sku( $sku );

        if ( isset( $data['regular_price'] ) && '' !== $data['regular_price'] ) {
            $product->set_regular_price( self::clean_price( $data['regular_price'] ) );
        }
        if ( isset( $data['sale_price'] ) && '' !== $data['sale_price'] ) {
            $product->set_sale_price( self::clean_price( $data['sale_price'] ) );
            // Sin precio regular, WooCommerce usa el de venta como base
            if ( '' === (string) $product->get_regular_price() ) {
                $product->set_regular_price( self::clean_price( $data['sale_price'] ) );
            }
        }
        if ( isset( $data['stock_quantity'] ) ) {
            $product->set_manage_stock( true );
            $product->set_stock_quantity( (int) $data['stock_quantity'] );
            $product->set_stock_status( (int) $data['stock_quantity'] > 0 ? 'instock' : 'outofstock' );
        }

        $product->set_status( 'publish' );
        $product->save();

        if ( ! empty( $data['categories'] ) && is_array( $data['categories'] ) ) {
            $cat_ids = array_filter( array_map( 'intval', $data['categories'] ) );
            wp_set_object_terms( $product->get_id(), $cat_ids, 'product_cat' );
        }

        if ( ! empty( $data['localidad'] ) ) {
            $loc = (int) $data['localidad'];
            if ( $loc > 0 ) {
                wp_set_object_terms( $product->get_id(), array( $loc ), 'zfl_localidad' );
            }
        }

        if ( ! empty( $data['cost_price'] ) ) {
            $product->update_meta_data( '_zfl_cost_price', (float) $data['cost_price'] );
        }
        if ( ! empty( $data['cost_rate'] ) ) {
            $product->update_meta_data( '_zfl_cost_rate', (float) $data['cost_rate'] );
        }
        $product->save();

        return $product;
    }

    public static function delete_product( $product_id, $force = false ) {
        $product = wc_get_product( (int) $product_id );
        if ( ! $product ) {
            return new WP_Error( 'zfl_product_not_found', 'Producto no encontrado.' );
        }
        return (bool) $product->delete( $force );
    }

    public static function bulk_action( $ids, $action ) {
        $ids    = array_filter( array_map( 'intval', (array) $ids ) );
        $count  = 0;
        $action = sanitize_key( $action );

        foreach ( $ids as $id ) {
            $product = wc_get_product( $id );
            if ( ! $product ) {
                continue;
            }
            if ( 'delete' === $action ) {
                $product->delete( true );
                $count++;
            } elseif ( 'draft' === $action ) {
                $product->set_status( 'draft' );
                $product->save();
                $count++;
            } elseif ( 'publish' === $action ) {
                $product->set_status( 'publish' );
                $product->save();
                $count++;
            }
        }

        return $count;
    }

    public static function upload_image( $product_id, $file_field ) {
        if ( empty( $_FILES[ $file_field ] ) || $_FILES[ $file_field ]['error'] !== UPLOAD_ERR_OK ) {
            return new WP_Error( 'zfl_image_upload_failed', 'No se recibió una imagen válida.' );
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $override = array( 'test_form' => false );
        $upload    = wp_handle_upload( $_FILES[ $file_field ], $override );

        if ( isset( $upload['error'] ) ) {
            return new WP_Error( 'zfl_image_upload_failed', $upload['error'] );
        }

        $attachment = array(
            'guid'           => $upload['url'],
            'post_mime_type' => $upload['type'],
            'post_title'     => sanitize_file_name( basename( $upload['file'] ) ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        $attach_id = wp_insert_attachment( $attachment, $upload['file'] );

        if ( is_wp_error( $attach_id ) ) {
            return $attach_id;
        }

        $attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
        wp_update_attachment_metadata( $attach_id, $attach_data );

        $product = wc_get_product( (int) $product_id );
        if ( $product ) {
            $product->set_image_id( $attach_id );
            $product->save();
        }

        return $attach_id;
    }

    public static function delete_image( $product_id ) {
        $product = wc_get_product( (int) $product_id );
        if ( ! $product ) {
            return false;
        }
        $thumb_id = (int) $product->get_image_id();
        if ( ! $thumb_id ) {
            return false;
        }
        $product->set_image_id( 0 );
        $product->save();
        wp_delete_attachment( $thumb_id, true );
        return true;
    }

    public static function get_categories() {
        $terms = get_terms( array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
        ) );

        if ( is_wp_error( $terms ) ) {
            return array();
        }

        return array_map( function ( $term ) {
            return array(
                'id'   => (int) $term->term_id,
                'name' => $term->name,
            );
        }, $terms );
    }

    private static function clean_price( $value ) {
        if ( '' === $value || null === $value ) {
            return '';
        }
        $value = str_replace( ',', '.', (string) $value );
        $value = preg_replace( '/[^0-9\.]/', '', $value );
        return is_numeric( $value ) ? $value : '';
    }

    private static function generate_sku( $product ) {
        $existing = (string) $product->get_sku();
        if ( '' !== $existing ) {
            return $existing;
        }
        return self::build_unique_sku( $product->get_name() );
    }

    private static function build_unique_sku( $name ) {
        $base = strtoupper( substr( preg_replace( '/[^A-Za-z0-9]/', '', $name ), 0, 6 ) );
        if ( '' === $base ) {
            $base = 'ZF';
        }
        $candidate = $base . '-' . str_pad( (string) wp_rand( 0, 9999 ), 4, '0', STR_PAD_LEFT );

        $exists = wc_get_product_id_by_sku( $candidate );
        if ( $exists ) {
            $candidate = $base . '-' . str_pad( (string) wp_rand( 10000, 99999 ), 5, '0', STR_PAD_LEFT );
        }

        return $candidate;
    }

    public static function handle_request() {
        if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'zfl_forbidden', 'No tienes permisos para administrar productos.' );
        }

        $action   = isset( $_POST['zfl_product_action'] ) ? sanitize_key( $_POST['zfl_product_action'] ) : '';
        $nonce_ok = isset( $_POST['zfl_product_nonce'] ) && wp_verify_nonce( $_POST['zfl_product_nonce'], 'zfl_product_action' );

        if ( '' === $action || ! $nonce_ok ) {
            return null;
        }

        switch ( $action ) {
            case 'update':
                $id     = isset( $_POST['product_id'] ) ? (int) $_POST['product_id'] : 0;
                $result = self::quick_update( $id, $_POST );
                if ( is_wp_error( $result ) ) {
                    return $result;
                }
                if ( ! empty( $_FILES['product_image']['name'] ) ) {
                    $image_result = self::upload_image( $id, 'product_image' );
                    if ( is_wp_error( $image_result ) ) {
                        return $image_result;
                    }
                }
                if ( ! empty( $_POST['zfl_remove_image'] ) ) {
                    self::delete_image( $id );
                }
                return array( 'updated' => $id );

            case 'create':
                $result = self::create_product( $_POST );
                if ( is_wp_error( $result ) ) {
                    return $result;
                }
                if ( ! empty( $_FILES['product_image']['name'] ) ) {
                    $image_result = self::upload_image( $result->get_id(), 'product_image' );
                    if ( is_wp_error( $image_result ) ) {
                        return $image_result;
                    }
                }
                return array( 'created' => $result->get_id() );

            case 'delete':
                $id     = isset( $_POST['product_id'] ) ? (int) $_POST['product_id'] : 0;
                $result = self::delete_product( $id );
                return is_wp_error( $result ) ? $result : array( 'deleted' => $id );

            case 'delete_image':
                $id = isset( $_POST['product_id'] ) ? (int) $_POST['product_id'] : 0;
                $result = self::delete_image( $id );
                return array( 'image_deleted' => $id, 'success' => $result );

            case 'bulk':
                $ids    = isset( $_POST['product_ids'] ) ? (array) $_POST['product_ids'] : array();
                $bulk   = isset( $_POST['zfl_bulk_action'] ) ? sanitize_key( $_POST['zfl_bulk_action'] ) : '';
                $count  = self::bulk_action( $ids, $bulk );
                return array( 'bulk' => array( 'action' => $bulk, 'count' => $count ) );
        }

        return null;
    }
}
