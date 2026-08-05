<?php

namespace CartRelay\App\Core;

defined( 'ABSPATH' ) || exit;

class Loader {

	protected array $actions = [];
	protected array $filters = [];
	protected array $shortcodes = [];

	public function add_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		$this->actions[] = $this->make_hook( $hook, $callback, $priority, $accepted_args );
	}

	public function add_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		$this->filters[] = $this->make_hook( $hook, $callback, $priority, $accepted_args );
	}

	public function add_shortcode( string $tag, callable $callback ): void {
		$this->shortcodes[] = (object) [
			'tag'      => $tag,
			'callback' => $callback,
		];
	}

	private function make_hook( string $hook, callable $callback, int $priority, int $accepted_args ): object {
		return (object) [
			'hook'          => $hook,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		];
	}

	public function run(): void {
		foreach ( $this->actions as $action ) {
			add_action(
				$action->hook,
				$action->callback,
				$action->priority,
				$action->accepted_args
			);
		}

		foreach ( $this->filters as $filter ) {
			add_filter(
				$filter->hook,
				$filter->callback,
				$filter->priority,
				$filter->accepted_args
			);
		}

		foreach ( $this->shortcodes as $shortcode ) {
			add_shortcode(
				$shortcode->tag,
				$shortcode->callback
			);
		}
	}

}
