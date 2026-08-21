<?php

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound, WordPress.WP.GlobalVariablesOverride.Prohibited -- Variables are isolated to the component include scope.

$form_id    = isset( $form_id ) ? (string) $form_id : '';
$tabs       = isset( $tabs ) && is_array( $tabs ) ? $tabs : [];
$active_tab = isset( $active_tab ) ? (string) $active_tab : '';
?>
<div class="cr:space-y-6" data-cr-tabs data-active-tab="<?php echo esc_attr( $active_tab ); ?>">
	<div class="cr:max-w-full cr:overflow-x-auto cr:rounded-xl cr:border cr:border-slate-200 cr:bg-white cr:p-1 cr:shadow-sm" data-cr-tab-scroller>
		<div class="cr:flex cr:min-w-max cr:gap-1" role="tablist" aria-label="<?php esc_attr_e( 'Settings sections', 'cart-relay' ); ?>" aria-orientation="horizontal">
			<?php foreach ( $tabs as $tab ) : ?>
				<?php
				$tab_id    = (string) ( $tab['id'] ?? '' );
				$tab_title = (string) ( $tab['title'] ?? '' );
				$is_active = $tab_id === $active_tab;
				?>
				<button
					id="<?php echo esc_attr( $form_id . '-tab-' . $tab_id ); ?>"
					type="button"
					class="cr:inline-flex cr:shrink-0 cr:items-center cr:justify-center cr:rounded-lg cr:px-4 cr:py-2.5 cr:text-sm cr:font-semibold cr:text-slate-600 cr:transition cr:hover:bg-slate-100 cr:hover:text-slate-900 cr:focus:outline-none cr:focus-visible:ring-2 cr:focus-visible:ring-blue-500 cr:focus-visible:ring-offset-2 cr:aria-selected:bg-blue-600 cr:aria-selected:text-white cr:aria-selected:shadow-sm cr:aria-selected:hover:bg-blue-600 cr:aria-selected:hover:text-white"
					role="tab"
					aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
					aria-controls="<?php echo esc_attr( $form_id . '-panel-' . $tab_id ); ?>"
					tabindex="<?php echo $is_active ? '0' : '-1'; ?>"
					data-cr-tab="<?php echo esc_attr( $tab_id ); ?>"
				>
					<?php echo esc_html( $tab_title ); ?>
				</button>
			<?php endforeach; ?>
		</div>
	</div>

	<?php foreach ( $tabs as $tab ) : ?>
		<?php
		$tab_id    = (string) ( $tab['id'] ?? '' );
		$content   = (string) ( $tab['content'] ?? '' );
		$is_active = $tab_id === $active_tab;
		?>
		<div
			id="<?php echo esc_attr( $form_id . '-panel-' . $tab_id ); ?>"
			class="cr:space-y-6"
			role="tabpanel"
			aria-labelledby="<?php echo esc_attr( $form_id . '-tab-' . $tab_id ); ?>"
			tabindex="0"
			data-cr-tab-panel="<?php echo esc_attr( $tab_id ); ?>"
			<?php if ( ! $is_active ) : ?>
				hidden
			<?php endif; ?>
		>
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Nested form components escape their leaf values. ?>
		</div>
	<?php endforeach; ?>
</div>
