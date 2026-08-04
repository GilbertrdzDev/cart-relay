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
	class="cr:space-y-6"
	method="post"
	data-cr-admin-form
	data-ajax-url="<?php echo esc_url( $ajax_url ); ?>"
	data-initial-values="<?php echo esc_attr( wp_json_encode( $initial_values ) ); ?>"
	x-data="crAdminSettings"
>
	<input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>">
	<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>">

	<?php echo $sections; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

	<div
		class="cr:hidden cr:rounded-lg cr:border cr:px-4 cr:py-3 cr:text-sm"
		data-cr-form-status
		role="status"
		aria-live="polite"
	></div>

	<div class="cr:flex cr:items-center cr:justify-end">
		<button
			type="submit"
			class="cr:inline-flex cr:min-w-32 cr:items-center cr:justify-center cr:gap-2 cr:rounded-lg cr:border cr:border-transparent cr:bg-blue-600 cr:px-4 cr:py-2.5 cr:text-sm cr:font-semibold cr:text-white cr:shadow-sm cr:transition cr:hover:bg-blue-700 cr:focus:outline-none cr:focus:ring-2 cr:focus:ring-blue-500 cr:focus:ring-offset-2 cr:disabled:pointer-events-none cr:disabled:opacity-60"
			data-cr-submit
		>
			<span data-cr-submit-label><?php esc_html_e( 'Save changes', 'cart-relay' ); ?></span>
			<span class="cr:hidden" data-cr-submit-loading><?php esc_html_e( 'Saving…', 'cart-relay' ); ?></span>
		</button>
	</div>
</form>
