<?php
/**
 * Single term page rendering and template integration.
 *
 * @package WPCalibrate\TermGlossary
 */

declare( strict_types=1 );

namespace WPCalibrate\TermGlossary\Glossary;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SingleTerm
 */
class SingleTerm {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'the_content', [ self::class, 'filter_single_content' ], 20 );
		add_filter( 'template_include', [ self::class, 'template_loader' ] );
	}

	/**
	 * Locate template with theme override support.
	 *
	 * @param string $template Current template file.
	 * @return string Filtered template file.
	 */
	public static function template_loader( string $template ): string {
		if ( ! is_singular( PostType::POST_TYPE ) ) {
			return $template;
		}

		// Look for theme override: single-wpctg_term.php or in wpcalibrate-term-glossary/ subfolder.
		$theme_file = locate_template( [ 'wpcalibrate-term-glossary/single-wpctg_term.php', 'single-wpctg_term.php' ] );
		if ( '' !== $theme_file ) {
			return $theme_file;
		}

		// In block themes (FSE), avoid forcing classic PHP template unless explicitly overridden by theme.
		if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
			return $template;
		}

		$plugin_template = WPCTG_DIR . 'templates/single-wpctg_term.php';
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}

		return $template;
	}

	/**
	 * Augment single post content with definition metadata if default theme template is used.
	 *
	 * @param string $content Post content.
	 * @return string Augment content.
	 */
	public static function filter_single_content( string $content ): string {
		if ( ! is_singular( PostType::POST_TYPE ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$post_id       = get_the_ID();
		$pronunciation = (string) get_post_meta( $post_id, '_wpctg_pronunciation', true );
		$source_url    = (string) get_post_meta( $post_id, '_wpctg_source_url', true );
		$aliases       = (string) get_post_meta( $post_id, '_wpctg_aliases', true );
		$related_raw   = (string) get_post_meta( $post_id, '_wpctg_related_terms', true );

		$terms_cats = get_the_terms( $post_id, PostType::TAXONOMY );
		$cat_list   = ! empty( $terms_cats ) && ! is_wp_error( $terms_cats ) ? wp_list_pluck( $terms_cats, 'name' ) : [];

		ob_start();
		?>
		<div class="wpctg-single-term-box">
			<?php if ( '' !== $pronunciation ) : ?>
				<div class="wpctg-single-pronunciation">
					<strong><?php esc_html_e( 'Pronunciation:', 'wpcalibrate-term-glossary' ); ?></strong>
					<span><?php echo esc_html( $pronunciation ); ?></span>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $cat_list ) ) : ?>
				<div class="wpctg-single-categories">
					<strong><?php esc_html_e( 'Category:', 'wpcalibrate-term-glossary' ); ?></strong>
					<span><?php echo esc_html( implode( ', ', $cat_list ) ); ?></span>
				</div>
			<?php endif; ?>

			<?php if ( '' !== $aliases ) : ?>
				<div class="wpctg-single-aliases">
					<strong><?php esc_html_e( 'Also known as:', 'wpcalibrate-term-glossary' ); ?></strong>
					<span><?php echo esc_html( $aliases ); ?></span>
				</div>
			<?php endif; ?>

			<?php if ( '' !== $related_raw ) : ?>
				<div class="wpctg-single-related">
					<strong><?php esc_html_e( 'Related Terms:', 'wpcalibrate-term-glossary' ); ?></strong>
					<span><?php echo esc_html( $related_raw ); ?></span>
				</div>
			<?php endif; ?>

			<?php if ( '' !== $source_url ) : ?>
				<div class="wpctg-single-source">
					<strong><?php esc_html_e( 'Reference / Authority:', 'wpcalibrate-term-glossary' ); ?></strong>
					<a href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $source_url ); ?></a>
				</div>
			<?php endif; ?>
		</div>
		<?php
		$meta_html = ob_get_clean();

		/**
		 * Filter the single glossary term metadata markup.
		 *
		 * @since 1.0.0
		 * @param string $meta_html The generated HTML metadata block.
		 * @param int    $post_id   The term post ID.
		 */
		$meta_html = apply_filters( 'wpctg_single_term_meta_html', $meta_html, $post_id );

		return $content . $meta_html;
	}
}
