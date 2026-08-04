<?php
/**
 * Verify the public shortcode registration contract in a loaded WordPress runtime.
 *
 * Run with: wp eval-file tools/smoke-shortcodes.php
 *
 * @package CartRelay
 */

defined( 'ABSPATH' ) || exit;

$expected_registered = array(
	'cart_relay_buttons',
	'cart_relay_import_form',
	'cart_relay_export_button',
	'cart_relay_pro_buttons',
	'cart_relay_pro_import_form',
	'cart_relay_pro_export_button',
);

$expected_absent = array(
	'cartbridge_buttons',
	'cartbridge_import_form',
	'cartbridge_export_button',
	'cartbridge_pro_buttons',
	'cartbridge_pro_import_form',
	'cartbridge_pro_export_button',
);

$failed = false;

foreach ( $expected_registered as $shortcode ) {
	$registered = shortcode_exists( $shortcode );
	WP_CLI::log( $shortcode . ':' . ( $registered ? 'registered' : 'missing' ) );
	$failed = $failed || ! $registered;
}

foreach ( $expected_absent as $shortcode ) {
	$registered = shortcode_exists( $shortcode );
	WP_CLI::log( $shortcode . ':' . ( $registered ? 'unexpected' : 'absent' ) );
	$failed = $failed || $registered;
}

if ( $failed ) {
	WP_CLI::error( 'The shortcode registration contract failed.' );
}

WP_CLI::success( 'The shortcode registration contract passed.' );
