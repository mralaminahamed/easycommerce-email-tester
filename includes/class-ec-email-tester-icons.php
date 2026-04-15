<?php
/**
 * SVG icon library for EasyCommerce Email Tester admin UI.
 *
 * Icons are sourced from the Lucide icon set (MIT licence).
 * All strings are built entirely from internal constants — no user input
 * is ever interpolated — so the output is safe to echo without escaping.
 *
 * Usage in templates:
 *   EC_Email_Tester_Icons::render( 'trash' );   // echoes the SVG
 *   $html = EC_Email_Tester_Icons::get( 'mail' ); // returns the SVG string
 *
 * @since   1.0.0
 * @package EC_Email_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class EC_Email_Tester_Icons
 */
class EC_Email_Tester_Icons {

	/**
	 * SVG icon definitions keyed by name.
	 *
	 * Each value is the inner content of a 24×24 viewBox SVG (paths only).
	 * The outer <svg> wrapper is added by get() / render().
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	private static array $icons = [

		// Paper-plane / send — replaces dashicons-email-alt2
		'send' => '<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>',

		// Envelope — replaces dashicons-email and dashicons-email-alt
		'mail' => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',

		// Eye — replaces dashicons-visibility
		'eye' => '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',

		// Circle with checkmark — replaces dashicons-yes-alt
		'circle-check' => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',

		// Gear — replaces dashicons-admin-settings
		'settings' => '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',

		// Trash can — replaces dashicons-trash
		'trash' => '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/>',

		// Triangle with exclamation — replaces dashicons-warning
		'triangle-alert' => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',

		// Bulleted list — replaces dashicons-list-view
		'list' => '<line x1="8" x2="21" y1="6" y2="6"/><line x1="8" x2="21" y1="12" y2="12"/><line x1="8" x2="21" y1="18" y2="18"/><line x1="3" x2="3.01" y1="6" y2="6"/><line x1="3" x2="3.01" y1="12" y2="12"/><line x1="3" x2="3.01" y1="18" y2="18"/>',

		// Left arrow — replaces dashicons-arrow-left-alt
		'arrow-left' => '<path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>',

		// Floppy disk — replaces dashicons-saved
		'save' => '<path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7"/><path d="M7 3v4a1 1 0 0 0 1 1h7"/>',
	];

	/**
	 * Return the SVG markup for the named icon.
	 *
	 * The returned string is constructed entirely from internal constants and
	 * is safe to echo directly without additional escaping.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Icon name (e.g. 'trash', 'mail').
	 * @return string SVG HTML string, or empty string for unknown names.
	 */
	public static function get( string $name ): string {
		$inner = self::$icons[ $name ] ?? '';

		if ( '' === $inner ) {
			return '';
		}

		return sprintf(
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" '
			. 'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" '
			. 'class="ect-icon" aria-hidden="true" focusable="false">%s</svg>',
			$inner
		);
	}

	/**
	 * Echo the SVG markup for the named icon.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Icon name (e.g. 'trash', 'mail').
	 */
	public static function render( string $name ): void {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- output is built from internal constants only.
		echo self::get( $name );
	}
}
