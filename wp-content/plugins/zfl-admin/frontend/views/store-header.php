<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Cabecera pública de ZoFloridane.
 * Variables: $localidades, $current_id, $current_name, $logo,
 * $search_action, $account_url, $cart_url, $cart_count, $can_manage, $panel_url.
 */

$nav_categories = function_exists( 'zfl_storefront_get_categories' ) ? zfl_storefront_get_categories( 6, true ) : array();
$shop_url       = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$offers_url     = add_query_arg( 'zfl_ofertas', '1', $shop_url );
$has_offers     = function_exists( 'zfl_storefront_has_offers_category' ) && zfl_storefront_has_offers_category( $nav_categories );
$official_logo  = function_exists( 'zfl_storefront_logo_url' ) ? zfl_storefront_logo_url() : $logo;
?>
<header class="zsl-header" id="zslHeader">
    <div class="zsl-topline">
        <div class="zsl-topline-inner">
            <span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 1 1 18 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                <?php echo $current_name ? 'Entrega en ' . esc_html( $current_name ) : 'Entrega en Florida, Camagüey'; ?>
            </span>
            <span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg>
                Pago con Zelle
            </span>
            <span class="zsl-topline-whatsapp">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 11.5a8.5 8.5 0 0 1-12.6 7.45L3 20l1.05-4.75A8.5 8.5 0 1 1 20.5 11.5Z"/><path d="M8.3 7.8c.2-.45.4-.46.7-.47h.6c.18 0 .4.08.5.4l.7 1.7c.1.27.05.5-.12.72l-.55.67c-.18.2-.16.4-.05.62.38.72 1.03 1.55 1.92 2.15.22.15.44.18.64 0l.82-.77c.23-.22.5-.28.78-.16l1.68.78c.3.14.4.33.36.58-.08.55-.4 1.3-.88 1.7-.55.46-1.3.65-2.05.48-1.35-.3-2.82-1.1-4.1-2.25-1.08-.96-2.02-2.3-2.43-3.48-.28-.83-.25-1.55.05-2.17Z"/></svg>
                Atención por WhatsApp
            </span>
        </div>
    </div>

    <div class="zsl-main">
        <a class="zsl-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="ZoFloridane, inicio">
            <?php if ( $official_logo ) : ?>
                <img src="<?php echo esc_url( $official_logo ); ?>" alt="ZoFloridane">
            <?php else : ?>
                <span>ZoFloridane</span>
            <?php endif; ?>
        </a>

        <form class="zsl-search" method="get" action="<?php echo esc_url( $search_action ); ?>" role="search">
            <label class="screen-reader-text" for="zslProductSearch">Buscar productos</label>
            <input id="zslProductSearch" type="search" name="s" placeholder="¿Qué producto estás buscando?" autocomplete="off">
            <input type="hidden" name="post_type" value="product">
            <button type="submit" aria-label="Buscar productos">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            </button>
        </form>

        <button type="button" class="zsl-loc-chip" id="zslLocChip" aria-haspopup="dialog" aria-expanded="false">
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 1 1 18 0Z"/><circle cx="12" cy="10" r="3"/></svg>
            <span class="zsl-loc-texts">
                <span class="zsl-loc-hello">Entregar en</span>
                <span class="zsl-loc-name"><?php echo esc_html( $current_name !== '' ? $current_name : 'Elige localidad' ); ?></span>
            </span>
        </button>

        <div class="zsl-actions">
            <a class="zsl-action" href="<?php echo esc_url( $account_url ); ?>" aria-label="<?php echo is_user_logged_in() ? 'Mi cuenta' : 'Ingresar a mi cuenta'; ?>">
                <svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span class="zsl-action-label"><?php echo is_user_logged_in() ? 'Mi cuenta' : 'Ingresar'; ?></span>
            </a>

            <a class="zsl-action zsl-cart" href="<?php echo esc_url( $cart_url ); ?>" aria-label="Carrito, <?php echo (int) $cart_count; ?> productos">
                <svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                <span class="zsl-action-label">Carrito</span>
                <span class="zsl-cart-count" id="zslCartCount"><?php echo (int) $cart_count; ?></span>
            </a>

            <select id="zslCurrencySelect" class="zsl-currency-select" aria-label="Moneda de referencia">
                <?php if ( class_exists( 'ZFL_Currencies' ) ) : $zfl_cur = ZFL_Currencies::get_rates(); ?>
                    <?php foreach ( $zfl_cur as $code => $data ) : ?>
                        <option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $code ); ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>

            <div class="zsl-theme-switch" id="zslThemeSwitch" role="group" aria-label="Tema visual">
                <button type="button" class="zsl-theme-option" data-zsl-theme="black" title="Base Negra" aria-label="Usar Base Negra" aria-pressed="true">
                    <span class="zsl-theme-swatch zsl-theme-swatch-black" aria-hidden="true"></span>
                    <span class="zsl-theme-label">Negra</span>
                </button>
                <button type="button" class="zsl-theme-option" data-zsl-theme="green" title="Base Verde" aria-label="Usar Base Verde" aria-pressed="false">
                    <span class="zsl-theme-swatch zsl-theme-swatch-green" aria-hidden="true"></span>
                    <span class="zsl-theme-label">Verde</span>
                </button>
            </div>

            <?php if ( $can_manage ) : ?>
                <a class="zsl-action zsl-panel-link" href="<?php echo esc_url( $panel_url ); ?>" title="Panel de gestión" aria-label="Ir al panel de gestión">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    <span class="zsl-action-label">Panel</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <nav class="zsl-nav" aria-label="Categorías principales">
        <div class="zsl-nav-inner">
            <?php foreach ( $nav_categories as $nav_cat ) :
                $nav_url = get_term_link( $nav_cat );
                if ( is_wp_error( $nav_url ) ) {
                    continue;
                }
                ?>
                <a href="<?php echo esc_url( $nav_url ); ?>"><?php echo esc_html( $nav_cat->name ); ?></a>
            <?php endforeach; ?>
            <?php if ( ! $has_offers ) : ?>
                <a class="zsl-nav-offers" href="<?php echo esc_url( $offers_url ); ?>">Ofertas</a>
            <?php endif; ?>
        </div>
    </nav>
</header>

<div class="zsl-loc-modal" id="zslLocModal" role="dialog" aria-modal="true" aria-labelledby="zslLocTitle" hidden>
    <div class="zsl-loc-box">
        <header class="zsl-loc-head">
            <strong id="zslLocTitle">¿Dónde quieres que entreguemos?</strong>
            <button type="button" class="zsl-loc-close zsl-loc-close-btn" data-zsl-close aria-label="Cerrar" hidden>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </header>
        <p class="zsl-loc-help">Selecciona la localidad para mostrarte productos disponibles y condiciones de entrega.</p>
        <div class="zsl-loc-list">
            <?php foreach ( $localidades as $loc ) : ?>
                <button type="button" class="zsl-loc-option <?php echo $current_id === (int) $loc['id'] ? 'active' : ''; ?>" data-loc="<?php echo (int) $loc['id']; ?>">
                    <span><?php echo esc_html( $loc['name'] ); ?></span>
                    <?php if ( $loc['note'] ) : ?><small><?php echo esc_html( $loc['note'] ); ?></small><?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>
