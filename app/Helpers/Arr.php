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
 * Class Arr
 * @package CartRelay\App\Helpers
 */
class Arr {

	/**
	 * This method adds an array or value to another array
	 * by searching for a value and then inserting it
	 * before or after the found value.
	 *
	 * @param String $search
	 * @param array  $arraySearch
	 * @param array  $arrayInsert
	 * @param bool   $keyInValue
	 * @param bool   $before
	 *
	 * @return array
	 */
	public static function addForKey(
		string $search,
		array $arraySearch,
		array $arrayInsert,
		bool $keyInValue = false,
		bool $before = false
	): array {

		$c = 0;
		$found = false;

		foreach ( $arraySearch as $indice => $valor ) {

			$c++;

			if ( $keyInValue ) {
				if ( $search === $valor[ $keyInValue ] ) {
					$found = true;
					break;
				}
			} else {
				if ( $search === $indice ) {
					$found = true;
					break;
				}
			}

		}

		if ( $before ) {
			$c--;
		}
		
		if ( $found ) {

			$arrLeft  = array_slice( $arraySearch, 0, $c );
			$arrRight = array_slice( $arraySearch, $c );

			$found = array_merge( $arrLeft, $arrayInsert, $arrRight );
			
		}

		return $found;

	}

}
