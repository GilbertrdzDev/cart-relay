<?php

namespace CartRelay\App\Components\Admin;

use CartRelay\App\Components\Forms\Form;
use CartRelay\App\Components\Forms\FormProcessor;
use CartRelay\App\Components\Forms\FormRenderer;
use CartRelay\App\Core\AssetManager;
use CartRelay\App\Core\ComponentCompiler;
use CartRelay\App\Core\Loader;
use CartRelay\App\Helpers\Settings;
use CartRelay\App\Interfaces\EnqueueScript;
use CartRelay\App\Interfaces\HasActions;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and processes the Cart Relay settings screen.
 */
class SettingsPageComponent implements EnqueueScript, HasActions {

	public const PAGE_SLUG = 'cart-relay';
	public const SCREEN_ID = 'woocommerce_page_' . self::PAGE_SLUG;
	public const SAVE_ACTION = 'cart_relay_save_settings';
	private const NONCE_ACTION = 'cart_relay_settings';
	private const CAPABILITY = 'manage_woocommerce';

	public function enqueue_scripts( AssetManager $asset_manager ): void {
		$asset_manager->admin_vite(
			'cart-relay-admin-js',
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
			__( 'Cart Relay settings', 'cart-relay' ),
			__( 'Cart Relay', 'cart-relay' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			[ $this, 'render_page' ]
		);
	}

	public function render_page(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You are not allowed to manage these settings.', 'cart-relay' ) );
		}

		$compiler = ComponentCompiler::get_instance();
		$form     = ( new FormRenderer( $compiler ) )->render( $this->form(), Settings::all( true ) );

		$page = $compiler->render(
			'admin.settings-page',
			[
				'form' => $form,
			]
		);

		echo $page; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered component escapes its leaf values.
	}

	public function handle_save(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error(
				[ 'message' => __( 'You are not allowed to manage these settings.', 'cart-relay' ) ],
				403
			);
		}

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			wp_send_json_error(
				[ 'message' => __( 'The request could not be verified. Refresh the page and try again.', 'cart-relay' ) ],
				403
			);
		}

		$submitted = isset( $_POST['settings'] ) && is_array( $_POST['settings'] )
			? wp_unslash( $_POST['settings'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- FormProcessor sanitizes every allowed field.
			: [];
		$form      = $this->form();
		$result    = ( new FormProcessor() )->process( $form, $submitted );

		if ( ! $result->isValid() ) {
			wp_send_json_error(
				[
					'message' => __( 'Review the highlighted fields and try again.', 'cart-relay' ),
					'errors'  => $result->errors,
				],
				422
			);
		}

		Settings::update( $result->values, array_keys( $form->getFields() ) );

		wp_send_json_success(
			[
				'message' => __( 'Settings saved successfully.', 'cart-relay' ),
				'values'  => $result->values,
			]
		);
	}

	private function form(): Form {
		$form = new Form( 'cart-relay-settings', self::SAVE_ACTION, self::NONCE_ACTION );

		$features = $form->section(
			'features',
			__( 'Cart features', 'cart-relay' ),
			__( 'Choose which cart tools are available to customers.', 'cart-relay' )
		);
		$features->toggle( 'export_enabled' )
			->label( __( 'Enable cart export', 'cart-relay' ) )
			->description( __( 'Allow customers to download the current cart as a CSV file.', 'cart-relay' ) )
			->default( true )
			->sanitize( 'boolean' )
			->rule( 'boolean' );
		$features->toggle( 'import_enabled' )
			->label( __( 'Enable cart import', 'cart-relay' ) )
			->description( __( 'Allow customers to add products from a Cart Relay CSV file.', 'cart-relay' ) )
			->default( true )
			->sanitize( 'boolean' )
			->rule( 'boolean' );
		$features->toggle( 'logged_in_only' )
			->label( __( 'Require an account', 'cart-relay' ) )
			->description( __( 'Limit import and export actions to logged-in customers.', 'cart-relay' ) )
			->default( false )
			->sanitize( 'boolean' )
			->rule( 'boolean' );

		$display = $form->section(
			'display',
			__( 'Labels and behavior', 'cart-relay' ),
			__( 'Customize labels and control how imported carts are applied.', 'cart-relay' )
		);
		$display->text( 'export_button_text' )
			->label( __( 'Export button text', 'cart-relay' ) )
			->description( __( 'Text displayed on the cart export button.', 'cart-relay' ) )
			->default( __( 'Export cart', 'cart-relay' ) )
			->requiredWhen( 'export_enabled' )
			->sanitize( 'sanitize_text_field' )
			->rule( 'max_length', [ 100 ] )
			->visibleWhen( 'export_enabled' );
		$display->text( 'import_button_text' )
			->label( __( 'Import button text', 'cart-relay' ) )
			->description( __( 'Text displayed on the cart import button.', 'cart-relay' ) )
			->default( __( 'Import cart', 'cart-relay' ) )
			->requiredWhen( 'import_enabled' )
			->sanitize( 'sanitize_text_field' )
			->rule( 'max_length', [ 100 ] )
			->visibleWhen( 'import_enabled' );
		$display->select( 'import_mode' )
			->label( __( 'Import mode', 'cart-relay' ) )
			->description( __( 'Merge with the current cart or replace its contents.', 'cart-relay' ) )
			->options(
				[
					'merge'   => __( 'Merge with current cart', 'cart-relay' ),
					'replace' => __( 'Replace current cart', 'cart-relay' ),
				]
			)
			->default( 'merge' )
			->sanitize( 'sanitize_text_field' )
			->rule( 'in', [ [ 'merge', 'replace' ] ] )
			->visibleWhen( 'import_enabled' );
		$display->select( 'button_location' )
			->label( __( 'Cart controls location', 'cart-relay' ) )
			->description( __( 'Choose where the import and export controls appear on the classic cart page.', 'cart-relay' ) )
			->options(
				[
					'woocommerce_before_cart_table' => __( 'Before the cart table', 'cart-relay' ),
					'woocommerce_after_cart_table'  => __( 'After the cart table', 'cart-relay' ),
					'woocommerce_after_cart_totals' => __( 'After the cart totals', 'cart-relay' ),
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
