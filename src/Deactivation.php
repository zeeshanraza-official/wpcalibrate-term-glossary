<?php
/**
 * Plugin Deactivation Handler.
 *
 * @package WPCalibrate\TermGlossary
 */

declare( strict_types=1 );

namespace WPCalibrate\TermGlossary;

use WPCalibrate\TermGlossary\Infrastructure\CacheManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Deactivation
 */
class Deactivation {

	/**
	 * Run deactivation tasks.
	 * Preserves all terms, taxonomies, and user settings.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Invalidate cached terms and transients.
		CacheManager::invalidate();

		// Flush rewrite rules.
		flush_rewrite_rules();
	}
}
