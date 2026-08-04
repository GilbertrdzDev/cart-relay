<?php

/**
 * This file specifies reusable CSV helpers.
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

use LengthException;
use SplFileObject;
use SplTempFileObject;
use UnexpectedValueException;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- CSV validation exceptions are escaped at their output boundaries.

/**
 * Class Csv
 * @package CartRelay\App\Helpers
 */
class Csv {
	public const MAX_ROWS = 500;
	public const MAX_COLUMNS = 10;
	public const MAX_CELL_LENGTH = 2048;

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
		$csv->setCsvControl( ',', '"', '' );
		$csv->setFlags( SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE );

		$rows    = [];
		$headers = [];

		foreach ( $csv as $row ) {
			if ( ! is_array( $row ) || $row === [ null ] || self::is_empty_row( $row ) ) {
				continue;
			}

			self::validate_row_shape( $row );

			if ( $headers === [] ) {
				$headers = self::normalize_headers( $row );
				self::validate_required_headers( $headers );
				continue;
			}

			if ( count( $rows ) >= self::MAX_ROWS ) {
				throw new LengthException( // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are escaped at output boundaries.
					sprintf(
						/* translators: %d: maximum number of CSV data rows. */
						__( 'The CSV cannot contain more than %d product rows.', 'cart-relay' ),
						self::MAX_ROWS
					)
				);
			}

			$item = [
				'__row' => $csv->key() + 1,
			];

			foreach ( $headers as $index => $header ) {
				$item[ $header ] = isset( $row[ $index ] ) ? self::normalize_cell( $row[ $index ] ) : '';
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
			$safe_row = array_map( [ self::class, 'escape_spreadsheet_formula' ], $row );
			$file->fputcsv( $safe_row, ',', '"', '' );
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

	private static function normalize_headers( array $row ): array {
		$headers = [];

		foreach ( $row as $index => $header ) {
			$normalized = self::normalize_header( $header );

			if ( $normalized === '' ) {
				continue;
			}

			if ( in_array( $normalized, $headers, true ) ) {
				throw new UnexpectedValueException( // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are escaped at output boundaries.
					sprintf(
						/* translators: %s: duplicated CSV column name. */
						__( 'The CSV contains the duplicate column "%s".', 'cart-relay' ),
						$normalized
					)
				);
			}

			$headers[ $index ] = $normalized;
		}

		if ( $headers === [] ) {
			throw new UnexpectedValueException( __( 'The CSV header row is empty.', 'cart-relay' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are escaped at output boundaries.
		}

		return $headers;
	}

	private static function validate_required_headers( array $headers ): void {
		if ( ! in_array( 'quantity', $headers, true ) ) {
			throw new UnexpectedValueException( __( 'The CSV must include a quantity column.', 'cart-relay' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are escaped at output boundaries.
		}

		if ( ! array_intersect( [ 'product_id', 'variation_id', 'sku' ], $headers ) ) {
			throw new UnexpectedValueException( // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are escaped at output boundaries.
				__( 'The CSV must include product_id, variation_id, or sku.', 'cart-relay' )
			);
		}
	}

	private static function validate_row_shape( array $row ): void {
		if ( count( $row ) > self::MAX_COLUMNS ) {
			throw new LengthException( // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are escaped at output boundaries.
				sprintf(
					/* translators: %d: maximum number of CSV columns. */
					__( 'The CSV cannot contain more than %d columns.', 'cart-relay' ),
					self::MAX_COLUMNS
				)
			);
		}

		foreach ( $row as $value ) {
			if ( strlen( (string) $value ) > self::MAX_CELL_LENGTH ) {
				throw new LengthException( // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are escaped at output boundaries.
					sprintf(
						/* translators: %d: maximum CSV cell length in bytes. */
						__( 'CSV values cannot exceed %d bytes.', 'cart-relay' ),
						self::MAX_CELL_LENGTH
					)
				);
			}
		}
	}

	private static function normalize_cell( mixed $value ): string {
		$value = trim( (string) $value );

		if ( preg_match( '/^\x27[=+\-@\t\r]/', $value ) === 1 ) {
			return substr( $value, 1 );
		}

		return $value;
	}

	private static function escape_spreadsheet_formula( mixed $value ): mixed {
		if ( ! is_string( $value ) || preg_match( '/^[=+\-@\t\r]/', $value ) !== 1 ) {
			return $value;
		}

		return "'" . $value;
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

// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
