<?php
/**
 * FlightLinq API shortcodes.
 *
 * @package FlightLinq_API
 * @since 1.4.0
 * @date 2026-05-23
 */

namespace FlightLinq_API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FlightLinq_API\Shortcodes class.
 *
 * Handles shortcodes used to display FlightLinq data on the frontend.
 * This redesigned version uses a modern approach with clean styles
 * and optimized cache handling.
 *
 * @since 1.4.0
 * @date 2026-05-23
 */
class Shortcodes {

	/**
	 * Unique class instance.
	 *
	 * @since 1.4.0
	 *
	 * @var Shortcodes
	 */
	private static $instance = null;

	/**
	 * Private constructor to enforce singleton usage.
	 *
	 * @since 1.4.0
	 */
	private function __construct() {
		add_shortcode( 'flightlinq_airline_summary', array( $this, 'render_airline_summary' ) );
		add_shortcode( 'flightlinq_recent_flights', array( $this, 'render_recent_flights' ) );
		add_shortcode( 'flightlinq_pilot_leaderboard', array( $this, 'render_pilot_leaderboard' ) );
		add_shortcode( 'flightlinq_fleet_by_type', array( $this, 'render_fleet_by_type' ) );
		add_shortcode( 'flightlinq_routes_by_hub', array( $this, 'render_routes_by_hub' ) );
		add_shortcode( 'flightlinq_routes_map', array( $this, 'render_routes_map' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
	}

	/**
	 * Enqueues frontend assets.
	 *
	 * Loads the frontend CSS on the frontend only, never in the admin.
	 *
	 * @since 1.4.0
	 * @date 2026-05-23
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets() {
		// Load only on the frontend.
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_style(
			'flightlinq-api-frontend',
			FLIGHTLINQ_API_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			FLIGHTLINQ_API_VERSION
		);
	}

	/**
	 * Returns the unique class instance.
	 *
	 * @since 1.4.0
	 *
	 * @return Shortcodes
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Normalizes a public shortcode limit.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param mixed $value   Value received from the shortcode attributes.
	 * @param int   $default Default value used when the limit is invalid.
	 *
	 * @return int Limit between 1 and 50.
	 */
	private function get_shortcode_limit( $value, $default = 10, $max = 50 ) {
		if ( ! is_numeric( $value ) ) {
			return $default;
		}

		$limit = (int) $value;

		if ( $limit < 1 ) {
			return $default;
		}

		return min( $limit, $max );
	}

	/**
	 * Normalizes a collection used by shortcodes.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param mixed $response Direct API response or response wrapped in data.
	 *
	 * @return array Normalized collection.
	 */
	private function normalize_shortcode_collection( $response ) {
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
	 * Unwraps a simple response that may be wrapped in data.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param mixed $response API response.
	 *
	 * @return mixed Unwrapped response when possible.
	 */
	private function unwrap_shortcode_response( $response ) {
		if ( is_array( $response ) && array_key_exists( 'data', $response ) && is_array( $response['data'] ) && ! $this->is_list_array( $response['data'] ) ) {
			return $response['data'];
		}

		return $response;
	}

	/**
	 * Checks whether an array is a numeric list.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param mixed $items Array to check.
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
	 * Shortcode: displays an airline summary.
	 *
	 * This shortcode displays the main airline information
	 * with a modern, clean style. Data is retrieved with caching
	 * to avoid repeated API calls.
	 *
	 * @since 1.4.0
	 * @since 1.4.1 Added the theme and layout attributes, plus granular control for each element.
	 * @since 1.4.2 Added the surface attribute to control the background independently from the theme.
	 * @since 1.4.3 Added theme="inherit" as the default value, and banner_url for a manual banner.
	 * @since 1.4.4 Improved automatic banner detection using BANNER-type media collections.
	 * @since 1.4.5 Reworked banner detection with recursive search to find BANNER-type media at any depth level.
	 * @since 1.4.6 Added banner_fit and banner_ratio attributes for better responsive banner display.
	 * @date 2026-05-23
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string Shortcode HTML.
	 */
	public function render_airline_summary( $atts ) {
		$atts = shortcode_atts(
			array(
				'theme'             => 'inherit',
				'surface'           => 'transparent',
				'layout'            => 'card',
				'show_logo'         => 'yes',
				'show_banner'       => 'yes',
				'banner_url'        => '',
				'banner_fit'        => 'contain',
				'banner_ratio'      => '3-1',
				'show_name'         => 'yes',
				'show_code'         => 'yes',
				'show_iata'         => 'yes',
				'show_headquarters' => 'yes',
				'show_founded'      => 'yes',
				'show_website'      => 'yes',
				'show_description'  => 'yes',
				'show_stats'        => 'yes',
				'show_total_pilots' => 'yes',
				'show_total_flights' => 'yes',
				'show_total_hours'  => 'yes',
				'show_average_rating' => 'yes',
			),
			$atts,
			'flightlinq_airline_summary'
		);

		// Convert attributes to booleans.
		$theme             = in_array( strtolower( $atts['theme'] ), array( 'inherit', 'auto', 'light', 'dark' ) ) ? strtolower( $atts['theme'] ) : 'inherit';
		$surface           = in_array( strtolower( $atts['surface'] ), array( 'transparent', 'card' ) ) ? strtolower( $atts['surface'] ) : 'transparent';
		$layout            = in_array( strtolower( $atts['layout'] ), array( 'card', 'compact' ) ) ? strtolower( $atts['layout'] ) : 'card';
		$banner_fit        = in_array( strtolower( $atts['banner_fit'] ), array( 'contain', 'cover' ) ) ? strtolower( $atts['banner_fit'] ) : 'contain';
		$banner_ratio      = in_array( strtolower( $atts['banner_ratio'] ), array( 'auto', '3-1' ) ) ? strtolower( $atts['banner_ratio'] ) : '3-1';
		$show_logo         = 'yes' === strtolower( $atts['show_logo'] );
		$show_banner       = 'yes' === strtolower( $atts['show_banner'] );
		$banner_url        = esc_url_raw( $atts['banner_url'] );
		$show_name         = 'yes' === strtolower( $atts['show_name'] );
		$show_code         = 'yes' === strtolower( $atts['show_code'] );
		$show_iata         = 'yes' === strtolower( $atts['show_iata'] );
		$show_headquarters = 'yes' === strtolower( $atts['show_headquarters'] );
		$show_founded      = 'yes' === strtolower( $atts['show_founded'] );
		$show_website      = 'yes' === strtolower( $atts['show_website'] );
		$show_description  = 'yes' === strtolower( $atts['show_description'] );
		$show_stats        = 'yes' === strtolower( $atts['show_stats'] );
		$show_total_pilots = 'yes' === strtolower( $atts['show_total_pilots'] );
		$show_total_flights = 'yes' === strtolower( $atts['show_total_flights'] );
		$show_total_hours  = 'yes' === strtolower( $atts['show_total_hours'] );
		$show_average_rating = 'yes' === strtolower( $atts['show_average_rating'] );

		// If show_stats is no, hide all statistics blocks.
		if ( ! $show_stats ) {
			$show_total_pilots = false;
			$show_total_flights = false;
			$show_total_hours = false;
			$show_average_rating = false;
		}

		// Retrieve data with caching.
		$data = flightlinq_api_get_cached_data( 'airline', 'get_airline' );

		// Handle errors.
		if ( is_wp_error( $data ) ) {
			return '<div class="flightlinq-shortcode flightlinq-error">' . esc_html__( 'FlightLinq data is temporarily unavailable.', 'flightlinq-api' ) . '</div>';
		}

		$data = $this->unwrap_shortcode_response( $data );

		if ( ! is_array( $data ) || empty( $data ) ) {
			return '<div class="flightlinq-shortcode flightlinq-empty">' . esc_html__( 'No airline data available.', 'flightlinq-api' ) . '</div>';
		}

		// Retrieve values safely.
		$logo                     = flightlinq_api_get_nested_value( $data, 'logo', '' );
		$name                     = flightlinq_api_get_nested_value( $data, 'name', '' );
		$code                     = flightlinq_api_get_nested_value( $data, 'code', '' );
		$iata_code                = flightlinq_api_get_nested_value( $data, 'iataCode', '' );
		$headquarters             = flightlinq_api_get_nested_value( $data, 'headquarters', '' );
		$founded                  = flightlinq_api_get_nested_value( $data, 'founded', '' );
		$description              = flightlinq_api_get_nested_value( $data, 'description', '' );
		$website                  = flightlinq_api_get_nested_value( $data, 'website', '' );
		$last_flight_submitted_at = $this->get_first_nested_value( $data, array( 'lastFlightSubmittedAt', 'stats.lastFlightSubmittedAt' ), '' );
		$last_flight_timestamp    = ! empty( $last_flight_submitted_at ) ? strtotime( (string) $last_flight_submitted_at ) : false;
		$recruiting               = $this->get_first_nested_value( $data, array( 'recruiting', 'isRecruiting' ), null );
		$requires_approval        = $this->get_first_nested_value( $data, array( 'requiresApproval', 'applicationRequiresApproval' ), null );
		$recruiting               = null !== $recruiting ? filter_var( $recruiting, FILTER_VALIDATE_BOOLEAN ) : null;
		$requires_approval        = null !== $requires_approval ? filter_var( $requires_approval, FILTER_VALIDATE_BOOLEAN ) : null;

		// Determine banner data using the new method.
		$banner_media = $this->get_airline_banner_media( $data, $banner_url );
		$banner_display = $banner_media['url'];
		$banner_alt = $banner_media['alt'];

		// Retrieve statistics.
		$stats = array(
			'totalPilots'   => $this->get_first_nested_value( $data, array( 'stats.totalPilots', 'totalPilots' ), 0 ),
			'totalFlights'  => $this->get_first_nested_value( $data, array( 'stats.totalFlights', 'totalFlights' ), 0 ),
			'totalHours'    => $this->get_first_nested_value( $data, array( 'stats.totalHours', 'totalHours' ), 0 ),
			'averageRating' => $this->get_first_nested_value( $data, array( 'stats.averageRating', 'stats.averageFlightRating', 'averageRating', 'averageFlightRating' ), 0 ),
		);

		// Determine the theme and surface classes.
		$theme_class = 'flightlinq-theme-' . $theme;
		$surface_class = 'flightlinq-surface-' . $surface;
		$layout_class = 'flightlinq-layout-' . $layout;
		$banner_fit_class = 'flightlinq-banner-fit-' . $banner_fit;
		$banner_ratio_class = 'flightlinq-banner-ratio-' . $banner_ratio;

		ob_start();
		?>
		<div class="flightlinq-shortcode flightlinq-shortcode--airline-summary <?php echo esc_attr( $theme_class ); ?> <?php echo esc_attr( $surface_class ); ?> <?php echo esc_attr( $layout_class ); ?>">
			<div class="flightlinq-card">
				<?php if ( $show_banner && ! empty( $banner_display ) ) : ?>
					<div class="flightlinq-card__banner <?php echo esc_attr( $banner_fit_class ); ?> <?php echo esc_attr( $banner_ratio_class ); ?>">
						<img src="<?php echo esc_url( $banner_display ); ?>" alt="<?php echo esc_attr( $banner_alt ); ?>" class="flightlinq-card__banner-image" />
					</div>
				<?php endif; ?>

				<?php if ( $show_logo && ! empty( $logo ) ) : ?>
					<div class="flightlinq-card__header">
						<img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( $name ); ?>" class="flightlinq-card__logo" />
						<div>
							<?php if ( $show_name && ! empty( $name ) ) : ?>
								<h2 class="flightlinq-card__title"><?php echo esc_html( $name ); ?></h2>
							<?php endif; ?>
							<?php if ( ( $show_code && ! empty( $code ) ) || ( $show_iata && ! empty( $iata_code ) ) ) : ?>
								<p class="flightlinq-card__subtitle">
									<?php if ( $show_code && ! empty( $code ) ) : ?>
										<?php esc_html_e( 'Code', 'flightlinq-api' ); ?>: <?php echo esc_html( $code ); ?>
									<?php endif; ?>
									<?php if ( $show_iata && ! empty( $iata_code ) ) : ?>
										<?php if ( $show_code && ! empty( $code ) ) : ?> | <?php endif; ?>
										<?php esc_html_e( 'IATA', 'flightlinq-api' ); ?>: <?php echo esc_html( $iata_code ); ?>
									<?php endif; ?>
								</p>
							<?php endif; ?>
						</div>
					</div>
				<?php else : ?>
					<div class="flightlinq-card__header">
						<div>
							<?php if ( $show_name && ! empty( $name ) ) : ?>
								<h2 class="flightlinq-card__title"><?php echo esc_html( $name ); ?></h2>
							<?php endif; ?>
							<?php if ( ( $show_code && ! empty( $code ) ) || ( $show_iata && ! empty( $iata_code ) ) ) : ?>
								<p class="flightlinq-card__subtitle">
									<?php if ( $show_code && ! empty( $code ) ) : ?>
										<?php esc_html_e( 'Code', 'flightlinq-api' ); ?>: <?php echo esc_html( $code ); ?>
									<?php endif; ?>
									<?php if ( $show_iata && ! empty( $iata_code ) ) : ?>
										<?php if ( $show_code && ! empty( $code ) ) : ?> | <?php endif; ?>
										<?php esc_html_e( 'IATA', 'flightlinq-api' ); ?>: <?php echo esc_html( $iata_code ); ?>
									<?php endif; ?>
								</p>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( $show_description && ! empty( $description ) ) : ?>
					<p class="flightlinq-card__description"><?php echo esc_html( $description ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $headquarters ) || ! empty( $founded ) || ( $show_website && ! empty( $website ) ) || false !== $last_flight_timestamp || null !== $recruiting || null !== $requires_approval ) : ?>
					<div class="flightlinq-card__meta">
						<?php if ( $show_headquarters && ! empty( $headquarters ) ) : ?>
							<div class="flightlinq-card__meta-item">
								<strong><?php esc_html_e( 'Headquarters', 'flightlinq-api' ); ?>:</strong> <?php echo esc_html( $headquarters ); ?>
							</div>
						<?php endif; ?>
						<?php if ( $show_founded && ! empty( $founded ) ) : ?>
							<div class="flightlinq-card__meta-item">
								<strong><?php esc_html_e( 'Founded on', 'flightlinq-api' ); ?>:</strong> <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $founded ) ) ); ?>
							</div>
						<?php endif; ?>
						<?php if ( $show_website && ! empty( $website ) ) : ?>
							<div class="flightlinq-card__meta-item">
								<strong><?php esc_html_e( 'Website', 'flightlinq-api' ); ?>:</strong> <a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $website ); ?></a>
							</div>
						<?php endif; ?>
						<?php if ( false !== $last_flight_timestamp ) : ?>
							<div class="flightlinq-card__meta-item">
								<strong><?php esc_html_e( 'Last flight', 'flightlinq-api' ); ?>:</strong> <?php echo esc_html( date_i18n( get_option( 'date_format' ), $last_flight_timestamp ) ); ?>
							</div>
						<?php endif; ?>
						<?php if ( null !== $recruiting ) : ?>
							<div class="flightlinq-card__meta-item">
								<strong><?php esc_html_e( 'Recruitment', 'flightlinq-api' ); ?>:</strong> <?php echo esc_html( $recruiting ? __( 'Open', 'flightlinq-api' ) : __( 'Closed', 'flightlinq-api' ) ); ?>
							</div>
						<?php endif; ?>
						<?php if ( null !== $requires_approval ) : ?>
							<div class="flightlinq-card__meta-item">
								<strong><?php esc_html_e( 'Application approval', 'flightlinq-api' ); ?>:</strong> <?php echo esc_html( $requires_approval ? __( 'Required', 'flightlinq-api' ) : __( 'Not required', 'flightlinq-api' ) ); ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( $show_total_pilots || $show_total_flights || $show_total_hours || $show_average_rating ) : ?>
					<div class="flightlinq-stats flightlinq-kpi-grid">
						<?php if ( $show_total_pilots ) : ?>
							<div class="flightlinq-stat flightlinq-kpi">
								<span class="flightlinq-stat__value"><?php echo esc_html( number_format_i18n( $stats['totalPilots'] ) ); ?></span>
								<span class="flightlinq-stat__label"><?php esc_html_e( 'Pilots', 'flightlinq-api' ); ?></span>
							</div>
						<?php endif; ?>
						<?php if ( $show_total_flights ) : ?>
							<div class="flightlinq-stat flightlinq-kpi">
								<span class="flightlinq-stat__value"><?php echo esc_html( number_format_i18n( $stats['totalFlights'] ) ); ?></span>
								<span class="flightlinq-stat__label"><?php esc_html_e( 'Flights', 'flightlinq-api' ); ?></span>
							</div>
						<?php endif; ?>
						<?php if ( $show_total_hours ) : ?>
							<div class="flightlinq-stat flightlinq-kpi">
								<span class="flightlinq-stat__value"><?php echo esc_html( number_format_i18n( $stats['totalHours'], 1 ) ); ?></span>
								<span class="flightlinq-stat__label"><?php esc_html_e( 'Hours', 'flightlinq-api' ); ?></span>
							</div>
						<?php endif; ?>
						<?php if ( $show_average_rating ) : ?>
							<div class="flightlinq-stat flightlinq-kpi">
								<span class="flightlinq-stat__value"><?php echo esc_html( $stats['averageRating'] ); ?>/5</span>
								<span class="flightlinq-stat__label"><?php esc_html_e( 'Rating', 'flightlinq-api' ); ?></span>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Recursively searches for BANNER-type media in the airline data.
	 *
	 * This method recursively scans all arrays and objects to find
	 * media entries that look like media objects with type and url.
	 *
	 * @since 1.4.5
	 * @date 2026-05-23
	 *
	 * @param mixed $data Data to scan, array or other value.
	 *
	 * @return array List of candidate media items as complete arrays.
	 */
	private function find_banner_media_recursive( $data ) {
		$candidates = array();

		if ( ! is_array( $data ) ) {
			return $candidates;
		}

		foreach ( $data as $key => $value ) {
			// If this is an associative array that looks like a media item.
			if ( is_array( $value ) && isset( $value['type'] ) && isset( $value['url'] ) ) {
				// Check the type case-insensitively.
				$type_upper = strtoupper( $value['type'] );
				if ( $type_upper === 'BANNER' ) {
					// Ignore non-public media.
					if ( isset( $value['isPublic'] ) && false === $value['isPublic'] ) {
						continue;
					}
					// Check that the URL is not empty.
					if ( ! empty( $value['url'] ) ) {
						$candidates[] = $value;
					}
				}
			}

			// Recurse into nested arrays.
			if ( is_array( $value ) ) {
				$candidates = array_merge( $candidates, $this->find_banner_media_recursive( $value ) );
			}
		}

		return $candidates;
	}

	/**
	 * Retrieves the airline banner data, URL and alt text.
	 *
	 * This method first checks the manual banner_url attribute, then simple fields
	 * from the API, and finally searches recursively through the full API response to find
	 * a BANNER-type media item.
	 *
	 * FlightLinq may return airline visuals as a media collection,
	 * with objects containing type, url, altText, sortOrder, isPublic, and so on.
	 *
	 * @since 1.4.5
	 * @date 2026-05-23
	 *
	 * @param array  $airline           Airline data from the API.
	 * @param string $manual_banner_url Manual banner URL from the banner_url attribute.
	 *
	 * @return array Array with 'url' and 'alt'.
	 */
	private function get_airline_banner_media( $airline, $manual_banner_url = '' ) {
		// Priority A: manual banner.
		if ( ! empty( $manual_banner_url ) ) {
			$name = flightlinq_api_get_nested_value( $airline, 'name', '' );
			$alt = ! empty( $name ) ? esc_attr( sprintf( __( '%s banner', 'flightlinq-api' ), $name ) ) : esc_attr( __( 'Airline banner', 'flightlinq-api' ) );
			return array(
				'url' => esc_url_raw( $manual_banner_url ),
				'alt' => $alt,
			);
		}

		// Priority B: direct simple fields (URL).
		$direct_url_fields = array( 'bannerUrl', 'coverUrl', 'headerImageUrl' );
		foreach ( $direct_url_fields as $field ) {
			$value = flightlinq_api_get_nested_value( $airline, $field, '' );
			if ( ! empty( $value ) ) {
				return array(
					'url' => esc_url_raw( $value ),
					'alt' => esc_attr( __( 'Airline banner', 'flightlinq-api' ) ),
				);
			}
		}

		// Priority C: simple fields that may be a string or an array with url.
		$flexible_fields = array( 'banner', 'cover', 'headerImage' );
		foreach ( $flexible_fields as $field ) {
			$value = flightlinq_api_get_nested_value( $airline, $field, '' );
			if ( ! empty( $value ) ) {
				// If this is a string, use it as the URL.
				if ( is_string( $value ) ) {
					return array(
						'url' => esc_url_raw( $value ),
						'alt' => esc_attr( __( 'Airline banner', 'flightlinq-api' ) ),
					);
				}
				// If this is an array with url, use url.
				if ( is_array( $value ) && isset( $value['url'] ) && ! empty( $value['url'] ) ) {
					return array(
						'url' => esc_url_raw( $value['url'] ),
						'alt' => esc_attr( __( 'Airline banner', 'flightlinq-api' ) ),
					);
				}
			}
		}

		// Priority D: recursive search for BANNER-type media.
		$candidates = $this->find_banner_media_recursive( $airline );

		if ( empty( $candidates ) ) {
			return array( 'url' => '', 'alt' => '' );
		}

		// Priority E: sort by ascending sortOrder when available.
		usort( $candidates, function( $a, $b ) {
			$sort_a = isset( $a['sortOrder'] ) ? (int) $a['sortOrder'] : 9999;
			$sort_b = isset( $b['sortOrder'] ) ? (int) $b['sortOrder'] : 9999;
			return $sort_a - $sort_b;
		});

		// Select the first media item.
		$selected = $candidates[0];

		// Priority F: determine the alt text.
		$alt = '';
		if ( ! empty( $selected['altText'] ) ) {
			$alt = esc_attr( $selected['altText'] );
		} elseif ( ! empty( $selected['title'] ) ) {
			$alt = esc_attr( $selected['title'] );
		} else {
			$name = flightlinq_api_get_nested_value( $airline, 'name', '' );
			$alt = ! empty( $name ) ? esc_attr( sprintf( __( '%s banner', 'flightlinq-api' ), $name ) ) : esc_attr( __( 'Airline banner', 'flightlinq-api' ) );
		}

		return array(
			'url' => esc_url_raw( $selected['url'] ),
			'alt' => $alt,
		);
	}

	/**
	 * Shortcode: displays the airline's latest flights.
	 *
	 * This shortcode displays a list of the latest approved flights with
	 * the ability to choose between a table or cards layout.
	 *
	 * @since 1.4.7
	 * @date 2026-05-27
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string Shortcode HTML.
	 */
	public function render_recent_flights( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'                        => 5,
				'theme'                        => 'inherit',
				'surface'                      => 'transparent',
				'layout'                       => 'table',
				'show_date'                    => 'yes',
				'show_pilot'                   => 'yes',
				'show_flight_number'           => 'yes',
				'show_route'                   => 'yes',
				'show_aircraft_type'           => 'yes',
				'show_aircraft_registration'    => 'yes',
				'show_block_time'              => 'yes',
				'show_flight_time'             => 'yes',
				'show_score'                   => 'yes',
				'show_landing_rate'            => 'yes',
			),
			$atts,
			'flightlinq_recent_flights'
		);

		// Convert and validate attributes.
		$limit = $this->get_shortcode_limit( $atts['limit'], 5 );
		$theme = in_array( strtolower( $atts['theme'] ), array( 'inherit', 'auto', 'light', 'dark' ) ) ? strtolower( $atts['theme'] ) : 'inherit';
		$surface = in_array( strtolower( $atts['surface'] ), array( 'transparent', 'card' ) ) ? strtolower( $atts['surface'] ) : 'transparent';
		$layout = in_array( strtolower( $atts['layout'] ), array( 'table', 'cards' ) ) ? strtolower( $atts['layout'] ) : 'table';

		$show_date = 'yes' === strtolower( $atts['show_date'] );
		$show_pilot = 'yes' === strtolower( $atts['show_pilot'] );
		$show_flight_number = 'yes' === strtolower( $atts['show_flight_number'] );
		$show_route = 'yes' === strtolower( $atts['show_route'] );
		$show_aircraft_type = 'yes' === strtolower( $atts['show_aircraft_type'] );
		$show_aircraft_registration = 'yes' === strtolower( $atts['show_aircraft_registration'] );
		$show_block_time = 'yes' === strtolower( $atts['show_block_time'] );
		$show_flight_time = 'yes' === strtolower( $atts['show_flight_time'] );
		$show_score = 'yes' === strtolower( $atts['show_score'] );
		$show_landing_rate = 'yes' === strtolower( $atts['show_landing_rate'] );

		// Retrieve data through the existing public function.
		$flights = flightlinq_api_get_cached_data(
			'flights/recent',
			'get_recent_flights',
			array( 'limit' => $limit )
		);

		// Handle errors.
		if ( is_wp_error( $flights ) ) {
			return $this->render_error_message();
		}

		$flights = $this->normalize_shortcode_collection( $flights );

		// Check whether flights are available.
		if ( empty( $flights ) ) {
			return $this->render_empty_message();
		}

		// Determine the theme and surface classes.
		$theme_class = 'flightlinq-theme-' . $theme;
		$surface_class = 'flightlinq-surface-' . $surface;
		$layout_class = 'flightlinq-layout-' . $layout;

		ob_start();
		?>
		<div class="flightlinq-shortcode flightlinq-shortcode--recent-flights <?php echo esc_attr( $theme_class ); ?> <?php echo esc_attr( $surface_class ); ?> <?php echo esc_attr( $layout_class ); ?>">
			<div class="flightlinq-recent-flights">
				<?php if ( 'table' === $layout ) : ?>
					<?php echo $this->render_flights_table( $flights, $show_date, $show_pilot, $show_flight_number, $show_route, $show_aircraft_type, $show_aircraft_registration, $show_block_time, $show_flight_time, $show_score, $show_landing_rate ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php else : ?>
					<?php echo $this->render_flights_cards( $flights, $show_date, $show_pilot, $show_flight_number, $show_route, $show_aircraft_type, $show_aircraft_registration, $show_block_time, $show_flight_time, $show_score, $show_landing_rate ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Displays flights as a table.
	 *
	 * @since 1.4.7
	 * @date 2026-05-27
	 *
	 * @param array  $flights                      List of flights.
	 * @param bool   $show_date                    Show the date.
	 * @param bool   $show_pilot                   Show the pilot.
	 * @param bool   $show_flight_number           Show the flight number.
	 * @param bool   $show_route                   Show the route.
	 * @param bool   $show_aircraft_type           Show the aircraft type.
	 * @param bool   $show_aircraft_registration    Show the registration.
	 * @param bool   $show_block_time              Show the block time.
	 * @param bool   $show_flight_time             Show the flight time.
	 * @param bool   $show_score                   Show the score.
	 * @param bool   $show_landing_rate            Show the descent rate.
	 *
	 * @return string Table HTML.
	 */
	private function render_flights_table( $flights, $show_date, $show_pilot, $show_flight_number, $show_route, $show_aircraft_type, $show_aircraft_registration, $show_block_time, $show_flight_time, $show_score, $show_landing_rate ) {
		ob_start();
		?>
		<div class="flightlinq-recent-flights__table-wrapper">
			<table class="flightlinq-table">
				<thead>
					<tr>
						<?php if ( $show_date ) : ?>
							<th><?php esc_html_e( 'Date', 'flightlinq-api' ); ?></th>
						<?php endif; ?>
						<?php if ( $show_pilot ) : ?>
							<th><?php esc_html_e( 'Pilot', 'flightlinq-api' ); ?></th>
						<?php endif; ?>
						<?php if ( $show_flight_number ) : ?>
							<th><?php esc_html_e( 'Flight', 'flightlinq-api' ); ?></th>
						<?php endif; ?>
						<?php if ( $show_route ) : ?>
							<th><?php esc_html_e( 'Route', 'flightlinq-api' ); ?></th>
						<?php endif; ?>
						<?php if ( $show_aircraft_type ) : ?>
							<th><?php esc_html_e( 'Aircraft', 'flightlinq-api' ); ?></th>
						<?php endif; ?>
						<?php if ( $show_aircraft_registration ) : ?>
							<th><?php esc_html_e( 'Reg.', 'flightlinq-api' ); ?></th>
						<?php endif; ?>
						<?php if ( $show_block_time ) : ?>
							<th><?php esc_html_e( 'Block time', 'flightlinq-api' ); ?></th>
						<?php endif; ?>
						<?php if ( $show_flight_time ) : ?>
							<th><?php esc_html_e( 'Flight time', 'flightlinq-api' ); ?></th>
						<?php endif; ?>
						<?php if ( $show_score ) : ?>
							<th><?php esc_html_e( 'Score', 'flightlinq-api' ); ?></th>
						<?php endif; ?>
						<?php if ( $show_landing_rate ) : ?>
							<th><?php esc_html_e( 'Landing rate', 'flightlinq-api' ); ?></th>
						<?php endif; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $flights as $flight ) : ?>
						<tr>
							<?php if ( $show_date ) : ?>
								<td><?php echo esc_html( $this->format_flight_date( $flight ) ); ?></td>
							<?php endif; ?>
							<?php if ( $show_pilot ) : ?>
								<td><?php echo esc_html( $this->get_flight_pilot( $flight ) ); ?></td>
							<?php endif; ?>
							<?php if ( $show_flight_number ) : ?>
								<td><?php echo esc_html( $this->get_flight_number( $flight ) ); ?></td>
							<?php endif; ?>
							<?php if ( $show_route ) : ?>
								<td><?php echo esc_html( $this->get_flight_route( $flight ) ); ?></td>
							<?php endif; ?>
							<?php if ( $show_aircraft_type ) : ?>
								<td><?php echo esc_html( $this->get_flight_aircraft_type( $flight ) ); ?></td>
							<?php endif; ?>
							<?php if ( $show_aircraft_registration ) : ?>
								<td><?php echo esc_html( $this->get_flight_aircraft_registration( $flight ) ); ?></td>
							<?php endif; ?>
							<?php if ( $show_block_time ) : ?>
								<td><?php echo esc_html( $this->format_flight_time( $flight, 'blockTimeMinutes' ) ); ?></td>
							<?php endif; ?>
							<?php if ( $show_flight_time ) : ?>
								<td><?php echo esc_html( $this->format_flight_time( $flight, 'flightTimeMinutes' ) ); ?></td>
							<?php endif; ?>
							<?php if ( $show_score ) : ?>
								<td><?php echo esc_html( $this->get_flight_score( $flight ) ); ?></td>
							<?php endif; ?>
							<?php if ( $show_landing_rate ) : ?>
								<td><?php echo esc_html( $this->get_flight_landing_rate( $flight ) ); ?></td>
							<?php endif; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Displays flights as cards.
	 *
	 * @since 1.4.7
	 * @date 2026-05-27
	 *
	 * @param array  $flights                      List of flights.
	 * @param bool   $show_date                    Show the date.
	 * @param bool   $show_pilot                   Show the pilot.
	 * @param bool   $show_flight_number           Show the flight number.
	 * @param bool   $show_route                   Show the route.
	 * @param bool   $show_aircraft_type           Show the aircraft type.
	 * @param bool   $show_aircraft_registration    Show the registration.
	 * @param bool   $show_block_time              Show the block time.
	 * @param bool   $show_flight_time             Show the flight time.
	 * @param bool   $show_score                   Show the score.
	 * @param bool   $show_landing_rate            Show the descent rate.
	 *
	 * @return string Cards HTML.
	 */
	private function render_flights_cards( $flights, $show_date, $show_pilot, $show_flight_number, $show_route, $show_aircraft_type, $show_aircraft_registration, $show_block_time, $show_flight_time, $show_score, $show_landing_rate ) {
		ob_start();
		?>
		<div class="flightlinq-flight-cards">
			<?php foreach ( $flights as $flight ) : ?>
				<div class="flightlinq-flight-card">
					<div class="flightlinq-flight-card__header">
						<?php if ( $show_flight_number ) : ?>
							<h3 class="flightlinq-flight-card__title"><?php echo esc_html( $this->get_flight_number( $flight ) ); ?></h3>
						<?php endif; ?>
						<?php if ( $show_date ) : ?>
							<div class="flightlinq-flight-card__meta"><?php echo esc_html( $this->format_flight_date( $flight ) ); ?></div>
						<?php endif; ?>
					</div>
					<div class="flightlinq-flight-card__grid">
						<?php if ( $show_pilot ) : ?>
							<div class="flightlinq-flight-card__item">
								<span class="flightlinq-flight-card__label"><?php esc_html_e( 'Pilot', 'flightlinq-api' ); ?></span>
								<span class="flightlinq-flight-card__value"><?php echo esc_html( $this->get_flight_pilot( $flight ) ); ?></span>
							</div>
						<?php endif; ?>
						<?php if ( $show_route ) : ?>
							<div class="flightlinq-flight-card__item">
								<span class="flightlinq-flight-card__label"><?php esc_html_e( 'Route', 'flightlinq-api' ); ?></span>
								<span class="flightlinq-flight-card__value"><?php echo esc_html( $this->get_flight_route( $flight ) ); ?></span>
							</div>
						<?php endif; ?>
						<?php if ( $show_aircraft_type ) : ?>
							<div class="flightlinq-flight-card__item">
								<span class="flightlinq-flight-card__label"><?php esc_html_e( 'Aircraft', 'flightlinq-api' ); ?></span>
								<span class="flightlinq-flight-card__value"><?php echo esc_html( $this->get_flight_aircraft_type( $flight ) ); ?></span>
							</div>
						<?php endif; ?>
						<?php if ( $show_aircraft_registration ) : ?>
							<div class="flightlinq-flight-card__item">
								<span class="flightlinq-flight-card__label"><?php esc_html_e( 'Reg.', 'flightlinq-api' ); ?></span>
								<span class="flightlinq-flight-card__value"><?php echo esc_html( $this->get_flight_aircraft_registration( $flight ) ); ?></span>
							</div>
						<?php endif; ?>
						<?php if ( $show_block_time ) : ?>
							<div class="flightlinq-flight-card__item">
								<span class="flightlinq-flight-card__label"><?php esc_html_e( 'Block time', 'flightlinq-api' ); ?></span>
								<span class="flightlinq-flight-card__value"><?php echo esc_html( $this->format_flight_time( $flight, 'blockTimeMinutes' ) ); ?></span>
							</div>
						<?php endif; ?>
						<?php if ( $show_flight_time ) : ?>
							<div class="flightlinq-flight-card__item">
								<span class="flightlinq-flight-card__label"><?php esc_html_e( 'Flight time', 'flightlinq-api' ); ?></span>
								<span class="flightlinq-flight-card__value"><?php echo esc_html( $this->format_flight_time( $flight, 'flightTimeMinutes' ) ); ?></span>
							</div>
						<?php endif; ?>
						<?php if ( $show_score ) : ?>
							<div class="flightlinq-flight-card__item">
								<span class="flightlinq-flight-card__label"><?php esc_html_e( 'Score', 'flightlinq-api' ); ?></span>
								<span class="flightlinq-flight-card__value"><?php echo esc_html( $this->get_flight_score( $flight ) ); ?></span>
							</div>
						<?php endif; ?>
						<?php if ( $show_landing_rate ) : ?>
							<div class="flightlinq-flight-card__item">
								<span class="flightlinq-flight-card__label"><?php esc_html_e( 'Landing rate', 'flightlinq-api' ); ?></span>
								<span class="flightlinq-flight-card__value"><?php echo esc_html( $this->get_flight_landing_rate( $flight ) ); ?></span>
							</div>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Formats a flight date.
	 *
	 * @since 1.4.7
	 * @date 2026-05-27
	 *
	 * @param array $flight Flight data.
	 *
	 * @return string Formatted date, or a dash if missing.
	 */
	private function format_flight_date( $flight ) {
		$created_at = flightlinq_api_get_nested_value( $flight, 'createdAt', '' );
		if ( empty( $created_at ) ) {
			return '—';
		}

		// Convert to a timestamp when this is an ISO string.
		$timestamp = is_numeric( $created_at ) ? $created_at : strtotime( $created_at );
		if ( false === $timestamp ) {
			return '—';
		}

		return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
	}

	/**
	 * Retrieves the pilot name.
	 *
	 * @since 1.4.7
	 * @date 2026-05-27
	 *
	 * @param array $flight Flight data.
	 *
	 * @return string Pilot name, or a dash if missing.
	 */
	private function get_flight_pilot( $flight ) {
		$pilot_name = flightlinq_api_get_nested_value( $flight, 'pilot.displayName', '' );
		return ! empty( $pilot_name ) ? $pilot_name : '—';
	}

	/**
	 * Retrieves the flight number.
	 *
	 * @since 1.4.7
	 * @date 2026-05-27
	 *
	 * @param array $flight Flight data.
	 *
	 * @return string Flight number, or a dash if missing.
	 */
	private function get_flight_number( $flight ) {
		$flight_number = flightlinq_api_get_nested_value( $flight, 'flightNumber', '' );
		return ! empty( $flight_number ) ? $flight_number : '—';
	}

	/**
	 * Retrieves the flight route.
	 *
	 * @since 1.4.7
	 * @date 2026-05-27
	 *
	 * @param array $flight Flight data.
	 *
	 * @return string Route, or a dash if missing.
	 */
	private function get_flight_route( $flight ) {
		$route = flightlinq_api_get_nested_value( $flight, 'route', '' );
		return ! empty( $route ) ? $route : '—';
	}

	/**
	 * Retrieves the aircraft type.
	 *
	 * @since 1.4.7
	 * @date 2026-05-27
	 *
	 * @param array $flight Flight data.
	 *
	 * @return string Aircraft type, or a dash if missing.
	 */
	private function get_flight_aircraft_type( $flight ) {
		$aircraft_type = flightlinq_api_get_nested_value( $flight, 'aircraftType', '' );
		return ! empty( $aircraft_type ) ? $aircraft_type : '—';
	}

	/**
	 * Retrieves the aircraft registration.
	 *
	 * @since 1.4.7
	 * @date 2026-05-27
	 *
	 * @param array $flight Flight data.
	 *
	 * @return string Registration, or a dash if missing.
	 */
	private function get_flight_aircraft_registration( $flight ) {
		$registration = flightlinq_api_get_nested_value( $flight, 'aircraftRegistration', '' );
		return ! empty( $registration ) ? $registration : '—';
	}

	/**
	 * Formats the flight time or block time.
	 *
	 * @since 1.4.7
	 * @date 2026-05-27
	 *
	 * @param array  $flight Flight data.
	 * @param string $field  Field to use, blockTimeMinutes or flightTimeMinutes.
	 *
	 * @return string Formatted time, for example 1h28, or a dash if missing.
	 */
	private function format_flight_time( $flight, $field ) {
		$minutes = flightlinq_api_get_nested_value( $flight, $field, 0 );
		if ( ! is_numeric( $minutes ) || $minutes <= 0 ) {
			return '—';
		}

		$hours = floor( $minutes / 60 );
		$mins = $minutes % 60;

		return sprintf( '%dh%02d', $hours, $mins );
	}

	/**
	 * Retrieves the flight score.
	 *
	 * @since 1.4.7
	 * @date 2026-05-27
	 *
	 * @param array $flight Flight data.
	 *
	 * @return string Score, or a dash if missing.
	 */
	private function get_flight_score( $flight ) {
		$score = flightlinq_api_get_nested_value( $flight, 'score', '' );
		if ( ! is_numeric( $score ) ) {
			return '—';
		}
		return (string) $score;
	}

	/**
	 * Retrieves the descent rate.
	 *
	 * @since 1.4.7
	 * @date 2026-05-27
	 *
	 * @param array $flight Flight data.
	 *
	 * @return string Formatted descent rate, or a dash if missing.
	 */
	private function get_flight_landing_rate( $flight ) {
		$landing_rate = flightlinq_api_get_nested_value( $flight, 'landingRate', '' );
		if ( ! is_numeric( $landing_rate ) ) {
			return '—';
		}
		return sprintf( '%d ft/min', (int) $landing_rate );
	}

	/**
	 * Displays an error message.
	 *
	 * @since 1.4.7
	 * @date 2026-05-27
	 *
	 * @return string Error message HTML.
	 */
	private function render_error_message() {
		ob_start();
		?>
		<div class="flightlinq-error">
			<?php esc_html_e( 'FlightLinq data is temporarily unavailable.', 'flightlinq-api' ); ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Displays an empty-list message.
	 *
	 * @since 1.4.7
	 * @date 2026-05-27
	 *
	 * @return string Empty-list message HTML.
	 */
	private function render_empty_message() {
		ob_start();
		?>
		<div class="flightlinq-empty">
			<?php esc_html_e( 'No recent flight available.', 'flightlinq-api' ); ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Shortcode: displays the fleet grouped by type.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string Shortcode HTML.
	 */
	public function render_fleet_by_type( $atts ) {
		$atts = shortcode_atts(
			array(
				'theme'         => 'inherit',
				'surface'       => 'transparent',
				'show_range'    => 'yes',
				'show_seats'    => 'yes',
				'show_hours'    => 'yes',
				'show_category' => 'yes',
			),
			$atts,
			'flightlinq_fleet_by_type'
		);

		$theme         = in_array( strtolower( $atts['theme'] ), array( 'inherit', 'auto', 'light', 'dark' ), true ) ? strtolower( $atts['theme'] ) : 'inherit';
		$surface       = in_array( strtolower( $atts['surface'] ), array( 'transparent', 'card' ), true ) ? strtolower( $atts['surface'] ) : 'transparent';
		$show_range    = 'yes' === $this->sanitize_yes_no_attribute( $atts['show_range'] );
		$show_seats    = 'yes' === $this->sanitize_yes_no_attribute( $atts['show_seats'] );
		$show_hours    = 'yes' === $this->sanitize_yes_no_attribute( $atts['show_hours'] );
		$show_category = 'yes' === $this->sanitize_yes_no_attribute( $atts['show_category'] );
		$fleet         = flightlinq_api_get_cached_data( 'fleet/types', 'get_fleet_types' );

		if ( flightlinq_api_is_error( $fleet ) ) {
			return $this->render_error_message();
		}

		$fleet = flightlinq_api_normalize_collection( $fleet );

		if ( empty( $fleet ) ) {
			return $this->render_shortcode_empty_message( __( 'No aircraft type available.', 'flightlinq-api' ) );
		}

		$fields = array(
			'type'         => array(
				'label' => __( 'Type', 'flightlinq-api' ),
				'paths' => array( 'type', 'aircraftType.type' ),
			),
			'icaoCode'     => array(
				'label' => __( 'ICAO code', 'flightlinq-api' ),
				'paths' => array( 'icaoCode', 'icao', 'aircraftType.icaoCode' ),
			),
			'name'         => array(
				'label' => __( 'Name', 'flightlinq-api' ),
				'paths' => array( 'name', 'aircraftType.name' ),
			),
			'manufacturer' => array(
				'label' => __( 'Manufacturer', 'flightlinq-api' ),
				'paths' => array( 'manufacturer', 'aircraftType.manufacturer' ),
			),
			'count'        => array(
				'label' => __( 'Count', 'flightlinq-api' ),
				'paths' => array( 'count', 'total', 'aircraftCount' ),
			),
			'active'       => array(
				'label' => __( 'Active', 'flightlinq-api' ),
				'paths' => array( 'active', 'isActive' ),
			),
		);

		if ( $show_category ) {
			$fields['category'] = array(
				'label' => __( 'Category', 'flightlinq-api' ),
				'paths' => array( 'category', 'aircraftType.category' ),
			);
		}

		if ( $show_range ) {
			$fields['maxRanke'] = array(
				'label' => __( 'Maximum range', 'flightlinq-api' ),
				'paths' => array( 'maxRanke', 'aircraftType.maxRanke' ),
			);
		}

		if ( $show_seats ) {
			$fields['maxSeats'] = array(
				'label' => __( 'Maximum seats', 'flightlinq-api' ),
				'paths' => array( 'maxSeats', 'aircraftType.maxSeats' ),
			);
		}

		if ( $show_hours ) {
			$fields['totalHoursFlown'] = array(
				'label' => __( 'Flight hours', 'flightlinq-api' ),
				'paths' => array( 'totalHoursFlown', 'aircraftType.totalHoursFlown' ),
			);
		}

		$visible_fields = $this->get_visible_shortcode_fields( $fleet, $fields );

		if ( empty( $visible_fields ) ) {
			return $this->render_shortcode_empty_message( __( 'No usable fleet data available.', 'flightlinq-api' ) );
		}

		return $this->render_shortcode_table(
			'flightlinq-shortcode--fleet-by-type flightlinq-theme-' . $theme . ' flightlinq-surface-' . $surface,
			$visible_fields,
			$fleet
		);
	}

	/**
	 * Shortcode: displays routes with optional hub filtering.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string Shortcode HTML.
	 */
	public function render_routes_by_hub( $atts ) {
		$atts = shortcode_atts(
			array(
				'hub'           => '',
				'departure'     => '',
				'arrival'       => '',
				'aircraft_type' => '',
				'search'        => '',
				'sort_by'       => 'flightNumber',
				'sort_order'    => 'asc',
				'limit'         => 50,
				'page'          => 1,
				'theme'         => 'inherit',
				'surface'       => 'transparent',
			),
			$atts,
			'flightlinq_routes_by_hub'
		);

		$hub           = $this->sanitize_route_hub( $atts['hub'] );
		$departure     = $this->sanitize_route_hub( $atts['departure'] );
		$arrival       = $this->sanitize_route_hub( $atts['arrival'] );
		$aircraft_type = $this->sanitize_route_hub( $atts['aircraft_type'] );
		$search        = sanitize_text_field( (string) $atts['search'] );
		$sort_by       = $this->sanitize_route_sort_by( $atts['sort_by'] );
		$sort_order    = $this->sanitize_sort_order( $atts['sort_order'] );
		$limit         = $this->get_shortcode_limit( $atts['limit'], 50, 200 );
		$page          = max( 1, absint( $atts['page'] ) );
		$theme         = in_array( strtolower( $atts['theme'] ), array( 'inherit', 'auto', 'light', 'dark' ), true ) ? strtolower( $atts['theme'] ) : 'inherit';
		$surface       = in_array( strtolower( $atts['surface'] ), array( 'transparent', 'card' ), true ) ? strtolower( $atts['surface'] ) : 'transparent';

		$departure_label = __( 'Departure', 'flightlinq-api' );

		if ( empty( $departure ) && ! empty( $hub ) ) {
			$departure       = $hub;
			$departure_label = __( 'Hub', 'flightlinq-api' );
		}

		$route_args = array(
			'search'       => $search,
			'departure'    => $departure,
			'arrival'      => $arrival,
			'aircraftType' => $aircraft_type,
			'sortBy'       => $sort_by,
			'sortOrder'    => $sort_order,
			'limit'        => $limit,
			'page'         => $page,
		);
		$routes     = flightlinq_api_get_cached_data(
			'routes',
			'get_routes',
			$route_args
		);

		if ( flightlinq_api_is_error( $routes ) ) {
			return $this->render_error_message();
		}

		$routes = flightlinq_api_normalize_collection( $routes );
		$routes = array_slice( $routes, 0, $limit );

		if ( empty( $routes ) ) {
			return $this->render_shortcode_empty_message( __( 'No FlightLinq route available.', 'flightlinq-api' ) );
		}

		$fields = array(
			'flightNumber'  => array(
				'label' => __( 'Flight', 'flightlinq-api' ),
				'paths' => array( 'flightNumber', 'number' ),
			),
			'type'          => array(
				'label' => __( 'Type', 'flightlinq-api' ),
				'paths' => array( 'type', 'routeType' ),
			),
			'departureIcao' => array(
				'label' => __( 'Departure', 'flightlinq-api' ),
				'paths' => array( 'departureIcao', 'departure.icaoCode', 'departure.icao' ),
			),
			'arrivalIcao'   => array(
				'label' => __( 'Arrival', 'flightlinq-api' ),
				'paths' => array( 'arrivalIcao', 'arrival.icaoCode', 'arrival.icao' ),
			),
			'distance'      => array(
				'label' => __( 'Distance', 'flightlinq-api' ),
				'paths' => array( 'distance', 'distanceNm' ),
			),
			'activeMonths'  => array(
				'label' => __( 'Active months', 'flightlinq-api' ),
				'paths' => array( 'activeMonths' ),
			),
			'description'   => array(
				'label' => __( 'Description', 'flightlinq-api' ),
				'paths' => array( 'description' ),
			),
			'aircraftTypes' => array(
				'label' => __( 'Aircraft', 'flightlinq-api' ),
				'paths' => array( 'aircraftTypes', 'aircraftType' ),
			),
		);

		$visible_fields = $this->get_visible_shortcode_fields( $routes, $fields );

		if ( empty( $visible_fields ) ) {
			return $this->render_shortcode_empty_message( __( 'No usable route data available.', 'flightlinq-api' ) );
		}

		$summary = array();

		if ( ! empty( $departure ) ) {
			$summary[ $departure_label ] = $departure;
		}
		if ( ! empty( $arrival ) ) {
			$summary[ __( 'Arrival', 'flightlinq-api' ) ] = $arrival;
		}
		if ( ! empty( $aircraft_type ) ) {
			$summary[ __( 'Aircraft', 'flightlinq-api' ) ] = $aircraft_type;
		}
		if ( ! empty( $search ) ) {
			$summary[ __( 'Search', 'flightlinq-api' ) ] = $search;
		}

		return $this->render_shortcode_table(
			'flightlinq-shortcode--routes-by-hub flightlinq-theme-' . $theme . ' flightlinq-surface-' . $surface,
			$visible_fields,
			$routes,
			$summary
		);
	}

	/**
	 * Shortcode: displays route map data as a map or table.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string Shortcode HTML.
	 */
	public function render_routes_map( $atts ) {
		$atts = shortcode_atts(
			array(
				'hub'           => '',
				'departure'     => '',
				'arrival'       => '',
				'aircraft_type' => '',
				'search'        => '',
				'sort_by'       => 'flightNumber',
				'sort_order'    => 'asc',
				'limit'         => 100,
				'layout'        => 'map',
				'table_view'    => 'compact',
				'provider'      => '',
				'height'        => '',
				'theme'         => 'inherit',
				'surface'       => 'transparent',
			),
			$atts,
			'flightlinq_routes_map'
		);

		$hub           = $this->sanitize_route_hub( $atts['hub'] );
		$departure     = $this->sanitize_route_hub( $atts['departure'] );
		$arrival       = $this->sanitize_route_hub( $atts['arrival'] );
		$aircraft_type = $this->sanitize_route_hub( $atts['aircraft_type'] );
		$search        = sanitize_text_field( (string) $atts['search'] );
		$sort_by       = $this->sanitize_route_sort_by( $atts['sort_by'] );
		$sort_order    = $this->sanitize_sort_order( $atts['sort_order'] );
		$limit         = $this->get_shortcode_limit( $atts['limit'], 100, 300 );
		$layout        = $this->sanitize_routes_map_layout( $atts['layout'] );
		$table_view    = $this->sanitize_routes_map_table_view( $atts['table_view'] );
		$provider      = $this->sanitize_map_provider( $atts['provider'] );
		$height        = $this->get_routes_map_height( $atts['height'] );
		$theme         = in_array( strtolower( $atts['theme'] ), array( 'inherit', 'auto', 'light', 'dark' ), true ) ? strtolower( $atts['theme'] ) : 'inherit';
		$surface       = in_array( strtolower( $atts['surface'] ), array( 'transparent', 'card' ), true ) ? strtolower( $atts['surface'] ) : 'transparent';

		if ( empty( $departure ) && ! empty( $hub ) ) {
			$departure = $hub;
		}

		if ( empty( $provider ) ) {
			$provider = $this->sanitize_map_provider( get_option( 'flightlinq_api_map_provider', 'openstreetmap' ) );
		}

		$route_args = array(
			'search'       => $search,
			'departure'    => $departure,
			'arrival'      => $arrival,
			'aircraftType' => $aircraft_type,
			'sortBy'       => $sort_by,
			'sortOrder'    => $sort_order,
		);
		$routes     = flightlinq_api_get_cached_data(
			'routes/map',
			'get_routes_map',
			$route_args
		);

		if ( flightlinq_api_is_error( $routes ) ) {
			return $this->render_error_message();
		}

		$routes = array_slice( array_values( flightlinq_api_normalize_collection( $routes ) ), 0, $limit );

		if ( 'table' === $layout ) {
			return $this->render_routes_map_table( $routes, $theme, $surface, $table_view );
		}

		if ( ! rest_sanitize_boolean( get_option( 'flightlinq_api_maps_enabled', true ) ) ) {
			return $this->render_routes_map_notice( __( 'Route mapping is disabled.', 'flightlinq-api' ), $routes, $theme, $surface );
		}

		$tile_layer = $this->get_routes_map_tile_layer( $provider );

		if ( empty( $tile_layer ) ) {
			return $this->render_routes_map_notice( __( 'Mapbox token is not configured.', 'flightlinq-api' ), $routes, $theme, $surface );
		}

		$map_payload = $this->prepare_routes_map_payload( $routes );

		if ( empty( $map_payload['routes'] ) ) {
			return $this->render_routes_map_notice( __( 'No route with available coordinates for this map.', 'flightlinq-api' ), $routes, $theme, $surface );
		}

		$this->enqueue_routes_map_assets();

		return $this->render_routes_map_canvas( $map_payload, $tile_layer, $height, $theme, $surface );
	}

	/**
	 * Loads the assets required for the Leaflet map.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @return void
	 */
	private function enqueue_routes_map_assets() {
		wp_enqueue_style(
			'flightlinq-leaflet',
			'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
			array(),
			'1.9.4'
		);

		wp_enqueue_script(
			'flightlinq-leaflet',
			'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
			array(),
			'1.9.4',
			true
		);

		wp_enqueue_script(
			'flightlinq-routes-map',
			FLIGHTLINQ_API_PLUGIN_URL . 'assets/js/routes-map.js',
			array( 'flightlinq-leaflet' ),
			FLIGHTLINQ_API_VERSION,
			true
		);

		wp_localize_script(
			'flightlinq-routes-map',
			'FLQRoutesMapI18n',
			array(
				'routeTitle'         => __( 'FlightLinq route', 'flightlinq-api' ),
				'route'              => _x( 'Route', 'Map popup label', 'flightlinq-api' ),
				'departure'          => __( 'Departure', 'flightlinq-api' ),
				'arrival'            => __( 'Arrival', 'flightlinq-api' ),
				'aircraftTypes'      => __( 'Aircraft', 'flightlinq-api' ),
				'distance'           => __( 'Distance', 'flightlinq-api' ),
				'description'        => __( 'Description', 'flightlinq-api' ),
				'noMapData'          => __( 'No usable map data.', 'flightlinq-api' ),
				'leafletUnavailable' => __( 'The Leaflet library could not be loaded.', 'flightlinq-api' ),
			)
		);
	}

	/**
	 * Sanitizes the routes map shortcode layout.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param mixed $layout Submitted layout.
	 *
	 * @return string Allowed layout.
	 */
	private function sanitize_routes_map_layout( $layout ) {
		$layout = strtolower( sanitize_text_field( (string) $layout ) );

		return in_array( $layout, array( 'map', 'table' ), true ) ? $layout : 'map';
	}

	/**
	 * Sanitizes the routes map table view.
	 *
	 * @since 1.7.2
	 * @date 2026-07-05
	 *
	 * @param mixed $table_view Submitted table view.
	 *
	 * @return string Allowed view.
	 */
	private function sanitize_routes_map_table_view( $table_view ) {
		$table_view = strtolower( sanitize_text_field( (string) $table_view ) );

		return in_array( $table_view, array( 'compact', 'full' ), true ) ? $table_view : 'compact';
	}

	/**
	 * Sanitizes the shortcode map provider.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param mixed $provider Submitted provider.
	 *
	 * @return string Allowed provider or empty string.
	 */
	private function sanitize_map_provider( $provider ) {
		$provider = strtolower( sanitize_text_field( (string) $provider ) );

		if ( '' === $provider ) {
			return '';
		}

		return in_array( $provider, array( 'openstreetmap', 'mapbox' ), true ) ? $provider : 'openstreetmap';
	}

	/**
	 * Returns the normalized map height.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param mixed $height Shortcode height.
	 *
	 * @return int Height in pixels.
	 */
	private function get_routes_map_height( $height ) {
		$height = '' === (string) $height ? get_option( 'flightlinq_api_map_default_height', 520 ) : $height;
		$height = absint( $height );

		if ( $height < 300 ) {
			return 300;
		}

		if ( $height > 1000 ) {
			return 1000;
		}

		return $height;
	}

	/**
	 * Renders the routes map shortcode table fallback.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array  $routes  Routes to display.
	 * @param string $theme   Visual theme.
	 * @param string $surface Visual surface.
	 *
	 * @return string Table HTML.
	 */
	private function render_routes_map_table( $routes, $theme, $surface, $table_view = 'compact' ) {
		if ( empty( $routes ) ) {
			return $this->render_shortcode_empty_message( __( 'No route available.', 'flightlinq-api' ) );
		}

		$fields = 'full' === $table_view ? $this->get_routes_map_table_fields() : $this->get_routes_map_compact_table_fields();

		$visible_fields = $this->get_visible_shortcode_fields( $routes, $fields );

		if ( empty( $visible_fields ) ) {
			return $this->render_shortcode_empty_message( __( 'No usable map data.', 'flightlinq-api' ) );
		}

		return $this->render_shortcode_table(
			'flightlinq-shortcode--routes-map flightlinq-routes-map--table flightlinq-routes-map--table-' . $table_view . ' flightlinq-theme-' . $theme . ' flightlinq-surface-' . $surface,
			$visible_fields,
			$routes
		);
	}

	/**
	 * Returns the compact routes map table columns.
	 *
	 * @since 1.7.2
	 * @date 2026-07-05
	 *
	 * @return array Compact table columns.
	 */
	private function get_routes_map_compact_table_fields() {
		return array(
			'flightNumber'  => array( 'label' => __( 'Flight', 'flightlinq-api' ), 'paths' => array( 'flightNumber', 'number' ) ),
			'departureIcao' => array( 'label' => __( 'Departure', 'flightlinq-api' ), 'paths' => array( 'departureIcao', 'departure.icaoCode', 'departure.icao' ) ),
			'arrivalIcao'   => array( 'label' => __( 'Arrival', 'flightlinq-api' ), 'paths' => array( 'arrivalIcao', 'arrival.icaoCode', 'arrival.icao' ) ),
			'destination'   => array(
				'label'    => __( 'Destination', 'flightlinq-api' ),
				'paths'    => array(),
				'callback' => array( $this, 'get_route_destination_label' ),
			),
			'aircraftTypes' => array( 'label' => __( 'Aircraft', 'flightlinq-api' ), 'paths' => array( 'aircraftTypes', 'aircraftType', 'aircraft' ) ),
			'distance'      => array( 'label' => __( 'Distance', 'flightlinq-api' ), 'paths' => array( 'distance', 'distanceNm' ) ),
		);
	}

	/**
	 * Returns a compact destination label for a route.
	 *
	 * @since 1.7.2
	 * @date 2026-07-05
	 *
	 * @param array $route Route source.
	 *
	 * @return string Destination lisible.
	 */
	private function get_route_destination_label( $route ) {
		$city    = $this->get_first_nested_value( $route, array( 'arrivalCity', 'arrival.city', 'arrival.cityName', 'arrivalAirport.city' ), '' );
		$country = $this->get_first_nested_value( $route, array( 'arrivalCountry', 'arrival.country', 'arrival.countryName', 'arrivalAirport.country' ), '' );
		$name    = $this->get_first_nested_value( $route, array( 'arrivalName', 'arrival.name', 'arrivalAirport.name' ), '' );

		$parts = array_filter( array( $city, $country ), array( $this, 'has_shortcode_value' ) );

		if ( ! empty( $parts ) ) {
			return implode( ', ', $parts );
		}

		return $this->has_shortcode_value( $name ) ? (string) $name : '—';
	}

	/**
	 * Returns the routes map table columns.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @return array Table columns.
	 */
	private function get_routes_map_table_fields() {
		return array(
			'flightNumber'       => array( 'label' => __( 'Flight', 'flightlinq-api' ), 'paths' => array( 'flightNumber', 'number' ) ),
			'departureIcao'      => array( 'label' => __( 'Departure', 'flightlinq-api' ), 'paths' => array( 'departureIcao', 'departure.icaoCode', 'departure.icao' ) ),
			'departureName'      => array( 'label' => __( 'Departure airport', 'flightlinq-api' ), 'paths' => array( 'departureName', 'departure.name', 'departureAirport.name' ) ),
			'departureCity'      => array( 'label' => __( 'Departure city', 'flightlinq-api' ), 'paths' => array( 'departureCity', 'departure.city', 'departure.cityName', 'departureAirport.city' ) ),
			'departureCountry'   => array( 'label' => __( 'Departure country', 'flightlinq-api' ), 'paths' => array( 'departureCountry', 'departure.country', 'departure.countryName', 'departureAirport.country' ) ),
			'arrivalIcao'        => array( 'label' => __( 'Arrival', 'flightlinq-api' ), 'paths' => array( 'arrivalIcao', 'arrival.icaoCode', 'arrival.icao' ) ),
			'arrivalName'        => array( 'label' => __( 'Arrival airport', 'flightlinq-api' ), 'paths' => array( 'arrivalName', 'arrival.name', 'arrivalAirport.name' ) ),
			'arrivalCity'        => array( 'label' => __( 'Arrival city', 'flightlinq-api' ), 'paths' => array( 'arrivalCity', 'arrival.city', 'arrival.cityName', 'arrivalAirport.city' ) ),
			'arrivalCountry'     => array( 'label' => __( 'Arrival country', 'flightlinq-api' ), 'paths' => array( 'arrivalCountry', 'arrival.country', 'arrival.countryName', 'arrivalAirport.country' ) ),
			'aircraftTypes'      => array( 'label' => __( 'Aircraft', 'flightlinq-api' ), 'paths' => array( 'aircraftTypes', 'aircraftType', 'aircraft' ) ),
			'distance'           => array( 'label' => __( 'Distance', 'flightlinq-api' ), 'paths' => array( 'distance', 'distanceNm' ) ),
			'description'        => array( 'label' => __( 'Description', 'flightlinq-api' ), 'paths' => array( 'description' ) ),
			'departureLatitude'  => array( 'label' => __( 'Dep. lat.', 'flightlinq-api' ), 'paths' => array( 'departureLatitude', 'departure.latitude', 'departure.lat' ) ),
			'departureLongitude' => array( 'label' => __( 'Dep. lon.', 'flightlinq-api' ), 'paths' => array( 'departureLongitude', 'departure.longitude', 'departure.lon', 'departure.lng' ) ),
			'arrivalLatitude'    => array( 'label' => __( 'Arr. lat.', 'flightlinq-api' ), 'paths' => array( 'arrivalLatitude', 'arrival.latitude', 'arrival.lat' ) ),
			'arrivalLongitude'   => array( 'label' => __( 'Arr. lon.', 'flightlinq-api' ), 'paths' => array( 'arrivalLongitude', 'arrival.longitude', 'arrival.lon', 'arrival.lng' ) ),
		);
	}

	/**
	 * Renders a routes map message with table fallback.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param string $message Message to display.
	 * @param array  $routes  Routes available for fallback.
	 * @param string $theme   Visual theme.
	 * @param string $surface Visual surface.
	 *
	 * @return string Message and fallback HTML.
	 */
	private function render_routes_map_notice( $message, $routes, $theme, $surface ) {
		ob_start();
		?>
		<div class="flightlinq-shortcode flightlinq-shortcode--routes-map flightlinq-routes-map flightlinq-routes-map--notice flightlinq-theme-<?php echo esc_attr( $theme ); ?> flightlinq-surface-<?php echo esc_attr( $surface ); ?>">
			<div class="flightlinq-empty"><?php echo esc_html( $message ); ?></div>
		</div>
		<?php
		return ob_get_clean() . $this->render_routes_map_table( $routes, $theme, $surface );
	}

	/**
	 * Renders the interactive map HTML container.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array $map_payload Filtered route data.
	 * @param array $tile_layer  Tile layer configuration.
	 * @param int   $height      Map height in pixels.
	 * @param string $theme      Visual theme.
	 * @param string $surface    Visual surface.
	 *
	 * @return string Map HTML.
	 */
	private function render_routes_map_canvas( $map_payload, $tile_layer, $height, $theme, $surface ) {
		$map_id  = wp_unique_id( 'flightlinq-routes-map-' );
		$data_id = $map_id . '-data';
		$data    = array(
			'tileLayer' => $tile_layer,
			'routes'    => $map_payload['routes'],
		);

		ob_start();
		?>
		<div class="flightlinq-shortcode flightlinq-shortcode--routes-map flightlinq-routes-map flightlinq-routes-map--map flightlinq-theme-<?php echo esc_attr( $theme ); ?> flightlinq-surface-<?php echo esc_attr( $surface ); ?>">
			<div class="flightlinq-routes-map-card">
				<div class="flightlinq-map-toolbar" aria-label="<?php echo esc_attr__( 'Map information', 'flightlinq-api' ); ?>">
					<div class="flightlinq-map-title"><?php esc_html_e( 'Routes map', 'flightlinq-api' ); ?></div>
					<div class="flightlinq-map-chips">
						<span class="flightlinq-map-chip"><?php printf( esc_html__( '%d routes', 'flightlinq-api' ), esc_html( count( $map_payload['routes'] ) ) ); ?></span>
						<span class="flightlinq-map-chip"><?php echo esc_html( $tile_layer['label'] ); ?></span>
					</div>
				</div>
				<div id="<?php echo esc_attr( $map_id ); ?>" class="flightlinq-leaflet-map flightlinq-leaflet-map--message" data-map-id="<?php echo esc_attr( $map_id ); ?>" data-routes-data="<?php echo esc_attr( $data_id ); ?>" data-map-height="<?php echo esc_attr( $height ); ?>" style="<?php echo esc_attr( '--flightlinq-map-height:' . $height . 'px;height:' . $height . 'px;min-height:300px;' ); ?>"><span class="flightlinq-leaflet-map__message"><?php esc_html_e( 'Initializing the FlightLinq map…', 'flightlinq-api' ); ?></span></div>
				<script type="application/json" id="<?php echo esc_attr( $data_id ); ?>"><?php echo wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
				<?php if ( $map_payload['skipped'] > 0 ) : ?>
					<p class="flightlinq-muted flightlinq-map-note">
						<?php printf( esc_html__( 'Routes skipped because of missing complete coordinates: %d.', 'flightlinq-api' ), esc_html( $map_payload['skipped'] ) ); ?>
					</p>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns the tile layer configuration.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param string $provider Map provider.
	 *
	 * @return array Tile layer configuration or empty array.
	 */
	private function get_routes_map_tile_layer( $provider ) {
		if ( 'mapbox' === $provider ) {
			$token = sanitize_text_field( (string) get_option( 'flightlinq_api_mapbox_token', '' ) );

			if ( '' === $token || 0 === stripos( $token, 'sk.' ) ) {
				return array();
			}

			$style = sanitize_text_field( (string) get_option( 'flightlinq_api_mapbox_style', 'mapbox/streets-v12' ) );

			if ( '' === $style ) {
				$style = 'mapbox/streets-v12';
			}

			$style = implode( '/', array_map( 'rawurlencode', explode( '/', trim( $style, '/' ) ) ) );

			return array(
				'provider'    => 'mapbox',
				'label'       => 'Mapbox',
				'url'         => 'https://api.mapbox.com/styles/v1/' . $style . '/tiles/256/{z}/{x}/{y}@2x?access_token=' . rawurlencode( $token ),
				'attribution' => '© Mapbox © OpenStreetMap',
			);
		}

		return array(
			'provider'    => 'openstreetmap',
			'label'       => 'OpenStreetMap',
			'url'         => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
			'attribution' => '© OpenStreetMap contributors',
		);
	}

	/**
	 * Prepares the minimal JSON data for Leaflet.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array $routes Normalized routes.
	 *
	 * @return array Map data and skipped route counter.
	 */
	private function prepare_routes_map_payload( $routes ) {
		$payload = array(
			'routes'  => array(),
			'skipped' => 0,
		);

		foreach ( $routes as $route ) {
			$route_payload = $this->prepare_routes_map_route( $route );

			if ( empty( $route_payload ) ) {
				$payload['skipped']++;
				continue;
			}

			$payload['routes'][] = $route_payload;
		}

		return $payload;
	}

	/**
	 * Prepares a single route for the Leaflet map.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array $route Route source.
	 *
	 * @return array Reduced route data or empty array.
	 */
	private function prepare_routes_map_route( $route ) {
		$departure = $this->get_map_airport_payload(
			$route,
			array(
				'icaoCode'  => array( 'departureIcao', 'departure.icaoCode', 'departure.icao' ),
				'name'      => array( 'departureName', 'departure.name', 'departureAirport.name' ),
				'city'      => array( 'departureCity', 'departure.city', 'departure.cityName', 'departureAirport.city' ),
				'country'   => array( 'departureCountry', 'departure.country', 'departure.countryName', 'departureAirport.country' ),
				'latitude'  => array( 'departureLatitude', 'departure.latitude', 'departure.lat' ),
				'longitude' => array( 'departureLongitude', 'departure.longitude', 'departure.lon', 'departure.lng' ),
			)
		);
		$arrival   = $this->get_map_airport_payload(
			$route,
			array(
				'icaoCode'  => array( 'arrivalIcao', 'arrival.icaoCode', 'arrival.icao' ),
				'name'      => array( 'arrivalName', 'arrival.name', 'arrivalAirport.name' ),
				'city'      => array( 'arrivalCity', 'arrival.city', 'arrival.cityName', 'arrivalAirport.city' ),
				'country'   => array( 'arrivalCountry', 'arrival.country', 'arrival.countryName', 'arrivalAirport.country' ),
				'latitude'  => array( 'arrivalLatitude', 'arrival.latitude', 'arrival.lat' ),
				'longitude' => array( 'arrivalLongitude', 'arrival.longitude', 'arrival.lon', 'arrival.lng' ),
			)
		);

		if ( null === $departure['latitude'] || null === $departure['longitude'] || null === $arrival['latitude'] || null === $arrival['longitude'] ) {
			return array();
		}

		$departure_icao = $departure['icaoCode'];
		$arrival_icao   = $arrival['icaoCode'];
		$label          = trim( $departure_icao . ' → ' . $arrival_icao );

		return array(
			'flightNumber'  => $this->get_map_payload_value( $route, array( 'flightNumber', 'number' ) ),
			'label'         => '' !== $label ? $label : __( 'FlightLinq route', 'flightlinq-api' ),
			'description'   => $this->get_map_payload_value( $route, array( 'description' ) ),
			'distance'      => $this->get_map_payload_value( $route, array( 'distance', 'distanceNm' ) ),
			'aircraftTypes' => $this->get_map_payload_value( $route, array( 'aircraftTypes', 'aircraftType', 'aircraft' ) ),
			'departure'     => $departure,
			'arrival'       => $arrival,
		);
	}

	/**
	 * Prepares airport information for the map.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array $route Route source.
	 * @param array $paths Airport field paths.
	 *
	 * @return array Reduced airport data.
	 */
	private function get_map_airport_payload( $route, $paths ) {
		return array(
			'icaoCode'  => $this->get_map_payload_value( $route, $paths['icaoCode'] ),
			'name'      => $this->get_map_payload_value( $route, $paths['name'] ),
			'city'      => $this->get_map_payload_value( $route, $paths['city'] ),
			'country'   => $this->get_map_payload_value( $route, $paths['country'] ),
			'latitude'  => $this->get_route_coordinate( $route, $paths['latitude'] ),
			'longitude' => $this->get_route_coordinate( $route, $paths['longitude'] ),
		);
	}

	/**
	 * Returns a usable numeric coordinate.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array $route Route source.
	 * @param array $paths Possible paths.
	 *
	 * @return float|null Coordinate or null.
	 */
	private function get_route_coordinate( $route, $paths ) {
		$value = $this->get_first_nested_value( $route, $paths, null );

		return is_numeric( $value ) ? (float) $value : null;
	}

	/**
	 * Returns a clean text value for map JSON.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array $route Route source.
	 * @param array $paths Possible paths.
	 *
	 * @return string Displayable value.
	 */
	private function get_map_payload_value( $route, $paths ) {
		$value = $this->get_first_nested_value( $route, $paths, '' );

		if ( ! $this->has_shortcode_value( $value ) ) {
			return '';
		}

		return wp_strip_all_tags( $this->format_shortcode_value( $value ) );
	}

	/**
	 * Sanitizes a hub attribute to keep a usable ICAO code.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param mixed $hub Raw hub attribute value.
	 *
	 * @return string Normalized hub code or empty string.
	 */
	private function sanitize_route_hub( $hub ) {
		$hub = strtoupper( sanitize_text_field( (string) $hub ) );
		$hub = preg_replace( '/[^A-Z0-9]/', '', $hub );

		return $hub;
	}

	/**
	 * Restricts route sorting to values supported by FlightLinq.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param mixed $sort_by Raw sort_by attribute value.
	 *
	 * @return string Valid sort field.
	 */
	private function sanitize_route_sort_by( $sort_by ) {
		$sort_by = sanitize_text_field( (string) $sort_by );
		$allowed = array( 'flightNumber', 'departure', 'arrival', 'distance' );

		return in_array( $sort_by, $allowed, true ) ? $sort_by : 'flightNumber';
	}

	/**
	 * Restricts sort order to values supported by FlightLinq.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param mixed $sort_order Raw sort_order attribute value.
	 *
	 * @return string Valid sort order.
	 */
	private function sanitize_sort_order( $sort_order ) {
		$sort_order = strtolower( sanitize_text_field( (string) $sort_order ) );

		return in_array( $sort_order, array( 'asc', 'desc' ), true ) ? $sort_order : 'asc';
	}

	/**
	 * Normalizes a yes/no display attribute.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param mixed $value Raw attribute value.
	 *
	 * @return string yes or no value.
	 */
	private function sanitize_yes_no_attribute( $value ) {
		return 'no' === strtolower( sanitize_text_field( (string) $value ) ) ? 'no' : 'yes';
	}

	/**
	 * Filters a route collection by departure or arrival hub.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array  $routes Route list.
	 * @param string $hub    Code hub ICAO optionnel.
	 *
	 * @return array Filtered routes.
	 */
	private function filter_routes_by_hub( $routes, $hub ) {
		if ( empty( $hub ) ) {
			return $routes;
		}

		return array_values(
			array_filter(
				$routes,
				function ( $route ) use ( $hub ) {
					$departure = strtoupper( (string) $this->get_first_nested_value( $route, array( 'departureIcao', 'departure.icaoCode', 'departure.icao' ) ) );
					$arrival   = strtoupper( (string) $this->get_first_nested_value( $route, array( 'arrivalIcao', 'arrival.icaoCode', 'arrival.icao' ) ) );

					return $hub === $departure || $hub === $arrival;
				}
			)
		);
	}

	/**
	 * Returns fields that exist at least once in a collection.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array $items  Collection to analyze.
	 * @param array $fields Field definitions.
	 *
	 * @return array Visible fields.
	 */
	private function get_visible_shortcode_fields( $items, $fields ) {
		$visible = array();

		foreach ( $fields as $key => $field ) {
			foreach ( $items as $item ) {
				$value = isset( $field['callback'] ) && is_callable( $field['callback'] )
					? call_user_func( $field['callback'], $item )
					: $this->get_first_nested_value( $item, $field['paths'], null );

				if ( $this->has_shortcode_value( $value ) ) {
					$visible[ $key ] = $field;
					break;
				}
			}
		}

		return $visible;
	}

	/**
	 * Retrieves the first available value from several paths.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param array $item    Source item.
	 * @param array $paths   Paths to test.
	 * @param mixed $default Default value.
	 *
	 * @return mixed Found value or default value.
	 */
	private function get_first_nested_value( $item, $paths, $default = '' ) {
		foreach ( $paths as $path ) {
			$value = flightlinq_api_get_nested_value( $item, $path, null );

			if ( $this->has_shortcode_value( $value ) ) {
				return $value;
			}
		}

		return $default;
	}

	/**
	 * Determines whether a value can be displayed in a shortcode.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param mixed $value Value to test.
	 *
	 * @return bool True if the value is usable.
	 */
	private function has_shortcode_value( $value ) {
		if ( is_bool( $value ) || is_numeric( $value ) ) {
			return true;
		}

		if ( is_array( $value ) ) {
			return ! empty( $value );
		}

		return '' !== trim( (string) $value );
	}

	/**
	 * Renders a clean HTML table for a FlightLinq collection.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param string $modifier_class Shortcode-specific CSS class.
	 * @param array  $fields         Column definitions.
	 * @param array  $items          Collection to display.
	 * @param array  $summary        Optional summary to display before the table.
	 *
	 * @return string Table HTML.
	 */
	private function render_shortcode_table( $modifier_class, $fields, $items, $summary = array() ) {
		ob_start();
		?>
		<div class="flightlinq-shortcode <?php echo esc_attr( $modifier_class ); ?>">
			<?php if ( ! empty( $summary ) ) : ?>
				<div class="flightlinq-shortcode__toolbar" aria-label="<?php echo esc_attr__( 'Active filters', 'flightlinq-api' ); ?>">
					<?php foreach ( $summary as $label => $value ) : ?>
						<span class="flightlinq-badge"><span class="flightlinq-muted"><?php echo esc_html( $label ); ?> :</span> <?php echo esc_html( $value ); ?></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<div class="flightlinq-table-wrapper">
				<table class="flightlinq-table">
					<thead>
						<tr>
							<?php foreach ( $fields as $field ) : ?>
								<th><?php echo esc_html( $field['label'] ); ?></th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $items as $item ) : ?>
							<tr>
								<?php foreach ( $fields as $field ) : ?>
									<?php
									$value = isset( $field['callback'] ) && is_callable( $field['callback'] )
						? call_user_func( $field['callback'], $item )
						: $this->get_first_nested_value( $item, $field['paths'], '' );
									?>
									<td><?php echo esc_html( $this->format_shortcode_value( $value ) ); ?></td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Formats a value before displaying it in a shortcode table.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string Readable value.
	 */
	private function format_shortcode_value( $value ) {
		if ( is_bool( $value ) ) {
			return $value ? __( 'Yes', 'flightlinq-api' ) : __( 'No', 'flightlinq-api' );
		}

		if ( is_array( $value ) ) {
			$values = array();

			foreach ( $value as $item ) {
				if ( is_array( $item ) ) {
					$values[] = (string) $this->get_first_nested_value( $item, array( 'name', 'type', 'icaoCode', 'code', 'id' ), '' );
				} elseif ( is_scalar( $item ) ) {
					$values[] = (string) $item;
				}
			}

			$values = array_filter( $values, 'strlen' );

			return ! empty( $values ) ? implode( ', ', $values ) : '—';
		}

		if ( ! $this->has_shortcode_value( $value ) ) {
			return '—';
		}

		return (string) $value;
	}

	/**
	 * Displays a custom empty message for a shortcode.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param string $message User-facing message.
	 *
	 * @return string Message HTML.
	 */
	private function render_shortcode_empty_message( $message ) {
		ob_start();
		?>
		<div class="flightlinq-empty">
			<?php echo esc_html( $message ); ?>
		</div>
		<?php
		return ob_get_clean();
	}
	/**
	 * Shortcode: displays the pilot leaderboard.
	 *
	 * @since 1.5.0
	 * @date 2026-05-27
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string Shortcode HTML.
	 */
	public function render_pilot_leaderboard( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'                     => 10,
				'timeframe'                 => 'month',
				'theme'                     => 'inherit',
				'surface'                   => 'transparent',
				'layout'                    => 'table',
				'show_rank'                 => 'yes',
				'show_name'                 => 'yes',
				'show_hours'                => 'yes',
				'show_total_flights'        => 'yes',
				'show_average_landing_rate' => 'yes',
			),
			$atts,
			'flightlinq_pilot_leaderboard'
		);

		$limit     = $this->get_shortcode_limit( $atts['limit'], 10, 50 );
		$timeframe = strtolower( sanitize_text_field( (string) $atts['timeframe'] ) );
		$timeframe = in_array( $timeframe, array( 'month', 'all' ), true ) ? $timeframe : 'month';
		$theme     = in_array( strtolower( $atts['theme'] ), array( 'inherit', 'auto', 'light', 'dark' ), true ) ? strtolower( $atts['theme'] ) : 'inherit';
		$surface   = in_array( strtolower( $atts['surface'] ), array( 'transparent', 'card' ), true ) ? strtolower( $atts['surface'] ) : 'transparent';
		$layout    = in_array( strtolower( $atts['layout'] ), array( 'table', 'cards', 'podium' ), true ) ? strtolower( $atts['layout'] ) : 'table';

		$show_rank                 = 'yes' === strtolower( $atts['show_rank'] );
		$show_name                 = 'yes' === strtolower( $atts['show_name'] );
		$show_hours                = 'yes' === strtolower( $atts['show_hours'] );
		$show_total_flights        = 'yes' === strtolower( $atts['show_total_flights'] );
		$show_average_landing_rate = 'yes' === strtolower( $atts['show_average_landing_rate'] );

		$pilots = flightlinq_api_get_cached_data(
			'leaderboards/pilots',
			'get_pilot_leaderboards',
			array(
				'limit'     => $limit,
				'timeframe' => $timeframe,
			)
		);

		if ( is_wp_error( $pilots ) ) {
			return $this->render_error_message();
		}

		$pilots = $this->normalize_shortcode_collection( $pilots );

		if ( empty( $pilots ) ) {
			return $this->render_pilot_empty_message();
		}

		$theme_class     = 'flightlinq-theme-' . $theme;
		$surface_class   = 'flightlinq-surface-' . $surface;
		$layout_class    = 'flightlinq-layout-' . $layout;
		$timeframe_label = 'month' === $timeframe ? __( 'This month', 'flightlinq-api' ) : __( 'All time', 'flightlinq-api' );

		ob_start();
		?>
		<div class="flightlinq-shortcode flightlinq-shortcode--pilot-leaderboard <?php echo esc_attr( $theme_class ); ?> <?php echo esc_attr( $surface_class ); ?> <?php echo esc_attr( $layout_class ); ?>">
			<div class="flightlinq-pilot-leaderboard">
				<div class="flightlinq-shortcode__toolbar">
					<span class="flightlinq-badge"><span class="flightlinq-muted"><?php esc_html_e( 'Period:', 'flightlinq-api' ); ?></span> <?php echo esc_html( $timeframe_label ); ?></span>
				</div>
				<?php
				if ( 'table' === $layout ) {
					echo $this->render_pilot_leaderboard_table( $pilots, $show_rank, $show_name, $show_hours, $show_total_flights, $show_average_landing_rate ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				} elseif ( 'cards' === $layout ) {
					echo $this->render_pilot_leaderboard_cards( $pilots, $show_rank, $show_name, $show_hours, $show_total_flights, $show_average_landing_rate ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				} else {
					echo $this->render_pilot_leaderboard_podium( $pilots, $show_rank, $show_name, $show_hours, $show_total_flights, $show_average_landing_rate ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Displays the pilot leaderboard as a table.
	 *
	 * @since 1.5.0
	 * @date 2026-05-27
	 *
	 * @param array $pilots                    List of pilots.
	 * @param bool  $show_rank                 Show the rank.
	 * @param bool  $show_name                 Show the name.
	 * @param bool  $show_hours                Show hours.
	 * @param bool  $show_total_flights        Show the number of flights.
	 * @param bool  $show_average_landing_rate Show the average landing rate.
	 *
	 * @return string Table HTML.
	 */
	private function render_pilot_leaderboard_table( $pilots, $show_rank, $show_name, $show_hours, $show_total_flights, $show_average_landing_rate ) {
		ob_start();
		?>
		<div class="flightlinq-pilot-leaderboard__table-wrapper">
			<table class="flightlinq-table">
				<thead>
					<tr>
						<?php if ( $show_rank ) : ?>
							<th><?php esc_html_e( 'Rank', 'flightlinq-api' ); ?></th>
						<?php endif; ?>
						<?php if ( $show_name ) : ?>
							<th><?php esc_html_e( 'Pilot', 'flightlinq-api' ); ?></th>
						<?php endif; ?>
						<?php if ( $show_hours ) : ?>
							<th><?php esc_html_e( 'Hours', 'flightlinq-api' ); ?></th>
						<?php endif; ?>
						<?php if ( $show_total_flights ) : ?>
							<th><?php esc_html_e( 'Flights', 'flightlinq-api' ); ?></th>
						<?php endif; ?>
						<?php if ( $show_average_landing_rate ) : ?>
							<th><?php esc_html_e( 'Average landing rate', 'flightlinq-api' ); ?></th>
						<?php endif; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $pilots as $index => $pilot ) : ?>
						<tr>
							<?php if ( $show_rank ) : ?>
								<td><?php echo esc_html( $this->get_pilot_rank( $pilot, $index ) ); ?></td>
							<?php endif; ?>
							<?php if ( $show_name ) : ?>
								<td><?php echo esc_html( $this->get_pilot_name( $pilot ) ); ?></td>
							<?php endif; ?>
							<?php if ( $show_hours ) : ?>
								<td><?php echo esc_html( $this->format_pilot_hours( $pilot ) ); ?></td>
							<?php endif; ?>
							<?php if ( $show_total_flights ) : ?>
								<td><?php echo esc_html( $this->format_pilot_total_flights( $pilot ) ); ?></td>
							<?php endif; ?>
							<?php if ( $show_average_landing_rate ) : ?>
								<td><?php echo esc_html( $this->format_pilot_average_landing_rate( $pilot ) ); ?></td>
							<?php endif; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Displays the pilot leaderboard as cards.
	 *
	 * @since 1.5.0
	 * @date 2026-05-27
	 *
	 * @param array $pilots                    List of pilots.
	 * @param bool  $show_rank                 Show the rank.
	 * @param bool  $show_name                 Show the name.
	 * @param bool  $show_hours                Show hours.
	 * @param bool  $show_total_flights        Show the number of flights.
	 * @param bool  $show_average_landing_rate Show the average landing rate.
	 *
	 * @return string Cards HTML.
	 */
	private function render_pilot_leaderboard_cards( $pilots, $show_rank, $show_name, $show_hours, $show_total_flights, $show_average_landing_rate ) {
		ob_start();
		?>
		<div class="flightlinq-leaderboard-cards">
			<?php foreach ( $pilots as $index => $pilot ) : ?>
				<div class="flightlinq-leaderboard-card">
					<div class="flightlinq-leaderboard-card__header">
						<?php if ( $show_rank ) : ?>
							<div class="flightlinq-leaderboard-card__rank"><?php echo esc_html( $this->get_pilot_rank( $pilot, $index ) ); ?></div>
						<?php endif; ?>
						<?php if ( $show_name ) : ?>
							<div class="flightlinq-leaderboard-card__name"><?php echo esc_html( $this->get_pilot_name( $pilot ) ); ?></div>
						<?php endif; ?>
					</div>
					<div class="flightlinq-leaderboard-card__grid">
						<?php if ( $show_hours ) : ?>
							<div class="flightlinq-leaderboard-card__item">
								<span class="flightlinq-leaderboard-card__label"><?php esc_html_e( 'Hours', 'flightlinq-api' ); ?></span>
								<span class="flightlinq-leaderboard-card__value"><?php echo esc_html( $this->format_pilot_hours( $pilot ) ); ?></span>
							</div>
						<?php endif; ?>
						<?php if ( $show_total_flights ) : ?>
							<div class="flightlinq-leaderboard-card__item">
								<span class="flightlinq-leaderboard-card__label"><?php esc_html_e( 'Flights', 'flightlinq-api' ); ?></span>
								<span class="flightlinq-leaderboard-card__value"><?php echo esc_html( $this->format_pilot_total_flights( $pilot ) ); ?></span>
							</div>
						<?php endif; ?>
						<?php if ( $show_average_landing_rate ) : ?>
							<div class="flightlinq-leaderboard-card__item">
								<span class="flightlinq-leaderboard-card__label"><?php esc_html_e( 'Average landing rate', 'flightlinq-api' ); ?></span>
								<span class="flightlinq-leaderboard-card__value"><?php echo esc_html( $this->format_pilot_average_landing_rate( $pilot ) ); ?></span>
							</div>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Displays the pilot leaderboard as a podium.
	 *
	 * @since 1.5.0
	 * @date 2026-05-27
	 *
	 * @param array $pilots                    List of pilots.
	 * @param bool  $show_rank                 Show the rank.
	 * @param bool  $show_name                 Show the name.
	 * @param bool  $show_hours                Show hours.
	 * @param bool  $show_total_flights        Show the number of flights.
	 * @param bool  $show_average_landing_rate Show the average landing rate.
	 *
	 * @return string Podium HTML and optionally the remaining cards.
	 */
	private function render_pilot_leaderboard_podium( $pilots, $show_rank, $show_name, $show_hours, $show_total_flights, $show_average_landing_rate ) {
		$top_three = array_slice( $pilots, 0, 3 );
		$others    = array_slice( $pilots, 3 );

		ob_start();
		?>
		<div class="flightlinq-pilot-leaderboard__podium">
			<?php foreach ( $top_three as $index => $pilot ) :
				$rank      = $index + 1;
				$rank_class = 'flightlinq-podium-card--rank-' . $rank;
				?>
				<div class="flightlinq-podium-card <?php echo esc_attr( $rank_class ); ?>">
					<?php if ( $show_rank ) : ?>
						<div class="flightlinq-podium-card__rank"><?php echo esc_html( '#' . $rank ); ?></div>
					<?php endif; ?>
					<?php if ( $show_name ) : ?>
						<div class="flightlinq-podium-card__name"><?php echo esc_html( $this->get_pilot_name( $pilot ) ); ?></div>
					<?php endif; ?>
					<div class="flightlinq-podium-card__stats">
						<?php if ( $show_hours ) : ?>
							<span class="flightlinq-podium-card__stat"><?php echo esc_html( $this->format_pilot_hours( $pilot ) ); ?></span>
						<?php endif; ?>
						<?php if ( $show_total_flights ) : ?>
							<span class="flightlinq-podium-card__stat"><?php echo esc_html( $this->format_pilot_total_flights( $pilot ) ); ?></span>
						<?php endif; ?>
						<?php if ( $show_average_landing_rate ) : ?>
							<span class="flightlinq-podium-card__stat"><?php echo esc_html( $this->format_pilot_average_landing_rate( $pilot ) ); ?></span>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php if ( ! empty( $others ) ) : ?>
			<div class="flightlinq-pilot-leaderboard__others">
				<?php echo $this->render_pilot_leaderboard_cards( $others, $show_rank, $show_name, $show_hours, $show_total_flights, $show_average_landing_rate ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns a formatted pilot rank.
	 *
	 * @since 1.5.0
	 * @date 2026-05-27
	 *
	 * @param array $pilot Pilot data.
	 * @param int   $index Index in the list, zero-based.
	 *
	 * @return string Formatted rank (#1, #2, ...).
	 */
	private function get_pilot_rank( $pilot, $index ) {
		$rank = flightlinq_api_get_nested_value( $pilot, 'rank', null );
		if ( ! is_numeric( $rank ) ) {
			$rank = $index + 1;
		}
		return '#' . (int) $rank;
	}

	/**
	 * Returns the pilot name.
	 *
	 * @since 1.5.0
	 * @date 2026-05-27
	 *
	 * @param array $pilot Pilot data.
	 *
	 * @return string Pilot name, or a dash if missing.
	 */
	private function get_pilot_name( $pilot ) {
		$name         = flightlinq_api_get_nested_value( $pilot, 'name', '' );
		$display_name = flightlinq_api_get_nested_value( $pilot, 'displayName', '' );
		if ( empty( $display_name ) ) {
			$display_name = flightlinq_api_get_nested_value( $pilot, 'pilot.displayName', '' );
		}

		if ( ! empty( $name ) ) {
			return $name;
		}
		if ( ! empty( $display_name ) ) {
			return $display_name;
		}
		return '—';
	}

	/**
	 * Formats a pilot's flight hours.
	 *
	 * @since 1.5.0
	 * @date 2026-05-27
	 *
	 * @param array $pilot Pilot data.
	 *
	 * @return string Formatted hours or dash.
	 */
	private function format_pilot_hours( $pilot ) {
		$hours = flightlinq_api_get_nested_value( $pilot, 'hours', null );
		if ( ! is_numeric( $hours ) ) {
			$hours = flightlinq_api_get_nested_value( $pilot, 'stats.hours', null );
		}
		if ( ! is_numeric( $hours ) ) {
			return '—';
		}
		return number_format_i18n( $hours );
	}

	/**
	 * Formats the total flight count.
	 *
	 * @since 1.5.0
	 * @date 2026-05-27
	 *
	 * @param array $pilot Pilot data.
	 *
	 * @return string Formatted flight count or dash.
	 */
	private function format_pilot_total_flights( $pilot ) {
		$total = flightlinq_api_get_nested_value( $pilot, 'totalFlights', null );
		if ( ! is_numeric( $total ) ) {
			$total = flightlinq_api_get_nested_value( $pilot, 'flights', null );
		}
		if ( ! is_numeric( $total ) ) {
			$total = flightlinq_api_get_nested_value( $pilot, 'stats.flights', null );
		}
		if ( ! is_numeric( $total ) ) {
			return '—';
		}
		return number_format_i18n( $total );
	}

	/**
	 * Formats the average landing rate.
	 *
	 * @since 1.5.0
	 * @date 2026-05-27
	 *
	 * @param array $pilot Pilot data.
	 *
	 * @return string Formatted landing rate or dash.
	 */
	private function format_pilot_average_landing_rate( $pilot ) {
		$rate = flightlinq_api_get_nested_value( $pilot, 'averageLandingRate', null );
		if ( ! is_numeric( $rate ) ) {
			$rate = flightlinq_api_get_nested_value( $pilot, 'stats.averageLandingRate', null );
		}
		if ( ! is_numeric( $rate ) ) {
			return '—';
		}
		return sprintf( '%d ft/min', (int) $rate );
	}

	/**
	 * Displays an empty-list message for the pilot leaderboard.
	 *
	 * @since 1.5.0
	 * @date 2026-05-27
	 *
	 * @return string Empty-list message HTML.
	 */
	private function render_pilot_empty_message() {
		ob_start();
		?>
		<div class="flightlinq-empty">
			<?php esc_html_e( 'No pilot leaderboard available.', 'flightlinq-api' ); ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
