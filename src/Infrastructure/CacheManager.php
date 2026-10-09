<?php
/**
 * Cache Manager for Glossary transient and object caching.
 *
 * @package WPCalibrate\TermGlossary
 */

declare( strict_types=1 );

namespace WPCalibrate\TermGlossary\Infrastructure;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CacheManager
 */
class CacheManager {

	public const CACHE_GROUP       = 'wpctg_glossary';
	public const TRANSIENT_PREFIX  = 'wpctg_cache_';
	public const INDEX_TRANSIENT   = 'wpctg_terms_matching_index';
	public const ALL_TERMS_KEY     = 'wpctg_all_published_terms';

	/**
	 * Register invalidation hooks on posts and taxonomy.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'save_post_wpctg_term', [ self::class, 'invalidate' ] );
		add_action( 'delete_post_wpctg_term', [ self::class, 'invalidate' ] );
		add_action( 'trash_post_wpctg_term', [ self::class, 'invalidate' ] );
		add_action( 'clean_term_cache', [ self::class, 'invalidate' ] );
		add_action( 'wpctg_settings_updated', [ self::class, 'invalidate' ] );

		// AJAX action to purge cache manually from admin.
		add_action( 'wp_ajax_wpctg_purge_cache', [ self::class, 'handle_ajax_purge' ] );
	}

	/**
	 * Invalidate all glossary cache.
	 *
	 * @return void
	 */
	public static function invalidate(): void {
		delete_transient( self::INDEX_TRANSIENT );
		delete_transient( self::ALL_TERMS_KEY );

		if ( function_exists( 'wp_cache_delete_group' ) ) {
			wp_cache_delete_group( self::CACHE_GROUP );
		}

		Logger::log( 'Glossary cache invalidated.' );
	}

	/**
	 * AJAX handler for clearing cache from admin settings.
	 *
	 * @return void
	 */
	public static function handle_ajax_purge(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'wpcalibrate-term-glossary' ) ] );
		}

		check_ajax_referer( 'wpctg_admin_nonce', 'nonce' );

		self::invalidate();
		wp_send_json_success( [ 'message' => __( 'Glossary cache purged successfully.', 'wpcalibrate-term-glossary' ) ] );
	}
}
