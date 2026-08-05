<?php

namespace CartRelay\App\Interfaces;

use CartRelay\App\Core\Loader;


defined( 'ABSPATH' ) || exit;

interface HasActions {
	public function register_actions( Loader $loader ): void;
}
