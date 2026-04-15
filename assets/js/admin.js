/**
 * EasyCommerce Email Tester — Admin JS
 *
 * Responsibilities:
 *   1. Show/hide the relevant ID field based on the selected email type.
 *   2. Initialize Select2 on searchable dropdowns backed by the plugin REST API.
 *   3. Switch tabs (Email #1, Email #2 …) in the results panel.
 *   4. Toggle the HTML source block for each captured email entry.
 *   5. Toggle the placeholder details block for each captured email entry.
 *   6. Auto-resize preview iframes to fit their content.
 *
 * @since 1.0.0
 */
( function ( $ ) {
	'use strict';

	// -------------------------------------------------------------------------
	// 1. Email type → ID field visibility  (Testing page only)
	// -------------------------------------------------------------------------

	var $typeSelect    = $( '#ect-email-type' );
	var $orderField    = $( '#ect-order-id-field' );
	var $userField     = $( '#ect-user-id-field' );
	var $cartHashField = $( '#ect-cart-hash-field' );

	/**
	 * Show the correct ID field for the currently selected email type,
	 * hide the others.
	 */
	function syncIdFields() {
		var type = $typeSelect.val();

		$orderField.hide();
		$userField.hide();
		$cartHashField.hide();

		if ( type && type.indexOf( 'order_' ) === 0 ) {
			$orderField.show();
		} else if ( type === 'new_account' ) {
			$userField.show();
		} else if ( type === 'abandoned_cart' ) {
			$cartHashField.show();
		}
	}

	if ( $typeSelect.length ) {
		$typeSelect.on( 'change', syncIdFields );
		syncIdFields();
	}

	// -------------------------------------------------------------------------
	// 2. Select2 — searchable dropdowns backed by the plugin REST API
	// -------------------------------------------------------------------------

	if ( typeof $.fn.select2 === 'function' && typeof ectAdmin !== 'undefined' ) {
		$( '.ect-select2' ).each( function () {
			var $select   = $( this );
			var endpoint  = $select.data( 'endpoint' );
			var placeholder = $select.data( 'placeholder' ) || '';

			$select.select2( {
				placeholder:    placeholder,
				allowClear:     true,
				minimumInputLength: 0,
				width:          '100%',
				ajax: {
					url:            ectAdmin.restUrl + endpoint,
					dataType:       'json',
					delay:          300,
					headers:        { 'X-WP-Nonce': ectAdmin.nonce },
					data: function ( params ) {
						return {
							search:   params.term || '',
							per_page: 20,
						};
					},
					processResults: function ( data ) {
						return { results: Array.isArray( data ) ? data : [] };
					},
					cache: true,
				},
			} );
		} );
	}

	// -------------------------------------------------------------------------
	// 3. Tab switching — Email #1, Email #2 … in the results panel
	// -------------------------------------------------------------------------

	$( document ).on( 'click', '.ect-tab-btn', function () {
		var $btn  = $( this );
		var $card = $btn.closest( '.ect-results-card' );

		// Deactivate all tabs and hide all panels within this card.
		$card.find( '.ect-tab-btn' ).attr( 'aria-selected', 'false' );
		$card.find( '.ect-tab-panel' ).attr( 'hidden', '' );

		// Activate the clicked tab and show its panel.
		$btn.attr( 'aria-selected', 'true' );
		$card.find( '#' + $btn.attr( 'aria-controls' ) ).removeAttr( 'hidden' );
	} );

	// -------------------------------------------------------------------------
	// 4 & 5. Generic collapsible toggle (source + placeholders)  (was 3 & 4)
	//
	// Buttons carry:  class="ect-toggle-source"  or  class="ect-toggle-placeholders"
	//                 data-target="<id-of-block-to-toggle>"
	//                 aria-expanded="false"
	//
	// Target blocks use the HTML `hidden` attribute for initial state.
	// -------------------------------------------------------------------------

	$( document ).on( 'click', '.ect-toggle-source, .ect-toggle-placeholders', function () {
		var $btn     = $( this );
		var targetId = $btn.data( 'target' );
		var $block   = $( '#' + targetId );

		if ( ! $block.length ) {
			return;
		}

		var isHidden = $block.attr( 'hidden' ) !== undefined;

		if ( isHidden ) {
			// Expand.
			$block.removeAttr( 'hidden' );
			$btn.attr( 'aria-expanded', 'true' );
			$btn.text( $btn.text().trim().replace( /^Show\b/, 'Hide' ) );
		} else {
			// Collapse.
			$block.attr( 'hidden', '' );
			$btn.attr( 'aria-expanded', 'false' );
			$btn.text( $btn.text().trim().replace( /^Hide\b/, 'Show' ) );
		}
	} );

	// -------------------------------------------------------------------------
	// 6. Auto-resize preview iframes to avoid internal scroll bars
	// -------------------------------------------------------------------------

	function resizeIframe( iframe ) {
		try {
			var body = iframe.contentDocument && iframe.contentDocument.body;
			if ( body ) {
				iframe.style.height = ( body.scrollHeight + 32 ) + 'px';
			}
		} catch ( e ) {
			// Cross-origin or not yet loaded — ignore.
		}
	}

	$( document ).on( 'load', '.ect-preview-iframe', function () {
		resizeIframe( this );
	} );

	// Also attempt resize for any iframes already loaded on DOMContentLoaded.
	$( '.ect-preview-iframe' ).each( function () {
		resizeIframe( this );
	} );

} )( jQuery );
