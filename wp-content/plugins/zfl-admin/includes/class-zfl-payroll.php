<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Nómina: cuánto debe cobrar cada empleado según su % de las ganancias,
 * acumulado desde su último cobro. Solo la ve el administrador principal.
 */
class ZFL_Payroll {

    const META_LAST_PAID = 'zfl_last_paid';

    // % y base de cálculo por rol
    public static function shares() {
        return array(
            'zfl_admin_2' => array( 'percent' => 70, 'label' => 'Administrador 2', 'base' => 'full' ),
            'zfl_gestor'  => array( 'percent' => 30, 'label' => 'Gestor de la tienda', 'base' => 'price' ),
        );
    }

    // Ganancias de UN pedido: [precio (CUP), tasa (CUP)]
    public static function order_profits( $order ) {
        $items_sale = 0.0;
        $items_cost = 0.0;

        foreach ( $order->get_items() as $item ) {
            $items_sale += (float) $item->get_total();
            $pid = $item->get_product_id();
            if ( $pid ) {
                $prod = wc_get_product( $pid );
                if ( $prod ) {
                    $cp = (float) $prod->get_meta( '_zfl_cost_price' );
                    if ( $cp > 0 ) {
                        $items_cost += $cp * $item->get_quantity();
                    }
                }
            }
        }

        $profit_price = $items_sale - $items_cost;

        $rate_public = class_exists( 'ZFL_Currencies' ) ? ZFL_Currencies::get_rate( 'USD' ) : 400;
        $rate_payout = class_exists( 'ZFL_Currencies' ) ? ZFL_Currencies::get_payout_rate( 'USD' ) : 400;
        $profit_rate = 0.0;
        if ( $rate_public > 0 && $rate_payout > 0 && $rate_payout < $rate_public ) {
            $profit_rate = (float) $order->get_total() * ( $rate_public - $rate_payout ) / $rate_public;
        }

        return array( $profit_price, $profit_rate );
    }

    // Empleados con su acumulado pendiente de cobro
    public static function get_employees() {
        $employees = array();
        $shares    = self::shares();

        foreach ( array_keys( $shares ) as $role ) {
            foreach ( get_users( array( 'role' => $role ) ) as $u ) {
                $employees[] = array(
                    'id'       => (int) $u->ID,
                    'name'     => $u->display_name,
                    'role'     => $role,
                    'label'    => $shares[ $role ]['label'],
                    'percent'  => (int) $shares[ $role ]['percent'],
                    'base'     => $shares[ $role ]['base'],
                );
            }
        }

        return $employees;
    }

    // Acumulado de un empleado desde su último cobro
    public static function pending_for( $employee ) {
        $last = (int) get_user_meta( $employee['id'], self::META_LAST_PAID, true );

        $args = array(
            'limit'   => -1,
            'status'  => array( 'processing', 'completed' ),
            'orderby' => 'date',
            'order'   => 'DESC',
        );

        if ( $last > 0 ) {
            $args['date_created'] = '>=' . date( 'Y-m-d H:i:s', $last );
        }

        $total = 0.0;

        foreach ( wc_get_orders( $args ) as $o ) {
            list( $profit_price, $profit_rate ) = self::order_profits( $o );
            $base = ( 'full' === $employee['base'] ) ? ( $profit_price + $profit_rate ) : $profit_price;
            $total += $base * ( $employee['percent'] / 100 );
        }

        return round( $total, 2 );
    }

    public static function handle_request() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'zfl_forbidden', 'Solo el administrador principal puede gestionar la nómina.' );
        }

        $action   = isset( $_POST['zfl_payroll_action'] ) ? sanitize_key( $_POST['zfl_payroll_action'] ) : '';
        $nonce_ok = isset( $_POST['zfl_payroll_nonce'] ) && wp_verify_nonce( $_POST['zfl_payroll_nonce'], 'zfl_payroll_action' );

        if ( 'paid' !== $action || ! $nonce_ok ) {
            return null;
        }

        $user_id = isset( $_POST['user_id'] ) ? (int) $_POST['user_id'] : 0;
        $user    = $user_id ? get_userdata( $user_id ) : false;

        if ( ! $user ) {
            return new WP_Error( 'zfl_not_found', 'Empleado no encontrado.' );
        }

        update_user_meta( $user_id, self::META_LAST_PAID, time() );

        return array( 'paid' => $user->display_name );
    }
}
