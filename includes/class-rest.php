<?php
/**
 * FlightLinq REST API.
 *
 * @package FlightLinq_API
 * @since 1.0.0
 */

namespace FlightLinq_API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FlightLinq_API\REST class.
 *
 * Registers WordPress REST endpoints to expose FlightLinq data.
 *
 * @since 1.0.0
 */
class REST {

	/**
	 * Unique class instance.
	 *
	 * @since 1.0.0
	 *
	 * @var REST
	 */
	private static $instance = null;

	/**
	 * REST API namespace.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $namespace = 'flightlinq/v1';

	/**
	 * Private constructor to enforce singleton usage.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Returns the unique class instance.
	 *
	 * @since 1.0.0
	 *
	 * @return REST
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Registers REST routes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_routes() {
		// Airline.
		register_rest_route(
			$this->namespace,
			'/airline',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_airline' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Airline stats.
		register_rest_route(
			$this->namespace,
			'/airline/stats',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_airline_stats' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Pilots.
		register_rest_route(
			$this->namespace,
			'/pilots',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_pilots' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $this->get_pilots_args(),
			)
		);

		// Routes.
		register_rest_route(
			$this->namespace,
			'/routes',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_routes' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $this->get_routes_args(),
			)
		);

		// Routes map.
		register_rest_route(
			$this->namespace,
			'/routes/map',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_routes_map' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $this->get_routes_map_args(),
			)
		);

		// Fleet types.
		register_rest_route(
			$this->namespace,
			'/fleet',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_fleet_types' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Recent flights.
		register_rest_route(
			$this->namespace,
			'/flights/recent',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_recent_flights' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $this->get_recent_flights_args(),
			)
		);

		// Pilot leaderboards.
		register_rest_route(
			$this->namespace,
			'/leaderboards/pilots',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_pilot_leaderboards' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $this->get_leaderboards_args(),
			)
		);
	}

	/**
	 * Checks permissions.
	 *
	 * @since 1.0.0
	 * @since 1.6.0 Respects the public REST API enablement option.
	 * @date 2026-07-04
	 *
	 * @return true|\WP_Error True when the public API is enabled, or a REST error otherwise.
	 */
	public function check_permission() {
		$is_enabled = get_option( 'flightlinq_api_public_rest_enabled', true );

		if ( ! rest_sanitize_boolean( $is_enabled ) ) {
			return new \WP_Error(
				'flightlinq_public_rest_disabled',
				__( 'The public FlightLinq REST API is disabled.', 'flightlinq-api' ),
				array( 'status' => 403 )
			);
		}

		// Public read access is preserved when the administrable option is enabled.
		return true;
	}

	/**
	 * Retrieves cached data and prepares the public REST response.
	 *
	 * @since 1.0.0
	 * @since 1.6.0 Added public filtering and concise REST errors.
	 * @date 2026-07-04
	 *
	 * @param string $endpoint         The FlightLinq endpoint.
	 * @param string $method           The client method to call.
	 * @param array  $args             The arguments.
	 * @param string $prepare_callback The public preparation helper.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	private function get_cached_data( $endpoint, $method, $args = array(), $prepare_callback = '' ) {
		$cache = Cache::get_instance();
		$data  = $cache->get( $endpoint, $args );

		if ( false === $data ) {
			$client = Client::get_instance();
			$data   = call_user_func( array( $client, $method ), $args );

			if ( is_wp_error( $data ) ) {
				return $this->get_public_error_response();
			}

			$cache->set( $endpoint, $args, $data );
		}

		if ( is_wp_error( $data ) ) {
			return $this->get_public_error_response();
		}

		if ( ! empty( $prepare_callback ) && method_exists( $this, $prepare_callback ) ) {
			$data = call_user_func( array( $this, $prepare_callback ), $data );
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Returns a concise public error for REST endpoints.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @return \WP_Error Public REST error without technical details.
	 */
	private function get_public_error_response() {
		return new \WP_Error(
			'flightlinq_public_rest_unavailable',
			__( 'FlightLinq data is temporarily unavailable.', 'flightlinq-api' ),
			array( 'status' => 503 )
		);
	}

	/**
	 * Normalizes a public REST limit.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param mixed $value The requested limit.
	 *
	 * @return int Limit between 1 and 50, or 10 if invalid.
	 */
	private function get_rest_limit( $value ) {
		if ( ! is_numeric( $value ) ) {
			return 10;
		}

		$limit = (int) $value;

		if ( $limit < 1 ) {
			return 10;
		}

		return min( $limit, 50 );
	}

	/**
	 * Sanitizes a public REST limit.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param mixed $value The limit received by the REST API.
	 *
	 * @return int Normalized limit.
	 */
	public function sanitize_public_limit( $value ) {
		return $this->get_rest_limit( $value );
	}

	/**
	 * Normalizes a public REST page.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param mixed $value Received page number.
	 *
	 * @return int Page greater than or equal to 1.
	 */
	public function sanitize_public_page( $value ) {
		return max( 1, absint( $value ) );
	}

	/**
	 * Normalizes a public route or aircraft type code.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param mixed $value Received code.
	 *
	 * @return string Uppercase code limited to expected characters.
	 */
	public function sanitize_public_route_code( $value ) {
		$value = strtoupper( sanitize_text_field( (string) $value ) );
		$value = preg_replace( '/[^A-Z0-9-]/', '', $value );

		return $value;
	}

	/**
	 * Limits public pilot sorting to supported fields.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param mixed $value Received sort field.
	 *
	 * @return string Valid sort field.
	 */
	public function sanitize_public_pilot_sort_by( $value ) {
		$value   = sanitize_text_field( (string) $value );
		$allowed = array( 'pilotId', 'displayName', 'rank', 'role', 'hub', 'hours', 'flights', 'status', 'joinedAt', 'averageLandingRate', 'averageScore' );

		return in_array( $value, $allowed, true ) ? $value : 'hours';
	}

	/**
	 * Limits public route sorting to supported fields.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param mixed $value Received sort field.
	 *
	 * @return string Valid sort field.
	 */
	public function sanitize_public_route_sort_by( $value ) {
		$value   = sanitize_text_field( (string) $value );
		$allowed = array( 'flightNumber', 'departure', 'arrival', 'distance' );

		return in_array( $value, $allowed, true ) ? $value : 'flightNumber';
	}

	/**
	 * Limits the public sort order to supported values.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param mixed $value Received sort order.
	 *
	 * @return string Valid sort order.
	 */
	public function sanitize_public_sort_order( $value ) {
		$value = strtolower( sanitize_text_field( (string) $value ) );

		return in_array( $value, array( 'asc', 'desc' ), true ) ? $value : 'asc';
	}

	/**
	 * Limits the public leaderboard timeframe to supported values.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param mixed $value Received timeframe.
	 *
	 * @return string Valid timeframe.
	 */
	public function sanitize_public_timeframe( $value ) {
		$value = strtolower( sanitize_text_field( (string) $value ) );

		return in_array( $value, array( 'month', 'all' ), true ) ? $value : 'month';
	}

	/**
	 * Normalizes a collection returned by FlightLinq.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param mixed $response The FlightLinq response.
	 *
	 * @return array Normalized collection.
	 */
	private function normalize_public_collection( $response ) {
		if ( ! is_array( $response ) ) {
			return array();
		}

		if ( array_key_exists( 'data', $response ) && is_array( $response['data'] ) ) {
			return $response['data'];
		}

		if ( $this->is_list_array( $response ) ) {
			return $response;
		}

		return array();
	}

	/**
	 * Checks whether an array is a numeric list.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array $items Array to check.
	 *
	 * @return bool True if the array is a list.
	 */
	private function is_list_array( $items ) {
		if ( ! is_array( $items ) ) {
			return false;
		}

		if ( empty( $items ) ) {
			return true;
		}

		return array_keys( $items ) === range( 0, count( $items ) - 1 );
	}

	/**
	 * Retrieves a public value from several possible paths.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array $item  Source item.
	 * @param array $paths Possible dot-separated paths.
	 *
	 * @return mixed|null Found value, or null.
	 */
	private function get_public_value( $item, $paths ) {
		if ( ! is_array( $item ) ) {
			return null;
		}

		foreach ( $paths as $path ) {
			$value = $item;
			$keys  = explode( '.', $path );

			foreach ( $keys as $key ) {
				if ( ! is_array( $value ) || ! array_key_exists( $key, $value ) ) {
					$value = null;
					break;
				}

				$value = $value[ $key ];
			}

			if ( null !== $value && '' !== $value ) {
				return $value;
			}
		}

		return null;
	}

	/**
	 * Adds a public field when a source value exists.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array  $output Public array being built.
	 * @param string $key    Public key.
	 * @param array  $item   Source item.
	 * @param array  $paths  Possible paths.
	 *
	 * @return void
	 */
	private function add_public_field( &$output, $key, $item, $paths ) {
		$value = $this->get_public_value( $item, $paths );

		if ( null !== $value ) {
			$output[ $key ] = $value;
		}
	}

	/**
	 * Prepares public airline information.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array $airline FlightLinq airline data.
	 *
	 * @return array Filtered airline data.
	 */
	private function prepare_public_airline( $airline ) {
		if ( is_array( $airline ) && array_key_exists( 'data', $airline ) && is_array( $airline['data'] ) ) {
			$airline = $airline['data'];
		}

		$output = array();

		$this->add_public_field( $output, 'name', $airline, array( 'name', 'airlineName' ) );
		$this->add_public_field( $output, 'code', $airline, array( 'code', 'airlineCode' ) );
		$this->add_public_field( $output, 'iataCode', $airline, array( 'iataCode', 'iata' ) );
		$this->add_public_field( $output, 'icaoCode', $airline, array( 'icaoCode', 'icao' ) );
		$this->add_public_field( $output, 'callsign', $airline, array( 'callsign', 'callSign' ) );
		$this->add_public_field( $output, 'description', $airline, array( 'description' ) );
		$this->add_public_field( $output, 'website', $airline, array( 'website', 'websiteUrl' ) );
		$this->add_public_field( $output, 'logoUrl', $airline, array( 'logoUrl', 'logo.url', 'logo' ) );
		$this->add_public_field( $output, 'bannerUrl', $airline, array( 'bannerUrl', 'banner.url', 'banner' ) );
		$this->add_public_field( $output, 'headquarters', $airline, array( 'headquarters', 'headQuarter' ) );
		$this->add_public_field( $output, 'founded', $airline, array( 'founded', 'foundedAt', 'foundedDate' ) );
		$this->add_public_field( $output, 'country', $airline, array( 'country', 'countryName' ) );
		$this->add_public_field( $output, 'lastFlightSubmittedAt', $airline, array( 'lastFlightSubmittedAt', 'stats.lastFlightSubmittedAt' ) );
		$this->add_public_field( $output, 'recruiting', $airline, array( 'recruiting', 'isRecruiting' ) );
		$this->add_public_field( $output, 'requiresApproval', $airline, array( 'requiresApproval', 'applicationRequiresApproval' ) );

		$stats = $this->get_public_value( $airline, array( 'stats', 'statistics' ) );
		if ( is_array( $stats ) ) {
			$output['stats'] = $this->prepare_public_airline_stats( $stats );
		}

		return $output;
	}

	/**
	 * Prepares public airline statistics.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array $stats FlightLinq statistics.
	 *
	 * @return array Filtered statistics.
	 */
	private function prepare_public_airline_stats( $stats ) {
		if ( is_array( $stats ) && array_key_exists( 'data', $stats ) && is_array( $stats['data'] ) ) {
			$stats = $stats['data'];
		}

		$output = array();

		$this->add_public_field( $output, 'totalPilots', $stats, array( 'totalPilots', 'pilots' ) );
		$this->add_public_field( $output, 'activePilots', $stats, array( 'activePilots' ) );
		$this->add_public_field( $output, 'totalFlights', $stats, array( 'totalFlights', 'flights.totalFlights' ) );
		$this->add_public_field( $output, 'totalHours', $stats, array( 'totalHours', 'hours' ) );
		$this->add_public_field( $output, 'totalRoutes', $stats, array( 'totalRoutes' ) );
		$this->add_public_field( $output, 'totalAircraft', $stats, array( 'totalAircraft' ) );
		$this->add_public_field( $output, 'averageRating', $stats, array( 'averageRating', 'averageFlightRating', 'rating' ) );
		$this->add_public_field( $output, 'averageFlightRating', $stats, array( 'averageFlightRating', 'averageRating' ) );
		$this->add_public_field( $output, 'pilotEngagementPct', $stats, array( 'pilotEngagementPct' ) );

		$flights = array();
		$this->add_public_field( $flights, 'monthToDateFlights', $stats, array( 'flights.monthToDateFlights', 'monthToDateFlights' ) );
		$this->add_public_field( $flights, 'monthToDateHours', $stats, array( 'flights.monthToDateHours', 'monthToDateHours' ) );
		$this->add_public_field( $flights, 'monthToDateAvgRating', $stats, array( 'flights.monthToDateAvgRating', 'monthToDateAvgRating' ) );
		if ( ! empty( $flights ) ) {
			$output['flights'] = $flights;
		}

		$fleet = array();
		$this->add_public_field( $fleet, 'utilizationPct', $stats, array( 'fleet.utilizationPct', 'utilizationPct' ) );
		if ( ! empty( $fleet ) ) {
			$output['fleet'] = $fleet;
		}

		$popular_routes = $this->get_public_value( $stats, array( 'popularRoutes' ) );
		if ( is_array( $popular_routes ) ) {
			$popular_routes          = array_slice( $this->normalize_public_collection( $popular_routes ), 0, 10 );
			$output['popularRoutes'] = array_map( array( $this, 'prepare_public_route' ), $popular_routes );
		}

		$finance = array();
		$this->add_public_field( $finance, 'postedBalance', $stats, array( 'airlineFinance.postedBalance', 'postedBalance' ) );
		$this->add_public_field( $finance, 'pendingBalance', $stats, array( 'airlineFinance.pendingBalance', 'pendingBalance' ) );
		$this->add_public_field( $finance, 'currency', $stats, array( 'airlineFinance.currency', 'currency' ) );
		if ( ! empty( $finance ) ) {
			$output['airlineFinance'] = $finance;
		}

		return $output;
	}

	/**
	 * Prepares a public pilot collection.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array $response FlightLinq response.
	 *
	 * @return array Filtered pilots.
	 */
	private function prepare_public_pilots( $response ) {
		$items = $this->normalize_public_collection( $response );
		$items = array_slice( $items, 0, 50 );

		return array_map( array( $this, 'prepare_public_pilot' ), $items );
	}

	/**
	 * Prepares a public pilot.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array $pilot FlightLinq pilot.
	 *
	 * @return array Filtered pilot.
	 */
	private function prepare_public_pilot( $pilot ) {
		$output = array();

		$this->add_public_field( $output, 'displayName', $pilot, array( 'displayName', 'name', 'pilot.displayName' ) );
		$this->add_public_field( $output, 'rank', $pilot, array( 'rank', 'rank.name' ) );
		$this->add_public_field( $output, 'role', $pilot, array( 'role', 'role.name' ) );
		$this->add_public_field( $output, 'status', $pilot, array( 'status' ) );
		$this->add_public_field( $output, 'currentLocation', $pilot, array( 'currentLocation', 'location', 'currentAirport.icaoCode' ) );

		$hub_icao = $this->get_public_value( $pilot, array( 'hub.icaoCode', 'hubIcao', 'hub' ) );
		if ( null !== $hub_icao ) {
			$output['hub'] = array( 'icaoCode' => $hub_icao );
		}

		$stats = array();
		$this->add_public_field( $stats, 'hours', $pilot, array( 'stats.hours', 'stats.totalHours', 'hours', 'totalHours' ) );
		$this->add_public_field( $stats, 'flights', $pilot, array( 'stats.flights', 'stats.totalFlights', 'flights', 'totalFlights' ) );
		$this->add_public_field( $stats, 'averageLandingRate', $pilot, array( 'stats.averageLandingRate', 'averageLandingRate' ) );
		$this->add_public_field( $stats, 'averageScore', $pilot, array( 'stats.averageScore', 'averageScore' ) );
		$this->add_public_field( $output, 'joinedAt', $pilot, array( 'joinedAt' ) );
		if ( ! empty( $stats ) ) {
			$output['stats'] = $stats;
		}

		return $output;
	}

	/**
	 * Prepares a public route collection.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array $response FlightLinq response.
	 *
	 * @return array Filtered routes.
	 */
	private function prepare_public_routes( $response ) {
		$items = $this->normalize_public_collection( $response );
		$items = array_slice( $items, 0, 50 );

		return array_map( array( $this, 'prepare_public_route' ), $items );
	}

	/**
	 * Prepares a public route.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array $route FlightLinq route.
	 *
	 * @return array Filtered route.
	 */
	private function prepare_public_route( $route ) {
		$output = array();

		$this->add_public_field( $output, 'flightNumber', $route, array( 'flightNumber', 'number' ) );
		$this->add_public_field( $output, 'type', $route, array( 'type', 'routeType' ) );
		$this->add_public_field( $output, 'departureIcao', $route, array( 'departureIcao', 'departure.icaoCode', 'departureAirport.icaoCode' ) );
		$this->add_public_field( $output, 'arrivalIcao', $route, array( 'arrivalIcao', 'arrival.icaoCode', 'arrivalAirport.icaoCode' ) );
		$this->add_public_field( $output, 'activeMonths', $route, array( 'activeMonths', 'months' ) );
		$this->add_public_field( $output, 'description', $route, array( 'description' ) );
		$this->add_public_field( $output, 'aircraftTypes', $route, array( 'aircraftTypes', 'aircraft' ) );
		$this->add_public_field( $output, 'distance', $route, array( 'distance', 'distanceNm' ) );

		return $output;
	}

	/**
	 * Prepares a public map route collection.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array $response FlightLinq response.
	 *
	 * @return array Filtered map routes.
	 */
	private function prepare_public_routes_map( $response ) {
		$items = $this->normalize_public_collection( $response );

		return array_map( array( $this, 'prepare_public_route_map' ), $items );
	}

	/**
	 * Prepares a public map route.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array $route FlightLinq map route.
	 *
	 * @return array Filtered map route.
	 */
	private function prepare_public_route_map( $route ) {
		$output = $this->prepare_public_route( $route );

		$this->add_public_field( $output, 'departureName', $route, array( 'departureName', 'departure.name', 'departureAirport.name' ) );
		$this->add_public_field( $output, 'arrivalName', $route, array( 'arrivalName', 'arrival.name', 'arrivalAirport.name' ) );
		$this->add_public_field( $output, 'departureLatitude', $route, array( 'departureLatitude', 'departure.latitude', 'departureAirport.latitude' ) );
		$this->add_public_field( $output, 'departureLongitude', $route, array( 'departureLongitude', 'departure.longitude', 'departureAirport.longitude' ) );
		$this->add_public_field( $output, 'arrivalLatitude', $route, array( 'arrivalLatitude', 'arrival.latitude', 'arrivalAirport.latitude' ) );
		$this->add_public_field( $output, 'arrivalLongitude', $route, array( 'arrivalLongitude', 'arrival.longitude', 'arrivalAirport.longitude' ) );
		$this->add_public_field( $output, 'coordinates', $route, array( 'coordinates' ) );
		$this->add_public_field( $output, 'path', $route, array( 'path' ) );

		return $output;
	}

	/**
	 * Prepares a public fleet collection.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array $response FlightLinq response.
	 *
	 * @return array Filtered fleet.
	 */
	private function prepare_public_fleet( $response ) {
		$items = $this->normalize_public_collection( $response );

		return array_map( array( $this, 'prepare_public_fleet_item' ), $items );
	}

	/**
	 * Prepares a public fleet item.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array $item FlightLinq fleet item.
	 *
	 * @return array Filtered fleet item.
	 */
	private function prepare_public_fleet_item( $item ) {
		$output = array();

		$this->add_public_field( $output, 'type', $item, array( 'type', 'aircraftType' ) );
		$this->add_public_field( $output, 'icaoCode', $item, array( 'icaoCode', 'icao' ) );
		$this->add_public_field( $output, 'name', $item, array( 'name', 'model' ) );
		$this->add_public_field( $output, 'manufacturer', $item, array( 'manufacturer' ) );
		$this->add_public_field( $output, 'count', $item, array( 'count', 'total' ) );
		$this->add_public_field( $output, 'active', $item, array( 'active', 'activeCount' ) );
		$this->add_public_field( $output, 'category', $item, array( 'category', 'aircraftType.category' ) );
		$this->add_public_field( $output, 'maxRange', $item, array( 'maxRange', 'aircraftType.maxRange' ) );
		$this->add_public_field( $output, 'maxSeats', $item, array( 'maxSeats', 'aircraftType.maxSeats' ) );
		$this->add_public_field( $output, 'totalHoursFlown', $item, array( 'totalHoursFlown', 'aircraftType.totalHoursFlown' ) );

		return $output;
	}

	/**
	 * Prepares a public recent flights collection.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array $response FlightLinq response.
	 *
	 * @return array Filtered recent flights.
	 */
	private function prepare_public_recent_flights( $response ) {
		$items = $this->normalize_public_collection( $response );
		$items = array_slice( $items, 0, 50 );

		return array_map( array( $this, 'prepare_public_recent_flight' ), $items );
	}

	/**
	 * Prepares a public recent flight.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array $flight FlightLinq flight.
	 *
	 * @return array Filtered recent flight.
	 */
	private function prepare_public_recent_flight( $flight ) {
		$output = array();

		$this->add_public_field( $output, 'createdAt', $flight, array( 'createdAt', 'date', 'submittedAt' ) );

		$pilot_name = $this->get_public_value( $flight, array( 'pilot.displayName', 'pilotName', 'displayName' ) );
		if ( null !== $pilot_name ) {
			$output['pilot'] = array( 'displayName' => $pilot_name );
		}

		$this->add_public_field( $output, 'flightNumber', $flight, array( 'flightNumber', 'number' ) );
		$this->add_public_field( $output, 'route', $flight, array( 'route', 'routeName' ) );
		$this->add_public_field( $output, 'aircraftType', $flight, array( 'aircraftType', 'aircraft.type' ) );
		$this->add_public_field( $output, 'aircraftRegistration', $flight, array( 'aircraftRegistration', 'aircraft.registration', 'registration' ) );
		$this->add_public_field( $output, 'blockTimeMinutes', $flight, array( 'blockTimeMinutes', 'blockTime' ) );
		$this->add_public_field( $output, 'flightTimeMinutes', $flight, array( 'flightTimeMinutes', 'flightTime' ) );
		$this->add_public_field( $output, 'score', $flight, array( 'score' ) );
		$this->add_public_field( $output, 'landingRate', $flight, array( 'landingRate' ) );

		return $output;
	}

	/**
	 * Prepares a public pilot leaderboard collection.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array $response FlightLinq response.
	 *
	 * @return array Filtered leaderboard.
	 */
	private function prepare_public_leaderboards( $response ) {
		$items = $this->normalize_public_collection( $response );
		$items = array_slice( $items, 0, 50 );

		return array_map( array( $this, 'prepare_public_leaderboard_item' ), $items );
	}

	/**
	 * Prepares a public pilot leaderboard item.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array $item FlightLinq leaderboard item.
	 *
	 * @return array Filtered leaderboard item.
	 */
	private function prepare_public_leaderboard_item( $item ) {
		$output = array();

		$this->add_public_field( $output, 'displayName', $item, array( 'displayName', 'pilot.displayName', 'name' ) );
		$this->add_public_field( $output, 'rank', $item, array( 'rank', 'position' ) );
		$this->add_public_field( $output, 'hours', $item, array( 'hours', 'totalHours', 'stats.hours' ) );
		$this->add_public_field( $output, 'flights', $item, array( 'flights', 'totalFlights', 'stats.flights' ) );
		$this->add_public_field( $output, 'score', $item, array( 'score' ) );
		$this->add_public_field( $output, 'averageLandingRate', $item, array( 'averageLandingRate', 'stats.averageLandingRate' ) );
		$this->add_public_field( $output, 'averageScore', $item, array( 'averageScore', 'stats.averageScore' ) );

		return $output;
	}

	/**
	 * GET /airline.
	 *
	 * @since 1.0.0
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_airline() {
		return $this->get_cached_data( 'airline', 'get_airline', array(), 'prepare_public_airline' );
	}

	/**
	 * GET /airline/stats.
	 *
	 * @since 1.0.0
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_airline_stats() {
		return $this->get_cached_data( 'airline/stats', 'get_airline_stats', array(), 'prepare_public_airline_stats' );
	}

	/**
	 * GET /pilots.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The REST request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_pilots( $request ) {
		$args = array(
			'page'      => $request->get_param( 'page' ),
			'limit'     => $this->get_rest_limit( $request->get_param( 'limit' ) ),
			'sortBy'    => $request->get_param( 'sortBy' ),
			'sortOrder' => $request->get_param( 'sortOrder' ),
			'hubId'     => $request->get_param( 'hubId' ),
		);

		return $this->get_cached_data( 'pilots', 'get_pilots', $args, 'prepare_public_pilots' );
	}

	/**
	 * Arguments for /pilots.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_pilots_args() {
		return array(
			'page'      => array(
				'default'           => 1,
				'sanitize_callback' => array( $this, 'sanitize_public_page' ),
			),
			'limit'     => array(
				'default'           => 10,
				'sanitize_callback' => array( $this, 'sanitize_public_limit' ),
				'maximum'           => 50,
			),
			'sortBy'    => array(
				'default'           => 'hours',
				'sanitize_callback' => array( $this, 'sanitize_public_pilot_sort_by' ),
			),
			'sortOrder' => array(
				'default'           => 'desc',
				'sanitize_callback' => array( $this, 'sanitize_public_sort_order' ),
			),
			'hubId'     => array(
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * GET /routes.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The REST request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_routes( $request ) {
		$args = array(
			'search'      => $request->get_param( 'search' ),
			'departure'   => $request->get_param( 'departure' ),
			'arrival'     => $request->get_param( 'arrival' ),
			'aircraftType' => $request->get_param( 'aircraftType' ),
			'page'        => $request->get_param( 'page' ),
			'limit'       => $this->get_rest_limit( $request->get_param( 'limit' ) ),
			'sortBy'      => $request->get_param( 'sortBy' ),
			'sortOrder'   => $request->get_param( 'sortOrder' ),
		);

		return $this->get_cached_data( 'routes', 'get_routes', $args, 'prepare_public_routes' );
	}

	/**
	 * Arguments for /routes.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_routes_args() {
		return array(
			'search'       => array(
				'sanitize_callback' => 'sanitize_text_field',
			),
			'departure'    => array(
				'sanitize_callback' => array( $this, 'sanitize_public_route_code' ),
			),
			'arrival'      => array(
				'sanitize_callback' => array( $this, 'sanitize_public_route_code' ),
			),
			'aircraftType' => array(
				'sanitize_callback' => array( $this, 'sanitize_public_route_code' ),
			),
			'page'         => array(
				'default'           => 1,
				'sanitize_callback' => array( $this, 'sanitize_public_page' ),
			),
			'limit'        => array(
				'default'           => 10,
				'sanitize_callback' => array( $this, 'sanitize_public_limit' ),
				'maximum'           => 50,
			),
			'sortBy'       => array(
				'default'           => 'flightNumber',
				'sanitize_callback' => array( $this, 'sanitize_public_route_sort_by' ),
			),
			'sortOrder'    => array(
				'default'           => 'asc',
				'sanitize_callback' => array( $this, 'sanitize_public_sort_order' ),
			),
		);
	}

	/**
	 * GET /routes/map.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The REST request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_routes_map( $request ) {
		$args = array(
			'search'       => $request->get_param( 'search' ),
			'departure'    => $request->get_param( 'departure' ),
			'arrival'      => $request->get_param( 'arrival' ),
			'aircraftType' => $request->get_param( 'aircraftType' ),
			'sortBy'       => $request->get_param( 'sortBy' ),
			'sortOrder'    => $request->get_param( 'sortOrder' ),
		);

		return $this->get_cached_data( 'routes/map', 'get_routes_map', $args, 'prepare_public_routes_map' );
	}

	/**
	 * Arguments for /routes/map.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_routes_map_args() {
		return array(
			'search'       => array(
				'sanitize_callback' => 'sanitize_text_field',
			),
			'departure'    => array(
				'sanitize_callback' => array( $this, 'sanitize_public_route_code' ),
			),
			'arrival'      => array(
				'sanitize_callback' => array( $this, 'sanitize_public_route_code' ),
			),
			'aircraftType' => array(
				'sanitize_callback' => array( $this, 'sanitize_public_route_code' ),
			),
			'sortBy'       => array(
				'default'           => 'flightNumber',
				'sanitize_callback' => array( $this, 'sanitize_public_route_sort_by' ),
			),
			'sortOrder'    => array(
				'default'           => 'asc',
				'sanitize_callback' => array( $this, 'sanitize_public_sort_order' ),
			),
		);
	}

	/**
	 * GET /fleet.
	 *
	 * @since 1.0.0
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_fleet_types() {
		return $this->get_cached_data( 'fleet/types', 'get_fleet_types', array(), 'prepare_public_fleet' );
	}

	/**
	 * GET /flights/recent.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The REST request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_recent_flights( $request ) {
		$limit = $this->get_rest_limit( $request->get_param( 'limit' ) );
		return $this->get_cached_data( 'flights/recent', 'get_recent_flights', array( 'limit' => $limit ), 'prepare_public_recent_flights' );
	}

	/**
	 * Arguments for /flights/recent.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_recent_flights_args() {
		return array(
			'limit' => array(
				'default'           => 10,
				'sanitize_callback' => array( $this, 'sanitize_public_limit' ),
				'maximum'           => 50,
			),
		);
	}

	/**
	 * GET /leaderboards/pilots.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The REST request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_pilot_leaderboards( $request ) {
		$args = array(
			'limit'     => $this->get_rest_limit( $request->get_param( 'limit' ) ),
			'timeframe' => $request->get_param( 'timeframe' ),
		);

		return $this->get_cached_data( 'leaderboards/pilots', 'get_pilot_leaderboards', $args, 'prepare_public_leaderboards' );
	}

	/**
	 * Arguments for /leaderboards/pilots.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_leaderboards_args() {
		return array(
			'limit'     => array(
				'default'           => 10,
				'sanitize_callback' => array( $this, 'sanitize_public_limit' ),
				'maximum'           => 50,
			),
			'timeframe' => array(
				'default'           => 'month',
				'sanitize_callback' => array( $this, 'sanitize_public_timeframe' ),
			),
		);
	}
}
