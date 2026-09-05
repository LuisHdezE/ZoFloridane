<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$result = isset( $catalog_result ) ? $catalog_result : null;
$cats   = ZFL_Catalog::get_categories_detailed();
$editing_cat = isset( $_GET['edit_cat'] ) ? (int) $_GET['edit_cat'] : 0;
$edit_cat    = $editing_cat ? ZFL_Catalog::get_category( $editing_cat ) : null;
$tab_url     = home_url( ZFL_SLUG . '/catalogo/?tab=categorias' );
?>
<h2 class="zfl-tab-title">Categorías</h2>

<?php if ( is_wp_error( $result ) ) : ?>
    <div class="zfl-error"><?php echo esc_html( $result->get_error_message() ); ?></div>
<?php elseif ( is_array( $result ) && ! empty( $result['cat_created'] ) ) : ?>
    <div class="zfl-success">Categoría creada correctamente.</div>
<?php elseif ( is_array( $result ) && ! empty( $result['cat_updated'] ) ) : ?>
    <div class="zfl-success">Categoría actualizada correctamente.</div>
<?php elseif ( is_array( $result ) && ! empty( $result['cat_deleted'] ) ) : ?>
    <div class="zfl-success">Categoría eliminada correctamente.</div>
<?php endif; ?>

<p class="zfl-meta">Limpia, edita o crea categorías. La imagen es opcional y se muestra en la tienda.</p>

<?php if ( $edit_cat ) : ?>
    <div class="zfl-zelle-form-wrap">
        <h3>Editar categoría: <?php echo esc_html( $edit_cat['name'] ); ?></h3>
        <form method="post" class="zfl-zelle-form" enctype="multipart/form-data">
            <?php wp_nonce_field( 'zfl_catalog_action', 'zfl_cat_nonce' ); ?>
            <input type="hidden" name="zfl_catalog_action" value="cat_update">
            <input type="hidden" name="cat_id" value="<?php echo (int) $edit_cat['id']; ?>">

            <label>Nombre *
                <input type="text" name="name" value="<?php echo esc_attr( $edit_cat['name'] ); ?>" required>
            </label>

            <label>Imagen actual / nueva
                <?php if ( $edit_cat['thumb'] ) : ?>
                    <img src="<?php echo esc_url( $edit_cat['thumb'] ); ?>" alt="" style="width:64px;height:64px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;">
                <?php else : ?>
                    <span style="font-size:12px;color:#94a3b8;">Sin imagen</span>
                <?php endif; ?>
                <input type="file" name="term_image" accept="image/*">
            </label>

            <label class="zfl-zelle-active-check">
                <input type="checkbox" name="remove_image" value="1">
                Quitar imagen actual
            </label>

            <div class="zfl-zelle-form-btns">
                <button type="submit" class="zfl-btn-primary">Guardar cambios</button>
                <a href="<?php echo esc_url( $tab_url ); ?>" class="zfl-btn-cancel">Cancelar</a>
            </div>
        </form>
    </div>

<?php else : ?>
    <details class="zfl-zelle-new">
        <summary>Agregar categoría</summary>
        <form method="post" class="zfl-zelle-form" enctype="multipart/form-data">
            <?php wp_nonce_field( 'zfl_catalog_action', 'zfl_cat_nonce' ); ?>
            <input type="hidden" name="zfl_catalog_action" value="cat_create">

            <label>Nombre *
                <input type="text" name="name" placeholder="Ej: Perfumería" required>
            </label>
            <label>Imagen (opcional)
                <input type="file" name="term_image" accept="image/*">
            </label>

            <button type="submit" class="zfl-btn-primary">Crear categoría</button>
        </form>
    </details>

    <?php if ( empty( $cats ) ) : ?>
        <div class="zfl-zelle-empty">
            <p>No hay categorías.</p>
        </div>
    <?php else : ?>
        <div class="zfl-cat-grid">
            <?php foreach ( $cats as $cat ) : ?>
                <div class="zfl-cat-card">
                    <?php if ( $cat['thumb'] ) : ?>
                        <button type="button" class="zfl-cat-thumb zfl-thumb-button" data-large="<?php echo esc_url( $cat['large'] ?: $cat['thumb'] ); ?>" data-name="<?php echo esc_attr( $cat['name'] ); ?>">
                            <img src="<?php echo esc_url( $cat['thumb'] ); ?>" alt="">
                        </button>
                    <?php else : ?>
                        <span class="zfl-cat-thumb zfl-thumb-empty">—</span>
                    <?php endif; ?>
                    <div class="zfl-cat-info">
                        <span class="zfl-cat-name" title="<?php echo esc_attr( $cat['name'] ); ?>"><?php echo esc_html( $cat['name'] ); ?></span>
                        <span class="zfl-cat-count"><?php echo (int) $cat['count']; ?> producto(s)</span>
                    </div>
                    <div class="zfl-cat-btns">
                        <a class="zfl-zelle-edit-btn" href="<?php echo esc_url( add_query_arg( 'edit_cat', (int) $cat['id'], $tab_url ) ); ?>">Editar</a>
                        <form method="post" style="display:inline;">
                            <?php wp_nonce_field( 'zfl_catalog_action', 'zfl_cat_nonce' ); ?>
                            <input type="hidden" name="zfl_catalog_action" value="cat_delete">
                            <input type="hidden" name="cat_id" value="<?php echo (int) $cat['id']; ?>">
                            <button type="submit" class="zfl-zelle-delete-btn" onclick="return confirm('¿Eliminar la categoría \'<?php echo esc_js( $cat['name'] ); ?>\'? Los <?php echo (int) $cat['count']; ?> producto(s) asociados NO se borran.');">Eliminar</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
