<?php

namespace WoocartBridge\App\Interfaces;

use WoocartBridge\App\Core\Loader;


defined( 'ABSPATH' ) || exit;

interface Shortcode {
	public static function register_shortcode( Loader $loader ): void;
	public static function render( array $atts, string $content = '' ): string;
}
