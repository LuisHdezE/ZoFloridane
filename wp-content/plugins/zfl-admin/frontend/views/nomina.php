<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_safe_redirect( home_url( ZFL_SLUG . '/dashboard/' ) );
    exit;
}

$result = ZFL_Payroll::handle_request();

$employees = array();
$grand     = 0.0;
foreach ( ZFL_Payroll::get_employees() as $e ) {
    $e['pending'] = ZFL_Payroll::pending_for( $e );
    $e['last']    = (int) get_user_meta( $e['id'], ZFL_Payroll::META_LAST_PAID, true );
    $grand       += $e['pending'];
    $employees[]  = $e;
}
?>
<h2 class="zfl-tab-title">Nómina de empleados</h2>

<?php if ( is_wp_error( $result ) ) : ?>
    <div class="zfl-error"><?php echo esc_html( $result->get_error_message() ); ?></div>
<?php elseif ( is_array( $result ) && ! empty( $result['paid'] ) ) : ?>
    <div class="zfl-success">Cobro registrado para <?php echo esc_html( $result['paid'] ); ?>. Su contador volvió a 0.</div>
<?php endif; ?>

<p class="zfl-meta">
    Cada empleado acumula su porcentaje de las ganancias desde su último cobro.
    Al pulsar <strong>Cobrado</strong>, su contador vuelve a 0 y empieza a acumular de nuevo.
    Esta sección solo la ve el administrador principal.
</p>

<?php if ( empty( $employees ) ) : ?>
    <div class="zfl-empty">Todavía no hay empleados. Crea usuarios con los roles "Gestor de la tienda" o "Administrador 2".</div>
<?php else : ?>
    <div class="zfl-card">
        <table class="zfl-table">
            <thead>
                <tr>
                    <th>Empleado</th>
                    <th>Rol</th>
                    <th>%</th>
                    <th>Debe cobrar</th>
                    <th>Último cobro</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $employees as $e ) : ?>
                    <tr>
                        <td><strong><?php echo esc_html( $e['name'] ); ?></strong></td>
                        <td><?php echo esc_html( $e['label'] ); ?></td>
                        <td><?php echo (int) $e['percent']; ?>%</td>
                        <td><strong><?php echo wp_kses_post( wc_price( $e['pending'] ) ); ?></strong></td>
                        <td><?php echo $e['last'] ? esc_html( date_i18n( 'j M Y, H:i', $e['last'] ) ) : '—'; ?></td>
                        <td>
                            <form method="post" onsubmit="return confirm('¿Registrar el cobro de <?php echo esc_js( $e['name'] ); ?>? Su contador volverá a 0.');">
                                <?php wp_nonce_field( 'zfl_payroll_action', 'zfl_payroll_nonce' ); ?>
                                <input type="hidden" name="zfl_payroll_action" value="paid">
                                <input type="hidden" name="user_id" value="<?php echo (int) $e['id']; ?>">
                                <button type="submit" class="zfl-order-btn zfl-order-btn-primary" <?php echo $e['pending'] <= 0 ? 'disabled' : ''; ?>>Cobrado</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <p class="zfl-meta" style="margin-top:10px;">
        Total pendiente de pagar: <strong><?php echo wp_kses_post( wc_price( $grand ) ); ?></strong>
    </p>
<?php endif; ?>
