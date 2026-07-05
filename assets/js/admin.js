/**
 * Admin scripts for FlightLinq API.
 *
 * @package FlightLinq_API
 * @since 1.0.0
 * @since 1.0.5 Simplified: removed advanced diagnostics.
 * @since 1.2.0 Added the code copy feature.
 * @date 2026-05-23
 */

document.addEventListener( 'DOMContentLoaded', function() {
	const clearCacheButton = document.getElementById( 'flightlinq-clear-cache' );
	const connectionTestButton = document.getElementById( 'flightlinq-connection-test' );
	const connectionTestResults = document.getElementById( 'flightlinq-connection-test-results' );

	// Handle the clear cache button.
	if ( clearCacheButton ) {
		let isConfirming = false;

		clearCacheButton.addEventListener( 'click', function( event ) {
			event.preventDefault();

			const cacheMessage = document.querySelector( '.flightlinq-cache-message' );
			if ( ! cacheMessage ) {
				return;
			}

			// First click: confirm.
			if ( ! isConfirming ) {
				isConfirming = true;
				const originalText = clearCacheButton.textContent;
				clearCacheButton.textContent = flightlinqApi.confirmClearCache;
				clearCacheButton.classList.add( 'flightlinq-button--confirm' );

				// Cancel when clicking elsewhere or after 5 seconds.
				const cancelHandler = function() {
					isConfirming = false;
					clearCacheButton.textContent = originalText;
					clearCacheButton.classList.remove( 'flightlinq-button--confirm' );
					document.removeEventListener( 'click', outsideClickHandler );
				};

				const outsideClickHandler = function( e ) {
					if ( ! clearCacheButton.contains( e.target ) ) {
						cancelHandler();
					}
				};

				setTimeout( cancelHandler, 5000 );
				document.addEventListener( 'click', outsideClickHandler, { once: true } );
				return;
			}

			// Second click: execute.
			isConfirming = false;
			clearCacheButton.textContent = flightlinqApi.cacheClearing;
			clearCacheButton.disabled = true;

			const data = new URLSearchParams();
			data.append( 'action', 'flightlinq_clear_cache' );
			data.append( 'nonce', flightlinqApi.nonce );

			fetch( ajaxurl, {
				method: 'POST',
				body: data,
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
			} )
				.then( response => response.json() )
				.then( result => {
					if ( result.success ) {
						cacheMessage.innerHTML = '<div class="flightlinq-admin-notice flightlinq-admin-notice--success">' + flightlinqApi.cacheCleared + '</div>';
					} else {
						cacheMessage.innerHTML = '<div class="flightlinq-admin-notice flightlinq-admin-notice--error">' + ( result.data || flightlinqApi.cacheClearError ) + '</div>';
					}
				} )
				.catch( error => {
					cacheMessage.innerHTML = '<div class="flightlinq-admin-notice flightlinq-admin-notice--error">' + flightlinqApi.cacheClearError + '</div>';
				} )
				.finally( () => {
					clearCacheButton.textContent = flightlinqApi.cacheClearButton;
					clearCacheButton.disabled = false;
					clearCacheButton.classList.remove( 'flightlinq-button--confirm' );

					// Clear the message after 3 seconds.
					setTimeout( () => {
						cacheMessage.innerHTML = '';
					}, 3000 );
				} );
		} );
	}

	// Handle the connection test button.
	if ( connectionTestButton ) {
		connectionTestButton.addEventListener( 'click', function() {
			if ( ! connectionTestResults ) {
				return;
			}

			connectionTestResults.innerHTML = '<p>' + flightlinqApi.diagnosticLoading + '</p>';

			const data = new URLSearchParams();
			data.append( 'action', 'flightlinq_connection_test' );
			data.append( 'nonce', flightlinqApi.nonce );

			fetch( ajaxurl, {
				method: 'POST',
				body: data,
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
			} )
				.then( response => response.json() )
				.then( result => {
					if ( result.success ) {
						connectionTestResults.innerHTML = '<div class="notice notice-success"><p>' + result.data + '</p></div>';
					} else {
						connectionTestResults.innerHTML = '<div class="notice notice-error"><p>' + result.data + '</p></div>';
					}
				} )
				.catch( error => {
					connectionTestResults.innerHTML = '<div class="notice notice-error"><p>' + flightlinqApi.error + '</p></div>';
				} );
		} );
	}

	// Handle code copy buttons.
	const copyCodeButtons = document.querySelectorAll( '.flightlinq-copy-code' );
	copyCodeButtons.forEach( function( button ) {
		button.addEventListener( 'click', function() {
			const targetId = this.getAttribute( 'data-target' );
			const codeElement = document.getElementById( targetId );

			if ( ! codeElement ) {
				return;
			}

			const codeText = codeElement.textContent;

			// Use the Clipboard API when available.
			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( codeText )
					.then( function() {
						const originalText = button.textContent;
						button.textContent = 'Copied';
						button.classList.add( 'is-copied' );
						button.disabled = true;

						setTimeout( function() {
							button.textContent = originalText;
							button.classList.remove( 'is-copied' );
							button.disabled = false;
						}, 2000 );
					} )
					.catch( function() {
						// Fallback when the Clipboard API fails.
						fallbackCopyText( codeText, button );
					} );
			} else {
				// Fallback for browsers that do not support the Clipboard API.
				fallbackCopyText( codeText, button );
			}
		} );
	} );

	// Fallback to copy text without the Clipboard API.
	function fallbackCopyText( text, button ) {
		const textArea = document.createElement( 'textarea' );
		textArea.value = text;
		textArea.style.position = 'fixed';
		textArea.style.left = '-9999px';
		document.body.appendChild( textArea );
		textArea.select();

		try {
			document.execCommand( 'copy' );
			const originalText = button.textContent;
			button.textContent = 'Copied';
			button.classList.add( 'is-copied' );
			button.disabled = true;

			setTimeout( function() {
				button.textContent = originalText;
				button.classList.remove( 'is-copied' );
				button.disabled = false;
			}, 2000 );
		} catch ( err ) {
			console.error( 'Error while copying', err );
		}

		document.body.removeChild( textArea );
	}
} );
