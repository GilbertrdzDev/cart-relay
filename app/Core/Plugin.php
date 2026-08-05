<?php

namespace CartRelay\App\Core;

use CartRelay\App\Interfaces\EnqueueScript;
use CartRelay\App\Interfaces\EnqueueStyle;
use CartRelay\App\Interfaces\HasActions;
use CartRelay\App\Interfaces\HasFilters;
use CartRelay\App\Interfaces\HasShortcodes;
use CartRelay\App\Interfaces\Shortcode;


defined( 'ABSPATH' ) || exit;

class Plugin {

	protected Loader $loader;
	protected AssetManager $asset_manager;
	public ComponentCompiler $component;
	protected string $name = 'cart-relay';
	private array $components = [];

	public function __construct() {
		$this->loader        = new Loader();
		$this->asset_manager = new AssetManager(
			$this->loader,
			CART_RELAY_DIR_PATH,
			CART_RELAY_DIR_URL
		);
		$this->component     = ComponentCompiler::get_instance(
			CART_RELAY_DIR_PATH . 'resources/views/components/'
		);
	}

	private function setAdminHooks(): void {
		$admin = new Admin( $this );
		$admin->registerAssets();
		$admin->registerHooks();
	}

	private function setFrontendHooks(): void {
		$frontend = new Front( $this );
		$frontend->registerAssets();
		$frontend->registerHooks();
	}

	public function addComponents( string ...$classes ): static {
		$this->components = [ ...$this->components, ...$classes ];

		return $this;
	}

	public function getComponents(): array {
		return $this->components;
	}

	private function registerComponents(): void {
		foreach ( $this->getComponents() as $component ) {
			$instance = new $component();

			$instance instanceof EnqueueStyle && $instance->enqueue_styles( $this->asset_manager );
			$instance instanceof EnqueueScript && $instance->enqueue_scripts( $this->asset_manager );
			$instance instanceof HasActions && $instance->register_actions( $this->loader );
			$instance instanceof HasFilters && $instance->register_filters( $this->loader );
			$instance instanceof HasShortcodes && $instance->register_shortcodes( $this->loader );
			$instance instanceof Shortcode && $instance::register_shortcode( $this->loader );
		}
	}

	public function run(): void {
		$this->setAdminHooks();
		$this->setFrontendHooks();
		$this->registerComponents();
		$this->asset_manager->init();
		$this->loader->run();
	}

	public function getName(): string {
		return $this->name;
	}

	public function getLoader(): Loader {
		return $this->loader;
	}

	public function getAssetManager(): AssetManager {
		return $this->asset_manager;
	}

	public function getVersion(): string {
		return CART_RELAY_VERSION;
	}

}
