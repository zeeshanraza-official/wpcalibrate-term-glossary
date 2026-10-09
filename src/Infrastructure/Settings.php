<?php
/**
 * Settings configuration and handling.
 *
 * @package WPCalibrate\TermGlossary
 */

declare( strict_types=1 );

namespace WPCalibrate\TermGlossary\Infrastructure;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Settings
 */
class Settings {

	public const OPTION_NAME = 'wpctg_settings';

	/**
	 * Get all default settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_defaults(): array {
		return [
			// General.
			'enable_auto_linking'       => true,
			'supported_post_types'      => [ 'post', 'page' ],
			'match_mode'                => 'first', // 'first' or 'all'
			'max_replacements_per_term' => 1,
			'max_total_replacements'    => 0, // 0 = unlimited
			'process_headings'          => false,
			'link_mode'                 => 'tooltip_link', // 'tooltip_link', 'tooltip_only', 'link_only'
			'case_sensitive'            => false,

			// Glossary Index.
			'terms_per_page'            => 20,
			'enable_live_search'        => true,
			'enable_alphabet_filter'    => true,
			'enable_category_filter'    => true,
			'default_sort_order'        => 'asc',

			// Tooltip.
			'enable_tooltips'           => true,
			'show_read_more'            => true,
			'read_more_text'            => __( 'Read full definition', 'wpcalibrate-term-glossary' ),
			'tooltip_theme'             => 'light',

			// SEO.
			'enable_defined_term_schema'     => true,
			'enable_defined_term_set_schema' => true,

			// Advanced.
			'enable_debug_log'          => false,
			'delete_data_on_uninstall'  => false,
			'cache_ttl'                 => 86400,
		];
	}

	/**
	 * Retrieve a specific setting value or all settings.
	 *
	 * @param string|null $key     Setting key or null to get all settings.
	 * @param mixed       $default Default value if key is not found.
	 * @return mixed
	 */
	public static function get( ?string $key = null, mixed $default = null ): mixed {
		$options = get_option( self::OPTION_NAME, [] );
		if ( ! is_array( $options ) ) {
			$options = [];
		}

		$settings = wp_parse_args( $options, self::get_defaults() );

		if ( null === $key ) {
			return $settings;
		}

		return $settings[ $key ] ?? $default ?? self::get_defaults()[ $key ] ?? null;
	}

	/**
	 * Update all settings or a specific key.
	 *
	 * @param array<string, mixed> $settings Updated settings.
	 * @return bool
	 */
	public static function update( array $settings ): bool {
		$existing  = self::get();
		$merged    = array_merge( $existing, $settings );
		$sanitized = self::sanitize( $merged );
		return update_option( self::OPTION_NAME, $sanitized );
	}

	/**
	 * Sanitize raw settings input.
	 *
	 * @param array<string, mixed> $input Raw input.
	 * @return array<string, mixed>
	 */
	public static function sanitize( array $input ): array {
		$defaults = self::get_defaults();
		$clean    = [];

		// General.
		$clean['enable_auto_linking']       = ! empty( $input['enable_auto_linking'] );
		$post_types                         = isset( $input['supported_post_types'] ) && is_array( $input['supported_post_types'] )
			? array_map( 'sanitize_key', $input['supported_post_types'] )
			: [ 'post', 'page' ];
		$clean['supported_post_types']      = ! empty( $post_types ) ? array_values( array_unique( $post_types ) ) : [ 'post', 'page' ];
		$clean['match_mode']                = in_array( $input['match_mode'] ?? '', [ 'first', 'all' ], true ) ? $input['match_mode'] : 'first';
		$clean['max_replacements_per_term'] = max( 0, (int) ( $input['max_replacements_per_term'] ?? 1 ) );
		$clean['max_total_replacements']    = max( 0, (int) ( $input['max_total_replacements'] ?? 0 ) );
		$clean['process_headings']          = ! empty( $input['process_headings'] );
		$clean['link_mode']                 = in_array( $input['link_mode'] ?? '', [ 'tooltip_link', 'tooltip_only', 'link_only' ], true ) ? $input['link_mode'] : 'tooltip_link';
		$clean['case_sensitive']            = ! empty( $input['case_sensitive'] );

		// Index.
		$clean['terms_per_page']         = max( 1, min( 100, (int) ( $input['terms_per_page'] ?? 20 ) ) );
		$clean['enable_live_search']     = ! empty( $input['enable_live_search'] );
		$clean['enable_alphabet_filter'] = ! empty( $input['enable_alphabet_filter'] );
		$clean['enable_category_filter'] = ! empty( $input['enable_category_filter'] );
		$clean['default_sort_order']     = in_array( strtolower( (string) ( $input['default_sort_order'] ?? 'asc' ) ), [ 'asc', 'desc' ], true ) ? strtolower( (string) $input['default_sort_order'] ) : 'asc';

		// Tooltip.
		$clean['enable_tooltips'] = ! empty( $input['enable_tooltips'] );
		$clean['show_read_more']  = ! empty( $input['show_read_more'] );
		$clean['read_more_text']  = sanitize_text_field( $input['read_more_text'] ?? __( 'Read full definition', 'wpcalibrate-term-glossary' ) );
		$clean['tooltip_theme']   = in_array( $input['tooltip_theme'] ?? '', [ 'light', 'dark' ], true ) ? $input['tooltip_theme'] : 'light';

		// SEO.
		$clean['enable_defined_term_schema']     = ! empty( $input['enable_defined_term_schema'] );
		$clean['enable_defined_term_set_schema'] = ! empty( $input['enable_defined_term_set_schema'] );

		// Advanced.
		$clean['enable_debug_log']         = ! empty( $input['enable_debug_log'] );
		$clean['delete_data_on_uninstall'] = ! empty( $input['delete_data_on_uninstall'] );
		$clean['cache_ttl']                = max( 60, (int) ( $input['cache_ttl'] ?? 86400 ) );

		return wp_parse_args( $clean, $defaults );
	}
}
