<?php
/**
 * Admin FlightLinq API.
 *
 * @package FlightLinq_API
 * @since 1.0.0
 */

namespace FlightLinq_API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FlightLinq_API\Admin class
 *
 * Handles the administration page used to configure the API key.
 *
 * @since 1.0.0
 */
class Admin {

	/**
	 * Unique class instance.
	 *
	 * @since 1.0.0
	 *
	 * @var Admin
	 */
	private static $instance = null;

	/**
	 * Private constructor to enforce singleton usage.
	 *
	 * @since 1.0.0
	 * @since 1.0.5 Simplified by removing advanced diagnostics.
	 * @date 2026-05-23
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_flightlinq_connection_test', array( $this, 'ajax_connection_test' ) );
	}

	/**
	 * Returns the unique class instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Admin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Sanitizes the public REST API enabled value.
	 *
	 * Converts the value submitted by the checkbox to a strict boolean before
	 * storage. This option prepares public endpoint control without
	 * changing their behavior in this pass.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param mixed $value Value submitted from the settings screen.
	 *
	 * @return bool Normalized boolean value.
	 */
	private function sanitize_public_rest_enabled( $value ) {
		return rest_sanitize_boolean( $value );
	}

	/**
	 * Sanitizes the cache duration.
	 *
	 * @since 1.8.2
	 *
	 * @param mixed $value Duration submitted from the settings screen.
	 *
	 * @return int Duration in minutes, constrained between 1 and 1440.
	 */
	private function sanitize_cache_duration( $value ) {
		$duration = absint( $value );

		return min( 1440, max( 1, $duration ) );
	}

	/**
	 * Sanitizes the route mapping enabled value.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param mixed $value Value submitted from the settings screen.
	 *
	 * @return bool Normalized boolean value.
	 */
	private function sanitize_maps_enabled( $value ) {
		return rest_sanitize_boolean( $value );
	}

	/**
	 * Sanitizes the allowed map provider.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param mixed $value Provider submitted from the settings screen.
	 *
	 * @return string Allowed map provider.
	 */
	private function sanitize_map_provider( $value ) {
		$value = strtolower( sanitize_text_field( (string) $value ) );

		return in_array( $value, array( 'openstreetmap', 'mapbox' ), true ) ? $value : 'openstreetmap';
	}

	/**
	 * Sanitizes the public Mapbox token.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param mixed $value Public token submitted from the settings screen.
	 *
	 * @return string Sanitized public token.
	 */
	private function sanitize_mapbox_token( $value ) {
		$value = sanitize_text_field( (string) $value );

		if ( 0 === stripos( $value, 'sk.' ) ) {
			add_settings_error(
				'flightlinq_api_settings',
				'flightlinq_api_mapbox_secret_token',
				__( 'Secret Mapbox tokens are not allowed because map tokens are exposed to the browser. Use a public token beginning with pk.', 'flightlinq-api' ),
				'error'
			);

			return '';
		}

		return $value;
	}

	/**
	 * Sanitizes the Mapbox style.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param mixed $value Mapbox style submitted from the settings screen.
	 *
	 * @return string Normalized Mapbox style.
	 */
	private function sanitize_mapbox_style( $value ) {
		$value = sanitize_text_field( (string) $value );

		return '' !== $value ? $value : 'mapbox/streets-v12';
	}

	/**
	 * Sanitizes the default map height.
	 *
	 * @since 1.6.0
	 * @date 2026-07-04
	 *
	 * @param mixed $value Height submitted from the settings screen.
	 *
	 * @return int Height in pixels, constrained between 300 and 1000.
	 */
	private function sanitize_map_default_height( $value ) {
		$height = absint( $value );

		if ( $height < 300 ) {
			return 300;
		}

		if ( $height > 1000 ) {
			return 1000;
		}

		return $height;
	}

	/**
	 * Adds the administration menu.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Dedicated FlightLinq API menu with submenus.
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		// Main FlightLinq API menu.
		add_menu_page(
			__( 'FlightLinq API', 'flightlinq-api' ),
			__( 'FlightLinq API', 'flightlinq-api' ),
			'manage_options',
			'flightlinq-api',
			array( $this, 'render_settings_page' ),
			'dashicons-rest-api',
			58
		);

		// Settings submenu (default page).
		add_submenu_page(
			'flightlinq-api',
			__( 'FlightLinq API Settings', 'flightlinq-api' ),
			__( 'Settings', 'flightlinq-api' ),
			'manage_options',
			'flightlinq-api',
			array( $this, 'render_settings_page' )
		);

		// PHP help / examples submenu.
		add_submenu_page(
			'flightlinq-api',
			__( 'FlightLinq API PHP Help', 'flightlinq-api' ),
			__( 'PHP Help / Examples', 'flightlinq-api' ),
			'manage_options',
			'flightlinq-api-help',
			array( $this, 'render_help_page' )
		);

		// Shortcodes submenu.
		add_submenu_page(
			'flightlinq-api',
			__( 'FlightLinq API Shortcodes', 'flightlinq-api' ),
			__( 'Shortcodes', 'flightlinq-api' ),
			'manage_options',
			'flightlinq-api-shortcodes',
			array( $this, 'render_shortcodes_page' )
		);
	}

	/**
	 * Registers the settings.
	 *
	 * @since 1.0.0
	 * @since 1.6.0 Added the public REST API activation setting.
	 * @date 2026-07-04
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'flightlinq_api_settings',
			'flightlinq_api_key',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			'flightlinq_api_settings',
			'flightlinq_cache_duration',
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_cache_duration' ),
				'default'           => 30,
			)
		);

		register_setting(
			'flightlinq_api_settings',
			'flightlinq_enable_cache',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => true,
			)
		);

		register_setting(
			'flightlinq_api_settings',
			'flightlinq_api_public_rest_enabled',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( $this, 'sanitize_public_rest_enabled' ),
				'default'           => true,
			)
		);

		register_setting(
			'flightlinq_api_settings',
			'flightlinq_api_maps_enabled',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( $this, 'sanitize_maps_enabled' ),
				'default'           => true,
			)
		);

		register_setting(
			'flightlinq_api_settings',
			'flightlinq_api_map_provider',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_map_provider' ),
				'default'           => 'openstreetmap',
			)
		);

		register_setting(
			'flightlinq_api_settings',
			'flightlinq_api_mapbox_token',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_mapbox_token' ),
				'default'           => '',
			)
		);

		register_setting(
			'flightlinq_api_settings',
			'flightlinq_api_mapbox_style',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_mapbox_style' ),
				'default'           => 'mapbox/streets-v12',
			)
		);

		register_setting(
			'flightlinq_api_settings',
			'flightlinq_api_map_default_height',
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_map_default_height' ),
				'default'           => 520,
			)
		);
	}

	/**
	 * Enqueues the admin assets.
	 *
	 * @since 1.0.0
	 * @since 1.0.2 Added variables for diagnostic tests.
	 * @since 1.3.0 Loaded on plugin pages (top-level and help).
	 * @since 1.4.3 Loaded on the Shortcodes page.
	 * @date 2026-05-23
	 *
	 * @param string $hook_suffix Current page hook.
	 *
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( 'toplevel_page_flightlinq-api' !== $hook_suffix && 'flightlinq-api_page_flightlinq-api-help' !== $hook_suffix && 'flightlinq-api_page_flightlinq-api-shortcodes' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'flightlinq-api-admin',
			FLIGHTLINQ_API_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			FLIGHTLINQ_API_VERSION
		);

		wp_enqueue_script(
			'flightlinq-api-admin',
			FLIGHTLINQ_API_PLUGIN_URL . 'assets/js/admin.js',
			array(),
			FLIGHTLINQ_API_VERSION,
			true
		);

		wp_localize_script(
			'flightlinq-api-admin',
			'flightlinqApi',
			array(
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'flightlinq-clear-cache' ),
				'confirmClearCache' => __( 'Confirm cache clearing?', 'flightlinq-api' ),
				'cacheClearing'     => __( 'Clearing...', 'flightlinq-api' ),
				'cacheCleared'      => __( 'FlightLinq cache cleared successfully.', 'flightlinq-api' ),
				'cacheClearError'   => __( 'Unable to clear the FlightLinq cache.', 'flightlinq-api' ),
				'cacheClearButton'  => __( 'Clear cache', 'flightlinq-api' ),
				'error'             => __( 'An error occurred.', 'flightlinq-api' ),
				'diagnosticLoading' => __( 'Test in progress...', 'flightlinq-api' ),
			)
		);
	}

	/**
	 * Renders the settings page.
	 *
	 * @since 1.0.0
	 * @since 1.0.2 Removed the automatic test and added the diagnostic section.
	 * @since 1.1.0 Adapted to the dedicated FlightLinq API menu.
	 * @since 1.6.0 Added the public REST API setting and status.
	 * @date 2026-07-04
	 *
	 * @return void
	 */
	public function render_settings_page() {
		$api_key             = get_option( 'flightlinq_api_key', '' );
		$cache_duration      = get_option( 'flightlinq_cache_duration', 30 );
		$enable_cache        = get_option( 'flightlinq_enable_cache', true );
		$public_rest_enabled = get_option( 'flightlinq_api_public_rest_enabled', true );
		$maps_enabled        = get_option( 'flightlinq_api_maps_enabled', true );
		$map_provider        = get_option( 'flightlinq_api_map_provider', 'openstreetmap' );
		$mapbox_token        = get_option( 'flightlinq_api_mapbox_token', '' );
		$mapbox_style        = get_option( 'flightlinq_api_mapbox_style', 'mapbox/streets-v12' );
		$map_default_height  = get_option( 'flightlinq_api_map_default_height', 520 );
		$masked_key          = $this->get_masked_api_key( $api_key );
		?>
		<div class="wrap flightlinq-admin-page flightlinq-settings-page">
			<div class="flightlinq-help-container flightlinq-settings-container">
				<div class="flightlinq-settings-header">
					<h1 class="flightlinq-settings-header__title"><?php esc_html_e( 'FlightLinq API Settings', 'flightlinq-api' ); ?></h1>
					<p class="flightlinq-settings-header__subtitle">
						<?php esc_html_e( 'Configure the FlightLinq connection, cache, and integration options for your WordPress site.', 'flightlinq-api' ); ?>
					</p>
					<div class="flightlinq-settings-header__badge">
						<span class="flightlinq-badge flightlinq-badge--success"><?php esc_html_e( 'Server API active', 'flightlinq-api' ); ?></span>
					</div>
				</div>

				<?php settings_errors( 'flightlinq_api_settings' ); ?>

				<div class="flightlinq-settings-layout">
					<div class="flightlinq-settings-main">
						<form class="flightlinq-settings-form" method="post" action="options.php">
							<?php
							settings_fields( 'flightlinq_api_settings' );
							do_settings_sections( 'flightlinq_api_settings' );
							?>

							<div class="flightlinq-settings-card">
								<div class="flightlinq-settings-card__header">
									<h2 class="flightlinq-settings-card__title"><?php esc_html_e( 'FlightLinq API Connection', 'flightlinq-api' ); ?></h2>
									<p class="flightlinq-settings-card__description"><?php esc_html_e( 'Enter the Developer API key provided by FlightLinq. It remains stored server-side and must never be exposed publicly.', 'flightlinq-api' ); ?></p>
								</div>

								<div class="flightlinq-settings-card__content">
									<div class="flightlinq-settings-field">
										<label for="flightlinq_api_key"><?php esc_html_e( 'FlightLinq API Key', 'flightlinq-api' ); ?></label>
										<input type="password" id="flightlinq_api_key" name="flightlinq_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" />
										<p class="flightlinq-settings-help">
											<?php esc_html_e( 'The key usually starts with flq_live_', 'flightlinq-api' ); ?>
										</p>
										<?php if ( ! empty( $api_key ) ) : ?>
											<p class="flightlinq-settings-help">
												<?php printf( esc_html__( 'Configured key: %s', 'flightlinq-api' ), esc_html( $masked_key ) ); ?>
											</p>
										<?php endif; ?>
									</div>

									<div class="flightlinq-connection-test">
										<div class="flightlinq-connection-test__action">
											<button type="button" id="flightlinq-connection-test" class="flightlinq-button flightlinq-button--secondary">
												<?php esc_html_e( 'Test connection', 'flightlinq-api' ); ?>
											</button>
										</div>
										<div class="flightlinq-connection-test__result" id="flightlinq-connection-test-results"></div>
									</div>
								</div>
							</div>

							<div class="flightlinq-settings-card">
								<div class="flightlinq-settings-card__header">
									<h2 class="flightlinq-settings-card__title"><?php esc_html_e( 'API Cache', 'flightlinq-api' ); ?></h2>
									<p class="flightlinq-settings-card__description"><?php esc_html_e( 'The cache reduces calls to FlightLinq and improves site performance.', 'flightlinq-api' ); ?></p>
								</div>

								<div class="flightlinq-settings-card__content">
									<div class="flightlinq-settings-field">
										<label>
											<input type="hidden" name="flightlinq_enable_cache" value="0" />
											<input type="checkbox" id="flightlinq_enable_cache" name="flightlinq_enable_cache" value="1" <?php checked( $enable_cache, true ); ?> />
											<?php esc_html_e( 'Enable cache', 'flightlinq-api' ); ?>
										</label>
										<p class="flightlinq-settings-help">
											<?php esc_html_e( 'Check this to enable caching of API responses (recommended).', 'flightlinq-api' ); ?>
										</p>
									</div>

									<div class="flightlinq-settings-field">
										<label for="flightlinq_cache_duration"><?php esc_html_e( 'Cache duration (minutes)', 'flightlinq-api' ); ?></label>
										<input type="number" id="flightlinq_cache_duration" name="flightlinq_cache_duration" value="<?php echo esc_attr( $cache_duration ); ?>" min="1" max="1440" />
										<p class="flightlinq-settings-help">
											<?php esc_html_e( 'Duration for which API responses are cached.', 'flightlinq-api' ); ?>
										</p>
									</div>

									<div class="flightlinq-settings-actions">
										<button type="button" id="flightlinq-clear-cache" class="flightlinq-button flightlinq-button--warning">
											<?php esc_html_e( 'Clear cache', 'flightlinq-api' ); ?>
										</button>
										<div class="flightlinq-cache-message" aria-live="polite"></div>
									</div>
								</div>
							</div>

							<div class="flightlinq-settings-card">
								<div class="flightlinq-settings-card__header">
									<h2 class="flightlinq-settings-card__title"><?php esc_html_e( 'Public REST API', 'flightlinq-api' ); ?></h2>
									<p class="flightlinq-settings-card__description"><?php esc_html_e( 'Control public access to FlightLinq REST endpoints intended for external integrations.', 'flightlinq-api' ); ?></p>
								</div>

								<div class="flightlinq-settings-card__content">
									<div class="flightlinq-settings-field">
										<label>
											<input type="hidden" name="flightlinq_api_public_rest_enabled" value="0" />
											<input type="checkbox" id="flightlinq_api_public_rest_enabled" name="flightlinq_api_public_rest_enabled" value="1" <?php checked( $public_rest_enabled, true ); ?> />
											<?php esc_html_e( 'Enable the public REST API', 'flightlinq-api' ); ?>
										</label>
										<p class="flightlinq-settings-help">
											<?php esc_html_e( 'Expose filtered REST data for widgets, automations, or dashboards. The FlightLinq API key remains strictly server-side.', 'flightlinq-api' ); ?>
										</p>
										<p class="flightlinq-settings-help">
											<strong><?php esc_html_e( 'Public REST API:', 'flightlinq-api' ); ?></strong>
											<?php
											if ( $public_rest_enabled ) {
												esc_html_e( 'Enabled', 'flightlinq-api' );
											} else {
												esc_html_e( 'Disabled', 'flightlinq-api' );
											}
											?>
										</p>
									</div>
								</div>
							</div>

							<div class="flightlinq-settings-card">
								<div class="flightlinq-settings-card__header">
									<h2 class="flightlinq-settings-card__title"><?php esc_html_e( 'Route mapping', 'flightlinq-api' ); ?></h2>
									<p class="flightlinq-settings-card__description"><?php esc_html_e( 'Configure the interactive display of the [flightlinq_routes_map] shortcode with Leaflet, OpenStreetMap, or Mapbox.', 'flightlinq-api' ); ?></p>
								</div>

								<div class="flightlinq-settings-card__content">
									<div class="flightlinq-settings-field">
										<label>
											<input type="hidden" name="flightlinq_api_maps_enabled" value="0" />
											<input type="checkbox" id="flightlinq_api_maps_enabled" name="flightlinq_api_maps_enabled" value="1" <?php checked( $maps_enabled, true ); ?> />
											<?php esc_html_e( 'Enable route mapping', 'flightlinq-api' ); ?>
										</label>
										<p class="flightlinq-settings-help">
											<?php esc_html_e( 'Enables route display on an interactive map in the [flightlinq_routes_map] shortcode.', 'flightlinq-api' ); ?>
										</p>
									</div>

									<div class="flightlinq-settings-field">
										<label for="flightlinq_api_map_provider"><?php esc_html_e( 'Map provider', 'flightlinq-api' ); ?></label>
										<select id="flightlinq_api_map_provider" name="flightlinq_api_map_provider">
											<option value="openstreetmap" <?php selected( $map_provider, 'openstreetmap' ); ?>><?php esc_html_e( 'OpenStreetMap', 'flightlinq-api' ); ?></option>
											<option value="mapbox" <?php selected( $map_provider, 'mapbox' ); ?>><?php esc_html_e( 'Mapbox', 'flightlinq-api' ); ?></option>
										</select>
										<p class="flightlinq-settings-help">
											<?php esc_html_e( 'OpenStreetMap works without an API key. Mapbox requires a public token configured below.', 'flightlinq-api' ); ?>
										</p>
									</div>

									<div class="flightlinq-settings-field">
										<label for="flightlinq_api_mapbox_token"><?php esc_html_e( 'Public Mapbox token', 'flightlinq-api' ); ?></label>
										<input type="text" id="flightlinq_api_mapbox_token" name="flightlinq_api_mapbox_token" value="<?php echo esc_attr( $mapbox_token ); ?>" class="regular-text" autocomplete="off" />
										<p class="flightlinq-settings-help">
											<?php esc_html_e( 'Public Mapbox token used in the browser. Do not enter a secret token.', 'flightlinq-api' ); ?>
										</p>
									</div>

									<div class="flightlinq-settings-field">
										<label for="flightlinq_api_mapbox_style"><?php esc_html_e( 'Mapbox style', 'flightlinq-api' ); ?></label>
										<input type="text" id="flightlinq_api_mapbox_style" name="flightlinq_api_mapbox_style" value="<?php echo esc_attr( $mapbox_style ); ?>" class="regular-text" />
										<p class="flightlinq-settings-help">
											<?php esc_html_e( 'Style used only with Mapbox, for example mapbox/streets-v12.', 'flightlinq-api' ); ?>
										</p>
									</div>

									<div class="flightlinq-settings-field">
										<label for="flightlinq_api_map_default_height"><?php esc_html_e( 'Default map height (px)', 'flightlinq-api' ); ?></label>
										<input type="number" id="flightlinq_api_map_default_height" name="flightlinq_api_map_default_height" value="<?php echo esc_attr( $map_default_height ); ?>" min="300" max="1000" />
										<p class="flightlinq-settings-help">
											<?php esc_html_e( 'Height applied to maps when the height attribute is missing. Allowed value: 300 to 1000 pixels.', 'flightlinq-api' ); ?>
										</p>
									</div>
								</div>
							</div>
							<div class="flightlinq-settings-card flightlinq-settings-card--actions">
								<div class="flightlinq-settings-card__header">
									<h2 class="flightlinq-settings-card__title"><?php esc_html_e( 'Actions', 'flightlinq-api' ); ?></h2>
									<p class="flightlinq-settings-card__description"><?php esc_html_e( 'Save the plugin settings.', 'flightlinq-api' ); ?></p>
								</div>

								<div class="flightlinq-settings-card__content">
									<div class="flightlinq-settings-actions">
										<button type="submit" class="flightlinq-button flightlinq-button--primary">
											<?php esc_html_e( 'Save changes', 'flightlinq-api' ); ?>
										</button>
									</div>
								</div>
							</div>
						</form>
					</div>

					<aside class="flightlinq-settings-sidebar">
						<div class="flightlinq-settings-sidebar-card">
							<h3 class="flightlinq-settings-sidebar-card__title"><?php esc_html_e( 'Plugin status', 'flightlinq-api' ); ?></h3>

							<div class="flightlinq-settings-sidebar-card__content">
								<ul class="flightlinq-status-list">
									<li class="flightlinq-status-item">
										<span class="flightlinq-status-label"><?php esc_html_e( 'Version', 'flightlinq-api' ); ?></span>
										<span class="flightlinq-status-value"><?php echo esc_html( FLIGHTLINQ_API_VERSION ); ?></span>
									</li>
									<li class="flightlinq-status-item">
										<span class="flightlinq-status-label"><?php esc_html_e( 'API URL', 'flightlinq-api' ); ?></span>
										<span class="flightlinq-status-value">api.flightlinq.com</span>
									</li>
									<li class="flightlinq-status-item">
										<span class="flightlinq-status-label"><?php esc_html_e( 'Public PHP functions', 'flightlinq-api' ); ?></span>
										<span class="flightlinq-status-value">
											<?php
											if ( function_exists( 'flightlinq_api_get_airline' ) ) {
												echo '<span class="flightlinq-status-badge flightlinq-status-badge--success">' . esc_html__( 'Available', 'flightlinq-api' ) . '</span>';
											} else {
												echo '<span class="flightlinq-status-badge flightlinq-status-badge--error">' . esc_html__( 'Unavailable', 'flightlinq-api' ) . '</span>';
											}
											?>
										</span>
									</li>
									<li class="flightlinq-status-item">
										<span class="flightlinq-status-label"><?php esc_html_e( 'Shortcodes', 'flightlinq-api' ); ?></span>
										<span class="flightlinq-status-value">
											<span class="flightlinq-status-badge flightlinq-status-badge--success"><?php esc_html_e( '6 available', 'flightlinq-api' ); ?></span>
										</span>
									</li>
									<li class="flightlinq-status-item">
										<span class="flightlinq-status-label"><?php esc_html_e( 'Cache', 'flightlinq-api' ); ?></span>
										<span class="flightlinq-status-value">
											<?php
											if ( $enable_cache ) {
												echo '<span class="flightlinq-status-badge flightlinq-status-badge--success">' . esc_html__( 'Enabled', 'flightlinq-api' ) . '</span>';
											} else {
												echo '<span class="flightlinq-status-badge flightlinq-status-badge--warning">' . esc_html__( 'Disabled', 'flightlinq-api' ) . '</span>';
											}
											?>
										</span>
									</li>
									<li class="flightlinq-status-item">
										<span class="flightlinq-status-label"><?php esc_html_e( 'Public REST API', 'flightlinq-api' ); ?></span>
										<span class="flightlinq-status-value">
											<?php
											if ( $public_rest_enabled ) {
												echo '<span class="flightlinq-status-badge flightlinq-status-badge--success">' . esc_html__( 'Enabled', 'flightlinq-api' ) . '</span>';
											} else {
												echo '<span class="flightlinq-status-badge flightlinq-status-badge--warning">' . esc_html__( 'Disabled', 'flightlinq-api' ) . '</span>';
											}
											?>
										</span>
									</li>
									<li class="flightlinq-status-item">
										<span class="flightlinq-status-label"><?php esc_html_e( 'API key', 'flightlinq-api' ); ?></span>
										<span class="flightlinq-status-value">
											<?php
											if ( ! empty( $api_key ) ) {
												echo '<span class="flightlinq-status-badge flightlinq-status-badge--success">' . esc_html__( 'Configured', 'flightlinq-api' ) . '</span>';
											} else {
												echo '<span class="flightlinq-status-badge flightlinq-status-badge--warning">' . esc_html__( 'Not configured', 'flightlinq-api' ) . '</span>';
											}
											?>
										</span>
									</li>
								</ul>
							</div>
						</div>

						<div class="flightlinq-settings-sidebar-card">
							<h3 class="flightlinq-settings-sidebar-card__title"><?php esc_html_e( 'Quick help', 'flightlinq-api' ); ?></h3>

							<div class="flightlinq-settings-sidebar-card__content">
								<p class="description">
									<?php esc_html_e( 'Display FlightLinq data with shortcodes or integrate it using the public PHP functions.', 'flightlinq-api' ); ?>
								</p>
								<p class="description">
									<code>flightlinq_api_get_airline()</code>
								</p>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=flightlinq-api-help' ) ); ?>" class="flightlinq-button flightlinq-button--secondary">
									<?php esc_html_e( 'View PHP help', 'flightlinq-api' ); ?>
								</a>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=flightlinq-api-shortcodes' ) ); ?>" class="flightlinq-button flightlinq-button--secondary">
									<?php esc_html_e( 'View shortcodes', 'flightlinq-api' ); ?>
								</a>
							</div>
						</div>
					</aside>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Masks an API key for secure display.
	 *
	 * @since 1.3.4
	 * @date 2026-05-23
	 *
	 * @param string $api_key API key to mask.
	 *
	 * @return string Masked key or "Not configured".
	 */
	private function get_masked_api_key( $api_key ) {
		$api_key = trim( $api_key );

		if ( empty( $api_key ) ) {
			return __( 'Not configured', 'flightlinq-api' );
		}

		// Show the first 12 characters and the last 4 characters.
		$length = strlen( $api_key );
		if ( $length <= 16 ) {
			return __( 'Present', 'flightlinq-api' );
		}

		$prefix = substr( $api_key, 0, 12 );
		$suffix = substr( $api_key, -4 );

		return $prefix . '...' . $suffix;
	}

	/**
	 * AJAX handler for the simple connection test.
	 *
	 * @since 1.0.5
	 * @date 2026-05-23
	 *
	 * @return void
	 */
	public function ajax_connection_test() {
		check_ajax_referer( 'flightlinq-clear-cache', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'flightlinq-api' ) );
		}

		$client = Client::get_instance();
		$result = $client->get_airline();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( __( 'Connection failed', 'flightlinq-api' ) );
		}

		wp_send_json_success( __( 'Connection OK', 'flightlinq-api' ) );
	}

	/**
	 * Renders a code example inside a clean card.
	 *
	 * @since 1.2.0
	 * @since 1.3.0 Refactored with a new visual structure.
	 * @date 2026-05-23
	 *
	 * @param string $id          Unique block identifier.
	 * @param string $title       Example title.
	 * @param string $description Short example description.
	 * @param string $code        PHP code to display (escaped).
	 *
	 * @return void
	 */
	private function render_code_example( $id, $title, $description, $code ) {
		?>
		<section id="<?php echo esc_attr( $id ); ?>" class="flightlinq-help-card">
			<div class="flightlinq-help-card__header">
				<div>
					<h2 class="flightlinq-help-card__title"><?php echo esc_html( $title ); ?></h2>
					<p class="flightlinq-help-card__description"><?php echo esc_html( $description ); ?></p>
				</div>
			</div>

			<div class="flightlinq-code-block">
				<div class="flightlinq-code-toolbar">
					<span class="flightlinq-code-language">PHP</span>
					<button type="button" class="flightlinq-copy-code" data-target="<?php echo esc_attr( $id . '-code' ); ?>">
						<?php esc_html_e( 'Copy code', 'flightlinq-api' ); ?>
					</button>
				</div>
				<pre><code id="<?php echo esc_attr( $id . '-code' ); ?>"><?php echo esc_html( $code ); ?></code></pre>
			</div>
		</section>
		<?php
	}

	/**
	 * Renders a shortcode example.
	 *
	 * This method renders a shortcode example with a title, a description,
	 * a code block, and a copy button. It is a render_code_example() variant
	 * dedicated to shortcodes.
	 *
	 * @since 1.4.1
	 * @date 2026-05-23
	 *
	 * @param string $id          Unique example identifier.
	 * @param string $title       Example title.
	 * @param string $description Example description.
	 * @param string $shortcode   Shortcode to display.
	 *
	 * @return void
	 */
	private function render_shortcode_example( $id, $title, $description, $shortcode ) {
		?>
		<section id="<?php echo esc_attr( $id ); ?>" class="flightlinq-help-card">
			<div class="flightlinq-help-card__header">
				<div>
					<h2 class="flightlinq-help-card__title"><?php echo esc_html( $title ); ?></h2>
					<p class="flightlinq-help-card__description"><?php echo esc_html( $description ); ?></p>
				</div>
			</div>

			<div class="flightlinq-code-block">
				<div class="flightlinq-code-toolbar">
					<span class="flightlinq-code-language">Shortcode</span>
					<button type="button" class="flightlinq-copy-code" data-target="<?php echo esc_attr( $id . '-code' ); ?>">
						<?php esc_html_e( 'Copy code', 'flightlinq-api' ); ?>
					</button>
				</div>
				<pre><code id="<?php echo esc_attr( $id . '-code' ); ?>"><?php echo esc_html( $shortcode ); ?></code></pre>
			</div>
		</section>
		<?php
	}

	/**
	 * Renders the PHP Help / Examples page.
	 *
	 * @since 1.1.0
	 * @since 1.2.0 Refactored with a new visual structure.
	 * @since 1.3.0 Improved the professional layout.
	 * @date 2026-05-23
	 *
	 * @return void
	 */
	public function render_help_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'flightlinq-api' ) );
		}
		?>
		<div class="wrap flightlinq-admin-page flightlinq-help-page">
			<div class="flightlinq-help-container">
				<div class="flightlinq-help-header">
					<h1><?php esc_html_e( 'FlightLinq API PHP Help', 'flightlinq-api' ); ?></h1>
					<p class="flightlinq-help-header__subtitle">
						<?php esc_html_e( 'Use the plugin public PHP functions to integrate FlightLinq data into any WordPress theme.', 'flightlinq-api' ); ?>
					</p>
					<div class="flightlinq-help-header__badge">
						<span class="flightlinq-badge flightlinq-badge--success"><?php esc_html_e( 'No shortcode required', 'flightlinq-api' ); ?></span>
					</div>
				</div>

				<div class="flightlinq-note">
					<p>
						<strong><?php esc_html_e( 'Important note:', 'flightlinq-api' ); ?></strong>
						<?php esc_html_e( 'These examples are intended for WordPress theme files. They are not executed in the administration area.', 'flightlinq-api' ); ?>
					</p>
				</div>

				<div class="flightlinq-help-layout">
				<div class="flightlinq-help-sidebar">
					<div class="flightlinq-help-nav-card">
						<h3 class="flightlinq-help-nav-title"><?php esc_html_e( 'Summary', 'flightlinq-api' ); ?></h3>
						<ul class="flightlinq-help-nav-list">
							<li><a href="#flightlinq-help-availability"><?php esc_html_e( 'Check plugin availability', 'flightlinq-api' ); ?></a></li>
							<li><a href="#flightlinq-help-airline"><?php esc_html_e( 'Airline summary', 'flightlinq-api' ); ?></a></li>
							<li><a href="#flightlinq-help-stats"><?php esc_html_e( 'Airline statistics', 'flightlinq-api' ); ?></a></li>
							<li><a href="#flightlinq-help-pilots"><?php esc_html_e( 'Pilots', 'flightlinq-api' ); ?></a></li>
							<li><a href="#flightlinq-help-routes"><?php esc_html_e( 'Routes', 'flightlinq-api' ); ?></a></li>
							<li><a href="#flightlinq-help-routes-map"><?php esc_html_e( 'Routes for map', 'flightlinq-api' ); ?></a></li>
							<li><a href="#flightlinq-help-fleet"><?php esc_html_e( 'Fleet', 'flightlinq-api' ); ?></a></li>
							<li><a href="#flightlinq-help-recent-flights"><?php esc_html_e( 'Recent flights', 'flightlinq-api' ); ?></a></li>
							<li><a href="#flightlinq-help-leaderboard"><?php esc_html_e( 'Pilot leaderboard', 'flightlinq-api' ); ?></a></li>
							<li><a href="#flightlinq-help-helpers"><?php esc_html_e( 'Useful helpers', 'flightlinq-api' ); ?></a></li>
							<li><a href="#flightlinq-help-best-practices"><?php esc_html_e( 'Best practices', 'flightlinq-api' ); ?></a></li>
							<li><a href="#flightlinq-help-template-example"><?php esc_html_e( 'Complete minimal template example', 'flightlinq-api' ); ?></a></li>
						</ul>
					</div>
				</div>

				<div class="flightlinq-help-content">
					<?php
					$this->render_code_example(
						'flightlinq-help-availability',
						__( 'Check plugin availability', 'flightlinq-api' ),
						__( 'Before using the plugin functions, check that it is active and that the functions are available.', 'flightlinq-api' ),
						'<?php
if ( ! function_exists( \'flightlinq_api_get_airline\' ) ) {
	echo esc_html__( \'FlightLinq API unavailable.\', \'your-theme\' );
	return;
}
?>'
					);

					$this->render_code_example(
						'flightlinq-help-airline',
						__( 'Airline summary', 'flightlinq-api' ),
						__( 'Retrieves the basic information for your airline (name, code, logo, description). Statistics are available in stats.totalPilots, stats.totalFlights, stats.totalHours, stats.averageRating.', 'flightlinq-api' ),
						'<?php
$airline = flightlinq_api_get_airline();

if ( flightlinq_api_is_error( $airline ) ) {
	echo esc_html( flightlinq_api_get_error_message( $airline ) );
	return;
}

$name = flightlinq_api_get_nested_value( $airline, \'name\', \'Unknown airline\' );
$code = flightlinq_api_get_nested_value( $airline, \'code\', \'N/A\' );
$total_pilots = flightlinq_api_get_nested_value( $airline, \'stats.totalPilots\', 0 );
$total_flights = flightlinq_api_get_nested_value( $airline, \'stats.totalFlights\', 0 );

echo esc_html( $name ) . \' (\' . esc_html( $code ) . \')\';
echo esc_html( number_format_i18n( $total_pilots ) ) . \' pilots\';
echo esc_html( number_format_i18n( $total_flights ) ) . \' flights\';
?>'
					);

					$this->render_code_example(
						'flightlinq-help-stats',
						__( 'Airline statistics', 'flightlinq-api' ),
						__( 'Retrieves global statistics for your airline. Pilots are available in pilots.total and pilots.active. Flights are available in flights.total, flights.totalHours, flights.monthToDateFlights.', 'flightlinq-api' ),
						'<?php
$stats = flightlinq_api_get_airline_stats();

if ( flightlinq_api_is_error( $stats ) ) {
	echo esc_html( flightlinq_api_get_error_message( $stats ) );
	return;
}

$total_pilots = flightlinq_api_get_nested_value( $stats, \'pilots.total\', 0 );
$active_pilots = flightlinq_api_get_nested_value( $stats, \'pilots.active\', 0 );
$total_flights = flightlinq_api_get_nested_value( $stats, \'flights.total\', 0 );
$total_hours = flightlinq_api_get_nested_value( $stats, \'flights.totalHours\', 0 );

echo esc_html( number_format_i18n( $active_pilots ) ) . \' / \' . esc_html( number_format_i18n( $total_pilots ) ) . \' active pilots\';
echo esc_html( number_format_i18n( $total_flights ) ) . \' flights\';
echo esc_html( number_format_i18n( $total_hours, 1 ) ) . \' flight hours\';
?>'
					);

					$this->render_code_example(
						'flightlinq-help-pilots',
						__( 'Pilots', 'flightlinq-api' ),
						__( 'Retrieves the list of pilots with their statistics. You can filter and paginate the results.', 'flightlinq-api' ),
						'<?php
$pilots = flightlinq_api_get_pilots( array( \'limit\' => 10 ) );

if ( flightlinq_api_is_error( $pilots ) ) {
	echo esc_html( flightlinq_api_get_error_message( $pilots ) );
	return;
}

$collection = flightlinq_api_normalize_collection( $pilots );

foreach ( $collection as $pilot ) {
	$name = flightlinq_api_get_nested_value( $pilot, \'displayName\', \'Unknown\' );
	$hours = flightlinq_api_get_nested_value( $pilot, \'stats.hours\', 0 );
	$flights = flightlinq_api_get_nested_value( $pilot, \'stats.flights\', 0 );

	echo esc_html( $name ) . \' - \' . esc_html( number_format_i18n( $hours, 1 ) ) . \'h (\' . esc_html( number_format_i18n( $flights ) ) . \' flights)\';
}
?>'
					);

					$this->render_code_example(
						'flightlinq-help-routes',
						__( 'Routes', 'flightlinq-api' ),
						__( 'Retrieves the list of your airline routes with departure and arrival airports.', 'flightlinq-api' ),
						'<?php
$routes = flightlinq_api_get_routes( array( \'limit\' => 20 ) );

if ( flightlinq_api_is_error( $routes ) ) {
	echo esc_html( flightlinq_api_get_error_message( $routes ) );
	return;
}

$collection = flightlinq_api_normalize_collection( $routes );

foreach ( $collection as $route ) {
	$flight_number = flightlinq_api_get_nested_value( $route, \'flightNumber\', \'N/A\' );
	$departure = flightlinq_api_get_nested_value( $route, \'departure.icaoCode\', \'N/A\' );
	$arrival = flightlinq_api_get_nested_value( $route, \'arrival.icaoCode\', \'N/A\' );

	echo esc_html( $flight_number ) . \' : \' . esc_html( $departure ) . \' → \' . esc_html( $arrival );
}
?>'
					);

					$this->render_code_example(
						'flightlinq-help-routes-map',
						__( 'Routes for map', 'flightlinq-api' ),
						__( 'Retrieves routes in a format optimized for map display (without pagination). Useful for generating a map with routes.', 'flightlinq-api' ),
						'<?php
$routes_map = flightlinq_api_get_routes_map();

if ( flightlinq_api_is_error( $routes_map ) ) {
	echo esc_html( flightlinq_api_get_error_message( $routes_map ) );
	return;
}

// Use this to generate a map with routes.
foreach ( $routes_map as $route ) {
	$dep_lat = flightlinq_api_get_nested_value( $route, \'departure.latitude\', 0 );
	$dep_lon = flightlinq_api_get_nested_value( $route, \'departure.longitude\', 0 );
	$arr_lat = flightlinq_api_get_nested_value( $route, \'arrival.latitude\', 0 );
	$arr_lon = flightlinq_api_get_nested_value( $route, \'arrival.longitude\', 0 );

	// Use these coordinates to draw the route on a map.
}
?>'
					);

					?>
					<section class="flightlinq-help-card">
						<div class="flightlinq-help-card__header">
							<div>
								<h2 class="flightlinq-help-card__title"><?php esc_html_e( 'Mapping note', 'flightlinq-api' ); ?></h2>
								<p class="flightlinq-help-card__description">
									<?php esc_html_e( 'The PHP functions expose departure and arrival coordinates when available. They can power a custom map integration, while the [flightlinq_routes_map] shortcode uses the map settings configured in the admin area.', 'flightlinq-api' ); ?>
								</p>
							</div>
						</div>
					</section>
					<?php

					$this->render_code_example(
						'flightlinq-help-fleet',
						__( 'Fleet', 'flightlinq-api' ),
						__( 'Retrieves your airline fleet organized by aircraft type.', 'flightlinq-api' ),
						'<?php
$fleet = flightlinq_api_get_fleet_types();

if ( flightlinq_api_is_error( $fleet ) ) {
	echo esc_html( flightlinq_api_get_error_message( $fleet ) );
	return;
}

foreach ( $fleet as $aircraft_type ) {
	$type_name = flightlinq_api_get_nested_value( $aircraft_type, \'name\', \'Unknown\' );
	$count = flightlinq_api_get_nested_value( $aircraft_type, \'count\', 0 );

	echo esc_html( $type_name ) . \' : \' . esc_html( number_format_i18n( $count ) ) . \' aircraft\';
}
?>'
					);

					$this->render_code_example(
						'flightlinq-help-recent-flights',
						__( 'Recent flights', 'flightlinq-api' ),
						__( 'Retrieves the most recent flights for your airline.', 'flightlinq-api' ),
						'<?php
$flights = flightlinq_api_get_recent_flights( array( \'limit\' => 10 ) );

if ( flightlinq_api_is_error( $flights ) ) {
	echo esc_html( flightlinq_api_get_error_message( $flights ) );
	return;
}

$collection = flightlinq_api_normalize_collection( $flights );

foreach ( $collection as $flight ) {
	$flight_number = flightlinq_api_get_nested_value( $flight, \'flightNumber\', \'N/A\' );
	$dep = flightlinq_api_get_nested_value( $flight, \'departure.icaoCode\', \'N/A\' );
	$arr = flightlinq_api_get_nested_value( $flight, \'arrival.icaoCode\', \'N/A\' );

	echo esc_html( $flight_number ) . \' : \' . esc_html( $dep ) . \' → \' . esc_html( $arr );
}
?>'
					);

					$this->render_code_example(
						'flightlinq-help-leaderboard',
						__( 'Pilot leaderboard', 'flightlinq-api' ),
						__( 'Retrieves the pilot leaderboard by flight hours or by period.', 'flightlinq-api' ),
						'<?php
$leaderboards = flightlinq_api_get_pilot_leaderboards( array( \'limit\' => 10, \'timeframe\' => \'month\' ) );

if ( flightlinq_api_is_error( $leaderboards ) ) {
	echo esc_html( flightlinq_api_get_error_message( $leaderboards ) );
	return;
}

$collection = flightlinq_api_normalize_collection( $leaderboards );

foreach ( $collection as $entry ) {
	$pilot_name = flightlinq_api_get_nested_value( $entry, \'pilot.displayName\', \'Unknown\' );
	$hours = flightlinq_api_get_nested_value( $entry, \'hours\', 0 );

	echo esc_html( $pilot_name ) . \' : \' . esc_html( number_format_i18n( $hours, 1 ) ) . \'h\';
}
?>'
					);

					$this->render_code_example(
						'flightlinq-help-helpers',
						__( 'Useful helpers', 'flightlinq-api' ),
						__( 'Use flightlinq_api_get_nested_value() to safely access deep values in an array without triggering a PHP warning. Use flightlinq_api_normalize_collection() to normalize collection responses.', 'flightlinq-api' ),
						'<?php
// Safe access to a nested value.
$name = flightlinq_api_get_nested_value( $data, \'stats.totalPilots\', 0 );
$dep_icao = flightlinq_api_get_nested_value( $route, \'departure.icaoCode\', \'N/A\' );

// Normalize a collection.
$response = flightlinq_api_get_pilots();
$collection = flightlinq_api_normalize_collection( $response );

foreach ( $collection as $item ) {
	// Process each item.
}
?>'
					);

					$this->render_code_example(
						'flightlinq-help-best-practices',
						__( 'Best practices', 'flightlinq-api' ),
						__( 'Always escape data before displaying it to prevent XSS vulnerabilities.', 'flightlinq-api' ),
						'<?php
// Always use esc_html() for text data.
echo esc_html( $airline_name );

// Use esc_url() for URLs.
echo esc_url( $logo_url );

// Use esc_attr() for HTML attributes.
echo esc_attr( $icao_code );

// For numbers, use number_format_i18n() for localized formatting.
echo number_format_i18n( $hours, 1 );
?>'
					);

					$this->render_code_example(
						'flightlinq-help-template-example',
						__( 'Complete minimal template example', 'flightlinq-api' ),
						__( 'Complete integration example in a WordPress template with checks, API call, error handling, and secure display.', 'flightlinq-api' ),
						'<?php
/**
 * WordPress template for displaying recent FlightLinq flights.
 *
 * @package Your_Theme
 */

// Check that the plugin is available.
if ( ! function_exists( \'flightlinq_api_get_recent_flights\' ) ) {
	echo esc_html__( \'FlightLinq API unavailable.\', \'your-theme\' );
	return;
}

// Retrieve recent flights.
$flights = flightlinq_api_get_recent_flights( array( \'limit\' => 5 ) );

// Handle errors.
if ( flightlinq_api_is_error( $flights ) ) {
	echo \'<p>\' . esc_html( flightlinq_api_get_error_message( $flights ) ) . \'</p>\';
	return;
}

// Normalize the collection.
$collection = flightlinq_api_normalize_collection( $flights );

// Display flights.
if ( empty( $collection ) ) {
	echo \'<p>\' . esc_html__( \'No recent flights.\', \'your-theme\' ) . \'</p>\';
	return;
}
?>

<div class="flightlinq-recent-flights">
	<h2><?php esc_html_e( \'Recent flights\', \'your-theme\' ); ?></h2>
	<ul>
		<?php foreach ( $collection as $flight ) : ?>
			<li>
				<?php
				$flight_number = flightlinq_api_get_nested_value( $flight, \'flightNumber\', \'N/A\' );
				$dep = flightlinq_api_get_nested_value( $flight, \'departure.icaoCode\', \'N/A\' );
				$arr = flightlinq_api_get_nested_value( $flight, \'arrival.icaoCode\', \'N/A\' );
				?>
				<?php echo esc_html( $flight_number ); ?> :
				<?php echo esc_html( $dep ); ?> → <?php echo esc_html( $arr ); ?>
			</li>
		<?php endforeach; ?>
	</ul>
</div>'
					);
					?>
				</div>
			</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders the shortcodes page.
	 *
	 * This page documents the available shortcodes and their attributes.
	 * It uses the same premium visual style as the other admin pages.
	 *
	 * @since 1.4.1
	 * @since 1.4.2 Refactored with dedicated CSS classes for visual consistency.
	 * @since 1.4.3 Aligned with the other admin pages; added theme="inherit" and banner_url.
	 * @date 2026-05-23
	 *
	 * @return void
	 */
	public function render_shortcodes_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'flightlinq-api' ) );
		}
		?>
		<div class="wrap flightlinq-admin-page flightlinq-shortcodes-page">
			<div class="flightlinq-help-container">
				<div class="flightlinq-help-header">
					<h1 class="flightlinq-help-header__title"><?php esc_html_e( 'FlightLinq Shortcodes', 'flightlinq-api' ); ?></h1>
					<p class="flightlinq-help-header__subtitle">
						<?php esc_html_e( 'Use shortcodes to display FlightLinq data without editing theme files.', 'flightlinq-api' ); ?>
					</p>
					<div class="flightlinq-help-header__badge">
						<span class="flightlinq-badge flightlinq-badge--success"><?php esc_html_e( 'Frontend ready to use', 'flightlinq-api' ); ?></span>
					</div>
				</div>

				<div class="flightlinq-help-layout">
					<div class="flightlinq-help-sidebar">
						<div class="flightlinq-help-nav-card">
							<h3 class="flightlinq-help-nav-title"><?php esc_html_e( 'Summary', 'flightlinq-api' ); ?></h3>
							<ul class="flightlinq-help-nav-list">
								<li><a href="#flightlinq-shortcodes-intro"><?php esc_html_e( 'Introduction', 'flightlinq-api' ); ?></a></li>
								<li><a href="#flightlinq-shortcodes-quick-start"><?php esc_html_e( 'Quick start', 'flightlinq-api' ); ?></a></li>
								<li><a href="#flightlinq-shortcodes-list"><?php esc_html_e( 'Available shortcodes', 'flightlinq-api' ); ?></a></li>
								<li><a href="#flightlinq-shortcodes-routes-map"><?php esc_html_e( 'Routes map', 'flightlinq-api' ); ?></a></li>
								<li><a href="#flightlinq-shortcodes-cartography"><?php esc_html_e( 'Mapping', 'flightlinq-api' ); ?></a></li>
								<li><a href="#flightlinq-shortcodes-best-practices"><?php esc_html_e( 'Best practices', 'flightlinq-api' ); ?></a></li>
								<li><a href="#flightlinq-shortcodes-theme-surface"><?php esc_html_e( 'Theme and surface', 'flightlinq-api' ); ?></a></li>
								<li><a href="#flightlinq-shortcodes-examples"><?php esc_html_e( 'Usage examples', 'flightlinq-api' ); ?></a></li>
							</ul>
						</div>
					</div>

					<div class="flightlinq-help-content">
						<!-- Introduction -->
						<section id="flightlinq-shortcodes-intro" class="flightlinq-help-card">
							<div class="flightlinq-help-card__header">
								<div>
									<h2 class="flightlinq-help-card__title"><?php esc_html_e( 'Introduction', 'flightlinq-api' ); ?></h2>
									<p class="flightlinq-help-card__description">
										<?php esc_html_e( 'FlightLinq shortcodes let you display your airline data directly in WordPress content (pages, posts). They use clean styles and are compatible with any theme.', 'flightlinq-api' ); ?>
									</p>
								</div>
							</div>
							<div class="flightlinq-help-card__content">
								<p>
									<strong><?php esc_html_e( 'Shortcode benefits:', 'flightlinq-api' ); ?></strong>
								</p>
								<ul>
									<li><?php esc_html_e( 'No code changes required', 'flightlinq-api' ); ?></li>
									<li><?php esc_html_e( 'Clean, responsive styles', 'flightlinq-api' ); ?></li>
									<li><?php esc_html_e( 'Automatic caching to optimize performance', 'flightlinq-api' ); ?></li>
									<li><?php esc_html_e( 'Compatible with all WordPress themes', 'flightlinq-api' ); ?></li>
									<li><?php esc_html_e( 'Customizable attributes to control display', 'flightlinq-api' ); ?></li>
								</ul>
							</div>
						</section>

						<!-- Quick start -->
						<section id="flightlinq-shortcodes-quick-start" class="flightlinq-help-card">
							<div class="flightlinq-help-card__header">
								<div>
									<h2 class="flightlinq-help-card__title"><?php esc_html_e( 'Quick start', 'flightlinq-api' ); ?></h2>
									<p class="flightlinq-help-card__description">
										<?php esc_html_e( 'Copy these examples into a WordPress page or post to quickly display FlightLinq data.', 'flightlinq-api' ); ?>
									</p>
								</div>
							</div>
							<div class="flightlinq-help-card__content">
								<ul>
									<li><code>[flightlinq_airline_summary]</code></li>
									<li><code>[flightlinq_recent_flights layout="cards" limit="10"]</code></li>
									<li><code>[flightlinq_pilot_leaderboard timeframe="month" limit="10"]</code></li>
									<li><code>[flightlinq_fleet_by_type]</code></li>
									<li><code>[flightlinq_routes_by_hub hub="LFPO" limit="20"]</code></li>
									<li><code>[flightlinq_routes_map]</code></li>
								</ul>
							</div>
						</section>

						<!-- Available shortcodes -->
						<section id="flightlinq-shortcodes-list" class="flightlinq-help-card">
							<div class="flightlinq-help-card__header">
								<div>
									<h2 class="flightlinq-help-card__title"><?php esc_html_e( 'Available shortcodes', 'flightlinq-api' ); ?></h2>
									<p class="flightlinq-help-card__description">
										<?php esc_html_e( 'Readable summary of the shortcodes actually registered by the plugin, with simple and advanced examples.', 'flightlinq-api' ); ?>
									</p>
								</div>
							</div>
							<div class="flightlinq-help-card__content">
								<ul class="flightlinq-shortcodes-list">
									<?php
									$documented_shortcodes = array(
										array(
											'name'        => '[flightlinq_airline_summary]',
											'description' => __( 'Displays the airline summary: logo, description, website, and key statistics.', 'flightlinq-api' ),
											'simple'      => '[flightlinq_airline_summary]',
											'primary'     => array( 'theme', 'surface', 'show_banner' ),
											'advanced'    => array( '[flightlinq_airline_summary theme="dark" surface="transparent" show_banner="yes" banner_fit="contain"]' ),
											'attributes'  => array( 'theme', 'surface', 'layout', 'show_logo', 'show_banner', 'banner_url', 'banner_fit', 'banner_ratio', 'show_name', 'show_code', 'show_iata', 'show_headquarters', 'show_founded', 'show_website', 'show_description', 'show_stats', 'show_total_pilots', 'show_total_flights', 'show_total_hours', 'show_average_rating' ),
										),
										array(
											'name'        => '[flightlinq_recent_flights]',
											'description' => __( 'Displays the latest approved flights, as a table or cards.', 'flightlinq-api' ),
											'simple'      => '[flightlinq_recent_flights]',
											'primary'     => array( 'limit', 'layout', 'theme' ),
											'advanced'    => array( '[flightlinq_recent_flights layout="cards" limit="10"]', '[flightlinq_recent_flights layout="table" limit="20"]' ),
											'attributes'  => array( 'limit', 'theme', 'surface', 'layout', 'show_date', 'show_pilot', 'show_flight_number', 'show_route', 'show_aircraft_type', 'show_aircraft_registration', 'show_block_time', 'show_flight_time', 'show_score', 'show_landing_rate' ),
										),
										array(
											'name'        => '[flightlinq_pilot_leaderboard]',
											'description' => __( 'Displays a pilot ranking for the requested period.', 'flightlinq-api' ),
											'simple'      => '[flightlinq_pilot_leaderboard]',
											'primary'     => array( 'timeframe', 'limit', 'layout' ),
											'advanced'    => array( '[flightlinq_pilot_leaderboard timeframe="month" limit="10"]', '[flightlinq_pilot_leaderboard timeframe="all" limit="10"]' ),
											'attributes'  => array( 'limit', 'timeframe', 'theme', 'surface', 'layout', 'show_rank', 'show_name', 'show_hours', 'show_total_flights', 'show_average_landing_rate' ),
										),
										array(
											'name'        => '[flightlinq_fleet_by_type]',
											'description' => __( 'Displays the fleet grouped by aircraft type.', 'flightlinq-api' ),
											'simple'      => '[flightlinq_fleet_by_type]',
											'primary'     => array( 'show_range', 'show_seats', 'show_hours' ),
											'advanced'    => array( '[flightlinq_fleet_by_type show_range="yes" show_seats="yes" show_hours="yes" show_category="yes"]' ),
											'attributes'  => array( 'theme', 'surface', 'show_range', 'show_seats', 'show_hours', 'show_category' ),
										),
										array(
											'name'        => '[flightlinq_routes_by_hub]',
											'description' => __( 'Displays a route list filterable by hub, departure, arrival, aircraft, or search query.', 'flightlinq-api' ),
											'simple'      => '[flightlinq_routes_by_hub hub="LFPO" limit="20"]',
											'primary'     => array( 'hub', 'limit', 'aircraft_type' ),
											'advanced'    => array( '[flightlinq_routes_by_hub departure="LFPO" aircraft_type="A20N" sort_by="distance" sort_order="desc" limit="20"]', '[flightlinq_routes_by_hub search="Boston" limit="20"]' ),
											'attributes'  => array( 'hub', 'departure', 'arrival', 'aircraft_type', 'search', 'sort_by', 'sort_order', 'limit', 'page', 'theme', 'surface' ),
										),
										array(
											'name'        => '[flightlinq_routes_map]',
											'description' => __( 'Displays routes as a Leaflet map or a table.', 'flightlinq-api' ),
											'simple'      => '[flightlinq_routes_map]',
											'primary'     => array( 'layout', 'departure', 'limit' ),
											'advanced'    => array( '[flightlinq_routes_map departure="EGPH" limit="100"]', '[flightlinq_routes_map layout="table" departure="LFPO" limit="50"]', '[flightlinq_routes_map layout="table" table_view="full" departure="LFPO" limit="20"]' ),
											'attributes'  => array( 'hub', 'departure', 'arrival', 'aircraft_type', 'search', 'sort_by', 'sort_order', 'limit', 'layout', 'table_view', 'provider', 'height', 'theme', 'surface' ),
										),
									);
									?>
									<?php foreach ( $documented_shortcodes as $documented_shortcode ) : ?>
										<li>
											<strong><code><?php echo esc_html( $documented_shortcode['name'] ); ?></code></strong>
											<p><?php echo esc_html( $documented_shortcode['description'] ); ?></p>
											<p class="flightlinq-shortcodes-example">
												<span><?php esc_html_e( 'Simple example', 'flightlinq-api' ); ?></span>
												<code><?php echo esc_html( $documented_shortcode['simple'] ); ?></code>
											</p>
											<p class="flightlinq-shortcodes-primary">
												<?php esc_html_e( 'Main attributes:', 'flightlinq-api' ); ?>
												<?php foreach ( $documented_shortcode['primary'] as $attribute ) : ?>
													<code><?php echo esc_html( $attribute ); ?></code>
												<?php endforeach; ?>
											</p>
											<details class="flightlinq-shortcodes-details">
												<summary><?php esc_html_e( 'Advanced examples', 'flightlinq-api' ); ?></summary>
												<div class="flightlinq-shortcodes-code-list">
													<?php foreach ( $documented_shortcode['advanced'] as $advanced_example ) : ?>
														<code><?php echo esc_html( $advanced_example ); ?></code>
													<?php endforeach; ?>
												</div>
											</details>
											<details class="flightlinq-shortcodes-details">
												<summary><?php esc_html_e( 'Available attributes', 'flightlinq-api' ); ?></summary>
												<div class="flightlinq-shortcodes-attribute-list">
													<?php foreach ( $documented_shortcode['attributes'] as $attribute ) : ?>
														<code><?php echo esc_html( $attribute ); ?></code>
													<?php endforeach; ?>
												</div>
											</details>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						</section>

						<!-- Routes map -->
						<section id="flightlinq-shortcodes-routes-map" class="flightlinq-help-card">
							<div class="flightlinq-help-card__header">
								<div>
									<h2 class="flightlinq-help-card__title"><?php esc_html_e( 'Routes map', 'flightlinq-api' ); ?></h2>
									<p class="flightlinq-help-card__description">
										<?php esc_html_e( 'The [flightlinq_routes_map] shortcode can display a general map, a filtered map for a hub page, or a compact table with table_view="compact" by default.', 'flightlinq-api' ); ?>
									</p>
								</div>
							</div>
							<div class="flightlinq-help-card__content">
								<h3 class="flightlinq-help-card__subtitle"><?php esc_html_e( 'Use case 1 — general map', 'flightlinq-api' ); ?></h3>
								<p><code>[flightlinq_routes_map]</code></p>
								<ul>
									<li><?php esc_html_e( 'Displays a general map of the routes returned by the API.', 'flightlinq-api' ); ?></li>
									<li><?php esc_html_e( 'Uses the map settings configured in the admin area.', 'flightlinq-api' ); ?></li>
									<li><?php esc_html_e( 'Applies the shortcode default limit.', 'flightlinq-api' ); ?></li>
									<li><?php esc_html_e( 'May not display the entire network if the limit is reached.', 'flightlinq-api' ); ?></li>
								</ul>

								<h3 class="flightlinq-help-card__subtitle"><?php esc_html_e( 'Use case 2 — map filtered by hub or airport', 'flightlinq-api' ); ?></h3>
								<p><code>[flightlinq_routes_map departure="EGPH" limit="100"]</code></p>
								<ul>
									<li><?php esc_html_e( 'Displays routes departing from EGPH.', 'flightlinq-api' ); ?></li>
									<li><?php esc_html_e( 'Recommended for a hub page.', 'flightlinq-api' ); ?></li>
									<li><?php esc_html_e( 'limit controls the number of displayed routes.', 'flightlinq-api' ); ?></li>
								</ul>

								<details class="flightlinq-shortcodes-details">
									<summary><?php esc_html_e( 'Table mode and additional examples', 'flightlinq-api' ); ?></summary>
									<ul>
										<li><code>[flightlinq_routes_map hub="EGPH" limit="100"]</code></li>
										<li><code>[flightlinq_routes_map departure="LFPO" aircraft_type="A20N" limit="100"]</code></li>
										<li><code>[flightlinq_routes_map layout="table" departure="LFPO" limit="50"]</code></li>
										<li><code>[flightlinq_routes_map layout="table" table_view="full" departure="LFPO" limit="20"]</code></li>
										<li><code>[flightlinq_routes_map layout="map" departure="LFPO" aircraft_type="A20N" height="600"]</code></li>
									</ul>
								</details>
							</div>
						</section>

						<!-- Mapping -->
						<section id="flightlinq-shortcodes-cartography" class="flightlinq-help-card">
							<div class="flightlinq-help-card__header">
								<div>
									<h2 class="flightlinq-help-card__title"><?php esc_html_e( 'Mapping', 'flightlinq-api' ); ?></h2>
									<p class="flightlinq-help-card__description">
										<?php esc_html_e( 'Reminders about the options required to display the Leaflet map.', 'flightlinq-api' ); ?>
									</p>
								</div>
							</div>
							<div class="flightlinq-help-card__content">
								<ul>
									<li><?php esc_html_e( 'Mapping must be enabled in the plugin options.', 'flightlinq-api' ); ?></li>
									<li><?php esc_html_e( 'OpenStreetMap works without an API key.', 'flightlinq-api' ); ?></li>
									<li><?php esc_html_e( 'Mapbox requires a public token configured in the admin area.', 'flightlinq-api' ); ?></li>
									<li><?php esc_html_e( 'This shortcode supports OpenStreetMap and Mapbox.', 'flightlinq-api' ); ?></li>
									<li><?php esc_html_e( 'If mapping is disabled, the shortcode displays the message or fallback provided by the plugin.', 'flightlinq-api' ); ?></li>
								</ul>
							</div>
						</section>

						<!-- Best practices -->
						<section id="flightlinq-shortcodes-best-practices" class="flightlinq-help-card">
							<div class="flightlinq-help-card__header">
								<div>
									<h2 class="flightlinq-help-card__title"><?php esc_html_e( 'Best practices', 'flightlinq-api' ); ?></h2>
								</div>
							</div>
							<div class="flightlinq-help-card__content">
								<ul>
									<li><?php esc_html_e( 'Use limit to avoid overly heavy pages.', 'flightlinq-api' ); ?></li>
									<li><?php esc_html_e( 'Use departure or hub to build targeted hub pages.', 'flightlinq-api' ); ?></li>
									<li><?php esc_html_e( 'Use aircraft_type to filter a fleet or a specific aircraft type.', 'flightlinq-api' ); ?></li>
									<li><?php esc_html_e( 'Use layout="table" if you do not want to display a map.', 'flightlinq-api' ); ?></li>
									<li><?php esc_html_e( 'Check that mapping is enabled in the plugin options before using layout="map".', 'flightlinq-api' ); ?></li>
									<li><?php esc_html_e( 'Choose OpenStreetMap for a simple setup without a key.', 'flightlinq-api' ); ?></li>
									<li><?php esc_html_e( 'Choose Mapbox if you want a more customized map rendering and have a public token.', 'flightlinq-api' ); ?></li>
								</ul>
							</div>
						</section>

						<!-- Airline summary shortcode -->
						<section id="flightlinq-shortcodes-airline-summary" class="flightlinq-help-card">
							<div class="flightlinq-help-card__header">
								<div>
									<h2 class="flightlinq-help-card__title">[flightlinq_airline_summary]</h2>
									<p class="flightlinq-help-card__description">
										<?php esc_html_e( 'Displays a complete airline summary with logo, name, description, headquarters, website, and statistics.', 'flightlinq-api' ); ?>
									</p>
								</div>
							</div>
							<div class="flightlinq-help-card__content">
								<p>
									<strong><?php esc_html_e( 'Displayed data:', 'flightlinq-api' ); ?></strong>
								</p>
								<ul>
									<li><?php esc_html_e( 'Airline logo (if available)', 'flightlinq-api' ); ?></li>
									<li><?php esc_html_e( 'Airline name', 'flightlinq-api' ); ?></li>
									<li><?php esc_html_e( 'Airline code and IATA code', 'flightlinq-api' ); ?></li>
									<li><?php esc_html_e( 'Headquarters', 'flightlinq-api' ); ?></li>
									<li><?php esc_html_e( 'Founding date', 'flightlinq-api' ); ?></li>
									<li><?php esc_html_e( 'Website', 'flightlinq-api' ); ?></li>
									<li><?php esc_html_e( 'Description', 'flightlinq-api' ); ?></li>
									<li><?php esc_html_e( 'Statistics: pilots, flights, flight hours, average rating', 'flightlinq-api' ); ?></li>
								</ul>
							</div>
						</section>

						<!-- Available attributes -->
						<section id="flightlinq-shortcodes-attributes" class="flightlinq-help-card">
							<div class="flightlinq-help-card__header">
								<div>
									<h2 class="flightlinq-help-card__title"><?php esc_html_e( 'Available attributes', 'flightlinq-api' ); ?></h2>
									<p class="flightlinq-help-card__description">
										<?php esc_html_e( 'Customize the shortcode display with these attributes.', 'flightlinq-api' ); ?>
									</p>
								</div>
							</div>
							<div class="flightlinq-help-card__content">
								<h3 class="flightlinq-help-card__subtitle"><?php esc_html_e( 'Theme and surface', 'flightlinq-api' ); ?></h3>
								<ul>
									<li><code>theme="inherit|auto|light|dark"</code> : <?php esc_html_e( 'Visual theme for text colors (default: inherit)', 'flightlinq-api' ); ?></li>
									<li><code>surface="transparent|card"</code> : <?php esc_html_e( 'Surface type for the background (default: transparent)', 'flightlinq-api' ); ?></li>
									<li><code>layout="card|compact"</code> : <?php esc_html_e( 'Display layout (default: card)', 'flightlinq-api' ); ?></li>
								</ul>

								<h3 class="flightlinq-help-card__subtitle"><?php esc_html_e( 'Main elements', 'flightlinq-api' ); ?></h3>
								<ul>
									<li><code>show_logo="yes|no"</code> : <?php esc_html_e( 'Show logo (default: yes)', 'flightlinq-api' ); ?></li>
									<li><code>show_banner="yes|no"</code> : <?php esc_html_e( 'Show banner if available (default: yes)', 'flightlinq-api' ); ?></li>
									<li><code>banner_url=""</code> : <?php esc_html_e( 'Allows forcing a manual banner. If empty, the plugin automatically uses the first public BANNER media returned by FlightLinq.', 'flightlinq-api' ); ?></li>
									<li><code>banner_fit="contain|cover"</code> : <?php esc_html_e( 'Image fit mode (default: contain)', 'flightlinq-api' ); ?></li>
									<li><code>banner_ratio="auto|3-1"</code> : <?php esc_html_e( 'Banner container ratio (default: 3-1)', 'flightlinq-api' ); ?></li>
									<li><code>show_name="yes|no"</code> : <?php esc_html_e( 'Show name (default: yes)', 'flightlinq-api' ); ?></li>
									<li><code>show_code="yes|no"</code> : <?php esc_html_e( 'Show airline code (default: yes)', 'flightlinq-api' ); ?></li>
									<li><code>show_iata="yes|no"</code> : <?php esc_html_e( 'Show IATA code (default: yes)', 'flightlinq-api' ); ?></li>
								</ul>

								<h3 class="flightlinq-help-card__subtitle"><?php esc_html_e( 'Detailed information', 'flightlinq-api' ); ?></h3>
								<ul>
									<li><code>show_headquarters="yes|no"</code> : <?php esc_html_e( 'Show headquarters (default: yes)', 'flightlinq-api' ); ?></li>
									<li><code>show_founded="yes|no"</code> : <?php esc_html_e( 'Show founding date (default: yes)', 'flightlinq-api' ); ?></li>
									<li><code>show_website="yes|no"</code> : <?php esc_html_e( 'Show website (default: yes)', 'flightlinq-api' ); ?></li>
									<li><code>show_description="yes|no"</code> : <?php esc_html_e( 'Show description (default: yes)', 'flightlinq-api' ); ?></li>
								</ul>

								<h3 class="flightlinq-help-card__subtitle"><?php esc_html_e( 'Statistics', 'flightlinq-api' ); ?></h3>
								<ul>
									<li><code>show_stats="yes|no"</code> : <?php esc_html_e( 'Show all statistics (default: yes)', 'flightlinq-api' ); ?></li>
									<li><code>show_total_pilots="yes|no"</code> : <?php esc_html_e( 'Show pilot count (default: yes)', 'flightlinq-api' ); ?></li>
									<li><code>show_total_flights="yes|no"</code> : <?php esc_html_e( 'Show flight count (default: yes)', 'flightlinq-api' ); ?></li>
									<li><code>show_total_hours="yes|no"</code> : <?php esc_html_e( 'Show flight hours (default: yes)', 'flightlinq-api' ); ?></li>
									<li><code>show_average_rating="yes|no"</code> : <?php esc_html_e( 'Show average rating (default: yes)', 'flightlinq-api' ); ?></li>
								</ul>
							</div>
						</section>

						<!-- Theme and surface -->
						<section id="flightlinq-shortcodes-theme-surface" class="flightlinq-help-card">
							<div class="flightlinq-help-card__header">
								<div>
									<h2 class="flightlinq-help-card__title"><?php esc_html_e( 'Theme and surface', 'flightlinq-api' ); ?></h2>
									<p class="flightlinq-help-card__description">
										<?php esc_html_e( 'Understand the difference between theme and surface.', 'flightlinq-api' ); ?>
									</p>
								</div>
							</div>
							<div class="flightlinq-help-card__content">
								<h3 class="flightlinq-help-card__subtitle"><?php esc_html_e( 'theme attribute', 'flightlinq-api' ); ?></h3>
								<p>
									<?php esc_html_e( 'The theme attribute controls text and border colors, but not the background.', 'flightlinq-api' ); ?>
								</p>
								<ul>
									<li><code>theme="inherit"</code> : <?php esc_html_e( 'Does not force colors; inherits from the current WordPress theme (recommended by default).', 'flightlinq-api' ); ?></li>
									<li><code>theme="auto"</code> : <?php esc_html_e( 'Uses prefers-color-scheme, useful if the site follows the system mode.', 'flightlinq-api' ); ?></li>
									<li><code>theme="light"</code> : <?php esc_html_e( 'Dark text, borders compatible with a light theme.', 'flightlinq-api' ); ?></li>
									<li><code>theme="dark"</code> : <?php esc_html_e( 'Light text, borders compatible with a dark theme.', 'flightlinq-api' ); ?></li>
								</ul>

								<h3 class="flightlinq-help-card__subtitle"><?php esc_html_e( 'surface attribute', 'flightlinq-api' ); ?></h3>
								<p>
									<?php esc_html_e( 'The surface attribute controls the shortcode background.', 'flightlinq-api' ); ?>
								</p>
								<ul>
									<li><code>surface="transparent"</code> : <?php esc_html_e( 'Does not force a background; adapts to the current theme. Recommended for natural integration.', 'flightlinq-api' ); ?></li>
									<li><code>surface="card"</code> : <?php esc_html_e( 'Applies a card with a subtle background adapted to the selected theme.', 'flightlinq-api' ); ?></li>
								</ul>

								<h3 class="flightlinq-help-card__subtitle"><?php esc_html_e( 'Combining theme and surface', 'flightlinq-api' ); ?></h3>
								<p>
									<?php esc_html_e( 'The two attributes are independent and can be combined according to your needs:', 'flightlinq-api' ); ?>
								</p>
								<ul>
									<li><code>theme="inherit" surface="transparent"</code> : <?php esc_html_e( 'Transparent background, inherited colors (recommended by default).', 'flightlinq-api' ); ?></li>
									<li><code>theme="dark" surface="transparent"</code> : <?php esc_html_e( 'Transparent background, light text (ideal for dark areas).', 'flightlinq-api' ); ?></li>
									<li><code>theme="light" surface="transparent"</code> : <?php esc_html_e( 'Transparent background, dark text (ideal for light areas).', 'flightlinq-api' ); ?></li>
									<li><code>theme="dark" surface="card"</code> : <?php esc_html_e( 'Subtle dark background, light text (standalone card).', 'flightlinq-api' ); ?></li>
									<li><code>theme="light" surface="card"</code> : <?php esc_html_e( 'Light background, dark text (standalone card).', 'flightlinq-api' ); ?></li>
								</ul>
							</div>
						</section>

						<!-- Exemples d'utilisation -->
						<section id="flightlinq-shortcodes-examples" class="flightlinq-help-card">
							<div class="flightlinq-help-card__header">
								<div>
									<h2 class="flightlinq-help-card__title"><?php esc_html_e( 'Usage examples', 'flightlinq-api' ); ?></h2>
									<p class="flightlinq-help-card__description">
										<?php esc_html_e( 'Here are a few shortcode usage examples.', 'flightlinq-api' ); ?>
									</p>
								</div>
							</div>
							<div class="flightlinq-help-card__content">
								<?php
								$this->render_shortcode_example(
									'flightlinq-shortcode-example-1',
									__( 'Full display (default)', 'flightlinq-api' ),
									__( 'Displays all available information with theme="inherit" and surface="transparent" (default values).', 'flightlinq-api' ),
									'[flightlinq_airline_summary]'
								);

								$this->render_shortcode_example(
									'flightlinq-shortcode-example-2',
									__( 'Transparent inherit theme', 'flightlinq-api' ),
									__( 'Displays all information with theme="inherit" and surface="transparent" (recommended for natural integration).', 'flightlinq-api' ),
									'[flightlinq_airline_summary theme="inherit" surface="transparent"]'
								);

								$this->render_shortcode_example(
									'flightlinq-shortcode-example-3',
									__( 'Transparent dark theme', 'flightlinq-api' ),
									__( 'Displays all information with theme="dark" and surface="transparent" (ideal for dark areas).', 'flightlinq-api' ),
									'[flightlinq_airline_summary theme="dark" surface="transparent"]'
								);

								$this->render_shortcode_example(
									'flightlinq-shortcode-example-4',
									__( 'Dark card theme', 'flightlinq-api' ),
									__( 'Displays all information with theme="dark" and surface="card" (standalone dark card).', 'flightlinq-api' ),
									'[flightlinq_airline_summary theme="dark" surface="card"]'
								);

								$this->render_shortcode_example(
									'flightlinq-shortcode-example-5',
									__( 'Without banner', 'flightlinq-api' ),
									__( 'Full display without banner, dark theme, transparent surface.', 'flightlinq-api' ),
									'[flightlinq_airline_summary theme="dark" surface="transparent" show_banner="no"]'
								);

								$this->render_shortcode_example(
									'flightlinq-shortcode-example-6',
									__( 'Compact without description', 'flightlinq-api' ),
									__( 'Compact display without description or website, dark theme, transparent surface.', 'flightlinq-api' ),
									'[flightlinq_airline_summary theme="dark" surface="transparent" show_description="no" show_website="no"]'
								);

								$this->render_shortcode_example(
									'flightlinq-shortcode-example-7',
									__( 'Manual banner', 'flightlinq-api' ),
									__( 'Provide a manual banner with the banner_url attribute.', 'flightlinq-api' ),
									'[flightlinq_airline_summary show_banner="yes" banner_url="https://votre-site.fr/banner.jpg"]'
								);

								$this->render_shortcode_example(
									'flightlinq-shortcode-example-8',
									__( 'Automatic banner', 'flightlinq-api' ),
									__( 'Displays the banner automatically if the API provides a BANNER media item or a direct image field.', 'flightlinq-api' ),
									'[flightlinq_airline_summary show_banner="yes"]'
								);

								$this->render_shortcode_example(
									'flightlinq-shortcode-example-9',
									__( 'Contain banner ratio 3:1', 'flightlinq-api' ),
									__( 'Displays the banner with banner_fit="contain" and banner_ratio="3-1" (default values).', 'flightlinq-api' ),
									'[flightlinq_airline_summary show_banner="yes" banner_fit="contain" banner_ratio="3-1"]'
								);

								$this->render_shortcode_example(
									'flightlinq-shortcode-example-10',
									__( 'Cover banner ratio 3:1', 'flightlinq-api' ),
									__( 'Displays the banner with banner_fit="cover" to fill the container (may crop the image).', 'flightlinq-api' ),
									'[flightlinq_airline_summary show_banner="yes" banner_fit="cover" banner_ratio="3-1"]'
								);

								$this->render_shortcode_example(
									'flightlinq-shortcode-example-11',
									__( 'Transparent contain banner', 'flightlinq-api' ),
									__( 'Displays the banner with theme="dark", surface="transparent", and banner_fit="contain".', 'flightlinq-api' ),
									'[flightlinq_airline_summary theme="dark" surface="transparent" show_banner="yes" banner_fit="contain"]'
								);
								?>
							</div>
						</section>

						<!-- Recent flights -->
						<section id="flightlinq-shortcodes-recent-flights" class="flightlinq-help-card">
							<div class="flightlinq-help-card__header">
								<div>
									<h2 class="flightlinq-help-card__title"><?php esc_html_e( 'Recent flights', 'flightlinq-api' ); ?></h2>
									<p class="flightlinq-help-card__description">
										<?php esc_html_e( 'Displays the airline latest approved FlightLinq flights from the /flights/recent endpoint.', 'flightlinq-api' ); ?>
									</p>
								</div>
							</div>
							<div class="flightlinq-help-card__content">
								<h3 class="flightlinq-help-card__subtitle"><?php esc_html_e( 'Attributes', 'flightlinq-api' ); ?></h3>
								<ul>
									<li><code>limit="1-50"</code> : <?php esc_html_e( 'Number of flights to display (default: 5)', 'flightlinq-api' ); ?></li>
									<li><code>theme="inherit|auto|light|dark"</code> : <?php esc_html_e( 'Visual theme for text colors (default: inherit)', 'flightlinq-api' ); ?></li>
									<li><code>surface="transparent|card"</code> : <?php esc_html_e( 'Surface type for the background (default: transparent)', 'flightlinq-api' ); ?></li>
									<li><code>layout="table|cards"</code> : <?php esc_html_e( 'Display layout (default: table)', 'flightlinq-api' ); ?></li>
									<li><code>show_date="yes|no"</code> : <?php esc_html_e( 'Show date (default: yes)', 'flightlinq-api' ); ?></li>
									<li><code>show_pilot="yes|no"</code> : <?php esc_html_e( 'Show pilot (default: yes)', 'flightlinq-api' ); ?></li>
									<li><code>show_flight_number="yes|no"</code> : <?php esc_html_e( 'Show flight number (default: yes)', 'flightlinq-api' ); ?></li>
									<li><code>show_route="yes|no"</code> : <?php esc_html_e( 'Show route (default: yes)', 'flightlinq-api' ); ?></li>
									<li><code>show_aircraft_type="yes|no"</code> : <?php esc_html_e( 'Show aircraft type (default: yes)', 'flightlinq-api' ); ?></li>
									<li><code>show_aircraft_registration="yes|no"</code> : <?php esc_html_e( 'Show registration (default: yes)', 'flightlinq-api' ); ?></li>
									<li><code>show_block_time="yes|no"</code> : <?php esc_html_e( 'Show block time (default: yes)', 'flightlinq-api' ); ?></li>
									<li><code>show_flight_time="yes|no"</code> : <?php esc_html_e( 'Show flight time (default: no)', 'flightlinq-api' ); ?></li>
									<li><code>show_score="yes|no"</code> : <?php esc_html_e( 'Show score (default: yes)', 'flightlinq-api' ); ?></li>
									<li><code>show_landing_rate="yes|no"</code> : <?php esc_html_e( 'Show landing rate (default: yes)', 'flightlinq-api' ); ?></li>
								</ul>

								<p class="description">
									<?php esc_html_e( 'Depending on the available data, the shortcode can display the flight date, pilot, flight number, route, aircraft type, registration, block time, flight time, score, and landing rate. Missing fields are displayed with a dash (—).', 'flightlinq-api' ); ?>
								</p>

								<h3 class="flightlinq-help-card__subtitle"><?php esc_html_e( 'Usage examples', 'flightlinq-api' ); ?></h3>
								<?php
								$this->render_shortcode_example(
									'flightlinq-shortcode-recent-flights-1',
									__( 'Default display', 'flightlinq-api' ),
									__( 'Displays the 5 latest flights in a table with theme="inherit" and surface="transparent".', 'flightlinq-api' ),
									'[flightlinq_recent_flights]'
								);

								$this->render_shortcode_example(
									'flightlinq-shortcode-recent-flights-2',
									__( '10 flights', 'flightlinq-api' ),
									__( 'Displays the 10 latest flights.', 'flightlinq-api' ),
									'[flightlinq_recent_flights limit="10"]'
								);

								$this->render_shortcode_example(
									'flightlinq-shortcode-recent-flights-3',
									__( 'Transparent dark theme', 'flightlinq-api' ),
									__( 'Displays flights with theme="dark" and surface="transparent".', 'flightlinq-api' ),
									'[flightlinq_recent_flights theme="dark" surface="transparent"]'
								);

								$this->render_shortcode_example(
									'flightlinq-shortcode-recent-flights-4',
									__( 'Cards layout', 'flightlinq-api' ),
									__( 'Displays flights as cards (ideal for mobile).', 'flightlinq-api' ),
									'[flightlinq_recent_flights theme="dark" surface="transparent" layout="cards"]'
								);

								$this->render_shortcode_example(
									'flightlinq-shortcode-recent-flights-5',
									__( 'Without score or landing rate', 'flightlinq-api' ),
									__( 'Displays flights without score or landing rate.', 'flightlinq-api' ),
									'[flightlinq_recent_flights limit="5" show_score="no" show_landing_rate="no"]'
								);
								?>
							</div>
						</section>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
