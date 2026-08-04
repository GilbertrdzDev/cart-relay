<?php

namespace CartRelay\App\Core;

use CartRelay\App\Components\Admin\SettingsPageComponent;
use CartRelay\App\Components\RequirementsCheck;

defined( 'ABSPATH' ) || exit;

class Admin {

	private Plugin $plugin;

	public function __construct( Plugin $plugin ) {

		$this->plugin = $plugin;
	}

	public function registerAssets(): void {

		// Admin assets are registered by components so they can be scoped to their screens.
	}
	public function registerHooks(): void {

		$this->plugin->addComponents( RequirementsCheck::class, SettingsPageComponent::class );
	}
	public function enqueueStyles(): void {}

	public function enqueueScripts(): void {}

}
