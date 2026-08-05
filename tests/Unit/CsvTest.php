<?php

namespace CartRelay\Tests\Unit;

use CartRelay\App\Helpers\Csv;
use LengthException;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class CsvTest extends TestCase {
	private array $temporary_files = [];

	protected function tearDown(): void {
		foreach ( $this->temporary_files as $file ) {
			if ( is_file( $file ) ) {
				unlink( $file );
			}
		}

		parent::tearDown();
	}

	public function test_it_parses_supported_identifier_columns(): void {
		$rows = Csv::parse_upload( $this->uploaded_file( "sku,quantity\nABC-123,2\n" ) );

		self::assertSame( 'ABC-123', $rows[0]['sku'] );
		self::assertSame( '2', $rows[0]['quantity'] );
		self::assertSame( 2, $rows[0]['__row'] );
	}

	public function test_it_preserves_empty_header_column_positions(): void {
		$rows = Csv::parse_upload( $this->uploaded_file( "product_id,,sku,quantity\n123,,ABC-123,2\n" ) );

		self::assertSame( 'ABC-123', $rows[0]['sku'] );
	}

	public function test_it_escapes_and_restores_spreadsheet_formulas(): void {
		$content = Csv::build(
			[
				[ 'sku', 'quantity' ],
				[ '=SUM(1+1)', 1 ],
			]
		);

		self::assertStringContainsString( "'=SUM(1+1)", $content );

		$rows = Csv::parse_upload( $this->uploaded_file( $content ) );
		self::assertSame( '=SUM(1+1)', $rows[0]['sku'] );
	}

	public function test_it_rejects_more_than_the_maximum_rows(): void {
		$rows = [ 'sku,quantity' ];

		for ( $index = 0; $index <= Csv::MAX_ROWS; $index++ ) {
			$rows[] = "SKU-{$index},1";
		}

		$this->expectException( LengthException::class );
		Csv::parse_upload( $this->uploaded_file( implode( "\n", $rows ) ) );
	}

	public function test_it_rejects_oversized_cells(): void {
		$this->expectException( LengthException::class );

		Csv::parse_upload(
			$this->uploaded_file( 'sku,quantity' . "\n" . str_repeat( 'A', Csv::MAX_CELL_LENGTH + 1 ) . ',1' )
		);
	}

	public function test_it_rejects_duplicate_headers(): void {
		$this->expectException( UnexpectedValueException::class );
		Csv::parse_upload( $this->uploaded_file( "sku,sku,quantity\nABC-123,ABC-123,1\n" ) );
	}

	public function test_it_requires_quantity_and_an_identifier(): void {
		$this->expectException( UnexpectedValueException::class );
		Csv::parse_upload( $this->uploaded_file( "product_name\nExample product\n" ) );
	}

	private function uploaded_file( string $contents ): array {
		$file = tempnam( sys_get_temp_dir(), 'cart-relay-csv-' );

		if ( $file === false ) {
			self::fail( 'Unable to create a temporary CSV file.' );
		}

		file_put_contents( $file, $contents );
		$this->temporary_files[] = $file;

		return [ 'tmp_name' => $file ];
	}
}
