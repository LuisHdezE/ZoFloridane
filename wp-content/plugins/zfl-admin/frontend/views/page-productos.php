<?php
/**
 * Template Name: Productos Floridame
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

$cat_args = array(
    'taxonomy'   => 'product_cat',
    'hide_empty' => false,
    'parent'     => 0,
    'number'     => 20,
);
$cats = get_terms( $cat_args );
if ( is_wp_error( $cats ) ) {
    $cats = array();
}

$loc_id   = class_exists( 'ZFL_Store' ) ? ZFL_Store::get_current_localidad() : 0;
$loc_name = class_exists( 'ZFL_Store' ) ? ZFL_Store::get_current_localidad_name() : '';

$product_args = array(
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => 100,
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
?>

<div class="zfh">

    <?php if ( ! empty( $cats ) ) : ?>
        <section class="zfh-catssec">
            <h2 class="zfh-h2">Categorías</h2>
            <div class="zfh-cats-cards" id="zfhCats">
                <button type="button" class="zfh-catcard zfh-catcard-active" data-cat="all">
                    <span class="zfh-catcard-ph">+</span>
                    <span class="zfh-catcard-name">Ver todo</span>
                </button>
                <?php foreach ( $cats as $cat ) :
                    $cat_thumb = (int) get_term_meta( $cat->term_id, 'thumbnail_id', true );
                    $cat_img   = $cat_thumb ? wp_get_attachment_image_url( $cat_thumb, 'medium' ) : '';
                    $initial   = mb_substr( $cat->name, 0, 1 );
                    ?>
                    <button type="button" class="zfh-catcard" data-cat="<?php echo esc_attr( $cat->slug ); ?>">
                        <?php if ( $cat_img ) : ?>
                            <img src="<?php echo esc_url( $cat_img ); ?>" alt="<?php echo esc_attr( $cat->name ); ?>" loading="lazy">
                        <?php else : ?>
                            <span class="zfh-catcard-ph"><?php echo esc_html( $initial ); ?></span>
                        <?php endif; ?>
                        <span class="zfh-catcard-name"><?php echo esc_html( $cat->name ); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="zfh-products">
        <div class="zfh-products-head">
            <h2 class="zfh-h2">Productos</h2>
            <?php if ( $loc_name ) : ?>
                <span class="zfh-loc-note">para entregar en <strong><?php echo esc_html( $loc_name ); ?></strong></span>
            <?php endif; ?>
        </div>

        <div class="zfh-search-wrap">
            <input type="text" class="zfh-search-input" id="zfhSearch" placeholder="Buscar producto..." autocomplete="off">
            <svg class="zfh-search-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        </div>

        <?php if ( ! $products_query->have_posts() ) : ?>
            <p class="zfh-empty">
                No hay productos disponibles<?php echo $loc_name ? ' para entregar en ' . esc_html( $loc_name ) : ''; ?> todavía.
            </p>
        <?php else : ?>
            <div class="zfh-grid" id="zfhGrid">
                <?php $zfh_idx = 0; while ( $products_query->have_posts() ) : $products_query->the_post();
                    $product = wc_get_product( get_the_ID() );
                    if ( ! $product ) {
                        continue;
                    }
                    $zfh_idx++;
                    $img = $product->get_image_id() ? wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' ) : wc_placeholder_img_src();
                    $agotado     = ! $product->is_in_stock();
                    $es_nuevo    = $product->get_date_created() && $product->get_date_created()->getTimestamp() > strtotime( '-14 days' );
                    $mas_vendido = $product->get_total_sales() >= 5;
                    $cat_slugs   = wp_get_post_terms( get_the_ID(), 'product_cat', array( 'fields' => 'slugs' ) );
                    $cat_str     = is_wp_error( $cat_slugs ) ? '' : implode( ' ', $cat_slugs );
                    ?>
                    <article class="zfh-card<?php echo $agotado ? ' zfh-card-out' : ''; ?>"
                             data-idx="<?php echo (int) $zfh_idx; ?>"
                             data-cat="<?php echo esc_attr( $cat_str ); ?>"
                             data-name="<?php echo esc_attr( mb_strtolower( get_the_title() ) ); ?>">
                        <a class="zfh-card-img" href="<?php echo esc_url( get_permalink() ); ?>">
                            <img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy">
                            <?php if ( $agotado ) : ?>
                                <span class="zfh-agotado">Agotado</span>
                            <?php endif; ?>
                        </a>
                        <?php if ( $mas_vendido || $es_nuevo ) : ?>
                            <div class="zfh-card-badges">
                                <?php if ( $mas_vendido ) : ?><span class="zfh-badge zfh-badge-hot">Más vendido</span><?php endif; ?>
                                <?php if ( $es_nuevo && ! $mas_vendido ) : ?><span class="zfh-badge zfh-badge-new">Nuevo</span><?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="zfh-card-body">
                            <a class="zfh-card-name" href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a>
                            <span class="zfh-card-price zfh-price" data-price-cup="<?php echo esc_attr( (float) $product->get_price() ); ?>"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
                            <?php if ( $product->is_type( 'simple' ) && $product->is_in_stock() ) : ?>
                                <a class="zfh-card-add zfh-add" href="<?php echo esc_url( add_query_arg( 'add-to-cart', $product->get_id(), get_permalink() ) ); ?>"
                                   data-product-id="<?php echo (int) $product->get_id(); ?>"
                                   data-product-name="<?php echo esc_attr( get_the_title() ); ?>">Añadir al carrito</a>
                            <?php else : ?>
                                <a class="zfh-card-add" href="<?php echo esc_url( get_permalink() ); ?>">Ver producto</a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>

            <?php $total_products = (int) $products_query->found_posts; ?>
            <?php if ( $total_products > 8 ) : ?>
                <div class="zfh-more" id="zfhMoreWrap">
                    <button type="button" class="zfh-more-btn" id="zfhMoreBtn">Ver más productos</button>
                    <span class="zfh-more-count" id="zfhMoreCount"></span>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <div class="zfh-trust">
        <div class="zfh-trust-item">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <b>Pagos seguros</b>
            <span>Verificamos cada transferencia</span>
        </div>
        <div class="zfh-trust-item">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 9V5a3 3 0 0 0-3-3l-4 1v4h4"/><path d="M3 5v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9h-6"/><path d="M3 5h10"/></svg>
            <b>Foto de tu entrega</b>
            <span>Te enviamos evidencia de cada paquete</span>
        </div>
        <div class="zfh-trust-item">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0Z"/><circle cx="12" cy="10" r="3"/></svg>
            <b>Entregas en Cuba</b>
            <span>Llegamos donde estén tus seres queridos</span>
        </div>
        <div class="zfh-trust-item">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <b>Atención personalizada</b>
            <span>Te acompañamos por WhatsApp</span>
        </div>
    </div>

</div>

<script>
(function(){
    var grid  = document.getElementById('zfhGrid');
    var btn   = document.getElementById('zfhMoreBtn');
    var wrap  = document.getElementById('zfhMoreWrap');
    var cnt   = document.getElementById('zfhMoreCount');
    var cats  = document.getElementById('zfhCats');
    var search = document.getElementById('zfhSearch');
    if ( ! grid ) return;

    var cards  = grid.querySelectorAll('.zfh-card');
    var total  = cards.length;
    var activeCat = 'all';
    var searchQuery = '';

    function isMobile(){ return window.innerWidth <= 768; }
    function getInitial(){ return isMobile() ? 20 : 12; }
    function getBatch(){ return isMobile() ? 10 : 8; }

    var shown = getInitial();

    function cardMatches(el){
        if ( activeCat !== 'all' ){
            var catStr = el.getAttribute('data-cat') || '';
            if ( catStr.indexOf(activeCat) === -1 ) return false;
        }
        if ( searchQuery ){
            var name = el.getAttribute('data-name') || '';
            if ( name.indexOf(searchQuery) === -1 ) return false;
        }
        return true;
    }

    function apply(){
        var visible = 0;
        for ( var i = 0; i < total; i++ ){
            var match = cardMatches(cards[i]);
            if ( match && visible < shown ){
                cards[i].removeAttribute('hidden');
                visible++;
            } else {
                cards[i].setAttribute('hidden','');
            }
        }
        var totalMatch = 0;
        for ( var j = 0; j < total; j++ ){
            if ( cardMatches(cards[j]) ) totalMatch++;
        }
        var remaining = totalMatch - visible;
        if ( !wrap ) return;
        if ( remaining <= 0 || totalMatch <= shown ){
            wrap.setAttribute('hidden','');
        } else {
            wrap.removeAttribute('hidden');
            cnt.textContent = remaining + ' producto' + ( remaining !== 1 ? 's' : '' ) + ' restante' + ( remaining !== 1 ? 's' : '' );
        }
    }

    if ( btn ){
        btn.addEventListener('click', function(){
            shown = shown + getBatch();
            apply();
        });
    }

    if ( cats ){
        cats.addEventListener('click', function(e){
            var card = e.target.closest('.zfh-catcard');
            if ( !card ) return;
            var allBtns = cats.querySelectorAll('.zfh-catcard');
            for ( var i = 0; i < allBtns.length; i++ ) allBtns[i].classList.remove('zfh-catcard-active');
            card.classList.add('zfh-catcard-active');
            activeCat = card.getAttribute('data-cat') || 'all';
            shown = getInitial();
            apply();
        });
    }

    if ( search ){
        search.addEventListener('input', function(){
            searchQuery = search.value.trim().toLowerCase();
            shown = getInitial();
            apply();
        });
    }

    apply();
})();
</script>

<?php
get_footer();
