<?php

namespace CartRelay\App\Components\CartImport;

use WC_Product;
use LengthException;
use RuntimeException;
use CartRelay\App\Core\AssetManager;
use CartRelay\App\Core\ComponentCompiler;
use CartRelay\App\Core\Loader;
use CartRelay\App\Helpers\Csv;
use CartRelay\App\Helpers\ProductResolver;
use CartRelay\App\Helpers\Settings;
use CartRelay\App\Interfaces\EnqueueScript;
use CartRelay\App\Interfaces\HasActions;
use CartRelay\App\Interfaces\Shortcode;

defined( 'ABSPATH' ) || exit;

/**
 * Imports CSV rows into the current WooCommerce cart.
 */
class CartImportComponent implements EnqueueScript, HasActions, Shortcode {

	public const PREVIEW_ACTION = 'cart_relay_preview_import_cart';
	public const IMPORT_CHUNK_ACTION = 'cart_relay_import_cart_chunk';
	public const TEMPLATE_ACTION = 'cart_relay_download_template';
	public const SHORTCODE = 'cart_relay_import_form';

	private const TEMPLATE_FILENAME = 'cart-relay-cart-template.csv';
	private const MAX_UPLOAD_SIZE = 2097152;
	private const MAX_CHUNK_SIZE = 25;
	private const MAX_TOTAL_CHUNKS = 20;
	private const MAX_ITEMS_JSON_SIZE = 65536;

	public function enqueue_scripts( AssetManager $asset_manager ): void {
		$asset_manager->frontend_vite(
			'cart-relay-front-js',
			'resources/assets/front/js/app-front.ts',
			[
				'in-footer'   => true,
				'condition'   => [ self::class, 'should_enqueue_assets' ],
				'dependencies' => [ 'wp-i18n' ],
				'text-domain' => 'cart-relay',
			]
		);
	}

	public function register_actions( Loader $loader ): void {
		if ( ! self::setting_enabled( 'import_enabled' ) ) {
			return;
		}

		$actions = [
			self::PREVIEW_ACTION      => 'handle_preview',
			self::IMPORT_CHUNK_ACTION => 'handle_import_chunk',
			self::TEMPLATE_ACTION     => 'handle_download_template',
		];

		foreach ( $actions as $action => $handler ) {
			$loader->add_action( 'wp_ajax_' . $action, [ self::class, $handler ] );

			if ( ! self::setting_enabled( 'logged_in_only' ) ) {
				$loader->add_action( 'wp_ajax_nopriv_' . $action, [ self::class, $handler ] );
			}
		}
	}

	public static function register_shortcode( Loader $loader ): void {
		$loader->add_shortcode( self::SHORTCODE, [ self::class, 'render' ] );
	}

	public static function render( array $atts = [], string $content = '' ): string {
		if ( ! self::setting_enabled( 'import_enabled' ) ) {
			return '';
		}

		return ComponentCompiler::get_instance()->render(
			'cart.import-form',
			[
				'ajax_url'         => admin_url( 'admin-ajax.php' ),
				'button_text'      => Settings::get( 'import_button_text', __( 'Import cart', 'cart-relay' ) ),
				'import_mode'      => self::get_import_mode(),
				'preview_action'   => self::PREVIEW_ACTION,
				'preview_nonce'    => wp_create_nonce( self::PREVIEW_ACTION ),
				'chunk_action'     => self::IMPORT_CHUNK_ACTION,
				'chunk_nonce'      => wp_create_nonce( self::IMPORT_CHUNK_ACTION ),
				'template_url'     => self::get_template_url(),
			]
		);
	}

	public static function handle_preview(): void {
		if ( false === check_ajax_referer( self::PREVIEW_ACTION, 'nonce', false ) ) {
			wp_send_json_error( [ 'errors' => [ __( 'Invalid request.', 'cart-relay' ) ] ], 403 );
		}

		$upload = isset( $_FILES['csv_file'] ) && is_array( $_FILES['csv_file'] )
			? $_FILES['csv_file'] // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- PHP upload metadata is validated before any path is used.
			: [];

		$validation_errors = self::validate_upload( $upload );

		if ( $validation_errors !== [] ) {
			self::send_validation_errors( $validation_errors );
		}

		$rows = [];

		try {
			$rows = Csv::parse_upload( $upload );
		} catch ( RuntimeException $exception ) {
			self::send_validation_errors( [ $exception->getMessage() ] );
		}

		if ( $rows === [] ) {
			self::send_validation_errors( [ __( 'The CSV is empty or has no importable rows.', 'cart-relay' ) ] );
		}

		$items  = [];
		$errors = [];

		foreach ( $rows as $index => $row ) {
			$row_number = absint( $row['__row'] ?? ( $index + 2 ) );
			$resolved   = ProductResolver::resolve_import_row( $row );

			if ( $resolved['errors'] !== [] ) {
				foreach ( $resolved['errors'] as $message ) {
					$errors[] = [
						'row'     => $row_number,
						'message' => $message,
					];
				}

				continue;
			}

			$product = $resolved['product'];

			if ( ! $product instanceof WC_Product ) {
				$errors[] = [
					'row'     => $row_number,
					'message' => __( 'Product not found.', 'cart-relay' ),
				];
				continue;
			}

			$items[] = self::make_preview_item( $row_number, $resolved, $product );
		}

		if ( $items === [] ) {
			self::send_validation_errors( self::format_row_errors( $errors ) );
		}

		wp_send_json_success(
			[
				'items'       => $items,
				'errors'      => $errors,
				'import_mode' => self::get_import_mode(),
				'currency'    => [
					'code'              => get_woocommerce_currency(),
					'symbol'            => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ),
					'decimal_separator' => wc_get_price_decimal_separator(),
					'thousand_separator' => wc_get_price_thousand_separator(),
					'decimals'          => wc_get_price_decimals(),
				],
			]
		);
	}

	public static function handle_import_chunk(): void {
		if ( false === check_ajax_referer( self::IMPORT_CHUNK_ACTION, 'nonce', false ) ) {
			wp_send_json_error( [ 'errors' => [ __( 'Invalid request.', 'cart-relay' ) ] ], 403 );
		}

		if ( ! self::ensure_cart() ) {
			wp_send_json_error( [ 'errors' => [ __( 'WooCommerce cart is not available.', 'cart-relay' ) ] ], 400 );
		}

		$chunk_index  = isset( $_POST['chunk_index'] ) ? absint( $_POST['chunk_index'] ) : 0;
		$total_chunks = isset( $_POST['total_chunks'] ) ? max( 1, absint( $_POST['total_chunks'] ) ) : 1;
		$posted_import_mode = isset( $_POST['import_mode'] ) && is_string( $_POST['import_mode'] )
			? sanitize_text_field( wp_unslash( $_POST['import_mode'] ) )
			: self::get_import_mode();
		$import_mode        = self::sanitize_import_mode( $posted_import_mode );

		if ( $total_chunks > self::MAX_TOTAL_CHUNKS || $chunk_index >= $total_chunks ) {
			self::send_validation_errors( [ __( 'The import chunk information is invalid.', 'cart-relay' ) ] );
		}

		$items = [];
		$raw_items = isset( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : '[]'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON is size-limited, decoded, and each field is sanitized below.

		try {
			$items = self::parse_posted_items( $raw_items );
		} catch ( RuntimeException $exception ) {
			self::send_validation_errors( [ $exception->getMessage() ] );
		}

		if ( $items === [] ) {
			self::send_validation_errors( [ __( 'There are no products to import in this chunk.', 'cart-relay' ) ] );
		}

		if ( $import_mode === 'replace' && $chunk_index === 0 ) {
			WC()->cart->empty_cart();
		}

		$added         = 0;
		$errors        = [];
		$updated_items = [];

		foreach ( $items as $item ) {
			$row_number = absint( $item['row'] ?? 0 );
			$resolved   = ProductResolver::resolve_import_row( $item );

			if ( $resolved['errors'] !== [] ) {
				foreach ( $resolved['errors'] as $message ) {
					$errors[] = self::make_row_error( $row_number, $message );
				}

				continue;
			}

			wc_clear_notices();

			$result = WC()->cart->add_to_cart(
				(int) $resolved['product_id'],
				(int) $resolved['quantity'],
				(int) $resolved['variation_id']
			);

			if ( $result ) {
				++$added;
				$updated_items[] = self::make_updated_cart_item( (string) $result, $resolved );
				wc_clear_notices();
				continue;
			}

			$errors[] = self::make_row_error( $row_number, self::get_cart_error_message( $resolved ) );
			wc_clear_notices();
		}

		WC()->cart->calculate_totals();

		wp_send_json_success(
			[
				'chunk_index'  => $chunk_index,
				'total_chunks' => $total_chunks,
				'added'        => $added,
				'errors'       => $errors,
				'updated_items' => $updated_items,
			]
		);
	}

	public static function handle_download_template(): void {
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::TEMPLATE_ACTION ) ) {
			wp_die( esc_html__( 'Invalid template request.', 'cart-relay' ), '', [ 'response' => 403 ] );
		}

		$csv = Csv::build(
			[
				[ 'product_id', 'variation_id', 'sku', 'product_name', 'quantity' ],
				[ '123', '', 'ABC-123', 'Example simple product', '2' ],
				[ '456', '789', 'XYZ-456-RED-M', 'Example variable product - Red / M', '1' ],
			]
		);

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . self::TEMPLATE_FILENAME . '"' );
		header( 'Content-Length: ' . strlen( $csv ) );

		echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		wp_die();
	}

	public static function should_enqueue_assets(): bool {
		if ( ! self::setting_enabled( 'import_enabled' ) ) {
			return false;
		}

		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return true;
		}

		$post = get_post();

		if ( ! $post || empty( $post->post_content ) ) {
			return false;
		}

		return has_shortcode( $post->post_content, self::SHORTCODE )
			|| has_shortcode( $post->post_content, 'cart_relay_buttons' );
	}

	private static function validate_upload( array $file ): array {
		if ( $file === [] ) {
			return [ __( 'You must select a CSV file.', 'cart-relay' ) ];
		}

		if ( (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) !== UPLOAD_ERR_OK ) {
			return [ __( 'The CSV file could not be uploaded.', 'cart-relay' ) ];
		}

		if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return [ __( 'The CSV file is not valid.', 'cart-relay' ) ];
		}

		$tmp_name = (string) $file['tmp_name'];
		$file_size = filesize( $tmp_name );

		if ( $file_size === false || $file_size <= 0 ) {
			return [ __( 'The CSV file is empty.', 'cart-relay' ) ];
		}

		if ( $file_size > self::MAX_UPLOAD_SIZE ) {
			return [ __( 'The CSV file cannot exceed 2 MB.', 'cart-relay' ) ];
		}

		$filename = sanitize_file_name( (string) ( $file['name'] ?? '' ) );
		$filetype = wp_check_filetype( $filename, [ 'csv' => 'text/csv' ] );

		if ( ( $filetype['ext'] ?? '' ) !== 'csv' ) {
			return [ __( 'The file must use the .csv extension.', 'cart-relay' ) ];
		}

		if ( class_exists( 'finfo' ) ) {
			$finfo = new \finfo( FILEINFO_MIME_TYPE );
			$mime  = $finfo->file( $tmp_name );
			$allowed_mimes = [
				'text/csv',
				'text/plain',
				'text/x-csv',
				'application/csv',
				'application/x-csv',
				'application/vnd.ms-excel',
			];

			if ( is_string( $mime ) && ! in_array( strtolower( $mime ), $allowed_mimes, true ) ) {
				return [ __( 'The uploaded file does not contain valid CSV data.', 'cart-relay' ) ];
			}
		}

		$handle = fopen( $tmp_name, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Reading a verified temporary upload does not use WP_Filesystem credentials.

		if ( $handle === false ) {
			return [ __( 'The CSV file could not be read.', 'cart-relay' ) ];
		}

		$sample = fread( $handle, 8192 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread -- Reading a verified temporary upload.
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing the temporary upload stream.

		if ( $sample === false || str_contains( $sample, "\0" ) ) {
			return [ __( 'The uploaded file does not contain valid CSV data.', 'cart-relay' ) ];
		}

		return [];
	}

	private static function make_preview_item( int $row_number, array $resolved, WC_Product $product ): array {
		$quantity = (int) $resolved['quantity'];
		$sku      = $product->get_sku() !== '' ? $product->get_sku() : (string) $resolved['sku'];
		$price    = (float) wc_get_price_to_display( $product );
		$subtotal = (float) wc_get_price_to_display( $product, [ 'qty' => $quantity ] );

		return [
			'row'          => $row_number,
			'product_id'   => (int) $resolved['product_id'],
			'variation_id' => (int) $resolved['variation_id'],
			'sku'          => $sku,
			'name'         => wp_strip_all_tags( $product->get_name() ),
			'quantity'     => $quantity,
			'price'        => $price,
			'subtotal'     => $subtotal,
			'image'        => self::get_product_image_url( $product ),
			'permalink'    => $product->get_permalink(),
		];
	}

	private static function get_product_image_url( WC_Product $product ): string {
		$image_id = $product->get_image_id();

		if ( ! $image_id && $product->is_type( 'variation' ) ) {
			$parent = ProductResolver::find_by_id( (int) $product->get_parent_id() );
			$image_id = $parent instanceof WC_Product ? $parent->get_image_id() : 0;
		}

		$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '';

		if ( ! $image_url && function_exists( 'wc_placeholder_img_src' ) ) {
			$image_url = wc_placeholder_img_src( 'thumbnail' );
		}

		return (string) $image_url;
	}

	private static function parse_posted_items( mixed $raw_items ): array {
		if ( ! is_string( $raw_items ) || strlen( $raw_items ) > self::MAX_ITEMS_JSON_SIZE ) {
			throw new LengthException( __( 'The import chunk is too large.', 'cart-relay' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are escaped at output boundaries.
		}

		$items     = json_decode( (string) $raw_items, true );

		if ( ! is_array( $items ) ) {
			return [];
		}

		if ( count( $items ) > self::MAX_CHUNK_SIZE ) {
			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are escaped at output boundaries.
			throw new LengthException(
				sprintf(
					/* translators: %d: maximum number of products in one import request. */
					__( 'An import request cannot contain more than %d products.', 'cart-relay' ),
					self::MAX_CHUNK_SIZE
				)
			);
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		return array_values(
			array_filter(
				array_map( [ self::class, 'sanitize_posted_item' ], $items )
			)
		);
	}

	private static function sanitize_posted_item( mixed $item ): array {
		if ( ! is_array( $item ) ) {
			return [];
		}

		return [
			'row'          => absint( $item['row'] ?? 0 ),
			'product_id'   => absint( $item['product_id'] ?? 0 ),
			'variation_id' => absint( $item['variation_id'] ?? 0 ),
			'sku'          => sanitize_text_field( (string) ( $item['sku'] ?? '' ) ),
			'quantity'     => absint( $item['quantity'] ?? 0 ),
		];
	}

	private static function get_cart_error_message( array $resolved ): string {
		$notices = wc_get_notices( 'error' );
		$errors  = [];

		foreach ( $notices as $notice ) {
			$message = is_array( $notice ) ? (string) ( $notice['notice'] ?? '' ) : (string) $notice;
			$message = trim( wp_strip_all_tags( $message ) );

			if ( $message !== '' ) {
				$errors[] = $message;
			}
		}

		if ( $errors !== [] ) {
			return implode( ' ', $errors );
		}

		return sprintf(
			/* translators: %d: WooCommerce product ID. */
			__( 'Product %d could not be added to the cart.', 'cart-relay' ),
			(int) $resolved['product_id']
		);
	}

	private static function make_row_error( int $row_number, string $message ): array {
		return [
			'row'     => $row_number,
			'message' => $message,
		];
	}

	private static function make_updated_cart_item( string $cart_item_key, array $resolved ): array {
		$cart_item = WC()->cart->get_cart_item( $cart_item_key );
		$cart_item = is_array( $cart_item ) ? $cart_item : [];

		return [
			'cart_item_key' => $cart_item_key,
			'product_id'    => (int) $resolved['product_id'],
			'variation_id'  => (int) $resolved['variation_id'],
			'sku'           => (string) $resolved['sku'],
			'quantity'      => (int) ( $cart_item['quantity'] ?? $resolved['quantity'] ),
		];
	}

	private static function format_row_errors( array $errors ): array {
		if ( $errors === [] ) {
			return [ __( 'No valid products were found in the CSV.', 'cart-relay' ) ];
		}

		return array_map(
			static function ( array $error ): string {
				$row = absint( $error['row'] ?? 0 );
				$message = (string) ( $error['message'] ?? __( 'Unknown error.', 'cart-relay' ) );

				return $row > 0
					? sprintf(
						/* translators: 1: CSV row number, 2: validation error message. */
						__( 'Row %1$d: %2$s', 'cart-relay' ),
						$row,
						$message
					)
					: $message;
			},
			$errors
		);
	}

	private static function send_validation_errors( array $errors ): void {
		wp_send_json_error(
			[
				'errors' => array_values( array_filter( $errors ) ),
			],
			422
		);
	}

	private static function get_template_url(): string {
		return wp_nonce_url(
			add_query_arg(
				[
					'action' => self::TEMPLATE_ACTION,
				],
				admin_url( 'admin-ajax.php' )
			),
			self::TEMPLATE_ACTION
		);
	}

	private static function get_import_mode(): string {
		return self::sanitize_import_mode( Settings::get( 'import_mode', 'merge' ) );
	}

	private static function sanitize_import_mode( mixed $mode ): string {
		return (string) $mode === 'replace' ? 'replace' : 'merge';
	}

	private static function setting_enabled( string $key ): bool {
		return filter_var( Settings::get( $key ), FILTER_VALIDATE_BOOLEAN );
	}

	private static function ensure_cart(): bool {
		if ( ! function_exists( 'WC' ) ) {
			return false;
		}

		if ( ! WC()->cart && function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}

		return (bool) WC()->cart;
	}

}
