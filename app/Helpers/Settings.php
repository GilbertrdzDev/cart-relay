<?php

/**
 * This file specifies reusable plugin settings helpers.
 *
 * @link         https://gilbertrdz.dev
 * @since        1.0.0
 *
 * @package      WoocartBridge
 * @subpackage   WoocartBridge/app/Helpers
 *
 * @author       Gilbert Rodríguez <gilbertrdz.dev@gmail.com>
 */

namespace WoocartBridge\App\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Class Settings
 * @package WoocartBridge\App\Helpers
 */
class Settings {

	/**
	 * Default plugin settings.
	 *
	 * @var array
	 */
	protected static array $defaults = [
		'export_enabled'     => true,
		'import_enabled'     => true,
		'export_button_text' => 'Exportar carrito',
		'import_button_text' => 'Importar carrito',
		'logged_in_only'     => false,
		'import_mode'        => 'merge',
		'button_location'    => 'woocommerce_after_cart_table',
	];

	/**
	 * Gets a plugin setting by key.
	 *
	 * @param string $key
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	public static function get( string $key, mixed $default = null ): mixed {
		$options = get_option( 'woocart_bridge_settings', [] );

		if ( ! is_array( $options ) ) {
			$options = [];
		}

		$settings = array_merge( static::$defaults, $options );

		return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
	}

}
