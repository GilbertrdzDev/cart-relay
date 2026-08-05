<?php

namespace CartRelay\App\Interfaces;

use CartRelay\App\Core\Loader;


defined( 'ABSPATH' ) || exit;

interface HasFilters {
	public function register_filters( Loader $loader ): void;
}
