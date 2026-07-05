<?php
/**
 * Template functions for integrating the FlightLinq API into the theme.
 *
 * This file exposes simple PHP functions that can be used directly
 * in WordPress theme templates to retrieve FlightLinq data.
 *
 * @package FlightLinq_API
 * @since 1.1.0
 * @date 2026-05-23
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieves the FlightLinq API client.
 *
 * @since 1.1.0
 * @date 2026-05-23
 *
 * @return FlightLinq_API\Client|\WP_Error API client instance or error.
 */
if ( ! function_exists( 'flightlinq_api_get_client' ) ) {
	function flightlinq_api_get_client() {
		if ( ! class_exists( 'FlightLinq_API\\Client' ) ) {
			return new \WP_Error( 'client_class_not_found', __( 'FlightLinq Client class is not available.', 'flightlinq-api' ) );
		}
		$client = FlightLinq_API\Client::get_instance();
		if ( ! $client ) {
			return new \WP_Error( 'client_unavailable', __( 'FlightLinq API client is not available.', 'flightlinq-api' ) );
		}
		return $client;
	}
}

/**
 * Retrieves airline information.
 *
 * @since 1.1.0
 * @date 2026-05-23
 *
 * @return array|\WP_Error Airline data or error.
 */
if ( ! function_exists( 'flightlinq_api_get_airline' ) ) {
	function flightlinq_api_get_airline() {
		$client = flightlinq_api_get_client();
		if ( is_wp_error( $client ) ) {
			return $client;
		}
		return $client->get_airline();
	}
}

/**
 * Retrieves airline statistics.
 *
 * @since 1.1.0
 * @date 2026-05-23
 *
 * @return array|\WP_Error Airline statistics or error.
 */
if ( ! function_exists( 'flightlinq_api_get_airline_stats' ) ) {
	function flightlinq_api_get_airline_stats() {
		$client = flightlinq_api_get_client();
		if ( is_wp_error( $client ) ) {
			return $client;
		}
		return $client->get_airline_stats();
	}
}

/**
 * Retrieves the pilot list.
 *
 * @since 1.1.0
 * @date 2026-05-23
 *
 * @param array $args Pagination and filtering arguments (limit, page, sortBy, sortOrder).
 *
 * @return array|\WP_Error Pilot list or error.
 */
if ( ! function_exists( 'flightlinq_api_get_pilots' ) ) {
	function flightlinq_api_get_pilots( $args = array() ) {
		$client = flightlinq_api_get_client();
		if ( is_wp_error( $client ) ) {
			return $client;
		}
		return $client->get_pilots( $args );
	}
}

/**
 * Retrieves the route list.
 *
 * @since 1.1.0
 * @date 2026-05-23
 *
 * @param array $args Pagination and filtering arguments (limit, page, sortBy, sortOrder).
 *
 * @return array|\WP_Error Route list or error.
 */
if ( ! function_exists( 'flightlinq_api_get_routes' ) ) {
	function flightlinq_api_get_routes( $args = array() ) {
		$client = flightlinq_api_get_client();
		if ( is_wp_error( $client ) ) {
			return $client;
		}
		return $client->get_routes( $args );
	}
}

/**
 * Retrieves routes in map format without pagination.
 *
 * @since 1.1.0
 * @date 2026-05-23
 *
 * @param array $args Filtering arguments.
 *
 * @return array|\WP_Error Routes in map format or error.
 */
if ( ! function_exists( 'flightlinq_api_get_routes_map' ) ) {
	function flightlinq_api_get_routes_map( $args = array() ) {
		$client = flightlinq_api_get_client();
		if ( is_wp_error( $client ) ) {
			return $client;
		}
		return $client->get_routes_map( $args );
	}
}

/**
 * Retrieves the fleet by aircraft type.
 *
 * @since 1.1.0
 * @date 2026-05-23
 *
 * @return array|\WP_Error Fleet by type or error.
 */
if ( ! function_exists( 'flightlinq_api_get_fleet_types' ) ) {
	function flightlinq_api_get_fleet_types() {
		$client = flightlinq_api_get_client();
		if ( is_wp_error( $client ) ) {
			return $client;
		}
		return $client->get_fleet_types();
	}
}

/**
 * Retrieves recent flights.
 *
 * @since 1.1.0
 * @date 2026-05-23
 *
 * @param array $args Arguments (limit, etc.).
 *
 * @return array|\WP_Error Recent flights or error.
 */
if ( ! function_exists( 'flightlinq_api_get_recent_flights' ) ) {
	function flightlinq_api_get_recent_flights( $args = array() ) {
		$client = flightlinq_api_get_client();
		if ( is_wp_error( $client ) ) {
			return $client;
		}
		return $client->get_recent_flights( $args );
	}
}

/**
 * Retrieves pilot leaderboards.
 *
 * @since 1.1.0
 * @date 2026-05-23
 *
 * @param array $args Arguments (limit, timeframe).
 *
 * @return array|\WP_Error Pilot leaderboards or error.
 */
if ( ! function_exists( 'flightlinq_api_get_pilot_leaderboards' ) ) {
	function flightlinq_api_get_pilot_leaderboards( $args = array() ) {
		$client = flightlinq_api_get_client();
		if ( is_wp_error( $client ) ) {
			return $client;
		}
		return $client->get_pilot_leaderboards( $args );
	}
}

/**
 * Checks whether a response is a WP_Error.
 *
 * @since 1.1.0
 * @date 2026-05-23
 *
 * @param mixed $response The response to check.
 *
 * @return bool True if this is a WP_Error, false otherwise.
 */
if ( ! function_exists( 'flightlinq_api_is_error' ) ) {
	function flightlinq_api_is_error( $response ) {
		return is_wp_error( $response );
	}
}

/**
 * Retrieves a concise user-facing error message.
 *
 * @since 1.1.0
 * @date 2026-05-23
 *
 * @param mixed $response The response to check.
 *
 * @return string Error message or empty string.
 */
if ( ! function_exists( 'flightlinq_api_get_error_message' ) ) {
	function flightlinq_api_get_error_message( $response ) {
		if ( ! is_wp_error( $response ) ) {
			return '';
		}
		return $response->get_error_message();
	}
}

/**
 * Safely retrieves a nested value from an array.
 *
 * Allows deep values to be retrieved from a multidimensional array
 * without triggering a PHP warning when an intermediate key is missing.
 *
 * @since 1.1.0
 * @date 2026-05-23
 *
 * @param array  $array   The source array.
 * @param string $path    Dot-separated path (for example: 'stats.totalPilots').
 * @param mixed  $default Default value if the path does not exist.
 *
 * @return mixed Found value or default value.
 */
if ( ! function_exists( 'flightlinq_api_get_nested_value' ) ) {
	function flightlinq_api_get_nested_value( $array, $path, $default = '' ) {
		if ( ! is_array( $array ) || empty( $path ) ) {
			return $default;
		}

		$keys = explode( '.', $path );
		$value = $array;

		foreach ( $keys as $key ) {
			if ( ! is_array( $value ) || ! array_key_exists( $key, $value ) ) {
				return $default;
			}
			$value = $value[ $key ];
		}

		return $value;
	}
}

/**
 * Normalizes a FlightLinq API collection response.
 *
 * Some endpoints return a direct array, while others return an array with
 * a 'data' key containing the collection. This function normalizes both
 * formats so an array of items is always returned.
 *
 * @since 1.1.0
 * @date 2026-05-23
 *
 * @param array $response The API response.
 *
 * @return array Normalized item array.
 */
if ( ! function_exists( 'flightlinq_api_normalize_collection' ) ) {
	function flightlinq_api_normalize_collection( $response ) {
		if ( ! is_array( $response ) ) {
			return array();
		}

		// Use the 'data' key when it exists and contains an array.
		if ( array_key_exists( 'data', $response ) && is_array( $response['data'] ) ) {
			return $response['data'];
		}

		// Return the response as-is when it is already a numeric array.
		if ( flightlinq_api_is_numeric_array( $response ) ) {
			return $response;
		}

		// Otherwise, return an empty array.
		return array();
	}
}

/**
 * Checks whether an array is a numeric array (list).
 *
 * @since 1.1.0
 * @date 2026-05-23
 *
 * @param array $array The array to check.
 *
 * @return bool True if this is a numeric array, false otherwise.
 */
if ( ! function_exists( 'flightlinq_api_is_numeric_array' ) ) {
	function flightlinq_api_is_numeric_array( $array ) {
		if ( ! is_array( $array ) || empty( $array ) ) {
			return false;
		}

		return array_keys( $array ) === range( 0, count( $array ) - 1 );
	}
}

/**
 * Retrieves data with cache support.
 *
 * This function retrieves data from the FlightLinq API using the cache
 * to avoid repeated calls. The cache is used only when enabled in the
 * plugin options.
 *
 * @since 1.4.0
 * @date 2026-05-23
 *
 * @param string $endpoint The FlightLinq endpoint (for example: 'airline', 'pilots').
 * @param string $method   The client method to call (for example: 'get_airline').
 * @param array  $args     Arguments to pass to the client method.
 *
 * @return array|\WP_Error API data or error.
 */
if ( ! function_exists( 'flightlinq_api_get_cached_data' ) ) {
	function flightlinq_api_get_cached_data( $endpoint, $method, $args = array() ) {
		// Check whether caching is enabled.
		$enable_cache = get_option( 'flightlinq_enable_cache', true );

		if ( $enable_cache && class_exists( 'FlightLinq_API\\Cache' ) ) {
			$cache = FlightLinq_API\Cache::get_instance();
			$data  = $cache->get( $endpoint, $args );

			// Return cached data when it exists and is not an error.
			if ( false !== $data && ! is_wp_error( $data ) ) {
				return $data;
			}
		}

		// Retrieve data through the client.
		$client = flightlinq_api_get_client();
		if ( is_wp_error( $client ) ) {
			return $client;
		}

		$data = call_user_func( array( $client, $method ), $args );

		// Do not cache errors.
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		// Cache the data when caching is enabled.
		if ( $enable_cache && class_exists( 'FlightLinq_API\\Cache' ) ) {
			$cache = FlightLinq_API\Cache::get_instance();
			$cache->set( $endpoint, $args, $data );
		}

		return $data;
	}
}
