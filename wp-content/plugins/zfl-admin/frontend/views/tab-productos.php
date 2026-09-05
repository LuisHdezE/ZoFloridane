<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$result     = ZFL_Products::handle_request();
$search     = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
$page       = get_query_var( 'zfl_page' ) ? (int) get_query_var( 'zfl_page' ) : 1;
$category   = isset( $_GET['cat'] ) ? (int) $_GET['cat'] : 0;
$localidad  = isset( $_COOKIE['zfl_panel_loc'] ) ? (int) $_COOKIE['zfl_panel_loc'] : 0;
$listing    = ZFL_Products::list_products( $search, $page, 20, $category, $localidad );
$items      = $listing['items'];
$total      = $listing['total'];
$total_pg   = $listing['total_pages'];
$current    = $listing['page'];
$categories = ZFL_Products::get_categories();
$localidades = ZFL_Catalog::get_localidades();
?>
<h2 class="zfl-tab-title">Productos</h2>

<?php if ( is_wp_error( $result ) ) : ?>
    <div class="zfl-error"><?php echo esc_html( $result->get_error_message() ); ?></div>
<?php elseif ( is_array( $result ) && ! empty( $result['created'] ) ) : ?>
    <div class="zfl-success">Producto creado correctamente (#<?php echo (int) $result['created']; ?>).</div>
<?php elseif ( is_array( $result ) && ! empty( $result['updated'] ) ) : ?>
    <div class="zfl-success">Producto actualizado correctamente.</div>
<?php elseif ( is_array( $result ) && ! empty( $result['deleted'] ) ) : ?>
    <div class="zfl-success">Producto eliminado correctamente.</div>
<?php elseif ( is_array( $result ) && ! empty( $result['image_deleted'] ) ) : ?>
    <div class="zfl-success">Imagen del producto eliminada.</div>
<?php elseif ( is_array( $result ) && ! empty( $result['bulk'] ) ) : ?>
    <div class="zfl-success">Acción masiva "<?php echo esc_html( $result['bulk']['action'] ); ?>" aplicada a <?php echo (int) $result['bulk']['count']; ?> producto(s).</div>
<?php endif; ?>

<form method="get" class="zfl-search">
    <input type="hidden" name="tab" value="productos">
    <input type="text" name="q" value="<?php echo esc_attr( $search ); ?>" placeholder="Buscar por nombre">
    <select name="cat">
        <option value="0">Todas las categorías</option>
        <?php foreach ( $categories as $cat ) : ?>
            <option value="<?php echo (int) $cat['id']; ?>" <?php selected( $category, $cat['id'] ); ?>><?php echo esc_html( $cat['name'] ); ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Filtrar</button>
</form>

<p class="zfl-meta">
    <?php echo (int) $total; ?> producto(s) encontrado(s).
    <?php if ( $localidad > 0 ) :
        $current_loc = null;
        foreach ( $localidades as $loc ) {
            if ( (int) $loc['id'] === $localidad ) { $current_loc = $loc['name']; break; }
        }
        if ( $current_loc ) :
    ?>
        — mostrando solo <strong><?php echo esc_html( $current_loc ); ?></strong> (cambia la localidad arriba)
    <?php endif; endif; ?>
</p>

<details class="zfl-new">
    <summary>Añadir nuevo producto</summary>
    <form method="post" class="zfl-new-form" enctype="multipart/form-data">
        <?php wp_nonce_field( 'zfl_product_action', 'zfl_product_nonce' ); ?>
        <input type="hidden" name="zfl_product_action" value="create">
        <label>Nombre *
            <input type="text" name="name" required>
        </label>
        <label>Precio de venta (CUP) *
            <input type="text" name="sale_price" required>
        </label>
        <label>Precio de compra (CUP)
            <input type="number" name="cost_price" min="0" step="1">
        </label>
        <label>Tasa al comprar (CUP/USD) — opcional
            <input type="number" name="cost_rate" min="0" step="0.01">
            <small>Solo si compraste en dólares. Vacía = no se toma en cuenta.</small>
        </label>
        <label>Stock
            <input type="number" name="stock_quantity" min="0" value="0">
        </label>
        <label>Estado
            <select name="status">
                <option value="publish">Publicado</option>
                <option value="draft">Borrador</option>
                <option value="pending">Pendiente</option>
            </select>
        </label>
        <label>Localidad
            <select name="localidad">
                <option value="0">— Sin localidad —</option>
                <?php foreach ( $localidades as $loc ) : ?>
                    <option value="<?php echo (int) $loc['id']; ?>" <?php selected( $localidad, $loc['id'] ); ?>><?php echo esc_html( $loc['name'] ); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="zfl-full">Imagen del producto
            <input type="file" name="product_image" accept="image/*">
            <small>JPG, PNG o WebP. Máx 4MB.</small>
        </label>
        <label class="zfl-full">Categorías
            <select name="categories[]" multiple size="4">
                <?php foreach ( $categories as $cat ) : ?>
                    <option value="<?php echo (int) $cat['id']; ?>"><?php echo esc_html( $cat['name'] ); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" class="zfl-btn-primary">Crear producto</button>
    </form>
</details>

<form method="post" id="zfl-bulk-form" enctype="multipart/form-data">
    <?php wp_nonce_field( 'zfl_product_action', 'zfl_product_nonce' ); ?>
    <input type="hidden" name="zfl_product_action" value="bulk">

    <div class="zfl-bulk-bar">
        <select name="zfl_bulk_action">
            <option value="">Acciones en lote</option>
            <option value="publish">Publicar</option>
            <option value="draft">Mover a borrador</option>
            <option value="delete">Eliminar</option>
        </select>
        <button type="submit" onclick="return confirm('¿Aplicar acción a los productos seleccionados?');">Aplicar</button>
    </div>

    <div class="zfl-card">
        <table class="zfl-table">
            <thead>
                <tr>
                    <th class="zfl-col-check"><input type="checkbox" id="zfl-check-all"></th>
                    <th>Imagen</th>
                    <th>Nombre</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Estado</th>
                    <th>Categoría</th>
                    <th>Localidad</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if ( empty( $items ) ) : ?>
                <tr><td colspan="9">No hay productos para mostrar.</td></tr>
            <?php else : ?>
                <?php foreach ( $items as $item ) :
                    $product_cats = wp_get_post_terms( $item['id'], 'product_cat', array( 'fields' => 'ids' ) );
                    if ( is_wp_error( $product_cats ) ) {
                        $product_cats = array();
                    }
                    $loc_id = ! empty( $item['loc_ids'] ) ? (int) $item['loc_ids'][0] : 0;
                ?>
                    <tr class="zfl-product-row"
                        data-product-id="<?php echo (int) $item['id']; ?>"
                        data-name="<?php echo esc_attr( $item['name'] ); ?>"
                        data-price="<?php echo esc_attr( $item['price'] ); ?>"
                        data-sale="<?php echo esc_attr( $item['sale_price'] ); ?>"
                        data-cost-price="<?php echo esc_attr( $item['cost_price'] ); ?>"
                        data-cost-rate="<?php echo esc_attr( $item['cost_rate'] ); ?>"
                        data-stock="<?php echo esc_attr( $item['stock'] ?? '' ); ?>"
                        data-status="<?php echo esc_attr( $item['status'] ); ?>"
                        data-thumb="<?php echo esc_url( $item['thumb'] ); ?>"
                        data-loc="<?php echo (int) $loc_id; ?>"
                        data-cats="<?php echo esc_attr( implode( ',', $product_cats ) ); ?>">
                        <td><input type="checkbox" name="product_ids[]" value="<?php echo (int) $item['id']; ?>" class="zfl-row-check"></td>
                        <td class="zfl-thumb">
                            <?php if ( $item['thumb'] ) : ?>
                                <button type="button" class="zfl-thumb-button" data-large="<?php echo esc_url( $item['large'] ?: $item['thumb'] ); ?>" data-name="<?php echo esc_attr( $item['name'] ); ?>">
                                    <img src="<?php echo esc_url( $item['thumb'] ); ?>" alt="">
                                </button>
                            <?php else : ?>
                                <span class="zfl-thumb-empty">Sin imagen</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo esc_html( $item['name'] ); ?></strong></td>
                        <td><?php echo $item['price'] !== '' ? wp_kses_post( wc_price( (float) $item['price'] ) ) : '—'; ?></td>
                        <td><?php echo $item['stock'] === null ? '—' : (int) $item['stock']; ?></td>
                        <td>
                            <?php
                            $labels = array( 'publish' => 'Publicado', 'draft' => 'Borrador', 'pending' => 'Pendiente' );
                            echo esc_html( $labels[ $item['status'] ] ?? $item['status'] );
                            ?>
                        </td>
                        <td><?php echo esc_html( $item['categories'] ?: '—' ); ?></td>
                        <td><?php echo esc_html( $item['localidad'] ?: '—' ); ?></td>
                        <td>
                            <div class="zfl-row-btns">
                                <button type="button" class="zfl-edit-trigger">Editar</button>
                                <form method="post" class="zfl-product-delete-form" style="display:inline;">
                                    <?php wp_nonce_field( 'zfl_product_action', 'zfl_product_nonce' ); ?>
                                    <input type="hidden" name="zfl_product_action" value="delete">
                                    <input type="hidden" name="product_id" value="<?php echo (int) $item['id']; ?>">
                                    <button type="submit" class="zfl-delete-trigger" onclick="return confirm('¿Eliminar este producto?');">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Cards móviles -->
    <div class="zfl-mobile-cards">
    <?php if ( empty( $items ) ) : ?>
        <p class="zfl-empty-msg">No hay productos para mostrar.</p>
    <?php else : ?>
        <?php foreach ( $items as $item ) :
            $product_cats = wp_get_post_terms( $item['id'], 'product_cat', array( 'fields' => 'ids' ) );
            if ( is_wp_error( $product_cats ) ) {
                $product_cats = array();
            }
            $loc_id = ! empty( $item['loc_ids'] ) ? (int) $item['loc_ids'][0] : 0;
        ?>
            <div class="zfl-mcard"
                data-product-id="<?php echo (int) $item['id']; ?>"
                data-name="<?php echo esc_attr( $item['name'] ); ?>"
                data-price="<?php echo esc_attr( $item['price'] ); ?>"
                data-sale="<?php echo esc_attr( $item['sale_price'] ); ?>"
                data-cost-price="<?php echo esc_attr( $item['cost_price'] ); ?>"
                data-cost-rate="<?php echo esc_attr( $item['cost_rate'] ); ?>"
                data-stock="<?php echo esc_attr( $item['stock'] ?? '' ); ?>"
                data-status="<?php echo esc_attr( $item['status'] ); ?>"
                data-thumb="<?php echo esc_url( $item['thumb'] ); ?>"
                data-loc="<?php echo (int) $loc_id; ?>"
                data-cats="<?php echo esc_attr( implode( ',', $product_cats ) ); ?>">

                <div class="zfl-mcard-top">
                    <input type="checkbox" name="product_ids[]" value="<?php echo (int) $item['id']; ?>" class="zfl-row-check zfl-mcard-check">
                    <?php if ( $item['thumb'] ) : ?>
                        <button type="button" class="zfl-mcard-img zfl-thumb-button" data-large="<?php echo esc_url( $item['large'] ?: $item['thumb'] ); ?>" data-name="<?php echo esc_attr( $item['name'] ); ?>">
                            <img src="<?php echo esc_url( $item['thumb'] ); ?>" alt="">
                        </button>
                    <?php else : ?>
                        <div class="zfl-mcard-img zfl-thumb-empty">Sin imagen</div>
                    <?php endif; ?>
                    <div class="zfl-mcard-info">
                        <span class="zfl-mcard-name"><?php echo esc_html( $item['name'] ); ?></span>
                        <span class="zfl-mcard-price"><?php echo $item['price'] !== '' ? wp_kses_post( wc_price( (float) $item['price'] ) ) : '—'; ?></span>
                        <?php
                        $labels = array( 'publish' => 'Publicado', 'draft' => 'Borrador', 'pending' => 'Pendiente' );
                        $status_text = $labels[ $item['status'] ] ?? $item['status'];
                        ?>
                        <span class="zfl-mcard-status zfl-status-<?php echo esc_attr( $item['status'] ); ?>"><?php echo esc_html( $status_text ); ?></span>
                        <?php if ( $item['localidad'] ) : ?>
                            <span class="zfl-mcard-loc"><?php echo esc_html( $item['localidad'] ); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="zfl-mcard-btns">
                    <button type="button" class="zfl-edit-trigger zfl-mcard-edit">Editar</button>
                    <form method="post" class="zfl-product-delete-form" style="display:inline;">
                        <?php wp_nonce_field( 'zfl_product_action', 'zfl_product_nonce' ); ?>
                        <input type="hidden" name="zfl_product_action" value="delete">
                        <input type="hidden" name="product_id" value="<?php echo (int) $item['id']; ?>">
                        <button type="submit" class="zfl-delete-trigger" onclick="return confirm('¿Eliminar este producto?');">Eliminar</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>
</form>

<?php if ( $total_pg > 1 ) : ?>
    <div class="zfl-pagination">
        <?php for ( $i = 1; $i <= $total_pg; $i++ ) : ?>
            <?php
            $page_args = array( 'tab' => 'productos', 'q' => $search, 'cat' => $category );
            if ( $i === 1 ) {
                $link = add_query_arg( $page_args, home_url( ZFL_SLUG . '/catalogo/' ) );
            } else {
                $link = add_query_arg( $page_args, home_url( ZFL_SLUG . '/catalogo/page/' . $i . '/' ) );
            }
            ?>
            <a class="zfl-page <?php echo $i === $current ? 'active' : ''; ?>" href="<?php echo esc_url( $link ); ?>">
                <?php echo (int) $i; ?>
            </a>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<!-- Modal de edición -->
<div class="zfl-modal-overlay" id="zfl-modal-overlay" hidden></div>

<div class="zfl-modal" id="zfl-modal" hidden>
    <div class="zfl-modal-header">
        <h2>Editar producto</h2>
        <button type="button" class="zfl-modal-close" aria-label="Cerrar"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
    </div>
    <form method="post" class="zfl-modal-form" enctype="multipart/form-data">
        <?php wp_nonce_field( 'zfl_product_action', 'zfl_product_nonce' ); ?>
        <input type="hidden" name="zfl_product_action" value="update">
        <input type="hidden" name="product_id" value="">
        <input type="hidden" name="zfl_remove_image" class="zfl-modal-remove-img" value="">

        <div class="zfl-modal-body">

            <div class="zfl-modal-img-section">
                <img class="zfl-modal-img-preview" src="" alt="" style="display:none;">
                <div class="zfl-modal-img-placeholder">Sin imagen</div>
                <p class="zfl-modal-img-name" style="margin:0;font-size:12px;color:#6b7280;"></p>
                <div class="zfl-modal-img-actions">
                    <label class="zfl-modal-img-upload">
                        Cambiar imagen
                        <input type="file" name="product_image" accept="image/*">
                    </label>
                    <button type="button" class="zfl-modal-img-remove">Quitar imagen</button>
                </div>
            </div>

            <div class="zfl-modal-fields">
                <label class="zfl-full">Nombre
                    <input type="text" name="name" required>
                </label>
                <label>Precio de venta (CUP)
                    <input type="text" name="sale_price">
                </label>
                <label>Precio de compra (CUP)
                    <input type="number" name="cost_price" min="0" step="1">
                </label>
                <label>Tasa al comprar (CUP/USD) — opcional
                    <input type="number" name="cost_rate" min="0" step="0.01">
                    <small>Solo si compraste en dólares. Vacía = no se toma en cuenta.</small>
                </label>
                <label>Stock
                    <input type="number" name="stock_quantity" min="0">
                </label>
                <label>Estado
                    <select name="status">
                        <option value="publish">Publicado</option>
                        <option value="draft">Borrador</option>
                        <option value="pending">Pendiente</option>
                    </select>
                </label>
                <label>Localidad
                    <select name="localidad">
                        <option value="0">— Sin localidad —</option>
                        <?php foreach ( $localidades as $loc ) : ?>
                            <option value="<?php echo (int) $loc['id']; ?>"><?php echo esc_html( $loc['name'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="zfl-full">Categorías
                    <select name="categories[]" multiple size="4">
                        <?php foreach ( $categories as $cat ) : ?>
                            <option value="<?php echo (int) $cat['id']; ?>"><?php echo esc_html( $cat['name'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

        </div>
        <div class="zfl-modal-footer">
            <button type="button" class="zfl-modal-cancel">Cancelar</button>
            <button type="submit" class="zfl-modal-save">Guardar cambios</button>
        </div>
    </form>
</div>
