<?php

namespace CartRelay\App\Interfaces;

use CartRelay\App\Core\Loader;


defined( 'ABSPATH' ) || exit;

interface Shortcode {
	public static function register_shortcode( Loader $loader ): void;
	public static function render( array $atts, string $content = '' ): string;
}
