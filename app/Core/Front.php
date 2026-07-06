<?php

namespace WoocartBridge\App\Core;

defined( 'ABSPATH' ) || exit;

class Front {

	private Plugin $plugin;

	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	public function registerAssets(): void {
		// Frontend assets are registered by feature components so they can load conditionally.
	}

	public function registerHooks(): void {}

	public function enqueueStyles(): void {}

	public function enqueueScripts(): void {}

}
