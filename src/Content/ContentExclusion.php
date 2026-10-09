<?php
/**
 * Per-content exclusion metabox and filtering.
 *
 * @package WPCalibrate\TermGlossary
 */

declare( strict_types=1 );

namespace WPCalibrate\TermGlossary\Content;

use WPCalibrate\TermGlossary\Infrastructure\Settings;
use WPCalibrate\TermGlossary\Glossary\PostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ContentExclusion
 */
class ContentExclusion {

	public const META_KEY     = '_wpctg_exclude_glossary';
	public const NONCE_ACTION = 'wpctg_save_content_exclusion';
	public const NONCE_NAME   = 'wpctg_exclusion_nonce';

	/**
	 * Register metabox hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'add_meta_boxes', [ self::class, 'add_exclusion_meta_box' ] );
		add_action( 'save_post', [ self::class, 'save_exclusion_meta' ], 10, 2 );
	}

	/**
	 * Add exclusion metabox on supported post types.
	 *
	 * @return void
	 */
	public static function add_exclusion_meta_box(): void {
		$supported_types = (array) Settings::get( 'supported_post_types', [ 'post', 'page' ] );

		foreach ( $supported_types as $post_type ) {
			if ( PostType::POST_TYPE === $post_type ) {
				continue;
			}

			add_meta_box(
				'wpctg_content_exclusion',
				__( 'Glossary Processing', 'wpcalibrate-term-glossary' ),
				[ self::class, 'render_meta_box' ],
				$post_type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render the exclusion metabox.
	 *
	 * @param \WP_Post $post Current post.
	 * @return void
	 */
	public static function render_meta_box( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		$is_excluded = (int) get_post_meta( $post->ID, self::META_KEY, true );
		?>
		<label for="wpctg_exclude_glossary">
			<input type="checkbox" id="wpctg_exclude_glossary" name="wpctg_exclude_glossary" value="1" <?php checked( $is_excluded, 1 ); ?> />
			<?php esc_html_e( 'Disable automatic glossary linking for this content', 'wpcalibrate-term-glossary' ); ?>
		</label>
		<p class="description" style="margin-top:6px;">
			<?php esc_html_e( 'Check this box to prevent glossary terms from being automatically highlighted or linked in this article.', 'wpcalibrate-term-glossary' ); ?>
		</p>
		<?php
	}

	/**
	 * Save exclusion meta.
	 *
	 * @param int            $post_id Post ID.
	 * @param \WP_Post|null $post    Optional post object.
	 * @return void
	 */
	public static function save_exclusion_meta( int $post_id, ?\WP_Post $post = null ): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$exclude = ! empty( $_POST['wpctg_exclude_glossary'] ) ? 1 : 0;
		update_post_meta( $post_id, self::META_KEY, $exclude );
	}

	/**
	 * Check if a post is excluded from automatic glossary processing.
	 *
	 * @param int $post_id Post ID.
	 * @return bool True if excluded.
	 */
	public static function is_post_excluded( int $post_id ): bool {
		$meta = (int) get_post_meta( $post_id, self::META_KEY, true );
		return 1 === $meta;
	}
}
