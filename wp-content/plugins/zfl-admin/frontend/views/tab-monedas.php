<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$result = ZFL_Currencies::handle_request();
$rates  = ZFL_Currencies::get_rates();

// Guardar costo de envío
if ( isset( $_POST['zfl_ship_save'] ) && wp_verify_nonce( $_POST['zfl_ship_nonce'] ?? '', 'zfl_ship_save' ) ) {
    $new_cost = max( 0, (int) ( $_POST['shipping_cost'] ?? 0 ) );
    update_option( 'er_shipping_cost', $new_cost );
}
$shipping_cost = (int) get_option( 'er_shipping_cost', defined( 'ER_SHIPPING_COST' ) ? (int) ER_SHIPPING_COST : 100 );
?>
<h2 class="zfl-tab-title">Monedas, tasas y envío</h2>

<?php if ( is_wp_error( $result ) ) : ?>
    <div class="zfl-error"><?php echo esc_html( $result->get_error_message() ); ?></div>
<?php elseif ( is_array( $result ) && ! empty( $result['saved'] ) ) : ?>
    <div class="zfl-success">Tasas guardadas correctamente.</div>
<?php endif; ?>

<p class="zfl-meta">
    <strong>Tasa de venta:</strong> cuántos CUP equivale 1 unidad de cada moneda al público.
    <strong>Tasa de pago a gestoras:</strong> la tarifa con la que se les liquida a las gestoras — por defecto 10 CUP por debajo de la pública. Esa diferencia es la ganancia del dueño por cada venta.
</p>

<form method="post">
    <?php wp_nonce_field( 'zfl_cur_action', 'zfl_cur_nonce' ); ?>
    <input type="hidden" name="zfl_cur_action" value="save">

    <div class="zfl-currencies-grid">
        <?php foreach ( array( 'USD', 'MXN', 'EUR' ) as $code ) :
            $data   = $rates[ $code ];
            $key    = strtolower( $code );
            $payout = isset( $data['payout_rate'] ) && (float) $data['payout_rate'] > 0 ? (float) $data['payout_rate'] : (float) $data['rate'] - 10;
            ?>
            <div class="zfl-currency-card">
                <div class="zfl-currency-head">
                    <b><?php echo esc_html( $code ); ?></b>
                    <label style="display:flex;align-items:center;gap:4px;font-size:12px;">Símbolo
                        <input type="text" name="symbol_<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $data['symbol'] ); ?>" style="width:40px;text-align:center;">
                    </label>
                </div>
                <label class="zfl-currency-rate">
                    <span>Tasa de venta (pública): 1 <?php echo esc_html( $code ); ?> =</span>
                    <input type="number" name="rate_<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $data['rate'] ); ?>" min="0.01" step="0.01" required>
                    <span>CUP</span>
                </label>
                <label class="zfl-currency-rate">
                    <span>Tasa de pago a gestoras: 1 <?php echo esc_html( $code ); ?> =</span>
                    <input type="number" name="payout_<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $payout ); ?>" min="0.01" step="0.01">
                    <span>CUP <small>(menor = gana el dueño)</small></span>
                </label>
            </div>
        <?php endforeach; ?>
    </div>

    <button type="submit" class="zfl-btn-primary">Guardar tasas</button>
</form>

<h3 class="zfl-section-subtitle">Costo de envío</h3>
<p class="zfl-meta">Solo aplica a pedidos con entrega a domicilio. La recogida no tiene costo de envío.</p>

<form method="post">
    <?php wp_nonce_field( 'zfl_ship_save', 'zfl_ship_nonce' ); ?>
    <input type="hidden" name="zfl_ship_save" value="1">

    <label style="display:inline-flex;align-items:center;gap:8px;font-weight:600;">
        Costo de envío:
        <input type="number" name="shipping_cost" value="<?php echo (int) $shipping_cost; ?>" min="0" step="1" style="width:120px;">
        <span>CUP</span>
    </label>

    <button type="submit" class="zfl-btn-primary" style="margin-left:12px;">Guardar envío</button>
</form>
