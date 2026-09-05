<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ZFL_Phones {

    const OPT = 'zfl_contact_phones';

    // Devuelve la lista; si nunca se ha guardado, siembra el número actual.
    public static function get_phones() {
        $phones = get_option( self::OPT, array() );
        if ( ! is_array( $phones ) || empty( $phones ) ) {
            $phones = array(
                array( 'label' => 'Atención al cliente', 'phone' => '5356514568' ),
            );
        }
        return $phones;
    }

    // Primer número de la lista = el principal (checkout y rastreo).
    public static function main_phone() {
        $phones = self::get_phones();
        return isset( $phones[0]['phone'] ) ? preg_replace( '/[^0-9]/', '', (string) $phones[0]['phone'] ) : '';
    }

    public static function handle_request() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'zfl_forbidden', 'Solo el administrador puede gestionar los teléfonos.' );
        }

        $action   = isset( $_POST['zfl_phones_action'] ) ? sanitize_key( $_POST['zfl_phones_action'] ) : '';
        $nonce_ok = isset( $_POST['zfl_phones_nonce'] ) && wp_verify_nonce( $_POST['zfl_phones_nonce'], 'zfl_phones_action' );

        if ( 'save' !== $action || ! $nonce_ok ) {
            return null;
        }

        $clean = array();

        if ( isset( $_POST['phones'] ) && is_array( $_POST['phones'] ) ) {
            foreach ( wp_unslash( $_POST['phones'] ) as $row ) {
                $label = isset( $row['label'] ) ? sanitize_text_field( $row['label'] ) : '';
                $phone = isset( $row['phone'] ) ? preg_replace( '/[^0-9+]/', '', (string) $row['phone'] ) : '';

                if ( ! empty( $row['delete'] ) ) {
                    continue;
                }
                if ( '' === $phone ) {
                    continue;
                }
                $clean[] = array(
                    'label' => $label !== '' ? $label : 'Atención',
                    'phone' => $phone,
                );
            }
        }

        update_option( self::OPT, $clean );

        return array( 'saved' => count( $clean ) );
    }
}
