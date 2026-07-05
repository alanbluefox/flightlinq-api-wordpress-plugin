<?php
/**
 * FlightLinq API cache.
 *
 * @package FlightLinq_API
 * @since 1.0.0
 */

namespace FlightLinq_API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FlightLinq_API\Cache class.
 *
 * Handles caching of FlightLinq API responses using transients.
 *
 * @since 1.0.0
 */
class Cache {

	/**
	 * Unique class instance.
	 *
	 * @since 1.0.0
	 *
	 * @var Cache
	 */
	private static $instance = null;

	/**
	 * Cache key prefix.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $prefix = 'flightlinq_';

	/**
	 * Private constructor to enforce singleton usage.
	 *
	 * @since 1.0.0
	 * @since 1.0.1 Removed wp_ajax_nopriv to restrict access to logged-in administrators.
	 * @date 2026-05-22
	 */
	private function __construct() {
		add_action( 'wp_ajax_flightlinq_clear_cache', array( $this, 'ajax_clear_cache' ) );
	}

	/**
	 * Returns the unique class instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Cache
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Generates a unique cache key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $endpoint The endpoint.
	 * @param array  $args     The request arguments.
	 *
	 * @return string
	 */
	private function get_cache_key( $endpoint, $args = array() ) {
		$api_key_hash = hash( 'sha256', (string) get_option( 'flightlinq_api_key', '' ) );
		$key          = $this->prefix . md5( $api_key_hash . $endpoint . serialize( $args ) );
		return $key;
	}

	/**
	 * Retrieves data from the cache.
	 *
	 * @since 1.0.0
	 *
	 * @param string $endpoint The endpoint.
	 * @param array  $args     The request arguments.
	 *
	 * @return mixed|false Cached data, or false if expired or unavailable.
	 */
	public function get( $endpoint, $args = array() ) {
		if ( ! $this->is_cache_enabled() ) {
			return false;
		}

		$key = $this->get_cache_key( $endpoint, $args );
		return get_transient( $key );
	}

	/**
	 * Stores data in the cache.
	 *
	 * @since 1.0.0
	 * @since 1.0.1 Never stores a WP_Error object in the cache.
	 * @date 2026-05-22
	 *
	 * @param string $endpoint The endpoint.
	 * @param array  $args     The request arguments.
	 * @param mixed  $data     The data to cache.
	 *
	 * @return bool
	 */
	public function set( $endpoint, $args, $data ) {
		if ( ! $this->is_cache_enabled() ) {
			return false;
		}

		// Do not store errors in the cache.
		if ( is_wp_error( $data ) ) {
			return false;
		}

		$key          = $this->get_cache_key( $endpoint, $args );
		$duration     = $this->get_cache_duration();
		$duration_sec = $duration * MINUTE_IN_SECONDS;

		return set_transient( $key, $data, $duration_sec );
	}

	/**
	 * Deletes data from the cache.
	 *
	 * @since 1.0.0
	 *
	 * @param string $endpoint The endpoint.
	 * @param array  $args     The request arguments.
	 *
	 * @return bool
	 */
	public function delete( $endpoint, $args = array() ) {
		$key = $this->get_cache_key( $endpoint, $args );
		return delete_transient( $key );
	}

	/**
	 * Clears the entire FlightLinq cache.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function clear_all() {
		global $wpdb;

		// Delete all transients using our prefix.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_' . $this->prefix ) . '%'
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_timeout_' . $this->prefix ) . '%'
			)
		);
	}

	/**
	 * Checks whether caching is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	private function is_cache_enabled() {
		return (bool) get_option( 'flightlinq_enable_cache', true );
	}

	/**
	 * Retrieves the cache duration in minutes.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	private function get_cache_duration() {
		return (int) get_option( 'flightlinq_cache_duration', 30 );
	}

	/**
	 * AJAX handler used to clear the cache.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_clear_cache() {
		check_ajax_referer( 'flightlinq-clear-cache', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'flightlinq-api' ) );
		}

		$this->clear_all();

		wp_send_json_success( __( 'Cache cleared successfully.', 'flightlinq-api' ) );
	}
}
