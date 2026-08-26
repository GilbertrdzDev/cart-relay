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
 * @package   CartRelay
 *
 * @wordpress-plugin
 * Plugin Name:       Cart Relay for WooCommerce
 * Description:       Import and export WooCommerce carts with simple CSV files using SKU and quantity.
 * Version:           1.0.3
 * Requires at least: 6.5
 * Requires PHP:      8.2
 * Requires Plugins:  woocommerce
 * WC tested up to:   11.0
 * Author:            Gilbert Rodríguez
 * Author URI:        https://gilbertrdz.dev
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cart-relay
 * Domain Path:       /languages
 */
use CartRelay\App\Core\Plugin;
use CartRelay\App\Core\Activator;
use CartRelay\App\Core\Deactivator;
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

define( 'CART_RELAY_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'CART_RELAY_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'CART_RELAY_VERSION', '1.0.3' );
/**
 * Declares compatibility with WooCommerce High-Performance Order Storage.
 */
add_action(
	'before_woocommerce_init',
	static function (): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

/**
 * Code that runs during plugin activation.
 */
register_activation_hook( __FILE__, [ Activator::class, 'activate' ] );

/**
 * Code that runs during plugin deactivation.
 */
register_deactivation_hook( __FILE__, [ Deactivator::class, 'deactivate' ] );

add_action(
	'plugins_loaded',
	function () {
		( new Plugin() )->run();
	}
);
