<?php
/**
 * Main Plugin Coordinator.
 *
 * @package WPCalibrate\TermGlossary
 */

declare( strict_types=1 );

namespace WPCalibrate\TermGlossary;

use WPCalibrate\TermGlossary\Glossary\PostType;
use WPCalibrate\TermGlossary\Glossary\SingleTerm;
use WPCalibrate\TermGlossary\Glossary\Capabilities;
use WPCalibrate\TermGlossary\Admin\TermMetaBox;
use WPCalibrate\TermGlossary\Admin\AdminMenu;
use WPCalibrate\TermGlossary\Content\ContentExclusion;
use WPCalibrate\TermGlossary\Content\Detector;
use WPCalibrate\TermGlossary\Frontend\GlossaryIndex;
use WPCalibrate\TermGlossary\Frontend\Block;
use WPCalibrate\TermGlossary\Frontend\RestApi;
use WPCalibrate\TermGlossary\Seo\Schema;
use WPCalibrate\TermGlossary\ImportExport\CsvExporter;
use WPCalibrate\TermGlossary\ImportExport\CsvImporter;
use WPCalibrate\TermGlossary\Infrastructure\CacheManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Plugin
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Protected constructor.
	 */
	private function __construct() {}

	/**
	 * Boot all plugin subsystems and hooks.
	 *
	 * @return void
	 */
	public function boot(): void {
		// Load textdomain.
		add_action( 'init', [ self::class, 'load_textdomain' ] );

		// Register Post Type and Taxonomy.
		add_action( 'init', [ PostType::class, 'register' ] );

		// Initialize Cache Manager.
		CacheManager::init();

		// Content detector and frontend tooltips.
		Detector::init();

		// Single term page templates.
		SingleTerm::init();

		// Glossary index shortcode and block.
		GlossaryIndex::init();
		Block::init();

		// REST API.
		RestApi::init();

		// Structured Data.
		Schema::init();

		// Admin subsystems.
		if ( is_admin() ) {
			add_action( 'admin_init', [ Capabilities::class, 'ensure_caps' ] );
			TermMetaBox::init();
			ContentExclusion::init();
			AdminMenu::init();
			CsvExporter::init();
			CsvImporter::init();
		}
	}

	/**
	 * Load translation files.
	 *
	 * @return void
	 */
	public static function load_textdomain(): void {
		load_plugin_textdomain(
			'wpcalibrate-term-glossary',
			false,
			dirname( WPCTG_BASENAME ) . '/languages'
		);
	}
}
