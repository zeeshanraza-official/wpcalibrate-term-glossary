/**
 * WPCalibrate Term Glossary - Frontend Scripts
 *
 * @package WPCalibrate\TermGlossary
 */

/* global wpctgFrontend */
( function() {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function() {
		initTooltips();
		initLiveSearch();
	} );

	/**
	 * Initialize accessible interactive tooltips.
	 */
	function initTooltips() {
		var activeTooltip = null;
		var activeTrigger = null;

		function closeActive() {
			if ( activeTooltip ) {
				activeTooltip.classList.remove( 'wpctg-is-active' );
				activeTooltip.setAttribute( 'aria-hidden', 'true' );
				if ( activeTrigger ) {
					activeTrigger.setAttribute( 'aria-expanded', 'false' );
				}
				activeTooltip = null;
				activeTrigger = null;
			}
		}

		// Adjust positioning to avoid viewport clipping.
		function adjustPosition( tooltip, trigger ) {
			tooltip.classList.remove( 'wpctg-pos-bottom', 'wpctg-pos-left', 'wpctg-pos-right' );

			var rect = tooltip.getBoundingClientRect();
			var triggerRect = trigger.getBoundingClientRect();
			var viewportWidth = window.innerWidth || document.documentElement.clientWidth;

			// Top overflow.
			if ( rect.top < 10 ) {
				tooltip.classList.add( 'wpctg-pos-bottom' );
			}

			// Left overflow.
			if ( rect.left < 10 ) {
				tooltip.classList.add( 'wpctg-pos-left' );
			} else if ( rect.right > viewportWidth - 10 ) {
				// Right overflow.
				tooltip.classList.add( 'wpctg-pos-right' );
			}
		}

		// Click / Tap handling.
		document.addEventListener( 'click', function( e ) {
			var trigger = e.target.closest( '.wpctg-term-trigger' );

			if ( trigger ) {
				var wrapper = trigger.closest( '.wpctg-term-wrapper' );
				if ( ! wrapper ) {
					return;
				}

				var tooltip = wrapper.querySelector( '.wpctg-tooltip' );
				if ( ! tooltip ) {
					return;
				}

				// If it's a mobile touch tap or button trigger, toggle tooltip.
				var isAlreadyOpen = tooltip.classList.contains( 'wpctg-is-active' );

				if ( isAlreadyOpen ) {
					// If it's a link and already open, allow navigating on second tap.
					if ( trigger.tagName.toLowerCase() === 'a' && trigger.classList.contains( 'wpctg-has-link' ) ) {
						return; // Allow standard link click.
					}
					e.preventDefault();
					closeActive();
				} else {
					// Open tooltip and prevent immediate page navigation on first tap.
					e.preventDefault();
					closeActive();

					tooltip.classList.add( 'wpctg-is-active' );
					tooltip.setAttribute( 'aria-hidden', 'false' );
					trigger.setAttribute( 'aria-expanded', 'true' );

					activeTooltip = tooltip;
					activeTrigger = trigger;

					adjustPosition( tooltip, trigger );
				}
				return;
			}

			// If clicked inside active tooltip, do not close.
			if ( activeTooltip && e.target.closest( '.wpctg-tooltip' ) ) {
				return;
			}

			// Clicked outside: close active tooltip.
			closeActive();
		} );

		// Hover and Focus adjustments for keyboard and pointer users.
		document.addEventListener( 'mouseover', function( e ) {
			var trigger = e.target.closest( '.wpctg-term-trigger' );
			if ( trigger ) {
				var wrapper = trigger.closest( '.wpctg-term-wrapper' );
				var tooltip = wrapper ? wrapper.querySelector( '.wpctg-tooltip' ) : null;
				if ( tooltip ) {
					adjustPosition( tooltip, trigger );
				}
			}
		} );

		document.addEventListener( 'focusin', function( e ) {
			var trigger = e.target.closest( '.wpctg-term-trigger' );
			if ( trigger ) {
				var wrapper = trigger.closest( '.wpctg-term-wrapper' );
				var tooltip = wrapper ? wrapper.querySelector( '.wpctg-tooltip' ) : null;
				if ( tooltip ) {
					adjustPosition( tooltip, trigger );
				}
			}
		} );

		// Close on Escape key.
		document.addEventListener( 'keydown', function( e ) {
			if ( 'Escape' === e.key || 'Esc' === e.key ) {
				closeActive();
			}
		} );
	}

	/**
	 * Initialize live search on the glossary index.
	 */
	function initLiveSearch() {
		var searchInputs = document.querySelectorAll( '.wpctg-index-search-input' );

		searchInputs.forEach( function( input ) {
			var container = input.closest( '.wpctg-index-container' );
			if ( ! container ) {
				return;
			}

			var grid = container.querySelector( '.wpctg-index-grid' );
			var cards = grid ? Array.from( grid.querySelectorAll( '.wpctg-index-card' ) ) : [];
			var debounceTimer = null;

			input.addEventListener( 'input', function() {
				clearTimeout( debounceTimer );
				var query = input.value.trim().toLowerCase();

				debounceTimer = setTimeout( function() {
					var matchCount = 0;

					cards.forEach( function( card ) {
						var title = ( card.querySelector( '.wpctg-index-card-title' )?.textContent || '' ).toLowerCase();
						var def = ( card.querySelector( '.wpctg-index-card-def' )?.textContent || '' ).toLowerCase();
						var aliases = ( card.querySelector( '.wpctg-index-card-meta' )?.textContent || '' ).toLowerCase();

						if ( '' === query || title.includes( query ) || def.includes( query ) || aliases.includes( query ) ) {
							card.style.display = '';
							matchCount++;
						} else {
							card.style.display = 'none';
						}
					} );

					// Announce results for assistive technologies.
					var liveRegion = container.querySelector( '.wpctg-live-announcer' );
					if ( liveRegion ) {
						liveRegion.textContent = matchCount + ' terms found matching "' + query + '".';
					}
				}, 200 );
			} );
		} );
	}
} )();
