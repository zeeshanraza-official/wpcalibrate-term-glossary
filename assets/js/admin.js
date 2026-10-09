/**
 * WPCalibrate Term Glossary - Admin Script
 *
 * @package WPCalibrate\TermGlossary
 */

/* global wpctgAdminData, jQuery */
( function( $ ) {
	'use strict';

	$( function() {
		// Handle Cache Purge Button.
		$( '#wpctg-clear-cache-btn' ).on( 'click', function( e ) {
			e.preventDefault();
			var $btn = $( this );
			var $status = $( '#wpctg-cache-status' );

			$btn.prop( 'disabled', true );
			$status.text( 'Purging...' ).css( 'color', '#64748b' );

			$.post(
				wpctgAdminData.ajaxUrl,
				{
					action: 'wpctg_purge_cache',
					nonce: wpctgAdminData.nonce
				},
				function( response ) {
					$btn.prop( 'disabled', false );
					if ( response.success ) {
						$status.text( 'Cache cleared successfully!' ).css( 'color', '#16a34a' );
					} else {
						$status.text( 'Failed to clear cache.' ).css( 'color', '#dc2626' );
					}
					setTimeout( function() {
						$status.text( '' );
					}, 4000 );
				}
			).fail( function() {
				$btn.prop( 'disabled', false );
				$status.text( 'Error contacting server.' ).css( 'color', '#dc2626' );
			} );
		} );
	} );
} )( jQuery );
