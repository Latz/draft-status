<?php
/**
 * Uninstall Script
 *
 * Fired when the plugin is uninstalled.
 * Cleans up all plugin data from the database.
 *
 * @package DraftStatusIndexer
 * @since 1.0.0
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete all post meta for draft completion status
delete_post_meta_by_key('_draft_complete');

// Optional: Clear any cached data
wp_cache_flush();
