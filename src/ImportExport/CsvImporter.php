<?php
/**
 * CSV Importer for Glossary Terms with validation and dry-run support.
 *
 * @package WPCalibrate\TermGlossary
 */

declare( strict_types=1 );

namespace WPCalibrate\TermGlossary\ImportExport;

use WPCalibrate\TermGlossary\Glossary\PostType;
use WPCalibrate\TermGlossary\Infrastructure\CacheManager;
use WPCalibrate\TermGlossary\Admin\AdminMenu;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CsvImporter
 */
class CsvImporter {

	/**
	 * Register import admin-post hook.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_post_wpctg_import_csv', [ self::class, 'handle_import' ] );
	}

	/**
	 * Handle CSV upload and import.
	 *
	 * @return void
	 */
	public static function handle_import(): void {
		if ( ! current_user_can( 'publish_wpctg_terms' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to import glossary terms.', 'wpcalibrate-term-glossary' ) );
		}

		check_admin_referer( 'wpctg_import_csv', 'wpctg_import_nonce' );

		if ( empty( $_FILES['wpctg_csv_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['wpctg_csv_file']['tmp_name'] ) ) {
			self::redirect_with_notice( __( 'No valid file was uploaded.', 'wpcalibrate-term-glossary' ), 'error' );
		}

		$file = $_FILES['wpctg_csv_file'];

		// Validate extension.
		$ext = strtolower( pathinfo( (string) $file['name'], PATHINFO_EXTENSION ) );
		if ( 'csv' !== $ext ) {
			self::redirect_with_notice( __( 'Invalid file extension. Only .csv files are supported.', 'wpcalibrate-term-glossary' ), 'error' );
		}

		// Validate size (max 5MB).
		if ( $file['size'] > 5 * 1024 * 1024 ) {
			self::redirect_with_notice( __( 'File exceeds maximum allowed size of 5MB.', 'wpcalibrate-term-glossary' ), 'error' );
		}

		$update_existing = ! empty( $_POST['wpctg_update_existing'] );
		$is_dry_run       = ! empty( $_POST['wpctg_dry_run'] );

		$tmp_path = (string) $file['tmp_name'];

		try {
			$result = self::process_csv_file( $tmp_path, $update_existing, $is_dry_run );

			if ( ! empty( $result['errors'] ) && 0 === $result['imported'] && 0 === $result['updated'] ) {
				$msg = sprintf(
					/* translators: 1: Error message list */
					__( 'Import failed. Errors: %s', 'wpcalibrate-term-glossary' ),
					implode( '; ', array_slice( $result['errors'], 0, 5 ) )
				);
				self::redirect_with_notice( $msg, 'error' );
			}

			if ( $is_dry_run ) {
				$msg = sprintf(
					/* translators: 1: Valid rows, 2: Updates, 3: Errors */
					__( 'Dry run validation completed. %1$d terms would be created, %2$d updated, %3$d errors found.', 'wpcalibrate-term-glossary' ),
					$result['imported'],
					$result['updated'],
					count( $result['errors'] )
				);
				if ( ! empty( $result['errors'] ) ) {
					$msg .= ' ' . implode( '; ', array_slice( $result['errors'], 0, 3 ) );
				}
				self::redirect_with_notice( $msg, 'info' );
			}

			$msg = sprintf(
				/* translators: 1: Created count, 2: Updated count, 3: Errors count */
				__( 'Import complete. %1$d terms created, %2$d updated. %3$d errors encountered.', 'wpcalibrate-term-glossary' ),
				$result['imported'],
				$result['updated'],
				count( $result['errors'] )
			);
			self::redirect_with_notice( $msg, 'success' );

		} finally {
			if ( file_exists( $tmp_path ) ) {
				@unlink( $tmp_path );
			}
		}
	}

	/**
	 * Parse and process CSV rows.
	 *
	 * @param string $file_path       Path to CSV.
	 * @param bool   $update_existing Whether to update existing matching terms.
	 * @param bool   $dry_run         If true, validate only.
	 * @return array{imported: int, updated: int, errors: string[]}
	 */
	public static function process_csv_file( string $file_path, bool $update_existing, bool $dry_run ): array {
		$handle = fopen( $file_path, 'r' );
		if ( false === $handle ) {
			return [
				'imported' => 0,
				'updated'  => 0,
				'errors'   => [ __( 'Could not open CSV file.', 'wpcalibrate-term-glossary' ) ],
			];
		}

		// Strip UTF-8 BOM if present.
		$bom = fread( $handle, 3 );
		if ( "\xEF\xBB\xBF" !== $bom ) {
			rewind( $handle );
		}

		$header = fgetcsv( $handle );
		if ( false === $header || empty( $header ) ) {
			fclose( $handle );
			return [
				'imported' => 0,
				'updated'  => 0,
				'errors'   => [ __( 'CSV file is empty or missing headers.', 'wpcalibrate-term-glossary' ) ],
			];
		}

		// Normalize headers to lowercase trimmed strings.
		$header_map = [];
		foreach ( $header as $idx => $col_name ) {
			$col_key = strtolower( trim( (string) $col_name ) );
			$header_map[ $col_key ] = $idx;
		}

		if ( ! isset( $header_map['term'] ) ) {
			fclose( $handle );
			return [
				'imported' => 0,
				'updated'  => 0,
				'errors'   => [ __( 'Missing required column "term" in CSV header.', 'wpcalibrate-term-glossary' ) ],
			];
		}

		$imported   = 0;
		$updated    = 0;
		$errors     = [];
		$row_number = 1;

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			$row_number++;

			// Skip completely empty rows.
			if ( empty( array_filter( $row ) ) ) {
				continue;
			}

			$term_name = self::clean_cell( $row[ $header_map['term'] ] ?? '' );
			if ( '' === $term_name ) {
				$errors[] = sprintf(
					/* translators: %d: Row number */
					__( 'Row %d: Missing term name.', 'wpcalibrate-term-glossary' ),
					$row_number
				);
				continue;
			}

			$short_def     = self::clean_cell( isset( $header_map['short_definition'] ) ? ( $row[ $header_map['short_definition'] ] ?? '' ) : '' );
			$full_def      = self::clean_cell( isset( $header_map['full_definition'] ) ? ( $row[ $header_map['full_definition'] ] ?? '' ) : '' );
			$aliases       = self::clean_cell( isset( $header_map['aliases'] ) ? ( $row[ $header_map['aliases'] ] ?? '' ) : '' );
			$categories    = self::clean_cell( isset( $header_map['categories'] ) ? ( $row[ $header_map['categories'] ] ?? '' ) : '' );
			$pronunciation = self::clean_cell( isset( $header_map['pronunciation'] ) ? ( $row[ $header_map['pronunciation'] ] ?? '' ) : '' );
			$source_url    = self::clean_cell( isset( $header_map['source_url'] ) ? ( $row[ $header_map['source_url'] ] ?? '' ) : '' );
			$auto_link_val = isset( $header_map['auto_link'] ) ? ( $row[ $header_map['auto_link'] ] ?? '1' ) : '1';
			$auto_link     = '0' === trim( (string) $auto_link_val ) ? 0 : 1;
			$status        = isset( $header_map['status'] ) ? sanitize_key( $row[ $header_map['status'] ] ?? 'publish' ) : 'publish';
			if ( ! in_array( $status, [ 'publish', 'draft', 'pending', 'private' ], true ) ) {
				$status = 'publish';
			}

			// Check if term already exists by exact title.
			$existing = get_page_by_title( $term_name, 'OBJECT', PostType::POST_TYPE );

			if ( $existing instanceof \WP_Post ) {
				if ( ! $update_existing ) {
					// Skip existing.
					continue;
				}

				if ( $dry_run ) {
					$updated++;
					continue;
				}

				// Update existing post.
				$post_data = [
					'ID'           => $existing->ID,
					'post_content' => wp_kses_post( $full_def ),
					'post_excerpt' => wp_kses_post( $short_def ),
					'post_status'  => $status,
				];

				wp_update_post( $post_data );

				self::save_term_meta_and_tax(
					$existing->ID,
					$short_def,
					$pronunciation,
					$source_url,
					$aliases,
					$auto_link,
					$categories
				);

				$updated++;
			} else {
				if ( $dry_run ) {
					$imported++;
					continue;
				}

				// Insert new term.
				$post_data = [
					'post_title'   => sanitize_text_field( $term_name ),
					'post_content' => wp_kses_post( $full_def ),
					'post_excerpt' => wp_kses_post( $short_def ),
					'post_status'  => $status,
					'post_type'    => PostType::POST_TYPE,
				];

				/**
				 * Filter post data before inserting imported glossary term.
				 *
				 * @since 1.0.0
				 * @param array $post_data Post args.
				 * @param array $row       Raw CSV row.
				 */
				$post_data = apply_filters( 'wpctg_import_term_post_data', $post_data, $row );

				$new_id = wp_insert_post( $post_data );
				if ( is_wp_error( $new_id ) || 0 === $new_id ) {
					$errors[] = sprintf(
						/* translators: 1: Row number, 2: Term name */
						__( 'Row %1$d: Failed to create "%2$s".', 'wpcalibrate-term-glossary' ),
						$row_number,
						$term_name
					);
					continue;
				}

				self::save_term_meta_and_tax(
					(int) $new_id,
					$short_def,
					$pronunciation,
					$source_url,
					$aliases,
					$auto_link,
					$categories
				);

				$imported++;
			}
		}

		fclose( $handle );

		if ( ! $dry_run && ( $imported > 0 || $updated > 0 ) ) {
			CacheManager::invalidate();
		}

		return [
			'imported' => $imported,
			'updated'  => $updated,
			'errors'   => $errors,
		];
	}

	/**
	 * Save term meta and taxonomies on import.
	 *
	 * @param int    $post_id       Term post ID.
	 * @param string $short_def     Short definition.
	 * @param string $pronunciation Pronunciation.
	 * @param string $source_url    Source URL.
	 * @param string $aliases       Aliases.
	 * @param int    $auto_link     Auto-link flag.
	 * @param string $categories    Semicolon or comma-separated categories.
	 * @return void
	 */
	private static function save_term_meta_and_tax(
		int $post_id,
		string $short_def,
		string $pronunciation,
		string $source_url,
		string $aliases,
		int $auto_link,
		string $categories
	): void {
		update_post_meta( $post_id, '_wpctg_short_definition', wp_kses_post( $short_def ) );
		update_post_meta( $post_id, '_wpctg_pronunciation', sanitize_text_field( $pronunciation ) );
		update_post_meta( $post_id, '_wpctg_source_url', esc_url_raw( $source_url ) );

		$alias_items = array_filter( array_map( 'trim', preg_split( '/[;,]/', $aliases ) ?: [] ) );
		update_post_meta( $post_id, '_wpctg_aliases', implode( ', ', array_unique( $alias_items ) ) );
		update_post_meta( $post_id, '_wpctg_auto_link', $auto_link );

		// Set categories if provided.
		if ( '' !== $categories ) {
			$cat_items = array_filter( array_map( 'trim', preg_split( '/[;,]/', $categories ) ?: [] ) );
			if ( ! empty( $cat_items ) ) {
				wp_set_object_terms( $post_id, $cat_items, PostType::TAXONOMY );
			}
		}
	}

	/**
	 * Clean cell and strip formula injection prefix.
	 *
	 * @param mixed $value Cell value.
	 * @return string Cleaned string.
	 */
	public static function clean_cell( mixed $value ): string {
		$str = trim( (string) $value );
		// Strip leading single quote used to neutralize formula injection.
		if ( str_starts_with( $str, "'" ) ) {
			$str = substr( $str, 1 );
		}
		return $str;
	}

	/**
	 * Redirect back to import tab with transient admin notice.
	 *
	 * @param string $message Notice message.
	 * @param string $type    Notice type: success|error|info.
	 * @return void
	 */
	private static function redirect_with_notice( string $message, string $type ): void {
		set_transient(
			'wpctg_import_notice',
			[
				'message' => $message,
				'type'    => $type,
			],
			60
		);

		wp_safe_redirect(
			add_query_arg(
				[
					'page' => AdminMenu::PAGE_SLUG,
					'tab'  => 'import_export',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
