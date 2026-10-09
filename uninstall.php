<?php
/**
 * Plugin Uninstall Handler.
 *
 * Triggered only when the plugin is completely deleted from WordPress.
 *
 * @package WPCalibrate\TermGlossary
 */

declare( strict_types=1 );

// Exit if accessed directly or not uninstalling.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check data retention preference. Default is OFF (preserve user data).
$wpctg_settings = get_option( 'wpctg_settings', [] );
$wpctg_delete_data = is_array( $wpctg_settings ) && ! empty( $wpctg_settings['delete_data_on_uninstall'] );

if ( ! $wpctg_delete_data ) {
	// Preserve terms, taxonomies, and settings.
	return;
}

// 1. Delete all glossary term posts.
$wpctg_posts = get_posts(
	[
		'post_type'      => 'wpctg_term',
		'post_status'    => [ 'any', 'trash', 'auto-draft' ],
		'numberposts'    => -1,
		'fields'         => 'ids',
	]
);

if ( ! empty( $wpctg_posts ) ) {
	foreach ( $wpctg_posts as $wpctg_post_id ) {
		wp_delete_post( (int) $wpctg_post_id, true );
	}
}

// 2. Delete all glossary category terms.
$wpctg_terms = get_terms(
	[
		'taxonomy'   => 'wpctg_category',
		'hide_empty' => false,
		'fields'     => 'ids',
	]
);

if ( ! empty( $wpctg_terms ) && ! is_wp_error( $wpctg_terms ) ) {
	foreach ( $wpctg_terms as $wpctg_term_id ) {
		wp_delete_term( (int) $wpctg_term_id, 'wpctg_category' );
	}
}

// 3. Remove plugin capabilities from roles.
$wpctg_caps = [
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

$wpctg_roles = [ 'administrator', 'editor' ];
foreach ( $wpctg_roles as $wpctg_role_slug ) {
	$wpctg_role = get_role( $wpctg_role_slug );
	if ( $wpctg_role ) {
		foreach ( $wpctg_caps as $wpctg_cap ) {
			$wpctg_role->remove_cap( $wpctg_cap );
		}
	}
}

// 4. Delete plugin options strictly belonging to this plugin.
delete_option( 'wpctg_settings' );
delete_option( 'wpctg_version' );

// 5. Delete transient caches.
delete_transient( 'wpctg_terms_matching_index' );
delete_transient( 'wpctg_all_published_terms' );
delete_transient( 'wpctg_import_notice' );

if ( function_exists( 'wp_cache_delete_group' ) ) {
	wp_cache_delete_group( 'wpctg_glossary' );
}
