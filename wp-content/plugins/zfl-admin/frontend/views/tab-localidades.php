<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$result = isset( $catalog_result ) ? $catalog_result : null;
$locs   = ZFL_Catalog::get_localidades();
$editing_loc = isset( $_GET['edit_loc'] ) ? (int) $_GET['edit_loc'] : 0;
$edit_loc    = $editing_loc ? ZFL_Catalog::get_localidad( $editing_loc ) : null;
$tab_url     = home_url( ZFL_SLUG . '/catalogo/?tab=localidades' );
?>
<h2 class="zfl-tab-title">Localidades</h2>

<?php if ( is_wp_error( $result ) ) : ?>
    <div class="zfl-error"><?php echo esc_html( $result->get_error_message() ); ?></div>
<?php elseif ( is_array( $result ) && ! empty( $result['loc_created'] ) ) : ?>
    <div class="zfl-success">Localidad creada correctamente.</div>
<?php elseif ( is_array( $result ) && ! empty( $result['loc_updated'] ) ) : ?>
    <div class="zfl-success">Localidad actualizada correctamente.</div>
<?php elseif ( is_array( $result ) && ! empty( $result['loc_deleted'] ) ) : ?>
    <div class="zfl-success">Localidad eliminada correctamente.</div>
<?php endif; ?>

<p class="zfl-meta">Destinos de entrega en Cuba. Cada producto pertenece a una localidad y los clientes solo ven la de su elección.</p>

<?php if ( $edit_loc ) : ?>
    <div class="zfl-zelle-form-wrap">
        <h3>Editar localidad: <?php echo esc_html( $edit_loc['name'] ); ?></h3>
        <form method="post" class="zfl-zelle-form">
            <?php wp_nonce_field( 'zfl_catalog_action', 'zfl_cat_nonce' ); ?>
            <input type="hidden" name="zfl_catalog_action" value="loc_update">
            <input type="hidden" name="loc_id" value="<?php echo (int) $edit_loc['id']; ?>">

            <label>Nombre *
                <input type="text" name="name" value="<?php echo esc_attr( $edit_loc['name'] ); ?>" required>
            </label>
            <label>Nota de entrega (opcional)
                <textarea name="note" rows="2" placeholder="Ej: Entregas de lunes a viernes"><?php echo esc_textarea( $edit_loc['note'] ); ?></textarea>
            </label>

            <div class="zfl-zelle-form-btns">
                <button type="submit" class="zfl-btn-primary">Guardar cambios</button>
                <a href="<?php echo esc_url( $tab_url ); ?>" class="zfl-btn-cancel">Cancelar</a>
            </div>
        </form>
    </div>

<?php else : ?>
    <details class="zfl-zelle-new">
        <summary>Agregar localidad</summary>
        <form method="post" class="zfl-zelle-form">
            <?php wp_nonce_field( 'zfl_catalog_action', 'zfl_cat_nonce' ); ?>
            <input type="hidden" name="zfl_catalog_action" value="loc_create">

            <label>Nombre *
                <input type="text" name="name" placeholder="Ej: La Habana" required>
            </label>
            <label>Nota de entrega (opcional)
                <textarea name="note" rows="2" placeholder="Ej: Entregas de lunes a viernes"></textarea>
            </label>

            <button type="submit" class="zfl-btn-primary">Crear localidad</button>
        </form>
    </details>

    <?php if ( empty( $locs ) ) : ?>
        <div class="zfl-zelle-empty">
            <p>No hay localidades configuradas.</p>
            <p>Agrega la primera (ej: La Habana) para poder asignar productos.</p>
        </div>
    <?php else : ?>
        <div class="zfl-loc-list">
            <?php foreach ( $locs as $loc ) : ?>
                <div class="zfl-loc-row">
                    <span class="zfl-loc-name"><?php echo esc_html( $loc['name'] ); ?></span>
                    <?php if ( $loc['note'] ) : ?>
                        <span class="zfl-loc-note"><?php echo esc_html( $loc['note'] ); ?></span>
                    <?php endif; ?>
                    <span class="zfl-loc-count"><?php echo (int) $loc['count']; ?> producto(s)</span>
                    <div class="zfl-cat-btns">
                        <a class="zfl-zelle-edit-btn" href="<?php echo esc_url( add_query_arg( 'edit_loc', (int) $loc['id'], $tab_url ) ); ?>">Editar</a>
                        <form method="post" style="display:inline;">
                            <?php wp_nonce_field( 'zfl_catalog_action', 'zfl_cat_nonce' ); ?>
                            <input type="hidden" name="zfl_catalog_action" value="loc_delete">
                            <input type="hidden" name="loc_id" value="<?php echo (int) $loc['id']; ?>">
                            <button type="submit" class="zfl-zelle-delete-btn" onclick="return confirm('¿Eliminar la localidad \'<?php echo esc_js( $loc['name'] ); ?>\'? Los productos quedarán sin localidad.');">Eliminar</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
