<?php

defined( 'ABSPATH' ) || exit;

$attributes = isset( $attributes ) ? (string) $attributes : '';
?>
<input type="text" <?php echo $attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
