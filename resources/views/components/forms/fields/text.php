<?php

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables are isolated to the component include scope.

$attributes = isset( $attributes ) ? (string) $attributes : '';
?>
<input type="text" <?php echo $attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
