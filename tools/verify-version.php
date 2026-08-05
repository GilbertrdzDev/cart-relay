<?php

$root = dirname( __DIR__ );
$plugin_file = file_get_contents( $root . '/cart-relay.php' );
$readme = file_get_contents( $root . '/readme.txt' );
$changelog = file_get_contents( $root . '/CHANGELOG.md' );
$package = json_decode( (string) file_get_contents( $root . '/package.json' ), true );
$package_lock = json_decode( (string) file_get_contents( $root . '/package-lock.json' ), true );

if ( $plugin_file === false || $readme === false || $changelog === false || ! is_array( $package ) || ! is_array( $package_lock ) ) {
	fwrite( STDERR, "Unable to read the versioned project files.\n" );
	exit( 1 );
}

$patterns = [
	'plugin header'    => '/^[ \t*]*Version:\s*([0-9]+(?:\.[0-9]+){2})\s*$/mi',
	'version constant' => "/define\(\s*'CART_RELAY_VERSION',\s*'([0-9]+(?:\.[0-9]+){2})'\s*\)/",
	'stable tag'       => '/^Stable tag:\s*([0-9]+(?:\.[0-9]+){2})\s*$/mi',
];

$versions = [];

foreach ( $patterns as $label => $pattern ) {
	$subject = $label === 'stable tag' ? $readme : $plugin_file;

	if ( preg_match( $pattern, $subject, $matches ) !== 1 ) {
		fwrite( STDERR, "Unable to find the {$label} version.\n" );
		exit( 1 );
	}

	$versions[ $label ] = $matches[1];
}

$versions['package.json'] = isset( $package['version'] ) ? (string) $package['version'] : '';
$versions['package-lock.json'] = isset( $package_lock['version'] ) ? (string) $package_lock['version'] : '';
$versions['package-lock root'] = isset( $package_lock['packages']['']['version'] )
	? (string) $package_lock['packages']['']['version']
	: '';

$expected = $versions['plugin header'];
$requested_version = isset( $argv[1] ) ? trim( (string) $argv[1] ) : '';

if ( '' !== $requested_version && $requested_version !== $expected ) {
	fwrite( STDERR, "Requested release version {$requested_version} does not match plugin version {$expected}.\n" );
	exit( 1 );
}

foreach ( $versions as $label => $version ) {
	if ( $version !== $expected ) {
		fwrite( STDERR, "Version mismatch: {$label} is {$version}; expected {$expected}.\n" );
		exit( 1 );
	}
}

if ( ! str_contains( $changelog, "## [{$expected}]" ) ) {
	fwrite( STDERR, "CHANGELOG.md does not contain a {$expected} release heading.\n" );
	exit( 1 );
}

fwrite( STDOUT, "Version {$expected} is synchronized.\n" );
