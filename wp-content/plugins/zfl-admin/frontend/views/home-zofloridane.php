<?php
/**
 * Template Name: Home ZoFloridane
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$loc_id   = class_exists( 'ZFL_Store' ) ? ZFL_Store::get_current_localidad() : 0;
$loc_name = class_exists( 'ZFL_Store' ) ? ZFL_Store::get_current_localidad_name() : '';
$cats     = function_exists( 'zfl_storefront_get_categories' ) ? zfl_storefront_get_categories( 8, true ) : array();

$raw_promos = class_exists( 'ZFL_Promos' ) ? ZFL_Promos::get_active() : array();
$promos     = array();
foreach ( (array) $raw_promos as $promo ) {
    $image_id = isset( $promo['image_id'] ) ? (int) $promo['image_id'] : 0;
    $image    = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '';
    if ( ! $image ) {
        continue;
    }
    $promo['_image_url'] = $image;
    $promos[]            = $promo;
}

$filter_by_locality = static function ( $products ) use ( $loc_id ) {
    if ( $loc_id <= 0 ) {
        return array_values( (array) $products );
    }

    return array_values( array_filter( (array) $products, static function ( $product ) use ( $loc_id ) {
        if ( ! $product ) {
            return false;
        }
        $ids = wp_get_post_terms( $product->get_id(), 'zfl_localidad', array( 'fields' => 'ids' ) );
        return ! is_wp_error( $ids ) && in_array( $loc_id, array_map( 'intval', (array) $ids ), true );
    } ) );
};

$best_sellers = array();
if ( function_exists( 'wc_get_products' ) ) {
    $best_sellers = wc_get_products( array(
        'limit'   => 48,
        'status'  => 'publish',
        'orderby' => 'popularity',
        'order'   => 'DESC',
    ) );
    $best_sellers = array_slice( $filter_by_locality( $best_sellers ), 0, 8 );
}

$render_product_card = static function ( $product ) {
    if ( ! $product ) {
        return;
    }

    $id         = $product->get_id();
    $image      = $product->get_image_id() ? wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' ) : wc_placeholder_img_src();
    $out        = ! $product->is_in_stock();
    $is_popular = $product->get_total_sales() >= 5;
    ?>
    <article class="zfh-card<?php echo $out ? ' zfh-card-out' : ''; ?>">
        <a class="zfh-card-img" href="<?php echo esc_url( $product->get_permalink() ); ?>">
            <img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" loading="lazy">
            <?php if ( $out ) : ?><span class="zfh-agotado">Agotado</span><?php endif; ?>
        </a>
        <?php if ( $is_popular ) : ?>
            <div class="zfh-card-badges"><span class="zfh-badge zfh-badge-hot">Más vendido</span></div>
        <?php endif; ?>
        <div class="zfh-card-body">
            <a class="zfh-card-name" href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
            <span class="zfh-card-price zfh-price" data-price-cup="<?php echo esc_attr( (float) $product->get_price() ); ?>"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
            <?php if ( $product->is_type( 'simple' ) && $product->is_in_stock() ) : ?>
                <a class="zfh-card-add zfh-add"
                   href="<?php echo esc_url( add_query_arg( 'add-to-cart', $id, $product->get_permalink() ) ); ?>"
                   data-product-id="<?php echo (int) $id; ?>"
                   data-product-name="<?php echo esc_attr( $product->get_name() ); ?>">Añadir al carrito</a>
            <?php else : ?>
                <a class="zfh-card-add zfh-card-secondary" href="<?php echo esc_url( $product->get_permalink() ); ?>">Ver producto</a>
            <?php endif; ?>
        </div>
    </article>
    <?php
};
?>

<?php if ( class_exists( 'ZFL_Currencies' ) ) : ?>
<script>window.zfhRates = <?php echo wp_json_encode( ZFL_Currencies::get_rates() ); ?>;</script>
<?php endif; ?>

<main class="zfh" id="contenido-principal">
    <section class="zfh-hero-grid" aria-label="Promociones principales">
        <div class="zfh-hero-main">
            <?php if ( ! empty( $promos ) ) : ?>
                <div class="zfh-carousel" id="zfhCarousel">
                    <div class="zfh-track">
                        <?php foreach ( $promos as $index => $promo ) :
                            $title       = ! empty( $promo['title'] ) ? $promo['title'] : 'Compra desde EE. UU. y entrégalo en Cuba';
                            $target_url  = ! empty( $promo['link'] ) ? $promo['link'] : $shop_url;
                            ?>
                            <div class="zfh-slide" aria-label="Promoción <?php echo (int) $index + 1; ?>">
                                <img src="<?php echo esc_url( $promo['_image_url'] ); ?>" alt="<?php echo esc_attr( $title ); ?>" <?php echo 0 === $index ? 'fetchpriority="high"' : 'loading="lazy"'; ?>>
                                <span class="zfh-slide-shade" aria-hidden="true"></span>
                                <div class="zfh-slide-copy">
                                    <small>ZoFloridane</small>
                                    <strong><?php echo esc_html( $title ); ?></strong>
                                    <em><?php echo $loc_name ? 'Productos disponibles para ' . esc_html( $loc_name ) : 'Selecciona una localidad y descubre qué podemos entregar'; ?></em>
                                    <a class="zfh-slide-cta" href="<?php echo esc_url( $target_url ); ?>">Ver productos</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="zfh-nav zfh-prev" aria-label="Promoción anterior"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg></button>
                    <button type="button" class="zfh-nav zfh-next" aria-label="Promoción siguiente"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg></button>
                    <div class="zfh-dots" aria-label="Seleccionar promoción"></div>
                </div>
            <?php else : ?>
                <div class="zfh-hero-fallback">
                    <span class="zfh-kicker">ZoFloridane</span>
                    <h1>Compra desde EE. UU.<br>y entrégalo en Cuba.</h1>
                    <p><?php echo $loc_name ? 'Compra productos disponibles para ' . esc_html( $loc_name ) . ' con un proceso claro y acompañado.' : 'Elige la localidad, compra tus productos y nosotros coordinamos la entrega.'; ?></p>
                    <a href="<?php echo esc_url( $shop_url ); ?>">Ver productos</a>
                </div>
            <?php endif; ?>
        </div>

        <aside class="zfh-hero-side" aria-label="Información de compra">
            <div class="zfh-side-card zfh-side-zelle">
                <span class="zfh-side-icon" aria-hidden="true">Z</span>
                <div>
                    <small>PAGO SIMPLE</small>
                    <h2>Pago seguro con Zelle</h2>
                    <p>Recibes las instrucciones al finalizar tu pedido. Verificamos la transferencia antes de coordinar la entrega.</p>
                </div>
            </div>
            <button type="button" class="zfh-side-card zfh-side-delivery" id="zfhHeroLocation">
                <span class="zfh-side-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="23" height="23" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 1 1 18 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                </span>
                <div>
                    <small>ENTREGA</small>
                    <h2><?php echo $loc_name ? esc_html( $loc_name ) : 'Florida, Camagüey'; ?></h2>
                    <p><?php echo $loc_name ? 'Mostrando disponibilidad para tu destino.' : 'Selecciona dónde quieres que entreguemos para ver la disponibilidad real.'; ?></p>
                    <span class="zfh-side-link"><?php echo $loc_name ? 'Cambiar localidad →' : 'Elegir localidad →'; ?></span>
                </div>
            </button>
        </aside>
    </section>

    <?php if ( ! empty( $cats ) ) : ?>
        <section class="zfh-section zfh-catssec" aria-labelledby="zfh-categories-title">
            <div class="zfh-section-head">
                <div><span class="zfh-eyebrow">Compra rápido</span><h2 class="zfh-h2" id="zfh-categories-title">Explora por categoría</h2></div>
                <a href="<?php echo esc_url( $shop_url ); ?>">Ver todo →</a>
            </div>
            <div class="zfh-cats-cards">
                <?php foreach ( $cats as $cat ) :
                    $cat_thumb = (int) get_term_meta( $cat->term_id, 'thumbnail_id', true );
                    $cat_img   = $cat_thumb ? wp_get_attachment_image_url( $cat_thumb, 'medium' ) : '';
                    $cat_url   = get_term_link( $cat );
                    if ( is_wp_error( $cat_url ) ) {
                        continue;
                    }
                    ?>
                    <a class="zfh-catcard" href="<?php echo esc_url( $cat_url ); ?>">
                        <?php if ( $cat_img ) : ?>
                            <img src="<?php echo esc_url( $cat_img ); ?>" alt="" loading="lazy">
                        <?php else : ?>
                            <span class="zfh-catcard-ph" aria-hidden="true"><?php echo esc_html( mb_substr( $cat->name, 0, 1 ) ); ?></span>
                        <?php endif; ?>
                        <span class="zfh-catcard-name"><?php echo esc_html( $cat->name ); ?></span>
                    </a>
                <?php endforeach; ?>
                <a class="zfh-catcard zfh-catcard-all" href="<?php echo esc_url( $shop_url ); ?>">
                    <span class="zfh-catcard-ph" aria-hidden="true">+</span>
                    <span class="zfh-catcard-name">Ver todo</span>
                </a>
            </div>
        </section>
    <?php endif; ?>

    <?php if ( ! empty( $best_sellers ) ) : ?>
        <section class="zfh-section zfh-featured" aria-labelledby="zfh-best-title">
            <div class="zfh-section-head">
                <div>
                    <span class="zfh-eyebrow">Favoritos</span>
                    <h2 class="zfh-h2" id="zfh-best-title">Los más vendidos</h2>
                    <?php if ( $loc_name ) : ?><p class="zfh-loc-note">Disponibles para entregar en <strong><?php echo esc_html( $loc_name ); ?></strong></p><?php endif; ?>
                </div>
                <a href="<?php echo esc_url( add_query_arg( 'orderby', 'popularity', $shop_url ) ); ?>">Ver más →</a>
            </div>
            <div class="zfh-grid zfh-grid-featured">
                <?php foreach ( $best_sellers as $best_product ) { $render_product_card( $best_product ); } ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="zfh-trust" aria-label="Ventajas de comprar en ZoFloridane">
        <div class="zfh-trust-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <span><b>Pagos seguros</b><small>Verificamos cada transferencia por Zelle</small></span>
        </div>
        <div class="zfh-trust-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="12" cy="12" r="3"/></svg>
            <span><b>Foto de tu entrega</b><small>Evidencia del paquete cuando corresponda</small></span>
        </div>
        <div class="zfh-trust-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 17h13V6H3z"/><path d="M16 10h3l2 3v4h-5z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>
            <span><b>Entregas en Cuba</b><small>Disponibilidad clara según la localidad</small></span>
        </div>
        <div class="zfh-trust-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <span><b>Atención personalizada</b><small>Soporte por WhatsApp durante tu compra</small></span>
        </div>
    </section>

    <section class="zfh-how" id="como-comprar" aria-labelledby="zfh-how-title">
        <div class="zfh-how-head">
            <span class="zfh-eyebrow">Sin complicaciones</span>
            <h2 id="zfh-how-title">Cómo comprar en ZoFloridane</h2>
            <p>Cuatro pasos claros para hacer llegar tu compra a Cuba.</p>
        </div>
        <div class="zfh-how-grid">
            <div class="zfh-how-step"><span>1</span><h3>Elige tus productos</h3><p>Explora el catálogo disponible para la localidad seleccionada.</p></div>
            <div class="zfh-how-step"><span>2</span><h3>Indica quién recibe</h3><p>Completa los datos de la persona que recibirá en Cuba.</p></div>
            <div class="zfh-how-step"><span>3</span><h3>Paga con Zelle</h3><p>Sigue las instrucciones que aparecen al finalizar el pedido.</p></div>
            <div class="zfh-how-step"><span>4</span><h3>Coordinamos la entrega</h3><p>Te acompañamos hasta que tu pedido quede entregado.</p></div>
        </div>
    </section>

    <section class="zfh-emotional" aria-label="Compra para tu familia en Cuba">
        <div>
            <span class="zfh-eyebrow">Más cerca, aunque estés lejos</span>
            <h2>Tú compras desde EE. UU.<br>Nosotros lo ponemos en sus manos en Cuba.</h2>
            <p>Selecciona el destino, elige lo que necesitan y deja la coordinación de la entrega en manos de ZoFloridane.</p>
        </div>
        <a href="<?php echo esc_url( $shop_url ); ?>">Empezar a comprar</a>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var heroLoc = document.getElementById('zfhHeroLocation');
    var chip = document.getElementById('zslLocChip');
    if (heroLoc && chip) {
        heroLoc.addEventListener('click', function () { chip.click(); });
    }
});
</script>

<?php get_footer(); ?>
