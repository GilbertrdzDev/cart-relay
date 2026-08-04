<?php

/**
 * Fires when the plugin is uninstalled.
 *
 * @link      https://gilbertrdz.dev
 * @since     1.0.0
 * @package   CartRelay
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'cart_relay_settings' );
