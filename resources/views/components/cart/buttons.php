<?php

defined( 'ABSPATH' ) || exit;

$export_button = isset( $export_button ) ? (string) $export_button : '';
$import_form   = isset( $import_form ) ? (string) $import_form : '';

if ( $export_button === '' && $import_form === '' ) {
	return;
}
?>
<div class="wcb-cart-buttons">
	<?php echo $export_button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php echo $import_form; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
