<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$product = wc_get_product( get_the_ID() );
if ( ! $product ) {
    echo '<p style="text-align:center;padding:60px 20px;">Producto no encontrado.</p>';
    get_footer();
    return;
}

$cup_price   = (float) $product->get_price();
$attachment_ids = $product->get_gallery_image_ids();
$main_img_id   = $product->get_image_id();
$agotado       = ! $product->is_in_stock();
$cat_slugs     = wp_get_post_terms( get_the_ID(), 'product_cat', array( 'fields' => 'slugs' ) );
$cat_str       = is_wp_error( $cat_slugs ) ? '' : implode( ' ', $cat_slugs );
$localidades   = wp_get_post_terms( get_the_ID(), 'zfl_localidad', array( 'fields' => 'names' ) );
$loc_str       = is_wp_error( $localidades ) ? '' : implode( ', ', $localidades );
$desc_short    = $product->get_short_description();
$desc_full     = get_the_content();
$ratings       = get_comments( array(
    'post_id' => get_the_ID(),
    'status'  => 'approve',
    'type'    => 'review',
) );
$avg_rating    = 0;
$rating_count  = count( $ratings );
if ( $rating_count > 0 ) {
    $sum = 0;
    foreach ( $ratings as $r ) {
        $sum += (int) get_comment_meta( $r->comment_ID, 'rating', true );
    }
    $avg_rating = round( $sum / $rating_count, 1 );
}

$related_ids = wc_get_related_products( get_the_ID(), 8 );
$related     = array();
if ( ! empty( $related_ids ) ) {
    $related = wc_get_products( array(
        'include' => $related_ids,
        'limit'   => 4,
        'status'  => 'publish',
    ) );
}
?>

<style>
html body .zfl-sp-wrap{max-width:960px;margin:0 auto;padding:20px 16px 60px}
html body .zfl-sp-bc{font-size:13px;color:#9ca3af;margin-bottom:16px}
html body .zfl-sp-bc a{color:#6b7280;text-decoration:none}
html body .zfl-sp-bc span{margin:0 6px;color:#d1d5db}
html body .zfl-sp-top{display:flex;flex-wrap:wrap;gap:28px;margin-bottom:36px}
html body .zfl-sp-gallery{flex:1 1 380px;min-width:0}
html body .zfl-sp-main-img{width:100%;border-radius:12px;object-fit:cover;aspect-ratio:1;background:#f3f4f6;cursor:zoom-in}
html body .zfl-sp-thumbs{display:flex;gap:8px;margin-top:10px;overflow-x:auto;padding-bottom:4px}
html body .zfl-sp-thumbs img{width:64px;height:64px;object-fit:cover;border-radius:8px;border:2px solid transparent;cursor:pointer;opacity:.7;transition:all .15s}
html body .zfl-sp-thumbs img.active,html body .zfl-sp-thumbs img:hover{border-color:#ffcc00;opacity:1}
html body .zfl-sp-info{flex:1 1 320px;min-width:0;display:flex;flex-direction:column;gap:14px}
html body .zfl-sp-name{font-size:24px;font-weight:700;color:#374151;line-height:1.25}
html body .zfl-sp-rating{display:flex;align-items:center;gap:8px;font-size:14px;color:#9ca3af}
html body .zfl-sp-rating-stars{color:#f59e0b;font-size:16px;letter-spacing:1px}
html body .zfl-sp-prices{display:flex;flex-direction:column;gap:6px}
html body .zfl-sp-price-item{font-size:20px;font-weight:700;color:#4b5563}
html body .zfl-sp-desc{font-size:15px;color:#6b7280;line-height:1.6}
html body .zfl-sp-meta{display:flex;flex-wrap:wrap;gap:8px;font-size:13px}
html body .zfl-sp-meta-tag{background:#f9fafb;color:#9ca3af;padding:5px 14px;border-radius:20px;border:1px solid #e5e7eb}
html body .zfl-sp-add{display:flex;align-items:center;gap:12px;margin-top:4px;flex-wrap:wrap}
html body .zfl-sp-add-btn{background:#ffcc00;color:#374151;border:none;padding:14px 28px;border-radius:10px;font-size:16px;font-weight:700;cursor:pointer;transition:background .15s;text-decoration:none;display:inline-flex;align-items:center}
html body .zfl-sp-add-btn:hover{background:#e6b800}
html body .zfl-sp-add-btn:disabled{background:#e5e7eb;color:#9ca3af;cursor:not-allowed}
html body .zfl-sp-wa{display:inline-flex;align-items:center;gap:8px;background:#25d366;color:#fff;padding:14px 24px;border-radius:10px;text-decoration:none;font-weight:600;font-size:15px;transition:background .15s}
html body .zfl-sp-wa:hover{background:#1da851}
html body .zfl-sp-section{margin-top:36px}
html body .zfl-sp-section h2{font-size:20px;font-weight:700;color:#4b5563;margin-bottom:16px}
html body .zfl-sp-full{font-size:15px;color:#6b7280;line-height:1.7}
html body .zfl-sp-full p{margin-bottom:12px}
html body .zfl-sp-rev-item{border-bottom:1px solid #f3f4f6;padding:16px 0}
html body .zfl-sp-rev-head{display:flex;align-items:center;gap:10px;margin-bottom:6px}
html body .zfl-sp-rev-avatar{width:32px;height:32px;border-radius:50%;background:#fef3c7;color:#92400e;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px}
html body .zfl-sp-rev-name{font-weight:600;color:#4b5563;font-size:14px}
html body .zfl-sp-rev-date{font-size:12px;color:#d1d5db}
html body .zfl-sp-rev-stars{color:#f59e0b;font-size:14px;margin-bottom:4px}
html body .zfl-sp-rev-text{font-size:14px;color:#6b7280;line-height:1.5}
html body .zfl-sp-related{margin-top:40px}
html body .zfl-sp-related h2{font-size:20px;font-weight:700;color:#4b5563;margin-bottom:16px}
html body .zfl-sp-rel-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px}
html body .zfl-sp-rel-card{background:#fafafa;border:1px solid #f3f4f6;border-radius:12px;overflow:hidden;transition:box-shadow .15s;text-decoration:none}
html body .zfl-sp-rel-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.06)}
html body .zfl-sp-rel-card img{width:100%;aspect-ratio:1;object-fit:cover}
html body .zfl-sp-rel-card-body{padding:10px 12px}
html body .zfl-sp-rel-card-name{font-size:14px;font-weight:600;color:#4b5563;display:block;margin-bottom:4px}
html body .zfl-sp-rel-card-price{font-size:13px;color:#9ca3af}
html body .zfl-sp-agotado{display:inline-block;background:#fef2f2;color:#b91c1c;padding:3px 10px;border-radius:6px;font-size:12px;font-weight:600;margin-bottom:4px}

/* DARK MODE */
html.dark-mode body .zfl-sp-bc,html.dark-mode body .zfl-sp-rating,html.dark-mode body .zfl-sp-rev-date{color:#9ca3af!important}
html.dark-mode body .zfl-sp-bc a{color:#60a5fa!important}
html.dark-mode body .zfl-sp-bc span{color:#6b7280!important}
html.dark-mode body .zfl-sp-name{color:#f3f4f6!important}
html.dark-mode body .zfl-sp-price-item{color:#f3f4f6!important}
html.dark-mode body .zfl-sp-desc{color:#d1d5db!important}
html.dark-mode body .zfl-sp-meta-tag{background:#252830!important;color:#d1d5db!important;border-color:#374151!important}
html.dark-mode body .zfl-sp-add-btn{background:#ffcc00!important;color:#111827!important}
html.dark-mode body .zfl-sp-add-btn:disabled{background:#374151!important;color:#6b7280!important}
html.dark-mode body .zfl-sp-wa{background:#25d366!important}
html.dark-mode body .zfl-sp-section h2{color:#f3f4f6!important}
html.dark-mode body .zfl-sp-full{color:#d1d5db!important}
html.dark-mode body .zfl-sp-full p{color:#d1d5db!important}
html.dark-mode body .zfl-sp-rev-item{border-bottom-color:#374151!important}
html.dark-mode body .zfl-sp-rev-avatar{background:#422006!important;color:#fbbf24!important}
html.dark-mode body .zfl-sp-rev-name{color:#f3f4f6!important}
html.dark-mode body .zfl-sp-rev-text{color:#d1d5db!important}
html.dark-mode body .zfl-sp-related h2{color:#f3f4f6!important}
html.dark-mode body .zfl-sp-rel-card{background:#1a1d23!important;border-color:#374151!important}
html.dark-mode body .zfl-sp-rel-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.3)!important}
html.dark-mode body .zfl-sp-rel-card-name{color:#f3f4f6!important}
html.dark-mode body .zfl-sp-rel-card-price{color:#9ca3af!important}
html.dark-mode body .zfl-sp-main-img{background:#252830!important}

@media(max-width:768px){html body .zfl-sp-top{flex-direction:column;gap:20px}html body .zfl-sp-gallery{flex-basis:auto}html body .zfl-sp-info{flex-basis:auto}html body .zfl-sp-name{font-size:20px}html body .zfl-sp-main-img{aspect-ratio:4/3}html body .zfl-sp-add{flex-direction:column;align-items:stretch}html body .zfl-sp-add-btn,html body .zfl-sp-wa{justify-content:center;text-align:center}}
</style>

<div class="zfl-sp-wrap">

    <div class="zfl-sp-bc">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Inicio</a>
        <span>›</span>
        <a href="<?php echo esc_url( home_url( '/catalogo/' ) ); ?>">Productos</a>
        <span>›</span>
        <?php echo esc_html( get_the_title() ); ?>
    </div>

    <div class="zfl-sp-top">

        <div class="zfl-sp-gallery">
            <?php if ( $main_img_id ) : ?>
                <?php $main_url = wp_get_attachment_image_url( $main_img_id, 'large' ); ?>
                <img class="zfl-sp-main-img" id="zflSpMain" src="<?php echo esc_url( $main_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>">
            <?php else : ?>
                <img class="zfl-sp-main-img" id="zflSpMain" src="<?php echo esc_url( wc_placeholder_img_src() ); ?>" alt="">
            <?php endif; ?>
            <?php if ( ! empty( $attachment_ids ) ) : ?>
                <div class="zfl-sp-thumbs" id="zflSpThumbs">
                    <?php if ( $main_img_id ) : ?>
                        <img src="<?php echo esc_url( wp_get_attachment_image_url( $main_img_id, 'thumbnail' ) ); ?>" data-src="<?php echo esc_url( wp_get_attachment_image_url( $main_img_id, 'large' ) ); ?>" alt="" class="active">
                    <?php endif; ?>
                    <?php foreach ( $attachment_ids as $aid ) : ?>
                        <img src="<?php echo esc_url( wp_get_attachment_image_url( $aid, 'thumbnail' ) ); ?>" data-src="<?php echo esc_url( wp_get_attachment_image_url( $aid, 'large' ) ); ?>" alt="">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="zfl-sp-info">
            <?php if ( $agotado ) : ?>
                <span class="zfl-sp-agotado">Agotado</span>
            <?php endif; ?>

            <h1 class="zfl-sp-name"><?php echo esc_html( get_the_title() ); ?></h1>

            <?php if ( $rating_count > 0 ) : ?>
                <div class="zfl-sp-rating">
                    <span class="zfl-sp-rating-stars"><?php echo str_repeat( '★', (int) round( $avg_rating ) ) . str_repeat( '☆', 5 - (int) round( $avg_rating ) ); ?></span>
                    <span><?php echo esc_html( $avg_rating ); ?> (<?php echo (int) $rating_count; ?> reseña<?php echo $rating_count !== 1 ? 's' : ''; ?>)</span>
                </div>
            <?php endif; ?>

            <div class="zfl-sp-prices">
                <?php if ( class_exists( 'ZFL_Currencies' ) ) : ?>
                    <?php foreach ( ZFL_Currencies::get_rates() as $code => $data ) :
                        $converted = ZFL_Currencies::convert( $cup_price, $code );
                        if ( $converted > 0 ) : ?>
                            <span class="zfl-sp-price-item"><?php echo esc_html( $data['symbol'] . number_format( $converted, 2 ) . ' ' . $code ); ?></span>
                        <?php endif;
                    endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ( $desc_short ) : ?>
                <div class="zfl-sp-desc"><?php echo wp_kses_post( $desc_short ); ?></div>
            <?php endif; ?>

            <?php if ( $loc_str ) : ?>
                <div class="zfl-sp-meta">
                    <span class="zfl-sp-meta-tag">📍 <?php echo esc_html( $loc_str ); ?></span>
                </div>
            <?php endif; ?>

            <?php if ( $cat_str && ! is_wp_error( $cat_slugs ) ) : ?>
                <div class="zfl-sp-meta">
                    <?php foreach ( $cat_slugs as $cs ) :
                        $cat_obj = get_term_by( 'slug', $cs, 'product_cat' );
                        if ( $cat_obj ) : ?>
                            <span class="zfl-sp-meta-tag"><?php echo esc_html( $cat_obj->name ); ?></span>
                        <?php endif;
                    endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="zfl-sp-add">
                <?php if ( $product->is_type( 'simple' ) && $agotado ) : ?>
                    <button class="zfl-sp-add-btn" disabled>Agotado</button>
                <?php elseif ( $product->is_type( 'simple' ) ) : ?>
                    <a class="zfl-sp-add-btn zfh-add" href="<?php echo esc_url( add_query_arg( 'add-to-cart', $product->get_id(), get_permalink() ) ); ?>"
                       data-product-id="<?php echo (int) $product->get_id(); ?>"
                       data-product-name="<?php echo esc_attr( get_the_title() ); ?>">Añadir al carrito</a>
                <?php endif; ?>
                <a class="zfl-sp-wa" href="https://wa.me/5356514568?text=<?php echo urlencode( 'Hola, me interesa: ' . get_the_title() . ' — ¿está disponible?' ); ?>" target="_blank" rel="noopener">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    Preguntar por WhatsApp
                </a>
            </div>
        </div>

    </div>

    <?php if ( $desc_full ) : ?>
        <div class="zfl-sp-section">
            <h2>Descripción</h2>
            <div class="zfl-sp-full"><?php echo wp_kses_post( apply_filters( 'the_content', $desc_full ) ); ?></div>
        </div>
    <?php endif; ?>

    <?php if ( $rating_count > 0 ) : ?>
        <div class="zfl-sp-section zfl-sp-reviews">
            <h2>Reseñas (<?php echo (int) $rating_count; ?>)</h2>
            <?php foreach ( $ratings as $rev ) :
                $rev_rating = (int) get_comment_meta( $rev->comment_ID, 'rating', true );
                $rev_name   = $rev->comment_author;
                $rev_date   = $rev->comment_date;
                $rev_text   = $rev->comment_content;
                $initial    = mb_strtoupper( mb_substr( $rev_name, 0, 1 ) );
                ?>
                <div class="zfl-sp-rev-item">
                    <div class="zfl-sp-rev-head">
                        <span class="zfl-sp-rev-avatar"><?php echo esc_html( $initial ); ?></span>
                        <span class="zfl-sp-rev-name"><?php echo esc_html( $rev_name ); ?></span>
                        <span class="zfl-sp-rev-date"><?php echo esc_html( date_i18n( 'd M Y', strtotime( $rev_date ) ) ); ?></span>
                    </div>
                    <div class="zfl-sp-rev-stars"><?php echo str_repeat( '★', $rev_rating ) . str_repeat( '☆', 5 - $rev_rating ); ?></div>
                    <p class="zfl-sp-rev-text"><?php echo esc_html( $rev_text ); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $related ) ) : ?>
        <div class="zfl-sp-related">
            <h2>También te puede interesar</h2>
            <div class="zfl-sp-rel-grid">
                <?php foreach ( $related as $rp ) :
                    $rp_img = $rp->get_image_id() ? wp_get_attachment_image_url( $rp->get_image_id(), 'woocommerce_thumbnail' ) : wc_placeholder_img_src();
                    $rp_cup = (float) $rp->get_price();
                    $rp_usd = class_exists( 'ZFL_Currencies' ) ? ZFL_Currencies::convert( $rp_cup, 'USD' ) : 0;
                    $rp_mxn = class_exists( 'ZFL_Currencies' ) ? ZFL_Currencies::convert( $rp_cup, 'MXN' ) : 0;
                    $rp_eur = class_exists( 'ZFL_Currencies' ) ? ZFL_Currencies::convert( $rp_cup, 'EUR') : 0;
                    ?>
                    <a class="zfl-sp-rel-card" href="<?php echo esc_url( $rp->get_permalink() ); ?>">
                        <img src="<?php echo esc_url( $rp_img ); ?>" alt="<?php echo esc_attr( $rp->get_name() ); ?>" loading="lazy">
                        <div class="zfl-sp-rel-card-body">
                            <span class="zfl-sp-rel-card-name"><?php echo esc_html( $rp->get_name() ); ?></span>
                            <span class="zfl-sp-rel-card-price"><?php if ( $rp_usd > 0 ) : ?>$<?php echo esc_html( number_format( $rp_usd, 2 ) ); ?> USD<?php endif; ?><?php if ( $rp_mxn > 0 ) : ?> · $<?php echo esc_html( number_format( $rp_mxn, 2 ) ); ?> MXN<?php endif; ?><?php if ( $rp_eur > 0 ) : ?> · €<?php echo esc_html( number_format( $rp_eur, 2 ) ); ?><?php endif; ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
(function(){
    var main = document.getElementById('zflSpMain');
    var thumbs = document.getElementById('zflSpThumbs');
    if (!main || !thumbs) return;
    thumbs.addEventListener('click', function(e){
        var img = e.target.closest('img');
        if (!img) return;
        var src = img.getAttribute('data-src') || img.src;
        main.src = src;
        var all = thumbs.querySelectorAll('img');
        for (var i = 0; i < all.length; i++) all[i].classList.remove('active');
        img.classList.add('active');
    });
    main.addEventListener('click', function(){
        if (typeof Zoombus !== 'undefined') return;
        var overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.9);z-index:99999;display:flex;align-items:center;justify-content:center;cursor:zoom-out';
        var big = document.createElement('img');
        big.src = main.src;
        big.style.cssText = 'max-width:92vw;max-height:92vh;border-radius:8px';
        overlay.appendChild(big);
        document.body.appendChild(overlay);
        overlay.addEventListener('click', function(){ overlay.remove(); });
    });
})();
</script>

<?php get_footer(); ?>
