<?php

namespace WoocartBridge\App\Interfaces;

use WoocartBridge\App\Core\AssetManager;


defined( 'ABSPATH' ) || exit;

interface EnqueueScript {
	public function enqueue_scripts( AssetManager $asset_manager ): void;
}
