<?php
/**
 * Repository for fetching and normalizing glossary terms.
 *
 * @package WPCalibrate\TermGlossary
 */

declare( strict_types=1 );

namespace WPCalibrate\TermGlossary\Glossary;

use WPCalibrate\TermGlossary\Infrastructure\CacheManager;
use WPCalibrate\TermGlossary\Infrastructure\Settings;
use WPCalibrate\TermGlossary\Infrastructure\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TermRepository
 */
class TermRepository {

	/**
	 * Get normalized matching candidates for in-content replacement.
	 * Returns array sorted by priority DESC and phrase length DESC.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_active_matching_candidates(): array {
		$cached = get_transient( CacheManager::INDEX_TRANSIENT );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$global_case_sensitive = (bool) Settings::get( 'case_sensitive', false );

		$args = [
			'post_type'              => PostType::POST_TYPE,
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		];

		/**
		 * Filter query arguments for fetching matching candidates.
		 *
		 * @since 1.0.0
		 * @param array $args Query arguments.
		 */
		$args = apply_filters( 'wpctg_matching_candidates_query_args', $args );

		$query = new \WP_Query( $args );
		$post_ids = ! empty( $query->posts ) ? (array) $query->posts : [];

		$candidates = [];

		foreach ( $post_ids as $post_id ) {
			$post_id   = (int) $post_id;
			$auto_link = get_post_meta( $post_id, '_wpctg_auto_link', true );
			if ( '' !== $auto_link && 0 === (int) $auto_link ) {
				continue;
			}

			$title = trim( (string) get_the_title( $post_id ) );
			if ( '' === $title ) {
				continue;
			}

			$short_def = (string) get_post_meta( $post_id, '_wpctg_short_definition', true );
			if ( '' === $short_def ) {
				$post_obj  = get_post( $post_id );
				$short_def = $post_obj ? ( ! empty( $post_obj->post_excerpt ) ? $post_obj->post_excerpt : wp_trim_words( wp_strip_all_tags( $post_obj->post_content ), 25, '...' ) ) : '';
			}

			$url = (string) get_permalink( $post_id );

			$priority_meta = get_post_meta( $post_id, '_wpctg_priority', true );
			$priority      = '' === $priority_meta ? 10 : (int) $priority_meta;

			$case_meta      = get_post_meta( $post_id, '_wpctg_case_sensitive', true );
			$case_sensitive = '' !== $case_meta && 1 === (int) $case_meta ? true : $global_case_sensitive;

			// Add primary term phrase.
			$candidates[] = [
				'id'             => $post_id,
				'term'           => $title,
				'phrase'         => $title,
				'url'            => $url,
				'short_def'      => $short_def,
				'priority'       => $priority,
				'case_sensitive' => $case_sensitive,
				'is_alias'       => false,
				'length'         => mb_strlen( $title, 'UTF-8' ),
			];

			// Process aliases.
			$raw_aliases = (string) get_post_meta( $post_id, '_wpctg_aliases', true );
			if ( '' !== $raw_aliases ) {
				$alias_items = array_filter( array_map( 'trim', explode( ',', $raw_aliases ) ) );
				foreach ( array_unique( $alias_items ) as $alias ) {
					if ( '' === $alias || mb_strtolower( $alias, 'UTF-8' ) === mb_strtolower( $title, 'UTF-8' ) ) {
						continue;
					}

					$candidates[] = [
						'id'             => $post_id,
						'term'           => $title,
						'phrase'         => $alias,
						'url'            => $url,
						'short_def'      => $short_def,
						'priority'       => $priority,
						'case_sensitive' => $case_sensitive,
						'is_alias'       => true,
						'length'         => mb_strlen( $alias, 'UTF-8' ),
					];
				}
			}
		}

		// Sort candidates:
		// 1. Priority DESC
		// 2. Phrase length DESC (longer phrases match first)
		// 3. ID ASC
		usort(
			$candidates,
			static function ( array $a, array $b ): int {
				if ( $a['priority'] !== $b['priority'] ) {
					return $b['priority'] <=> $a['priority'];
				}
				if ( $a['length'] !== $b['length'] ) {
					return $b['length'] <=> $a['length'];
				}
				return $a['id'] <=> $b['id'];
			}
		);

		/**
		 * Filter candidates after sorting.
		 *
		 * @since 1.0.0
		 * @param array $candidates Formatted matching candidates.
		 */
		$candidates = apply_filters( 'wpctg_matching_candidates', $candidates );

		$ttl = (int) Settings::get( 'cache_ttl', 86400 );
		set_transient( CacheManager::INDEX_TRANSIENT, $candidates, $ttl );

		Logger::log( 'Matching index built and cached.', [ 'candidates_count' => count( $candidates ) ] );

		return $candidates;
	}

	/**
	 * Get published terms for glossary index with pagination and filtering.
	 *
	 * @param array<string, mixed> $args Query filter arguments.
	 * @return array{terms: array<int, array<string, mixed>>, total: int, max_pages: int}
	 */
	public static function query_terms( array $args = [] ): array {
		$defaults = [
			'letter'         => '',
			'search'         => '',
			'category'       => '',
			'posts_per_page' => (int) Settings::get( 'terms_per_page', 20 ),
			'paged'          => 1,
			'order'          => (string) Settings::get( 'default_sort_order', 'asc' ),
		];

		$params = wp_parse_args( $args, $defaults );

		$query_args = [
			'post_type'      => PostType::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => $params['posts_per_page'],
			'paged'          => $params['paged'],
			'orderby'        => 'title',
			'order'          => strtoupper( $params['order'] ) === 'DESC' ? 'DESC' : 'ASC',
		];

		if ( ! empty( $params['search'] ) ) {
			$query_args['s'] = sanitize_text_field( $params['search'] );
		}

		if ( ! empty( $params['category'] ) ) {
			$query_args['tax_query'] = [
				[
					'taxonomy' => PostType::TAXONOMY,
					'field'    => 'slug',
					'terms'    => sanitize_title( $params['category'] ),
				],
			];
		}

		// Filter by letter: custom WHERE clause via filter.
		$letter = sanitize_text_field( $params['letter'] );
		if ( '' !== $letter ) {
			$filter_callback = static function ( string $where ) use ( $letter ): string {
				global $wpdb;
				if ( '#' === $letter ) {
					// Match numeric or non-letter starting characters.
					$where .= " AND {$wpdb->posts}.post_title REGEXP '^[^a-zA-Z]'";
				} else {
					$escaped = $wpdb->esc_like( $letter );
					$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_title LIKE %s", $escaped . '%' );
				}
				return $where;
			};

			add_filter( 'posts_where', $filter_callback );
		}

		/**
		 * Filter WP_Query arguments before executing glossary index query.
		 *
		 * @since 1.0.0
		 * @param array $query_args The query arguments.
		 * @param array $params     The parsed request parameters.
		 */
		$query_args = apply_filters( 'wpctg_index_query_args', $query_args, $params );

		$query = new \WP_Query( $query_args );

		if ( '' !== $letter && isset( $filter_callback ) ) {
			remove_filter( 'posts_where', $filter_callback );
		}

		$terms = [];
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id   = get_the_ID();
				$short_def = (string) get_post_meta( $post_id, '_wpctg_short_definition', true );
				if ( '' === $short_def ) {
					$short_def = wp_trim_words( wp_strip_all_tags( get_the_content() ), 25, '...' );
				}

				$pronunciation = (string) get_post_meta( $post_id, '_wpctg_pronunciation', true );
				$aliases       = (string) get_post_meta( $post_id, '_wpctg_aliases', true );
				$terms_cats    = get_the_terms( $post_id, PostType::TAXONOMY );
				$cat_names     = ! empty( $terms_cats ) && ! is_wp_error( $terms_cats ) ? wp_list_pluck( $terms_cats, 'name' ) : [];

				$terms[] = [
					'id'            => $post_id,
					'title'         => get_the_title(),
					'url'           => get_permalink(),
					'short_def'     => $short_def,
					'pronunciation' => $pronunciation,
					'aliases'       => $aliases,
					'categories'    => $cat_names,
				];
			}
			wp_reset_postdata();
		}

		return [
			'terms'     => $terms,
			'total'     => (int) $query->found_posts,
			'max_pages' => (int) $query->max_num_pages,
		];
	}

	/**
	 * Get all active initial letters present in published glossary terms.
	 *
	 * @return string[] Unique uppercase initial letters or '#' for non-letters.
	 */
	public static function get_active_letters(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$titles = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT UPPER(LEFT(post_title, 1)) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish' ORDER BY post_title ASC",
				PostType::POST_TYPE
			)
		);

		$letters = [];
		foreach ( (array) $titles as $letter ) {
			$letter = trim( (string) $letter );
			if ( '' === $letter ) {
				continue;
			}
			if ( preg_match( '/^[A-Z]$/', $letter ) ) {
				$letters[] = $letter;
			} else {
				if ( ! in_array( '#', $letters, true ) ) {
					$letters[] = '#';
				}
			}
		}

		sort( $letters );
		return $letters;
	}
}
