<?php

defined( 'ABSPATH' ) || exit;

$button_text = isset( $button_text ) ? (string) $button_text : __( 'Exportar carrito', 'woocart-bridge' );
$export_url  = isset( $export_url ) ? (string) $export_url : '#';
?>
<a
	href="<?php echo esc_url( $export_url ); ?>"
	class="button alt wcb-export-button"
	data-woocart-bridge-export-button
	data-export-url="<?php echo esc_url( $export_url ); ?>"
>
	<svg
		class="wcb-export-button__icon"
		width="18"
		height="18"
		viewBox="0 0 24 24"
		aria-hidden="true"
		focusable="false"
	>
		<path
			d="M12 3a1 1 0 0 1 1 1v9.59l3.3-3.3a1 1 0 1 1 1.4 1.42l-5 5a1 1 0 0 1-1.4 0l-5-5a1 1 0 1 1 1.4-1.42l3.3 3.3V4a1 1 0 0 1 1-1Zm-7 16a1 1 0 0 1 1-1h12a1 1 0 1 1 0 2H6a1 1 0 0 1-1-1Z"
			fill="currentColor"
		/>
	</svg>
	<span class="wcb-export-button__text"><?php echo esc_html( $button_text ); ?></span>
</a>
