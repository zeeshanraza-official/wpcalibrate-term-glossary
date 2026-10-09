<?php
/**
 * Plugin Overview Tab: Purpose, How to Use, and Methods.
 *
 * @package WPCalibrate\TermGlossary
 */

declare( strict_types=1 );

namespace WPCalibrate\TermGlossary\Admin;

use WPCalibrate\TermGlossary\Glossary\PostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Overview
 */
class Overview {

	/**
	 * Render the Overview tab content.
	 *
	 * @return void
	 */
	public static function render(): void {
		$dark_icon_url     = WPCTG_URL . 'assets/branding/icon-dark.png';
		$add_term_url      = admin_url( 'post-new.php?post_type=' . PostType::POST_TYPE );
		$all_terms_url     = admin_url( 'edit.php?post_type=' . PostType::POST_TYPE );
		$categories_url    = admin_url( 'edit-tags.php?taxonomy=' . PostType::TAXONOMY . '&post_type=' . PostType::POST_TYPE );
		$general_tab_url   = add_query_arg( [ 'page' => AdminMenu::PAGE_SLUG, 'tab' => 'general' ], admin_url( 'admin.php' ) );
		$index_tab_url     = add_query_arg( [ 'page' => AdminMenu::PAGE_SLUG, 'tab' => 'index' ], admin_url( 'admin.php' ) );
		$tooltip_tab_url   = add_query_arg( [ 'page' => AdminMenu::PAGE_SLUG, 'tab' => 'tooltip' ], admin_url( 'admin.php' ) );
		$seo_tab_url       = add_query_arg( [ 'page' => AdminMenu::PAGE_SLUG, 'tab' => 'seo' ], admin_url( 'admin.php' ) );
		$import_tab_url    = add_query_arg( [ 'page' => AdminMenu::PAGE_SLUG, 'tab' => 'import_export' ], admin_url( 'admin.php' ) );

		?>
		<div class="wpctg-overview-container">
			<!-- Welcome Header Banner -->
			<div class="wpctg-overview-banner">
				<div class="wpctg-overview-logo">
					<img src="<?php echo esc_url( $dark_icon_url ); ?>" alt="WPCalibrate" />
				</div>
				<div class="wpctg-overview-header-text">
					<h2><?php esc_html_e( 'Welcome to WPCalibrate Term Glossary', 'wpcalibrate-term-glossary' ); ?></h2>
					<p class="wpctg-overview-lead">
						<?php esc_html_e( 'Build a centralized vocabulary knowledge base, boost educational authority, and automatically link terms throughout your content with non-destructive HTML parsing and accessible tooltips.', 'wpcalibrate-term-glossary' ); ?>
					</p>
					<div class="wpctg-badge-group">
						<span class="wpctg-badge wpctg-badge-success"><?php esc_html_e( 'WordPress 6.8+ / 7.1+ Ready', 'wpcalibrate-term-glossary' ); ?></span>
						<span class="wpctg-badge wpctg-badge-success"><?php esc_html_e( 'PHP 8.2 / 8.3 Strict', 'wpcalibrate-term-glossary' ); ?></span>
						<span class="wpctg-badge wpctg-badge-info"><?php esc_html_e( 'DOMText Safe Engine', 'wpcalibrate-term-glossary' ); ?></span>
						<span class="wpctg-badge wpctg-badge-warning"><?php esc_html_e( 'Schema.org DefinedTerm', 'wpcalibrate-term-glossary' ); ?></span>
						<span class="wpctg-badge wpctg-badge-info"><?php esc_html_e( 'Gutenberg Dynamic Block', 'wpcalibrate-term-glossary' ); ?></span>
					</div>
				</div>
			</div>

			<!-- Quick Actions Bar -->
			<div class="wpctg-quick-actions-bar">
				<a href="<?php echo esc_url( $add_term_url ); ?>" class="button button-primary">
					<span class="dashicons dashicons-plus-alt2" style="vertical-align: middle; margin-right: 4px;"></span>
					<?php esc_html_e( 'Add New Term', 'wpcalibrate-term-glossary' ); ?>
				</a>
				<a href="<?php echo esc_url( $all_terms_url ); ?>" class="button button-secondary">
					<span class="dashicons dashicons-list-view" style="vertical-align: middle; margin-right: 4px;"></span>
					<?php esc_html_e( 'Manage Terms', 'wpcalibrate-term-glossary' ); ?>
				</a>
				<a href="<?php echo esc_url( $categories_url ); ?>" class="button button-secondary">
					<span class="dashicons dashicons-category" style="vertical-align: middle; margin-right: 4px;"></span>
					<?php esc_html_e( 'Categories', 'wpcalibrate-term-glossary' ); ?>
				</a>
				<a href="<?php echo esc_url( $general_tab_url ); ?>" class="button button-secondary">
					<span class="dashicons dashicons-admin-generic" style="vertical-align: middle; margin-right: 4px;"></span>
					<?php esc_html_e( 'General Settings', 'wpcalibrate-term-glossary' ); ?>
				</a>
				<a href="<?php echo esc_url( $index_tab_url ); ?>" class="button button-secondary">
					<span class="dashicons dashicons-book" style="vertical-align: middle; margin-right: 4px;"></span>
					<?php esc_html_e( 'Glossary Index', 'wpcalibrate-term-glossary' ); ?>
				</a>
				<a href="<?php echo esc_url( $import_tab_url ); ?>" class="button button-secondary">
					<span class="dashicons dashicons-database-import" style="vertical-align: middle; margin-right: 4px;"></span>
					<?php esc_html_e( 'Import / Export', 'wpcalibrate-term-glossary' ); ?>
				</a>
			</div>

			<!-- Section 1: Purpose & Architecture -->
			<div class="wpctg-overview-card" style="margin-top: 24px;">
				<h3>
					<span class="dashicons dashicons-info" style="color: #0284c7; vertical-align: middle; margin-right: 8px;"></span>
					<?php esc_html_e( 'Plugin Purpose & Architecture', 'wpcalibrate-term-glossary' ); ?>
				</h3>
				<p>
					<?php esc_html_e( 'Online readers frequently abandon articles or bounce when encountering unexplained industry jargon, acronyms, or technical terminology. Traditional glossary plugins either force manual link insertion or rely on crude regular expression string replacements that corrupt HTML tags, break buttons, inject links into code snippets, and mangle layout structures.', 'wpcalibrate-term-glossary' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'WPCalibrate Term Glossary eliminates these failures through a non-destructive DOMText parsing pipeline. It isolates raw text nodes via PHP DOMDocument and XPath, safely evaluating terms without touching existing links, headings, interactive buttons, or scripts. High-traffic performance is guaranteed by compiling term candidates into a normalized transient index that evaluates hundreds of terms in mere milliseconds.', 'wpcalibrate-term-glossary' ); ?>
				</p>
			</div>

			<!-- Section 2: How to Use (Step by Step) -->
			<div class="wpctg-overview-card" style="margin-top: 20px;">
				<h3>
					<span class="dashicons dashicons-clipboard" style="color: #0284c7; vertical-align: middle; margin-right: 8px;"></span>
					<?php esc_html_e( 'How to Use WPCalibrate Term Glossary', 'wpcalibrate-term-glossary' ); ?>
				</h3>
				<div class="wpctg-steps-grid">
					<div class="wpctg-step-item">
						<div class="wpctg-step-number">1</div>
						<div class="wpctg-step-content">
							<h4><?php esc_html_e( 'Create Terms & Definitions', 'wpcalibrate-term-glossary' ); ?></h4>
							<p>
								<?php
								printf(
									/* translators: %s: Link to Add Term screen */
									esc_html__( 'Navigate to %s. Enter the term name, long-form definition in the main editor, and a concise 1–2 sentence Short Definition for the preview tooltip.', 'wpcalibrate-term-glossary' ),
									'<a href="' . esc_url( $add_term_url ) . '"><strong>' . esc_html__( 'Glossary Terms > Add New Term', 'wpcalibrate-term-glossary' ) . '</strong></a>'
								);
								?>
							</p>
						</div>
					</div>

					<div class="wpctg-step-item">
						<div class="wpctg-step-number">2</div>
						<div class="wpctg-step-content">
							<h4><?php esc_html_e( 'Add Pronunciation, Aliases & Priority', 'wpcalibrate-term-glossary' ); ?></h4>
							<p>
								<?php esc_html_e( 'In the Term Details meta box, specify phonetic pronunciation, external reference URLs, and comma-separated aliases/synonyms (e.g. "SEO, Search Engine Optimization"). Adjust priority to resolve matching conflicts.', 'wpcalibrate-term-glossary' ); ?>
							</p>
						</div>
					</div>

					<div class="wpctg-step-item">
						<div class="wpctg-step-number">3</div>
						<div class="wpctg-step-content">
							<h4><?php esc_html_e( 'Configure Automatic Term Linking', 'wpcalibrate-term-glossary' ); ?></h4>
							<p>
								<?php
								printf(
									/* translators: %s: Link to General settings */
									esc_html__( 'Open %s to select supported post types (posts, pages, products), choose whether to link only the first occurrence or all instances, and toggle heading protection.', 'wpcalibrate-term-glossary' ),
									'<a href="' . esc_url( $general_tab_url ) . '"><strong>' . esc_html__( 'General Settings', 'wpcalibrate-term-glossary' ) . '</strong></a>'
								);
								?>
							</p>
						</div>
					</div>

					<div class="wpctg-step-item">
						<div class="wpctg-step-number">4</div>
						<div class="wpctg-step-content">
							<h4><?php esc_html_e( 'Select Interaction & Tooltip Mode', 'wpcalibrate-term-glossary' ); ?></h4>
							<p>
								<?php
								printf(
									/* translators: %s: Link to Tooltip settings */
									esc_html__( 'Choose between Tooltip + Link, Tooltip Only, or Link Only in %s. Customize the "Read full definition" button text and choose between light or dark tooltip themes.', 'wpcalibrate-term-glossary' ),
									'<a href="' . esc_url( $tooltip_tab_url ) . '"><strong>' . esc_html__( 'Tooltip Settings', 'wpcalibrate-term-glossary' ) . '</strong></a>'
								);
								?>
							</p>
						</div>
					</div>

					<div class="wpctg-step-item">
						<div class="wpctg-step-number">5</div>
						<div class="wpctg-step-content">
							<h4><?php esc_html_e( 'Publish the Public A-Z Glossary Index', 'wpcalibrate-term-glossary' ); ?></h4>
							<p>
								<?php
								printf(
									/* translators: 1: Block name, 2: Shortcode string */
									esc_html__( 'Create a new WordPress page and insert the dynamic Gutenberg block %1$s or add the shortcode %2$s to display an A-Z directory with live search and category filters.', 'wpcalibrate-term-glossary' ),
									'<strong>' . esc_html__( 'WPCalibrate Glossary Index', 'wpcalibrate-term-glossary' ) . '</strong>',
									'<code>[wpcalibrate_glossary]</code>'
								);
								?>
							</p>
						</div>
					</div>

					<div class="wpctg-step-item">
						<div class="wpctg-step-number">6</div>
						<div class="wpctg-step-content">
							<h4><?php esc_html_e( 'Targeted Content Exclusions', 'wpcalibrate-term-glossary' ); ?></h4>
							<p>
								<?php esc_html_e( 'Exclude any individual post using the sidebar meta box checkbox, or exempt any container in your theme or page builder by adding class .wpctg-no-glossary or attribute data-wpctg-ignore.', 'wpcalibrate-term-glossary' ); ?>
							</p>
						</div>
					</div>
				</div>
			</div>

			<!-- Section 3: Methods & Architectural Deep-Dive -->
			<div class="wpctg-overview-card" style="margin-top: 20px;">
				<h3>
					<span class="dashicons dashicons-admin-settings" style="color: #0284c7; vertical-align: middle; margin-right: 8px;"></span>
					<?php esc_html_e( 'Methods & Architectural Deep-Dive', 'wpcalibrate-term-glossary' ); ?>
				</h3>
				<p>
					<?php esc_html_e( 'A side-by-side architectural reference detailing how detection, precedence, interaction, and structured data operate:', 'wpcalibrate-term-glossary' ); ?>
				</p>

				<div class="wpctg-methods-table-wrapper">
					<table class="widefat striped wpctg-methods-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Subsystem / Method', 'wpcalibrate-term-glossary' ); ?></th>
								<th><?php esc_html_e( 'Implementation Logic', 'wpcalibrate-term-glossary' ); ?></th>
								<th><?php esc_html_e( 'Technical Advantage', 'wpcalibrate-term-glossary' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><strong><?php esc_html_e( 'DOMText XPath Parser', 'wpcalibrate-term-glossary' ); ?></strong></td>
								<td><code>DOMXPath query: //text()[not(ancestor::a|button|code|pre|script|style)]</code></td>
								<td><?php esc_html_e( 'Guarantees HTML integrity; eliminates nested anchor links, broken attributes, and script breakage common with regex.', 'wpcalibrate-term-glossary' ); ?></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Phrase-Length Disambiguation', 'wpcalibrate-term-glossary' ); ?></strong></td>
								<td><code>Priority DESC &rarr; mb_strlen(phrase) DESC</code></td>
								<td><?php esc_html_e( 'Multi-word phrases like "Search Engine Optimization" always match before nested substrings like "Search Engine".', 'wpcalibrate-term-glossary' ); ?></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Unicode Word Boundaries', 'wpcalibrate-term-glossary' ); ?></strong></td>
								<td><code>(?&lt;![\p{L}\p{N}_])phrase(?![\p{L}\p{N}_])/u</code></td>
								<td><?php esc_html_e( 'Prevents false matches inside larger words (e.g. "art" in "partner") while seamlessly supporting accented and non-Latin scripts.', 'wpcalibrate-term-glossary' ); ?></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Triple Interaction Modes', 'wpcalibrate-term-glossary' ); ?></strong></td>
								<td><code>tooltip_link | tooltip_only | link_only</code></td>
								<td><?php esc_html_e( 'Adapts to your site UX: lightweight anchor links, interactive informative popups, or dedicated reference navigation.', 'wpcalibrate-term-glossary' ); ?></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Schema.org DefinedTerm', 'wpcalibrate-term-glossary' ); ?></strong></td>
								<td><code>{"@context":"https://schema.org","@type":"DefinedTerm",...}</code></td>
								<td><?php esc_html_e( 'Provides search engines with machine-readable definitions and aliases for Google rich snippet eligibility.', 'wpcalibrate-term-glossary' ); ?></td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Formula-Safe CSV Pipeline', 'wpcalibrate-term-glossary' ); ?></strong></td>
								<td><code>Cells starting with =, +, -, @ prepended with '</code></td>
								<td><?php esc_html_e( 'Neutralizes spreadsheet formula injection vulnerabilities when glossary terms are exported to Excel or Google Sheets.', 'wpcalibrate-term-glossary' ); ?></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<?php
	}
}
