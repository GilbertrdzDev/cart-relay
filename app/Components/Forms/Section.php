<?php

namespace WoocartBridge\App\Components\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Groups related fields inside a form.
 */
class Section {

	private array $fields = [];

	public function __construct(
		private readonly string $id,
		private readonly string $title,
		private readonly string $description = ''
	) {}

	public function text( string $name ): Field {
		return $this->add( new Field( $name, FieldType::TEXT ) );
	}

	public function select( string $name ): Field {
		return $this->add( new Field( $name, FieldType::SELECT ) );
	}

	public function toggle( string $name ): Field {
		return $this->add( new Field( $name, FieldType::TOGGLE ) );
	}

	public function hidden( string $name ): Field {
		return $this->add( new Field( $name, FieldType::HIDDEN ) );
	}

	public function getId(): string {
		return $this->id;
	}

	public function getTitle(): string {
		return $this->title;
	}

	public function getDescription(): string {
		return $this->description;
	}

	public function getFields(): array {
		return $this->fields;
	}

	private function add( Field $field ): Field {
		$this->fields[] = $field;
		return $field;
	}

}
