<?php

defined( 'ABSPATH' ) || exit;

$form = isset( $form ) ? (string) $form : '';
?>
<div class="wcb-admin wcb:mx-auto wcb:max-w-5xl wcb:py-8 wcb:pr-5">
	<div class="wcb:mb-7">
		<p class="wcb:mb-2 wcb:text-sm wcb:font-semibold wcb:uppercase wcb:tracking-wide wcb:text-blue-600">
			<?php esc_html_e( 'WooCommerce cart tools', 'woocart-bridge' ); ?>
		</p>
		<h1 class="wcb:m-0 wcb:text-3xl wcb:font-bold wcb:tracking-tight wcb:text-slate-950">
			<?php esc_html_e( 'WooCart Bridge settings', 'woocart-bridge' ); ?>
		</h1>
		<p class="wcb:mt-2 wcb:mb-0 wcb:max-w-3xl wcb:text-base wcb:text-slate-600">
			<?php esc_html_e( 'Configure CSV cart import and export without changing your WooCommerce cart templates.', 'woocart-bridge' ); ?>
		</p>
	</div>

	<?php echo $form; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
