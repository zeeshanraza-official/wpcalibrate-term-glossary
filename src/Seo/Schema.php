<?php
/**
 * Schema.org JSON-LD Structured Data generator.
 *
 * @package WPCalibrate\TermGlossary
 */

declare( strict_types=1 );

namespace WPCalibrate\TermGlossary\Seo;

use WPCalibrate\TermGlossary\Glossary\PostType;
use WPCalibrate\TermGlossary\Infrastructure\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Schema
 */
class Schema {

	/**
	 * Register structured data output hook.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'wp_head', [ self::class, 'output_schema' ], 30 );
	}

	/**
	 * Output JSON-LD schema in wp_head.
	 *
	 * @return void
	 */
	public static function output_schema(): void {
		if ( is_admin() || is_feed() ) {
			return;
		}

		if ( is_singular( PostType::POST_TYPE ) ) {
			self::output_defined_term();
		}
	}

	/**
	 * Generate and output DefinedTerm schema on single term pages.
	 *
	 * @return void
	 */
	public static function output_defined_term(): void {
		if ( ! Settings::get( 'enable_defined_term_schema', true ) ) {
			return;
		}

		$post = get_post();
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$short_def = (string) get_post_meta( $post->ID, '_wpctg_short_definition', true );
		if ( '' === $short_def ) {
			$short_def = wp_trim_words( wp_strip_all_tags( $post->post_content ), 30 );
		}

		$aliases_raw = (string) get_post_meta( $post->ID, '_wpctg_aliases', true );
		$aliases     = array_filter( array_map( 'trim', explode( ',', $aliases_raw ) ) );

		$schema = [
			'@context'    => 'https://schema.org',
			'@type'       => 'DefinedTerm',
			'@id'         => get_permalink( $post->ID ) . '#defined-term',
			'name'        => get_the_title( $post->ID ),
			'description' => wp_strip_all_tags( $short_def ),
			'url'         => get_permalink( $post->ID ),
			'inLanguage'  => get_bloginfo( 'language' ),
		];

		if ( ! empty( $aliases ) ) {
			$schema['alternateName'] = array_values( $aliases );
		}

		$source_url = (string) get_post_meta( $post->ID, '_wpctg_source_url', true );
		if ( '' !== $source_url ) {
			$schema['sameAs'] = esc_url_raw( $source_url );
		}

		/**
		 * Filter DefinedTerm JSON-LD schema array.
		 *
		 * @since 1.0.0
		 * @param array    $schema Schema data.
		 * @param \WP_Post $post   Current post.
		 */
		$schema = apply_filters( 'wpctg_schema_defined_term', $schema, $post );

		if ( ! empty( $schema ) ) {
			echo "\n<!-- WPCalibrate Term Glossary Structured Data -->\n";
			echo '<script type="application/ld+json">' . "\n";
			echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
			echo "\n</script>\n";
		}
	}
}
