<?php

namespace WoocartBridge\App\Core;

defined( 'ABSPATH' ) || exit;

class Front {

	private Plugin $plugin;

	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	public function registerAssets(): void {
		$this->plugin->getAssetManager()->frontend_vite(
			"{$this->plugin->getName()}-front-js",
			'resources/assets/front/js/app-front.js',
			[
				'in-footer' => true,
			]
		);
	}

	public function registerHooks(): void {}

	public function enqueueStyles(): void {}

	public function enqueueScripts(): void {}

}
