<?php

use CartRelay\App\Components\Forms\Field;
use CartRelay\App\Components\Forms\FieldType;

defined( 'ABSPATH' ) || exit;

$field      = isset( $field ) && $field instanceof Field ? $field : null;
$input      = isset( $input ) ? (string) $input : '';
$error      = isset( $error ) ? (string) $error : '';
$visibility = isset( $visibility ) && is_array( $visibility ) ? $visibility : null;

if ( ! $field ) {
	return;
}

if ( $field->getType() === FieldType::HIDDEN ) {
	echo $input; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
?>
<div
	class="cr:grid cr:gap-2 cr:md:grid-cols-[minmax(0,1fr)_minmax(280px,1.25fr)] cr:md:gap-8"
	data-cr-field-wrapper="<?php echo esc_attr( $field->getName() ); ?>"
	<?php if ( $visibility ) : ?>
		data-visible-field="<?php echo esc_attr( (string) $visibility['field'] ); ?>"
		data-visible-value="<?php echo esc_attr( wp_json_encode( $visibility['value'] ) ); ?>"
		x-show="isFieldVisible($el)"
		x-cloak
	<?php endif; ?>
>
	<div>
		<label for="<?php echo esc_attr( $field->getId() ); ?>" class="cr:block cr:text-sm cr:font-semibold cr:text-slate-900">
			<?php echo esc_html( $field->getLabel() ); ?>
			<?php if ( $field->isRequired() ) : ?>
				<span class="cr:text-red-600" aria-hidden="true">*</span>
			<?php endif; ?>
		</label>
		<?php if ( $field->getDescription() !== '' ) : ?>
			<p id="<?php echo esc_attr( $field->getId() ); ?>-description" class="cr:mt-1 cr:mb-0 cr:text-sm cr:text-slate-600">
				<?php echo esc_html( $field->getDescription() ); ?>
			</p>
		<?php endif; ?>
	</div>

	<div>
		<?php echo $input; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php if ( $field->getHelp() !== '' ) : ?>
			<p id="<?php echo esc_attr( $field->getId() ); ?>-help" class="cr:mt-1.5 cr:mb-0 cr:text-xs cr:text-slate-500">
				<?php echo esc_html( $field->getHelp() ); ?>
			</p>
		<?php endif; ?>
		<p
			id="<?php echo esc_attr( $field->getId() ); ?>-error"
			class="<?php echo $error === '' ? 'cr:hidden ' : ''; ?>cr:mt-1.5 cr:mb-0 cr:text-sm cr:text-red-600"
			data-cr-field-error="<?php echo esc_attr( $field->getName() ); ?>"
			aria-live="polite"
		><?php echo esc_html( $error ); ?></p>
	</div>
</div>
