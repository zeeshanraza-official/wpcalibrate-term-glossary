<?php
/**
 * HTML-aware Automatic Term Detector for frontend content.
 *
 * @package WPCalibrate\TermGlossary
 */

declare( strict_types=1 );

namespace WPCalibrate\TermGlossary\Content;

use WPCalibrate\TermGlossary\Glossary\TermRepository;
use WPCalibrate\TermGlossary\Glossary\PostType;
use WPCalibrate\TermGlossary\Infrastructure\Settings;
use WPCalibrate\TermGlossary\Infrastructure\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Detector
 */
class Detector {

	/**
	 * Track processed post IDs to avoid duplicate execution.
	 *
	 * @var array<int, bool>
	 */
	private static array $processed_posts = [];

	/**
	 * Register detector filter hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		// Priority 12 ensures shortcodes/blocks have rendered their basic markup first.
		add_filter( 'the_content', [ self::class, 'filter_content' ], 12 );

		// Enqueue frontend assets when needed.
		add_action( 'wp_enqueue_scripts', [ self::class, 'enqueue_frontend_assets' ] );
	}

	/**
	 * Enqueue frontend CSS and JS for tooltips.
	 *
	 * @return void
	 */
	public static function enqueue_frontend_assets(): void {
		if ( ! Settings::get( 'enable_auto_linking' ) && ! Settings::get( 'enable_tooltips' ) ) {
			return;
		}

		wp_enqueue_style(
			'wpctg-frontend-css',
			WPCTG_URL . 'assets/css/frontend.css',
			[],
			WPCTG_VERSION
		);

		wp_enqueue_script(
			'wpctg-frontend-js',
			WPCTG_URL . 'assets/js/frontend.js',
			[],
			WPCTG_VERSION,
			true
		);

		wp_localize_script(
			'wpctg-frontend-js',
			'wpctgFrontend',
			[
				'restUrl' => esc_url_raw( rest_url( 'wpctg/v1/search' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'i18n'    => [
					'close' => __( 'Close definition', 'wpcalibrate-term-glossary' ),
				],
			]
		);
	}

	/**
	 * Filter post content to detect and wrap glossary terms.
	 *
	 * @param string $content Post content HTML.
	 * @return string Processed content HTML.
	 */
	public static function filter_content( string $content ): string {
		if ( '' === trim( $content ) ) {
			return $content;
		}

		// Skip in admin screens, XML feeds, search indexers/robots without post context.
		if ( is_admin() || is_feed() || wp_doing_ajax() ) {
			return $content;
		}

		if ( ! Settings::get( 'enable_auto_linking' ) ) {
			return $content;
		}

		$post = get_post();
		if ( ! $post instanceof \WP_Post ) {
			return $content;
		}

		// Don't auto-link inside the glossary term's own single page definition.
		if ( PostType::POST_TYPE === $post->post_type ) {
			return $content;
		}

		// Check supported post types.
		$supported_types = (array) Settings::get( 'supported_post_types', [ 'post', 'page' ] );
		if ( ! in_array( $post->post_type, $supported_types, true ) ) {
			return $content;
		}

		// Check per-content exclusion.
		if ( ContentExclusion::is_post_excluded( $post->ID ) ) {
			return $content;
		}

		/**
		 * Filter whether content is eligible for automatic glossary processing.
		 *
		 * @since 1.0.0
		 * @param bool     $is_eligible Eligibility flag.
		 * @param \WP_Post $post        Current post object.
		 */
		$is_eligible = apply_filters( 'wpctg_is_content_eligible', true, $post );
		if ( ! $is_eligible ) {
			return $content;
		}

		// Retrieve matching candidates.
		$candidates = TermRepository::get_active_matching_candidates();
		if ( empty( $candidates ) ) {
			return $content;
		}

		try {
			return self::process_html( $content, $candidates );
		} catch ( \Throwable $e ) {
			Logger::log( 'Error processing content HTML for glossary terms: ' . $e->getMessage() );
			// Fail gracefully: NEVER return blank or broken content.
			return $content;
		}
	}

	/**
	 * Safely process HTML text nodes using DOMDocument and XPath.
	 *
	 * @param string                      $html       Source HTML.
	 * @param array<int, array<string, mixed>> $candidates Sorted candidate glossary terms.
	 * @return string Processed HTML.
	 */
	public static function process_html( string $html, array $candidates ): string {
		$match_mode       = (string) Settings::get( 'match_mode', 'first' );
		$max_per_term     = (int) Settings::get( 'max_replacements_per_term', 1 );
		if ( 'first' === $match_mode ) {
			$max_per_term = 1;
		}
		$max_total        = (int) Settings::get( 'max_total_replacements', 0 );
		$process_headings = (bool) Settings::get( 'process_headings', false );

		$term_counts       = [];
		$total_replacements = 0;

		// Load HTML into DOMDocument safely with UTF-8.
		$prev_libxml = libxml_use_internal_errors( true );

		$dom = new \DOMDocument( '1.0', 'UTF-8' );
		// Container wrapper to extract innerHTML cleanly.
		$wrapper_id  = 'wpctg-wrap-' . wp_rand( 1000, 999999 );
		$wrapped_html = '<?xml encoding="utf-8" ?>' .
			'<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>' .
			'<body><div id="' . $wrapper_id . '">' . $html . '</div></body></html>';

		$loaded = $dom->loadHTML( $wrapped_html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();
		libxml_use_internal_errors( $prev_libxml );

		if ( ! $loaded ) {
			return $html;
		}

		$xpath = new \DOMXPath( $dom );

		// XPath query to select text nodes excluding forbidden tags, attributes, and classes.
		$excluded_tags = [
			'ancestor::a',
			'ancestor::button',
			'ancestor::script',
			'ancestor::style',
			'ancestor::code',
			'ancestor::pre',
			'ancestor::textarea',
			'ancestor::select',
			'ancestor::option',
			'ancestor::input',
			'ancestor::*[contains(concat(" ", normalize-space(@class), " "), " wpctg-no-glossary ")]',
			'ancestor::*[@data-wpctg-ignore]',
			'ancestor::*[contains(concat(" ", normalize-space(@class), " "), " wpctg-glossary-term ")]',
			'ancestor::*[contains(concat(" ", normalize-space(@class), " "), " wpctg-term-wrapper ")]',
		];

		if ( ! $process_headings ) {
			$excluded_tags[] = 'ancestor::h1';
			$excluded_tags[] = 'ancestor::h2';
			$excluded_tags[] = 'ancestor::h3';
			$excluded_tags[] = 'ancestor::h4';
			$excluded_tags[] = 'ancestor::h5';
			$excluded_tags[] = 'ancestor::h6';
		}

		/**
		 * Filter excluded XPath conditions for glossary detection.
		 *
		 * @since 1.0.0
		 * @param string[] $excluded_tags List of XPath condition strings.
		 */
		$excluded_tags = apply_filters( 'wpctg_excluded_xpath_conditions', $excluded_tags );

		$xpath_query = '//div[@id="' . $wrapper_id . '"]//text()[not(' . implode( ' or ', $excluded_tags ) . ')]';
		$text_nodes  = $xpath->query( $xpath_query );

		if ( ! $text_nodes || 0 === $text_nodes->length ) {
			return $html;
		}

		// Convert DOMNodeList to an array because we will mutate the DOM.
		$node_list = [];
		foreach ( $text_nodes as $node ) {
			$node_list[] = $node;
		}

		foreach ( $node_list as $node ) {
			if ( $max_total > 0 && $total_replacements >= $max_total ) {
				break;
			}

			$text = $node->nodeValue;
			if ( null === $text || '' === trim( $text ) ) {
				continue;
			}

			// Check matches in this text node.
			$result_nodes = self::replace_in_text(
				$text,
				$candidates,
				$dom,
				$term_counts,
				$max_per_term,
				$total_replacements,
				$max_total
			);

			if ( ! empty( $result_nodes ) && $node->parentNode ) {
				foreach ( $result_nodes as $new_node ) {
					$node->parentNode->insertBefore( $new_node, $node );
				}
				$node->parentNode->removeChild( $node );
			}
		}

		// Retrieve inner HTML of our wrapper element.
		$container = $dom->getElementById( $wrapper_id );
		if ( ! $container ) {
			return $html;
		}

		$output = '';
		foreach ( $container->childNodes as $child ) {
			$output .= $dom->saveHTML( $child );
		}

		return $output;
	}

	/**
	 * Match and split text into text nodes and term replacement fragments.
	 *
	 * @param string                            $text               The text string of the node.
	 * @param array<int, array<string, mixed>>  $candidates         Sorted glossary terms.
	 * @param \DOMDocument                      $dom                Target DOM document.
	 * @param array<int, int>                   $term_counts        Reference to term counts array.
	 * @param int                               $max_per_term       Max replacements per term.
	 * @param int                               $total_replacements Reference to total replacements counter.
	 * @param int                               $max_total          Max total replacements limit.
	 * @return \DOMNode[]|null Array of replacement DOM nodes or null if no match.
	 */
	private static function replace_in_text(
		string $text,
		array $candidates,
		\DOMDocument $dom,
		array &$term_counts,
		int $max_per_term,
		int &$total_replacements,
		int $max_total
	): ?array {
		// Collect candidate matches: [ 'start' => int, 'end' => int, 'candidate' => array, 'matched' => string ]
		$matches = [];

		foreach ( $candidates as $candidate ) {
			$term_id = (int) $candidate['id'];
			$current_count = $term_counts[ $term_id ] ?? 0;
			if ( $max_per_term > 0 && $current_count >= $max_per_term ) {
				continue;
			}

			$phrase = (string) $candidate['phrase'];
			$flags  = 'u';
			if ( empty( $candidate['case_sensitive'] ) ) {
				$flags .= 'i';
			}

			// Unicode word boundaries using lookaround assertions.
			$pattern = '/(?<![\p{L}\p{N}_])(' . preg_quote( $phrase, '/' ) . ')(?![\p{L}\p{N}_])/' . $flags;

			if ( preg_match_all( $pattern, $text, $found, PREG_OFFSET_CAPTURE ) ) {
				foreach ( $found[0] as $match_info ) {
					$matched_str = $match_info[0];
					$byte_offset = $match_info[1];

					// Convert byte offset to character offset for multibyte string manipulation.
					$char_start = mb_strlen( substr( $text, 0, $byte_offset ), 'UTF-8' );
					$char_len   = mb_strlen( $matched_str, 'UTF-8' );
					$char_end   = $char_start + $char_len;

					$matches[] = [
						'start'     => $char_start,
						'end'       => $char_end,
						'candidate' => $candidate,
						'matched'   => $matched_str,
					];
				}
			}
		}

		if ( empty( $matches ) ) {
			return null;
		}

		// Sort matches by start position ASC, then by length DESC.
		usort(
			$matches,
			static function ( array $a, array $b ): int {
				if ( $a['start'] !== $b['start'] ) {
					return $a['start'] <=> $b['start'];
				}
				return ( $b['end'] - $b['start'] ) <=> ( $a['end'] - $a['start'] );
			}
		);

		// Filter non-overlapping matches deterministically.
		$selected_matches = [];
		$last_end         = 0;

		foreach ( $matches as $m ) {
			if ( $max_total > 0 && $total_replacements >= $max_total ) {
				break;
			}

			$term_id = (int) $m['candidate']['id'];
			$current_count = $term_counts[ $term_id ] ?? 0;
			if ( $max_per_term > 0 && $current_count >= $max_per_term ) {
				continue;
			}

			if ( $m['start'] >= $last_end ) {
				$selected_matches[] = $m;
				$last_end           = $m['end'];
				$term_counts[ $term_id ] = ( $term_counts[ $term_id ] ?? 0 ) + 1;
				$total_replacements++;
			}
		}

		if ( empty( $selected_matches ) ) {
			return null;
		}

		// Build replacement DOM nodes.
		$nodes    = [];
		$cursor   = 0;
		$text_len = mb_strlen( $text, 'UTF-8' );

		foreach ( $selected_matches as $m ) {
			if ( $m['start'] > $cursor ) {
				$plain_text = mb_substr( $text, $cursor, $m['start'] - $cursor, 'UTF-8' );
				$nodes[]    = $dom->createTextNode( $plain_text );
			}

			// Render markup for candidate.
			$markup   = Tooltip::render( $m['candidate'], $m['matched'] );
			$fragment = $dom->createDocumentFragment();

			// Append XML or fallback to HTML element if appendXML encounters entity issues.
			$appended = @$fragment->appendXML( $markup );
			if ( $appended ) {
				$nodes[] = $fragment;
			} else {
				// Fallback: create safe text/link element manually.
				$span = $dom->createElement( 'span', htmlspecialchars( $m['matched'], ENT_QUOTES, 'UTF-8' ) );
				$span->setAttribute( 'class', 'wpctg-glossary-term' );
				$nodes[] = $span;
			}

			$cursor = $m['end'];
		}

		if ( $cursor < $text_len ) {
			$remaining_text = mb_substr( $text, $cursor, $text_len - $cursor, 'UTF-8' );
			$nodes[]        = $dom->createTextNode( $remaining_text );
		}

		return $nodes;
	}
}
