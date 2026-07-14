<?php

/**
 * Plugin file
 *
 * This file is read by WordPress to generate the plugin information
 * in the plugin admin area. This file also includes all dependencies
 * used by the plugin, registers the activation and deactivation functions,
 * and defines a function that starts the plugin.
 *
 */

/**
 * @link      https://gilbertrdz.dev
 * @since     1.0.0
 *
 * @package   WoocartBridge
 *
 * @wordpress-plugin
 * Plugin Name:       Woocart Bridge
 * Plugin URI:        https://gilbertrdz.dev
 * Description:       WooCart Bridge Free lets customers import and export WooCommerce carts with simple CSV files using SKU and quantity.
 * Version:           1.0.0
 * Requires PHP:      8.2
 * WC tested up to:   10.9
 * Author:            Gilbert Rodríguez
 * Author URI:        https://gilbertrdz.dev
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woocart-bridge
 * Domain Path:       /languages
 */

use WoocartBridge\App\Core\Plugin;
use WoocartBridge\App\Core\Activator;
use WoocartBridge\App\Core\Deactivator;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
	require_once dirname( __FILE__ ) . '/vendor/autoload.php';
}

define( 'WOOCART_BRIDGE_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'WOOCART_BRIDGE_DIR_URL', plugin_dir_url( __FILE__ ) );

/**
 * Declares compatibility with WooCommerce High-Performance Order Storage.
 */
add_action( 'before_woocommerce_init', static function(): void {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			__FILE__,
			true
		);
	}
} );

/**
 * Code that runs during plugin activation.
 */
register_activation_hook( __FILE__, [ Activator::class, 'activate' ] );

/**
 * Code that runs during plugin deactivation.
 */
register_deactivation_hook( __FILE__, [ Deactivator::class, 'deactivate' ] );

add_action( 'plugins_loaded', function() {
	( new Plugin() )->run();
} );
