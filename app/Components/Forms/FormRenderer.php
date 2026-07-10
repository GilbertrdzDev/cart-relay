<?php

namespace WoocartBridge\App\Components\Forms;

use WoocartBridge\App\Core\ComponentCompiler;

defined( 'ABSPATH' ) || exit;

/**
 * Converts form definitions into escaped PHP component views.
 */
class FormRenderer {

	public function __construct( private readonly ComponentCompiler $compiler ) {}

	public function render( Form $form, array $values, array $errors = [] ): string {
		$sections = '';
		$initial  = [];

		foreach ( $form->getSections() as $section ) {
			$fields = '';

			foreach ( $section->getFields() as $field ) {
				$value = $values[ $field->getName() ] ?? $field->getDefault();
				$initial[ $field->getName() ] = $value;
				$fields .= $this->renderField( $field, $value, $errors[ $field->getName() ] ?? '' );
			}

			$sections .= $this->compiler->render(
				'forms.section',
				[
					'id'          => $section->getId(),
					'title'       => $section->getTitle(),
					'description' => $section->getDescription(),
					'fields'      => $fields,
				]
			);
		}

		return $this->compiler->render(
			'forms.form',
			[
				'id'             => $form->getId(),
				'action'         => $form->getAction(),
				'nonce'          => wp_create_nonce( $form->getNonceAction() ),
				'ajax_url'       => admin_url( 'admin-ajax.php' ),
				'initial_values' => $initial,
				'sections'       => $sections,
			]
		);
	}

	private function renderField( Field $field, mixed $value, string $error ): string {
		$requiredCondition = $field->getRequiredCondition();
		$describedBy = array_filter(
			[
				$field->getDescription() !== '' ? $field->getId() . '-description' : '',
				$field->getHelp() !== '' ? $field->getId() . '-help' : '',
				$field->getId() . '-error',
			]
		);

		$attributes = array_merge(
			$field->getAttributes(),
			[
				'id'               => $field->getId(),
				'name'             => 'settings[' . $field->getName() . ']',
				'disabled'         => $field->isDisabled(),
				'readonly'         => $field->isReadonly(),
				'required'         => $field->isRequired() && $requiredCondition === null,
				'placeholder'      => $field->getPlaceholder(),
				'aria-describedby' => implode( ' ', $describedBy ),
				'aria-invalid'     => $error !== '' ? 'true' : 'false',
				'data-wcb-field'   => $field->getName(),
				'class'            => implode( ' ', [ ...$this->defaultClasses( $field ), ...$field->getClasses() ] ),
			]
		);

		if ( $requiredCondition !== null ) {
			$attributes['data-required-field'] = $requiredCondition['field'];
			$attributes['data-required-value'] = wp_json_encode( $requiredCondition['value'] );
			$attributes['x-bind:required']      = 'isFieldRequired($el)';
		}

		if ( $field->getType() === FieldType::TOGGLE ) {
			$attributes['value']   = '1';
			$attributes['checked'] = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
			$attributes['x-model'] = 'values.' . $field->getName();
		}

		if ( $field->getType() === FieldType::TEXT ) {
			$attributes['value'] = (string) $value;
		}

		$input = $this->compiler->render(
			'forms.fields.' . $field->getType()->value,
			[
				'attributes' => $this->attributes( $attributes ),
				'name'       => $field->getName(),
				'value'      => $value,
				'options'    => $field->getOptions(),
			]
		);

		return $this->compiler->render(
			'forms.field',
			[
				'field'      => $field,
				'input'      => $input,
				'error'      => $error,
				'visibility' => $field->getVisibility(),
			]
		);
	}

	private function defaultClasses( Field $field ): array {
		return match ( $field->getType() ) {
			FieldType::TEXT => [
				'wcb:block', 'wcb:w-full', 'wcb:rounded-lg', 'wcb:border', 'wcb:border-slate-300',
				'wcb:bg-white', 'wcb:px-3', 'wcb:py-2.5', 'wcb:text-sm', 'wcb:text-slate-900',
				'wcb:shadow-sm', 'wcb:outline-none', 'wcb:transition', 'wcb:focus:border-blue-500',
				'wcb:focus:ring-2', 'wcb:focus:ring-blue-500/20', 'wcb:disabled:cursor-not-allowed',
				'wcb:disabled:bg-slate-100',
			],
			FieldType::SELECT => [
				'wcb:block', 'wcb:w-full', 'wcb:rounded-lg', 'wcb:border', 'wcb:border-slate-300',
				'wcb:bg-white', 'wcb:px-3', 'wcb:py-2.5', 'wcb:text-sm', 'wcb:text-slate-900',
				'wcb:shadow-sm', 'wcb:outline-none', 'wcb:focus:border-blue-500', 'wcb:focus:ring-2',
				'wcb:focus:ring-blue-500/20',
			],
			FieldType::TOGGLE => [
				'wcb:peer', 'wcb:sr-only',
			],
			FieldType::HIDDEN => [],
		};
	}

	private function attributes( array $attributes ): string {
		$output = [];

		foreach ( $attributes as $name => $value ) {
			$name = (string) $name;

			if (
				! preg_match( '/^[A-Za-z_:][A-Za-z0-9:._-]*$/', $name )
				|| str_starts_with( strtolower( $name ), 'on' )
				|| strtolower( $name ) === 'style'
			) {
				continue;
			}

			if ( $value === false || $value === null || $value === '' ) {
				continue;
			}

			if ( $value === true ) {
				$output[] = esc_attr( $name );
				continue;
			}

			if ( ! is_scalar( $value ) ) {
				continue;
			}

			$output[] = sprintf( '%s="%s"', esc_attr( $name ), esc_attr( (string) $value ) );
		}

		return implode( ' ', $output );
	}

}
