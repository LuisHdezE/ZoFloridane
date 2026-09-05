<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    echo '<div class="zfl-error">Esta sección solo está disponible para el administrador.</div>';
    return;
}

$result = ZFL_Phones::handle_request();
$phones = ZFL_Phones::get_phones();
?>
<h2 class="zfl-tab-title">Números de teléfono</h2>

<?php if ( is_wp_error( $result ) ) : ?>
    <div class="zfl-error"><?php echo esc_html( $result->get_error_message() ); ?></div>
<?php elseif ( is_array( $result ) && isset( $result['saved'] ) ) : ?>
    <div class="zfl-success">Números guardados correctamente (<?php echo (int) $result['saved']; ?> activos).</div>
<?php endif; ?>

<p class="zfl-meta">
    El <strong>primer número</strong> es el principal: aparece en el checkout ("¿Dudas con tu pedido?") y en los pedidos rechazados del rastreo.
    Esta sección solo la ve el administrador.
</p>

<form method="post">
    <?php wp_nonce_field( 'zfl_phones_action', 'zfl_phones_nonce' ); ?>
    <input type="hidden" name="zfl_phones_action" value="save">

    <div class="zfl-phones-list">
        <?php foreach ( $phones as $i => $row ) : ?>
            <div class="zfl-phone-row">
                <label>Etiqueta
                    <input type="text" name="phones[<?php echo (int) $i; ?>][label]" value="<?php echo esc_attr( $row['label'] ); ?>" placeholder="Ej: Atención al cliente">
                </label>
                <label>Número (con código de país, solo dígitos)
                    <input type="text" name="phones[<?php echo (int) $i; ?>][phone]" value="<?php echo esc_attr( $row['phone'] ); ?>" placeholder="Ej: 5356514568" required>
                </label>
                <label class="zfl-phone-del">
                    <input type="checkbox" name="phones[<?php echo (int) $i; ?>][delete]" value="1">
                    Eliminar
                </label>
            </div>
        <?php endforeach; ?>

        <div class="zfl-phone-row zfl-phone-new">
            <label>Nuevo: Etiqueta
                <input type="text" name="phones[new][label]" placeholder="Ej: Ventas">
            </label>
            <label>Nuevo: Número (opcional)
                <input type="text" name="phones[new][phone]" placeholder="Ej: 5356514568">
            </label>
        </div>
    </div>

    <button type="submit" class="zfl-btn-primary">Guardar números</button>
</form>
