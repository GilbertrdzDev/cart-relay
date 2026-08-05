<?php

/**
 * This file specifies the admin-side plugin functionality.
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
 * Class Str
 * @package CartRelay\App\Helpers
 */
class Str {

	/**
	 * The cache of snake-cased words.
	 *
	 * @var array
	 */
	protected static array $snakeCache = [];

	/**
	 * The cache of camel-cased words.
	 *
	 * @var array
	 */
	protected static array $camelCache = [];

	/**
	 * The cache of studly-cased words.
	 *
	 * @var array
	 */
	protected static array $studlyCache = [];

	/**
	 * The cache of ucwords-force.
	 *
	 * @var array
	 */
	protected static array $ucwordsforceCache = [];

	/**
	 * Convert a value to camel case.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public static function camel( string $value ): string {

		if ( isset( static::$camelCache[ $value ] ) ) {
			return static::$camelCache[ $value ];
		}

		return static::$camelCache[ $value ] = lcfirst( static::studly( $value ) );
	}

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	public static function lower( string $value ): string {
		return mb_strtolower( $value, 'UTF-8' );
	}

	/**
	 * Convert a string to kebab case.
	 *
	 * @param string $value
	 * @param bool   $upper
	 *
	 * @return string
	 */
	public static function kebab( string $value, bool $upper = false ): string {

		$value = static::snake( $value, '-' );

		if ( $upper ) {
			$value = mb_strtoupper( $value, 'UTF-8' );
		}

		return $value;
	}

	/**
	 * Convert a string to snake case.
	 *
	 * @param string $value
	 * @param string $delimiter
	 *
	 * @return string
	 */
	public static function snake( string $value, string $delimiter = '_' ): string {

		$key = $value;

		if ( isset( static::$snakeCache[ $key ][ $delimiter ] ) ) {
			return static::$snakeCache[ $key ][ $delimiter ];
		}

		if ( ! ctype_lower( $value ) ) {
			$value = preg_replace( '/\s+/u', '', ucwords( $value ) );

			$value = static::lower( preg_replace( '/(.)(?=[A-Z])/u', '$1' . $delimiter, $value ) );
		}

		return static::$snakeCache[ $key ][ $delimiter ] = $value;
	}

	/**
	 * Determine if a given string starts with a given substring.
	 *
	 * @param string          $haystack
	 * @param string|string[] $needles
	 *
	 * @return bool
	 */
	public static function startsWith( string $haystack, $needles ): bool {

		foreach ( (array) $needles as $needle ) {
			if ( (string) $needle !== '' && strncmp( $haystack, $needle, strlen( $needle ) ) === 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine if a given string ends with a given substring.
	 *
	 * @param string          $haystack
	 * @param string|string[] $needles
	 *
	 * @return bool
	 */
	public static function endsWith( string $haystack, $needles ): bool {

		foreach ( (array) $needles as $needle ) {
			if ( $needle !== '' && substr( $haystack, -strlen( $needle ) ) === (string) $needle ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Convert a value to studly caps case.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public static function studly( string $value ): string {

		$key = $value;

		if ( isset( static::$studlyCache[ $key ] ) ) {
			return static::$studlyCache[ $key ];
		}

		$value = ucwords( str_replace( [ '-', '_' ], ' ', $value ) );

		return static::$studlyCache[ $key ] = str_replace( ' ', '', $value );
	}

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	public static function ucwordsforce( string $value ): string {

		$key = $value;

		if ( isset( static::$ucwordsforceCache[ $key ] ) ) {
			return static::$ucwordsforceCache[ $key ];
		}

		$value = static::lower( $value );

		return static::$ucwordsforceCache[ $key ] = ucwords( $value );
	}

}
