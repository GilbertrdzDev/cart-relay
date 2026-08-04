<?php

namespace CartRelay\App\Core;

use CartRelay\App\Components\CartButtons\CartButtonsComponent;
use CartRelay\App\Components\CartExport\CartExportComponent;
use CartRelay\App\Components\CartImport\CartImportComponent;

defined( 'ABSPATH' ) || exit;

class Front {

	private Plugin $plugin;

	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	public function registerAssets(): void {
		// Frontend assets are registered by feature components so they can load conditionally.
	}

	public function registerHooks(): void {
		$this->plugin->addComponents(
			CartExportComponent::class,
			CartImportComponent::class,
			CartButtonsComponent::class
		);
	}

	public function enqueueStyles(): void {}

	public function enqueueScripts(): void {}

}
