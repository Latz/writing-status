<?php
/**
 * Uninstall Script
 *
 * Fired when the plugin is uninstalled.
 * Cleans up all plugin data from the database.
 *
 * @package WritingStatus
 * @since 1.0.0
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete all post meta created by this plugin
delete_post_meta_by_key('_writing_complete');
delete_post_meta_by_key('_writing_due_date');
delete_post_meta_by_key('_writing_priority');

// Delete cached overdue-drafts count
delete_transient('writing_status_overdue_count');
