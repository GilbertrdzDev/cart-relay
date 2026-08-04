<?php

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables are isolated to the component include scope.

$export_button = isset( $export_button ) ? (string) $export_button : '';
$import_form   = isset( $import_form ) ? (string) $import_form : '';

if ( $export_button === '' && $import_form === '' ) {
	return;
}
?>
<div class="cr-cart-module">
	<div class="cr-cart-module__header">
		<div class="cr-cart-module__heading">
			<h2 class="cr-cart-module__title"><?php esc_html_e( 'Import or export cart', 'cart-relay' ); ?></h2>
			<p class="cr-cart-module__description"><?php esc_html_e( 'Save your cart or load it from a CSV file.', 'cart-relay' ); ?></p>
		</div>

		<?php if ( $export_button !== '' ) : ?>
			<div class="cr-cart-module__export">
				<?php echo $export_button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( $import_form !== '' ) : ?>
		<?php echo $import_form; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php endif; ?>
</div>
