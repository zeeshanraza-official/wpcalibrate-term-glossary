<?php
/**
 * Admin Menu and Settings UI integration.
 *
 * @package WPCalibrate\TermGlossary
 */

declare( strict_types=1 );

namespace WPCalibrate\TermGlossary\Admin;

use WPCalibrate\TermGlossary\Glossary\PostType;
use WPCalibrate\TermGlossary\Infrastructure\Settings;
use WPCalibrate\TermGlossary\Infrastructure\CacheManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AdminMenu
 */
class AdminMenu {

	public const PARENT_SLUG = 'wpcalibrate';
	public const PAGE_SLUG   = 'wpcalibrate-term-glossary';

	/**
	 * Initialize menu hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_menu', [ self::class, 'register_menus' ], 20 );
		add_action( 'admin_init', [ self::class, 'handle_save_settings' ] );
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_admin_assets' ] );
		add_action( 'admin_head', [ self::class, 'render_admin_menu_styles' ] );
	}

	/**
	 * Output inline CSS in admin_head to strictly constrain the WPCalibrate admin menu icon globally.
	 *
	 * Constrains icon to 20x20px with proper padding on all WordPress admin pages,
	 * preventing oversized icon overflow on screens where plugin admin.css is not enqueued.
	 *
	 * @return void
	 */
	public static function render_admin_menu_styles(): void {
		$icon_dark_url = esc_url( WPCTG_URL . 'assets/branding/icon-dark.png' );
		?>
		<style id="wpcalibrate-menu-icon-style">
			#adminmenu #toplevel_page_wpcalibrate .wp-menu-image img,
			#adminmenu a.toplevel_page_wpcalibrate .wp-menu-image img,
			#adminmenu a[href*="page=wpcalibrate"] .wp-menu-image img {
				width: 20px !important;
				height: 20px !important;
				max-width: 20px !important;
				max-height: 20px !important;
				padding-top: 7px !important;
				object-fit: contain !important;
				box-sizing: content-box !important;
				opacity: 0.85;
				transition: opacity 0.15s ease-in-out;
			}
			#adminmenu #toplevel_page_wpcalibrate:hover .wp-menu-image img,
			#adminmenu #toplevel_page_wpcalibrate.wp-has-current-submenu .wp-menu-image img,
			#adminmenu #toplevel_page_wpcalibrate.current .wp-menu-image img,
			#adminmenu #toplevel_page_wpcalibrate.wp-menu-open .wp-menu-image img {
				opacity: 1 !important;
			}
			body.admin-color-light #adminmenu #toplevel_page_wpcalibrate .wp-menu-image img,
			body.admin-color-light #adminmenu a.toplevel_page_wpcalibrate .wp-menu-image img,
			body.admin-color-light #adminmenu a[href*="page=wpcalibrate"] .wp-menu-image img {
				content: url('<?php echo $icon_dark_url; ?>') !important;
			}
		</style>
		<?php
	}

	/**
	 * Register the WPCalibrate parent menu (if not existing) and Term Glossary submenus.
	 *
	 * @return void
	 */
	public static function register_menus(): void {
		global $menu;

		// Check if the shared WPCalibrate parent menu is already registered.
		$parent_exists = false;
		if ( ! empty( $GLOBALS['admin_page_hooks'][ self::PARENT_SLUG ] ) ) {
			$parent_exists = true;
		} else {
			foreach ( (array) $menu as $item ) {
				if ( isset( $item[2] ) && self::PARENT_SLUG === $item[2] ) {
					$parent_exists = true;
					break;
				}
			}
		}

		if ( ! $parent_exists ) {
			$icon_white_url = WPCTG_URL . 'assets/branding/icon-white.png';
			add_menu_page(
				'WPCalibrate',
				'WPCalibrate',
				'edit_wpctg_terms',
				self::PARENT_SLUG,
				[ self::class, 'render_parent_dashboard' ],
				$icon_white_url,
				28
			);
		}

		// Submenus under WPCalibrate.
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Glossary Terms', 'wpcalibrate-term-glossary' ),
			__( 'Glossary Terms', 'wpcalibrate-term-glossary' ),
			'edit_wpctg_terms',
			'edit.php?post_type=' . PostType::POST_TYPE
		);

		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Add New Term', 'wpcalibrate-term-glossary' ),
			__( 'Add New Term', 'wpcalibrate-term-glossary' ),
			'edit_wpctg_terms',
			'post-new.php?post_type=' . PostType::POST_TYPE
		);

		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Glossary Categories', 'wpcalibrate-term-glossary' ),
			__( 'Categories', 'wpcalibrate-term-glossary' ),
			'manage_wpctg_categories',
			'edit-tags.php?taxonomy=' . PostType::TAXONOMY . '&post_type=' . PostType::POST_TYPE
		);

		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Term Glossary Settings', 'wpcalibrate-term-glossary' ),
			__( 'Term Glossary', 'wpcalibrate-term-glossary' ),
			'manage_options',
			self::PAGE_SLUG,
			[ self::class, 'render_settings_page' ]
		);
	}

	/**
	 * Default landing page for WPCalibrate parent if no other plugin redirected it.
	 *
	 * @return void
	 */
	public static function render_parent_dashboard(): void {
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
		exit;
	}

	/**
	 * Enqueue admin stylesheet and scripts.
	 *
	 * @param string $hook_suffix Admin page hook.
	 * @return void
	 */
	public static function enqueue_admin_assets( string $hook_suffix ): void {
		if ( false !== strpos( $hook_suffix, self::PAGE_SLUG ) || PostType::POST_TYPE === get_post_type() ) {
			wp_enqueue_style(
				'wpctg-admin-css',
				WPCTG_URL . 'assets/css/admin.css',
				[],
				WPCTG_VERSION
			);

			wp_enqueue_script(
				'wpctg-admin-js',
				WPCTG_URL . 'assets/js/admin.js',
				[ 'jquery' ],
				WPCTG_VERSION,
				true
			);

			wp_localize_script(
				'wpctg-admin-js',
				'wpctgAdminData',
				[
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'wpctg_admin_nonce' ),
				]
			);
		}
	}

	/**
	 * Handle settings form submission.
	 *
	 * @return void
	 */
	public static function handle_save_settings(): void {
		if ( ! isset( $_POST['wpctg_save_settings'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to manage options.', 'wpcalibrate-term-glossary' ) );
		}

		check_admin_referer( 'wpctg_settings_nonce_action', 'wpctg_settings_nonce_name' );

		$raw_input = isset( $_POST['wpctg_settings'] ) && is_array( $_POST['wpctg_settings'] ) ? wp_unslash( $_POST['wpctg_settings'] ) : [];
		$active_tab = isset( $_POST['wpctg_active_tab'] ) ? sanitize_key( wp_unslash( $_POST['wpctg_active_tab'] ) ) : 'general';

		// Get current settings so tabs don't wipe out other settings.
		$current_settings = Settings::get();
		$updated_settings = array_merge( $current_settings, $raw_input );

		// Specific handling for checkboxes on the current tab.
		if ( 'general' === $active_tab ) {
			$updated_settings['enable_auto_linking']  = ! empty( $raw_input['enable_auto_linking'] );
			$updated_settings['process_headings']     = ! empty( $raw_input['process_headings'] );
			$updated_settings['case_sensitive']       = ! empty( $raw_input['case_sensitive'] );
			$updated_settings['supported_post_types'] = ! empty( $raw_input['supported_post_types'] ) ? (array) $raw_input['supported_post_types'] : [ 'post', 'page' ];
		} elseif ( 'index' === $active_tab ) {
			$updated_settings['enable_live_search']     = ! empty( $raw_input['enable_live_search'] );
			$updated_settings['enable_alphabet_filter'] = ! empty( $raw_input['enable_alphabet_filter'] );
			$updated_settings['enable_category_filter'] = ! empty( $raw_input['enable_category_filter'] );
		} elseif ( 'tooltip' === $active_tab ) {
			$updated_settings['enable_tooltips'] = ! empty( $raw_input['enable_tooltips'] );
			$updated_settings['show_read_more']  = ! empty( $raw_input['show_read_more'] );
		} elseif ( 'seo' === $active_tab ) {
			$updated_settings['enable_defined_term_schema']     = ! empty( $raw_input['enable_defined_term_schema'] );
			$updated_settings['enable_defined_term_set_schema'] = ! empty( $raw_input['enable_defined_term_set_schema'] );
		} elseif ( 'advanced' === $active_tab ) {
			$updated_settings['enable_debug_log']         = ! empty( $raw_input['enable_debug_log'] );
			$updated_settings['delete_data_on_uninstall'] = ! empty( $raw_input['delete_data_on_uninstall'] );
		}

		Settings::update( $updated_settings );
		CacheManager::invalidate();

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => self::PAGE_SLUG,
					'tab'     => $active_tab,
					'updated' => '1',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Render the full plugin settings page.
	 *
	 * @return void
	 */
	public static function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wpcalibrate-term-glossary' ) );
		}

		$settings   = Settings::get();
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';

		$tabs = [
			'overview'      => __( 'Overview', 'wpcalibrate-term-glossary' ),
			'general'       => __( 'General', 'wpcalibrate-term-glossary' ),
			'index'         => __( 'Glossary Index', 'wpcalibrate-term-glossary' ),
			'tooltip'       => __( 'Tooltip', 'wpcalibrate-term-glossary' ),
			'seo'           => __( 'SEO & Schema', 'wpcalibrate-term-glossary' ),
			'advanced'      => __( 'Advanced', 'wpcalibrate-term-glossary' ),
			'import_export' => __( 'Import / Export', 'wpcalibrate-term-glossary' ),
			'contact'       => __( 'Contact & Support', 'wpcalibrate-term-glossary' ),
		];

		if ( ! array_key_exists( $active_tab, $tabs ) ) {
			$active_tab = 'overview';
		}

		?>
		<div class="wrap wpctg-admin-wrap">
			<div class="wpctg-header-bar">
				<div class="wpctg-branding-block">
					<img src="<?php echo esc_url( WPCTG_URL . 'assets/branding/icon-dark.png' ); ?>" alt="WPCalibrate" class="wpctg-brand-icon" />
					<div class="wpctg-title-group">
						<h1 class="wpctg-page-title"><?php esc_html_e( 'WPCalibrate Term Glossary', 'wpcalibrate-term-glossary' ); ?></h1>
						<span class="wpctg-version-badge"><?php echo esc_html( 'v' . WPCTG_VERSION ); ?></span>
					</div>
				</div>
				<div class="wpctg-header-actions">
					<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . PostType::POST_TYPE ) ); ?>" class="button button-primary">
						<span class="dashicons dashicons-plus-alt2" style="margin-top:4px;"></span> <?php esc_html_e( 'Add New Term', 'wpcalibrate-term-glossary' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . PostType::POST_TYPE ) ); ?>" class="button">
						<?php esc_html_e( 'View All Terms', 'wpcalibrate-term-glossary' ); ?>
					</a>
				</div>
			</div>

			<?php if ( isset( $_GET['updated'] ) && '1' === $_GET['updated'] ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Glossary settings saved successfully.', 'wpcalibrate-term-glossary' ); ?></p>
				</div>
			<?php endif; ?>

			<?php
			$import_notice = get_transient( 'wpctg_import_notice' );
			if ( is_array( $import_notice ) ) :
				delete_transient( 'wpctg_import_notice' );
				?>
				<div class="notice notice-<?php echo esc_attr( $import_notice['type'] ?? 'info' ); ?> is-dismissible">
					<p><?php echo esc_html( $import_notice['message'] ?? '' ); ?></p>
				</div>
			<?php endif; ?>

			<nav class="nav-tab-wrapper wpctg-nav-tabs" aria-label="<?php esc_attr_e( 'Settings Tabs', 'wpcalibrate-term-glossary' ); ?>">
				<?php foreach ( $tabs as $tab_id => $tab_title ) : ?>
					<a href="<?php echo esc_url( add_query_arg( [ 'page' => self::PAGE_SLUG, 'tab' => $tab_id ], admin_url( 'admin.php' ) ) ); ?>" class="nav-tab <?php echo $active_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $tab_title ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="wpctg-tab-content-card">
				<?php if ( 'overview' === $active_tab ) : ?>
					<?php Overview::render(); ?>
				<?php elseif ( 'import_export' === $active_tab ) : ?>
					<?php self::render_import_export_tab(); ?>
				<?php elseif ( 'contact' === $active_tab ) : ?>
					<?php self::render_contact_tab(); ?>
				<?php else : ?>
					<form method="post" action="" class="wpctg-settings-form">
						<?php wp_nonce_field( 'wpctg_settings_nonce_action', 'wpctg_settings_nonce_name' ); ?>
						<input type="hidden" name="wpctg_active_tab" value="<?php echo esc_attr( $active_tab ); ?>" />

						<?php
						switch ( $active_tab ) {
							case 'general':
								self::render_general_tab( $settings );
								break;
							case 'index':
								self::render_index_tab( $settings );
								break;
							case 'tooltip':
								self::render_tooltip_tab( $settings );
								break;
							case 'seo':
								self::render_seo_tab( $settings );
								break;
							case 'advanced':
								self::render_advanced_tab( $settings );
								break;
						}
						?>

						<p class="submit" style="margin-top:24px;">
							<button type="submit" name="wpctg_save_settings" class="button button-primary button-hero">
								<?php esc_html_e( 'Save Settings', 'wpcalibrate-term-glossary' ); ?>
							</button>
						</p>
					</form>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * General Settings Tab.
	 *
	 * @param array<string, mixed> $settings Current settings.
	 * @return void
	 */
	private static function render_general_tab( array $settings ): void {
		$all_post_types = get_post_types( [ 'public' => true ], 'objects' );
		unset( $all_post_types[ PostType::POST_TYPE ], $all_post_types['attachment'] );
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Automatic Linking', 'wpcalibrate-term-glossary' ); ?></th>
				<td>
					<label for="wpctg_enable_auto_linking">
						<input type="checkbox" id="wpctg_enable_auto_linking" name="wpctg_settings[enable_auto_linking]" value="1" <?php checked( $settings['enable_auto_linking'] ); ?> />
						<strong><?php esc_html_e( 'Enable automatic term detection in frontend content', 'wpcalibrate-term-glossary' ); ?></strong>
					</label>
					<p class="description">
						<?php esc_html_e( 'When enabled, published glossary terms will be automatically detected and highlighted across supported post types.', 'wpcalibrate-term-glossary' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Supported Post Types', 'wpcalibrate-term-glossary' ); ?></th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><span><?php esc_html_e( 'Supported Post Types', 'wpcalibrate-term-glossary' ); ?></span></legend>
						<?php foreach ( $all_post_types as $pt_name => $pt_obj ) : ?>
							<label style="display:inline-block; margin-right:16px; margin-bottom:8px;">
								<input type="checkbox" name="wpctg_settings[supported_post_types][]" value="<?php echo esc_attr( $pt_name ); ?>" <?php checked( in_array( $pt_name, (array) $settings['supported_post_types'], true ) ); ?> />
								<?php echo esc_html( $pt_obj->labels->name ); ?> (<code><?php echo esc_html( $pt_name ); ?></code>)
							</label>
						<?php endforeach; ?>
					</fieldset>
					<p class="description">
						<?php esc_html_e( 'Select which content types should be automatically parsed for glossary terms.', 'wpcalibrate-term-glossary' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Replacement Mode', 'wpcalibrate-term-glossary' ); ?></th>
				<td>
					<fieldset>
						<label style="display:block; margin-bottom:8px;">
							<input type="radio" name="wpctg_settings[match_mode]" value="first" <?php checked( 'first', $settings['match_mode'] ); ?> />
							<strong><?php esc_html_e( 'First occurrence only (Recommended)', 'wpcalibrate-term-glossary' ); ?></strong> — <?php esc_html_e( 'Highlights the first time each term appears in an article to keep content readable.', 'wpcalibrate-term-glossary' ); ?>
						</label>
						<label style="display:block;">
							<input type="radio" name="wpctg_settings[match_mode]" value="all" <?php checked( 'all', $settings['match_mode'] ); ?> />
							<strong><?php esc_html_e( 'Every occurrence', 'wpcalibrate-term-glossary' ); ?></strong> — <?php esc_html_e( 'Highlights every instance of candidate terms subject to maximum limits below.', 'wpcalibrate-term-glossary' ); ?>
						</label>
					</fieldset>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="wpctg_max_replacements_per_term"><?php esc_html_e( 'Max Per Term', 'wpcalibrate-term-glossary' ); ?></label></th>
				<td>
					<input type="number" id="wpctg_max_replacements_per_term" name="wpctg_settings[max_replacements_per_term]" value="<?php echo esc_attr( (string) $settings['max_replacements_per_term'] ); ?>" min="0" max="100" class="small-text" />
					<p class="description"><?php esc_html_e( 'Maximum times an individual term can be highlighted per post (0 for unlimited).', 'wpcalibrate-term-glossary' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="wpctg_max_total_replacements"><?php esc_html_e( 'Max Total Replacements', 'wpcalibrate-term-glossary' ); ?></label></th>
				<td>
					<input type="number" id="wpctg_max_total_replacements" name="wpctg_settings[max_total_replacements]" value="<?php echo esc_attr( (string) $settings['max_total_replacements'] ); ?>" min="0" max="500" class="small-text" />
					<p class="description"><?php esc_html_e( 'Maximum total terms highlighted in a single content item (0 for unlimited).', 'wpcalibrate-term-glossary' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Processing in Headings', 'wpcalibrate-term-glossary' ); ?></th>
				<td>
					<label for="wpctg_process_headings">
						<input type="checkbox" id="wpctg_process_headings" name="wpctg_settings[process_headings]" value="1" <?php checked( $settings['process_headings'] ); ?> />
						<?php esc_html_e( 'Allow term detection inside headings (H1, H2, H3, H4, H5, H6)', 'wpcalibrate-term-glossary' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Disabled by default for clean heading hierarchy and styling.', 'wpcalibrate-term-glossary' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Link & Tooltip Behavior', 'wpcalibrate-term-glossary' ); ?></th>
				<td>
					<fieldset>
						<label style="display:block; margin-bottom:8px;">
							<input type="radio" name="wpctg_settings[link_mode]" value="tooltip_link" <?php checked( 'tooltip_link', $settings['link_mode'] ); ?> />
							<strong><?php esc_html_e( 'Tooltip + Term Page Link (Default)', 'wpcalibrate-term-glossary' ); ?></strong> — <?php esc_html_e( 'Shows tooltip on hover/focus and links to full term page.', 'wpcalibrate-term-glossary' ); ?>
						</label>
						<label style="display:block; margin-bottom:8px;">
							<input type="radio" name="wpctg_settings[link_mode]" value="tooltip_only" <?php checked( 'tooltip_only', $settings['link_mode'] ); ?> />
							<strong><?php esc_html_e( 'Tooltip Only', 'wpcalibrate-term-glossary' ); ?></strong> — <?php esc_html_e( 'Displays tooltip definition without linking to a separate term page.', 'wpcalibrate-term-glossary' ); ?>
						</label>
						<label style="display:block;">
							<input type="radio" name="wpctg_settings[link_mode]" value="link_only" <?php checked( 'link_only', $settings['link_mode'] ); ?> />
							<strong><?php esc_html_e( 'Link Only', 'wpcalibrate-term-glossary' ); ?></strong> — <?php esc_html_e( 'Links directly to the term page without a popup tooltip.', 'wpcalibrate-term-glossary' ); ?>
						</label>
					</fieldset>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Case Sensitivity', 'wpcalibrate-term-glossary' ); ?></th>
				<td>
					<label for="wpctg_case_sensitive">
						<input type="checkbox" id="wpctg_case_sensitive" name="wpctg_settings[case_sensitive]" value="1" <?php checked( $settings['case_sensitive'] ); ?> />
						<?php esc_html_e( 'Enable global case-sensitive matching by default', 'wpcalibrate-term-glossary' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'When unchecked, "api" matches "API" and "Api". Individual terms can also override this setting.', 'wpcalibrate-term-glossary' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Glossary Index Tab.
	 *
	 * @param array<string, mixed> $settings Current settings.
	 * @return void
	 */
	private static function render_index_tab( array $settings ): void {
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="wpctg_terms_per_page"><?php esc_html_e( 'Terms Per Page', 'wpcalibrate-term-glossary' ); ?></label></th>
				<td>
					<input type="number" id="wpctg_terms_per_page" name="wpctg_settings[terms_per_page]" value="<?php echo esc_attr( (string) $settings['terms_per_page'] ); ?>" min="1" max="100" class="small-text" />
					<p class="description"><?php esc_html_e( 'Number of glossary terms displayed per page in the A-Z index.', 'wpcalibrate-term-glossary' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Live Search', 'wpcalibrate-term-glossary' ); ?></th>
				<td>
					<label for="wpctg_enable_live_search">
						<input type="checkbox" id="wpctg_enable_live_search" name="wpctg_settings[enable_live_search]" value="1" <?php checked( $settings['enable_live_search'] ); ?> />
						<?php esc_html_e( 'Enable instant client-side / REST search filter', 'wpcalibrate-term-glossary' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Provides an instant search input above the index, with non-JavaScript fallback.', 'wpcalibrate-term-glossary' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Alphabet Navigation Bar', 'wpcalibrate-term-glossary' ); ?></th>
				<td>
					<label for="wpctg_enable_alphabet_filter">
						<input type="checkbox" id="wpctg_enable_alphabet_filter" name="wpctg_settings[enable_alphabet_filter]" value="1" <?php checked( $settings['enable_alphabet_filter'] ); ?> />
						<?php esc_html_e( 'Display A-Z navigation bar', 'wpcalibrate-term-glossary' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Allows visitors to quickly jump between letters (handles # for numerals and non-Latin characters).', 'wpcalibrate-term-glossary' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Category Filter', 'wpcalibrate-term-glossary' ); ?></th>
				<td>
					<label for="wpctg_enable_category_filter">
						<input type="checkbox" id="wpctg_enable_category_filter" name="wpctg_settings[enable_category_filter]" value="1" <?php checked( $settings['enable_category_filter'] ); ?> />
						<?php esc_html_e( 'Display category dropdown filter on the index', 'wpcalibrate-term-glossary' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="wpctg_default_sort_order"><?php esc_html_e( 'Default Alphabetical Order', 'wpcalibrate-term-glossary' ); ?></label></th>
				<td>
					<select id="wpctg_default_sort_order" name="wpctg_settings[default_sort_order]">
						<option value="asc" <?php selected( 'asc', $settings['default_sort_order'] ); ?>><?php esc_html_e( 'A to Z (Ascending)', 'wpcalibrate-term-glossary' ); ?></option>
						<option value="desc" <?php selected( 'desc', $settings['default_sort_order'] ); ?>><?php esc_html_e( 'Z to A (Descending)', 'wpcalibrate-term-glossary' ); ?></option>
					</select>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Index Shortcode & Block', 'wpcalibrate-term-glossary' ); ?></th>
				<td>
					<p>
						<?php esc_html_e( 'Insert the glossary index into any page or post using the Gutenberg block or shortcode:', 'wpcalibrate-term-glossary' ); ?>
					</p>
					<code>[wpcalibrate_glossary]</code>
					<p class="description" style="margin-top:8px;">
						<?php esc_html_e( 'Available shortcode attributes: category="slug", search="true|false", alphabet="true|false", per_page="20", order="asc|desc".', 'wpcalibrate-term-glossary' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Tooltip Tab.
	 *
	 * @param array<string, mixed> $settings Current settings.
	 * @return void
	 */
	private static function render_tooltip_tab( array $settings ): void {
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Tooltips', 'wpcalibrate-term-glossary' ); ?></th>
				<td>
					<label for="wpctg_enable_tooltips">
						<input type="checkbox" id="wpctg_enable_tooltips" name="wpctg_settings[enable_tooltips]" value="1" <?php checked( $settings['enable_tooltips'] ); ?> />
						<strong><?php esc_html_e( 'Enable accessible interactive tooltips', 'wpcalibrate-term-glossary' ); ?></strong>
					</label>
					<p class="description"><?php esc_html_e( 'Accessible via hover, keyboard focus (Tab), and mobile/touch tap.', 'wpcalibrate-term-glossary' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Read More Link', 'wpcalibrate-term-glossary' ); ?></th>
				<td>
					<label for="wpctg_show_read_more">
						<input type="checkbox" id="wpctg_show_read_more" name="wpctg_settings[show_read_more]" value="1" <?php checked( $settings['show_read_more'] ); ?> />
						<?php esc_html_e( 'Include "Read full definition" link inside the tooltip', 'wpcalibrate-term-glossary' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="wpctg_read_more_text"><?php esc_html_e( 'Read More Text', 'wpcalibrate-term-glossary' ); ?></label></th>
				<td>
					<input type="text" id="wpctg_read_more_text" name="wpctg_settings[read_more_text]" value="<?php echo esc_attr( $settings['read_more_text'] ); ?>" class="regular-text" />
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="wpctg_tooltip_theme"><?php esc_html_e( 'Tooltip Theme', 'wpcalibrate-term-glossary' ); ?></label></th>
				<td>
					<select id="wpctg_tooltip_theme" name="wpctg_settings[tooltip_theme]">
						<option value="light" <?php selected( 'light', $settings['tooltip_theme'] ); ?>><?php esc_html_e( 'Light (Clean white card with subtle shadow)', 'wpcalibrate-term-glossary' ); ?></option>
						<option value="dark" <?php selected( 'dark', $settings['tooltip_theme'] ); ?>><?php esc_html_e( 'Dark (Sleek slate dark background)', 'wpcalibrate-term-glossary' ); ?></option>
					</select>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * SEO & Schema Tab.
	 *
	 * @param array<string, mixed> $settings Current settings.
	 * @return void
	 */
	private static function render_seo_tab( array $settings ): void {
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Single Term Schema', 'wpcalibrate-term-glossary' ); ?></th>
				<td>
					<label for="wpctg_enable_defined_term_schema">
						<input type="checkbox" id="wpctg_enable_defined_term_schema" name="wpctg_settings[enable_defined_term_schema]" value="1" <?php checked( $settings['enable_defined_term_schema'] ); ?> />
						<strong><?php esc_html_e( 'Generate Schema.org DefinedTerm JSON-LD on single glossary pages', 'wpcalibrate-term-glossary' ); ?></strong>
					</label>
					<p class="description">
						<?php esc_html_e( 'Injects clean, semantic JSON-LD structured data for search engine comprehension.', 'wpcalibrate-term-glossary' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Glossary Index Schema', 'wpcalibrate-term-glossary' ); ?></th>
				<td>
					<label for="wpctg_enable_defined_term_set_schema">
						<input type="checkbox" id="wpctg_enable_defined_term_set_schema" name="wpctg_settings[enable_defined_term_set_schema]" value="1" <?php checked( $settings['enable_defined_term_set_schema'] ); ?> />
						<strong><?php esc_html_e( 'Generate Schema.org DefinedTermSet JSON-LD on glossary index pages', 'wpcalibrate-term-glossary' ); ?></strong>
					</label>
					<p class="description">
						<?php esc_html_e( 'Groups published terms into an indexed Schema term collection for search engines.', 'wpcalibrate-term-glossary' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Advanced Tab.
	 *
	 * @param array<string, mixed> $settings Current settings.
	 * @return void
	 */
	private static function render_advanced_tab( array $settings ): void {
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Glossary Cache', 'wpcalibrate-term-glossary' ); ?></th>
				<td>
					<p>
						<?php esc_html_e( 'Glossary matching patterns and term indices are automatically cached in WordPress transients for maximum performance. Caches automatically invalidate whenever terms, categories, or settings are updated.', 'wpcalibrate-term-glossary' ); ?>
					</p>
					<button type="button" id="wpctg-clear-cache-btn" class="button button-secondary">
						<?php esc_html_e( 'Purge Glossary Cache Now', 'wpcalibrate-term-glossary' ); ?>
					</button>
					<span id="wpctg-cache-status" style="margin-left:8px; font-weight:600;"></span>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Debug Logging', 'wpcalibrate-term-glossary' ); ?></th>
				<td>
					<label for="wpctg_enable_debug_log">
						<input type="checkbox" id="wpctg_enable_debug_log" name="wpctg_settings[enable_debug_log]" value="1" <?php checked( $settings['enable_debug_log'] ); ?> />
						<?php esc_html_e( 'Enable debug logging to PHP error log', 'wpcalibrate-term-glossary' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Logs term detection metrics and cache events. Sensitive data (passwords, tokens, nonces) is strictly redacted.', 'wpcalibrate-term-glossary' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Data Retention on Uninstall', 'wpcalibrate-term-glossary' ); ?></th>
				<td>
					<label for="wpctg_delete_data_on_uninstall" style="color:#b91c1c;">
						<input type="checkbox" id="wpctg_delete_data_on_uninstall" name="wpctg_settings[delete_data_on_uninstall]" value="1" <?php checked( $settings['delete_data_on_uninstall'] ); ?> />
						<strong><?php esc_html_e( 'Delete all WPCalibrate Term Glossary data when the plugin is deleted', 'wpcalibrate-term-glossary' ); ?></strong>
					</label>
					<p class="description">
						<?php esc_html_e( 'Warning: When checked, completely deleting the plugin will permanently purge all glossary terms, categories, and settings from the database. Default: Off (preserves your terms).', 'wpcalibrate-term-glossary' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Import & Export Tab.
	 *
	 * @return void
	 */
	private static function render_import_export_tab(): void {
		// Nonces and forms for CSV export and import.
		?>
		<div style="display:grid; grid-template-columns: 1fr 1fr; gap:32px;">
			<div class="wpctg-card-box" style="background:#fff; border:1px solid #cbd5e1; border-radius:8px; padding:20px;">
				<h3 style="margin-top:0;"><?php esc_html_e( 'Export Glossary Terms (CSV)', 'wpcalibrate-term-glossary' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'Export all published and draft glossary terms, definitions, categories, and aliases into a standard UTF-8 CSV file.', 'wpcalibrate-term-glossary' ); ?>
				</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:16px;">
					<?php wp_nonce_field( 'wpctg_export_csv', 'wpctg_export_nonce' ); ?>
					<input type="hidden" name="action" value="wpctg_export_csv" />
					<button type="submit" class="button button-primary">
						<span class="dashicons dashicons-download" style="margin-top:4px;"></span>
						<?php esc_html_e( 'Export All Terms to CSV', 'wpcalibrate-term-glossary' ); ?>
					</button>
				</form>
			</div>

			<div class="wpctg-card-box" style="background:#fff; border:1px solid #cbd5e1; border-radius:8px; padding:20px;">
				<h3 style="margin-top:0;"><?php esc_html_e( 'Import Glossary Terms (CSV)', 'wpcalibrate-term-glossary' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'Upload a CSV file containing glossary terms. You can preview validation errors before committing changes.', 'wpcalibrate-term-glossary' ); ?>
				</p>
				<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:16px;">
					<?php wp_nonce_field( 'wpctg_import_csv', 'wpctg_import_nonce' ); ?>
					<input type="hidden" name="action" value="wpctg_import_csv" />
					<div style="margin-bottom:12px;">
						<input type="file" name="wpctg_csv_file" accept=".csv,text/csv" required />
					</div>
					<div style="margin-bottom:12px;">
						<label>
							<input type="checkbox" name="wpctg_update_existing" value="1" checked />
							<?php esc_html_e( 'Update existing terms if matching by name', 'wpcalibrate-term-glossary' ); ?>
						</label>
					</div>
					<div style="margin-bottom:16px;">
						<label>
							<input type="checkbox" name="wpctg_dry_run" value="1" />
							<strong><?php esc_html_e( 'Dry run / Validate only (Do not save)', 'wpcalibrate-term-glossary' ); ?></strong>
						</label>
					</div>
					<button type="submit" class="button button-secondary">
						<span class="dashicons dashicons-upload" style="margin-top:4px;"></span>
						<?php esc_html_e( 'Validate & Import CSV', 'wpcalibrate-term-glossary' ); ?>
					</button>
				</form>
			</div>
		</div>

		<div style="margin-top:24px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:16px;">
			<h4 style="margin-top:0;"><?php esc_html_e( 'CSV Format Specification', 'wpcalibrate-term-glossary' ); ?></h4>
			<p><?php esc_html_e( 'The CSV must contain a header row with the following column headers:', 'wpcalibrate-term-glossary' ); ?></p>
			<code>term, short_definition, full_definition, aliases, categories, pronunciation, source_url, auto_link, status</code>
			<p class="description" style="margin-top:8px;">
				<?php esc_html_e( 'Multiple aliases and categories can be separated by semicolons (;) or commas (,).', 'wpcalibrate-term-glossary' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Contact & Support Tab.
	 *
	 * @return void
	 */
	private static function render_contact_tab(): void {
		?>
		<div class="wpctg-contact-card" style="max-width:680px; background:#fff; border:1px solid #cbd5e1; border-radius:8px; padding:24px;">
			<div style="display:flex; align-items:center; gap:16px; margin-bottom:16px; border-bottom:1px solid #e2e8f0; padding-bottom:16px;">
				<img src="<?php echo esc_url( WPCTG_URL . 'assets/branding/icon-dark.png' ); ?>" alt="WPCalibrate" style="width:48px; height:48px; object-fit:contain;" />
				<div>
					<h3 style="margin:0; font-size:18px;"><?php esc_html_e( 'WPCalibrate Support & Inquiries', 'wpcalibrate-term-glossary' ); ?></h3>
					<p style="margin:4px 0 0; color:#64748b;"><?php esc_html_e( 'Engineered for performance, reliability, and precision.', 'wpcalibrate-term-glossary' ); ?></p>
				</div>
			</div>

			<table class="form-table" role="presentation" style="margin-top:0;">
				<tr>
					<th scope="row" style="width:140px;"><?php esc_html_e( 'Official Website', 'wpcalibrate-term-glossary' ); ?></th>
					<td><a href="https://wpcalibrate.com" target="_blank" rel="noopener noreferrer">https://wpcalibrate.com</a></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Marketplace', 'wpcalibrate-term-glossary' ); ?></th>
					<td><a href="https://marketplace.wpcalibrate.com/" target="_blank" rel="noopener noreferrer">https://marketplace.wpcalibrate.com/</a></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Support Email', 'wpcalibrate-term-glossary' ); ?></th>
					<td><a href="mailto:support@wpcalibrate.com">support@wpcalibrate.com</a></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Phone / WhatsApp', 'wpcalibrate-term-glossary' ); ?></th>
					<td><a href="https://wa.me/447474795976" target="_blank" rel="noopener noreferrer">+44 7474 795976</a></td>
				</tr>
			</table>
		</div>
		<?php
	}
}
