<?php

namespace CartRelay\App\Interfaces;

use CartRelay\App\Core\Loader;


defined( 'ABSPATH' ) || exit;

interface HasShortcodes {
	public function register_shortcodes( Loader $loader ): void;
}
