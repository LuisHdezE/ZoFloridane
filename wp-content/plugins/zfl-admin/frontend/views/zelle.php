<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$result   = ZFL_Zelle::handle_request();
$accounts = ZFL_Zelle::get_all();
$editing  = isset( $_GET['edit'] ) ? (int) $_GET['edit'] : 0;
$edit_account = $editing ? ZFL_Zelle::get( $editing ) : null;
$tab_url  = home_url( ZFL_SLUG . '/catalogo/?tab=zelle' );
?>
<h2 class="zfl-tab-title">Cuentas Zelle</h2>

<?php if ( is_wp_error( $result ) ) : ?>
    <div class="zfl-error"><?php echo esc_html( $result->get_error_message() ); ?></div>
<?php elseif ( is_array( $result ) && ! empty( $result['created'] ) ) : ?>
    <div class="zfl-success">Cuenta creada correctamente.</div>
<?php elseif ( is_array( $result ) && ! empty( $result['updated'] ) ) : ?>
    <div class="zfl-success">Cuenta actualizada correctamente.</div>
<?php elseif ( is_array( $result ) && ! empty( $result['activated'] ) ) : ?>
    <div class="zfl-success">Cuenta activada como principal.</div>
<?php elseif ( is_array( $result ) && ! empty( $result['deleted'] ) ) : ?>
    <div class="zfl-success">Cuenta eliminada correctamente.</div>
<?php endif; ?>

<p class="zfl-meta">La cuenta activa se muestra a los clientes durante el checkout.</p>

<?php
$has_active = false;
foreach ( $accounts as $acc_check ) {
    if ( ! empty( $acc_check['is_active'] ) ) { $has_active = true; break; }
}
if ( ! empty( $accounts ) && ! $has_active ) :
?>
    <div class="zfl-error">
        Ninguna cuenta está activa: el checkout no mostrará datos de pago.
        Pulsa <strong>Activar</strong> en una cuenta de la lista.
    </div>
<?php endif; ?>

<?php if ( $edit_account ) : ?>
    <div class="zfl-zelle-form-wrap">
        <h3>Editar cuenta</h3>
        <form method="post" class="zfl-zelle-form">
            <?php wp_nonce_field( 'zfl_zelle_action', 'zfl_zelle_nonce' ); ?>
            <input type="hidden" name="zfl_zelle_action" value="update">
            <input type="hidden" name="account_id" value="<?php echo (int) $edit_account['id']; ?>">

            <label>Nombre de la cuenta *
                <input type="text" name="label" value="<?php echo esc_attr( $edit_account['label'] ); ?>" placeholder="Ej: Zelle Personal" required>
            </label>
            <label>Teléfono o email de Zelle *
                <input type="text" name="phone_or_email" value="<?php echo esc_attr( $edit_account['phone_or_email'] ); ?>" placeholder="Ej: 3051234567 o email@gmail.com" required>
            </label>
            <label>Nombre del titular
                <input type="text" name="holder_name" value="<?php echo esc_attr( $edit_account['holder_name'] ); ?>" placeholder="Ej: María García">
            </label>
            <label>Nota de pago
                <textarea name="payment_note" rows="3" placeholder="Ej: Enviar comprobante por este mismo número"><?php echo esc_textarea( $edit_account['payment_note'] ); ?></textarea>
            </label>
            <label class="zfl-zelle-active-check">
                <input type="checkbox" name="is_active" value="1" <?php checked( $edit_account['is_active'], 1 ); ?>>
                Cuenta activa (se muestra en checkout)
            </label>

            <div class="zfl-zelle-form-btns">
                <button type="submit" class="zfl-btn-primary">Guardar cambios</button>
                <a href="<?php echo esc_url( $tab_url ); ?>" class="zfl-btn-cancel">Cancelar</a>
            </div>
        </form>
    </div>

<?php else : ?>
    <details class="zfl-zelle-new">
        <summary>Agregar cuenta Zelle</summary>
        <form method="post" class="zfl-zelle-form">
            <?php wp_nonce_field( 'zfl_zelle_action', 'zfl_zelle_nonce' ); ?>
            <input type="hidden" name="zfl_zelle_action" value="create">

            <label>Nombre de la cuenta *
                <input type="text" name="label" placeholder="Ej: Zelle Personal" required>
            </label>
            <label>Teléfono o email de Zelle *
                <input type="text" name="phone_or_email" placeholder="Ej: 3051234567 o email@gmail.com" required>
            </label>
            <label>Nombre del titular
                <input type="text" name="holder_name" placeholder="Ej: María García">
            </label>
            <label>Nota de pago
                <textarea name="payment_note" rows="3" placeholder="Ej: Enviar comprobante por este mismo número"></textarea>
            </label>
            <label class="zfl-zelle-active-check">
                <input type="checkbox" name="is_active" value="1">
                Cuenta activa (se muestra en checkout)
            </label>

            <button type="submit" class="zfl-btn-primary">Crear cuenta</button>
        </form>
    </details>

    <?php if ( empty( $accounts ) ) : ?>
        <div class="zfl-zelle-empty">
            <p>No hay cuentas Zelle configuradas.</p>
            <p>Agrega la primera cuenta para que los clientes puedan pagar con Zelle.</p>
        </div>
    <?php else : ?>
        <div class="zfl-zelle-list">
            <?php foreach ( $accounts as $acc ) : ?>
                <div class="zfl-zelle-card <?php echo $acc['is_active'] ? 'zfl-zelle-active' : ''; ?>">
                    <div class="zfl-zelle-card-header">
                        <span class="zfl-zelle-badge"><?php echo $acc['is_active'] ? 'ACTIVA' : 'INACTIVA'; ?></span>
                        <div class="zfl-zelle-card-actions">
                            <?php if ( ! $acc['is_active'] ) : ?>
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field( 'zfl_zelle_action', 'zfl_zelle_nonce' ); ?>
                                    <input type="hidden" name="zfl_zelle_action" value="activate">
                                    <input type="hidden" name="account_id" value="<?php echo (int) $acc['id']; ?>">
                                    <button type="submit" class="zfl-zelle-activate-btn" title="Activar esta cuenta">Activar</button>
                                </form>
                            <?php endif; ?>
                            <a href="<?php echo esc_url( add_query_arg( 'edit', (int) $acc['id'], $tab_url ) ); ?>" class="zfl-zelle-edit-btn">Editar</a>
                            <form method="post" style="display:inline;">
                                <?php wp_nonce_field( 'zfl_zelle_action', 'zfl_zelle_nonce' ); ?>
                                <input type="hidden" name="zfl_zelle_action" value="delete">
                                <input type="hidden" name="account_id" value="<?php echo (int) $acc['id']; ?>">
                                <button type="submit" class="zfl-zelle-delete-btn" onclick="return confirm('¿Eliminar esta cuenta?');">Eliminar</button>
                            </form>
                        </div>
                    </div>
                    <div class="zfl-zelle-card-body">
                        <h3><?php echo esc_html( $acc['label'] ); ?></h3>
                        <div class="zfl-zelle-field">
                            <span class="zfl-zelle-field-label">Zelle</span>
                            <span class="zfl-zelle-field-value"><?php echo esc_html( $acc['phone_or_email'] ); ?></span>
                        </div>
                        <?php if ( $acc['holder_name'] ) : ?>
                        <div class="zfl-zelle-field">
                            <span class="zfl-zelle-field-label">Titular</span>
                            <span class="zfl-zelle-field-value"><?php echo esc_html( $acc['holder_name'] ); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ( $acc['payment_note'] ) : ?>
                        <div class="zfl-zelle-field">
                            <span class="zfl-zelle-field-label">Nota</span>
                            <span class="zfl-zelle-field-value zfl-zelle-note"><?php echo esc_html( $acc['payment_note'] ); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
