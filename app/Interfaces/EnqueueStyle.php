<?php

namespace CartRelay\App\Interfaces;

use CartRelay\App\Core\AssetManager;


defined( 'ABSPATH' ) || exit;

interface EnqueueStyle {
	public function enqueue_styles( AssetManager $asset_manager ): void;
}
