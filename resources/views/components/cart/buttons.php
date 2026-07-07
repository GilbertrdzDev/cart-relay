<?php

defined( 'ABSPATH' ) || exit;

$export_button = isset( $export_button ) ? (string) $export_button : '';
$import_form   = isset( $import_form ) ? (string) $import_form : '';

if ( $export_button === '' && $import_form === '' ) {
	return;
}
?>
<div class="wcb-cart-module">
	<div class="wcb-cart-module__header">
		<div class="wcb-cart-module__heading">
			<h2 class="wcb-cart-module__title"><?php esc_html_e( 'Importar o exportar carrito', 'woocart-bridge' ); ?></h2>
			<p class="wcb-cart-module__description"><?php esc_html_e( 'Guarda tu carrito o cárgalo desde un archivo CSV.', 'woocart-bridge' ); ?></p>
		</div>

		<?php if ( $export_button !== '' ) : ?>
			<div class="wcb-cart-module__export">
				<?php echo $export_button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( $import_form !== '' ) : ?>
		<?php echo $import_form; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php endif; ?>
</div>
