<?php

/**
 * This file specifies reusable CSV helpers.
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

use SplFileObject;
use SplTempFileObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class Csv
 * @package WoocartBridge\App\Helpers
 */
class Csv {

	/**
	 * Parses an uploaded CSV file into row arrays.
	 *
	 * @param array $file
	 *
	 * @return array
	 */
	public static function parse_upload( array $file ): array {
		if ( empty( $file['tmp_name'] ) ) {
			return [];
		}

		$csv = new SplFileObject( $file['tmp_name'] );
		$csv->setFlags( SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE );

		$rows            = [];
		$has_read_header = false;

		foreach ( $csv as $row ) {
			if ( ! $has_read_header ) {
				$has_read_header = true;
				continue;
			}

			if ( $row === [ null ] || $row === false ) {
				continue;
			}

			$rows[] = $row;
		}

		return $rows;
	}

	/**
	 * Builds CSV content from row arrays.
	 *
	 * @param array $rows
	 *
	 * @return string
	 */
	public static function build( array $rows ): string {
		$file = new SplTempFileObject();

		foreach ( $rows as $row ) {
			$file->fputcsv( $row );
		}

		$file->rewind();

		$content = '';

		while ( ! $file->eof() ) {
			$content .= $file->fgets();
		}

		return $content;
	}

}
