<?php
/**
 * Gutenberg block registration for Glossary Index.
 *
 * @package WPCalibrate\TermGlossary
 */

declare( strict_types=1 );

namespace WPCalibrate\TermGlossary\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Block
 */
class Block {

	/**
	 * Register block and its assets.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'init', [ self::class, 'register_block' ] );
	}

	/**
	 * Register block type.
	 *
	 * @return void
	 */
	public static function register_block(): void {
		// Register editor script.
		wp_register_script(
			'wpctg-glossary-index-block-editor',
			WPCTG_URL . 'blocks/glossary-index/index.js',
			[ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ],
			WPCTG_VERSION,
			true
		);

		// Register editor style.
		wp_register_style(
			'wpctg-glossary-index-block-editor-style',
			WPCTG_URL . 'blocks/glossary-index/editor.css',
			[],
			WPCTG_VERSION
		);

		register_block_type(
			WPCTG_DIR . 'blocks/glossary-index',
			[
				'render_callback' => [ GlossaryIndex::class, 'render' ],
			]
		);
	}
}
