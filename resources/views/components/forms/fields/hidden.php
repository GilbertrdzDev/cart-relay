<?php

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables are isolated to the component include scope.

$attributes = isset( $attributes ) ? (string) $attributes : '';
$value      = isset( $value ) ? (string) $value : '';
?>
<input type="hidden" value="<?php echo esc_attr( $value ); ?>" <?php echo $attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
