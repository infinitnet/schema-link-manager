<?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.ShortPrefixPassed -- Short prefix used in legacy code for backward compatibility.
/**
 * Uninstall Script for Schema Link Manager
 *
 * This file is executed when the plugin is uninstalled (deleted) from the WordPress admin.
 * It removes all plugin data including custom post meta fields from all posts.
 *
 * IMPORTANT: This file should NOT be included or executed directly.
 * It is automatically called by WordPress when the plugin is uninstalled.
 *
 * @package Schema_Link_Manager
 * @since 1.1.8
 */

// If uninstall not called from WordPress, exit immediately.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Fires before plugin uninstall cleanup.
 *
 * Allows other code to hook into the uninstall process before data is removed.
 * Useful for extensions or integrations that need to perform their own cleanup.
 *
 * @since 1.1.8
 */
do_action( 'schema_link_manager_before_uninstall' );

// Delete all schema link meta entries from all posts.
delete_post_meta_by_key( 'schema_significant_links' );
delete_post_meta_by_key( 'schema_related_links' );

/**
 * Fires after plugin uninstall cleanup.
 *
 * Allows other code to hook into the uninstall process after data is removed.
 * All plugin data has been cleaned up at this point.
 *
 * @since 1.1.8
 */
do_action( 'schema_link_manager_after_uninstall' );
