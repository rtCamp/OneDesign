#!/usr/bin/env node
/**
 * Scaffold audit for the OneDesign AI-optimized best-practice baseline.
 *
 * Validates that the current repo has each scaffold pillar correctly in place
 * and prints a per-pillar PASS / MISSING / DRIFTED report. Doubles as the
 * gap report for the `replicate-ai-scaffold` skill.
 *
 * Zero runtime dependencies — safe to run before `npm install`.
 *
 * Usage:
 *   node bin/validate-scaffold.mjs [--slug <plugin-slug>] [--json] [--remaining]
 *
 * --remaining prints just the ordered list of not-yet-PASS pillars (the work
 * left), so the skill's assess step is a single command.
 *
 * Exit code: 0 if every pillar PASSes, 1 otherwise.
 */

import { existsSync, readFileSync, readdirSync, statSync } from 'node:fs';
import { join } from 'node:path';

const root = process.cwd();
const args = process.argv.slice( 2 );
const asJson = args.includes( '--json' );
const remainingOnly = args.includes( '--remaining' );
const slugArg = ( () => {
	const i = args.indexOf( '--slug' );
	return i !== -1 && args[ i + 1 ] ? args[ i + 1 ] : null;
} )();

/** Resolve the plugin slug from --slug, else package.json `name`. */
function resolveSlug() {
	if ( slugArg ) {
		return slugArg;
	}
	const pkg = readJson( 'package.json' );
	return pkg?.name ?? 'plugin';
}

function abs( rel ) {
	return join( root, rel );
}
function exists( rel ) {
	return existsSync( abs( rel ) );
}
function read( rel ) {
	try {
		return readFileSync( abs( rel ), 'utf8' );
	} catch {
		return null;
	}
}
function readJson( rel ) {
	const raw = read( rel );
	if ( raw === null ) {
		return null;
	}
	try {
		return JSON.parse( raw );
	} catch {
		return undefined; // present but unparseable
	}
}

/** Recursively collect files under `rel` matching `test(name)`. */
function walk( rel, test, acc = [] ) {
	const dir = abs( rel );
	if ( ! existsSync( dir ) || ! statSync( dir ).isDirectory() ) {
		return acc;
	}
	for ( const entry of readdirSync( dir, { withFileTypes: true } ) ) {
		const childRel = join( rel, entry.name );
		if ( entry.isDirectory() ) {
			if ( entry.name === 'node_modules' || entry.name === 'build' ) {
				continue;
			}
			walk( childRel, test, acc );
		} else if ( test( entry.name ) ) {
			acc.push( childRel );
		}
	}
	return acc;
}

const slug = resolveSlug();

/**
 * A pillar is a named group of checks. Each check returns:
 *   { ok: boolean, missing?: boolean, detail: string }
 * `missing: true` means a required file is absent (→ MISSING);
 * a failing check without `missing` means present-but-wrong (→ DRIFTED).
 */
const pillars = [];
function pillar( name, checks ) {
	pillars.push( { name, checks } );
}
function ok( detail ) {
	return { ok: true, detail };
}
function missing( detail ) {
	return { ok: false, missing: true, detail };
}
function drift( detail ) {
	return { ok: false, missing: false, detail };
}

const WORKFLOWS = '.github/workflows';
const REUSABLE = [
	'reusable-build.yml',
	'reusable-e2e.yml',
	'reusable-jest.yml',
	'reusable-lint-css-js.yml',
	'reusable-phpcs.yml',
	'reusable-phpstan.yml',
	'reusable-phpunit.yml',
	'reusable-wp-playground-pr-preview.yml',
];

// ── Pillar 1: TypeScript + build ────────────────────────────────────────────
pillar( 'TypeScript + build', () => {
	const out = [];
	out.push(
		exists( 'tsconfig.base.json' )
			? ok( 'tsconfig.base.json present' )
			: missing( 'tsconfig.base.json' )
	);
	// tsconfig files allow // comments, so match on raw text rather than JSON.parse.
	const baseRaw = read( 'tsconfig.base.json' ) ?? '';
	out.push(
		/"strict"\s*:\s*true/.test( baseRaw )
			? ok( 'strict: true' )
			: drift( 'tsconfig.base.json compilerOptions.strict must be true' )
	);
	out.push(
		exists( 'tsconfig.json' )
			? ok( 'tsconfig.json present' )
			: missing( 'tsconfig.json' )
	);
	out.push(
		exists( 'eslint.config.mjs' )
			? ok( 'eslint.config.mjs present' )
			: missing( 'eslint.config.mjs' )
	);
	out.push(
		exists( '.nvmrc' ) ? ok( '.nvmrc present' ) : missing( '.nvmrc' )
	);
	const pkg = readJson( 'package.json' );
	const scripts = pkg?.scripts ?? {};
	for ( const s of [ 'build:prod', 'build:js', 'lint:js:types' ] ) {
		out.push(
			scripts[ s ]
				? ok( `package.json script "${ s }"` )
				: drift( `package.json missing script "${ s }"` )
		);
	}
	if ( exists( 'assets/src' ) ) {
		const legacy = walk(
			'assets/src',
			( n ) => /\.(js|jsx)$/.test( n ) && ! n.endsWith( '.min.js' )
		);
		out.push(
			legacy.length === 0
				? ok( 'no legacy .js/.jsx under assets/src' )
				: drift(
						`legacy JS under assets/src (migrate to TS): ${ legacy
							.slice( 0, 5 )
							.join( ', ' ) }${ legacy.length > 5 ? '…' : '' }`
				  )
		);
	}
	return out;
} );

// ── Pillar 2: Jest ───────────────────────────────────────────────────────────
pillar( 'Jest unit tests', () => {
	const out = [];
	out.push(
		exists( 'jest.config.js' )
			? ok( 'jest.config.js present' )
			: missing( 'jest.config.js' )
	);
	const scripts = readJson( 'package.json' )?.scripts ?? {};
	for ( const s of [ 'test:js', 'test:js:coverage' ] ) {
		out.push(
			scripts[ s ]
				? ok( `package.json script "${ s }"` )
				: drift( `package.json missing script "${ s }"` )
		);
	}
	out.push(
		exists( 'tests/js' )
			? ok( 'tests/js/ present' )
			: missing( 'tests/js/ directory' )
	);
	return out;
} );

// ── Pillar 3: Playwright E2E ─────────────────────────────────────────────────
pillar( 'Playwright E2E', () => {
	const out = [];
	out.push(
		exists( 'playwright.config.ts' )
			? ok( 'playwright.config.ts present' )
			: missing( 'playwright.config.ts' )
	);
	const scripts = readJson( 'package.json' )?.scripts ?? {};
	for ( const s of [ 'test:e2e', 'wp-env:test' ] ) {
		out.push(
			scripts[ s ]
				? ok( `package.json script "${ s }"` )
				: drift( `package.json missing script "${ s }"` )
		);
	}
	out.push(
		exists( '.wp-env.test.json' )
			? ok( '.wp-env.test.json present' )
			: missing( '.wp-env.test.json' )
	);
	const specs = walk( 'tests/e2e', ( n ) => n.endsWith( '.spec.ts' ) );
	out.push(
		specs.length > 0
			? ok( `${ specs.length } E2E spec(s)` )
			: missing( 'no tests/e2e/**/*.spec.ts' )
	);
	return out;
} );

// ── Pillar 4: Reusable CI ────────────────────────────────────────────────────
pillar( 'Reusable CI', () => {
	const out = [];
	out.push(
		exists( `${ WORKFLOWS }/ci.yml` )
			? ok( 'ci.yml present' )
			: missing( 'ci.yml' )
	);
	const ci = read( `${ WORKFLOWS }/ci.yml` ) ?? '';
	out.push(
		/^permissions:/m.test( ci )
			? ok( 'ci.yml declares permissions' )
			: drift( 'ci.yml missing top-level permissions' )
	);
	for ( const w of REUSABLE ) {
		out.push(
			exists( `${ WORKFLOWS }/${ w }` )
				? ok( w )
				: missing( `${ WORKFLOWS }/${ w }` )
		);
	}
	// Third-party actions must be SHA-pinned.
	const unpinned = [];
	for ( const wf of walk( WORKFLOWS, ( n ) => n.endsWith( '.yml' ) ) ) {
		const body = read( wf ) ?? '';
		// \buses: avoids matching inside words like "statuses:".
		for ( const m of body.matchAll( /\buses:\s*([^\s#]+)/g ) ) {
			const ref = m[ 1 ];
			if ( ref.startsWith( './' ) ) {
				continue; // local reusable workflow
			}
			const at = ref.lastIndexOf( '@' );
			const pin = at === -1 ? '' : ref.slice( at + 1 );
			if ( ! /^[0-9a-f]{40}$/.test( pin ) ) {
				unpinned.push(
					`${ wf.replace( WORKFLOWS + '/', '' ) }: ${ ref }`
				);
			}
		}
	}
	out.push(
		unpinned.length === 0
			? ok( 'all third-party actions SHA-pinned' )
			: drift(
					`unpinned action(s): ${ unpinned
						.slice( 0, 3 )
						.join( '; ' ) }${ unpinned.length > 3 ? '…' : '' }`
			  )
	);
	return out;
} );

// ── Pillar 5: GitHub flow + releases ─────────────────────────────────────────
pillar( 'GitHub flow + releases', () => {
	const out = [];
	const cfg = readJson( 'release-please-config.json' );
	out.push(
		cfg
			? ok( 'release-please-config.json present' )
			: missing( 'release-please-config.json' )
	);
	out.push(
		cfg?.packages?.[ '.' ]?.[ 'release-type' ] === 'php'
			? ok( 'release-type: php' )
			: drift( 'release-please-config.json release-type must be "php"' )
	);
	const manifest = readJson( '.release-please-manifest.json' );
	out.push(
		manifest
			? ok( '.release-please-manifest.json present' )
			: missing( '.release-please-manifest.json' )
	);
	out.push(
		typeof manifest?.[ '.' ] === 'string' &&
			/^\d+\.\d+\.\d+/.test( manifest[ '.' ] )
			? ok( `manifest version ${ manifest[ '.' ] }` )
			: drift( 'manifest "." must be a semver version string' )
	);
	out.push(
		exists( `${ WORKFLOWS }/release.yml` )
			? ok( 'release.yml present' )
			: missing( 'release.yml' )
	);
	out.push(
		exists( `${ WORKFLOWS }/pr-title.yml` )
			? ok( 'pr-title.yml present' )
			: missing( 'pr-title.yml' )
	);
	return out;
} );

// ── Pillar 6: Playground previews + wp-env ───────────────────────────────────
pillar( 'Playground previews', () => {
	const out = [];
	out.push(
		exists( `${ WORKFLOWS }/reusable-wp-playground-pr-preview.yml` )
			? ok( 'preview workflow present' )
			: missing( 'reusable-wp-playground-pr-preview.yml' )
	);
	const bp = readJson( 'blueprint.json' );
	out.push(
		bp ? ok( 'blueprint.json present' ) : missing( 'blueprint.json' )
	);
	const install = Array.isArray( bp?.steps )
		? bp.steps.find( ( s ) => s.step === 'installPlugin' )
		: null;
	if ( ! install ) {
		out.push( drift( 'blueprint.json has no installPlugin step' ) );
	} else {
		const url = install.pluginData?.url ?? '';
		out.push(
			url.endsWith( `${ slug }.zip` )
				? ok( `blueprint installs ${ slug }.zip` )
				: drift(
						`blueprint installPlugin url should end with "${ slug }.zip" (got "${ url }")`
				  )
		);
	}
	for ( const f of [ '.wp-env.json', '.wp-env.child.json' ] ) {
		out.push( exists( f ) ? ok( `${ f } present` ) : missing( f ) );
	}
	// The classic trap: multisite must be a real boolean, not "false"/"flase".
	if ( ! exists( '.wp-env.override.json' ) ) {
		out.push( missing( '.wp-env.override.json' ) );
	} else {
		const ov = readJson( '.wp-env.override.json' );
		if ( ov === undefined ) {
			out.push( drift( '.wp-env.override.json is not valid JSON' ) );
		} else if (
			'multisite' in ( ov ?? {} ) &&
			typeof ov.multisite !== 'boolean'
		) {
			out.push(
				drift(
					`.wp-env.override.json "multisite" must be a boolean, got ${ JSON.stringify(
						ov.multisite
					) }`
				)
			);
		} else {
			out.push( ok( '.wp-env.override.json multisite is boolean' ) );
		}
	}
	return out;
} );

// ── Pillar 7: Dependabot + hooks + codecov ───────────────────────────────────
pillar( 'Dependabot + hooks + codecov', () => {
	const out = [];
	const db = read( '.github/dependabot.yml' );
	if ( db === null ) {
		out.push( missing( '.github/dependabot.yml' ) );
	} else {
		out.push( ok( 'dependabot.yml present' ) );
		for ( const eco of [ 'github-actions', 'composer', 'npm' ] ) {
			out.push(
				db.includes( eco )
					? ok( `dependabot ecosystem: ${ eco }` )
					: drift( `dependabot.yml missing ${ eco } ecosystem` )
			);
		}
		out.push(
			db.includes( 'groups:' )
				? ok( 'dependabot groups configured' )
				: drift( 'dependabot.yml has no groups' )
		);
	}
	out.push(
		exists( '.github/.codecov.yml' )
			? ok( '.codecov.yml present' )
			: missing( '.github/.codecov.yml' )
	);
	out.push(
		exists( '.lefthook.yml' )
			? ok( '.lefthook.yml present' )
			: missing( '.lefthook.yml' )
	);
	out.push(
		exists( '.lintstagedrc.mjs' )
			? ok( '.lintstagedrc.mjs present' )
			: missing( '.lintstagedrc.mjs' )
	);
	const scripts = readJson( 'package.json' )?.scripts ?? {};
	for ( const s of [ 'prepare', 'validate:scaffold' ] ) {
		out.push(
			scripts[ s ]
				? ok( `package.json script "${ s }"` )
				: drift( `package.json missing script "${ s }"` )
		);
	}
	return out;
} );

// ── Run ──────────────────────────────────────────────────────────────────────
const results = pillars.map( ( p ) => {
	const checks = p.checks();
	let status = 'PASS';
	if ( checks.some( ( c ) => ! c.ok && c.missing ) ) {
		status = 'MISSING';
	}
	if ( checks.some( ( c ) => ! c.ok && ! c.missing ) ) {
		status = status === 'MISSING' ? 'MISSING' : 'DRIFTED';
	}
	return { pillar: p.name, status, checks };
} );

const allPass = results.every( ( r ) => r.status === 'PASS' );

if ( asJson ) {
	process.stdout.write(
		JSON.stringify( { slug, allPass, results }, null, 2 ) + '\n'
	);
	process.exit( allPass ? 0 : 1 );
}

// --remaining: just the ordered pillars still needing work (the resume list).
if ( remainingOnly ) {
	const remaining = results.filter( ( r ) => r.status !== 'PASS' );
	if ( remaining.length === 0 ) {
		console.log( 'All pillars PASS — nothing remaining.' );
	} else {
		for ( const r of remaining ) {
			const n = results.indexOf( r ) + 1; // 1-based pillar ordinal
			console.log(
				`${ n }. ${ r.status.padEnd( 7 ) } ${ r.pillar }`
			);
		}
	}
	process.exit( allPass ? 0 : 1 );
}

const ICON = { PASS: '✓', DRIFTED: '~', MISSING: '✗' };
console.log( `\nScaffold audit — slug "${ slug }"\n` );
for ( const r of results ) {
	console.log(
		`${ ICON[ r.status ] } ${ r.status.padEnd( 8 ) } ${ r.pillar }`
	);
	if ( r.status !== 'PASS' ) {
		for ( const c of r.checks.filter( ( c ) => ! c.ok ) ) {
			console.log( `      - ${ c.detail }` );
		}
	}
}
console.log(
	`\n${
		allPass
			? '✓ All pillars PASS.'
			: '✗ Some pillars need work (see above).'
	}\n`
);
process.exit( allPass ? 0 : 1 );
