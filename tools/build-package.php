<?php

declare( strict_types=1 );

$root       = dirname( __DIR__ );
$build_root = $root . DIRECTORY_SEPARATOR . 'build';
$stage      = $build_root . DIRECTORY_SEPARATOR . 'cart-relay';
$zip_path   = $build_root . DIRECTORY_SEPARATOR . 'cart-relay.zip';
$manifest   = $build_root . DIRECTORY_SEPARATOR . 'package-manifest.txt';

/**
 * Removes a generated directory tree.
 */
function cart_relay_remove_tree( string $path, string $allowed_parent ): void {
	if ( ! is_dir( $path ) ) {
		return;
	}

	$resolved_path   = realpath( $path );
	$resolved_parent = realpath( $allowed_parent );

	if ( false === $resolved_path || false === $resolved_parent || ! str_starts_with( $resolved_path, $resolved_parent . DIRECTORY_SEPARATOR ) ) {
		throw new RuntimeException( 'Refusing to remove a path outside the package build directory.' );
	}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $resolved_path, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ( $iterator as $item ) {
		if ( $item->isLink() ) {
			throw new RuntimeException( 'Generated package directories must not contain symbolic links.' );
		}

		$item->isDir() ? rmdir( $item->getPathname() ) : unlink( $item->getPathname() );
	}

	rmdir( $resolved_path );
}

/**
 * Copies a file or directory without following symbolic links.
 */
function cart_relay_copy_path( string $source, string $destination ): void {
	if ( is_link( $source ) ) {
		throw new RuntimeException( "Package sources must not be symbolic links: {$source}" );
	}

	if ( is_file( $source ) ) {
		$parent = dirname( $destination );

		if ( ! is_dir( $parent ) && ! mkdir( $parent, 0777, true ) && ! is_dir( $parent ) ) {
			throw new RuntimeException( "Could not create package directory: {$parent}" );
		}

		if ( ! copy( $source, $destination ) ) {
			throw new RuntimeException( "Could not copy package file: {$source}" );
		}

		return;
	}

	if ( ! is_dir( $source ) ) {
		throw new RuntimeException( "Required package source is missing: {$source}" );
	}

	if ( ! is_dir( $destination ) && ! mkdir( $destination, 0777, true ) && ! is_dir( $destination ) ) {
		throw new RuntimeException( "Could not create package directory: {$destination}" );
	}

	foreach ( new FilesystemIterator( $source, FilesystemIterator::SKIP_DOTS ) as $item ) {
		cart_relay_copy_path( $item->getPathname(), $destination . DIRECTORY_SEPARATOR . $item->getFilename() );
	}
}

/**
 * Runs Composer inside the staged package.
 */
function cart_relay_install_production_dependencies( string $stage ): void {
	$composer_binary = getenv( 'COMPOSER_BINARY' );
	$command         = is_string( $composer_binary ) && is_file( $composer_binary )
		? [ PHP_BINARY, $composer_binary ]
		: [ 'composer' ];
	$command         = [
		...$command,
		'install',
		'--working-dir=' . $stage,
		'--no-dev',
		'--no-interaction',
		'--no-progress',
		'--no-scripts',
		'--prefer-dist',
		'--classmap-authoritative',
	];
	$process  = proc_open( $command, [ STDIN, STDOUT, STDERR ], $pipes );

	if ( ! is_resource( $process ) || 0 !== proc_close( $process ) ) {
		throw new RuntimeException( 'Composer failed to install production dependencies in the package staging directory.' );
	}
}

/**
 * Verifies that packaged JavaScript and translation files remain extractable.
 */
function cart_relay_verify_i18n( string $root, string $stage ): void {
	$translation_process = proc_open(
		[
			'node',
			$root . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'build-translations.mjs',
			'--check',
			'--package=' . $stage,
		],
		[ STDIN, STDOUT, STDERR ],
		$pipes
	);

	if ( ! is_resource( $translation_process ) || 0 !== proc_close( $translation_process ) ) {
		throw new RuntimeException( 'The production package contains missing, incomplete, or stale translation catalogs.' );
	}

	$process = proc_open(
		[ 'node', $root . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'verify-i18n.mjs', '--package=' . $stage ],
		[ STDIN, STDOUT, STDERR ],
		$pipes
	);

	if ( ! is_resource( $process ) || 0 !== proc_close( $process ) ) {
		throw new RuntimeException( 'The production package failed internationalization verification.' );
	}
}

/**
 * Removes development-only directories included by production dependencies.
 *
 * @param string[] $directory_names Directory basenames to remove.
 */
function cart_relay_prune_dependency_directories( string $stage, array $directory_names ): void {
	$vendor = $stage . DIRECTORY_SEPARATOR . 'vendor';

	if ( ! is_dir( $vendor ) ) {
		return;
	}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $vendor, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ( $iterator as $item ) {
		if ( $item->isDir() && in_array( $item->getFilename(), $directory_names, true ) ) {
			cart_relay_remove_tree( $item->getPathname(), $stage );
		}
	}
}

/**
 * Returns sorted package-relative file paths.
 *
 * @return string[]
 */
function cart_relay_package_files( string $stage ): array {
	$files    = [];
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $stage, FilesystemIterator::SKIP_DOTS )
	);

	foreach ( $iterator as $item ) {
		if ( $item->isLink() ) {
			throw new RuntimeException( 'The production package must not contain symbolic links.' );
		}

		if ( $item->isFile() ) {
			$files[] = str_replace( '\\', '/', substr( $item->getPathname(), strlen( $stage ) + 1 ) );
		}
	}

	sort( $files, SORT_STRING );

	return $files;
}

cart_relay_remove_tree( $stage, $build_root );

if ( ! is_dir( $build_root ) && ! mkdir( $build_root, 0777, true ) && ! is_dir( $build_root ) ) {
	throw new RuntimeException( 'Could not create the package build directory.' );
}

foreach ( [ $zip_path, $manifest ] as $generated_file ) {
	if ( is_file( $generated_file ) && ! unlink( $generated_file ) ) {
		throw new RuntimeException( "Could not replace generated file: {$generated_file}" );
	}
}

$runtime_paths = [
	'app',
	'resources/views',
	'dist',
	'languages',
	'cart-relay.php',
	'uninstall.php',
	'readme.txt',
	'LICENSE',
	'THIRD_PARTY_NOTICES.txt',
];

foreach ( $runtime_paths as $relative_path ) {
	cart_relay_copy_path(
		$root . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $relative_path ),
		$stage . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $relative_path )
	);
}

cart_relay_copy_path( $root . DIRECTORY_SEPARATOR . 'composer.json', $stage . DIRECTORY_SEPARATOR . 'composer.json' );
cart_relay_copy_path( $root . DIRECTORY_SEPARATOR . 'composer.lock', $stage . DIRECTORY_SEPARATOR . 'composer.lock' );
cart_relay_install_production_dependencies( $stage );
cart_relay_prune_dependency_directories( $stage, [ '.vscode' ] );

$files     = cart_relay_package_files( $stage );
$forbidden = '#(^|/)(?:node_modules|tests|tools|\.git|\.github|\.idea|\.vscode|\.claude|\.cocoindex_code|\.phpunit\.cache)(?:/|$)|(?:^|/)(?:phpcs\.xml(?:\.dist)?|phpunit\.xml(?:\.dist)?|AGENTS\.md|CLAUDE\.md)$|\.map$|(?:^|/)\.env(?:\.|$)|\.(?:pem|key)$#i';

foreach ( $files as $file ) {
	if ( preg_match( $forbidden, $file ) ) {
		throw new RuntimeException( "Forbidden file detected in production package: {$file}" );
	}
}

if ( ! in_array( 'dist/manifest.json', $files, true ) || ! in_array( 'vendor/autoload.php', $files, true ) ) {
	throw new RuntimeException( 'The production package is missing compiled assets or Composer autoloading.' );
}

if ( ! in_array( 'languages/cart-relay.pot', $files, true ) ) {
	throw new RuntimeException( 'The production package is missing its translation template.' );
}

cart_relay_verify_i18n( $root, $stage );

$source_date_epoch = (int) ( getenv( 'SOURCE_DATE_EPOCH' ) ?: 315532800 );
$zip               = new ZipArchive();

if ( true !== $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
	throw new RuntimeException( 'Could not create cart-relay.zip.' );
}

foreach ( $files as $file ) {
	$archive_name = 'cart-relay/' . $file;
	$source       = $stage . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $file );

	if ( ! $zip->addFile( $source, $archive_name ) ) {
		throw new RuntimeException( "Could not add file to package: {$file}" );
	}

	$zip->setMtimeName( $archive_name, $source_date_epoch );
	$zip->setCompressionName( $archive_name, ZipArchive::CM_DEFLATE, 9 );
}

$zip->close();

$manifest_lines = array_map(
	static fn( string $file ): string => hash_file( 'sha256', $stage . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $file ) ) . "  cart-relay/{$file}",
	$files
);

file_put_contents( $manifest, implode( PHP_EOL, $manifest_lines ) . PHP_EOL );

$verification_zip = new ZipArchive();

if ( true !== $verification_zip->open( $zip_path ) ) {
	throw new RuntimeException( 'Could not reopen the generated package for verification.' );
}

for ( $index = 0; $index < $verification_zip->numFiles; ++$index ) {
	$name = $verification_zip->getNameIndex( $index );

	if ( ! is_string( $name ) || ! str_starts_with( $name, 'cart-relay/' ) ) {
		throw new RuntimeException( 'The ZIP must contain exactly one top-level cart-relay directory.' );
	}
}

$verification_zip->close();

fwrite( STDOUT, sprintf( "Built %s with %d files.%s", $zip_path, count( $files ), PHP_EOL ) );
fwrite( STDOUT, 'SHA-256: ' . hash_file( 'sha256', $zip_path ) . PHP_EOL );
