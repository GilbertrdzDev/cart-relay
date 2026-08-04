<?php

/**
 * This file specifies reusable plugin settings helpers.
 *
 * @link         https://gilbertrdz.dev
 * @since        1.0.0
 *
 * @package      CartRelay
 * @subpackage   CartRelay/app/Helpers
 *
 * @author       Gilbert Rodríguez <gilbertrdz.dev@gmail.com>
 */

namespace CartRelay\App\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Class Settings
 * @package CartRelay\App\Helpers
 */
class Settings {
	public const OPTION_NAME = 'cart_relay_settings';

	private static ?array $cache = null;
	private static ?array $stored_cache = null;

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
		$settings = static::all();

		if ( ! array_key_exists( $key, $settings ) ) {
			return $default;
		}

		if ( ! array_key_exists( $key, static::stored() ) ) {
			return static::translate_default( $key, $settings[ $key ] );
		}

		return $settings[ $key ];
	}

	/**
	 * Gets all known settings while preserving translated defaults.
	 */
	public static function all( bool $translate_defaults = false ): array {
		if ( static::$cache === null ) {
			static::$cache = array_merge( static::$defaults, static::stored() );
		}

		$settings = static::$cache;

		if ( $translate_defaults ) {
			foreach ( static::$defaults as $key => $value ) {
				if ( ! array_key_exists( $key, static::stored() ) ) {
					$settings[ $key ] = static::translate_default( $key, $value );
				}
			}
		}

		return $settings;
	}

	/**
	 * Updates only allowed values present in the submitted payload.
	 */
	public static function update( array $values, array $allowed_keys ): void {
		$stored = static::stored();

		foreach ( $allowed_keys as $key ) {
			if ( array_key_exists( $key, $values ) ) {
				$stored[ $key ] = $values[ $key ];
			}
		}

		update_option( static::OPTION_NAME, $stored );
		static::$cache        = null;
		static::$stored_cache = null;
	}

	private static function stored(): array {
		if ( static::$stored_cache === null ) {
			$stored = get_option( static::OPTION_NAME, [] );
			static::$stored_cache = is_array( $stored ) ? $stored : [];
		}

		return static::$stored_cache;
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
			'export_button_text' => __( 'Export cart', 'cart-relay' ),
			'import_button_text' => __( 'Import cart', 'cart-relay' ),
			default              => $value,
		};
	}

}
