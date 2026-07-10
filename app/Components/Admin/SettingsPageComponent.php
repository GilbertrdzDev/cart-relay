<?php

namespace WoocartBridge\App\Components\Admin;

use WoocartBridge\App\Components\Forms\Form;
use WoocartBridge\App\Components\Forms\FormProcessor;
use WoocartBridge\App\Components\Forms\FormRenderer;
use WoocartBridge\App\Core\AssetManager;
use WoocartBridge\App\Core\ComponentCompiler;
use WoocartBridge\App\Core\Loader;
use WoocartBridge\App\Helpers\Settings;
use WoocartBridge\App\Interfaces\EnqueueScript;
use WoocartBridge\App\Interfaces\HasActions;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and processes the WooCart Bridge settings screen.
 */
class SettingsPageComponent implements EnqueueScript, HasActions {

	public const PAGE_SLUG = 'woocart-bridge';
	public const SCREEN_ID = 'woocommerce_page_' . self::PAGE_SLUG;
	public const SAVE_ACTION = 'woocart_bridge_save_settings';
	private const NONCE_ACTION = 'woocart_bridge_settings';
	private const CAPABILITY = 'manage_woocommerce';

	public function enqueue_scripts( AssetManager $asset_manager ): void {
		$asset_manager->admin_vite(
			'woocart-bridge-admin-js',
			'resources/assets/admin/js/app-admin.ts',
			[
				'in-footer' => true,
				'screens'   => self::SCREEN_ID,
			]
		);
	}

	public function register_actions( Loader $loader ): void {
		$loader->add_action( 'admin_menu', [ $this, 'register_menu' ] );
		$loader->add_action( 'wp_ajax_' . self::SAVE_ACTION, [ $this, 'handle_save' ] );
	}

	public function register_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'WooCart Bridge settings', 'woocart-bridge' ),
			__( 'WooCart Bridge', 'woocart-bridge' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			[ $this, 'render_page' ]
		);
	}

	public function render_page(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You are not allowed to manage these settings.', 'woocart-bridge' ) );
		}

		$compiler = ComponentCompiler::get_instance();
		$form     = ( new FormRenderer( $compiler ) )->render( $this->form(), Settings::all( true ) );

		echo $compiler->render(
			'admin.settings-page',
			[
				'form' => $form,
			]
		); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function handle_save(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error(
				[ 'message' => __( 'You are not allowed to manage these settings.', 'woocart-bridge' ) ],
				403
			);
		}

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			wp_send_json_error(
				[ 'message' => __( 'The request could not be verified. Refresh the page and try again.', 'woocart-bridge' ) ],
				403
			);
		}

		$submitted = isset( $_POST['settings'] ) && is_array( $_POST['settings'] )
			? wp_unslash( $_POST['settings'] )
			: [];
		$form      = $this->form();
		$result    = ( new FormProcessor() )->process( $form, $submitted );

		if ( ! $result->isValid() ) {
			wp_send_json_error(
				[
					'message' => __( 'Review the highlighted fields and try again.', 'woocart-bridge' ),
					'errors'  => $result->errors,
				],
				422
			);
		}

		Settings::update( $result->values, array_keys( $form->getFields() ) );

		wp_send_json_success(
			[
				'message' => __( 'Settings saved successfully.', 'woocart-bridge' ),
				'values'  => $result->values,
			]
		);
	}

	private function form(): Form {
		$form = new Form( 'woocart-bridge-settings', self::SAVE_ACTION, self::NONCE_ACTION );

		$features = $form->section(
			'features',
			__( 'Cart features', 'woocart-bridge' ),
			__( 'Choose which cart tools are available to customers.', 'woocart-bridge' )
		);
		$features->toggle( 'export_enabled' )
			->label( __( 'Enable cart export', 'woocart-bridge' ) )
			->description( __( 'Allow customers to download the current cart as a CSV file.', 'woocart-bridge' ) )
			->default( true )
			->sanitize( 'boolean' )
			->rule( 'boolean' );
		$features->toggle( 'import_enabled' )
			->label( __( 'Enable cart import', 'woocart-bridge' ) )
			->description( __( 'Allow customers to add products from a WooCart Bridge CSV file.', 'woocart-bridge' ) )
			->default( true )
			->sanitize( 'boolean' )
			->rule( 'boolean' );
		$features->toggle( 'logged_in_only' )
			->label( __( 'Require an account', 'woocart-bridge' ) )
			->description( __( 'Limit import and export actions to logged-in customers.', 'woocart-bridge' ) )
			->default( false )
			->sanitize( 'boolean' )
			->rule( 'boolean' );

		$display = $form->section(
			'display',
			__( 'Labels and behavior', 'woocart-bridge' ),
			__( 'Customize labels and control how imported carts are applied.', 'woocart-bridge' )
		);
		$display->text( 'export_button_text' )
			->label( __( 'Export button text', 'woocart-bridge' ) )
			->description( __( 'Text displayed on the cart export button.', 'woocart-bridge' ) )
			->default( __( 'Export cart', 'woocart-bridge' ) )
			->requiredWhen( 'export_enabled' )
			->sanitize( 'sanitize_text_field' )
			->rule( 'max_length', [ 100 ] )
			->visibleWhen( 'export_enabled' );
		$display->text( 'import_button_text' )
			->label( __( 'Import button text', 'woocart-bridge' ) )
			->description( __( 'Text displayed on the cart import button.', 'woocart-bridge' ) )
			->default( __( 'Import cart', 'woocart-bridge' ) )
			->requiredWhen( 'import_enabled' )
			->sanitize( 'sanitize_text_field' )
			->rule( 'max_length', [ 100 ] )
			->visibleWhen( 'import_enabled' );
		$display->select( 'import_mode' )
			->label( __( 'Import mode', 'woocart-bridge' ) )
			->description( __( 'Merge with the current cart or replace its contents.', 'woocart-bridge' ) )
			->options(
				[
					'merge'   => __( 'Merge with current cart', 'woocart-bridge' ),
					'replace' => __( 'Replace current cart', 'woocart-bridge' ),
				]
			)
			->default( 'merge' )
			->sanitize( 'sanitize_text_field' )
			->rule( 'in', [ [ 'merge', 'replace' ] ] )
			->visibleWhen( 'import_enabled' );
		$display->select( 'button_location' )
			->label( __( 'Cart controls location', 'woocart-bridge' ) )
			->description( __( 'Choose where the import and export controls appear on the classic cart page.', 'woocart-bridge' ) )
			->options(
				[
					'woocommerce_before_cart_table' => __( 'Before the cart table', 'woocart-bridge' ),
					'woocommerce_after_cart_table'  => __( 'After the cart table', 'woocart-bridge' ),
					'woocommerce_after_cart_totals' => __( 'After the cart totals', 'woocart-bridge' ),
				]
			)
			->default( 'woocommerce_after_cart_table' )
			->sanitize( 'sanitize_text_field' )
			->rule(
				'in',
				[
					[
						'woocommerce_before_cart_table',
						'woocommerce_after_cart_table',
						'woocommerce_after_cart_totals',
					],
				]
			);

		return $form;
	}

}
