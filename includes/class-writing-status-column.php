<?php
/**
 * Writing Status Column
 *
 * Manages the Writing Status column in the posts list table,
 * including display, sorting, and custom orderby logic.
 *
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WritingStatusColumn extends WritingStatusRenderer {

    public function __construct() {
        add_filter('manage_posts_columns',           array($this, 'addCompletionColumn'));
        add_action('manage_posts_custom_column',     array($this, 'displayCompletionColumn'), 10, 2);
        add_filter('manage_edit-post_sortable_columns', array($this, 'makeCompletionSortable'));
        add_action('pre_get_posts',                  array($this, 'sortByCompletion'));
    }

    public function addCompletionColumn($columns) {
        $columns['writing_completion'] = __('Writing Status', 'writing-status');
        return $columns;
    }

    public function displayCompletionColumn($column, $post_id) {
        if ($column !== 'writing_completion') {
            return;
        }

        if (get_post_status($post_id) === 'publish') {
            printf(
                '<span class="writing-status-indicator writing-status-published" aria-label="%s">● %s</span>',
                esc_attr__('Post status: Published', 'writing-status'),
                esc_html__('Published', 'writing-status')
            );
        } else {
            $is_complete = get_post_meta($post_id, '_writing_complete', true);
            $due_date    = get_post_meta($post_id, '_writing_due_date', true);
            $priority    = get_post_meta($post_id, '_writing_priority', true);

            $this->renderCompletionStatus($is_complete);
            $this->renderDueDate($due_date);
            $this->renderPriorityBadge($priority);
        }

        printf(
            '<span class="hidden writing-status-qe-data" id="writing-status-data-%1$d"'
            . ' data-complete="%2$s" data-due-date="%3$s" data-priority="%4$s"></span>',
            $post_id,
            esc_attr(get_post_meta($post_id, '_writing_complete', true) ?: 'no'),
            esc_attr(get_post_meta($post_id, '_writing_due_date', true) ?: ''),
            esc_attr(get_post_meta($post_id, '_writing_priority', true) ?: 'none')
        );
    }

    public function makeCompletionSortable($columns) {
        $columns['writing_completion'] = 'writing_completion';
        return $columns;
    }

    public function sortByCompletion($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        if ($query->get('orderby') === 'writing_completion') {
            $meta_query = $query->get('meta_query');
            if (empty($meta_query) || !is_array($meta_query)) {
                $meta_query = array();
            }

            $meta_query['priority_clause'] = array(
                'key'     => '_writing_priority',
                'compare' => 'EXISTS',
            );
            $meta_query['completion_clause'] = array(
                'key'     => '_writing_complete',
                'compare' => 'EXISTS',
            );

            $query->set('meta_query', $meta_query);

            $order = $query->get('order', 'ASC');
            $query->set('orderby', array(
                'priority_clause'   => $order,
                'completion_clause' => $order,
            ));

            add_filter('posts_orderby', array($this, 'customPriorityOrderby'), 10, 2);
        }
    }

    public function customPriorityOrderby($orderby, $query) {
        global $wpdb;

        if (!is_admin() || !$query->is_main_query()) {
            return $orderby;
        }

        $order = strtoupper($query->get('order', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        // Numbers map priority strings to a sort order SQL can understand.
        // Direct string sorting would be alphabetical (high < low < medium < urgent),
        // which is meaningless. The CASE translates each value to an integer so
        // ORDER BY ASC puts the most important priority first.
        $orderby = "
            CASE (SELECT meta_value FROM {$wpdb->postmeta} WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID AND meta_key = '_writing_priority' LIMIT 1)
                WHEN 'urgent' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
                WHEN 'none' THEN 5
                ELSE 6
            END {$order},
            (SELECT meta_value FROM {$wpdb->postmeta} WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID AND meta_key = '_writing_complete' LIMIT 1) {$order}
        ";

        remove_filter('posts_orderby', array($this, 'customPriorityOrderby'), 10);

        return $orderby;
    }
}
