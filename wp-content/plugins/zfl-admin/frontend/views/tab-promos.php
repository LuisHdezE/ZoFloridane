<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$result = ZFL_Promos::handle_request();
$promos = ZFL_Promos::get_all();
$editing_promo = isset( $_GET['edit_promo'] ) ? (int) $_GET['edit_promo'] : 0;
$edit_promo    = $editing_promo ? ZFL_Promos::get( $editing_promo ) : null;
$tab_url       = home_url( ZFL_SLUG . '/catalogo/?tab=promos' );
?>
<h2 class="zfl-tab-title">Promos</h2>

<?php if ( is_wp_error( $result ) ) : ?>
    <div class="zfl-error"><?php echo esc_html( $result->get_error_message() ); ?></div>
<?php elseif ( is_array( $result ) && ! empty( $result['promo_created'] ) ) : ?>
    <div class="zfl-success">Promo creada correctamente.</div>
<?php elseif ( is_array( $result ) && ! empty( $result['promo_updated'] ) ) : ?>
    <div class="zfl-success">Promo actualizada correctamente.</div>
<?php elseif ( is_array( $result ) && ! empty( $result['promo_deleted'] ) ) : ?>
    <div class="zfl-success">Promo eliminada correctamente.</div>
<?php endif; ?>

<p class="zfl-meta">Banners del carrusel de la página principal. Las activas se muestran en el orden indicado.</p>

<?php if ( $edit_promo ) : ?>
    <div class="zfl-zelle-form-wrap">
        <h3>Editar promo</h3>
        <form method="post" class="zfl-zelle-form" enctype="multipart/form-data">
            <?php wp_nonce_field( 'zfl_promo_action', 'zfl_promo_nonce' ); ?>
            <input type="hidden" name="zfl_promo_action" value="promo_update">
            <input type="hidden" name="promo_id" value="<?php echo (int) $edit_promo['id']; ?>">

            <label>Título (opcional)
                <input type="text" name="title" value="<?php echo esc_attr( $edit_promo['title'] ); ?>" placeholder="Ej: Rebajas de agosto">
            </label>
            <label>Enlace al hacer clic (opcional)
                <input type="url" name="link" value="<?php echo esc_attr( $edit_promo['link'] ); ?>" placeholder="https://zofloridane.com/tienda/">
            </label>

            <label>Imagen actual / nueva
                <?php
                $edit_img = (int) $edit_promo['image_id'] ? wp_get_attachment_image_url( (int) $edit_promo['image_id'], 'thumbnail' ) : '';
                if ( $edit_img ) :
                    ?>
                    <img src="<?php echo esc_url( $edit_img ); ?>" alt="" style="width:80px;height:48px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb;">
                <?php else : ?>
                    <span style="font-size:12px;color:#94a3b8;">Sin imagen</span>
                <?php endif; ?>
                <input type="file" name="promo_image" accept="image/*">
            </label>

            <label class="zfl-zelle-active-check">
                <input type="checkbox" name="is_active" value="1" <?php checked( $edit_promo['is_active'], 1 ); ?>>
                Activa (se muestra en el carrusel)
            </label>

            <div class="zfl-zelle-form-btns">
                <button type="submit" class="zfl-btn-primary">Guardar cambios</button>
                <a href="<?php echo esc_url( $tab_url ); ?>" class="zfl-btn-cancel">Cancelar</a>
            </div>
        </form>
    </div>

<?php else : ?>
    <details class="zfl-zelle-new">
        <summary>Agregar promo</summary>
        <form method="post" class="zfl-zelle-form" enctype="multipart/form-data">
            <?php wp_nonce_field( 'zfl_promo_action', 'zfl_promo_nonce' ); ?>
            <input type="hidden" name="zfl_promo_action" value="promo_create">

            <label>Título (opcional)
                <input type="text" name="title" placeholder="Ej: Rebajas de agosto">
            </label>
            <label>Enlace al hacer clic (opcional)
                <input type="url" name="link" placeholder="https://zofloridane.com/tienda/">
            </label>
            <label class="zfl-full">Imagen del banner *
                <input type="file" name="promo_image" accept="image/*" required>
                <small>Recomendado: 1200×420px o similar (horizontal).</small>
            </label>
            <label class="zfl-zelle-active-check">
                <input type="checkbox" name="is_active" value="1" checked>
                Activa (se muestra en el carrusel)
            </label>

            <button type="submit" class="zfl-btn-primary">Crear promo</button>
        </form>
    </details>

    <?php if ( empty( $promos ) ) : ?>
        <div class="zfl-zelle-empty">
            <p>No hay promos todavía.</p>
            <p>Sube banners y aparecerán en el carrusel de la página principal.</p>
        </div>
    <?php else : ?>
        <div class="zfl-loc-list">
            <?php foreach ( $promos as $i => $promo ) :
                $thumb = (int) $promo['image_id'] ? wp_get_attachment_image_url( (int) $promo['image_id'], 'thumbnail' ) : '';
                ?>
                <div class="zfl-loc-row">
                    <?php if ( $thumb ) : ?>
                        <img src="<?php echo esc_url( $thumb ); ?>" alt="" style="width:72px;height:44px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb;">
                    <?php else : ?>
                        <span class="zfl-loc-count">Sin imagen</span>
                    <?php endif; ?>

                    <span class="zfl-loc-name"><?php echo esc_html( $promo['title'] !== '' ? $promo['title'] : 'Promo #' . $promo['id'] ); ?></span>

                    <?php if ( $promo['link'] ) : ?>
                        <span class="zfl-loc-note"><?php echo esc_html( wp_trim_words( $promo['link'], 6, '…' ) ); ?></span>
                    <?php endif; ?>

                    <span class="zfl-loc-count"><?php echo $promo['is_active'] ? 'ACTIVA' : 'Oculta'; ?></span>

                    <div class="zfl-cat-btns">
                        <?php if ( $i > 0 ) : ?>
                            <form method="post" style="display:inline;">
                                <?php wp_nonce_field( 'zfl_promo_action', 'zfl_promo_nonce' ); ?>
                                <input type="hidden" name="zfl_promo_action" value="promo_move">
                                <input type="hidden" name="promo_id" value="<?php echo (int) $promo['id']; ?>">
                                <input type="hidden" name="direction" value="up">
                                <button type="submit" class="zfl-zelle-edit-btn" title="Subir">&uarr;</button>
                            </form>
                        <?php endif; ?>
                        <?php if ( $i < count( $promos ) - 1 ) : ?>
                            <form method="post" style="display:inline;">
                                <?php wp_nonce_field( 'zfl_promo_action', 'zfl_promo_nonce' ); ?>
                                <input type="hidden" name="zfl_promo_action" value="promo_move">
                                <input type="hidden" name="promo_id" value="<?php echo (int) $promo['id']; ?>">
                                <input type="hidden" name="direction" value="down">
                                <button type="submit" class="zfl-zelle-edit-btn" title="Bajar">&darr;</button>
                            </form>
                        <?php endif; ?>
                        <a class="zfl-zelle-edit-btn" href="<?php echo esc_url( add_query_arg( 'edit_promo', (int) $promo['id'], $tab_url ) ); ?>">Editar</a>
                        <form method="post" style="display:inline;">
                            <?php wp_nonce_field( 'zfl_promo_action', 'zfl_promo_nonce' ); ?>
                            <input type="hidden" name="zfl_promo_action" value="promo_delete">
                            <input type="hidden" name="promo_id" value="<?php echo (int) $promo['id']; ?>">
                            <button type="submit" class="zfl-zelle-delete-btn" onclick="return confirm('¿Eliminar esta promo? La imagen también se borra.');">Eliminar</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
