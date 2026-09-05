<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Helpers de presentación del storefront ZoFloridane.
 *
 * Mantienen la Home desacoplada de categorías demo de Electro y concentran
 * pequeños ajustes de UI sin duplicar la lógica comercial existente.
 */

function zfl_storefront_asset_url( $relative_path ) {
    return ZFL_URL . 'frontend/assets/' . ltrim( (string) $relative_path, '/' );
}

function zfl_storefront_logo_url() {
    return zfl_storefront_asset_url( 'brand/zofloridane-logo.webp' );
}

function zfl_storefront_default_hero_slides( $shop_url, $loc_name = '' ) {
    $destination = $loc_name ? ' para ' . $loc_name : '';

    return array(
        array(
            '_image_url' => zfl_storefront_asset_url( 'hero/hero-family-delivery.avif' ),
            'title'      => 'Compra desde EE. UU. y entrégalo en Cuba.',
            'copy'       => 'Productos esenciales' . $destination . ', pago con Zelle y acompañamiento durante todo el pedido.',
            'link'       => $shop_url,
            'cta'        => 'Ver productos',
        ),
        array(
            '_image_url' => zfl_storefront_asset_url( 'hero/hero-camaguey-delivery.avif' ),
            'title'      => 'Entregas confiables para tu familia.',
            'copy'       => $loc_name ? 'Coordinamos la entrega en ' . $loc_name . ' con disponibilidad clara según tu destino.' : 'Selecciona la localidad y coordinamos la entrega con disponibilidad clara.',
            'link'       => home_url( '/#como-comprar' ),
            'cta'        => 'Cómo comprar',
        ),
        array(
            '_image_url' => zfl_storefront_asset_url( 'hero/hero-online-shopping.avif' ),
            'title'      => 'Haz tu pedido online de forma simple.',
            'copy'       => 'Alimentos, bebidas, aseo, higiene y más para tu familia en Cuba.',
            'link'       => $shop_url,
            'cta'        => 'Explorar catálogo',
        ),
    );
}

function zfl_storefront_normalize_label( $value ) {
    $value = remove_accents( wp_strip_all_tags( (string) $value ) );
    $value = strtolower( $value );
    $value = preg_replace( '/[^a-z0-9]+/', '-', $value );

    return trim( (string) $value, '-' );
}

function zfl_storefront_is_demo_category( $term ) {
    if ( ! is_object( $term ) || empty( $term->name ) ) {
        return true;
    }

    $label = zfl_storefront_normalize_label( $term->name . ' ' . $term->slug );
    $demo_markers = array(
        'laptops-computers',
        'computers-accessories',
        'cameras-photography',
        'video-games-consoles',
        'pc-gaming-headsets',
        'headphones',
        'smartphones-tablets',
        'cell-phones-tablets',
        'tv-video',
        'television-video',
        'home-entertainment',
        'audio-music',
        'car-electronics-gps',
        'printers-scanners',
        'smartwatches',
        'virtual-reality',
        'gadgets',
        'accessories-demo',
        'uncategorized',
        'sin-categorizar',
    );

    foreach ( $demo_markers as $marker ) {
        if ( false !== strpos( $label, $marker ) ) {
            return true;
        }
    }

    return false;
}

function zfl_storefront_category_priority( $term ) {
    $label = zfl_storefront_normalize_label( $term->name . ' ' . $term->slug );
    $priorities = array(
        'alimento'      => 10,
        'comida'        => 11,
        'bebida'        => 20,
        'aseo'          => 30,
        'higiene'       => 40,
        'perfumer'      => 50,
        'hogar'         => 60,
        'cocina'        => 61,
        'electrodomest' => 70,
        'oferta'        => 80,
    );

    foreach ( $priorities as $needle => $priority ) {
        if ( false !== strpos( $label, $needle ) ) {
            return $priority;
        }
    }

    return 100;
}

function zfl_storefront_get_categories( $limit = 8, $hide_empty = true ) {
    if ( ! taxonomy_exists( 'product_cat' ) ) {
        return array();
    }

    $terms = get_terms( array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => (bool) $hide_empty,
        'parent'     => 0,
        'number'     => 0,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ) );

    if ( is_wp_error( $terms ) ) {
        return array();
    }

    $terms = array_values( array_filter( $terms, static function ( $term ) {
        return ! zfl_storefront_is_demo_category( $term );
    } ) );

    usort( $terms, static function ( $a, $b ) {
        $priority_a = zfl_storefront_category_priority( $a );
        $priority_b = zfl_storefront_category_priority( $b );

        if ( $priority_a === $priority_b ) {
            return strcasecmp( $a->name, $b->name );
        }

        return $priority_a <=> $priority_b;
    } );

    return array_slice( $terms, 0, max( 0, (int) $limit ) );
}

function zfl_storefront_has_offers_category( $categories ) {
    foreach ( (array) $categories as $category ) {
        if ( false !== strpos( zfl_storefront_normalize_label( $category->name . ' ' . $category->slug ), 'oferta' ) ) {
            return true;
        }
    }

    return false;
}

function zfl_storefront_find_page_url( $paths ) {
    foreach ( (array) $paths as $path ) {
        $page = get_page_by_path( sanitize_title( $path ) );
        if ( $page && 'publish' === $page->post_status ) {
            return get_permalink( $page );
        }
    }

    return '';
}

function zfl_storefront_home_template_label( $templates ) {
    if ( isset( $templates[ ZFL_Store::HOME_TEMPLATE ] ) ) {
        $templates[ ZFL_Store::HOME_TEMPLATE ] = 'Home ZoFloridane';
    }

    return $templates;
}
add_filter( 'theme_page_templates', 'zfl_storefront_home_template_label', 100 );

/**
 * Extiende WC_Product_Query únicamente con los dos parámetros que necesita
 * la Home: ordenar por ventas y limitar por la taxonomía de localidad.
 */
function zfl_storefront_product_query_args( $query, $query_vars ) {
    if ( ! empty( $query_vars['zfl_best_sellers'] ) ) {
        $query['meta_key'] = 'total_sales';
        $query['orderby']  = 'meta_value_num';
        $query['order']    = 'DESC';
    }

    $localidad = isset( $query_vars['zfl_localidad'] ) ? (int) $query_vars['zfl_localidad'] : 0;
    if ( $localidad > 0 ) {
        if ( empty( $query['tax_query'] ) || ! is_array( $query['tax_query'] ) ) {
            $query['tax_query'] = array();
        }
        $query['tax_query'][] = array(
            'taxonomy' => 'zfl_localidad',
            'field'    => 'term_id',
            'terms'    => array( $localidad ),
        );
    }

    return $query;
}
add_filter( 'woocommerce_product_data_store_cpt_get_products_query', 'zfl_storefront_product_query_args', 10, 2 );

/**
 * Permite que el enlace "Ofertas" use productos realmente rebajados sin
 * crear una categoría artificial ni una administración paralela.
 */
function zfl_storefront_filter_offers( $query ) {
    if ( is_admin() || ! $query->is_main_query() || empty( $_GET['zfl_ofertas'] ) ) {
        return;
    }

    $is_product_archive = $query->is_post_type_archive( 'product' ) || $query->is_tax( array( 'product_cat', 'product_tag' ) );
    $is_product_search  = $query->is_search() && 'product' === $query->get( 'post_type' );

    if ( ! $is_product_archive && ! $is_product_search ) {
        return;
    }

    $sale_ids = function_exists( 'wc_get_product_ids_on_sale' ) ? wc_get_product_ids_on_sale() : array();
    $query->set( 'post__in', ! empty( $sale_ids ) ? array_map( 'intval', $sale_ids ) : array( 0 ) );
}
add_action( 'pre_get_posts', 'zfl_storefront_filter_offers', 30 );

/**
 * Capas finales del rediseño. Cada hoja posterior corrige únicamente
 * presentación y se mantiene fuera de la lógica comercial del storefront.
 */
function zfl_storefront_enqueue_v1() {
    if ( ! class_exists( 'ZFL_Store' ) || ! ZFL_Store::is_store_page() ) {
        return;
    }

    wp_enqueue_style(
        'zfl-storefront-v1',
        ZFL_URL . 'frontend/assets/storefront-v1.css',
        array( 'zfl-brand-black' ),
        ZFL_VERSION
    );

    wp_enqueue_style(
        'zfl-storefront-v1-2',
        ZFL_URL . 'frontend/assets/storefront-v1-2.css',
        array( 'zfl-storefront-v1' ),
        ZFL_VERSION
    );

    wp_enqueue_style(
        'zfl-storefront-hero-quality',
        ZFL_URL . 'frontend/assets/storefront-hero-quality.css',
        array( 'zfl-storefront-v1-2' ),
        ZFL_VERSION
    );
}
add_action( 'wp_enqueue_scripts', 'zfl_storefront_enqueue_v1', 30 );
