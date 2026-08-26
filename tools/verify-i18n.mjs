import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import ts from 'typescript';

const TEXT_DOMAIN = 'cart-relay';
const root = path.resolve( path.dirname( fileURLToPath( import.meta.url ) ), '..' );
const packageArgument = process.argv.find( ( argument ) => argument.startsWith( '--package=' ) );
const packageRoot = packageArgument
	? path.resolve( packageArgument.slice( '--package='.length ) )
	: root;

const signatures = {
	__: { domainIndex: 1, messageIndexes: [ 0 ] },
	_x: { domainIndex: 2, messageIndexes: [ 0 ] },
	_n: { domainIndex: 3, messageIndexes: [ 0, 1 ] },
	_nx: { domainIndex: 4, messageIndexes: [ 0, 1 ] },
};

const failures = [];

const fail = ( message ) => {
	failures.push( message );
};

const walk = ( directory, extension ) => {
	if ( ! fs.existsSync( directory ) ) {
		return [];
	}

	return fs.readdirSync( directory, { withFileTypes: true } ).flatMap( ( entry ) => {
		const entryPath = path.join( directory, entry.name );

		if ( entry.isDirectory() ) {
			return walk( entryPath, extension );
		}

		return entry.isFile() && entry.name.endsWith( extension ) ? [ entryPath ] : [];
	} );
};

const literalValue = ( node ) => {
	return node && ( ts.isStringLiteral( node ) || ts.isNoSubstitutionTemplateLiteral( node ) )
		? node.text
		: null;
};

const collectCalls = ( files, scriptKind, enforceDomain ) => {
	const calls = new Map();

	files.forEach( ( file ) => {
		const source = fs.readFileSync( file, 'utf8' );
		const sourceFile = ts.createSourceFile( file, source, ts.ScriptTarget.Latest, true, scriptKind );

		const visit = ( node ) => {
			if ( ts.isCallExpression( node ) && ts.isIdentifier( node.expression ) ) {
				const functionName = node.expression.text;
				const signature = signatures[functionName];

				if ( signature ) {
					const domain = literalValue( node.arguments[signature.domainIndex] );
					const location = sourceFile.getLineAndCharacterOfPosition( node.getStart( sourceFile ) );
					const relativeFile = path.relative( root, file ).replaceAll( '\\', '/' );
					const reference = `${relativeFile}:${location.line + 1}`;

					if ( enforceDomain && domain !== TEXT_DOMAIN ) {
						fail( `${reference} must pass the literal '${TEXT_DOMAIN}' text domain to ${functionName}().` );
					}

					if ( domain === TEXT_DOMAIN ) {
						const messages = signature.messageIndexes.map( ( index ) => literalValue( node.arguments[index] ) );

						if ( messages.some( ( message ) => message === null ) ) {
							fail( `${reference} must use literal gettext source strings.` );
						} else {
							const key = JSON.stringify( [ functionName, ...messages ] );
							calls.set( key, { functionName, messages, reference } );
						}
					}
				}
			}

			ts.forEachChild( node, visit );
		};

		visit( sourceFile );
	} );

	return calls;
};

const pluginFile = path.join( packageRoot, 'cart-relay.php' );

if ( ! fs.existsSync( pluginFile ) ) {
	fail( `Missing plugin entry point: ${pluginFile}` );
} else {
	const pluginHeader = fs.readFileSync( pluginFile, 'utf8' );

	if ( ! /^\s*\*\s+Text Domain:\s+cart-relay\s*$/mu.test( pluginHeader ) ) {
		fail( 'The plugin header must declare Text Domain: cart-relay.' );
	}

	if ( ! /^\s*\*\s+Domain Path:\s+\/languages\s*$/mu.test( pluginHeader ) ) {
		fail( 'The plugin header must declare Domain Path: /languages.' );
	}
}

const sourceFiles = walk( path.join( root, 'resources' ), '.ts' );
const sourceCalls = collectCalls( sourceFiles, ts.ScriptKind.TS, true );

if ( sourceCalls.size === 0 ) {
	fail( 'No TypeScript gettext calls were found.' );
}

const directionalPattern = /\b(?:margin|padding|border)-(?:left|right)\b|\b(?:left|right)\s*:|text-align\s*:\s*(?:left|right)\b/giu;
const directionSensitiveFiles = [
	...walk( path.join( root, 'resources', 'assets' ), '.css' ),
	...walk( path.join( root, 'resources', 'assets' ), '.scss' ),
	...sourceFiles,
];

directionSensitiveFiles.forEach( ( file ) => {
	const source = fs.readFileSync( file, 'utf8' );
	const match = directionalPattern.exec( source );
	directionalPattern.lastIndex = 0;

	if ( match ) {
		const line = source.slice( 0, match.index ).split( /\r?\n/u ).length;
		const relativeFile = path.relative( root, file ).replaceAll( '\\', '/' );
		fail( `${relativeFile}:${line} uses a physical inline direction; use a logical CSS property for RTL support.` );
	}
} );

const manifestPath = path.join( packageRoot, 'dist', 'manifest.json' );
let bundleFiles = [];

if ( ! fs.existsSync( manifestPath ) ) {
	fail( `Missing Vite manifest: ${manifestPath}` );
} else {
	const manifest = JSON.parse( fs.readFileSync( manifestPath, 'utf8' ) );
	bundleFiles = Object.values( manifest )
		.map( ( entry ) => entry.file )
		.filter( ( file ) => typeof file === 'string' && file.endsWith( '.js' ) )
		.map( ( file ) => path.join( packageRoot, 'dist', file ) );

	bundleFiles.filter( ( file ) => ! fs.existsSync( file ) ).forEach( ( file ) => {
		fail( `Manifest JavaScript file does not exist: ${file}` );
	} );
}

const bundleCalls = collectCalls(
	bundleFiles.filter( ( file ) => fs.existsSync( file ) ),
	ts.ScriptKind.JS,
	false
);

sourceCalls.forEach( ( call, key ) => {
	if ( ! bundleCalls.has( key ) ) {
		fail( `${call.reference} is not extractable from the production JavaScript bundle.` );
	}
} );

const potPath = path.join( packageRoot, 'languages', 'cart-relay.pot' );

if ( ! fs.existsSync( potPath ) ) {
	fail( `Missing translation template: ${potPath}` );
} else {
	const pot = fs.readFileSync( potPath, 'utf8' );

	sourceCalls.forEach( ( call ) => {
		call.messages.forEach( ( message, index ) => {
			const keyword = index === 1 ? 'msgid_plural' : 'msgid';
			const declaration = `${keyword} ${JSON.stringify( message )}`;

			if ( ! pot.includes( declaration ) ) {
				fail( `${call.reference} is missing from languages/cart-relay.pot: ${message}` );
			}
		} );
	} );
}

if ( failures.length > 0 ) {
	failures.forEach( ( failure ) => process.stderr.write( `- ${failure}\n` ) );
	process.exit( 1 );
}

process.stdout.write(
	`Verified ${sourceCalls.size} unique JavaScript gettext calls across ${bundleFiles.length} production bundle(s), including POT coverage and RTL-safe source styles.\n`
);
