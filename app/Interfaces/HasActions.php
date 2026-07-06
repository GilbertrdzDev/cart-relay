<?php

namespace WoocartBridge\App\Interfaces;

use WoocartBridge\App\Core\Loader;


defined( 'ABSPATH' ) || exit;

interface HasActions {
	public function register_actions( Loader $loader ): void;
}
