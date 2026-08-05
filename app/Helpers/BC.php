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
 * Class BC
 * @package CartRelay\App\Helpers
 */
class BC {


	public static function parseExtraData( $extra, $delimiter = '|', $secondary_delimiter = '=', $in_object = true ) {

		$parsed_data = [];
		$extra       = explode( $delimiter, $extra );
		foreach ( $extra as $item ) {
			$item_part = explode( $secondary_delimiter, $item, 2 );
			if ( count( $item_part ) !== 2 ) {
				continue;
			}

			$parsed_data[ $item_part[0] ] = $item_part[1];
		}

		if ( $in_object ) {
			$parsed_data = (object) $parsed_data;
		}

		return $parsed_data;
	}

}
