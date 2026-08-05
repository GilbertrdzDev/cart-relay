<?php

namespace CartRelay\App\Components\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Groups related fields inside a form.
 */
class Section {

	private array $fields = [];
	private string $tabId = '';
	private string $tabTitle = '';
	private int $tabOrder = 0;

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

	public function tab( string $id, string $title, int $order ): self {
		$this->tabId    = $id;
		$this->tabTitle = $title;
		$this->tabOrder = $order;

		return $this;
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

	public function hasTab(): bool {
		return $this->tabId !== '';
	}

	public function getTabId(): string {
		return $this->tabId;
	}

	public function getTabTitle(): string {
		return $this->tabTitle;
	}

	public function getTabOrder(): int {
		return $this->tabOrder;
	}

	public function getFields(): array {
		return $this->fields;
	}

	private function add( Field $field ): Field {
		$this->fields[] = $field;
		return $field;
	}

}
