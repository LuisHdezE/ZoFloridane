<?php
/**
 * Template Name: Home Zofloridane
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>
<script>window.zfhConfig = { cartUrl: <?php echo wp_json_encode( esc_url_raw( wc_get_page_permalink( 'cart' ) ) ); ?> };</script>
<?php if ( class_exists( 'ZFL_Currencies' ) ) : ?>
<script>window.zfhRates = <?php echo wp_json_encode( ZFL_Currencies::get_rates() ); ?>;</script>
<?php endif; ?>

<?php
$promos = class_exists( 'ZFL_Promos' ) ? ZFL_Promos::get_active() : array();

$cats = get_terms( array(
    'taxonomy'   => 'product_cat',
    'hide_empty' => false,
    'parent'     => 0,
    'number'     => 8,
) );
if ( is_wp_error( $cats ) ) {
    $cats = array();
}

$loc_id   = class_exists( 'ZFL_Store' ) ? ZFL_Store::get_current_localidad() : 0;
$loc_name = class_exists( 'ZFL_Store' ) ? ZFL_Store::get_current_localidad_name() : '';

$product_args = array(
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => 12,
    'orderby'        => 'date',
    'order'          => 'DESC',
);
if ( $loc_id > 0 ) {
    $product_args['tax_query'] = array(
        array(
            'taxonomy' => 'zfl_localidad',
            'field'    => 'term_id',
            'terms'    => $loc_id,
        ),
    );
}
$products_query = new WP_Query( $product_args );
$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );

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

$best_sellers = $filter_by_locality( wc_get_products( array(
    'limit'    => 12,
    'orderby'  => 'meta_value_num',
    'meta_key' => 'total_sales',
    'order'    => 'DESC',
    'status'   => 'publish',
) ) );
$best_sellers = array_slice( $best_sellers, 0, 4 );

$testimonials = class_exists( 'ZFL_Reviews' ) ? ZFL_Reviews::get_reviews() : array();

$render_product_card = static function ( $product ) {
    if ( ! $product ) {
        return;
    }
    $id          = $product->get_id();
    $img         = $product->get_image_id() ? wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' ) : wc_placeholder_img_src();
    $out         = ! $product->is_in_stock();
    $is_new      = $product->get_date_created() && $product->get_date_created()->getTimestamp() > strtotime( '-14 days' );
    $is_popular  = $product->get_total_sales() >= 5;
    ?>
    <article class="zfh-card<?php echo $out ? ' zfh-card-out' : ''; ?>">
        <a class="zfh-card-img" href="<?php echo esc_url( $product->get_permalink() ); ?>">
            <img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" loading="lazy">
            <?php if ( $out ) : ?><span class="zfh-agotado">Agotado</span><?php endif; ?>
        </a>
        <?php if ( $is_popular || $is_new ) : ?>
            <div class="zfh-card-badges">
                <?php if ( $is_popular ) : ?><span class="zfh-badge zfh-badge-hot">Más vendido</span><?php endif; ?>
                <?php if ( $is_new && ! $is_popular ) : ?><span class="zfh-badge zfh-badge-new">Nuevo</span><?php endif; ?>
            </div>
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

<main class="zfh">
    <div class="zfh-install" id="zfhInstall" hidden>
        <div class="zfh-install-text">
            <b>Instala Zofloridane en tu teléfono</b>
            <span>Compra más rápido desde tu pantalla de inicio.</span>
        </div>
        <button type="button" class="zfh-install-btn" id="zfhInstallBtn">Instalar</button>
        <button type="button" class="zfh-install-close" id="zfhInstallClose" aria-label="Cerrar">×</button>
    </div>

    <section class="zfh-hero-grid" aria-label="Promociones principales">
        <div class="zfh-hero-main">
            <?php if ( ! empty( $promos ) ) : ?>
                <div class="zfh-carousel" id="zfhCarousel">
                    <div class="zfh-track">
                        <?php foreach ( $promos as $promo ) :
                            $img = (int) $promo['image_id'] ? wp_get_attachment_image_url( (int) $promo['image_id'], 'full' ) : '';
                            if ( ! $img ) {
                                continue;
                            }
                            $tag_open  = $promo['link'] ? '<a class="zfh-slide" href="' . esc_url( $promo['link'] ) . '">' : '<div class="zfh-slide">';
                            $tag_close = $promo['link'] ? '</a>' : '</div>';
                            ?>
                            <?php echo $tag_open; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $promo['title'] ); ?>" loading="eager">
                                <span class="zfh-slide-shade" aria-hidden="true"></span>
                                <span class="zfh-slide-copy">
                                    <small>ZOFLORIDANE</small>
                                    <strong><?php echo esc_html( $promo['title'] !== '' ? $promo['title'] : 'Compra desde EE. UU. y entrégalo en Cuba' ); ?></strong>
                                    <em><?php echo $loc_name ? 'Productos disponibles para ' . esc_html( $loc_name ) : 'Selecciona una localidad y comienza tu compra'; ?></em>
                                    <span class="zfh-slide-cta">Ver productos</span>
                                </span>
                            <?php echo $tag_close; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="zfh-nav zfh-prev" aria-label="Anterior"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m15 18-6-6 6-6"/></svg></button>
                    <button type="button" class="zfh-nav zfh-next" aria-label="Siguiente"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg></button>
                    <div class="zfh-dots"></div>
                </div>
            <?php else : ?>
                <div class="zfh-hero-fallback">
                    <span class="zfh-kicker">ZOFLORIDANE</span>
                    <h1>Compra desde EE. UU.<br>y entrégalo en Cuba.</h1>
                    <p>Elige productos, indica quién recibe y nosotros coordinamos la entrega.</p>
                    <a href="<?php echo esc_url( $shop_url ); ?>">Ver productos</a>
                </div>
            <?php endif; ?>
        </div>

        <aside class="zfh-hero-side">
            <div class="zfh-side-card zfh-side-zelle">
                <span class="zfh-side-icon">Z</span>
                <div>
                    <small>PAGO SIMPLE</small>
                    <h2>Pago seguro con Zelle</h2>
                    <p>Recibes las instrucciones al finalizar tu pedido.</p>
                </div>
            </div>
            <button type="button" class="zfh-side-card zfh-side-delivery" id="zfhHeroLocation">
                <span class="zfh-side-icon">
                    <svg viewBox="0 0 24 24" width="23" height="23" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 1 1 18 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                </span>
                <div>
                    <small>DESTINO</small>
                    <h2><?php echo $loc_name ? esc_html( $loc_name ) : 'Elige la localidad'; ?></h2>
                    <p><?php echo $loc_name ? 'Mostrando disponibilidad para tu destino.' : 'Así podremos mostrarte los productos disponibles.'; ?></p>
                    <span class="zfh-side-link">Cambiar localidad →</span>
                </div>
            </button>
        </aside>
    </section>

    <?php if ( ! empty( $cats ) ) : ?>
        <section class="zfh-section zfh-catssec">
            <div class="zfh-section-head">
                <div><span class="zfh-eyebrow">Compra rápido</span><h2 class="zfh-h2">Explora por categoría</h2></div>
                <a href="<?php echo esc_url( $shop_url ); ?>">Ver todo →</a>
            </div>
            <div class="zfh-cats-cards">
                <?php foreach ( $cats as $cat ) :
                    $cat_thumb = (int) get_term_meta( $cat->term_id, 'thumbnail_id', true );
                    $cat_img   = $cat_thumb ? wp_get_attachment_image_url( $cat_thumb, 'medium' ) : '';
                    ?>
                    <a class="zfh-catcard" href="<?php echo esc_url( get_term_link( $cat ) ); ?>">
                        <?php if ( $cat_img ) : ?>
                            <img src="<?php echo esc_url( $cat_img ); ?>" alt="<?php echo esc_attr( $cat->name ); ?>" loading="lazy">
                        <?php else : ?>
                            <span class="zfh-catcard-ph"><?php echo esc_html( mb_substr( $cat->name, 0, 1 ) ); ?></span>
                        <?php endif; ?>
                        <span class="zfh-catcard-name"><?php echo esc_html( $cat->name ); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ( ! empty( $best_sellers ) ) : ?>
        <section class="zfh-section zfh-featured">
            <div class="zfh-section-head">
                <div><span class="zfh-eyebrow">Favoritos</span><h2 class="zfh-h2">Los más vendidos</h2></div>
                <a href="<?php echo esc_url( add_query_arg( 'orderby', 'popularity', $shop_url ) ); ?>">Ver más →</a>
            </div>
            <div class="zfh-grid zfh-grid-featured">
                <?php foreach ( $best_sellers as $best_product ) { $render_product_card( $best_product ); } ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="zfh-trust" aria-label="Ventajas de comprar en Zofloridane">
        <div class="zfh-trust-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <span><b>Pagos seguros</b><small>Verificamos cada transferencia</small></span>
        </div>
        <div class="zfh-trust-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="12" cy="12" r="3"/></svg>
            <span><b>Foto de tu entrega</b><small>Evidencia del paquete entregado</small></span>
        </div>
        <div class="zfh-trust-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 17h13V6H3z"/><path d="M16 10h3l2 3v4h-5z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>
            <span><b>Entregas en Cuba</b><small>Coordinación clara del destino</small></span>
        </div>
        <div class="zfh-trust-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <span><b>Atención personalizada</b><small>Te acompañamos durante la compra</small></span>
        </div>
    </section>

    <section class="zfh-section zfh-products">
        <div class="zfh-section-head zfh-products-head">
            <div>
                <span class="zfh-eyebrow">Catálogo</span>
                <h2 class="zfh-h2">Productos</h2>
                <?php if ( $loc_name ) : ?><p class="zfh-loc-note">Disponibles para entregar en <strong><?php echo esc_html( $loc_name ); ?></strong></p><?php endif; ?>
            </div>
            <a href="<?php echo esc_url( $shop_url ); ?>">Ver todo →</a>
        </div>

        <?php if ( ! $products_query->have_posts() ) : ?>
            <p class="zfh-empty">No hay productos disponibles<?php echo $loc_name ? ' para ' . esc_html( $loc_name ) : ''; ?> en este momento.</p>
        <?php else : ?>
            <div class="zfh-grid">
                <?php while ( $products_query->have_posts() ) : $products_query->the_post(); $render_product_card( wc_get_product( get_the_ID() ) ); endwhile; wp_reset_postdata(); ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="zfh-how">
        <div class="zfh-how-head">
            <span class="zfh-eyebrow">Sin complicaciones</span>
            <h2>Cómo comprar en Zofloridane</h2>
            <p>Cuatro pasos y nosotros nos ocupamos del resto.</p>
        </div>
        <div class="zfh-how-grid">
            <div class="zfh-how-step"><span>1</span><h3>Elige tus productos</h3><p>Explora el catálogo y añade al carrito.</p></div>
            <div class="zfh-how-step"><span>2</span><h3>Indica quién recibe</h3><p>Completa los datos de tu familiar en Cuba.</p></div>
            <div class="zfh-how-step"><span>3</span><h3>Paga con Zelle</h3><p>Sigue las instrucciones mostradas al finalizar.</p></div>
            <div class="zfh-how-step"><span>4</span><h3>Coordinamos la entrega</h3><p>Te mantenemos informado hasta completar el pedido.</p></div>
        </div>
    </section>

    <?php if ( ! empty( $testimonials ) ) : ?>
        <section class="zfh-section zfh-testi">
            <div class="zfh-section-head">
                <div><span class="zfh-eyebrow">Confianza</span><h2 class="zfh-h2">Lo que dicen nuestros clientes</h2></div>
            </div>
            <div class="zfh-testi-grid">
                <?php foreach ( array_slice( $testimonials, 0, 3 ) as $t ) :
                    $stars = (int) ( $t['stars'] ?? 5 );
                    $name  = (string) ( $t['name'] ?? '' );
                    $from  = (string) ( $t['from'] ?? '' );
                    $text  = (string) ( $t['text'] ?? '' );
                    ?>
                    <article class="zfh-testi-card">
                        <div class="zfh-testi-stars"><?php echo class_exists( 'ZFL_Reviews' ) ? wp_kses_post( ZFL_Reviews::render_stars( $stars ) ) : ''; ?></div>
                        <p>“<?php echo esc_html( $text ); ?>”</p>
                        <div class="zfh-testi-who">
                            <span class="zfh-testi-avatar"><?php echo esc_html( mb_substr( $name, 0, 1 ) ); ?></span>
                            <span><b><?php echo esc_html( $name ); ?></b><small><?php echo esc_html( $from ); ?></small></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
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
