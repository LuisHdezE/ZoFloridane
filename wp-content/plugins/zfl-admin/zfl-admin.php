<?php
/**
 * Plugin Name: ZoFloridane Admin
 * Description: Panel privado y storefront personalizado de ZoFloridane con catálogo, localidades, promociones, pedidos y Zelle.
 * Version: 1.4.6
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'ZFL_VERSION', '1.4.6' );
define( 'ZFL_PATH', plugin_dir_path( __FILE__ ) );
define( 'ZFL_URL', plugin_dir_url( __FILE__ ) );
define( 'ZFL_SLUG', 'panel' );
define( 'ZFL_ALLOWED_ROLES', array( 'administrator', 'shop_manager' ) );
define( 'ZFL_BRAND_NAME', 'ZoFloridane' );

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
require_once ZFL_PATH . 'includes/storefront-helpers.php';
require_once ZFL_PATH . 'includes/storefront-polish.php';
require_once ZFL_PATH . 'includes/class-zfl-frontend.php';
