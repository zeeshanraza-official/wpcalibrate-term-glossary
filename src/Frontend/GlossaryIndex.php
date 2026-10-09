<?php
/**
 * Public A-Z Glossary Index Renderer.
 *
 * @package WPCalibrate\TermGlossary
 */

declare( strict_types=1 );

namespace WPCalibrate\TermGlossary\Frontend;

use WPCalibrate\TermGlossary\Glossary\TermRepository;
use WPCalibrate\TermGlossary\Glossary\PostType;
use WPCalibrate\TermGlossary\Infrastructure\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GlossaryIndex
 */
class GlossaryIndex {

	/**
	 * Register shortcode and hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_shortcode( 'wpcalibrate_glossary', [ self::class, 'render_shortcode' ] );
	}

	/**
	 * Render shortcode [wpcalibrate_glossary].
	 *
	 * @param array<string, mixed>|string $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public static function render_shortcode( array|string $atts = [] ): string {
		$parsed_atts = shortcode_atts(
			[
				'category' => '',
				'search'   => '',
				'alphabet' => '',
				'per_page' => '',
				'order'    => '',
			],
			is_array( $atts ) ? $atts : [],
			'wpcalibrate_glossary'
		);

		return self::render( $parsed_atts );
	}

	/**
	 * Reusable renderer for both Gutenberg block and shortcode.
	 *
	 * @param array<string, mixed> $attributes Attributes.
	 * @return string Rendered HTML.
	 */
	public static function render( array $attributes = [] ): string {
		// Read settings defaults.
		$default_per_page = (int) Settings::get( 'terms_per_page', 20 );
		$default_search   = (bool) Settings::get( 'enable_live_search', true );
		$default_alphabet = (bool) Settings::get( 'enable_alphabet_filter', true );
		$default_category = (bool) Settings::get( 'enable_category_filter', true );
		$default_order    = (string) Settings::get( 'default_sort_order', 'asc' );

		// Parse attributes.
		$per_page = ! empty( $attributes['per_page'] ) ? max( 1, (int) $attributes['per_page'] ) : $default_per_page;
		$show_search = '' !== ( $attributes['search'] ?? '' )
			? filter_var( $attributes['search'], FILTER_VALIDATE_BOOLEAN )
			: $default_search;
		$show_alphabet = '' !== ( $attributes['alphabet'] ?? '' )
			? filter_var( $attributes['alphabet'], FILTER_VALIDATE_BOOLEAN )
			: $default_alphabet;
		$order = ! empty( $attributes['order'] ) && in_array( strtolower( (string) $attributes['order'] ), [ 'asc', 'desc' ], true )
			? strtolower( (string) $attributes['order'] )
			: $default_order;
		$attr_cat = sanitize_title( (string) ( $attributes['category'] ?? '' ) );

		// Parse request query parameters (non-JS fallback and direct URL filtering).
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$req_search = isset( $_GET['glossary_search'] ) ? sanitize_text_field( wp_unslash( $_GET['glossary_search'] ) ) : '';
		$req_letter = isset( $_GET['letter'] ) ? sanitize_text_field( wp_unslash( $_GET['letter'] ) ) : '';
		$req_cat    = isset( $_GET['glossary_cat'] ) ? sanitize_title( wp_unslash( $_GET['glossary_cat'] ) ) : $attr_cat;
		$paged      = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : ( ( get_query_var( 'paged' ) ) ? (int) get_query_var( 'paged' ) : 1 );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Query terms.
		$query_result = TermRepository::query_terms(
			[
				'letter'         => $req_letter,
				'search'         => $req_search,
				'category'       => $req_cat,
				'posts_per_page' => $per_page,
				'paged'          => $paged,
				'order'          => $order,
			]
		);

		$terms       = $query_result['terms'];
		$total_terms = $query_result['total'];
		$max_pages   = $query_result['max_pages'];

		// Active initial letters.
		$active_letters = TermRepository::get_active_letters();

		// Fetch all categories for filter dropdown.
		$categories = get_terms(
			[
				'taxonomy'   => PostType::TAXONOMY,
				'hide_empty' => true,
			]
		);

		ob_start();
		?>
		<div class="wpctg-index-container">
			<div class="wpctg-live-announcer screen-reader-text" aria-live="polite"></div>

			<?php if ( $show_search || ( $default_category && ! empty( $categories ) && ! is_wp_error( $categories ) ) ) : ?>
				<div class="wpctg-index-toolbar">
					<?php if ( $show_search ) : ?>
						<form method="get" action="" class="wpctg-index-search-form" role="search">
							<label for="wpctg-search-input" class="screen-reader-text"><?php esc_html_e( 'Search glossary terms', 'wpcalibrate-term-glossary' ); ?></label>
							<input type="search" id="wpctg-search-input" name="glossary_search" value="<?php echo esc_attr( $req_search ); ?>" placeholder="<?php esc_attr_e( 'Search glossary terms...', 'wpcalibrate-term-glossary' ); ?>" class="wpctg-index-search-input" />
							<?php if ( '' !== $req_letter ) : ?>
								<input type="hidden" name="letter" value="<?php echo esc_attr( $req_letter ); ?>" />
							<?php endif; ?>
							<?php if ( '' !== $req_cat ) : ?>
								<input type="hidden" name="glossary_cat" value="<?php echo esc_attr( $req_cat ); ?>" />
							<?php endif; ?>
							<button type="submit" class="wpctg-index-search-btn"><?php esc_html_e( 'Search', 'wpcalibrate-term-glossary' ); ?></button>
						</form>
					<?php endif; ?>

					<?php if ( $default_category && ! empty( $categories ) && ! is_wp_error( $categories ) && empty( $attr_cat ) ) : ?>
						<div class="wpctg-index-category-filter">
							<form method="get" action="">
								<label for="wpctg-category-select" class="screen-reader-text"><?php esc_html_e( 'Filter by Category', 'wpcalibrate-term-glossary' ); ?></label>
								<select id="wpctg-category-select" name="glossary_cat" onchange="this.form.submit()">
									<option value=""><?php esc_html_e( 'All Categories', 'wpcalibrate-term-glossary' ); ?></option>
									<?php foreach ( $categories as $cat ) : ?>
										<option value="<?php echo esc_attr( $cat->slug ); ?>" <?php selected( $req_cat, $cat->slug ); ?>>
											<?php echo esc_html( $cat->name ); ?> (<?php echo (int) $cat->count; ?>)
										</option>
									<?php endforeach; ?>
								</select>
								<?php if ( '' !== $req_letter ) : ?>
									<input type="hidden" name="letter" value="<?php echo esc_attr( $req_letter ); ?>" />
								<?php endif; ?>
								<noscript><button type="submit" class="button"><?php esc_html_e( 'Filter', 'wpcalibrate-term-glossary' ); ?></button></noscript>
							</form>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( $show_alphabet ) : ?>
				<nav class="wpctg-alphabet-nav-wrapper" aria-label="<?php esc_attr_e( 'Alphabetical Navigation', 'wpcalibrate-term-glossary' ); ?>">
					<ul class="wpctg-alphabet-nav">
						<?php
						$current_url = remove_query_arg( [ 'letter', 'paged' ] );
						// All button.
						$all_active = '' === $req_letter;
						?>
						<li class="wpctg-alphabet-item <?php echo $all_active ? 'wpctg-active' : ''; ?>">
							<a href="<?php echo esc_url( $current_url ); ?>" <?php echo $all_active ? 'aria-current="page"' : ''; ?>>
								<?php esc_html_e( 'All', 'wpcalibrate-term-glossary' ); ?>
							</a>
						</li>
						<?php
						$alphabet_chars = array_merge( range( 'A', 'Z' ), [ '#' ] );
						foreach ( $alphabet_chars as $char ) :
							$has_terms = in_array( $char, $active_letters, true );
							$is_active = $req_letter === $char;
							$char_url  = add_query_arg( 'letter', $char, $current_url );

							if ( $has_terms ) :
								?>
								<li class="wpctg-alphabet-item <?php echo $is_active ? 'wpctg-active' : ''; ?>">
									<a href="<?php echo esc_url( $char_url ); ?>" <?php echo $is_active ? 'aria-current="page"' : ''; ?>>
										<?php echo esc_html( $char ); ?>
									</a>
								</li>
							<?php else : ?>
								<li class="wpctg-alphabet-item wpctg-disabled" aria-hidden="true">
									<span><?php echo esc_html( $char ); ?></span>
								</li>
								<?php
							endif;
						endforeach;
						?>
					</ul>
				</nav>
			<?php endif; ?>

			<?php if ( ! empty( $terms ) ) : ?>
				<div class="wpctg-index-grid">
					<?php foreach ( $terms as $term ) : ?>
						<article class="wpctg-index-card">
							<h3 class="wpctg-index-card-title">
								<a href="<?php echo esc_url( $term['url'] ); ?>">
									<?php echo esc_html( $term['title'] ); ?>
								</a>
							</h3>
							<?php if ( ! empty( $term['pronunciation'] ) ) : ?>
								<div class="wpctg-index-card-pronunciation">
									<?php echo esc_html( $term['pronunciation'] ); ?>
								</div>
							<?php endif; ?>
							<div class="wpctg-index-card-def">
								<?php echo esc_html( $term['short_def'] ); ?>
							</div>
							<?php if ( ! empty( $term['categories'] ) || ! empty( $term['aliases'] ) ) : ?>
								<div class="wpctg-index-card-meta">
									<?php if ( ! empty( $term['categories'] ) ) : ?>
										<div><strong><?php esc_html_e( 'Category:', 'wpcalibrate-term-glossary' ); ?></strong> <?php echo esc_html( implode( ', ', $term['categories'] ) ); ?></div>
									<?php endif; ?>
									<?php if ( ! empty( $term['aliases'] ) ) : ?>
										<div><strong><?php esc_html_e( 'Synonyms:', 'wpcalibrate-term-glossary' ); ?></strong> <?php echo esc_html( $term['aliases'] ); ?></div>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>

				<?php if ( $max_pages > 1 ) : ?>
					<nav class="wpctg-pagination" aria-label="<?php esc_attr_e( 'Glossary pagination', 'wpcalibrate-term-glossary' ); ?>">
						<?php
						echo paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							[
								'total'     => $max_pages,
								'current'   => $paged,
								'format'    => '?paged=%#%',
								'prev_text' => '&laquo; ' . esc_html__( 'Previous', 'wpcalibrate-term-glossary' ),
								'next_text' => esc_html__( 'Next', 'wpcalibrate-term-glossary' ) . ' &raquo;',
							]
						);
						?>
					</nav>
				<?php endif; ?>
			<?php else : ?>
				<div class="wpctg-empty-state" style="padding:32px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; text-align:center;">
					<p style="font-size:16px; color:#64748b; margin:0;">
						<?php esc_html_e( 'No glossary terms found matching your criteria.', 'wpcalibrate-term-glossary' ); ?>
					</p>
					<?php if ( '' !== $req_search || '' !== $req_letter || '' !== $req_cat ) : ?>
						<p style="margin-top:12px;">
							<a href="<?php echo esc_url( remove_query_arg( [ 'glossary_search', 'letter', 'glossary_cat', 'paged' ] ) ); ?>" class="button button-secondary">
								<?php esc_html_e( 'Clear Filters & Show All', 'wpcalibrate-term-glossary' ); ?>
							</a>
						</p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
