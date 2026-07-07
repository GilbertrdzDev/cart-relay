<?php

defined( 'ABSPATH' ) || exit;

$ajax_url       = isset( $ajax_url ) ? (string) $ajax_url : admin_url( 'admin-ajax.php' );
$button_text    = isset( $button_text ) ? (string) $button_text : __( 'Import cart', 'woocart-bridge' );
$import_mode    = isset( $import_mode ) ? (string) $import_mode : 'merge';
$preview_action = isset( $preview_action ) ? (string) $preview_action : '';
$preview_nonce  = isset( $preview_nonce ) ? (string) $preview_nonce : '';
$chunk_action   = isset( $chunk_action ) ? (string) $chunk_action : '';
$chunk_nonce    = isset( $chunk_nonce ) ? (string) $chunk_nonce : '';
$template_url   = isset( $template_url ) ? (string) $template_url : '#';
?>
<div
	class="wcb-import-form"
	data-woocart-bridge-import-form
	data-ajax-url="<?php echo esc_url( $ajax_url ); ?>"
	data-preview-action="<?php echo esc_attr( $preview_action ); ?>"
	data-preview-nonce="<?php echo esc_attr( $preview_nonce ); ?>"
	data-chunk-action="<?php echo esc_attr( $chunk_action ); ?>"
	data-chunk-nonce="<?php echo esc_attr( $chunk_nonce ); ?>"
	data-import-mode="<?php echo esc_attr( $import_mode ); ?>"
>
	<input
		class="wcb-import-form__input"
		type="file"
		accept=".csv,text/csv"
		data-wcb-import-file
		hidden
	>

	<button
		type="button"
		class="wcb-import-dropzone"
		data-wcb-import-dropzone
		aria-label="<?php echo esc_attr__( 'Select CSV file', 'woocart-bridge' ); ?>"
	>
		<svg
			class="wcb-import-dropzone__icon"
			width="18"
			height="18"
			viewBox="0 0 24 24"
			aria-hidden="true"
			focusable="false"
		>
			<path
				d="M12 3a1 1 0 0 1 .7.29l4 4a1 1 0 1 1-1.4 1.42L13 6.41V16a1 1 0 1 1-2 0V6.41L8.7 8.71a1 1 0 1 1-1.4-1.42l4-4A1 1 0 0 1 12 3Zm-7 13a1 1 0 0 1 1 1v2h12v-2a1 1 0 1 1 2 0v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1Z"
				fill="currentColor"
			/>
		</svg>

		<span class="wcb-import-dropzone__state" data-wcb-import-empty-state>
			<span class="wcb-import-dropzone__text">
				<?php esc_html_e( 'Drop your CSV here', 'woocart-bridge' ); ?>
				<span class="wcb-import-dropzone__separator"><?php esc_html_e( 'or', 'woocart-bridge' ); ?></span>
				<span class="wcb-import-dropzone__link"><?php esc_html_e( 'click to select', 'woocart-bridge' ); ?></span>
			</span>
			<span class="wcb-import-dropzone__hint">
				<?php esc_html_e( 'CSV format, up to 10 MB', 'woocart-bridge' ); ?>
			</span>
		</span>

		<span class="wcb-import-dropzone__state" data-wcb-import-file-meta hidden>
			<span class="wcb-import-file__name" data-wcb-import-file-name></span>
			<span class="wcb-import-file__ready"><?php esc_html_e( 'Ready to import', 'woocart-bridge' ); ?></span>
			<span class="wcb-import-file__size" data-wcb-import-file-size></span>
			<span class="wcb-import-file__remove-wrap">
				<span class="wcb-import-file__remove" data-wcb-import-remove>
					<?php esc_html_e( 'Remove', 'woocart-bridge' ); ?>
				</span>
			</span>
		</span>
	</button>

	<div class="wcb-import-actions">
		<a class="wcb-import-template-link" href="<?php echo esc_url( $template_url ); ?>" data-wcb-import-template>
			<svg
				class="wcb-import-template-link__icon"
				width="14"
				height="14"
				viewBox="0 0 24 24"
				aria-hidden="true"
				focusable="false"
			>
				<path
					d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 2.5L17.5 8H14V4.5ZM6 4h6v5a1 1 0 0 0 1 1h5v10H6V4Z"
					fill="currentColor"
				/>
			</svg>
			<?php esc_html_e( 'Download CSV template', 'woocart-bridge' ); ?>
		</a>
		<button type="button" class="button alt wcb-import-submit" data-wcb-import-preview>
			<?php echo esc_html( $button_text ); ?>
		</button>
	</div>

	<div class="wcb-import-summary" data-wcb-import-summary hidden></div>
</div>
