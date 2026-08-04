<?php

defined( 'ABSPATH' ) || define( 'ABSPATH', dirname( __DIR__ ) . DIRECTORY_SEPARATOR );

if ( ! function_exists( '__' ) ) {
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

require dirname( __DIR__ ) . '/vendor/autoload.php';
