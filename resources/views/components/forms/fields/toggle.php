<?php

defined( 'ABSPATH' ) || exit;

$attributes = isset( $attributes ) ? (string) $attributes : '';
$name       = isset( $name ) ? (string) $name : '';
?>
<label class="cr:relative cr:inline-flex cr:cursor-pointer cr:items-center">
	<input type="hidden" name="settings[<?php echo esc_attr( $name ); ?>]" value="0">
	<input type="checkbox" <?php echo $attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<span class="cr:h-6 cr:w-11 cr:rounded-full cr:bg-slate-300 cr:transition cr:peer-checked:bg-blue-600 cr:peer-focus-visible:ring-2 cr:peer-focus-visible:ring-blue-500 cr:peer-focus-visible:ring-offset-2 cr:peer-disabled:cursor-not-allowed cr:peer-disabled:opacity-50 cr:after:absolute cr:after:start-0.5 cr:after:top-0.5 cr:after:h-5 cr:after:w-5 cr:after:rounded-full cr:after:bg-white cr:after:shadow cr:after:transition-transform cr:peer-checked:after:translate-x-5"></span>
</label>
