<?php
/**
 * Plugin Name: Floridame Admin
 * Description: Panel privado para Floridame con control financiero, Zelle, productos y visitas.
 * Version: 1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'ZFL_VERSION', '1.4.0' );
define( 'ZFL_PATH', plugin_dir_path( __FILE__ ) );
define( 'ZFL_URL', plugin_dir_url( __FILE__ ) );
define( 'ZFL_SLUG', 'panel' );
define( 'ZFL_ALLOWED_ROLES', array( 'administrator', 'shop_manager' ) );

require_once ZFL_PATH . 'includes/class-zfl-install.php';
require_once ZFL_PATH . 'includes/class-zfl-auth.php';
require_once ZFL_PATH . 'includes/class-zfl-currencies.php';
require_once ZFL_PATH . 'includes/class-zfl-products.php';
require_once ZFL_PATH . 'includes/class-zfl-zelle.php';
require_once ZFL_PATH . 'includes/class-zfl-catalog.php';
require_once ZFL_PATH . 'includes/class-zfl-promos.php';
require_once ZFL_PATH . 'includes/class-zfl-orders.php';
require_once ZFL_PATH . 'includes/class-zfl-payroll.php';
require_once ZFL_PATH . 'includes/class-zfl-visits.php';
require_once ZFL_PATH . 'includes/class-zfl-reviews.php';
require_once ZFL_PATH . 'includes/class-zfl-phones.php';
require_once ZFL_PATH . 'includes/class-zfl-store.php';
require_once ZFL_PATH . 'includes/class-zfl-frontend.php';

register_activation_hook( __FILE__, array( 'ZFL_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ZFL_Install', 'deactivate' ) );

add_action( 'init', function () {
    $role = get_role( 'zfl_admin_2' );
    if ( ! $role ) {
        add_role( 'zfl_admin_2', 'Administrador 2', array(
            'read'                   => true,
            'manage_woocommerce'     => true,
            'edit_posts'             => true,
            'upload_files'           => true,
            'view_woocommerce_reports' => true,
        ) );
    }

    $role = get_role( 'zfl_gestor' );
    if ( ! $role ) {
        add_role( 'zfl_gestor', 'Gestor de la tienda', array(
            'read'                   => true,
            'manage_woocommerce'     => true,
            'edit_posts'             => true,
            'upload_files'           => true,
        ) );
    }
}, 5 );

add_action( 'plugins_loaded', function () {
    ZFL_Frontend::instance();

    $saved = get_option( 'zfl_version', '' );
    if ( $saved !== ZFL_VERSION ) {
        ZFL_Install::create_tables();
        flush_rewrite_rules();
        update_option( 'zfl_version', ZFL_VERSION );
    }
}, 10 );

add_action( 'plugins_loaded', function () {
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
        return;
    }
    require_once ZFL_PATH . 'includes/class-zfl-zelle-gateway.php';

    add_filter( 'woocommerce_payment_gateways', function ( $gateways ) {
        $gateways[] = 'ZFL_Zelle_Gateway';
        return $gateways;
    } );
}, 20 );
