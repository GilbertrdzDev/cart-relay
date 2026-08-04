<?php

declare( strict_types=1 );

$root    = dirname( __DIR__ );
$targets = [
	$root . DIRECTORY_SEPARATOR . 'app',
	$root . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views',
	$root . DIRECTORY_SEPARATOR . 'tests',
	$root . DIRECTORY_SEPARATOR . 'tools',
	$root . DIRECTORY_SEPARATOR . 'cart-relay.php',
	$root . DIRECTORY_SEPARATOR . 'uninstall.php',
];
$files   = [];

foreach ( $targets as $target ) {
	if ( is_file( $target ) ) {
		$files[] = $target;
		continue;
	}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $target, FilesystemIterator::SKIP_DOTS )
	);

	foreach ( $iterator as $item ) {
		if ( $item->isFile() && 'php' === strtolower( $item->getExtension() ) ) {
			$files[] = $item->getPathname();
		}
	}
}

sort( $files, SORT_STRING );

foreach ( $files as $file ) {
	$process = proc_open( [ PHP_BINARY, '-l', $file ], [ STDIN, STDOUT, STDERR ], $pipes );

	if ( ! is_resource( $process ) || 0 !== proc_close( $process ) ) {
		fwrite( STDERR, "PHP lint failed for {$file}." . PHP_EOL );
		exit( 1 );
	}
}

fwrite( STDOUT, sprintf( "PHP lint passed for %d files.%s", count( $files ), PHP_EOL ) );
