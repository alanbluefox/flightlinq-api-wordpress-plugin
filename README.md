# FlightLinq API WordPress Plugin

FlightLinq API WordPress Plugin is an unofficial WordPress plugin for connecting a Commercial-tier FlightLinq Developer API key to a WordPress website. It displays virtual airline data through shortcodes, PHP helper functions, and filtered REST endpoints, and is designed for virtual airline websites and dashboards.

## About FlightLinq

[FlightLinq](https://flightlinq.com/) is a platform for virtual airlines, pilots, and flight simulation communities. It provides tools for managing airline operations, tracking flights, handling routes, fleets, pilots, and related operational data.

This plugin is not the official FlightLinq API. It is a WordPress integration plugin that helps virtual airlines display selected FlightLinq data on their own WordPress websites.

## Features

- Server-side storage for the FlightLinq Developer API key.
- WordPress admin settings and connection testing.
- Optional API response caching with a configurable duration.
- Read-only, filtered public REST endpoints that can be disabled.
- Responsive frontend shortcodes for airline summaries, recent flights, pilot leaderboards, fleet data, routes, and route maps.
- Route filtering by hub, departure, arrival, aircraft type, and search terms.
- Interactive Leaflet route maps.
- OpenStreetMap tiles by default, with no map API key required.
- Optional Mapbox tile provider using a public token.
- PHP helper functions for theme and template integration.
- Internationalization-ready structure using the `flightlinq-api` text domain.

Google Maps is not supported.

## Requirements

- WordPress 5.8 or later.
- PHP 7.4 or later.
- FlightLinq Commercial-tier Developer API access.
- A read-only FlightLinq Developer API key.

Leaflet 1.9.4 assets are loaded from `unpkg.com` only when an interactive route map is rendered. OpenStreetMap or Mapbox supplies the map tiles, so interactive maps require access to the selected provider.

## Installation

1. Upload the `flightlinq-api` directory to `/wp-content/plugins/`.
2. Activate **FlightLinq API** in the WordPress Plugins screen.
3. Open the FlightLinq API settings page in WordPress.
4. Add the FlightLinq Developer API key.
5. Configure cache, public REST access, and route mapping options.
6. Save the settings and test the connection.

## Configuration

| Setting | Description |
| --- | --- |
| API key | Read-only FlightLinq Developer API key stored on the WordPress server. |
| Cache | Enables or disables WordPress transient caching for API responses. |
| Cache duration | Controls how long API responses remain cached, in minutes. |
| Public REST API | Enables or disables the filtered public endpoints under `flightlinq/v1`. |
| Route mapping | Enables or disables interactive route maps. |
| Map provider | Selects OpenStreetMap or Mapbox. |
| Mapbox public token | Public token used only when Mapbox is selected. |
| Mapbox style | Mapbox style identifier used for map tiles. |
| Default map height | Default Leaflet map height in pixels. |

OpenStreetMap works without an API key. Mapbox requires a public token. The FlightLinq Developer API key is never exposed to frontend JavaScript or public REST responses.

## Shortcodes

All shortcodes support the existing plugin cache and display user-friendly fallback messages when data is unavailable.

### Airline summary

`[flightlinq_airline_summary]` displays an airline profile with its identity, media, description, website, and available statistics.

Simple example:

```text
[flightlinq_airline_summary]
```

Advanced example:

```text
[flightlinq_airline_summary layout="compact" surface="card" show_banner="no" show_description="no"]
```

Main attributes:

- `layout`: `card` or `compact`.
- `theme`: `inherit`, `auto`, `light`, or `dark`.
- `surface`: `transparent` or `card`.
- `show_logo`, `show_banner`, `show_name`, `show_code`, `show_iata`, `show_headquarters`, `show_founded`, `show_website`, `show_description`, and statistic-specific `show_*` attributes: `yes` or `no`.
- `banner_url`, `banner_fit`, and `banner_ratio`: optional banner overrides and display controls.

### Recent flights

`[flightlinq_recent_flights]` displays recently approved flights as a table or responsive cards.

Simple example:

```text
[flightlinq_recent_flights]
```

Advanced example:

```text
[flightlinq_recent_flights layout="cards" limit="10"]
```

Main attributes:

- `limit`: number of flights to display, from 1 to 50.
- `layout`: `table` or `cards`.
- `theme` and `surface`: visual integration controls.
- `show_date`, `show_pilot`, `show_flight_number`, `show_route`, `show_aircraft_type`, `show_aircraft_registration`, `show_block_time`, `show_flight_time`, `show_score`, and `show_landing_rate`: `yes` or `no`.

### Pilot leaderboard

`[flightlinq_pilot_leaderboard]` displays monthly or all-time pilot rankings.

Simple example:

```text
[flightlinq_pilot_leaderboard]
```

Advanced example:

```text
[flightlinq_pilot_leaderboard timeframe="month" limit="10"]
```

Main attributes:

- `timeframe`: `month` or `all`.
- `limit`: number of pilots to display, from 1 to 50.
- `layout`: `table`, `cards`, or `podium`.
- `theme` and `surface`: visual integration controls.
- `show_rank`, `show_name`, `show_hours`, `show_total_flights`, and `show_average_landing_rate`: `yes` or `no`.

### Fleet by aircraft type

`[flightlinq_fleet_by_type]` displays fleet totals grouped by aircraft type, including technical data when available.

Simple example:

```text
[flightlinq_fleet_by_type]
```

Advanced example:

```text
[flightlinq_fleet_by_type show_range="yes" show_seats="yes" show_hours="yes" show_category="yes"]
```

Main attributes:

- `show_range`, `show_seats`, `show_hours`, and `show_category`: `yes` or `no`.
- `theme` and `surface`: visual integration controls.

### Routes by hub

`[flightlinq_routes_by_hub]` displays a route table with optional hub, airport, aircraft, search, pagination, and sorting filters.

Simple example:

```text
[flightlinq_routes_by_hub hub="LFPO" limit="20"]
```

Advanced example:

```text
[flightlinq_routes_by_hub departure="LFPO" aircraft_type="A20N" sort_by="distance" sort_order="desc" limit="20"]
```

Main attributes:

- `hub`, `departure`, and `arrival`: ICAO airport filters. `hub` is used as the departure filter when `departure` is omitted.
- `aircraft_type` and `search`: aircraft and free-text filters.
- `sort_by` and `sort_order`: route sorting controls.
- `limit` and `page`: pagination controls.
- `theme` and `surface`: visual integration controls.

### Route map and route table

`[flightlinq_routes_map]` displays routes as an interactive map or as a route table.

Simple example:

```text
[flightlinq_routes_map]
```

Interactive filtered map:

```text
[flightlinq_routes_map layout="map" departure="LFPO" limit="50"]
```

Compact route table:

```text
[flightlinq_routes_map layout="table" departure="LFPO" limit="50"]
```

Detailed route table:

```text
[flightlinq_routes_map layout="table" table_view="full" departure="LFPO" limit="20"]
```

Main attributes:

- `layout`: `map` or `table`.
- `table_view`: `compact` or `full` when table layout is selected.
- `hub`, `departure`, `arrival`, `aircraft_type`, and `search`: route filters.
- `sort_by`, `sort_order`, and `limit`: sorting and result controls.
- `provider`: `openstreetmap` or `mapbox`; the admin setting is used when omitted.
- `height`: map height in pixels.
- `theme` and `surface`: visual integration controls.

## Route maps

- `layout="map"` displays an interactive Leaflet map with route lines, airport markers, and popups.
- OpenStreetMap is the default tile provider.
- Mapbox can be selected in the admin and requires a public token.
- `layout="table"` displays a compact route table.
- `table_view="full"` displays the detailed route table.
- Routes without coordinates remain available in table mode.
- Routes without valid coordinates are skipped on the map and reported in the map notice.
- The provider and default map height can be configured globally and overridden by supported shortcode attributes.

## PHP helper functions

The plugin exposes read-only helper functions for WordPress themes and custom integrations.

| Function | Purpose |
| --- | --- |
| `flightlinq_api_get_airline()` | Returns airline information. |
| `flightlinq_api_get_airline_stats()` | Returns airline statistics. |
| `flightlinq_api_get_pilots( $args = array() )` | Returns pilots with supported pagination, filtering, and sorting arguments. |
| `flightlinq_api_get_routes( $args = array() )` | Returns routes with supported pagination, filtering, and sorting arguments. |
| `flightlinq_api_get_routes_map( $args = array() )` | Returns route-map data with supported filters. |
| `flightlinq_api_get_fleet_types()` | Returns fleet data grouped by aircraft type. |
| `flightlinq_api_get_recent_flights( $args = array() )` | Returns recent approved flights. |
| `flightlinq_api_get_pilot_leaderboards( $args = array() )` | Returns pilot leaderboard data. |
| `flightlinq_api_is_error()` | Checks whether a response is a `WP_Error`. |
| `flightlinq_api_get_error_message()` | Returns a user-facing error message. |
| `flightlinq_api_get_nested_value()` | Safely reads a nested array value. |
| `flightlinq_api_normalize_collection()` | Normalizes supported FlightLinq collection responses. |

Example: display the airline name.

```php
<?php
$airline = flightlinq_api_get_airline();

if ( flightlinq_api_is_error( $airline ) ) {
	echo esc_html( flightlinq_api_get_error_message( $airline ) );
	return;
}

$name = flightlinq_api_get_nested_value( $airline, 'name', 'Unknown airline' );
echo esc_html( $name );
```

Example: display recent route data.

```php
<?php
$routes = flightlinq_api_get_routes( array( 'limit' => 20 ) );

if ( flightlinq_api_is_error( $routes ) ) {
	echo esc_html__( 'No data available', 'flightlinq-api' );
	return;
}

foreach ( flightlinq_api_normalize_collection( $routes ) as $route ) {
	$departure = flightlinq_api_get_nested_value( $route, 'departure.icaoCode', 'N/A' );
	$arrival   = flightlinq_api_get_nested_value( $route, 'arrival.icaoCode', 'N/A' );

	echo '<p>' . esc_html( $departure . ' -> ' . $arrival ) . '</p>';
}
```

Helper functions return data or `WP_Error` objects and do not render output directly.

## REST API

The plugin registers read-only endpoints under the `flightlinq/v1` namespace:

- `GET /wp-json/flightlinq/v1/airline`
- `GET /wp-json/flightlinq/v1/airline/stats`
- `GET /wp-json/flightlinq/v1/pilots`
- `GET /wp-json/flightlinq/v1/routes`
- `GET /wp-json/flightlinq/v1/routes/map`
- `GET /wp-json/flightlinq/v1/fleet`
- `GET /wp-json/flightlinq/v1/flights/recent`
- `GET /wp-json/flightlinq/v1/leaderboards/pilots`

Public responses are filtered for external use. Public REST access can be disabled in the plugin settings; disabled endpoints return HTTP 403. The FlightLinq Developer API key is never included in REST responses.

## Cache

The optional cache uses WordPress transients to reduce repeated FlightLinq API requests. Administrators can configure the cache duration and clear cached responses from the settings page.

## Security

- Store the FlightLinq Developer API key on the server only.
- Do not expose the key in frontend JavaScript or custom public output.
- Public REST responses are filtered to supported fields.
- REST endpoints are read-only.
- Technical FlightLinq errors are hidden from public REST responses.
- Use scoped, read-only FlightLinq keys whenever possible.

## Internationalization

The plugin uses the `flightlinq-api` text domain and loads translation files from `/languages`. English is the source language. French translation files will be provided in a later release.

## Support

For support, bug reports, or feature requests, open an issue on the project repository.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for the full release history.

## License

Copyright © AirBlueFox. No open-source license has been declared for this repository. Contact the author before redistributing or modifying the plugin outside the rights granted by applicable law.
