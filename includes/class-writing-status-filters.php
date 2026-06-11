<?php
/**
 * Writing Status Filters
 *
 * Adds completion and priority filter dropdowns to the posts list
 * and applies the selected filters to the query.
 *
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WritingStatusFilters extends WritingStatusRenderer {

    public function __construct() {
        add_action('restrict_manage_posts', array($this, 'addCompletionFilterDropdown'));
        add_filter('parse_query',           array($this, 'filterPostsByCompletion'));
    }

    public function addCompletionFilterDropdown($post_type) {
        if ($post_type !== 'post') {
            return;
        }

        $selected = isset($_GET['writing_completion_filter']) ? sanitize_text_field(wp_unslash($_GET['writing_completion_filter'])) : '';
        ?>
        <select name="writing_completion_filter" id="writing_completion_filter" aria-label="<?php esc_attr_e('Filter posts by completion status', 'writing-status'); ?>">
            <option value=""><?php esc_html_e('All Completion Status', 'writing-status'); ?></option>
            <option value="complete" <?php selected($selected, 'complete'); ?>><?php esc_html_e('Complete', 'writing-status'); ?></option>
            <option value="incomplete" <?php selected($selected, 'incomplete'); ?>><?php esc_html_e('Incomplete', 'writing-status'); ?></option>
        </select>

        <?php
        $priority_selected = isset($_GET['writing_priority_filter']) ? sanitize_text_field(wp_unslash($_GET['writing_priority_filter'])) : '';
        ?>
        <select name="writing_priority_filter">
            <option value=""><?php esc_html_e('All Priorities', 'writing-status'); ?></option>
            <option value="urgent" <?php selected($priority_selected, 'urgent'); ?>><?php esc_html_e('Urgent', 'writing-status'); ?></option>
            <option value="high" <?php selected($priority_selected, 'high'); ?>><?php esc_html_e('High', 'writing-status'); ?></option>
            <option value="medium" <?php selected($priority_selected, 'medium'); ?>><?php esc_html_e('Medium', 'writing-status'); ?></option>
            <option value="low" <?php selected($priority_selected, 'low'); ?>><?php esc_html_e('Low', 'writing-status'); ?></option>
            <option value="none" <?php selected($priority_selected, 'none'); ?>><?php esc_html_e('None', 'writing-status'); ?></option>
        </select>
        <?php
    }

    public function filterPostsByCompletion($query) {
        global $pagenow;

        if (!is_admin() || $pagenow !== 'edit.php') {
            return;
        }

        $has_completion_filter = isset($_GET['writing_completion_filter']);
        $has_priority_filter   = isset($_GET['writing_priority_filter']);

        if (!$has_completion_filter && !$has_priority_filter) {
            return;
        }

        $meta_query = $query->get('meta_query');
        if (empty($meta_query) || !is_array($meta_query)) {
            $meta_query = array();
        }

        $filter_meta_query = array('relation' => 'AND');

        if ($has_completion_filter) {
            $this->applyCompletionFilter($query, $filter_meta_query);
        }

        if ($has_priority_filter) {
            $this->applyPriorityFilter($query, $filter_meta_query, $has_completion_filter);
        }

        if (count($filter_meta_query) > 1) {
            $meta_query[] = $filter_meta_query;
            $query->set('meta_query', $meta_query);
        }
    }

    private function applyCompletionFilter($query, &$filter_meta_query) {
        $filter = sanitize_text_field(wp_unslash($_GET['writing_completion_filter']));

        if ($filter === 'complete') {
            $filter_meta_query[] = array(
                'key'     => '_writing_complete',
                'value'   => 'yes',
                'compare' => '=',
            );
            $query->set('post_status', 'draft');
        } elseif ($filter === 'incomplete') {
            $filter_meta_query[] = array(
                'relation' => 'OR',
                array(
                    'key'     => '_writing_complete',
                    'value'   => 'no',
                    'compare' => '=',
                ),
                array(
                    'key'     => '_writing_complete',
                    'compare' => 'NOT EXISTS',
                ),
            );
            $query->set('post_status', 'draft');
        }
    }

    private function applyPriorityFilter($query, &$filter_meta_query, $has_completion_filter) {
        $priority_filter = sanitize_text_field(wp_unslash($_GET['writing_priority_filter']));

        if (in_array($priority_filter, $this->getValidPriorities(), true)) {
            $filter_meta_query[] = array(
                'key'     => '_writing_priority',
                'value'   => $priority_filter,
                'compare' => '=',
            );
            if (!$has_completion_filter) {
                $query->set('post_status', 'draft');
            }
        }
    }
}
