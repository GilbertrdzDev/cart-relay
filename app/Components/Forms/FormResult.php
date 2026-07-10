<?php

namespace WoocartBridge\App\Components\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Contains sanitized form values and field-level validation errors.
 */
readonly class FormResult {

	public function __construct(
		public array $values,
		public array $errors
	) {}

	public function isValid(): bool {
		return $this->errors === [];
	}

}
