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

		$rows    = [];
		$headers = [];

		foreach ( $csv as $row ) {
			if ( $row === [ null ] || $row === false || self::is_empty_row( $row ) ) {
				continue;
			}

			if ( $headers === [] ) {
				$headers = array_map( [ self::class, 'normalize_header' ], $row );
				$headers = array_values( array_filter( $headers ) );
				continue;
			}

			$item = [
				'__row' => $csv->key() + 1,
			];

			foreach ( $headers as $index => $header ) {
				$item[ $header ] = isset( $row[ $index ] ) ? trim( (string) $row[ $index ] ) : '';
			}

			$rows[] = $item;
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

	private static function normalize_header( mixed $header ): string {
		$header = strtolower( trim( (string) $header ) );
		$header = preg_replace( '/[^a-z0-9]+/', '_', $header ) ?? '';

		return trim( $header, '_' );
	}

	private static function is_empty_row( array $row ): bool {
		foreach ( $row as $value ) {
			if ( trim( (string) $value ) !== '' ) {
				return false;
			}
		}

		return true;
	}

}
