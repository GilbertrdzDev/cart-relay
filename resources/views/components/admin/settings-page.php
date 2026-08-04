<?php

defined( 'ABSPATH' ) || exit;

$form = isset( $form ) ? (string) $form : '';
?>
<div class="cr-admin cr:mx-auto cr:max-w-5xl cr:py-8 cr:pr-5">
	<div class="cr:mb-7">
		<p class="cr:mb-2 cr:text-sm cr:font-semibold cr:uppercase cr:tracking-wide cr:text-blue-600">
			<?php esc_html_e( 'WooCommerce cart tools', 'cart-relay' ); ?>
		</p>
		<h1 class="cr:m-0 cr:text-3xl cr:font-bold cr:tracking-tight cr:text-slate-950">
			<?php esc_html_e( 'Cart Relay settings', 'cart-relay' ); ?>
		</h1>
		<p class="cr:mt-2 cr:mb-0 cr:max-w-3xl cr:text-base cr:text-slate-600">
			<?php esc_html_e( 'Configure CSV cart import and export without changing your WooCommerce cart templates.', 'cart-relay' ); ?>
		</p>
	</div>

	<?php echo $form; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
