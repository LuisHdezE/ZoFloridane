<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ZFL_Store {

    const COOKIE = 'zfl_localidad';

    public static function init() {
        add_action( 'wp_body_open', array( __CLASS__, 'render_header' ), 1 );
        add_action( 'electro_before_header', array( __CLASS__, 'render_header' ), 1 );
        add_action( 'wp_footer', array( __CLASS__, 'render_footer' ), 1 );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
        add_filter( 'body_class', array( __CLASS__, 'add_body_class' ) );
        add_action( 'pre_get_posts', array( __CLASS__, 'filter_products' ) );
        add_filter( 'theme_page_templates', array( __CLASS__, 'register_home_template' ) );
        add_filter( 'template_include', array( __CLASS__, 'maybe_load_home_template' ), 99 );
        add_filter( 'template_include', array( __CLASS__, 'maybe_load_single_product' ), 99 );
        add_filter( 'template_include', array( __CLASS__, 'maybe_load_cart_template' ), 99 );
        add_filter( 'woocommerce_account_menu_items', array( __CLASS__, 'account_menu_items' ), 20 );
        add_action( 'template_redirect', array( __CLASS__, 'account_redirect_root' ) );
        add_action( 'template_redirect', array( __CLASS__, 'account_view_order_redirect' ) );
        add_action( 'init', array( __CLASS__, 'register_pwa' ), 20 );
        add_filter( 'query_vars', array( __CLASS__, 'pwa_query_vars' ) );
        add_action( 'template_redirect', array( __CLASS__, 'serve_pwa' ) );
        add_action( 'wp_head', array( __CLASS__, 'head_pwa' ) );
    }

    /* ── PWA instalable ─────────────────────────── */

    public static function register_pwa() {
        add_rewrite_rule( '^zfl-manifest\.json$', 'index.php?zfl_pwa=manifest', 'top' );
        add_rewrite_rule( '^zfl-sw\.js$', 'index.php?zfl_pwa=sw', 'top' );

        if ( get_option( 'zfl_version', '' ) !== ZFL_VERSION ) {
            flush_rewrite_rules();
            update_option( 'zfl_version', ZFL_VERSION );
        }
    }

    public static function pwa_query_vars( $vars ) {
        $vars[] = 'zfl_pwa';
        return $vars;
    }

    public static function serve_pwa() {
        $pwa = get_query_var( 'zfl_pwa' );
        if ( ! $pwa ) {
            return;
        }

        if ( 'manifest' === $pwa ) {
            header( 'Content-Type: application/manifest+json; charset=utf-8' );
            header( 'Cache-Control: no-cache' );
            echo wp_json_encode( self::manifest_data() );
            exit;
        }

        if ( 'sw' === $pwa ) {
            header( 'Content-Type: application/javascript; charset=utf-8' );
            header( 'Cache-Control: no-cache, no-store, must-revalidate' );
            header( 'Service-Worker-Allowed: /' );
            readfile( ZFL_PATH . 'frontend/assets/sw.js' );
            exit;
        }
    }

    private static function manifest_data() {
        $icon_512 = function_exists( 'get_site_icon_url' ) ? get_site_icon_url( 512 ) : '';
        $icon_192 = function_exists( 'get_site_icon_url' ) ? get_site_icon_url( 192 ) : '';

        if ( ! $icon_512 ) {
            $icon_512 = ZFL_URL . 'frontend/assets/logo-bg-black.png';
        }
        if ( ! $icon_192 ) {
            $icon_192 = $icon_512;
        }

        return array(
            'name'             => get_bloginfo( 'name' ),
            'short_name'       => get_bloginfo( 'name' ),
            'description'      => 'Compra en EE. UU. y envía a tus familiares en Cuba.',
            'start_url'        => home_url( '/' ),
            'scope'            => home_url( '/' ),
            'display'          => 'standalone',
            'orientation'      => 'portrait',
            'background_color' => '#070a09',
            'theme_color'      => '#20c77a',
            'icons'            => array(
                array(
                    'src'   => $icon_192,
                    'sizes' => '192x192',
                    'type'  => 'image/png',
                    'purpose' => 'any',
                ),
                array(
                    'src'   => $icon_512,
                    'sizes' => '512x512',
                    'type'  => 'image/png',
                    'purpose' => 'any',
                ),
            ),
        );
    }

    public static function head_pwa() {
        if ( is_admin() ) {
            return;
        }
        // El panel es la app de gestión de los gestores: también recibe push.
        echo '<link rel="manifest" href="' . esc_url( home_url( '/zfl-manifest.json' ) ) . '">' . "\n";
        echo '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">' . "\n";
        echo '<meta name="theme-color" content="#070a09">' . "\n";
        echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
        echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
        echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">' . "\n";
        echo '<meta name="apple-mobile-web-app-title" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
        echo '<style>body{margin:0;padding:0;padding-bottom:env(safe-area-inset-bottom, 0px);}</style>' . "\n";
        echo '<script>if("serviceWorker" in navigator){window.addEventListener("load",function(){navigator.serviceWorker.register("' . esc_url( home_url( '/zfl-sw.js/' ) ) . '?v=' . esc_attr( ZFL_VERSION ) . '").catch(function(){});});}</script>' . "\n";

        if ( self::is_store_page() ) {
            echo '<script>try{fetch(' . wp_json_encode( esc_url_raw( rest_url( 'zfl-visit/v1/track' ) ) ) . ',{method:"POST",credentials:"include",keepalive:true,headers:{"Content-Type":"application/json"},body:JSON.stringify({type:"visit"})}).catch(function(){});}catch(e){}</script>' . "\n";
        }

        // Tema público: negro por defecto o verde. Se aplica antes del render para evitar flash.
        echo '<script>(function(){try{var t=localStorage.getItem("zfl_base_theme")==="green"?"green":"black";var r=document.documentElement;r.classList.add("dark-mode","zfl-theme-"+t);r.setAttribute("data-zfl-theme",t);var m=document.querySelector("meta[name=theme-color]");if(m)m.setAttribute("content",t==="green"?"#073d2a":"#070a09");}catch(e){document.documentElement.classList.add("dark-mode","zfl-theme-black");}})();</script>' . "\n";
    }

    public static function account_menu_items( $items ) {
        unset( $items['downloads'], $items['dashboard'] );
        return $items;
    }

    public static function account_redirect_root() {
        if ( ! function_exists( 'is_account_page' ) || ! function_exists( 'is_wc_endpoint_url' ) ) {
            return;
        }
        if ( ! is_account_page() || is_wc_endpoint_url() || ! is_user_logged_in() ) {
            return;
        }
        wp_safe_redirect( wc_get_account_endpoint_url( 'orders' ) );
        exit;
    }

    // "Ver" un pedido en Mi cuenta → nuestra página de rastreo.
    public static function account_view_order_redirect() {
        if ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'view-order' ) ) {
            return;
        }
        if ( ! is_user_logged_in() ) {
            return;
        }

        $order_id = (int) get_query_var( 'view-order' );
        $order    = $order_id ? wc_get_order( $order_id ) : false;

        if ( ! $order ) {
            return;
        }

        // Solo si el pedido pertenece al usuario; si no, vista nativa.
        if ( (int) $order->get_customer_id() !== get_current_user_id() ) {
            return;
        }

        $phone = (string) $order->get_meta( '_er_buyer_phone' );
        if ( '' === $phone ) {
            $phone = (string) $order->get_billing_phone();
        }

        $last4 = substr( preg_replace( '/\D/', '', $phone ), -4 );

        wp_safe_redirect( add_query_arg( array(
            'pedido'   => $order_id,
            'telefono' => $last4,
        ), home_url( '/rastrear-pedido/' ) ) );
        exit;
    }

    const HOME_TEMPLATE = 'zfl-home.php';

    public static function register_home_template( $templates ) {
        $templates[ self::HOME_TEMPLATE ] = 'Home Floridame';
        return $templates;
    }

    public static function maybe_load_home_template( $template ) {
        if ( is_page() && self::HOME_TEMPLATE === get_page_template_slug( get_queried_object_id() ) ) {
            $custom = ZFL_PATH . 'frontend/views/home-zofloridane.php';
            if ( file_exists( $custom ) ) {
                return $custom;
            }
        }
        return $template;
    }

    public static function is_home_template_page() {
        return is_page() && self::HOME_TEMPLATE === get_page_template_slug( get_queried_object_id() );
    }

    public static function maybe_load_single_product( $template ) {
        if ( is_singular( 'product' ) ) {
            $custom = ZFL_PATH . 'frontend/views/single-product.php';
            if ( file_exists( $custom ) ) {
                return $custom;
            }
        }
        return $template;
    }

    public static function is_single_product_page() {
        return is_singular( 'product' );
    }

    public static function maybe_load_cart_template( $template ) {
        if ( function_exists( 'is_cart' ) && is_cart() ) {
            $custom = ZFL_PATH . 'frontend/views/cart-page.php';
            if ( file_exists( $custom ) ) {
                return $custom;
            }
        }
        return $template;
    }

    public static function is_cart_page() {
        return function_exists( 'is_cart' ) && is_cart();
    }

    public static function is_store_page() {
        if ( is_admin() ) {
            return false;
        }
        if ( get_query_var( 'zfl_route' ) ) {
            return false;
        }
        return true;
    }

    public static function get_current_localidad() {
        $id = isset( $_COOKIE[ self::COOKIE ] ) ? (int) $_COOKIE[ self::COOKIE ] : 0;
        if ( $id <= 0 ) {
            return 0;
        }
        $term = get_term( $id, 'zfl_localidad' );
        if ( ! $term || is_wp_error( $term ) ) {
            return 0;
        }
        return (int) $term->term_id;
    }

    public static function get_current_localidad_name() {
        $id = self::get_current_localidad();
        if ( ! $id ) {
            return '';
        }
        $term = get_term( $id, 'zfl_localidad' );
        return ( $term && ! is_wp_error( $term ) ) ? $term->name : '';
    }

    public static function render_header() {
        static $done = false;
        if ( $done || ! self::is_store_page() ) {
            return;
        }
        $done = true;

        if ( ! class_exists( 'ZFL_Catalog' ) ) {
            return;
        }

        $localidades  = ZFL_Catalog::get_localidades();
        $current_id   = self::get_current_localidad();
        $current_name = self::get_current_localidad_name();

        $logo = '';
        if ( function_exists( 'has_custom_logo' ) && has_custom_logo() ) {
            $logo_id = (int) get_theme_mod( 'custom_logo' );
            if ( $logo_id ) {
                $logo = wp_get_attachment_image_url( $logo_id, 'full' );
            }
        }

        $search_action = home_url( '/' );
        $account_url   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' );
        $cart_url      = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'cart' ) : home_url( '/' );
        $cart_count    = ( function_exists( 'WC' ) && WC()->cart ) ? (int) WC()->cart->get_cart_contents_count() : 0;

        $can_manage = is_user_logged_in() && class_exists( 'ZFL_Auth' ) && ZFL_Auth::is_allowed_user();
        $panel_url  = home_url( ZFL_SLUG . '/dashboard/' );

        include ZFL_PATH . 'frontend/views/store-header.php';
    }

    public static function render_footer() {
        static $done = false;
        if ( $done || ! self::is_store_page() ) {
            return;
        }
        $done = true;

        if ( ! class_exists( 'ZFL_Catalog' ) ) {
            return;
        }

        $localidades = ZFL_Catalog::get_localidades();

        $logo = '';
        if ( function_exists( 'has_custom_logo' ) && has_custom_logo() ) {
            $logo_id = (int) get_theme_mod( 'custom_logo' );
            if ( $logo_id ) {
                $logo = wp_get_attachment_image_url( $logo_id, 'full' );
            }
        }

        $shop_url    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
        $account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' );
        $cart_url    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'cart' ) : home_url( '/' );

        include ZFL_PATH . 'frontend/views/store-footer.php';
    }

    public static function enqueue() {
        if ( ! self::is_store_page() ) {
            return;
        }
        wp_enqueue_style( 'zfl-store', ZFL_URL . 'frontend/assets/store.css', array(), ZFL_VERSION );
        wp_enqueue_script( 'zfl-store', ZFL_URL . 'frontend/assets/store.js', array(), ZFL_VERSION, true );

        if ( self::is_single_product_page() ) {
            wp_enqueue_style( 'zfl-sp', ZFL_URL . 'frontend/assets/single-product.css', array(), ZFL_VERSION );
        }

        if ( self::is_home_template_page() ) {
            wp_enqueue_style( 'zfl-home', ZFL_URL . 'frontend/assets/home.css', array( 'zfl-store' ), ZFL_VERSION );
            wp_enqueue_script( 'zfl-home', ZFL_URL . 'frontend/assets/home.js', array(), ZFL_VERSION, true );
        }

        wp_enqueue_style( 'zfl-dark', ZFL_URL . 'frontend/assets/dark-mode.css', array(), ZFL_VERSION );
        wp_enqueue_style( 'zfl-brand-black', ZFL_URL . 'frontend/assets/zofloridane-black.css', array( 'zfl-dark' ), ZFL_VERSION );
    }

    public static function add_body_class( $classes ) {
        if ( self::is_store_page() ) {
            $classes[] = 'zfl-store-active';
        }
        return $classes;
    }

    public static function filter_products( $query ) {
        if ( is_admin() || ! $query->is_main_query() ) {
            return;
        }

        $loc = self::get_current_localidad();
        if ( ! $loc ) {
            return;
        }

        $is_product_archive = $query->is_post_type_archive( 'product' ) || $query->is_tax( array( 'product_cat', 'product_tag' ) );
        $is_product_search  = $query->is_search() && 'product' === $query->get( 'post_type' );

        if ( ! $is_product_archive && ! $is_product_search ) {
            return;
        }

        $tax_query = $query->get( 'tax_query' );
        if ( ! is_array( $tax_query ) ) {
            $tax_query = array();
        }
        $tax_query[] = array(
            'taxonomy' => 'zfl_localidad',
            'field'    => 'term_id',
            'terms'    => $loc,
        );
        $query->set( 'tax_query', $tax_query );
    }
}

ZFL_Store::init();
