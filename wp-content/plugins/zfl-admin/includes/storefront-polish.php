<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Ajustes finales de presentación del storefront.
 *
 * - Base Verde como tema predeterminado para nuevos visitantes.
 * - Correcciones visuales del logo en Base Negra.
 * - Controles del carrusel más visibles y accesibles.
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

    // El selector existente conserva cualquier preferencia explícita del usuario.
    // Solo los visitantes sin preferencia previa arrancan en Base Verde.
    wp_add_inline_script(
        'zfl-store',
        "(function(){try{var k='zfl_base_theme';var v=localStorage.getItem(k);if(v!=='black'&&v!=='green'){localStorage.setItem(k,'green');}}catch(e){}})();",
        'before'
    );

    // Corrige la clase dark-mode heredada: Base Verde es realmente clara,
    // mientras Base Negra conserva el modo oscuro y ambas siguen persistiendo.
    wp_enqueue_script(
        'zfl-storefront-theme-default',
        ZFL_URL . 'frontend/assets/storefront-theme-default.js',
        array( 'zfl-store' ),
        ZFL_VERSION,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'zfl_storefront_polish_assets', 40 );
