<?php
/**
 * Post Type and Taxonomy registration for glossary terms.
 *
 * @package WPCalibrate\TermGlossary
 */

declare( strict_types=1 );

namespace WPCalibrate\TermGlossary\Glossary;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PostType
 */
class PostType {

	public const POST_TYPE = 'wpctg_term';
	public const TAXONOMY  = 'wpctg_category';

	/**
	 * Register post type, taxonomy, and meta fields.
	 *
	 * @return void
	 */
	public static function register(): void {
		self::register_post_type();
		self::register_taxonomy();
		self::register_meta();
	}

	/**
	 * Register the custom post type for glossary terms.
	 *
	 * @return void
	 */
	public static function register_post_type(): void {
		$labels = [
			'name'                  => _x( 'Glossary Terms', 'Post type general name', 'wpcalibrate-term-glossary' ),
			'singular_name'         => _x( 'Glossary Term', 'Post type singular name', 'wpcalibrate-term-glossary' ),
			'menu_name'             => _x( 'Glossary Terms', 'Admin Menu text', 'wpcalibrate-term-glossary' ),
			'name_admin_bar'        => _x( 'Glossary Term', 'Add New on Toolbar', 'wpcalibrate-term-glossary' ),
			'add_new'               => __( 'Add New Term', 'wpcalibrate-term-glossary' ),
			'add_new_item'          => __( 'Add New Glossary Term', 'wpcalibrate-term-glossary' ),
			'new_item'              => __( 'New Glossary Term', 'wpcalibrate-term-glossary' ),
			'edit_item'             => __( 'Edit Glossary Term', 'wpcalibrate-term-glossary' ),
			'view_item'             => __( 'View Glossary Term', 'wpcalibrate-term-glossary' ),
			'all_items'             => __( 'All Terms', 'wpcalibrate-term-glossary' ),
			'search_items'          => __( 'Search Glossary Terms', 'wpcalibrate-term-glossary' ),
			'parent_item_colon'     => __( 'Parent Glossary Term:', 'wpcalibrate-term-glossary' ),
			'not_found'             => __( 'No glossary terms found.', 'wpcalibrate-term-glossary' ),
			'not_found_in_trash'    => __( 'No glossary terms found in Trash.', 'wpcalibrate-term-glossary' ),
			'archives'              => __( 'Glossary Archives', 'wpcalibrate-term-glossary' ),
			'attributes'            => __( 'Glossary Term Attributes', 'wpcalibrate-term-glossary' ),
			'insert_into_item'      => __( 'Insert into glossary term', 'wpcalibrate-term-glossary' ),
			'uploaded_to_this_item' => __( 'Uploaded to this glossary term', 'wpcalibrate-term-glossary' ),
			'filter_items_list'     => __( 'Filter glossary terms list', 'wpcalibrate-term-glossary' ),
			'items_list_navigation' => __( 'Glossary terms list navigation', 'wpcalibrate-term-glossary' ),
			'items_list'            => __( 'Glossary terms list', 'wpcalibrate-term-glossary' ),
		];

		/**
		 * Filter the rewrite slug for the glossary post type.
		 *
		 * @since 1.0.0
		 * @param string $slug The rewrite slug. Default 'glossary'.
		 */
		$rewrite_slug = apply_filters( 'wpctg_term_rewrite_slug', 'glossary' );

		$args = [
			'labels'              => $labels,
			'description'         => __( 'Glossary terms and definitions.', 'wpcalibrate-term-glossary' ),
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => false, // Handled under WPCalibrate admin menu.
			'query_var'           => true,
			'rewrite'             => [
				'slug'       => sanitize_title( $rewrite_slug ),
				'with_front' => false,
			],
			'capability_type'     => [ 'wpctg_term', 'wpctg_terms' ],
			'map_meta_cap'        => true,
			'has_archive'         => false,
			'hierarchical'        => false,
			'menu_position'       => 30,
			'menu_icon'           => 'dashicons-book-alt',
			'supports'            => [ 'title', 'editor', 'excerpt', 'revisions' ],
			'show_in_rest'        => true,
			'rest_base'           => 'glossary-terms',
			'taxonomies'          => [ self::TAXONOMY ],
		];

		/**
		 * Filter the custom post type registration arguments.
		 *
		 * @since 1.0.0
		 * @param array $args Post type registration arguments.
		 */
		$args = apply_filters( 'wpctg_term_post_type_args', $args );

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Register the taxonomy for glossary categories.
	 *
	 * @return void
	 */
	public static function register_taxonomy(): void {
		$labels = [
			'name'                       => _x( 'Glossary Categories', 'taxonomy general name', 'wpcalibrate-term-glossary' ),
			'singular_name'              => _x( 'Glossary Category', 'taxonomy singular name', 'wpcalibrate-term-glossary' ),
			'search_items'               => __( 'Search Categories', 'wpcalibrate-term-glossary' ),
			'popular_items'              => __( 'Popular Categories', 'wpcalibrate-term-glossary' ),
			'all_items'                  => __( 'All Categories', 'wpcalibrate-term-glossary' ),
			'parent_item'                => __( 'Parent Category', 'wpcalibrate-term-glossary' ),
			'parent_item_colon'          => __( 'Parent Category:', 'wpcalibrate-term-glossary' ),
			'edit_item'                  => __( 'Edit Category', 'wpcalibrate-term-glossary' ),
			'update_item'                => __( 'Update Category', 'wpcalibrate-term-glossary' ),
			'add_new_item'               => __( 'Add New Category', 'wpcalibrate-term-glossary' ),
			'new_item_name'              => __( 'New Category Name', 'wpcalibrate-term-glossary' ),
			'separate_items_with_commas' => __( 'Separate categories with commas', 'wpcalibrate-term-glossary' ),
			'add_or_remove_items'        => __( 'Add or remove categories', 'wpcalibrate-term-glossary' ),
			'choose_from_most_used'      => __( 'Choose from the most used categories', 'wpcalibrate-term-glossary' ),
			'not_found'                  => __( 'No categories found.', 'wpcalibrate-term-glossary' ),
			'menu_name'                  => __( 'Categories', 'wpcalibrate-term-glossary' ),
		];

		$args = [
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => [ 'slug' => 'glossary-category' ],
			'show_in_rest'      => true,
			'capabilities'      => [
				'manage_terms' => 'manage_wpctg_categories',
				'edit_terms'   => 'manage_wpctg_categories',
				'delete_terms' => 'manage_wpctg_categories',
				'assign_terms' => 'edit_wpctg_terms',
			],
		];

		/**
		 * Filter the glossary category taxonomy registration arguments.
		 *
		 * @since 1.0.0
		 * @param array $args Taxonomy arguments.
		 */
		$args = apply_filters( 'wpctg_category_taxonomy_args', $args );

		register_taxonomy( self::TAXONOMY, [ self::POST_TYPE ], $args );
	}

	/**
	 * Register post meta keys for glossary terms.
	 *
	 * @return void
	 */
	public static function register_meta(): void {
		$meta_keys = [
			'_wpctg_short_definition' => [
				'type'              => 'string',
				'description'       => __( 'Concise definition used for the tooltip.', 'wpcalibrate-term-glossary' ),
				'single'            => true,
				'sanitize_callback' => 'wp_kses_post',
				'show_in_rest'      => true,
			],
			'_wpctg_pronunciation'    => [
				'type'              => 'string',
				'description'       => __( 'Optional phonetic pronunciation guide.', 'wpcalibrate-term-glossary' ),
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			],
			'_wpctg_source_url'       => [
				'type'              => 'string',
				'description'       => __( 'Optional external source or reference URL.', 'wpcalibrate-term-glossary' ),
				'single'            => true,
				'sanitize_callback' => 'esc_url_raw',
				'show_in_rest'      => true,
			],
			'_wpctg_aliases'          => [
				'type'              => 'string',
				'description'       => __( 'Comma-separated aliases and synonyms.', 'wpcalibrate-term-glossary' ),
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			],
			'_wpctg_related_terms'    => [
				'type'              => 'string',
				'description'       => __( 'Comma-separated IDs or names of related glossary terms.', 'wpcalibrate-term-glossary' ),
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
			],
			'_wpctg_auto_link'        => [
				'type'              => 'integer',
				'description'       => __( 'Whether automatic linking is enabled for this term (1 or 0).', 'wpcalibrate-term-glossary' ),
				'single'            => true,
				'sanitize_callback' => 'absint',
				'default'           => 1,
				'show_in_rest'      => true,
			],
			'_wpctg_case_sensitive'   => [
				'type'              => 'integer',
				'description'       => __( 'Whether matching for this term is case-sensitive (1 or 0).', 'wpcalibrate-term-glossary' ),
				'single'            => true,
				'sanitize_callback' => 'absint',
				'default'           => 0,
				'show_in_rest'      => true,
			],
			'_wpctg_priority'         => [
				'type'              => 'integer',
				'description'       => __( 'Matching priority for resolving conflicts (higher number = higher priority).', 'wpcalibrate-term-glossary' ),
				'single'            => true,
				'sanitize_callback' => [ self::class, 'sanitize_priority' ],
				'default'           => 10,
				'show_in_rest'      => true,
			],
		];

		foreach ( $meta_keys as $meta_key => $args ) {
			register_post_meta( self::POST_TYPE, $meta_key, $args );
		}
	}

	/**
	 * Sanitize priority meta value safely across all PHP versions.
	 *
	 * WordPress passes 4 arguments to sanitize_{$meta_type}_meta_{$meta_key} filters.
	 * Using a userland method prevents PHP 8+ ArgumentCountError thrown by internal intval().
	 *
	 * @param mixed $value Raw meta value.
	 * @return int Sanitized integer priority.
	 */
	public static function sanitize_priority( mixed $value ): int {
		return (int) $value;
	}
}
