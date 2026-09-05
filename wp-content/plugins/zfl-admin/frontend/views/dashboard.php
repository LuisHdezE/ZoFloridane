<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Métricas ──
$orders_recent = wc_get_orders( array(
    'limit'   => 100,
    'orderby' => 'date',
    'order'   => 'DESC',
    'status'  => array( 'on-hold', 'processing', 'completed', 'pending' ),
) );

$phase_counts = array( 1 => 0, 2 => 0, 3 => 0, 4 => 0 );
$sales_today  = 0.0;
$count_today  = 0;
$today        = date( 'Y-m-d' );

if ( class_exists( 'ZFL_Orders' ) ) {
    foreach ( $orders_recent as $o ) {
        $p = ZFL_Orders::get_phase( $o );
        if ( isset( $phase_counts[ $p ] ) ) {
            $phase_counts[ $p ]++;
        }
        if ( $o->get_date_created() && $o->get_date_created()->date( 'Y-m-d' ) === $today ) {
            $sales_today += (float) $o->get_total();
            $count_today++;
        }
    }
}

$latest = array_slice( $orders_recent, 0, 5 );

$orders_url   = home_url( ZFL_SLUG . '/orders/' );
$pending_url  = home_url( ZFL_SLUG . '/orders/?st=1' );
$catalog_url  = home_url( ZFL_SLUG . '/catalogo/' );
$finance_url  = home_url( ZFL_SLUG . '/finance/' );
$visits_url   = home_url( ZFL_SLUG . '/visits/' );
$zelle_url    = home_url( ZFL_SLUG . '/catalogo/?tab=zelle' );
?>
<section class="zfl-section">

    <h1>Resumen</h1>

    <div class="zfl-stats">
        <div class="zfl-stat">
            <span class="zfl-stat-num"><?php echo wp_kses_post( wc_price( $sales_today ) ); ?></span>
            <span class="zfl-stat-label">Ventas de hoy</span>
            <span class="zfl-stat-sub"><?php echo (int) $count_today; ?> pedido(s) hoy</span>
        </div>
        <a class="zfl-stat zfl-stat-link <?php echo $phase_counts[1] > 0 ? 'zfl-stat-alert' : ''; ?>" href="<?php echo esc_url( $pending_url ); ?>">
            <span class="zfl-stat-num"><?php echo (int) $phase_counts[1]; ?></span>
            <span class="zfl-stat-label">Por verificar pago</span>
            <span class="zfl-stat-sub">Toca para revisar</span>
        </a>
        <div class="zfl-stat">
            <span class="zfl-stat-num"><?php echo (int) $phase_counts[2] + (int) $phase_counts[3]; ?></span>
            <span class="zfl-stat-label">En preparación</span>
            <span class="zfl-stat-sub">Pago verificado o empaquetando</span>
        </div>
        <div class="zfl-stat">
            <span class="zfl-stat-num"><?php echo (int) $phase_counts[4]; ?></span>
            <span class="zfl-stat-label">Entregados</span>
            <span class="zfl-stat-sub">Pedidos completados</span>
        </div>
    </div>

    <h2 class="zfl-dash-h2">Accesos rápidos</h2>
    <ul class="zfl-tile-grid">
        <li><a href="<?php echo esc_url( $orders_url ); ?>"><strong>Pedidos</strong><span>Verifica pagos, prepara y entrega con fotos.</span></a></li>
        <li><a href="<?php echo esc_url( $catalog_url ); ?>"><strong>Catálogo</strong><span>Productos, categorías, localidades, promos y Zelle.</span></a></li>
        <li><a href="<?php echo esc_url( $finance_url ); ?>"><strong>Finanzas</strong><span>Ventas, propinas y redondeos por período.</span></a></li>
        <li><a href="<?php echo esc_url( $visits_url ); ?>"><strong>Visitas</strong><span>Visitantes únicos, carritos y pedidos iniciados.</span></a></li>
    </ul>

    <h2 class="zfl-dash-h2">Últimos pedidos</h2>

    <?php if ( empty( $latest ) ) : ?>
        <div class="zfl-orders-empty">
            <p>Todavía no hay pedidos. Cuando llegue el primero, verás una notificación aquí.</p>
        </div>
    <?php else : ?>
        <div class="zfl-orders-list">
            <?php
            $labels = array( 1 => 'Pedido recibido', 2 => 'Pago verificado', 3 => 'Paquete preparado', 4 => 'Paquete entregado' );
            foreach ( $latest as $order ) :
                $phases = class_exists( 'ZFL_Orders' ) ? ZFL_Orders::get_phases( $order ) : array();
                $phase  = class_exists( 'ZFL_Orders' ) ? ZFL_Orders::get_phase( $order ) : 1;
                $phase_label = isset( $phases[ $phase ] ) ? $phases[ $phase ] : $phase;
                ?>
                <div class="zfl-order-card <?php echo 1 === $phase ? 'zfl-order-urgent' : ''; ?>">
                    <div class="zfl-order-head">
                        <div>
                            <a class="zfl-order-num" href="<?php echo esc_url( add_query_arg( 'order', $order->get_id(), $orders_url ) ); ?>">#<?php echo esc_html( $order->get_order_number() ); ?></a>
                            <span class="zfl-order-date"><?php echo esc_html( human_time_diff( $order->get_date_created()->getTimestamp() ) ); ?> atrás</span>
                        </div>
                        <span class="zfl-order-status zfl-oph-<?php echo (int) $phase; ?>"><?php echo esc_html( $phase_label ); ?></span>
                    </div>
                    <div class="zfl-order-body">
                        <div class="zfl-order-row">
                            <span class="zfl-order-label">Cliente</span>
                            <span><?php echo esc_html( $order->get_formatted_billing_full_name() ?: '—' ); ?> · <?php echo esc_html( $order->get_billing_phone() ?: '—' ); ?></span>
                        </div>
                        <div class="zfl-order-row zfl-order-total">
                            <span class="zfl-order-label">Total</span>
                            <span><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <a class="zfl-order-btn zfl-order-btn-primary zfl-dash-more" href="<?php echo esc_url( $orders_url ); ?>">Ver todos los pedidos</a>
        </div>
    <?php endif; ?>

</section>
