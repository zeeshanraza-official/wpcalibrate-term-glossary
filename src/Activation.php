<?php
/**
 * Plugin Activation Handler.
 *
 * @package WPCalibrate\TermGlossary
 */

declare( strict_types=1 );

namespace WPCalibrate\TermGlossary;

use WPCalibrate\TermGlossary\Glossary\Capabilities;
use WPCalibrate\TermGlossary\Glossary\PostType;
use WPCalibrate\TermGlossary\Infrastructure\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Activation
 */
class Activation {

	/**
	 * Run activation tasks.
	 *
	 * @return void
	 */
	public static function activate(): void {
		// Grant custom capabilities.
		Capabilities::register_caps();

		// Register post type and taxonomy prior to flushing rewrite rules.
		PostType::register();

		// Flush rewrite rules once.
		flush_rewrite_rules();

		// Initialize default options if not already present.
		$existing_options = get_option( Settings::OPTION_NAME );
		if ( false === $existing_options ) {
			add_option( Settings::OPTION_NAME, Settings::get_defaults() );
		}

		// Store installed version.
		update_option( 'wpctg_version', WPCTG_VERSION );
	}
}
