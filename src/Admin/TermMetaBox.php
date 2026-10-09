<?php
/**
 * Glossary Term Meta Box and Custom Columns in Admin.
 *
 * @package WPCalibrate\TermGlossary
 */

declare( strict_types=1 );

namespace WPCalibrate\TermGlossary\Admin;

use WPCalibrate\TermGlossary\Glossary\PostType;
use WPCalibrate\TermGlossary\Infrastructure\CacheManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TermMetaBox
 */
class TermMetaBox {

	public const NONCE_ACTION = 'wpctg_save_term_meta';
	public const NONCE_NAME   = 'wpctg_term_meta_nonce';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'add_meta_boxes', [ self::class, 'add_meta_boxes' ] );
		add_action( 'save_post_' . PostType::POST_TYPE, [ self::class, 'save_meta_box' ], 10, 2 );

		// Custom admin columns for wpctg_term.
		add_filter( 'manage_' . PostType::POST_TYPE . '_posts_columns', [ self::class, 'add_admin_columns' ] );
		add_action( 'manage_' . PostType::POST_TYPE . '_posts_custom_column', [ self::class, 'render_admin_columns' ], 10, 2 );
	}

	/**
	 * Add glossary term meta box.
	 *
	 * @return void
	 */
	public static function add_meta_boxes(): void {
		add_meta_box(
			'wpctg_term_settings',
			__( 'Glossary Term Settings & Details', 'wpcalibrate-term-glossary' ),
			[ self::class, 'render_meta_box' ],
			PostType::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render the term meta box form.
	 *
	 * @param \WP_Post $post Current post object.
	 * @return void
	 */
	public static function render_meta_box( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$short_def      = (string) get_post_meta( $post->ID, '_wpctg_short_definition', true );
		$pronunciation  = (string) get_post_meta( $post->ID, '_wpctg_pronunciation', true );
		$source_url     = (string) get_post_meta( $post->ID, '_wpctg_source_url', true );
		$aliases        = (string) get_post_meta( $post->ID, '_wpctg_aliases', true );
		$related_terms  = (string) get_post_meta( $post->ID, '_wpctg_related_terms', true );
		$auto_link_meta = get_post_meta( $post->ID, '_wpctg_auto_link', true );
		$auto_link      = '' === $auto_link_meta ? 1 : (int) $auto_link_meta;
		$case_sensitive = (int) get_post_meta( $post->ID, '_wpctg_case_sensitive', true );
		$priority_meta  = get_post_meta( $post->ID, '_wpctg_priority', true );
		$priority       = '' === $priority_meta ? 10 : (int) $priority_meta;

		// Check for alias conflicts.
		$conflict_warnings = self::check_alias_conflicts( $post->ID, $aliases );

		?>
		<div class="wpctg-meta-box-wrap" style="display:flex; flex-direction:column; gap:16px;">
			<?php if ( ! empty( $conflict_warnings ) ) : ?>
				<div class="notice notice-warning inline" style="margin: 0; padding: 10px 12px;">
					<p><strong><?php esc_html_e( 'Potential Alias Conflict Detected:', 'wpcalibrate-term-glossary' ); ?></strong></p>
					<ul style="list-style-type: disc; margin-left: 20px;">
						<?php foreach ( $conflict_warnings as $warning ) : ?>
							<li><?php echo esc_html( $warning ); ?></li>
						<?php endforeach; ?>
					</ul>
					<p class="description">
						<?php esc_html_e( 'Tip: Adjust matching priority if you want this term or alias to take precedence.', 'wpcalibrate-term-glossary' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<div>
				<label for="wpctg_short_definition" style="font-weight:600; display:block; margin-bottom:4px;">
					<?php esc_html_e( 'Short Definition (Tooltip)', 'wpcalibrate-term-glossary' ); ?>
				</label>
				<textarea id="wpctg_short_definition" name="wpctg_short_definition" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'Brief explanation displayed inside hover/tap tooltips...', 'wpcalibrate-term-glossary' ); ?>"><?php echo esc_textarea( $short_def ); ?></textarea>
				<p class="description">
					<?php esc_html_e( 'This concise definition appears inside frontend tooltips. If left empty, a truncated excerpt of the main content will be used.', 'wpcalibrate-term-glossary' ); ?>
				</p>
			</div>

			<div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
				<div>
					<label for="wpctg_aliases" style="font-weight:600; display:block; margin-bottom:4px;">
						<?php esc_html_e( 'Aliases / Synonyms', 'wpcalibrate-term-glossary' ); ?>
					</label>
					<input type="text" id="wpctg_aliases" name="wpctg_aliases" value="<?php echo esc_attr( $aliases ); ?>" class="regular-text" style="width:100%;" placeholder="<?php esc_attr_e( 'e.g. SEO, Search Engine Optimization', 'wpcalibrate-term-glossary' ); ?>" />
					<p class="description">
						<?php esc_html_e( 'Separate multiple aliases with commas. Automatic linking will also match these phrases.', 'wpcalibrate-term-glossary' ); ?>
					</p>
				</div>
				<div>
					<label for="wpctg_pronunciation" style="font-weight:600; display:block; margin-bottom:4px;">
						<?php esc_html_e( 'Pronunciation Guide (Optional)', 'wpcalibrate-term-glossary' ); ?>
					</label>
					<input type="text" id="wpctg_pronunciation" name="wpctg_pronunciation" value="<?php echo esc_attr( $pronunciation ); ?>" class="regular-text" style="width:100%;" placeholder="<?php esc_attr_e( 'e.g. /ˌkæl.ɪ.breɪt/', 'wpcalibrate-term-glossary' ); ?>" />
					<p class="description">
						<?php esc_html_e( 'Phonetic guide displayed on the single glossary term page.', 'wpcalibrate-term-glossary' ); ?>
					</p>
				</div>
			</div>

			<div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
				<div>
					<label for="wpctg_source_url" style="font-weight:600; display:block; margin-bottom:4px;">
						<?php esc_html_e( 'Source / Reference URL (Optional)', 'wpcalibrate-term-glossary' ); ?>
					</label>
					<input type="url" id="wpctg_source_url" name="wpctg_source_url" value="<?php echo esc_url( $source_url ); ?>" class="regular-text" style="width:100%;" placeholder="https://" />
					<p class="description">
						<?php esc_html_e( 'Citation or external authority link displayed on the term page.', 'wpcalibrate-term-glossary' ); ?>
					</p>
				</div>
				<div>
					<label for="wpctg_related_terms" style="font-weight:600; display:block; margin-bottom:4px;">
						<?php esc_html_e( 'Related Terms (Optional)', 'wpcalibrate-term-glossary' ); ?>
					</label>
					<input type="text" id="wpctg_related_terms" name="wpctg_related_terms" value="<?php echo esc_attr( $related_terms ); ?>" class="regular-text" style="width:100%;" placeholder="<?php esc_attr_e( 'Comma-separated term names or IDs', 'wpcalibrate-term-glossary' ); ?>" />
					<p class="description">
						<?php esc_html_e( 'List related terms to create cross-links on the single term definition page.', 'wpcalibrate-term-glossary' ); ?>
					</p>
				</div>
			</div>

			<div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:16px; border-top: 1px solid #e2e8f0; padding-top:12px;">
				<div>
					<label style="font-weight:600; display:block; margin-bottom:4px;">
						<?php esc_html_e( 'Automatic Linking', 'wpcalibrate-term-glossary' ); ?>
					</label>
					<label for="wpctg_auto_link">
						<input type="checkbox" id="wpctg_auto_link" name="wpctg_auto_link" value="1" <?php checked( $auto_link, 1 ); ?> />
						<?php esc_html_e( 'Enable auto-detection for this term', 'wpcalibrate-term-glossary' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Uncheck to exclude this term from automatic in-content highlighting.', 'wpcalibrate-term-glossary' ); ?></p>
				</div>
				<div>
					<label style="font-weight:600; display:block; margin-bottom:4px;">
						<?php esc_html_e( 'Case Sensitivity', 'wpcalibrate-term-glossary' ); ?>
					</label>
					<label for="wpctg_case_sensitive">
						<input type="checkbox" id="wpctg_case_sensitive" name="wpctg_case_sensitive" value="1" <?php checked( $case_sensitive, 1 ); ?> />
						<?php esc_html_e( 'Match exact case only', 'wpcalibrate-term-glossary' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Enable for specific acronyms where lowercase letters should not match.', 'wpcalibrate-term-glossary' ); ?></p>
				</div>
				<div>
					<label for="wpctg_priority" style="font-weight:600; display:block; margin-bottom:4px;">
						<?php esc_html_e( 'Matching Priority', 'wpcalibrate-term-glossary' ); ?>
					</label>
					<input type="number" id="wpctg_priority" name="wpctg_priority" value="<?php echo esc_attr( (string) $priority ); ?>" min="0" max="1000" style="width:90px;" />
					<p class="description"><?php esc_html_e( 'Higher number = higher precedence when terms overlap (Default: 10).', 'wpcalibrate-term-glossary' ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save the meta box values.
	 *
	 * @param int            $post_id Post ID.
	 * @param \WP_Post|null $post    Optional post object.
	 * @return void
	 */
	public static function save_meta_box( int $post_id, ?\WP_Post $post = null ): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_wpctg_term', $post_id ) ) {
			return;
		}

		// Sanitize and save fields.
		if ( isset( $_POST['wpctg_short_definition'] ) ) {
			update_post_meta( $post_id, '_wpctg_short_definition', wp_kses_post( wp_unslash( $_POST['wpctg_short_definition'] ) ) );
		}

		if ( isset( $_POST['wpctg_pronunciation'] ) ) {
			update_post_meta( $post_id, '_wpctg_pronunciation', sanitize_text_field( wp_unslash( $_POST['wpctg_pronunciation'] ) ) );
		}

		if ( isset( $_POST['wpctg_source_url'] ) ) {
			update_post_meta( $post_id, '_wpctg_source_url', esc_url_raw( wp_unslash( $_POST['wpctg_source_url'] ) ) );
		}

		if ( isset( $_POST['wpctg_aliases'] ) ) {
			// Clean aliases: split, trim, remove empty, and re-join with commas.
			$raw_aliases = sanitize_text_field( wp_unslash( $_POST['wpctg_aliases'] ) );
			$alias_items = array_filter( array_map( 'trim', explode( ',', $raw_aliases ) ) );
			update_post_meta( $post_id, '_wpctg_aliases', implode( ', ', array_unique( $alias_items ) ) );
		}

		if ( isset( $_POST['wpctg_related_terms'] ) ) {
			update_post_meta( $post_id, '_wpctg_related_terms', sanitize_text_field( wp_unslash( $_POST['wpctg_related_terms'] ) ) );
		}

		$auto_link = ! empty( $_POST['wpctg_auto_link'] ) ? 1 : 0;
		update_post_meta( $post_id, '_wpctg_auto_link', $auto_link );

		$case_sensitive = ! empty( $_POST['wpctg_case_sensitive'] ) ? 1 : 0;
		update_post_meta( $post_id, '_wpctg_case_sensitive', $case_sensitive );

		$priority = isset( $_POST['wpctg_priority'] ) ? (int) $_POST['wpctg_priority'] : 10;
		update_post_meta( $post_id, '_wpctg_priority', $priority );

		// Invalidate glossary cache.
		CacheManager::invalidate();
	}

	/**
	 * Check for potential alias conflicts across terms.
	 *
	 * @param int    $current_post_id Post ID.
	 * @param string $raw_aliases     Comma separated aliases.
	 * @return string[] Array of conflict messages.
	 */
	public static function check_alias_conflicts( int $current_post_id, string $raw_aliases ): array {
		if ( empty( $raw_aliases ) ) {
			return [];
		}

		$aliases = array_filter( array_map( 'trim', explode( ',', $raw_aliases ) ) );
		if ( empty( $aliases ) ) {
			return [];
		}

		$conflicts = [];
		$query     = new \WP_Query(
			[
				'post_type'      => PostType::POST_TYPE,
				'post_status'    => 'publish',
				'post__not_in'   => [ $current_post_id ],
				'posts_per_page' => 100,
				'no_found_rows'  => true,
				'fields'         => 'ids',
			]
		);

		if ( ! empty( $query->posts ) ) {
			foreach ( $query->posts as $other_id ) {
				$other_title   = get_the_title( $other_id );
				$other_aliases = (string) get_post_meta( $other_id, '_wpctg_aliases', true );
				$other_items   = array_filter( array_map( 'trim', explode( ',', $other_aliases ) ) );

				foreach ( $aliases as $alias ) {
					// Check against other term's title.
					if ( 0 === strcasecmp( $alias, $other_title ) ) {
						$conflicts[] = sprintf(
							/* translators: 1: Alias name, 2: Other term title */
							__( 'The alias "%1$s" matches the main title of "%2$s".', 'wpcalibrate-term-glossary' ),
							$alias,
							$other_title
						);
					}

					// Check against other term's aliases.
					foreach ( $other_items as $other_alias ) {
						if ( 0 === strcasecmp( $alias, $other_alias ) ) {
							$conflicts[] = sprintf(
								/* translators: 1: Alias name, 2: Other term title */
								__( 'The alias "%1$s" is already used as an alias for "%2$s".', 'wpcalibrate-term-glossary' ),
								$alias,
								$other_title
							);
						}
					}
				}
			}
		}

		return array_unique( $conflicts );
	}

	/**
	 * Add custom columns to terms admin list.
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string>
	 */
	public static function add_admin_columns( array $columns ): array {
		$new_columns = [];
		foreach ( $columns as $key => $title ) {
			$new_columns[ $key ] = $title;
			if ( 'title' === $key ) {
				$new_columns['wpctg_short_def'] = __( 'Tooltip Definition', 'wpcalibrate-term-glossary' );
				$new_columns['wpctg_aliases']   = __( 'Aliases', 'wpcalibrate-term-glossary' );
				$new_columns['wpctg_auto_link'] = __( 'Auto Link', 'wpcalibrate-term-glossary' );
				$new_columns['wpctg_priority']  = __( 'Priority', 'wpcalibrate-term-glossary' );
			}
		}
		return $new_columns;
	}

	/**
	 * Render custom columns content.
	 *
	 * @param string $column_name Column key.
	 * @param int    $post_id     Post ID.
	 * @return void
	 */
	public static function render_admin_columns( string $column_name, int $post_id ): void {
		switch ( $column_name ) {
			case 'wpctg_short_def':
				$short_def = (string) get_post_meta( $post_id, '_wpctg_short_definition', true );
				echo esc_html( wp_trim_words( $short_def, 10, '...' ) ?: '—' );
				break;

			case 'wpctg_aliases':
				$aliases = (string) get_post_meta( $post_id, '_wpctg_aliases', true );
				echo esc_html( $aliases ?: '—' );
				break;

			case 'wpctg_auto_link':
				$auto_link = get_post_meta( $post_id, '_wpctg_auto_link', true );
				$enabled   = '' === $auto_link || 1 === (int) $auto_link;
				if ( $enabled ) {
					echo '<span style="color:#16a34a; font-weight:600;">' . esc_html__( 'Enabled', 'wpcalibrate-term-glossary' ) . '</span>';
				} else {
					echo '<span style="color:#94a3b8;">' . esc_html__( 'Disabled', 'wpcalibrate-term-glossary' ) . '</span>';
				}
				break;

			case 'wpctg_priority':
				$priority = get_post_meta( $post_id, '_wpctg_priority', true );
				echo esc_html( '' === $priority ? '10' : (string) $priority );
				break;
		}
	}
}
