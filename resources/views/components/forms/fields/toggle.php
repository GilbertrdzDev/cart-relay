<?php

defined( 'ABSPATH' ) || exit;

$attributes = isset( $attributes ) ? (string) $attributes : '';
$name       = isset( $name ) ? (string) $name : '';
?>
<label class="wcb:relative wcb:inline-flex wcb:cursor-pointer wcb:items-center">
	<input type="hidden" name="settings[<?php echo esc_attr( $name ); ?>]" value="0">
	<input type="checkbox" <?php echo $attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<span class="wcb:h-6 wcb:w-11 wcb:rounded-full wcb:bg-slate-300 wcb:transition wcb:peer-checked:bg-blue-600 wcb:peer-focus-visible:ring-2 wcb:peer-focus-visible:ring-blue-500 wcb:peer-focus-visible:ring-offset-2 wcb:peer-disabled:cursor-not-allowed wcb:peer-disabled:opacity-50 wcb:after:absolute wcb:after:start-0.5 wcb:after:top-0.5 wcb:after:h-5 wcb:after:w-5 wcb:after:rounded-full wcb:after:bg-white wcb:after:shadow wcb:after:transition-transform wcb:peer-checked:after:translate-x-5"></span>
</label>
