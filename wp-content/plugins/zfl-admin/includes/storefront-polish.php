<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Ajustes finales de presentación y compatibilidad del storefront.
 *
 * Esta capa mantiene el rediseño desacoplado de la configuración histórica
 * de la portada en cada instalación de WordPress. En producción la portada
 * puede no tener asignada explícitamente la plantilla zfl-home.php aunque
 * LocalWP sí la tenga; por eso la Home ZoFloridane se fuerza para la portada
 * real del sitio sin depender del editor ni de Ajustes > Lectura.
 */

/**
 * Base Verde como valor inicial real, antes del primer render.
 *
 * La migración se ejecuta una sola vez por navegador. Después de eso se
 * respeta cualquier elección manual del usuario mediante zfl_base_theme.
 */
function zfl_storefront_green_bootstrap() {
    if ( is_admin() || ! class_exists( 'ZFL_Store' ) || ! ZFL_Store::is_store_page() ) {
        return;
    }

    echo '<script>(function(){try{var k="zfl_base_theme",m="zfl_theme_default_green_v147";if(localStorage.getItem(m)!=="1"){localStorage.setItem(k,"green");localStorage.setItem(m,"1");}}catch(e){}})();</script>' . "\n";
}
add_action( 'wp_head', 'zfl_storefront_green_bootstrap', 1 );

/**
 * Garantiza que la portada pública use la Home nueva incluso si la base de
 * datos de producción conserva una plantilla histórica de Electro.
 */
function zfl_storefront_force_front_page_template( $template ) {
    if ( is_admin() || ! is_front_page() ) {
        return $template;
    }

    $custom = ZFL_PATH . 'frontend/views/home-zofloridane.php';
    return file_exists( $custom ) ? $custom : $template;
}
add_filter( 'template_include', 'zfl_storefront_force_front_page_template', 120 );

/**
 * Carga los assets de la Home cuando la portada no tiene zfl-home.php
 * guardada como page template. ZFL_Store ya los carga en LocalWP cuando sí
 * existe esa asignación, y wp_enqueue_* evita duplicados por handle.
 */
function zfl_storefront_front_page_assets() {
    if ( is_admin() || ! class_exists( 'ZFL_Store' ) || ! ZFL_Store::is_store_page() || ! is_front_page() ) {
        return;
    }

    wp_enqueue_style(
        'zfl-home',
        ZFL_URL . 'frontend/assets/home.css',
        array( 'zfl-store' ),
        ZFL_VERSION
    );

    wp_enqueue_script(
        'zfl-home',
        ZFL_URL . 'frontend/assets/home.js',
        array(),
        ZFL_VERSION,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'zfl_storefront_front_page_assets', 35 );

/**
 * Capa visual final del logo y los controles del carrusel.
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
