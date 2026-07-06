<?php

namespace WoocartBridge\App\Components\CartImport;

use WC_Product;
use WoocartBridge\App\Core\AssetManager;
use WoocartBridge\App\Core\ComponentCompiler;
use WoocartBridge\App\Core\Loader;
use WoocartBridge\App\Helpers\Csv;
use WoocartBridge\App\Helpers\ProductResolver;
use WoocartBridge\App\Helpers\Settings;
use WoocartBridge\App\Interfaces\EnqueueScript;
use WoocartBridge\App\Interfaces\HasActions;
use WoocartBridge\App\Interfaces\Shortcode;

defined( 'ABSPATH' ) || exit;

/**
 * Imports CSV rows into the current WooCommerce cart.
 */
class CartImportComponent implements EnqueueScript, HasActions, Shortcode {

	public const PREVIEW_ACTION = 'woocart_bridge_preview_import_cart';
	public const IMPORT_CHUNK_ACTION = 'woocart_bridge_import_cart_chunk';
	public const TEMPLATE_ACTION = 'woocart_bridge_download_template';

	private const TEMPLATE_FILENAME = 'woocart-bridge-cart-template.csv';
	private const MAX_UPLOAD_SIZE = 10485760;

	public function enqueue_scripts( AssetManager $asset_manager ): void {
		$asset_manager->frontend_vite(
			'woocart-bridge-front-js',
			'resources/assets/front/js/app-front.ts',
			[
				'in-footer' => true,
				'condition' => [ self::class, 'should_enqueue_assets' ],
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
		$loader->add_shortcode( 'cartbridge_import_form', [ self::class, 'render' ] );
	}

	public static function render( array $atts = [], string $content = '' ): string {
		if ( ! self::setting_enabled( 'import_enabled' ) ) {
			return '';
		}

		return ComponentCompiler::get_instance()->render(
			'cart.import-form',
			[
				'ajax_url'         => admin_url( 'admin-ajax.php' ),
				'button_text'      => Settings::get( 'import_button_text', 'Importar carrito' ),
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
		self::verify_post_nonce( self::PREVIEW_ACTION );

		$validation_errors = self::validate_upload();

		if ( $validation_errors !== [] ) {
			self::send_validation_errors( $validation_errors );
		}

		$rows = Csv::parse_upload( $_FILES['csv_file'] );

		if ( $rows === [] ) {
			self::send_validation_errors( [ 'El CSV está vacío o no tiene filas importables.' ] );
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
					'message' => 'Producto no encontrado.',
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
			]
		);
	}

	public static function handle_import_chunk(): void {
		self::verify_post_nonce( self::IMPORT_CHUNK_ACTION );

		if ( ! self::ensure_cart() ) {
			wp_send_json_error( [ 'errors' => [ 'WooCommerce cart is not available.' ] ], 400 );
		}

		$items        = self::get_posted_items();
		$chunk_index  = isset( $_POST['chunk_index'] ) ? absint( $_POST['chunk_index'] ) : 0;
		$total_chunks = isset( $_POST['total_chunks'] ) ? max( 1, absint( $_POST['total_chunks'] ) ) : 1;
		$import_mode  = self::sanitize_import_mode( $_POST['import_mode'] ?? self::get_import_mode() );

		if ( $items === [] ) {
			self::send_validation_errors( [ 'No hay productos para importar en este chunk.' ] );
		}

		if ( $import_mode === 'replace' && $chunk_index === 0 ) {
			WC()->cart->empty_cart();
		}

		$added  = 0;
		$errors = [];

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
				$added++;
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
			]
		);
	}

	public static function handle_download_template(): void {
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::TEMPLATE_ACTION ) ) {
			wp_die( esc_html__( 'Invalid template request.', 'woocart-bridge' ), '', [ 'response' => 403 ] );
		}

		$csv = Csv::build(
			[
				[ 'product_id', 'variation_id', 'sku', 'product_name', 'quantity', 'price', 'subtotal' ],
				[ '123', '', 'ABC-123', 'Producto simple de ejemplo', '2', '19.99', '39.98' ],
				[ '456', '789', 'XYZ-456-RED-M', 'Producto variable de ejemplo - Rojo / M', '1', '24.99', '24.99' ],
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

		return has_shortcode( $post->post_content, 'cartbridge_import_form' )
			|| has_shortcode( $post->post_content, 'cartbridge_buttons' );
	}

	private static function validate_upload(): array {
		if ( empty( $_FILES['csv_file'] ) || ! is_array( $_FILES['csv_file'] ) ) {
			return [ 'Debes seleccionar un archivo CSV.' ];
		}

		$file = $_FILES['csv_file'];

		if ( (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) !== UPLOAD_ERR_OK ) {
			return [ 'No se pudo subir el archivo CSV.' ];
		}

		if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return [ 'El archivo CSV no es válido.' ];
		}

		if ( (int) ( $file['size'] ?? 0 ) > self::MAX_UPLOAD_SIZE ) {
			return [ 'El archivo CSV no puede superar 10 MB.' ];
		}

		$filename = sanitize_file_name( (string) ( $file['name'] ?? '' ) );

		if ( strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) ) !== 'csv' ) {
			return [ 'El archivo debe tener extensión .csv.' ];
		}

		return [];
	}

	private static function make_preview_item( int $row_number, array $resolved, WC_Product $product ): array {
		$quantity = (int) $resolved['quantity'];
		$price    = (float) wc_get_price_to_display( $product );
		$subtotal = $price * $quantity;
		$sku      = $product->get_sku() ?: (string) $resolved['sku'];

		return [
			'row'          => $row_number,
			'product_id'   => (int) $resolved['product_id'],
			'variation_id' => (int) $resolved['variation_id'],
			'sku'          => $sku,
			'name'         => wp_strip_all_tags( $product->get_name() ),
			'quantity'     => $quantity,
			'price'        => wc_format_decimal( $price, wc_get_price_decimals() ),
			'subtotal'     => wc_format_decimal( $subtotal, wc_get_price_decimals() ),
			'image'        => self::get_product_image_url( $product ),
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

	private static function get_posted_items(): array {
		$raw_items = isset( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : '[]';
		$items     = json_decode( (string) $raw_items, true );

		if ( ! is_array( $items ) ) {
			return [];
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

	private static function verify_post_nonce( string $action ): void {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			wp_send_json_error( [ 'errors' => [ 'Solicitud inválida.' ] ], 403 );
		}
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

		return sprintf( 'No se pudo agregar el producto %d al carrito.', (int) $resolved['product_id'] );
	}

	private static function make_row_error( int $row_number, string $message ): array {
		return [
			'row'     => $row_number,
			'message' => $message,
		];
	}

	private static function format_row_errors( array $errors ): array {
		if ( $errors === [] ) {
			return [ 'No se encontraron productos válidos en el CSV.' ];
		}

		return array_map(
			static function( array $error ): string {
				$row = absint( $error['row'] ?? 0 );
				$message = (string) ( $error['message'] ?? 'Error desconocido.' );

				return $row > 0 ? sprintf( 'Fila %d: %s', $row, $message ) : $message;
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
