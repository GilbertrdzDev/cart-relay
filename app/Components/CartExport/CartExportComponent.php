<?php

namespace WoocartBridge\App\Components\CartExport;

use WoocartBridge\App\Core\ComponentCompiler;
use WoocartBridge\App\Core\AssetManager;
use WoocartBridge\App\Core\Loader;
use WoocartBridge\App\Helpers\Csv;
use WoocartBridge\App\Helpers\Settings;
use WoocartBridge\App\Interfaces\EnqueueScript;
use WoocartBridge\App\Interfaces\HasActions;
use WoocartBridge\App\Interfaces\Shortcode;

defined( 'ABSPATH' ) || exit;

/**
 * Exports the current WooCommerce cart as a CSV file.
 */
class CartExportComponent implements EnqueueScript, HasActions, Shortcode {

	public const ACTION = 'woocart_bridge_export_cart';
	private const NONCE_ACTION = 'woocart_bridge_export_cart';

	public function enqueue_scripts( AssetManager $asset_manager ): void {
		$asset_manager->frontend_vite(
			'woocart-bridge-front-js',
			'resources/assets/front/js/app-front.ts',
			[
				'in-footer'   => true,
				'condition'   => [ self::class, 'should_enqueue_assets' ],
				'dependencies' => [ 'wp-i18n' ],
				'text-domain' => 'woocart-bridge',
			]
		);
	}

	public function register_actions( Loader $loader ): void {
		if ( ! self::setting_enabled( 'export_enabled' ) ) {
			return;
		}

		$loader->add_action( 'wp_ajax_' . self::ACTION, [ self::class, 'handle_export' ] );

		if ( ! self::setting_enabled( 'logged_in_only' ) ) {
			$loader->add_action( 'wp_ajax_nopriv_' . self::ACTION, [ self::class, 'handle_export' ] );
		}
	}

	public static function register_shortcode( Loader $loader ): void {
		$loader->add_shortcode( 'cartbridge_export_button', [ self::class, 'render' ] );
	}

	public static function render( array $atts, string $content = '' ): string {
		if ( ! self::setting_enabled( 'export_enabled' ) ) {
			return '';
		}

		return ComponentCompiler::get_instance()->render(
			'cart.export-button',
			[
				'button_text' => Settings::get( 'export_button_text', __( 'Export cart', 'woocart-bridge' ) ),
				'export_url'  => self::get_export_url(),
			]
		);
	}

	public static function handle_export(): void {
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			wp_die( esc_html__( 'Invalid export request.', 'woocart-bridge' ), '', [ 'response' => 403 ] );
		}

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_die( esc_html__( 'WooCommerce cart is not available.', 'woocart-bridge' ), '', [ 'response' => 400 ] );
		}

		$rows = [
			[ 'product_id', 'variation_id', 'sku', 'product_name', 'quantity', 'price', 'subtotal' ],
		];

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'] ?? null;

			if ( ! $product || ! is_object( $product ) ) {
				continue;
			}

			$quantity = max( 0, (float) ( $cart_item['quantity'] ?? 0 ) );
			$subtotal = (float) ( $cart_item['line_subtotal'] ?? 0 );
			$price    = $quantity > 0 ? $subtotal / $quantity : 0;

			$rows[] = [
				(int) ( $cart_item['product_id'] ?? 0 ),
				! empty( $cart_item['variation_id'] ) ? (int) $cart_item['variation_id'] : '',
				$product->get_sku(),
				wp_strip_all_tags( $product->get_name() ),
				wc_format_decimal( $quantity, wc_get_price_decimals() ),
				wc_format_decimal( $price, wc_get_price_decimals() ),
				wc_format_decimal( $subtotal, wc_get_price_decimals() ),
			];
		}

		$csv      = Csv::build( $rows );
		$filename = 'woocart-bridge-cart-' . gmdate( 'Y-m-d-His' ) . '.csv';

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $csv ) );

		echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		wp_die();
	}

	public static function should_enqueue_assets(): bool {
		if ( ! self::setting_enabled( 'export_enabled' ) ) {
			return false;
		}

		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return true;
		}

		$post = get_post();

		if ( ! $post || empty( $post->post_content ) ) {
			return false;
		}

		return has_shortcode( $post->post_content, 'cartbridge_export_button' )
			|| has_shortcode( $post->post_content, 'cartbridge_buttons' );
	}

	private static function get_export_url(): string {
		return wp_nonce_url(
			add_query_arg(
				[
					'action' => self::ACTION,
				],
				admin_url( 'admin-ajax.php' )
			),
			self::NONCE_ACTION
		);
	}

	private static function setting_enabled( string $key ): bool {
		return filter_var( Settings::get( $key ), FILTER_VALIDATE_BOOLEAN );
	}

}
