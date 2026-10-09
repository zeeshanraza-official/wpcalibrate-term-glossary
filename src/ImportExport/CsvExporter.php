<?php
/**
 * CSV Exporter for Glossary Terms.
 *
 * @package WPCalibrate\TermGlossary
 */

declare( strict_types=1 );

namespace WPCalibrate\TermGlossary\ImportExport;

use WPCalibrate\TermGlossary\Glossary\PostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CsvExporter
 */
class CsvExporter {

	/**
	 * Register export admin-post hook.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_post_wpctg_export_csv', [ self::class, 'handle_export' ] );
	}

	/**
	 * Handle CSV export request.
	 *
	 * @return void
	 */
	public static function handle_export(): void {
		if ( ! current_user_can( 'edit_wpctg_terms' ) ) {
			wp_die( esc_html__( 'You do not have permission to export glossary terms.', 'wpcalibrate-term-glossary' ) );
		}

		check_admin_referer( 'wpctg_export_csv', 'wpctg_export_nonce' );

		// Query all glossary terms.
		$query = new \WP_Query(
			[
				'post_type'      => PostType::POST_TYPE,
				'post_status'    => [ 'publish', 'draft', 'pending', 'private' ],
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);

		$filename = sprintf( 'wpcalibrate-glossary-terms-%s.csv', gmdate( 'Y-m-d-His' ) );

		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Open output stream.
		$output = fopen( 'php://output', 'w' );
		if ( false === $output ) {
			wp_die( esc_html__( 'Unable to open output stream.', 'wpcalibrate-term-glossary' ) );
		}

		// UTF-8 BOM for Microsoft Excel compatibility.
		fputs( $output, "\xEF\xBB\xBF" );

		// Header columns.
		$headers = [
			'term',
			'short_definition',
			'full_definition',
			'aliases',
			'categories',
			'pronunciation',
			'source_url',
			'auto_link',
			'status',
		];
		fputcsv( $output, $headers );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id = get_the_ID();

				$terms_cats = get_the_terms( $post_id, PostType::TAXONOMY );
				$cat_names  = ! empty( $terms_cats ) && ! is_wp_error( $terms_cats ) ? wp_list_pluck( $terms_cats, 'name' ) : [];

				$auto_link = get_post_meta( $post_id, '_wpctg_auto_link', true );
				$auto_link_val = '' === $auto_link || 1 === (int) $auto_link ? '1' : '0';

				$row = [
					self::sanitize_for_csv( get_the_title() ),
					self::sanitize_for_csv( (string) get_post_meta( $post_id, '_wpctg_short_definition', true ) ),
					self::sanitize_for_csv( get_the_content() ),
					self::sanitize_for_csv( (string) get_post_meta( $post_id, '_wpctg_aliases', true ) ),
					self::sanitize_for_csv( implode( '; ', $cat_names ) ),
					self::sanitize_for_csv( (string) get_post_meta( $post_id, '_wpctg_pronunciation', true ) ),
					self::sanitize_for_csv( (string) get_post_meta( $post_id, '_wpctg_source_url', true ) ),
					$auto_link_val,
					get_post_status( $post_id ),
				];

				/**
				 * Filter exported CSV row data.
				 *
				 * @since 1.0.0
				 * @param array $row     Row column values.
				 * @param int   $post_id Post ID.
				 */
				$row = apply_filters( 'wpctg_export_csv_row', $row, $post_id );

				fputcsv( $output, $row );
			}
			wp_reset_postdata();
		}

		fclose( $output );
		exit;
	}

	/**
	 * Neutralize formula injection characters (=, +, -, @).
	 *
	 * @param string $value Cell value.
	 * @return string Safe value.
	 */
	public static function sanitize_for_csv( string $value ): string {
		$first_char = substr( $value, 0, 1 );
		if ( in_array( $first_char, [ '=', '+', '-', '@', "\t", "\r" ], true ) ) {
			return "'" . $value;
		}
		return $value;
	}
}
