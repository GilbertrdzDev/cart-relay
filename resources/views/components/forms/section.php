<?php

defined( 'ABSPATH' ) || exit;

$id          = isset( $id ) ? (string) $id : '';
$title       = isset( $title ) ? (string) $title : '';
$description = isset( $description ) ? (string) $description : '';
$fields      = isset( $fields ) ? (string) $fields : '';
?>
<section class="wcb:rounded-xl wcb:border wcb:border-slate-200 wcb:bg-white wcb:shadow-sm" aria-labelledby="<?php echo esc_attr( $id ); ?>-title">
	<header class="wcb:border-b wcb:border-slate-200 wcb:px-6 wcb:py-5">
		<h2 id="<?php echo esc_attr( $id ); ?>-title" class="wcb:m-0 wcb:text-lg wcb:font-semibold wcb:text-slate-900">
			<?php echo esc_html( $title ); ?>
		</h2>
		<?php if ( $description !== '' ) : ?>
			<p class="wcb:mt-1 wcb:mb-0 wcb:text-sm wcb:text-slate-600"><?php echo esc_html( $description ); ?></p>
		<?php endif; ?>
	</header>
	<div class="wcb:space-y-6 wcb:px-6 wcb:py-5">
		<?php echo $fields; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</section>
