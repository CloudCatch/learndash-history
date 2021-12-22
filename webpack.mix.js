/**
 * Laravel Mix configuration file.
 *
 * Laravel Mix is a layer built on top of Webpack that simplifies much of the
 * complexity of building out a Webpack configuration file. Use this file
 * to configure how your assets are handled in the build process.
 *
 * @link https://laravel.com/docs/5.8/mix
 */

// Import required packages.
const mix = require("laravel-mix");
const CopyWebpackPlugin = require("copy-webpack-plugin");
const ImageminPlugin = require('imagemin-webpack-plugin').default;
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const path = require('path');

require("laravel-mix-polyfill");
require("laravel-mix-svg-sprite");

/*
 * Disable all notifications.
 */

mix.disableNotifications();

/*
 * -----------------------------------------------------------------------------
 * Build Process
 * -----------------------------------------------------------------------------
 * The section below handles processing, compiling, transpiling, and combining
 * all of the theme's assets into their final location. This is the meat of the
 * build process.
 * -----------------------------------------------------------------------------
 */

/*
 * Sets the development path to assets. By default, this is the `/resources`
 * folder in the theme.
 */
const devPath = "resources";
const distPath = "assets";

/*
 * Sets the path to the generated assets. By default, this is the root folder in
 * the theme. If doing something custom, make sure to change this everywhere.
 */
mix.setPublicPath("./");

/*
 * Set Laravel Mix options.
 *
 * @link https://laravel.com/docs/5.6/mix#postcss
 * @link https://laravel.com/docs/5.6/mix#url-processing
 */
mix.options({
  postCss: [require("postcss-preset-env")()],
  processCssUrls: false,
});

/*
 * Versioning and cache busting. Append a unique hash for production assets. If
 * you only want versioned assets in production, do a conditional check for
 * `mix.inProduction()`.
 *
 * @link https://laravel.com/docs/5.6/mix#versioning-and-cache-busting
 */
mix.version();

/*
 * Compile JavaScript.
 *
 * @link https://laravel.com/docs/5.6/mix#working-with-scripts
 */
mix.js(`${devPath}/js/admin.js`, `${distPath}/js`).react();

/*
 * Compile CSS. Mix supports Sass, Less, Stylus, and plain CSS, and has functions
 * for each of them.
 *
 * @link https://laravel.com/docs/5.6/mix#working-with-stylesheets
 * @link https://laravel.com/docs/5.6/mix#sass
 * @link https://github.com/sass/node-sass#options
 */

// Sass configuration.
var sassConfig = {
	outputStyle: "compressed",
	indentType : "tab",
	indentWidth: 1,
};

// Compile SASS/CSS.
mix
	.sass( `${devPath}/scss/admin.scss`, `${distPath}/css` )
	.options( {
		sassOptions: sassConfig,
		postCss    : [
			require( "cssnano" )( {
				preset: [
					"default",
					{
						discardComments: {
							removeAll: true,
						},
					},
				],
			} ),
		],
	} )
	.sass(
		`${devPath}/scss/frontend.scss`,
		`${distPath}/css`
	)
	.options( {
		sassOptions: sassConfig,
		postCss    : [
			require( "cssnano" )( {
				preset: [
					"default",
					{
						discardComments: {
							removeAll: true,
						},
					},
				],
			} ),
		],
	} );

/*
 * Add custom Webpack configuration.
 *
 * Laravel Mix doesn't currently minimize images while using its `.copy()`
 * function, so we're using the `CopyWebpackPlugin` for processing and copying
 * images into the distribution folder.
 *
 * @link https://laravel.com/docs/5.6/mix#custom-webpack-configuration
 * @link https://webpack.js.org/configuration/
 */
mix.webpackConfig({
  stats: "minimal",
  performance: { hints: false },
  externals: { jquery: "jQuery" },
  plugins: [
    // @link https://github.com/webpack-contrib/copy-webpack-plugin
    new CopyWebpackPlugin( {
		patterns: [ 
			{
				from            : `${devPath}/fonts`,
				to              : `${distPath}/fonts`, 
				noErrorOnMissing: true,
				globOptions: {
					ignore: [
						'**/selection.json'
					]
				}
			},
			{
				from: `${devPath}/svg`, 
				to: `${distPath}/svg`,
				noErrorOnMissing: true
			},
		]
	} ),
	// @link https://github.com/Klathmon/imagemin-webpack-plugin
	new ImageminPlugin({
		test: /\.(jpe?g|png|gif|svg)$/i,
		disable: process.env.NODE_ENV !== 'production',
		svgo: {
			plugins: [
				{cleanupIDs: false},
				{removeViewBox: false},
				{removeUnknownsAndDefaults: false}
			]
		}
	})
  ]
});

if (process.env.sync) {
  /*
   * Monitor files for changes and inject your changes into the browser.
   *
   * @link https://laravel.com/docs/5.6/mix#browsersync-reloading
   */
  mix.browserSync({
    notify: false,
    proxy: process.env.MIX_PROXY,
    host: process.env.MIX_HOST,
    open: "external",
    port: process.env.MIX_PORT,
    https: {
      key: process.env.MIX_KEY,
      cert: process.env.MIX_CRT,
    },
    files: [`${devPath}/**/*`],
  });
}
