<?php
/**
 * REST API endpoints for Glossary search.
 *
 * @package WPCalibrate\TermGlossary
 */

declare( strict_types=1 );

namespace WPCalibrate\TermGlossary\Frontend;

use WPCalibrate\TermGlossary\Glossary\TermRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RestApi
 */
class RestApi {

	public const ROUTE_NAMESPACE = 'wpctg/v1';

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'rest_api_init', [ self::class, 'register_routes' ] );
	}

	/**
	 * Register the search route.
	 *
	 * @return void
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::ROUTE_NAMESPACE,
			'/search',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ self::class, 'handle_search' ],
				'permission_callback' => '__return_true', // Public read-only access to published terms.
				'args'                => [
					's'        => [
						'description'       => __( 'Search query string.', 'wpcalibrate-term-glossary' ),
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => static function ( $param ): bool {
							return is_string( $param ) && mb_strlen( $param, 'UTF-8' ) <= 100;
						},
						'default'           => '',
					],
					'category' => [
						'description'       => __( 'Glossary category slug.', 'wpcalibrate-term-glossary' ),
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_title',
						'default'           => '',
					],
					'letter'   => [
						'description'       => __( 'Initial letter filter.', 'wpcalibrate-term-glossary' ),
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => '',
					],
					'per_page' => [
						'description'       => __( 'Number of results to return.', 'wpcalibrate-term-glossary' ),
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'default'           => 20,
					],
				],
			]
		);
	}

	/**
	 * Handle search request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public static function handle_search( \WP_REST_Request $request ): \WP_REST_Response {
		$search   = (string) $request->get_param( 's' );
		$category = (string) $request->get_param( 'category' );
		$letter   = (string) $request->get_param( 'letter' );
		$per_page = min( 50, max( 1, (int) $request->get_param( 'per_page' ) ) );

		$result = TermRepository::query_terms(
			[
				'search'         => $search,
				'category'       => $category,
				'letter'         => $letter,
				'posts_per_page' => $per_page,
				'paged'          => 1,
			]
		);

		return new \WP_REST_Response(
			[
				'success' => true,
				'data'    => $result['terms'],
				'total'   => $result['total'],
			],
			200
		);
	}
}
