const { CleanWebpackPlugin } = require( 'clean-webpack-plugin' );
let defaultConfig = require('./node_modules/@wordpress/scripts/config/webpack.config.js');
const path = require( 'path' );

// Ensure CleanWebpackPlugin doesn't remove composer build dir from php-scoper
let plugins = defaultConfig.plugins;
for (let i in plugins) {
	if (plugins[i] instanceof CleanWebpackPlugin) {
		plugins[i] = new CleanWebpackPlugin({
			cleanAfterEveryBuildPatterns: ['!fonts/**', '!images/**', '!composer/**'],
			cleanOnceBeforeBuildPatterns: ['**/*', '!composer/**'],
		});
	}
}
defaultConfig.plugins = plugins;

module.exports = {
	...defaultConfig,
	entry: {
		backend: path.resolve( process.cwd(), 'src', 'backend.js' ),
		frontend: path.resolve( process.cwd(), 'src', 'frontend.js' ),
	},
};