<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ZFL_Currencies {

    const OPT = 'zfl_currencies';

    public static function defaults() {
        return array(
            'USD' => array( 'rate' => 400, 'symbol' => '$', 'payout_rate' => 390 ),
            'MXN' => array( 'rate' => 20, 'symbol' => '$', 'payout_rate' => 19 ),
            'EUR' => array( 'rate' => 430, 'symbol' => '€', 'payout_rate' => 420 ),
        );
    }

    public static function get_rates() {
        $rates = get_option( self::OPT, array() );
        $defaults = self::defaults();

        if ( ! is_array( $rates ) || empty( $rates ) ) {
            return $defaults;
        }

        // Asegurar que las 3 monedas y sus tasas de pago siempre existan
        foreach ( $defaults as $code => $data ) {
            if ( ! isset( $rates[ $code ] ) || empty( $rates[ $code ]['rate'] ) ) {
                $rates[ $code ] = $data;
            } elseif ( empty( $rates[ $code ]['payout_rate'] ) ) {
                $rates[ $code ]['payout_rate'] = max( 1, (float) $rates[ $code ]['rate'] - 10 );
            }
        }

        return $rates;
    }

    public static function get_rate( $code ) {
        $rates = self::get_rates();
        $code  = strtoupper( $code );
        return isset( $rates[ $code ] ) ? (float) $rates[ $code ]['rate'] : 0;
    }

    // Tasa a la que se le paga a las gestoras (menor que la pública = ganancia del dueño)
    public static function get_payout_rate( $code ) {
        $rates = self::get_rates();
        $code  = strtoupper( $code );
        if ( isset( $rates[ $code ]['payout_rate'] ) && (float) $rates[ $code ]['payout_rate'] > 0 ) {
            return (float) $rates[ $code ]['payout_rate'];
        }
        return self::get_rate( $code );
    }

    public static function get_symbol( $code ) {
        $rates = self::get_rates();
        $code  = strtoupper( $code );
        return isset( $rates[ $code ]['symbol'] ) ? $rates[ $code ]['symbol'] : '$';
    }

    // Convierte CUP a la moneda destino
    public static function convert( $cup_amount, $code ) {
        $rate = self::get_rate( $code );
        if ( $rate <= 0 ) {
            return 0;
        }
        return round( (float) $cup_amount / $rate, 2 );
    }

    public static function handle_request() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'zfl_forbidden', 'Solo el administrador puede gestionar las monedas.' );
        }

        $action   = isset( $_POST['zfl_cur_action'] ) ? sanitize_key( $_POST['zfl_cur_action'] ) : '';
        $nonce_ok = isset( $_POST['zfl_cur_nonce'] ) && wp_verify_nonce( $_POST['zfl_cur_nonce'], 'zfl_cur_action' );

        if ( 'save' !== $action || ! $nonce_ok ) {
            return null;
        }

        $rates = self::defaults();

        foreach ( array( 'USD', 'MXN', 'EUR' ) as $code ) {
            $key    = strtolower( $code );
            $rate   = isset( $_POST[ 'rate_' . $key ] ) ? (float) $_POST[ 'rate_' . $key ] : 0;
            $symbol = isset( $_POST[ 'symbol_' . $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'symbol_' . $key ] ) ) : '';
            $payout = isset( $_POST[ 'payout_' . $key ] ) ? (float) $_POST[ 'payout_' . $key ] : 0;

            if ( $rate > 0 ) {
                $rates[ $code ]['rate'] = $rate;
            }
            if ( '' !== $symbol ) {
                $rates[ $code ]['symbol'] = $symbol;
            }
            if ( $payout > 0 ) {
                $rates[ $code ]['payout_rate'] = $payout;
            }
        }

        update_option( self::OPT, $rates );

        return array( 'saved' => true );
    }
}
