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
		'export_button_text' => 'Export cart',
		'import_button_text' => 'Import cart',
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

		if ( ! array_key_exists( $key, $settings ) ) {
			return $default;
		}

		if ( ! array_key_exists( $key, $options ) ) {
			return static::translate_default( $key, $settings[ $key ] );
		}

		return $settings[ $key ];
	}

	/**
	 * Translates visible default setting values while preserving saved options as-is.
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return mixed
	 */
	private static function translate_default( string $key, mixed $value ): mixed {
		return match ( $key ) {
			'export_button_text' => __( 'Export cart', 'woocart-bridge' ),
			'import_button_text' => __( 'Import cart', 'woocart-bridge' ),
			default              => $value,
		};
	}

}
