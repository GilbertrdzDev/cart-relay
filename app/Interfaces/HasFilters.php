<?php

namespace WoocartBridge\App\Interfaces;

use WoocartBridge\App\Core\Loader;


defined( 'ABSPATH' ) || exit;

interface HasFilters {
	public function register_filters( Loader $loader ): void;
}
