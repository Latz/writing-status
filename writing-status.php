<?php
/**
 * Plugin Name: Writing Status
 * Plugin URI: https://github.com/yourusername/writing-status
 * Description: Mark draft posts by completion status (complete/incomplete) with priority levels
 * Version: 1.9.0
 * Author: Latz
 * Author URI: https://elektroelch.de
 * * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: writing-status
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WRITING_STATUS_VERSION', '1.9.0');

require_once plugin_dir_path(__FILE__) . 'class-writing-status-renderer.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-writing-status-column.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-writing-status-meta-box.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-writing-status-filters.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-writing-status-dashboard.php';

/**
 * Main plugin class — coordinates sub-classes and owns assets, meta registration,
 * bulk edit saving, overdue notice, and plugin activation.
 *
 * @since 1.0.0
 */
class WritingStatus extends WritingStatusRenderer {

    private WritingStatusColumn    $column;
    private WritingStatusMetaBox   $metaBox;
    private WritingStatusFilters   $filters;
    private WritingStatusDashboard $dashboard;

    public function __construct() {
        $this->column    = new WritingStatusColumn();
        $this->metaBox   = new WritingStatusMetaBox();
        $this->filters   = new WritingStatusFilters();
        $this->dashboard = new WritingStatusDashboard();

        add_action('admin_enqueue_scripts',       array($this, 'enqueueAdminStyles'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueueBlockEditorAssets'));
        add_action('init',                        array($this, 'registerMetaField'));
        add_action('bulk_edit_custom_box',        array($this, 'renderBulkEditBox'), 10, 2);
        add_action('quick_edit_custom_box',       array($this, 'renderQuickEditBox'), 10, 2);
        add_action('save_post',                   array($this, 'saveBulkEdit'), 20);
        add_action('admin_notices',               array($this, 'showOverdueNotice'));
    }

    public function enqueueAdminStyles($hook) {
        if ($hook !== 'edit.php' && $hook !== 'post.php' && $hook !== 'post-new.php' && $hook !== 'index.php') {
            return;
        }

        wp_enqueue_style(
            'writing-status',
            plugin_dir_url(__FILE__) . 'writing-status.css',
            array(),
            WRITING_STATUS_VERSION
        );

        if ($hook === 'edit.php') {
            wp_enqueue_script(
                'writing-status',
                plugin_dir_url(__FILE__) . 'writing-status.js',
                array('inline-edit-post'),
                WRITING_STATUS_VERSION,
                true
            );
        }
    }

    public function enqueueBlockEditorAssets() {
        wp_enqueue_script(
            'writing-status-gutenberg',
            plugin_dir_url(__FILE__) . 'writing-status.js',
            array('wp-plugins', 'wp-edit-post', 'wp-element', 'wp-data', 'wp-components', 'wp-i18n'),
            WRITING_STATUS_VERSION,
            true
        );
    }

    public function registerMetaField() {
        register_post_meta('post', '_writing_complete', array(
            'type'              => 'string',
            'description'       => __('Draft completion status', 'writing-status'),
            'single'            => true,
            'show_in_rest'      => true,
            'default'           => 'no',
            'sanitize_callback' => function($value) {
                return ($value === 'yes') ? 'yes' : 'no';
            },
            'auth_callback'     => function() {
                return current_user_can('edit_posts');
            },
        ));

        register_post_meta('post', '_writing_due_date', array(
            'type'              => 'string',
            'description'       => __('Draft due date', 'writing-status'),
            'single'            => true,
            'show_in_rest'      => true,
            'default'           => '',
            'sanitize_callback' => function($value) {
                if (empty($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    return $value;
                }
                return '';
            },
            'auth_callback'     => function() {
                return current_user_can('edit_posts');
            },
        ));

        register_post_meta('post', '_writing_priority', array(
            'type'              => 'string',
            'description'       => __('Draft priority level', 'writing-status'),
            'single'            => true,
            'show_in_rest'      => true,
            'default'           => 'none',
            'sanitize_callback' => array($this, 'sanitizePriorityValue'),
            'auth_callback'     => function() {
                return current_user_can('edit_posts');
            },
        ));
    }

    public function sanitizePriorityValue($value) {
        if (in_array($value, $this->getValidPriorities(), true)) {
            return $value;
        }
        return 'none';
    }

    private function isValidBulkEditRequest(int $post_id): bool {
        if (!isset($_REQUEST['_writing_status_bulk_nonce'])) {
            return false;
        }
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['_writing_status_bulk_nonce'])), 'writing_status_bulk_edit')) {
            return false;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return false;
        }
        return (bool) current_user_can('edit_post', $post_id);
    }

    public function saveBulkEdit($post_id) {
        if (!$this->isValidBulkEditRequest($post_id)) {
            return;
        }

        if (isset($_REQUEST['writing_complete_bulk']) && $_REQUEST['writing_complete_bulk'] !== '') {
            $value = sanitize_text_field(wp_unslash($_REQUEST['writing_complete_bulk']));
            update_post_meta($post_id, '_writing_complete', $value === 'yes' ? 'yes' : 'no');
        }

        if (isset($_REQUEST['writing_priority_bulk']) && $_REQUEST['writing_priority_bulk'] !== '') {
            $priority = sanitize_text_field(wp_unslash($_REQUEST['writing_priority_bulk']));
            if (in_array($priority, $this->getValidPriorities(), true)) {
                update_post_meta($post_id, '_writing_priority', $priority);
            }
        }

        if (!empty($_REQUEST['writing_due_date_bulk'])) {
            $due_date = sanitize_text_field(wp_unslash($_REQUEST['writing_due_date_bulk']));
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $due_date)) {
                update_post_meta($post_id, '_writing_due_date', $due_date);
            }
        }

        delete_transient('writing_status_overdue_count');
    }

    public function showOverdueNotice() {
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->id, array('edit-post', 'dashboard'), true)) {
            return;
        }

        $count = get_transient('writing_status_overdue_count');
        if (false === $count) {
            $count = $this->countOverdueDrafts();
            set_transient('writing_status_overdue_count', $count, HOUR_IN_SECONDS);
        }

        if ($count < 1) {
            return;
        }

        $url = admin_url('edit.php?post_status=draft&post_type=post');
        printf(
            '<div class="notice notice-warning"><p>%s</p></div>',
            wp_kses(
                sprintf(
                    _n(
                        'Writing Status: <strong>%1$d incomplete draft is overdue.</strong> <a href="%2$s">View drafts &rarr;</a>',
                        'Writing Status: <strong>%1$d incomplete drafts are overdue.</strong> <a href="%2$s">View drafts &rarr;</a>',
                        $count,
                        'writing-status'
                    ),
                    $count,
                    esc_url($url)
                ),
                array(
                    'strong' => array(),
                    'a'      => array('href' => array()),
                )
            )
        );
    }

    public static function migrateMetaKeys() {
        global $wpdb;

        $migrations = array(
            '_draft_complete' => '_writing_complete',
            '_draft_due_date' => '_writing_due_date',
            '_draft_priority' => '_writing_priority',
        );

        foreach ($migrations as $old_key => $new_key) {
            $wpdb->update(
                $wpdb->postmeta,
                array('meta_key' => $new_key),
                array('meta_key' => $old_key),
                array('%s'),
                array('%s')
            );
        }
    }
}

register_activation_hook(__FILE__, array('WritingStatus', 'migrateMetaKeys'));

$writing_status = new WritingStatus();
