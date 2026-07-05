<?php
/**
 * FlightLinq API client.
 *
 * @package FlightLinq_API
 * @since 1.0.0
 */

namespace FlightLinq_API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FlightLinq_API\Client class.
 *
 * Handles requests to the FlightLinq API.
 *
 * @since 1.0.0
 */
class Client {

	/**
	 * Unique class instance.
	 *
	 * @since 1.0.0
	 *
	 * @var Client
	 */
	private static $instance = null;

	/**
	 * FlightLinq API key.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $api_key = '';

	/**
	 * Private constructor to enforce singleton usage.
	 *
	 * @since 1.0.0
	 * @since 1.0.2 Added trim() to the API key.
	 * @date 2026-05-22
	 */
	private function __construct() {
		$api_key = get_option( 'flightlinq_api_key', '' );
		$this->api_key = trim( $api_key );
	}

	/**
	 * Returns the unique class instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Client
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Updates the API key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_key The API key.
	 *
	 * @return void
	 */
	public function set_api_key( $api_key ) {
		$this->api_key = sanitize_text_field( $api_key );
	}

	/**
	 * Sanitizes a response excerpt for safe display.
	 *
	 * @since 1.0.1
	 * @date 2026-05-22
	 *
	 * @param string $content The content to sanitize.
	 * @param int    $max_length Maximum length. Default 500.
	 *
	 * @return string The sanitized excerpt.
	 */
	private function sanitize_response_excerpt( $content, $max_length = 500 ) {
		// Remove HTML tags.
		$cleaned = wp_strip_all_tags( $content );
		// Replace multiple whitespace characters with spaces.
		$cleaned = preg_replace( '/\s+/', ' ', $cleaned );
		// Truncate to the maximum length.
		$cleaned = substr( $cleaned, 0, $max_length );
		// Trim whitespace.
		$cleaned = trim( $cleaned );

		return $cleaned;
	}

	/**
	 * Performs a request to the FlightLinq API with detailed diagnostics.
	 *
	 * @since 1.0.0
	 * @since 1.0.1 Improved JSON diagnostics and added detailed logs.
	 * @since 1.0.2 Uses lowercase x-api-key and adds User-Agent.
	 * @date 2026-05-22
	 *
	 * @param string $endpoint The endpoint, for example 'airline' or 'pilots'.
	 * @param array  $args     The request arguments as query parameters.
	 *
	 * @return array|\WP_Error Decoded data or an error.
	 */
	private function request( $endpoint, $args = array() ) {
		if ( empty( $this->api_key ) ) {
			return new \WP_Error( 'no_api_key', __( 'FlightLinq API key is missing or empty.', 'flightlinq-api' ) );
		}

		$url = FLIGHTLINQ_API_BASE_URL . '/' . ltrim( $endpoint, '/' );

		if ( ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		$response = wp_remote_get(
			$url,
			array(
				'headers' => array(
					'Accept'     => 'application/json',
					'x-api-key'  => $this->api_key,
					'User-Agent' => 'WordPress FlightLinq API Plugin/' . FLIGHTLINQ_API_VERSION,
				),
				'timeout'  => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body         = wp_remote_retrieve_body( $response );
		$code         = wp_remote_retrieve_response_code( $response );
		$message      = wp_remote_retrieve_response_message( $response );
		$headers      = wp_remote_retrieve_headers( $response );
		$content_type = isset( $headers['content-type'] ) ? $headers['content-type'] : '';

		// Check whether the response body is empty.
		if ( empty( $body ) ) {
			$error_msg = sprintf(
				/* translators: 1: HTTP code, 2: URL */
				__( 'Empty response from FlightLinq. HTTP %1$d, URL: %2$s', 'flightlinq-api' ),
				$code,
				$url
			);
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'FlightLinq API: ' . $error_msg );
			}
			return new \WP_Error( 'empty_response', $error_msg );
		}

		// Check HTTP error codes.
		if ( 401 === $code ) {
			return new \WP_Error( 'unauthorized', __( 'Invalid or revoked API key.', 'flightlinq-api' ) );
		}

		if ( 403 === $code ) {
			return new \WP_Error( 'forbidden', __( 'Access denied. Check your API key scopes.', 'flightlinq-api' ) );
		}

		if ( 404 === $code ) {
			return new \WP_Error( 'not_found', __( 'Resource not found.', 'flightlinq-api' ) );
		}

		if ( $code >= 400 ) {
			$error_msg = sprintf(
				/* translators: 1: HTTP code, 2: HTTP message */
				__( 'FlightLinq API error (%1$d: %2$s).', 'flightlinq-api' ),
				$code,
				$message
			);
			return new \WP_Error( 'api_error', $error_msg );
		}

		// Check whether the content type appears to be JSON.
		$is_json = false !== strpos( $content_type, 'application/json' );

		// Attempt to decode JSON.
		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$excerpt = $this->sanitize_response_excerpt( $body, 500 );
			$error_msg = sprintf(
				/* translators: 1: JSON error message, 2: HTTP code, 3: content-type, 4: excerpt, 5: URL */
				__( 'The FlightLinq response is not valid JSON. JSON error: %1$s. HTTP %2$d, Content-Type: %3$s. Excerpt: %4$s. URL: %5$s', 'flightlinq-api' ),
				json_last_error_msg(),
				$code,
				$content_type,
				$excerpt,
				$url
			);

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'FlightLinq API: ' . $error_msg );
				error_log( 'FlightLinq API: Raw body: ' . $body );
			}

			return new \WP_Error( 'json_error', $error_msg );
		}

		return $data;
	}

	/**
	 * Retrieves airline information.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Optional arguments. Unused, kept for consistency.
	 *
	 * @return array|\WP_Error
	 */
	public function get_airline( $args = array() ) {
		return $this->request( 'airline' );
	}

	/**
	 * Retrieves airline statistics.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Optional arguments. Unused, kept for consistency.
	 *
	 * @return array|\WP_Error
	 */
	public function get_airline_stats( $args = array() ) {
		return $this->request( 'airline/stats' );
	}

	/**
	 * Retrieves the pilot list.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Pagination and filtering arguments.
	 *
	 * @return array|\WP_Error
	 */
	public function get_pilots( $args = array() ) {
		$defaults = array(
			'page'      => 1,
			'limit'     => 25,
			'sortBy'    => 'hours',
			'sortOrder' => 'desc',
		);
		$args     = wp_parse_args( $args, $defaults );

		return $this->request( 'pilots', $args );
	}

	/**
	 * Retrieves the route list.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Pagination and filtering arguments.
	 *
	 * @return array|\WP_Error
	 */
	public function get_routes( $args = array() ) {
		$defaults = array(
			'page'      => 1,
			'limit'     => 100,
			'sortBy'    => 'flightNumber',
			'sortOrder' => 'asc',
		);
		$args     = wp_parse_args( $args, $defaults );

		return $this->request( 'routes', $args );
	}

	/**
	 * Retrieves routes in map format without pagination.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Filtering arguments.
	 *
	 * @return array|\WP_Error
	 */
	public function get_routes_map( $args = array() ) {
		return $this->request( 'routes/map', $args );
	}

	/**
	 * Retrieves the fleet by aircraft type.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Optional arguments. Unused, kept for consistency.
	 *
	 * @return array|\WP_Error
	 */
	public function get_fleet_types( $args = array() ) {
		return $this->request( 'fleet/types' );
	}

	/**
	 * Retrieves recent flights.
	 *
	 * @since 1.0.0
	 * @since 1.0.1 Accepts either an arguments array or an integer for limit.
	 * @date 2026-05-22
	 *
	 * @param array|int $args Arguments array or integer limit.
	 *
	 * @return array|\WP_Error
	 */
	public function get_recent_flights( $args = array() ) {
		if ( is_int( $args ) ) {
			$limit = min( $args, 50 );
			$args = array( 'limit' => $limit );
		} else {
			$args = is_array( $args ) ? $args : array();
			$limit = isset( $args['limit'] ) ? (int) $args['limit'] : 10;
			$limit = min( $limit, 50 );
			$args['limit'] = $limit;
		}
		return $this->request( 'flights/recent', $args );
	}

	/**
	 * Retrieves the pilot leaderboard.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Arguments, including limit and timeframe.
	 *
	 * @return array|\WP_Error
	 */
	public function get_pilot_leaderboards( $args = array() ) {
		$defaults = array(
			'limit'     => 10,
			'timeframe' => 'month',
		);
		$args     = wp_parse_args( $args, $defaults );

		return $this->request( 'leaderboards/pilots', $args );
	}

	/**
	 * Performs a diagnostic test on the /airline endpoint.
	 *
	 * @since 1.0.2
	 * @date 2026-05-22
	 *
	 * @param string $auth_mode Authentication mode: 'none', 'x-api-key', or 'bearer'.
	 *
	 * @return array|\WP_Error Diagnostic array or error.
	 */
	public function diagnostic_test( $auth_mode = 'x-api-key' ) {
		$url = FLIGHTLINQ_API_BASE_URL . '/airline';

		$headers = array(
			'Accept'     => 'application/json',
			'User-Agent' => 'WordPress FlightLinq API Plugin/' . FLIGHTLINQ_API_VERSION,
		);

		if ( 'x-api-key' === $auth_mode ) {
			$headers['x-api-key'] = $this->api_key;
		} elseif ( 'bearer' === $auth_mode ) {
			$headers['Authorization'] = 'Bearer ' . $this->api_key;
		}

		$response = wp_remote_get(
			$url,
			array(
				'headers' => $headers,
				'timeout'  => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body         = wp_remote_retrieve_body( $response );
		$code         = wp_remote_retrieve_response_code( $response );
		$message      = wp_remote_retrieve_response_message( $response );
		$headers_resp = wp_remote_retrieve_headers( $response );
		$content_type = isset( $headers_resp['content-type'] ) ? $headers_resp['content-type'] : '';

		$diagnostic = array(
			'auth_mode'    => $auth_mode,
			'url'          => $url,
			'http_code'    => $code,
			'http_message' => $message,
			'content_type' => $content_type,
			'json_valid'   => false,
			'json_error'   => '',
			'php_type'     => '',
			'keys'         => array(),
			'excerpt'      => $this->sanitize_response_excerpt( $body, 500 ),
		);

		if ( empty( $body ) ) {
			$diagnostic['json_error'] = 'Empty body';
			return $diagnostic;
		}

		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$diagnostic['json_error'] = json_last_error_msg();
			return $diagnostic;
		}

		$diagnostic['json_valid'] = true;
		$diagnostic['php_type']   = gettype( $data );
		$diagnostic['keys']       = is_array( $data ) ? array_keys( $data ) : array();

		return $diagnostic;
	}

	/**
	 * Returns a masked version of the API key for display.
	 *
	 * @since 1.0.2
	 * @date 2026-05-22
	 *
	 * @return string Masked API key, for example flq_live_...XXXX.
	 */
	public function get_masked_api_key() {
		if ( empty( $this->api_key ) ) {
			return '';
		}
		$length = strlen( $this->api_key );
		if ( $length <= 4 ) {
			return '****';
		}
		return substr( $this->api_key, 0, -4 ) . '...' . substr( $this->api_key, -4 );
	}

	/**
	 * Tests all shortcode endpoints for diagnostics.
	 *
	 * @since 1.0.4
	 * @date 2026-05-23
	 *
	 * @return array Array containing the results for each endpoint.
	 */
	public function diagnostic_shortcodes() {
		$results = array(
			'airline'       => $this->test_endpoint( 'get_airline' ),
			'pilots'        => $this->test_endpoint( 'get_pilots', array( 'limit' => 5 ) ),
			'routes'        => $this->test_endpoint( 'get_routes', array( 'limit' => 5 ) ),
			'fleet'         => $this->test_endpoint( 'get_fleet_types' ),
			'recent_flights' => $this->test_endpoint( 'get_recent_flights', array( 'limit' => 5 ) ),
			'leaderboards'  => $this->test_endpoint( 'get_pilot_leaderboards', array( 'limit' => 5 ) ),
		);

		return $results;
	}

	/**
	 * Tests a specific endpoint for diagnostics.
	 *
	 * @since 1.0.4
	 * @date 2026-05-23
	 *
	 * @param string $method The client method to call.
	 * @param array  $args   The arguments to pass.
	 *
	 * @return array Array containing the status and item count.
	 */
	private function test_endpoint( $method, $args = array() ) {
		$result = call_user_func( array( $this, $method ), $args );

		if ( is_wp_error( $result ) ) {
			return array(
				'status'  => 'error',
				'message' => $result->get_error_message(),
				'count'   => 0,
			);
		}

		$count = 0;
		if ( is_array( $result ) ) {
			if ( array_key_exists( 'data', $result ) && is_array( $result['data'] ) ) {
				$count = count( $result['data'] );
			} elseif ( $this->is_numeric_array( $result ) ) {
				$count = count( $result );
			} else {
				$count = 1; // Simple object, such as airline.
			}
		}

		return array(
			'status'  => 'ok',
			'message' => '',
			'count'   => $count,
		);
	}

	/**
	 * Checks whether an array is a numeric array, also known as a list.
	 *
	 * @since 1.0.4
	 * @date 2026-05-23
	 *
	 * @param array $array The array to check.
	 *
	 * @return bool True if this is a numeric array, false otherwise.
	 */
	private function is_numeric_array( $array ) {
		if ( ! is_array( $array ) || empty( $array ) ) {
			return false;
		}

		return array_keys( $array ) === range( 0, count( $array ) - 1 );
	}

	/**
	 * Tests the comparison between the old and new API URLs.
	 *
	 * @since 1.0.3
	 * @date 2026-05-23
	 *
	 * @return array Array containing the test results for both URLs.
	 */
	public function diagnostic_url_comparison() {
		$old_url = 'https://app.flightlinq.com/api/v1/external/airline';
		$new_url = FLIGHTLINQ_API_BASE_URL . '/airline';

		$headers = array(
			'Accept'     => 'application/json',
			'User-Agent' => 'WordPress FlightLinq API Plugin/' . FLIGHTLINQ_API_VERSION,
		);

		if ( ! empty( $this->api_key ) ) {
			$headers['x-api-key'] = $this->api_key;
		}

		$old_result = $this->perform_diagnostic_request( $old_url, $headers );
		$new_result = $this->perform_diagnostic_request( $new_url, $headers );

		return array(
			'old_url' => $old_result,
			'new_url' => $new_result,
		);
	}

	/**
	 * Performs a diagnostic request and returns the results.
	 *
	 * @since 1.0.3
	 * @date 2026-05-23
	 *
	 * @param string $url     The URL to test.
	 * @param array  $headers The HTTP headers.
	 *
	 * @return array Diagnostic array.
	 */
	private function perform_diagnostic_request( $url, $headers ) {
		$response = wp_remote_get(
			$url,
			array(
				'headers' => $headers,
				'timeout'  => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'url'          => $url,
				'http_code'    => 0,
				'http_message' => $response->get_error_message(),
				'content_type' => '',
				'json_valid'   => false,
				'json_error'   => $response->get_error_message(),
				'php_type'     => '',
				'keys'         => array(),
				'excerpt'      => '',
			);
		}

		$body         = wp_remote_retrieve_body( $response );
		$code         = wp_remote_retrieve_response_code( $response );
		$message      = wp_remote_retrieve_response_message( $response );
		$headers_resp = wp_remote_retrieve_headers( $response );
		$content_type = isset( $headers_resp['content-type'] ) ? $headers_resp['content-type'] : '';

		$diagnostic = array(
			'url'          => $url,
			'http_code'    => $code,
			'http_message' => $message,
			'content_type' => $content_type,
			'json_valid'   => false,
			'json_error'   => '',
			'php_type'     => '',
			'keys'         => array(),
			'excerpt'      => $this->sanitize_response_excerpt( $body, 500 ),
		);

		if ( empty( $body ) ) {
			$diagnostic['json_error'] = 'Empty body';
			return $diagnostic;
		}

		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$diagnostic['json_error'] = json_last_error_msg();
			return $diagnostic;
		}

		$diagnostic['json_valid'] = true;
		$diagnostic['php_type']   = gettype( $data );
		$diagnostic['keys']       = is_array( $data ) ? array_keys( $data ) : array();

		return $diagnostic;
	}
}
