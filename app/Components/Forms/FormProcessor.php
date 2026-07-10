<?php

namespace WoocartBridge\App\Components\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Sanitizes and validates only known fields present in a request.
 */
class FormProcessor {

	public function __construct(
		private readonly Sanitizer $sanitizer = new Sanitizer(),
		private readonly Validator $validator = new Validator()
	) {}

	public function process( Form $form, array $input ): FormResult {
		$values = [];
		$errors = [];

		foreach ( $form->getFields() as $name => $field ) {
			if ( $field->isDisabled() || ! array_key_exists( $name, $input ) ) {
				continue;
			}

			$values[ $name ] = $this->sanitizer->sanitize( $field, $input[ $name ] );
		}

		foreach ( $values as $name => $value ) {
			$error = $this->validator->validate( $form->getFields()[ $name ], $value, $values );

			if ( $error !== null ) {
				$errors[ $name ] = $error;
			}
		}

		return new FormResult( $values, $errors );
	}

}
