<?php
/**
 * Tooltip and glossary link markup generator.
 *
 * @package WPCalibrate\TermGlossary
 */

declare( strict_types=1 );

namespace WPCalibrate\TermGlossary\Content;

use WPCalibrate\TermGlossary\Infrastructure\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tooltip
 */
class Tooltip {

	private static int $instance_counter = 0;

	/**
	 * Generate replacement markup for a matched glossary term.
	 *
	 * @param array<string, mixed> $candidate Matched candidate info.
	 * @param string               $matched_text Original text segment in content.
	 * @return string HTML replacement markup.
	 */
	public static function render( array $candidate, string $matched_text ): string {
		self::$instance_counter++;
		$tip_id = sprintf( 'wpctg-tip-%d-%d', (int) $candidate['id'], self::$instance_counter );

		$link_mode      = (string) Settings::get( 'link_mode', 'tooltip_link' );
		$enable_tooltip = (bool) Settings::get( 'enable_tooltips', true );
		$show_read_more = (bool) Settings::get( 'show_read_more', true );
		$read_more_text = (string) Settings::get( 'read_more_text', __( 'Read full definition', 'wpcalibrate-term-glossary' ) );
		$tooltip_theme  = (string) Settings::get( 'tooltip_theme', 'light' );

		$term_title = esc_html( (string) $candidate['term'] );
		$short_def  = wp_kses_post( (string) $candidate['short_def'] );
		$term_url   = esc_url( (string) $candidate['url'] );
		$text_html  = esc_html( $matched_text );

		// Mode 1: Link only (no tooltip).
		if ( 'link_only' === $link_mode || ! $enable_tooltip ) {
			$markup = sprintf(
				'<a href="%s" class="wpctg-glossary-term wpctg-term-link" data-term-id="%d" title="%s">%s</a>',
				$term_url,
				(int) $candidate['id'],
				esc_attr( $candidate['term'] ),
				$text_html
			);

			/**
			 * Filter link-only markup.
			 *
			 * @since 1.0.0
			 * @param string $markup Generated markup.
			 * @param array  $candidate Matched candidate data.
			 */
			return apply_filters( 'wpctg_term_link_markup', $markup, $candidate );
		}

		// Tooltip popup container.
		$read_more_markup = '';
		if ( $show_read_more && 'tooltip_only' !== $link_mode ) {
			$read_more_markup = sprintf(
				'<span class="wpctg-tooltip-footer"><a href="%s" class="wpctg-tooltip-more">%s &#8594;</a></span>',
				$term_url,
				esc_html( $read_more_text )
			);
		}

		$tooltip_html = sprintf(
			'<span id="%s" class="wpctg-tooltip wpctg-theme-%s" role="tooltip" aria-hidden="true"><span class="wpctg-tooltip-inner"><strong class="wpctg-tooltip-title">%s</strong><span class="wpctg-tooltip-body">%s</span>%s</span></span>',
			esc_attr( $tip_id ),
			esc_attr( $tooltip_theme ),
			$term_title,
			$short_def,
			$read_more_markup
		);

		// Trigger element (Anchor or Button).
		if ( 'tooltip_only' === $link_mode ) {
			$trigger_html = sprintf(
				'<button type="button" class="wpctg-glossary-term wpctg-term-trigger" aria-describedby="%s" aria-expanded="false" data-term-id="%d">%s</button>',
				esc_attr( $tip_id ),
				(int) $candidate['id'],
				$text_html
			);
		} else {
			// Tooltip + Link.
			$trigger_html = sprintf(
				'<a href="%s" class="wpctg-glossary-term wpctg-term-trigger wpctg-has-link" aria-describedby="%s" aria-expanded="false" data-term-id="%d">%s</a>',
				$term_url,
				esc_attr( $tip_id ),
				(int) $candidate['id'],
				$text_html
			);
		}

		$output = sprintf(
			'<span class="wpctg-term-wrapper">%s%s</span>',
			$trigger_html,
			$tooltip_html
		);

		/**
		 * Filter tooltip and wrapper markup.
		 *
		 * @since 1.0.0
		 * @param string $output Generated HTML.
		 * @param array  $candidate Candidate data.
		 */
		return apply_filters( 'wpctg_term_tooltip_markup', $output, $candidate );
	}
}
