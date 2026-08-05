<?php

namespace CartRelay\App\Components;

use CartRelay\App\Core\Loader;
use CartRelay\App\Interfaces\HasActions;


defined( 'ABSPATH' ) || exit;

/**
 * Notifies admins when WooCommerce is not active, without deactivating the plugin.
 */
class RequirementsCheck implements HasActions {

	public function register_actions( Loader $loader ): void {
		$loader->add_action( 'admin_notices', [ $this, 'render_notice' ] );
	}

	/**
	 * Renders an admin notice if the WooCommerce plugin is not active.
	 */
	public function render_notice(): void {
		if ( class_exists( 'WooCommerce' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
			esc_html__( 'Cart Relay requires WooCommerce to be installed and active in order to import/export carts.', 'cart-relay' )
		);
	}

}
