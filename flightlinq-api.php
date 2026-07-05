<?php
/**
 * Plugin Name: FlightLinq API
 * Plugin URI: https://airbluefox.fr
 * Description: FlightLinq API integration for WordPress. Provides access to your airline data (pilots, routes, fleet, recent flights) through PHP functions in the theme and shortcodes.
 * Version: 1.8.2
 * Author: AirBlueFox
 * Author URI: https://airbluefox.fr
 * Text Domain: flightlinq-api
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package FlightLinq_API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'FLIGHTLINQ_API_VERSION', '1.8.2' );
define( 'FLIGHTLINQ_API_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FLIGHTLINQ_API_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FLIGHTLINQ_API_BASE_URL', 'https://api.flightlinq.com/api/v1/external' );

/**
 * Autoloader for plugin classes.
 *
 * @since 1.0.0
 *
 * @param string $class Name of the class to load.
 *
 * @return void
 */
function flightlinq_api_autoloader( $class ) {
	// Check whether the class belongs to our namespace.
	if ( strpos( $class, 'FlightLinq_API' ) !== 0 ) {
		return;
	}

	// Convert the namespace into a file path.
	$class_file = str_replace( 'FlightLinq_API\\', '', $class );
	$class_file = str_replace( '_', '-', strtolower( $class_file ) );
	$class_file = FLIGHTLINQ_API_PLUGIN_DIR . 'includes/class-' . $class_file . '.php';

	if ( file_exists( $class_file ) ) {
		require_once $class_file;
	}
}
spl_autoload_register( 'flightlinq_api_autoloader' );

/**
 * Initializes the plugin.
 *
 * @since 1.0.0
 *
 * @return void
 */
function flightlinq_api_init() {
	// Load the translation file.
	load_plugin_textdomain( 'flightlinq-api', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	// Initialize the admin area.
	if ( is_admin() ) {
		FlightLinq_API\Admin::get_instance();
	}

	// Initialize the API client.
	FlightLinq_API\Client::get_instance();

	// Initialize the cache.
	FlightLinq_API\Cache::get_instance();

	// Initialize REST endpoints.
	FlightLinq_API\REST::get_instance();

	// Initialize shortcodes (re-enabled in v1.4.0 with the new generation).
	FlightLinq_API\Shortcodes::get_instance();

	/**
	 * Loads template functions for theme integration.
	 *
	 * This file is loaded after class initialization to ensure
	 * that the API client is available.
	 *
	 * @since 1.1.0
	 * @date 2026-05-23
	 */
	require_once FLIGHTLINQ_API_PLUGIN_DIR . 'includes/functions-template.php';
}
add_action( 'plugins_loaded', 'flightlinq_api_init' );

/**
 * Plugin activation.
 *
 * @since 1.0.0
 * @since 1.0.3 Automatically clears the cache during updates.
 * @date 2026-05-23
 *
 * @return void
 */
function flightlinq_api_activate() {
	// Activation actions, such as flushing rewrite rules.
	flush_rewrite_rules();

	// Clear the FlightLinq cache to avoid stale data after a URL change.
	$cache = FlightLinq_API\Cache::get_instance();
	$cache->clear_all();
}
register_activation_hook( __FILE__, 'flightlinq_api_activate' );

/**
 * Plugin deactivation.
 *
 * @since 1.0.0
 *
 * @return void
 */
function flightlinq_api_deactivate() {
	// Deactivation actions, such as flushing rewrite rules.
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'flightlinq_api_deactivate' );
