/**
 * External dependencies
 */
const _ = require( 'lodash' ); // eslint-disable-line lodash/import-scope
const path = require( 'path' );
const getBaseWebpackConfig = require( '@automattic/calypso-build/webpack.config.js' );

const isDevelopment = process.env.NODE_ENV !== 'production';

const webpackConfig = getBaseWebpackConfig(
	{ WP: true },
	{
		entry: {
			admin: path.join( __dirname, '_inc', 'client', 'admin.js' ),
			static: path.join( __dirname, '_inc', 'client', 'static.jsx' ),
		},
		'output-filename': '[name].js',
		'output-path': path.join( __dirname, '_inc', 'build' ),
	}
);

module.exports = _.merge( {}, webpackConfig, {
	resolve: {
		modules: [ 'node_modules', path.resolve( __dirname, '_inc/client' ) ],
	},
	node: {
		fs: 'empty',
		process: true,
	},
	devtool: isDevelopment ? 'source-map' : false,
} );
