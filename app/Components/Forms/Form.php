<?php

namespace WoocartBridge\App\Components\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Defines a renderable and processable plugin form.
 */
class Form {

	private array $sections = [];

	public function __construct(
		private readonly string $id,
		private readonly string $action,
		private readonly string $nonceAction
	) {}

	public function section( string $id, string $title, string $description = '' ): Section {
		$section = new Section( $id, $title, $description );
		$this->sections[] = $section;

		return $section;
	}

	public function getId(): string {
		return $this->id;
	}

	public function getAction(): string {
		return $this->action;
	}

	public function getNonceAction(): string {
		return $this->nonceAction;
	}

	public function getSections(): array {
		return $this->sections;
	}

	public function getFields(): array {
		$fields = [];

		foreach ( $this->sections as $section ) {
			foreach ( $section->getFields() as $field ) {
				$fields[ $field->getName() ] = $field;
			}
		}

		return $fields;
	}

}
