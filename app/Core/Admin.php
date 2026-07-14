<?php

namespace WoocartBridge\App\Core;

use WoocartBridge\App\Components\Admin\SettingsPageComponent;
use WoocartBridge\App\Components\RequirementsCheck;
use WoocartBridge\App\Helpers\PageTemplater;


defined( 'ABSPATH' ) || exit;

class Admin {

	private Plugin $plugin;
	private PageTemplater $pageTemplater;

	public function __construct( Plugin $plugin ) {
		$this->plugin        = $plugin;
		$this->pageTemplater = new PageTemplater();
	}

	public function registerAssets(): void {
		// Admin assets are registered by components so they can be scoped to their screens.
	}

	public function registerHooks(): void {
		$this->plugin->addComponents(
			RequirementsCheck::class,
			SettingsPageComponent::class
		);

		$this->plugin->getLoader()->add_action( 'after_setup_theme', [ $this, 'templates' ] );
	}

	public function enqueueStyles(): void {}

	public function enqueueScripts(): void {}

	public function templates(): void {
		$this->pageTemplater->addTemplates( [
			// 'templates/template-blank.php' => 'Blank template',
		] )->run();
	}

}
