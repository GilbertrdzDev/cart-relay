<?php

namespace WoocartBridge\App\Interfaces;

use WoocartBridge\App\Core\Loader;


defined( 'ABSPATH' ) || exit;

interface HasShortcodes {
	public function register_shortcodes( Loader $loader ): void;
}
