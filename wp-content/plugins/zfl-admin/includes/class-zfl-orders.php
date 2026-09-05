<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ZFL_Orders {

    public static function get_fulfillment( $order ) {
        $f = (string) $order->get_meta( '_er_fulfillment' );
        return in_array( $f, array( 'delivery', 'cod', 'pickup' ), true ) ? $f : 'delivery';
    }

    public static function get_fulfillment_label( $order ) {
        switch ( self::get_fulfillment( $order ) ) {
            case 'cod':
                return 'Pago al recibir';
            case 'pickup':
                return 'Pago + recogida en tienda';
            default:
                return 'Pago + entrega a domicilio';
        }
    }

    // Fases según la modalidad del pedido.
    public static function get_phases( $order ) {
        switch ( self::get_fulfillment( $order ) ) {
            case 'cod':
                return array(
                    1 => 'Pedido recibido',
                    2 => 'Paquete preparado',
                    3 => 'Entregado y pagado',
                );
            case 'pickup':
                return array(
                    1 => 'Pedido recibido',
                    2 => 'Pago verificado',
                    3 => 'Listo para recoger',
                    4 => 'Recogido en tienda',
                );
            default:
                return array(
                    1 => 'Pedido recibido',
                    2 => 'Pago verificado',
                    3 => 'Paquete preparado',
                    4 => 'Paquete entregado',
                );
        }
    }

    public static function get_phase( $order ) {
        $meta = (int) $order->get_meta( '_zfl_phase' );
        if ( $meta >= 1 ) {
            $phases = self::get_phases( $order );
            if ( isset( $phases[ $meta ] ) ) {
                return $meta;
            }
        }

        $status = $order->get_status();
        if ( 'completed' === $status ) {
            $phases = self::get_phases( $order );
            end( $phases );
            return key( $phases );
        }
        if ( 'processing' === $status ) {
            return 2;
        }
        return 1;
    }

    public static function get_orders( $phase = 'all', $limit = 50 ) {
        $orders = wc_get_orders( array(
            'limit'   => $limit,
            'orderby' => 'date',
            'order'   => 'DESC',
            'status'  => array( 'on-hold', 'processing', 'completed', 'pending' ),
        ) );

        if ( 'all' === $phase ) {
            return $orders;
        }

        return array_values( array_filter( $orders, function ( $o ) use ( $phase ) {
            return self::get_phase( $o ) === (int) $phase;
        } ) );
    }

    public static function get_counts() {
        $counts = array( 1 => 0, 2 => 0, 3 => 0, 4 => 0 );
        foreach ( self::get_orders( 'all' ) as $o ) {
            $p = self::get_phase( $o );
            if ( isset( $counts[ $p ] ) ) {
                $counts[ $p ]++;
            }
        }
        $counts['all'] = array_sum( $counts );
        return $counts;
    }

    public static function get_receipt_url( $order ) {
        $id = (int) $order->get_meta( '_zfl_receipt_id' );
        if ( ! $id ) {
            $id = (int) $order->get_meta( '_er_payment_proof_id' );
        }
        return $id ? (string) wp_get_attachment_url( $id ) : '';
    }

    public static function get_locality( $order ) {
        $name = (string) $order->get_meta( '_zfl_localidad_name' );
        if ( '' === $name ) {
            $name = (string) $order->get_meta( '_er_delivery_address' );
        }
        return $name;
    }

    // Datos de pago en la MONEDA que eligió el cliente (para verificar cobros)
    public static function get_pay_info( $order ) {
        $currency = strtoupper( (string) $order->get_meta( '_er_pay_currency' ) );
        if ( '' === $currency || 'CUP' === $currency ) {
            $currency = 'USD';
        }
        $rate = class_exists( 'ZFL_Currencies' ) ? (float) ZFL_Currencies::get_rate( $currency ) : 0;
        if ( $rate <= 0 ) {
            $rate = 400;
        }
        $symbol = class_exists( 'ZFL_Currencies' ) ? ZFL_Currencies::get_symbol( $currency ) : '$';
        $total  = (float) $order->get_total();
        $method = strtoupper( (string) $order->get_meta( '_er_payment_method' ) );

        return array(
            'currency'  => $currency,
            'symbol'    => $symbol,
            'rate'      => $rate,
            'total'     => round( $total / $rate, 2 ),
            'formatted' => $symbol . ' ' . number_format( $total / $rate, 2 ) . ' ' . $currency,
            'method'    => 'PAYPAL' === $method ? 'PayPal' : 'Zelle',
        );
    }

    // Foto de prueba de una fase completada.
    public static function get_photo( $order, $step ) {
        $step = (int) $step;
        $id   = (int) $order->get_meta( '_zfl_step_photo_' . $step );

        if ( ! $id && 3 === $step ) {
            $id = (int) $order->get_meta( '_zfl_phase3_photo' );
        }
        if ( ! $id && 4 === $step ) {
            $id = (int) $order->get_meta( '_zfl_phase4_photo' );
        }

        return $id ? (string) wp_get_attachment_url( $id ) : '';
    }

    private static function upload_order_photo() {
        if ( empty( $_FILES['order_photo']['name'] ) || $_FILES['order_photo']['error'] !== UPLOAD_ERR_OK ) {
            return 0;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $file = wp_handle_upload( $_FILES['order_photo'], array( 'test_form' => false ) );
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
        if ( ! ZFL_Auth::is_allowed_user() ) {
            return new WP_Error( 'zfl_forbidden', 'No tienes permisos.' );
        }

        $action   = isset( $_POST['zfl_order_action'] ) ? sanitize_key( $_POST['zfl_order_action'] ) : '';
        $nonce_ok = isset( $_POST['zfl_order_nonce'] ) && wp_verify_nonce( $_POST['zfl_order_nonce'], 'zfl_order_action' );

        if ( '' === $action ) {
            return null;
        }

        if ( ! $nonce_ok ) {
            return new WP_Error( 'zfl_order_nonce', 'La página quedó desactualizada. Recarga e inténtalo de nuevo.' );
        }

        $order_id = isset( $_POST['order_id'] ) ? (int) $_POST['order_id'] : 0;
        $order    = $order_id ? wc_get_order( $order_id ) : false;

        if ( ! $order ) {
            return new WP_Error( 'zfl_order_not_found', 'Pedido no encontrado.' );
        }

        $fulfillment = self::get_fulfillment( $order );
        $phase       = self::get_phase( $order );

        switch ( $action ) {

            case 'verify': // delivery/pickup: 1 → 2
                if ( 'cod' === $fulfillment || 1 !== $phase ) {
                    return new WP_Error( 'zfl_order_phase', 'Este pedido no está esperando verificación de pago.' );
                }
                $order->update_status( 'processing', 'Pago por Zelle verificado desde el panel.' );
                $order->update_meta_data( '_zfl_phase', 2 );
                $order->save();
                return array( 'updated' => $order_id );

            case 'prepare': // delivery/pickup: 2 → 3 | cod: 1 → 2
                $to = ( 'cod' === $fulfillment ) ? 2 : 3;
                if ( $phase !== $to - 1 ) {
                    return new WP_Error( 'zfl_order_phase', 'Este pedido no está pendiente de preparación.' );
                }
                $photo = self::upload_order_photo();
                if ( ! $photo ) {
                    return new WP_Error( 'zfl_order_photo', 'Debes agregar la foto del paquete preparado.' );
                }
                if ( 'processing' !== $order->get_status() ) {
                    $order->update_status( 'processing', 'Pedido en preparación.' );
                }
                $order->update_meta_data( '_zfl_step_photo_' . $to, $photo );
                $order->update_meta_data( '_zfl_phase', $to );
                $order->add_order_note( 'Paquete preparado. Foto de prueba agregada.' );
                $order->save();
                return array( 'updated' => $order_id );

            case 'deliver': // delivery/pickup: 3 → 4 | cod: 2 → 3
                $to = ( 'cod' === $fulfillment ) ? 3 : 4;
                if ( $phase !== $to - 1 ) {
                    return new WP_Error( 'zfl_order_phase', 'Este pedido no está pendiente de entrega.' );
                }
                $photo = self::upload_order_photo();
                if ( ! $photo ) {
                    return new WP_Error( 'zfl_order_photo', 'Debes agregar la foto de la entrega (carnet + paquete).' );
                }
                $order->update_meta_data( '_zfl_step_photo_' . $to, $photo );
                $order->update_meta_data( '_zfl_phase', $to );
                $order->update_status( 'completed', 'Pedido entregado. Foto de prueba agregada.' );
                $order->save();
                return array( 'updated' => $order_id );

            case 'cancel': // El cliente lo ve como "Rechazado" en el rastreo
                if ( in_array( $order->get_status(), array( 'completed', 'cancelled' ), true ) ) {
                    return new WP_Error( 'zfl_order_status', 'Este pedido ya está cerrado y no se puede cancelar.' );
                }
                $order->update_status( 'cancelled', 'Pedido cancelado desde el panel.' );
                $order->add_order_note( 'Pedido cancelado por el personal de la tienda.' );
                $order->save();
                return array( 'cancelled' => $order_id );

            case 'delete': // Eliminación real — solo administrador
                if ( ! current_user_can( 'manage_options' ) ) {
                    return new WP_Error( 'zfl_forbidden', 'Solo el administrador puede eliminar pedidos.' );
                }
                $order->delete( true );
                return array( 'deleted' => $order_id );
        }

        return null;
    }
}
