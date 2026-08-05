<?php

namespace CartRelay\App\Components\Forms;

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

	public function getTabs(): array {
		$tabs = [];

		foreach ( $this->sections as $section ) {
			if ( ! $section->hasTab() || $section->getFields() === [] ) {
				continue;
			}

			$tab_id = $section->getTabId();

			if ( ! isset( $tabs[ $tab_id ] ) ) {
				$tabs[ $tab_id ] = [
					'id'       => $tab_id,
					'title'    => $section->getTabTitle(),
					'order'    => $section->getTabOrder(),
					'position' => count( $tabs ),
					'sections' => [],
				];
			}

			$tabs[ $tab_id ]['sections'][] = $section;
		}

		$tabs = array_values( $tabs );
		usort(
			$tabs,
			static fn( array $left, array $right ): int => [ $left['order'], $left['position'] ] <=> [ $right['order'], $right['position'] ]
		);

		return array_map(
			static function ( array $tab ): array {
				unset( $tab['position'] );
				return $tab;
			},
			$tabs
		);
	}

	public function resolveTabId( string $requested ): string {
		$tabs = $this->getTabs();

		foreach ( $tabs as $tab ) {
			if ( $tab['id'] === $requested ) {
				return $requested;
			}
		}

		return $tabs[0]['id'] ?? '';
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
