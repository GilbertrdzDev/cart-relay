<?php

namespace CartRelay\App\Interfaces;

use CartRelay\App\Core\AssetManager;


defined( 'ABSPATH' ) || exit;

interface EnqueueScript {
	public function enqueue_scripts( AssetManager $asset_manager ): void;
}
