<?php

namespace WoocartBridge\App\Interfaces;

use WoocartBridge\App\Core\AssetManager;


defined( 'ABSPATH' ) || exit;

interface EnqueueStyle {
	public function enqueue_styles( AssetManager $asset_manager ): void;
}
