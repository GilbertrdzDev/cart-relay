<?php

namespace WoocartBridge\App\Components\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Validates sanitized values using the plugin's supported rule set.
 */
class Validator {

	public function validate( Field $field, mixed $value, array $values ): ?string {
		foreach ( $field->getRules() as $rule ) {
			$when = $rule['when'];

			if ( is_callable( $when ) && ! call_user_func( $when, $values, $field ) ) {
				continue;
			}

			$name       = (string) $rule['name'];
			$parameters = (array) $rule['parameters'];
			$result     = $this->passes( $name, $value, $parameters, $values, $field );

			if ( $result === true ) {
				continue;
			}

			if ( is_string( $result ) && $result !== '' ) {
				return $result;
			}

			return $rule['message']
				?? $field->getMessage( $name )
				?? $this->defaultMessage( $field, $name, $parameters );
		}

		return null;
	}

	private function passes(
		string $rule,
		mixed $value,
		array $parameters,
		array $values,
		Field $field
	): bool|string {
		if ( $rule !== 'required' && $this->isEmpty( $value ) ) {
			return true;
		}

		return match ( $rule ) {
			'required'   => ! $this->isEmpty( $value ),
			'email'      => is_email( (string) $value ) !== false,
			'url'        => filter_var( $value, FILTER_VALIDATE_URL ) !== false,
			'numeric'    => is_numeric( $value ),
			'integer'    => filter_var( $value, FILTER_VALIDATE_INT ) !== false,
			'min'        => is_numeric( $value ) && (float) $value >= (float) ( $parameters[0] ?? 0 ),
			'max'        => is_numeric( $value ) && (float) $value <= (float) ( $parameters[0] ?? 0 ),
			'min_length' => $this->length( (string) $value ) >= (int) ( $parameters[0] ?? 0 ),
			'max_length' => $this->length( (string) $value ) <= (int) ( $parameters[0] ?? 0 ),
			'in'         => in_array( $value, (array) ( $parameters[0] ?? [] ), true ),
			'boolean'    => is_bool( $value ),
			'custom'     => $this->passesCustom( $parameters, $value, $values, $field ),
			default      => false,
		};
	}

	private function passesCustom( array $parameters, mixed $value, array $values, Field $field ): bool|string {
		$callback = $parameters[0] ?? null;

		if ( ! is_callable( $callback ) ) {
			return false;
		}

		$result = call_user_func( $callback, $value, $values, $field );

		return is_string( $result ) || is_bool( $result ) ? $result : false;
	}

	private function defaultMessage( Field $field, string $rule, array $parameters ): string {
		$label = $field->getLabel() !== '' ? $field->getLabel() : $field->getName();
		$limit = (string) ( $parameters[0] ?? '' );

		return match ( $rule ) {
			'required'   => sprintf( __( '%s is required.', 'woocart-bridge' ), $label ),
			'email'      => sprintf( __( '%s must be a valid email address.', 'woocart-bridge' ), $label ),
			'url'        => sprintf( __( '%s must be a valid URL.', 'woocart-bridge' ), $label ),
			'numeric'    => sprintf( __( '%s must be numeric.', 'woocart-bridge' ), $label ),
			'integer'    => sprintf( __( '%s must be an integer.', 'woocart-bridge' ), $label ),
			'min'        => sprintf( __( '%1$s must be at least %2$s.', 'woocart-bridge' ), $label, $limit ),
			'max'        => sprintf( __( '%1$s must not exceed %2$s.', 'woocart-bridge' ), $label, $limit ),
			'min_length' => sprintf( __( '%1$s must contain at least %2$s characters.', 'woocart-bridge' ), $label, $limit ),
			'max_length' => sprintf( __( '%1$s must not exceed %2$s characters.', 'woocart-bridge' ), $label, $limit ),
			'in'         => sprintf( __( '%s contains an invalid selection.', 'woocart-bridge' ), $label ),
			'boolean'    => sprintf( __( '%s must be enabled or disabled.', 'woocart-bridge' ), $label ),
			default      => sprintf( __( '%s is invalid.', 'woocart-bridge' ), $label ),
		};
	}

	private function isEmpty( mixed $value ): bool {
		return $value === null || $value === '' || ( is_array( $value ) && $value === [] );
	}

	private function length( string $value ): int {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value );
	}

}
