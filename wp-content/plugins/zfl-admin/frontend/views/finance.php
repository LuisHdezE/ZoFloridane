<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$period  = isset( $_GET['periodo'] ) ? sanitize_key( $_GET['periodo'] ) : 'hoy';
$finance_base = home_url( ZFL_SLUG . '/finance/' );
$periods = array(
    'hoy' => array( 'label' => 'Hoy', 'since' => date( 'Y-m-d' ) ),
    '7d'  => array( 'label' => '7 días', 'since' => date( 'Y-m-d', strtotime( '-7 days' ) ) ),
    '30d' => array( 'label' => '30 días', 'since' => date( 'Y-m-d', strtotime( '-30 days' ) ) ),
    'todo' => array( 'label' => 'Todo', 'since' => '' ),
);

if ( ! isset( $periods[ $period ] ) ) {
    $period = 'hoy';
}

$args = array(
    'limit'   => -1,
    'orderby' => 'date',
    'order'   => 'DESC',
    'status'  => array( 'on-hold', 'processing', 'completed', 'pending' ),
);

if ( '' !== $periods[ $period ]['since'] ) {
    $args['date_created'] = '>=' . $periods[ $period ]['since'];
}

$orders = wc_get_orders( $args );

// ── Ganancias (solo admin/admin2 ven estos números) ──
$current_user = wp_get_current_user();
$profit_share = 1.0; // Admin: 100%

if ( in_array( 'zfl_admin_2', (array) $current_user->roles, true ) ) {
    $profit_share = 0.7;
} elseif ( ! in_array( 'administrator', (array) $current_user->roles, true ) && ! in_array( 'zfl_admin_2', (array) $current_user->roles, true ) ) {
    $profit_share = 0.3; // Gestor de la tienda
}

$ventas       = 0.0;
$propinas     = 0.0;
$redondeos    = 0.0;
$envios       = 0.0;
$confirmados  = 0;
$pend_count   = 0;
$pend_total   = 0.0;
$profit_price = 0.0;
$profit_rate  = 0.0;
$por_modalidad = array( 'delivery' => 0.0, 'cod' => 0.0, 'pickup' => 0.0 );
$por_localidad = array();

$modality_labels = array(
    'delivery' => 'Entrega a domicilio',
    'cod'      => 'Pago al recibir',
    'pickup'   => 'Recogida en tienda',
);

// Tarifa pública (venta) y tarifa de pago a gestoras — el diferencial es ganancia del dueño
$rate_public = class_exists( 'ZFL_Currencies' ) ? ZFL_Currencies::get_rate( 'USD' ) : 400;
$rate_payout = class_exists( 'ZFL_Currencies' ) ? ZFL_Currencies::get_payout_rate( 'USD' ) : 400;

foreach ( $orders as $o ) {
    $total = (float) $o->get_total();
    $st    = $o->get_status();
    $f     = ZFL_Orders::get_fulfillment( $o );
    $loc   = ZFL_Orders::get_locality( $o );
    $loc   = $loc !== '' ? $loc : 'Sin localidad';

    $tip_o = 0;
    $round_o = 0;
    foreach ( $o->get_fees() as $fee ) {
        $fname = $fee->get_name();
        if ( false !== stripos( $fname, 'propina' ) ) { $tip_o += (float) $fee->get_total(); }
        if ( false !== stripos( $fname, 'redondeo' ) ) { $round_o += (float) $fee->get_total(); }
    }

    $items_cost = 0.0;

    foreach ( $o->get_items() as $item ) {
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

    if ( in_array( $st, array( 'processing', 'completed' ), true ) ) {
        $confirmados++;
        $ventas += $total;
        $propinas += $tip_o;
        $redondeos += $round_o;
        $envios += (float) $o->get_meta( '_er_shipping_cost' );

        $por_modalidad[ $f ] = ( $por_modalidad[ $f ] ?? 0 ) + $total;
        $por_localidad[ $loc ] = ( $por_localidad[ $loc ] ?? 0 ) + $total;

        // Ganancia por precio (CUP): venta - compra (margen del producto)
        $items_sale = 0.0;
        foreach ( $o->get_items() as $item ) {
            $items_sale += (float) $item->get_total();
        }
        $profit_price += $items_sale - $items_cost;

        // Ganancia por tasa (CUP): el dueño cobra la venta a la tarifa pública
        // y liquida a las gestoras con la tarifa de pago (menor). El diferencial es suyo.
        if ( $rate_public > 0 && $rate_payout > 0 && $rate_payout < $rate_public ) {
            $profit_rate += $total * ( $rate_public - $rate_payout ) / $rate_public;
        }
    } else {
        $pend_count++;
        $pend_total += $total;
    }
}

$ticket = $confirmados > 0 ? $ventas / $confirmados : 0;
$max_loc = 1;
foreach ( $por_localidad as $v ) { $max_loc = max( $max_loc, $v ); }

$is_admin_full = in_array( 'administrator', (array) $current_user->roles, true );
$share_label = $is_admin_full ? 'Ganancia total (100%)' : sprintf( 'Mis ganancias (%d%%)', round( $profit_share * 100 ) );
?>
<section class="zfl-section">

    <h1>Finanzas</h1>

    <nav class="zfl-subnav">
        <?php foreach ( $periods as $slug => $data ) : ?>
            <a class="zfl-subnav-link <?php echo $period === $slug ? 'active' : ''; ?>"
               href="<?php echo esc_url( add_query_arg( 'periodo', $slug, $finance_base ) ); ?>">
                <?php echo esc_html( $data['label'] ); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="zfl-stats">
        <?php if ( $is_admin_full || in_array( 'zfl_admin_2', (array) $current_user->roles, true ) ) : ?>
            <div class="zfl-stat">
                <span class="zfl-stat-num"><?php echo wp_kses_post( wc_price( $profit_price * $profit_share ) ); ?></span>
                <span class="zfl-stat-label"><?php echo esc_html( $share_label ); ?> — por producto</span>
                <span class="zfl-stat-sub">Precio de venta − precio de compra</span>
            </div>
            <div class="zfl-stat">
                <span class="zfl-stat-num"><?php echo wp_kses_post( wc_price( $profit_rate * $profit_share ) ); ?></span>
                <span class="zfl-stat-label"><?php echo esc_html( $share_label ); ?> — por tasa</span>
                <span class="zfl-stat-sub">Tarifa pública <?php echo esc_html( $rate_public ); ?> vs pago a gestoras <?php echo esc_html( $rate_payout ); ?> CUP/USD</span>
            </div>
        <?php else : ?>
            <div class="zfl-stat">
                <span class="zfl-stat-num"><?php echo wp_kses_post( wc_price( $profit_price * $profit_share ) ); ?></span>
                <span class="zfl-stat-label"><?php echo esc_html( $share_label ); ?></span>
                <span class="zfl-stat-sub">Tu porcentaje del margen por producto</span>
            </div>
        <?php endif; ?>
        <div class="zfl-stat">
            <span class="zfl-stat-num"><?php echo wp_kses_post( wc_price( $ventas ) ); ?></span>
            <span class="zfl-stat-label">Ventas confirmadas</span>
            <span class="zfl-stat-sub"><?php echo (int) $confirmados; ?> pedido(s)</span>
        </div>
        <div class="zfl-stat">
            <span class="zfl-stat-num"><?php echo wp_kses_post( wc_price( $propinas ) ); ?></span>
            <span class="zfl-stat-label">Propinas</span>
            <span class="zfl-stat-sub">Incluye el redondeo a favor</span>
        </div>
        <div class="zfl-stat">
            <span class="zfl-stat-num"><?php echo wp_kses_post( wc_price( $ticket ) ); ?></span>
            <span class="zfl-stat-label">Ticket promedio</span>
            <span class="zfl-stat-sub">Por pedido confirmado</span>
        </div>
    </div>

    <h2 class="zfl-dash-h2">Por modalidad</h2>
    <div class="zfl-stats">
        <?php foreach ( $por_modalidad as $f => $v ) : ?>
            <div class="zfl-stat">
                <span class="zfl-stat-num"><?php echo wp_kses_post( wc_price( $v ) ); ?></span>
                <span class="zfl-stat-label"><?php echo esc_html( isset( $modality_labels[ $f ] ) ? $modality_labels[ $f ] : $f ); ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <h2 class="zfl-dash-h2">Por localidad</h2>
    <?php if ( empty( $por_localidad ) ) : ?>
        <p class="zfl-order-label">Sin ventas confirmadas en este período.</p>
    <?php else : ?>
        <div class="zfl-bars">
            <?php foreach ( $por_localidad as $loc => $v ) :
                $w = round( (float) $v * 100 / $max_loc );
                ?>
                <div class="zfl-bar-row">
                    <span class="zfl-bar-date"><?php echo esc_html( $loc ); ?></span>
                    <div class="zfl-bar-track"><div class="zfl-bar-fill" style="width: <?php echo (int) $w; ?>%;"></div></div>
                    <span class="zfl-bar-nums"><b><?php echo wp_kses_post( wc_price( $v ) ); ?></b></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h2 class="zfl-dash-h2">Pedidos del período</h2>
    <div class="zfl-card">
        <table class="zfl-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Pedido</th>
                    <th>Cliente</th>
                    <th>Modalidad</th>
                    <th>Propina</th>
                    <th>Total</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $orders ) ) : ?>
                    <tr><td colspan="7">Sin pedidos en este período.</td></tr>
                <?php else : ?>
                    <?php
                    $status_labels = array( 'on-hold' => 'En espera', 'processing' => 'En preparación', 'completed' => 'Entregado', 'pending' => 'Pendiente' );
                    foreach ( $orders as $o ) :
                        $tip_o = 0;
                        foreach ( $o->get_fees() as $fee ) {
                            if ( false !== stripos( $fee->get_name(), 'propina' ) ) {
                                $tip_o += (float) $fee->get_total();
                            }
                        }
                        $st_o = $o->get_status();
                        ?>
                        <tr>
                            <td><?php echo esc_html( $o->get_date_created() ? $o->get_date_created()->date_i18n( 'j M, H:i' ) : '' ); ?></td>
                            <td><a class="zfl-order-num" href="<?php echo esc_url( add_query_arg( 'order', $o->get_id(), home_url( ZFL_SLUG . '/orders/' ) ) ); ?>">#<?php echo esc_html( $o->get_order_number() ); ?></a></td>
                            <td><?php echo esc_html( $o->get_formatted_billing_full_name() ?: '—' ); ?></td>
                            <td><?php
                                $f_o = ZFL_Orders::get_fulfillment( $o );
                                echo esc_html( isset( $modality_labels[ $f_o ] ) ? $modality_labels[ $f_o ] : $f_o );
                            ?></td>
                            <td><?php echo $tip_o > 0 ? wp_kses_post( wc_price( $tip_o ) ) : '—'; ?></td>
                            <td><?php echo wp_kses_post( $o->get_formatted_order_total() ); ?></td>
                            <td><span class="zfl-order-status zfl-ost-<?php echo esc_attr( $st_o ); ?>"><?php echo esc_html( isset( $status_labels[ $st_o ] ) ? $status_labels[ $st_o ] : $st_o ); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</section>
