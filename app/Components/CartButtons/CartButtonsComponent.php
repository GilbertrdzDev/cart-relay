<?php

namespace CartRelay\App\Components\CartButtons;

use CartRelay\App\Components\CartExport\CartExportComponent;
use CartRelay\App\Core\ComponentCompiler;
use CartRelay\App\Core\Loader;
use CartRelay\App\Helpers\Settings;
use CartRelay\App\Interfaces\HasActions;
use CartRelay\App\Interfaces\Shortcode;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the plugin cart controls through shortcodes or WooCommerce hooks.
 */
class CartButtonsComponent implements HasActions, Shortcode {

	public const SHORTCODE = 'cart_relay_buttons';

	private const DEFAULT_HOOK = 'woocommerce_after_cart_table';
	private const ALLOWED_HOOKS = [
		'woocommerce_before_cart_table',
		'woocommerce_after_cart_table',
		'woocommerce_after_cart_totals',
	];

	public function register_actions( Loader $loader ): void {
		$loader->add_action( self::get_button_location(), [ self::class, 'render_on_cart_hook' ] );
	}

	public static function register_shortcode( Loader $loader ): void {
		$loader->add_shortcode( self::SHORTCODE, [ self::class, 'render' ] );
	}

	public static function render_on_cart_hook(): void {
		echo self::render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public static function render( array $atts = [], string $content = '' ): string {
		$export = CartExportComponent::render( [] );
		$import = self::render_import_form();

		if ( $export === '' && $import === '' ) {
			return '';
		}

		return ComponentCompiler::get_instance()->render(
			'cart.buttons',
			[
				'export_button' => $export,
				'import_form'   => $import,
			]
		);
	}

	private static function get_button_location(): string {
		$location = (string) Settings::get( 'button_location', self::DEFAULT_HOOK );

		return in_array( $location, self::ALLOWED_HOOKS, true ) ? $location : self::DEFAULT_HOOK;
	}

	private static function render_import_form(): string {
		$component = 'CartRelay\\App\\Components\\CartImport\\CartImportComponent';

		if ( ! class_exists( $component ) || ! is_callable( [ $component, 'render' ] ) ) {
			return '';
		}

		return (string) $component::render( [], '' );
	}

}
