<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ZFL_Zelle_Gateway extends WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'zfl_zelle';
        $this->has_fields         = true;
        $this->method_title       = 'Zelle (Floridame)';
        $this->method_description = 'Pago manual por Zelle. El cliente ve la cuenta activa del panel, envía el total y sube su comprobante.';

        $this->init_form_fields();
        $this->init_settings();

        $this->title       = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_after_order_notes', array( $this, 'receipt_field' ) );
        add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'admin_order_receipt' ) );
        add_action( 'wp_footer', array( $this, 'checkout_js' ) );
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => 'Activar/Desactivar',
                'type'    => 'checkbox',
                'label'   => 'Habilitar pago por Zelle',
                'default' => 'yes',
            ),
            'title' => array(
                'title'   => 'Título',
                'type'    => 'text',
                'default' => 'Pago por Zelle',
            ),
            'description' => array(
                'title'   => 'Descripción',
                'type'    => 'textarea',
                'default' => 'Envía el total a la cuenta Zelle indicada y sube tu comprobante. Preparamos tu pedido cuando confirmamos el pago.',
            ),
        );
    }

    public function payment_fields() {
        if ( $this->description ) {
            echo wpautop( esc_html( $this->description ) );
        }

        $account = ZFL_Zelle::get_active();
        if ( ! $account ) {
            echo '<p><strong>No hay cuenta Zelle activa.</strong> Actívala en el panel: Catálogo → Cuentas Zelle.</p>';
            return;
        }

        echo '<div class="zfl-checkout-zelle" style="background:#fff7d6;border:1px solid #f5b400;border-radius:10px;padding:12px 14px;margin-top:8px;font-size:14px;line-height:1.5;">';
        echo '<p style="margin:0 0 6px;font-weight:700;">Datos para enviar el pago:</p>';
        echo '<p style="margin:0 0 4px;"><strong>Zelle:</strong> ' . esc_html( $account['phone_or_email'] ) . '</p>';
        if ( ! empty( $account['holder_name'] ) ) {
            echo '<p style="margin:0 0 4px;"><strong>Titular:</strong> ' . esc_html( $account['holder_name'] ) . '</p>';
        }
        if ( ! empty( $account['payment_note'] ) ) {
            echo '<p style="margin:0;"><strong>Nota:</strong> ' . esc_html( $account['payment_note'] ) . '</p>';
        }
        echo '</div>';
    }

    public function receipt_field( $checkout ) {
        echo '<div id="zfl_receipt_field" style="margin-top:1em;display:none;">';
        echo '<label for="zfl_receipt" style="font-weight:600;display:block;margin-bottom:4px;">Comprobante de pago (captura) — opcional, también puedes enviarla luego por WhatsApp</label>';
        echo '<input type="file" name="zfl_receipt" id="zfl_receipt" accept="image/*" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;" />';
        echo '</div>';
    }

    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! empty( $_FILES['zfl_receipt']['name'] ) ) {
            $upload = $this->save_receipt( $order_id );
            if ( is_wp_error( $upload ) ) {
                wc_add_notice( 'No se pudo subir el comprobante: ' . esc_html( $upload->get_error_message() ), 'error' );
                return array();
            }
        }

        $order->update_status( 'on-hold', 'Pedido en espera: el cliente pagará por Zelle. Confirmar ingreso antes de enviar.' );

        WC()->cart->empty_cart();

        return array(
            'result'   => 'success',
            'redirect' => $this->get_return_url( $order ),
        );
    }

    private function save_receipt( $order_id ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $file = wp_handle_upload( $_FILES['zfl_receipt'], array( 'test_form' => false ) );

        if ( isset( $file['error'] ) ) {
            return new WP_Error( 'zfl_upload', $file['error'] );
        }

        $attachment = array(
            'guid'           => $file['url'],
            'post_mime_type' => $file['type'],
            'post_title'     => 'Comprobante Zelle - Pedido #' . $order_id,
            'post_status'    => 'inherit',
        );

        $attach_id = wp_insert_attachment( $attachment, $file['file'] );
        if ( is_wp_error( $attach_id ) ) {
            return $attach_id;
        }

        wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $file['file'] ) );

        $order = wc_get_order( $order_id );
        $order->update_meta_data( '_zfl_receipt_id', $attach_id );
        $order->save();
        $order->add_order_note( 'Comprobante de pago subido por el cliente.' );

        return $attach_id;
    }

    public function admin_order_receipt( $order ) {
        $attach_id = $order->get_meta( '_zfl_receipt_id' );
        if ( ! $attach_id ) {
            return;
        }
        $url = wp_get_attachment_url( $attach_id );
        echo '<p class="form-field form-field-wide"><strong>Comprobante Zelle:</strong> <a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">Ver comprobante</a></p>';
    }

    public function checkout_js() {
        if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
            return;
        }
        ?>
        <script>
        (function(){
            function init(){
                var f=document.querySelector('form.checkout');
                if(f && !f.getAttribute('enctype')){ f.setAttribute('enctype','multipart/form-data'); }
            }
            function toggle(){
                var c=document.querySelector('input[name="payment_method"]:checked');
                var d=document.getElementById('zfl_receipt_field');
                if(d){ d.style.display=(c && c.value==='zfl_zelle')?'block':'none'; }
            }
            document.addEventListener('DOMContentLoaded',function(){ init(); toggle(); });
            document.addEventListener('change',function(e){ if(e.target && e.target.name==='payment_method'){ toggle(); } });
            if(window.jQuery){ jQuery(document.body).on('updated_checkout', toggle); }
        })();
        </script>
        <?php
    }
}
