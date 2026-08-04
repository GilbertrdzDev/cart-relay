<?php

namespace CartRelay\App\Components\Forms;

use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

/**
 * Applies named WordPress sanitizers or a form-specific callable.
 */
class Sanitizer {

	public function sanitize( Field $field, mixed $value ): mixed {
		$strategy = $field->getSanitizer() ?? $this->defaultStrategy( $field->getType() );

		if ( is_callable( $strategy ) ) {
			return call_user_func( $strategy, $value, $field );
		}

		return match ( $strategy ) {
			'sanitize_text_field'     => sanitize_text_field( (string) $value ),
			'sanitize_textarea_field' => sanitize_textarea_field( (string) $value ),
			'sanitize_email'          => sanitize_email( (string) $value ),
			'esc_url_raw'             => esc_url_raw( (string) $value ),
			'absint'                  => absint( $value ),
			'rest_sanitize_boolean',
			'boolean'                 => rest_sanitize_boolean( $value ),
			default                   => throw new InvalidArgumentException( "Unknown sanitizer {$strategy}." ), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal exception, not HTML output.
		};
	}

	private function defaultStrategy( FieldType $type ): string {
		return match ( $type ) {
			FieldType::TOGGLE => 'boolean',
			default           => 'sanitize_text_field',
		};
	}

}
