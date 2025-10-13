/**
 * External dependencies
 */
const fs = require( 'fs' );
const path = require( 'path' );
const CssMinimizerPlugin = require( 'css-minimizer-webpack-plugin' );
const RemoveEmptyScriptsPlugin = require( 'webpack-remove-empty-scripts' );

/**
 * WordPress dependencies
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

// Extend the default config.
const sharedConfig = {
	...defaultConfig,
	output: {
		path: path.resolve( process.cwd(), 'assets', 'build', 'js' ),
		filename: '[name].js',
		chunkFilename: '[name].js',
	},
	plugins: [
		...defaultConfig.plugins
			.map(
				( plugin ) => {
					if ( plugin.constructor.name === 'MiniCssExtractPlugin' ) {
						plugin.options.filename = '../css/[name].css';
					}
					return plugin;
				},
			),
		new RemoveEmptyScriptsPlugin(),
	],
	optimization: {
		...defaultConfig.optimization,
		splitChunks: {
			...defaultConfig.optimization.splitChunks,
		},
		minimizer: defaultConfig.optimization.minimizer.concat( [ new CssMinimizerPlugin() ] ),
	},
};

// Generate a webpack config which includes setup for CSS extraction.
// Look for css/scss files and extract them into a build/css directory.
const styles = {
	...sharedConfig,
	entry: () => {
		const entries = {};

		const dir = './assets/src/css';
		fs.readdirSync( dir ).forEach( ( fileName ) => {
			const fullPath = `${ dir }/${ fileName }`;
			if ( ! fs.lstatSync( fullPath ).isDirectory() ) {
				entries[ fileName.replace( /\.[^/.]+$/, '' ) ] = fullPath;
			}
		} );

		return entries;
	},
	module: {
		...sharedConfig.module,
	},
	plugins: [
		...sharedConfig.plugins.filter(
			( plugin ) => plugin.constructor.name !== 'DependencyExtractionWebpackPlugin',
		),
	],

};

const scripts = {
	...sharedConfig,
	entry: {
		main: path.resolve( process.cwd(), 'assets', 'src', 'js', 'main.js' ),
		editor: path.resolve( process.cwd(), 'assets', 'src', 'js', 'editor.js' ),
		admin: path.resolve( process.cwd(), 'assets', 'src', 'js', 'admin.js' ),
		'templates-library': path.resolve( process.cwd(), 'assets', 'src', 'admin', 'templates', 'index.js' ),
		'patterns-library': path.resolve( process.cwd(), 'assets', 'src', 'admin', 'patterns', 'index.js' ),
		settings: path.resolve( process.cwd(), 'assets', 'src', 'admin', 'settings', 'index.js' ),
		plugin: path.resolve( process.cwd(), 'assets', 'src', 'admin', 'plugin', 'index.js' ),
	},
};

module.exports = [
	scripts,
	styles, // Do not remove this.
];
