<?php
/**
 * Role and capability definitions for glossary management.
 *
 * @package WPCalibrate\TermGlossary
 */

declare( strict_types=1 );

namespace WPCalibrate\TermGlossary\Glossary;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Capabilities
 */
class Capabilities {

	/**
	 * All custom capabilities introduced by the plugin.
	 *
	 * @return string[]
	 */
	public static function get_all(): array {
		return [
			'edit_wpctg_term',
			'read_wpctg_term',
			'delete_wpctg_term',
			'edit_wpctg_terms',
			'edit_others_wpctg_terms',
			'publish_wpctg_terms',
			'read_private_wpctg_terms',
			'delete_wpctg_terms',
			'delete_private_wpctg_terms',
			'delete_published_wpctg_terms',
			'delete_others_wpctg_terms',
			'edit_private_wpctg_terms',
			'edit_published_wpctg_terms',
			'manage_wpctg_categories',
		];
	}

	/**
	 * Capabilities granted to Editors (content management only).
	 *
	 * @return string[]
	 */
	public static function get_editor_caps(): array {
		return [
			'edit_wpctg_term',
			'read_wpctg_term',
			'delete_wpctg_term',
			'edit_wpctg_terms',
			'edit_others_wpctg_terms',
			'publish_wpctg_terms',
			'read_private_wpctg_terms',
			'delete_wpctg_terms',
			'delete_published_wpctg_terms',
			'delete_others_wpctg_terms',
			'edit_published_wpctg_terms',
			'manage_wpctg_categories',
		];
	}

	/**
	 * Ensure administrator and editor have capabilities if missing.
	 *
	 * Self-heals environments where plugin was deployed via FTP or direct file upload
	 * without triggering the standard register_activation_hook.
	 *
	 * @return void
	 */
	public static function ensure_caps(): void {
		$admin_role = get_role( 'administrator' );
		if ( $admin_role && ! $admin_role->has_cap( 'edit_wpctg_terms' ) ) {
			self::register_caps();
		}
	}

	/**
	 * Grant capabilities on plugin activation.
	 *
	 * @return void
	 */
	public static function register_caps(): void {
		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			foreach ( self::get_all() as $cap ) {
				$admin_role->add_cap( $cap );
			}
		}

		$editor_role = get_role( 'editor' );
		if ( $editor_role ) {
			foreach ( self::get_editor_caps() as $cap ) {
				$editor_role->add_cap( $cap );
			}
		}
	}

	/**
	 * Remove custom capabilities on uninstall.
	 *
	 * @return void
	 */
	public static function unregister_caps(): void {
		$roles = [ 'administrator', 'editor' ];
		$caps  = self::get_all();

		foreach ( $roles as $role_slug ) {
			$role = get_role( $role_slug );
			if ( $role ) {
				foreach ( $caps as $cap ) {
					$role->remove_cap( $cap );
				}
			}
		}
	}
}
