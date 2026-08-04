<?php

defined( 'ABSPATH' ) || exit;

$id          = isset( $id ) ? (string) $id : '';
$title       = isset( $title ) ? (string) $title : '';
$description = isset( $description ) ? (string) $description : '';
$fields      = isset( $fields ) ? (string) $fields : '';
?>
<section class="cr:rounded-xl cr:border cr:border-slate-200 cr:bg-white cr:shadow-sm" aria-labelledby="<?php echo esc_attr( $id ); ?>-title">
	<header class="cr:border-b cr:border-slate-200 cr:px-6 cr:py-5">
		<h2 id="<?php echo esc_attr( $id ); ?>-title" class="cr:m-0 cr:text-lg cr:font-semibold cr:text-slate-900">
			<?php echo esc_html( $title ); ?>
		</h2>
		<?php if ( $description !== '' ) : ?>
			<p class="cr:mt-1 cr:mb-0 cr:text-sm cr:text-slate-600"><?php echo esc_html( $description ); ?></p>
		<?php endif; ?>
	</header>
	<div class="cr:space-y-6 cr:px-6 cr:py-5">
		<?php echo $fields; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</section>
