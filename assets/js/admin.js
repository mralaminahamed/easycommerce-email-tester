/**
 * EasyCommerce Email Tester — Admin JS
 *
 * Responsibilities:
 *   1. Show/hide the relevant ID field based on the selected email type.
 *   2. Initialize Select2 on searchable dropdowns backed by the plugin REST API.
 *   3. Switch tabs (Email #1, Email #2 …) in the results panel.
 *   4. Toggle the HTML source / headers / placeholder blocks.
 *   5. Auto-resize preview iframes to fit their content.
 *   6. Settings page — disable retention inputs and dim child fields.
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
	// 4. Generic collapsible toggle (source blocks, headers, placeholders)
	//
	// Buttons carry:  class="ect-toggle-source"  or  class="ect-toggle-placeholders"
	//                 data-target="<id-of-block-to-toggle>"
	//                 data-label-show="Show …"   ← i18n-safe label for collapsed state
	//                 data-label-hide="Hide …"   ← i18n-safe label for expanded state
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

		var isHidden   = $block.attr( 'hidden' ) !== undefined;
		var labelShow  = $btn.data( 'label-show' );
		var labelHide  = $btn.data( 'label-hide' );

		if ( isHidden ) {
			$block.removeAttr( 'hidden' );
			$btn.attr( 'aria-expanded', 'true' );
			if ( labelHide ) {
				var $labelHideSpan = $btn.find( '.ect-toggle-label-text' );
				if ( $labelHideSpan.length ) {
					$labelHideSpan.text( labelHide );
				} else {
					$btn.text( labelHide );
				}
			}
		} else {
			$block.attr( 'hidden', '' );
			$btn.attr( 'aria-expanded', 'false' );
			if ( labelShow ) {
				var $labelShowSpan = $btn.find( '.ect-toggle-label-text' );
				if ( $labelShowSpan.length ) {
					$labelShowSpan.text( labelShow );
				} else {
					$btn.text( labelShow );
				}
			}
		}
	} );

	// -------------------------------------------------------------------------
	// 5. Settings page — disable retention inputs; dim child fields when
	//    the parent "Enable email logging" checkbox is unchecked.
	//
	// .ect-logger-dependents wraps all fields that depend on logger_enabled.
	// Retention number inputs are hard-disabled (not just dimmed) so users
	// can't accidentally edit a value that has no effect.
	// -------------------------------------------------------------------------

	function ectSyncSettings() {
		if ( ! $( '#ect-logger-enabled' ).length ) {
			return;
		}

		var loggerOn = $( '#ect-logger-enabled' ).is( ':checked' );
		var countOn  = loggerOn && $( '#ect-logger-retention-count-enabled' ).is( ':checked' );
		var daysOn   = loggerOn && $( '#ect-logger-retention-days-enabled' ).is( ':checked' );

		// Dim the whole child-fields block when logging is off.
		$( '.ect-logger-dependents' ).toggleClass( 'ect-disabled', ! loggerOn );

		// Disable / enable the retention number inputs.
		$( '#ect-logger-retention-count' ).prop( 'disabled', ! countOn );
		$( '#ect-logger-retention-count' ).closest( '.ect-retention-row' ).toggleClass( 'ect-disabled', ! countOn );

		$( '#ect-logger-retention-days' ).prop( 'disabled', ! daysOn );
		$( '#ect-logger-retention-days' ).closest( '.ect-retention-row' ).toggleClass( 'ect-disabled', ! daysOn );
	}

	$( '#ect-logger-enabled, #ect-logger-retention-count-enabled, #ect-logger-retention-days-enabled' )
		.on( 'change', ectSyncSettings );

	ectSyncSettings();

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
