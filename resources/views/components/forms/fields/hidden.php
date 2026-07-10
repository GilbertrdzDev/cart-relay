<?php

defined( 'ABSPATH' ) || exit;

$attributes = isset( $attributes ) ? (string) $attributes : '';
$value      = isset( $value ) ? (string) $value : '';
?>
<input type="hidden" value="<?php echo esc_attr( $value ); ?>" <?php echo $attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
