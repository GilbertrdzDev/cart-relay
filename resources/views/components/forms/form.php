<?php

defined( 'ABSPATH' ) || exit;

$id             = isset( $id ) ? (string) $id : '';
$action         = isset( $action ) ? (string) $action : '';
$nonce          = isset( $nonce ) ? (string) $nonce : '';
$ajax_url       = isset( $ajax_url ) ? (string) $ajax_url : '';
$initial_values = isset( $initial_values ) && is_array( $initial_values ) ? $initial_values : [];
$sections       = isset( $sections ) ? (string) $sections : '';
?>
<form
	id="<?php echo esc_attr( $id ); ?>"
	class="wcb:space-y-6"
	method="post"
	data-wcb-admin-form
	data-ajax-url="<?php echo esc_url( $ajax_url ); ?>"
	data-initial-values="<?php echo esc_attr( wp_json_encode( $initial_values ) ); ?>"
	x-data="wcbAdminSettings"
>
	<input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>">
	<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>">

	<?php echo $sections; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

	<div
		class="wcb:hidden wcb:rounded-lg wcb:border wcb:px-4 wcb:py-3 wcb:text-sm"
		data-wcb-form-status
		role="status"
		aria-live="polite"
	></div>

	<div class="wcb:flex wcb:items-center wcb:justify-end">
		<button
			type="submit"
			class="wcb:inline-flex wcb:min-w-32 wcb:items-center wcb:justify-center wcb:gap-2 wcb:rounded-lg wcb:border wcb:border-transparent wcb:bg-blue-600 wcb:px-4 wcb:py-2.5 wcb:text-sm wcb:font-semibold wcb:text-white wcb:shadow-sm wcb:transition wcb:hover:bg-blue-700 wcb:focus:outline-none wcb:focus:ring-2 wcb:focus:ring-blue-500 wcb:focus:ring-offset-2 wcb:disabled:pointer-events-none wcb:disabled:opacity-60"
			data-wcb-submit
		>
			<span data-wcb-submit-label><?php esc_html_e( 'Save changes', 'woocart-bridge' ); ?></span>
			<span class="wcb:hidden" data-wcb-submit-loading><?php esc_html_e( 'Saving…', 'woocart-bridge' ); ?></span>
		</button>
	</div>
</form>
