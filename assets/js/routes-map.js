(function (window, document) {
	'use strict';

	var leafletCssId = 'flightlinq-leaflet-css';
	var leafletCssHref = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
	var leafletScriptId = 'flightlinq-leaflet-js';
	var leafletScriptHref = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
	var leafletLoading = null;
	var i18n = window.FLQRoutesMapI18n || {};

	function getText(key, fallback) {
		return typeof i18n[key] === 'string' && i18n[key] ? i18n[key] : fallback;
	}

	function ensureLeafletCss() {
		if (document.getElementById(leafletCssId)) {
			return;
		}

		var link = document.createElement('link');
		link.id = leafletCssId;
		link.rel = 'stylesheet';
		link.href = leafletCssHref;
		document.head.appendChild(link);
	}

	function ensureLeafletScript() {
		if (window.L) {
			return Promise.resolve();
		}

		if (leafletLoading) {
			return leafletLoading;
		}

		leafletLoading = new Promise(function (resolve, reject) {
			var existing = document.getElementById(leafletScriptId);

			if (existing) {
				var timeout = window.setTimeout(function () {
					if (window.L) {
						resolve();
						return;
					}

					reject();
				}, 5000);

				existing.addEventListener('load', function () {
					window.clearTimeout(timeout);
					resolve();
				}, { once: true });
				existing.addEventListener('error', function () {
					window.clearTimeout(timeout);
					reject();
				}, { once: true });
				return;
			}

			var script = document.createElement('script');
			script.id = leafletScriptId;
			script.src = leafletScriptHref;
			script.async = true;
			script.onload = resolve;
			script.onerror = reject;
			document.head.appendChild(script);
		});

		return leafletLoading;
	}

	function setMapMessage(container, message) {
		container.classList.add('flightlinq-leaflet-map--message');
		container.innerHTML = '<span class="flightlinq-leaflet-map__message">' + escapeHtml(message) + '</span>';
	}

	function clearMapMessage(container) {
		container.classList.remove('flightlinq-leaflet-map--message');
		container.innerHTML = '';
	}

	function escapeHtml(value) {
		return String(value || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function addPopupRow(rows, label, value) {
		if (!value) {
			return;
		}

		rows.push('<dt>' + escapeHtml(label) + '</dt><dd>' + escapeHtml(value) + '</dd>');
	}

	function airportLabel(airport) {
		var parts = [];

		if (airport.icaoCode) {
			parts.push(airport.icaoCode);
		}

		if (airport.name) {
			parts.push(airport.name);
		}

		if (airport.city || airport.country) {
			parts.push([airport.city, airport.country].filter(Boolean).join(', '));
		}

		return parts.join(' - ');
	}

	function buildPopup(route) {
		var rows = [];
		var title = route.flightNumber || getText('routeTitle', 'FlightLinq route');

		addPopupRow(rows, getText('route', 'Route'), route.label);
		addPopupRow(rows, getText('departure', 'Departure'), airportLabel(route.departure || {}));
		addPopupRow(rows, getText('arrival', 'Arrival'), airportLabel(route.arrival || {}));
		addPopupRow(rows, getText('aircraftTypes', 'Aircraft'), route.aircraftTypes);
		addPopupRow(rows, getText('distance', 'Distance'), route.distance);
		addPopupRow(rows, getText('description', 'Description'), route.description);

		return '<div class="flightlinq-map-popup"><strong class="flightlinq-map-popup__title">' + escapeHtml(title) + '</strong><dl>' + rows.join('') + '</dl></div>';
	}

	function addEndpointMarker(map, latLng, popup) {
		return window.L.circleMarker(latLng, {
			radius: 5,
			color: '#0f6fa8',
			weight: 2,
			opacity: 0.95,
			fillColor: '#ff9f1c',
			fillOpacity: 0.88,
		}).addTo(map).bindPopup(popup);
	}

	function parseMapData(container) {
		var dataId = container.getAttribute('data-routes-data');
		var dataNode = dataId ? document.getElementById(dataId) : null;

		if (!dataNode) {
			return null;
		}

		try {
			return JSON.parse(dataNode.textContent || '{}');
		} catch (error) {
			return null;
		}
	}

	function getLatLng(airport) {
		if (!airport || typeof airport.latitude !== 'number' || typeof airport.longitude !== 'number') {
			return null;
		}

		return [airport.latitude, airport.longitude];
	}

	function getConfiguredHeight(container) {
		var height = parseInt(container.getAttribute('data-map-height'), 10);

		if (!height) {
			height = parseInt(window.getComputedStyle(container).getPropertyValue('--flightlinq-map-height'), 10);
		}

		if (!height || height < 300) {
			return 520;
		}

		return Math.min(height, 1000);
	}

	function ensureMapHeight(container) {
		var height = getConfiguredHeight(container);

		container.style.setProperty('--flightlinq-map-height', height + 'px');

		if (container.offsetHeight < 100) {
			container.style.height = height + 'px';
			container.style.minHeight = '300px';
		}
	}

	function renderMap(container) {
		var data = parseMapData(container);

		if (!data || !data.tileLayer || !Array.isArray(data.routes) || !data.routes.length) {
			setMapMessage(container, getText('noMapData', 'No usable map data is available.'));
			return;
		}

		if (!window.L) {
			setMapMessage(container, getText('leafletUnavailable', 'Leaflet could not be loaded.'));
			return;
		}

		clearMapMessage(container);
		ensureMapHeight(container);

		var map = window.L.map(container, {
			scrollWheelZoom: true,
		});
		var bounds = window.L.latLngBounds();

		window.L.tileLayer(data.tileLayer.url, {
			attribution: data.tileLayer.attribution || '',
			maxZoom: 19,
		}).addTo(map);

		data.routes.forEach(function (route) {
			var departure = getLatLng(route.departure);
			var arrival = getLatLng(route.arrival);

			if (!departure || !arrival) {
				return;
			}

			var popup = buildPopup(route);
			var line = window.L.polyline([departure, arrival], {
				color: '#1477b8',
				weight: 2.6,
				opacity: 0.78,
				lineCap: 'round',
				lineJoin: 'round',
			}).addTo(map);

			line.bindPopup(popup);
			addEndpointMarker(map, departure, popup);
			addEndpointMarker(map, arrival, popup);
			bounds.extend(departure);
			bounds.extend(arrival);
		});

		if (bounds.isValid()) {
			map.fitBounds(bounds, {
				padding: [28, 28],
				maxZoom: 8,
			});
		}

		window.setTimeout(function () {
			map.invalidateSize();

			if (bounds.isValid()) {
				map.fitBounds(bounds, {
					padding: [28, 28],
					maxZoom: 8,
				});
			}
		}, 150);
	}

	function initMap(container) {
		if (container.getAttribute('data-flightlinq-map-ready') === '1') {
			return;
		}

		container.setAttribute('data-flightlinq-map-ready', '1');
		ensureLeafletCss();

		if (window.L) {
			renderMap(container);
			return;
		}

		ensureLeafletScript()
			.then(function () {
				renderMap(container);
			})
			.catch(function () {
				setMapMessage(container, getText('leafletUnavailable', 'Leaflet could not be loaded.'));
			});
	}

	function initMaps() {
		document.querySelectorAll('.flightlinq-leaflet-map').forEach(initMap);
	}

	window.FLQRoutesMap = {
		init: initMaps,
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initMaps);
	} else {
		initMaps();
	}

	window.addEventListener('load', initMaps);
})(window, document);
