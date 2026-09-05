<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Ajustes finales de presentación del storefront.
 *
 * El comportamiento del selector Negro / Verde vive en store.js para evitar
 * dos controladores de tema compitiendo entre sí. Esta capa solo carga los
 * ajustes visuales del logo y los controles del carrusel.
 */
function zfl_storefront_polish_assets() {
    if ( ! class_exists( 'ZFL_Store' ) || ! ZFL_Store::is_store_page() ) {
        return;
    }

    wp_enqueue_style(
        'zfl-storefront-polish',
        ZFL_URL . 'frontend/assets/storefront-polish.css',
        array( 'zfl-storefront-hero-quality' ),
        ZFL_VERSION
    );
}
add_action( 'wp_enqueue_scripts', 'zfl_storefront_polish_assets', 40 );
