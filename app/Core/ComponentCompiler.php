<?php

namespace CartRelay\App\Core;

use Exception;


defined( 'ABSPATH' ) || exit;

class ComponentCompiler {

	private static ?ComponentCompiler $instance = null;

	public function __construct(
		private string $component_path,
		private array $data = []
	) {}

	public static function get_instance( string $component_path = '', array $data = [] ): self {
		if ( self::$instance === null ) {
			self::$instance = new self( $component_path, $data );
		}

		if ( $component_path !== '' ) {
			self::$instance->set_component_path( $component_path );
		}

		return self::$instance;
	}

	public function set_component_path( string $component_path ): self {
		$this->component_path = rtrim( $component_path, '/\\' ) . DIRECTORY_SEPARATOR;

		return $this;
	}

	public function render( string $component, array $data = [] ): string {
		$template = $this->get_component( $component );

		return $this->compile( $template, array_merge( $this->data, $data ) );
	}

	private function get_component( string $component ): string {
		if ( ! preg_match( '/^[A-Za-z0-9_-]+(?:\.[A-Za-z0-9_-]+)*$/', $component ) ) {
			throw new Exception( "Invalid component name {$component}." );
		}

		$base_path = realpath( $this->component_path );

		if ( $base_path === false || ! is_dir( $base_path ) ) {
			throw new Exception( 'Component path not found.' );
		}

		$template      = $base_path . DIRECTORY_SEPARATOR . str_replace( '.', DIRECTORY_SEPARATOR, $component ) . '.php';
		$real_template = realpath( $template );

		if (
			$real_template === false
			|| ! is_file( $real_template )
			|| ! str_starts_with( $real_template, $base_path . DIRECTORY_SEPARATOR )
		) {
			throw new Exception( "Component {$component} not found." );
		}

		return $real_template;
	}

	private function compile( string $template, array $data = [] ): string {
		ob_start();

		extract( $data, EXTR_SKIP );
		include $template;

		return (string) ob_get_clean();
	}

}
