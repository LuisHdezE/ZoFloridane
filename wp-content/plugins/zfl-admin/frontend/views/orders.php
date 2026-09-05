<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$result  = ZFL_Orders::handle_request();

// Pedido eliminado: volver a la lista. El header del panel ya envió salida,
// así que si los headers no se pueden reenviar, se usa redirect por JS.
if ( is_array( $result ) && ! empty( $result['deleted'] ) ) {
    $orders_list_url = home_url( ZFL_SLUG . '/orders/' );
    if ( ! headers_sent() ) {
        wp_safe_redirect( $orders_list_url );
        exit;
    }
    echo '<script>location.replace(' . wp_json_encode( $orders_list_url ) . ');</script>';
    exit;
}

$counts  = ZFL_Orders::get_counts();
$base    = home_url( ZFL_SLUG . '/orders/' );
$status  = isset( $_GET['st'] ) ? (int) $_GET['st'] : 0;
$view_id = isset( $_GET['order'] ) ? (int) $_GET['order'] : 0;

// Búsqueda por número de pedido: si existe, abre el detalle directamente.
$buscar = isset( $_GET['buscar'] ) ? sanitize_text_field( wp_unslash( $_GET['buscar'] ) ) : '';
$search_error = '';
if ( '' !== $buscar && ! $view_id ) {
    $found = wc_get_order( (int) preg_replace( '/\D/', '', $buscar ) );
    if ( $found ) {
        wp_safe_redirect( add_query_arg( 'order', $found->get_id(), $base ) );
        exit;
    }
    $search_error = 'No encontramos ningún pedido con el número ' . esc_html( $buscar ) . '.';
}

$tabs = array(
    0 => 'Todas',
    1 => 'Recibidos',
    2 => 'Pago verificado',
    3 => 'Preparados',
    4 => 'Entregados',
);

$modality_labels = array(
    'delivery' => 'Pago + entrega a domicilio',
    'cod'      => 'Pago al recibir',
    'pickup'   => 'Pago + recogida en tienda',
);

$detail = null;
if ( $view_id ) {
    $detail = wc_get_order( $view_id );
}
?>
<section class="zfl-section">

    <h1>Pedidos</h1>

    <?php if ( is_wp_error( $result ) ) : ?>
        <div class="zfl-error"><?php echo esc_html( $result->get_error_message() ); ?></div>
    <?php elseif ( is_array( $result ) && ! empty( $result['updated'] ) ) : ?>
        <div class="zfl-success">Pedido actualizado correctamente.</div>
    <?php elseif ( is_array( $result ) && ! empty( $result['cancelled'] ) ) : ?>
        <div class="zfl-success">Pedido cancelado. El cliente lo verá como rechazado en su rastreo.</div>
    <?php endif; ?>

    <?php if ( $view_id && ! $detail ) : ?>
        <div class="zfl-error">Pedido no encontrado.</div>
        <a class="zfl-btn-cancel" href="<?php echo esc_url( $base ); ?>">Volver a pedidos</a>
    <?php endif; ?>

    <?php if ( $detail ) :
        $phases      = ZFL_Orders::get_phases( $detail );
        $phase       = ZFL_Orders::get_phase( $detail );
        $fulfillment = ZFL_Orders::get_fulfillment( $detail );
        $phase_label = isset( $phases[ $phase ] ) ? $phases[ $phase ] : $phase;
        $receipt_url = ZFL_Orders::get_receipt_url( $detail );
        $locality    = ZFL_Orders::get_locality( $detail );
        $max_phase   = max( array_keys( $phases ) );
        $status      = $detail->get_status();

        $buyer_digits    = preg_replace( '/\D/', '', (string) $detail->get_billing_phone() );
        $receiver_digits = preg_replace( '/\D/', '', (string) $detail->get_meta( '_er_receiver_phone' ) );
        $buyer_wa    = '' !== $buyer_digits ? 'https://wa.me/' . ( 10 === strlen( $buyer_digits ) ? '1' . $buyer_digits : $buyer_digits ) : '';
        $receiver_wa = '' !== $receiver_digits ? 'https://wa.me/' . ( strlen( $receiver_digits ) <= 8 ? '53' . $receiver_digits : $receiver_digits ) : '';

        $delivery_address = (string) $detail->get_meta( '_er_delivery_address' );
        $zelle_holder     = (string) $detail->get_meta( '_er_zelle_name' );
        ?>

        <a class="zfl-btn-cancel" href="<?php echo esc_url( $base ); ?>">← Volver a pedidos</a>

        <div class="zfl-detail-head">
            <div>
                <span class="zfl-order-num">Pedido #<?php echo esc_html( $detail->get_order_number() ); ?></span>
                <span class="zfl-order-date"><?php echo esc_html( $detail->get_date_created() ? $detail->get_date_created()->date_i18n( 'j \d\e F \d\e Y \a \l\a\s H:i' ) : '' ); ?></span>
            </div>
            <?php if ( 'cancelled' === $status ) : ?>
                <span class="zfl-order-status zfl-ost-cancelled">Cancelado</span>
            <?php else : ?>
                <span class="zfl-order-status zfl-oph-<?php echo (int) $phase; ?>"><?php echo esc_html( $phase_label ); ?></span>
            <?php endif; ?>
        </div>

        <?php if ( 'cancelled' === $status ) : ?>
            <div class="zfl-error">Este pedido fue cancelado. Ya no aparecerá en el rastreo del cliente como activo.</div>
        <?php else : ?>

        <div class="zfl-detail-card">
            <h3>Progreso</h3>
            <ol class="zfl-phases">
                <?php foreach ( $phases as $idx => $label ) :
                    // "Pedido recibido" SIEMPRE hecho — sincronizado con el rastreo del cliente
                    $done = ( 1 === $idx ) || ( $idx < $phase ) || ( $idx === $max_phase && 'completed' === $status );
                    $current = $idx === $phase && 'cancelled' !== $status && ! $done;
                    ?>
                    <li class="<?php echo $done ? 'done' : ''; ?><?php echo $current ? ' current' : ''; ?>">
                        <span class="zfl-phase-dot"><?php echo $done ? '✓' : (int) $idx; ?></span>
                        <span><?php echo esc_html( $label ); ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>

        <div class="zfl-detail-grid">

            <div class="zfl-detail-card">
                <h3>Cliente</h3>
                <div class="zfl-order-row"><span class="zfl-order-label">Nombre</span><span><?php echo esc_html( $detail->get_formatted_billing_full_name() ?: '—' ); ?></span></div>
                <div class="zfl-order-row"><span class="zfl-order-label">Teléfono</span><span><?php echo esc_html( $detail->get_billing_phone() ?: '—' ); ?></span></div>
                <div class="zfl-order-row"><span class="zfl-order-label">Titular Zelle</span><span><?php echo esc_html( $zelle_holder ?: '—' ); ?></span></div>
                <?php if ( $buyer_wa ) : ?>
                    <a class="zfl-order-btn" href="<?php echo esc_url( $buyer_wa ); ?>" target="_blank" rel="noopener">WhatsApp cliente</a>
                <?php endif; ?>
            </div>

            <div class="zfl-detail-card">
                <h3>Entrega</h3>
                <div class="zfl-order-row"><span class="zfl-order-label">Modalidad</span><span><?php echo esc_html( isset( $modality_labels[ $fulfillment ] ) ? $modality_labels[ $fulfillment ] : $fulfillment ); ?></span></div>
                <div class="zfl-order-row"><span class="zfl-order-label">Recibe</span><span><?php
                    $receiver = $detail->get_meta( '_er_receiver_name' );
                    echo esc_html( $receiver ? $receiver . ' · ' . $detail->get_meta( '_er_receiver_phone' ) : '—' );
                ?></span></div>
                <?php if ( $delivery_address ) : ?>
                <div class="zfl-order-row"><span class="zfl-order-label">Dirección</span><span><?php echo esc_html( $delivery_address ); ?></span></div>
                <?php endif; ?>
                <?php if ( $locality ) : ?>
                <div class="zfl-order-row"><span class="zfl-order-label">Localidad</span><span><?php echo esc_html( $locality ); ?></span></div>
                <?php endif; ?>
                <?php if ( $receiver_wa ) : ?>
                    <a class="zfl-order-btn" href="<?php echo esc_url( $receiver_wa ); ?>" target="_blank" rel="noopener">WhatsApp destinatario</a>
                <?php endif; ?>
            </div>

        </div>

        <div class="zfl-detail-card">
            <h3>Productos</h3>
            <?php
            $items = $detail->get_items();
            if ( empty( $items ) ) :
                echo '<p class="zfl-order-label">Sin productos registrados.</p>';
            else :
                ?>
                <div class="zfl-detail-items">
                    <?php foreach ( $items as $item ) :
                        $pid = $item->get_product_id();
                        $img = '';
                        if ( $pid ) {
                            $prod = wc_get_product( $pid );
                            if ( $prod && $prod->get_image_id() ) {
                                $img = wp_get_attachment_image_url( $prod->get_image_id(), 'thumbnail' );
                            }
                        }
                        ?>
                        <div class="zfl-detail-item">
                            <?php if ( $img ) : ?>
                                <img src="<?php echo esc_url( $img ); ?>" alt="">
                            <?php else : ?>
                                <span class="zfl-detail-item-noimg">—</span>
                            <?php endif; ?>
                            <span class="zfl-detail-item-name"><?php echo esc_html( $item->get_name() ); ?> <small>×<?php echo (int) $item->get_quantity(); ?></small></span>
                            <strong><?php echo wp_kses_post( $item->get_total() !== '' ? wc_price( (float) $item->get_total() ) : '—' ); ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php foreach ( $detail->get_fees() as $fee ) : ?>
                <div class="zfl-order-row zfl-order-total"><span class="zfl-order-label"><?php echo esc_html( $fee->get_name() ); ?></span><span><?php echo wp_kses_post( wc_price( (float) $fee->get_total() ) ); ?></span></div>
            <?php endforeach; ?>

            <div class="zfl-order-row zfl-order-total"><span class="zfl-order-label">Total</span><span><?php echo wp_kses_post( $detail->get_formatted_order_total() ); ?></span></div>
        </div>

        <?php
        // Lo que el cliente paga, en la MONEDA que eligió (para verificar el cobro)
        $pay = ZFL_Orders::get_pay_info( $detail );
        $pay_items = 0.0;
        foreach ( $detail->get_items() as $pay_item ) {
            $pay_items += (float) $pay_item->get_total();
        }
        $pay_ship = (float) $detail->get_meta( '_er_shipping_cost' );
        $pay_tip  = (float) $detail->get_total() - $pay_items - $pay_ship;
        $pay_rate = $pay['rate'] > 0 ? $pay['rate'] : 1;
        ?>
        <div class="zfl-detail-card zfl-pay-card">
            <h3>Pago del cliente</h3>
            <div class="zfl-pay-head">
                <span class="zfl-pay-badge zfl-pay-badge-<?php echo esc_attr( strtolower( $pay['method'] ) ); ?>"><?php echo esc_html( $pay['method'] ); ?></span>
                <span class="zfl-pay-cur"><?php echo esc_html( $pay['currency'] ); ?></span>
            </div>
            <div class="zfl-order-row"><span class="zfl-order-label">Subtotal</span><span><?php echo esc_html( $pay['symbol'] . ' ' . number_format( $pay_items / $pay_rate, 2 ) ); ?></span></div>
            <?php if ( $pay_ship > 0 ) : ?>
                <div class="zfl-order-row"><span class="zfl-order-label">Envío</span><span><?php echo esc_html( $pay['symbol'] . ' ' . number_format( $pay_ship / $pay_rate, 2 ) ); ?></span></div>
            <?php endif; ?>
            <?php if ( abs( $pay_tip ) >= 0.01 ) : ?>
                <div class="zfl-order-row"><span class="zfl-order-label">Propina</span><span><?php echo esc_html( ( $pay_tip > 0 ? '+' : '' ) . $pay['symbol'] . ' ' . number_format( $pay_tip / $pay_rate, 2 ) ); ?></span></div>
            <?php endif; ?>
            <div class="zfl-order-row zfl-order-total"><span class="zfl-order-label">Total que paga</span><span><?php echo esc_html( $pay['formatted'] ); ?></span></div>
            <small class="zfl-order-label">Tasa aplicada: <?php echo esc_html( number_format( $pay_rate, 2 ) ); ?> CUP/<?php echo esc_html( $pay['currency'] ); ?></small>
        </div>

        <div class="zfl-detail-card">
            <h3>Pago</h3>
            <?php if ( $receipt_url ) : ?>
                <div class="zfl-detail-receipt">
                    <button type="button" class="zfl-detail-receipt-thumb" data-zfl-lightbox data-large="<?php echo esc_url( $receipt_url ); ?>" data-name="Comprobante #<?php echo esc_attr( $detail->get_order_number() ); ?>">
                        <img src="<?php echo esc_url( $receipt_url ); ?>" alt="Comprobante">
                    </button>
                    <button type="button" class="zfl-order-btn" data-zfl-lightbox data-large="<?php echo esc_url( $receipt_url ); ?>" data-name="Comprobante">Ver comprobante completo</button>
                </div>
            <?php else : ?>
                <p class="zfl-order-label">Sin comprobante adjunto.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="zfl-detail-card">
            <h3>Acciones</h3>
            <?php if ( 'cancelled' === $status ) : ?>
                <p class="zfl-order-label">Este pedido fue cancelado.</p>
            <?php else : ?>
            <div class="zfl-order-actions">

                <?php if ( 'cod' !== $fulfillment && 1 === $phase ) : ?>
                    <form method="post">
                        <?php wp_nonce_field( 'zfl_order_action', 'zfl_order_nonce' ); ?>
                        <input type="hidden" name="zfl_order_action" value="verify">
                        <input type="hidden" name="order_id" value="<?php echo (int) $detail->get_id(); ?>">
                        <button type="submit" class="zfl-order-btn zfl-order-btn-primary" onclick="return confirm('¿Confirmar que el pago por Zelle fue recibido?');">Verificar pago</button>
                    </form>
                <?php endif; ?>

                <?php
                $show_prepare = ( 'cod' === $fulfillment && 1 === $phase ) || ( 'cod' !== $fulfillment && 2 === $phase );
                if ( $show_prepare ) :
                    $prepare_label = 'pickup' === $fulfillment ? 'Marcar listo para recoger' : 'Marcar preparado';
                    ?>
                    <form method="post" enctype="multipart/form-data" class="zfl-order-photo-form">
                        <?php wp_nonce_field( 'zfl_order_action', 'zfl_order_nonce' ); ?>
                        <input type="hidden" name="zfl_order_action" value="prepare">
                        <input type="hidden" name="order_id" value="<?php echo (int) $detail->get_id(); ?>">
                        <label class="zfl-order-file">Foto del paquete (obligatoria)
                            <input type="file" name="order_photo" accept="image/*" required>
                        </label>
                        <button type="submit" class="zfl-order-btn zfl-order-btn-primary"><?php echo esc_html( $prepare_label ); ?></button>
                    </form>
                <?php endif; ?>

                <?php
                $show_deliver = ( 'cod' === $fulfillment && 2 === $phase ) || ( 'cod' !== $fulfillment && 3 === $phase );
                if ( $show_deliver ) :
                    $deliver_label = 'cod' === $fulfillment ? 'Marcar entregado y pagado' : ( 'pickup' === $fulfillment ? 'Marcar recogido' : 'Marcar entregado' );
                    ?>
                    <form method="post" enctype="multipart/form-data" class="zfl-order-photo-form">
                        <?php wp_nonce_field( 'zfl_order_action', 'zfl_order_nonce' ); ?>
                        <input type="hidden" name="zfl_order_action" value="deliver">
                        <input type="hidden" name="order_id" value="<?php echo (int) $detail->get_id(); ?>">
                        <label class="zfl-order-file">Foto de la entrega: carnet + paquete (obligatoria)
                            <input type="file" name="order_photo" accept="image/*" required>
                        </label>
                        <button type="submit" class="zfl-order-btn zfl-order-btn-primary"><?php echo esc_html( $deliver_label ); ?></button>
                    </form>
                <?php endif; ?>

                <?php if ( ! in_array( $status, array( 'completed', 'cancelled' ), true ) ) : ?>
                    <form method="post" onsubmit="return confirm('¿Cancelar este pedido? El cliente lo verá como rechazado.');">
                        <?php wp_nonce_field( 'zfl_order_action', 'zfl_order_nonce' ); ?>
                        <input type="hidden" name="zfl_order_action" value="cancel">
                        <input type="hidden" name="order_id" value="<?php echo (int) $detail->get_id(); ?>">
                        <button type="submit" class="zfl-order-btn zfl-order-cancel">Cancelar pedido</button>
                    </form>
                <?php endif; ?>

                <?php if ( current_user_can( 'manage_options' ) ) : ?>
                    <form method="post" onsubmit="return confirm('¿ELIMINAR este pedido permanentemente? Esta acción no se puede deshacer.');">
                        <?php wp_nonce_field( 'zfl_order_action', 'zfl_order_nonce' ); ?>
                        <input type="hidden" name="zfl_order_action" value="delete">
                        <input type="hidden" name="order_id" value="<?php echo (int) $detail->get_id(); ?>">
                        <button type="submit" class="zfl-order-btn zfl-order-cancel">Eliminar</button>
                    </form>
                <?php endif; ?>

                <a class="zfl-order-btn" href="<?php echo esc_url( home_url( '/factura/' . $detail->get_id() . '/?key=' . $detail->get_order_key() ) ); ?>" target="_blank">Ver factura</a>

            </div>
            <?php endif; ?>
        </div>

    <?php else : ?>

        <form method="get" class="zfl-search">
            <input type="number" name="buscar" min="1" placeholder="Buscar pedido por número" value="<?php echo esc_attr( $buscar ); ?>">
            <button type="submit">Buscar</button>
        </form>

        <?php if ( $search_error ) : ?>
            <div class="zfl-error"><?php echo esc_html( $search_error ); ?></div>
        <?php endif; ?>

        <nav class="zfl-subnav">
            <?php foreach ( $tabs as $slug => $label ) :
                $count = 0 === $slug ? (int) $counts['all'] : (int) ( $counts[ $slug ] ?? 0 );
                $href  = 0 === $slug ? $base : add_query_arg( 'st', $slug, $base );
                ?>
                <a class="zfl-subnav-link <?php echo $status === $slug ? 'active' : ''; ?>" href="<?php echo esc_url( $href ); ?>">
                    <?php echo esc_html( $label ); ?>
                    <span class="zfl-tab-count"><?php echo $count; ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php
        $orders = ZFL_Orders::get_orders( $status ? $status : 'all' );
        if ( empty( $orders ) ) :
            ?>
            <div class="zfl-orders-empty">
                <p>No hay pedidos<?php echo $status ? ' en este estado' : ''; ?> todavía.</p>
            </div>
        <?php else : ?>
            <div class="zfl-orders-list">
                <?php foreach ( $orders as $order ) :
                    $order_id    = $order->get_id();
                    $receipt_url = ZFL_Orders::get_receipt_url( $order );
                    $locality    = ZFL_Orders::get_locality( $order );
                    $fulfillment = ZFL_Orders::get_fulfillment( $order );
                    $phases      = ZFL_Orders::get_phases( $order );
                    $phase       = ZFL_Orders::get_phase( $order );
                    $phase_label = isset( $phases[ $phase ] ) ? $phases[ $phase ] : $phase;
                    $items       = $order->get_items();
                    $item_names  = array();
                    foreach ( array_slice( $items, 0, 3 ) as $item ) {
                        $item_names[] = $item->get_name() . ' ×' . $item->get_quantity();
                    }
                    $more = count( $items ) > 3 ? ' +' . ( count( $items ) - 3 ) : '';
                    $o_status = $order->get_status();

                    $buyer_digits    = preg_replace( '/\D/', '', (string) $order->get_billing_phone() );
                    $receiver_digits = preg_replace( '/\D/', '', (string) $order->get_meta( '_er_receiver_phone' ) );
                    $buyer_wa    = '' !== $buyer_digits ? 'https://wa.me/' . ( 10 === strlen( $buyer_digits ) ? '1' . $buyer_digits : $buyer_digits ) : '';
                    $receiver_wa = '' !== $receiver_digits ? 'https://wa.me/' . ( strlen( $receiver_digits ) <= 8 ? '53' . $receiver_digits : $receiver_digits ) : '';
                    ?>
                    <div class="zfl-order-card <?php echo 1 === $phase ? 'zfl-order-urgent' : ''; ?>">

                        <div class="zfl-order-head">
                            <div>
                                <a class="zfl-order-num" href="<?php echo esc_url( add_query_arg( 'order', $order_id, $base ) ); ?>" title="Ver detalle del pedido">#<?php echo esc_html( $order->get_order_number() ); ?></a>
                                <span class="zfl-order-date"><?php echo esc_html( human_time_diff( $order->get_date_created()->getTimestamp() ) ); ?> atrás</span>
                            </div>
                            <span class="zfl-order-status zfl-oph-<?php echo (int) $phase; ?>"><?php echo esc_html( $phase_label ); ?></span>
                        </div>

                        <div class="zfl-order-body">
                            <div class="zfl-order-row">
                                <span class="zfl-order-label">Modalidad</span>
                                <span><?php echo esc_html( isset( $modality_labels[ $fulfillment ] ) ? $modality_labels[ $fulfillment ] : $fulfillment ); ?></span>
                            </div>
                            <div class="zfl-order-row">
                                <span class="zfl-order-label">Cliente</span>
                                <span><?php echo esc_html( $order->get_formatted_billing_full_name() ?: '—' ); ?> · <?php echo esc_html( $order->get_billing_phone() ?: '—' ); ?></span>
                            </div>
                            <div class="zfl-order-row">
                                <span class="zfl-order-label">Recibe</span>
                                <span><?php
                                    $receiver = $order->get_meta( '_er_receiver_name' );
                                    echo esc_html( $receiver ? $receiver . ' · ' . $order->get_meta( '_er_receiver_phone' ) : '—' );
                                ?></span>
                            </div>
                            <?php if ( 'pickup' !== $fulfillment && $locality ) : ?>
                            <div class="zfl-order-row">
                                <span class="zfl-order-label">Entrega</span>
                                <span><?php echo esc_html( mb_substr( $locality, 0, 80 ) ); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="zfl-order-row">
                                <span class="zfl-order-label">Items</span>
                                <span><?php echo esc_html( implode( ', ', $item_names ) . $more ); ?></span>
                            </div>
                            <div class="zfl-order-row zfl-order-total">
                                <span class="zfl-order-label">Total</span>
                                <span><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></span>
                            </div>
                            <?php $pay_o = ZFL_Orders::get_pay_info( $order ); ?>
                            <div class="zfl-order-row zfl-order-total-cur">
                                <span class="zfl-order-label"><?php echo esc_html( $pay_o['method'] . ' · ' . $pay_o['currency'] ); ?></span>
                                <span><?php echo esc_html( $pay_o['formatted'] ); ?></span>
                            </div>
                        </div>

                        <div class="zfl-order-actions">
                            <?php if ( $receipt_url ) : ?>
                                <button type="button" class="zfl-order-btn" data-zfl-lightbox data-large="<?php echo esc_url( $receipt_url ); ?>" data-name="Comprobante #<?php echo esc_attr( $order->get_order_number() ); ?>">Ver comprobante</button>
                            <?php endif; ?>
                            <?php if ( $buyer_wa ) : ?>
                                <a class="zfl-order-btn" href="<?php echo esc_url( $buyer_wa ); ?>" target="_blank" rel="noopener" title="Escribir al cliente por WhatsApp">WhatsApp cliente</a>
                            <?php endif; ?>
                            <?php if ( $receiver_wa ) : ?>
                                <a class="zfl-order-btn" href="<?php echo esc_url( $receiver_wa ); ?>" target="_blank" rel="noopener" title="Escribir al destinatario por WhatsApp">WhatsApp destinatario</a>
                            <?php endif; ?>

                            <?php if ( 'cod' !== $fulfillment && 1 === $phase ) : ?>
                                <form method="post">
                                    <?php wp_nonce_field( 'zfl_order_action', 'zfl_order_nonce' ); ?>
                                    <input type="hidden" name="zfl_order_action" value="verify">
                                    <input type="hidden" name="order_id" value="<?php echo (int) $order_id; ?>">
                                    <button type="submit" class="zfl-order-btn zfl-order-btn-primary" onclick="return confirm('¿Confirmar que el pago por Zelle fue recibido?');">Verificar pago</button>
                                </form>
                            <?php endif; ?>

                            <?php
                            $show_prepare = ( 'cod' === $fulfillment && 1 === $phase ) || ( 'cod' !== $fulfillment && 2 === $phase );
                            if ( $show_prepare ) :
                                $prepare_label = 'pickup' === $fulfillment ? 'Marcar listo para recoger' : 'Marcar preparado';
                                ?>
                                <form method="post" enctype="multipart/form-data" class="zfl-order-photo-form">
                                    <?php wp_nonce_field( 'zfl_order_action', 'zfl_order_nonce' ); ?>
                                    <input type="hidden" name="zfl_order_action" value="prepare">
                                    <input type="hidden" name="order_id" value="<?php echo (int) $order_id; ?>">
                                    <label class="zfl-order-file">Foto del paquete (obligatoria)
                                        <input type="file" name="order_photo" accept="image/*" required>
                                    </label>
                                    <button type="submit" class="zfl-order-btn zfl-order-btn-primary"><?php echo esc_html( $prepare_label ); ?></button>
                                </form>
                            <?php endif; ?>

                            <?php
                            $show_deliver = ( 'cod' === $fulfillment && 2 === $phase ) || ( 'cod' !== $fulfillment && 3 === $phase );
                            if ( $show_deliver ) :
                                $deliver_label = 'cod' === $fulfillment ? 'Marcar entregado y pagado' : ( 'pickup' === $fulfillment ? 'Marcar recogido' : 'Marcar entregado' );
                                ?>
                                <form method="post" enctype="multipart/form-data" class="zfl-order-photo-form">
                                    <?php wp_nonce_field( 'zfl_order_action', 'zfl_order_nonce' ); ?>
                                    <input type="hidden" name="zfl_order_action" value="deliver">
                                    <input type="hidden" name="order_id" value="<?php echo (int) $order_id; ?>">
                                    <label class="zfl-order-file">Foto de la entrega: carnet + paquete (obligatoria)
                                        <input type="file" name="order_photo" accept="image/*" required>
                                    </label>
                                    <button type="submit" class="zfl-order-btn zfl-order-btn-primary"><?php echo esc_html( $deliver_label ); ?></button>
                                </form>
                            <?php endif; ?>

                            <a class="zfl-order-btn" href="<?php echo esc_url( add_query_arg( 'order', $order_id, $base ) ); ?>">Ver detalle ›</a>
                            <a class="zfl-order-btn" href="<?php echo esc_url( home_url( '/factura/' . $order_id . '/?key=' . $order->get_order_key() ) ); ?>" target="_blank">Factura</a>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Lightbox -->
    <div class="zfl-lightbox" id="zfl-lightbox" role="dialog" aria-modal="true" aria-label="Comprobante ampliado" hidden>
        <button type="button" class="zfl-lightbox-close" aria-label="Cerrar"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        <img class="zfl-lightbox-img" alt="">
        <p class="zfl-lightbox-caption"></p>
    </div>

</section>
