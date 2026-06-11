<?php
/**
 * Writing Status Dashboard Widget
 *
 * Registers and renders the Draft Writing Status dashboard widget,
 * including the priority-based orderby filter for widget queries.
 *
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WritingStatusDashboard extends WritingStatusRenderer {

    public function __construct() {
        add_action('wp_dashboard_setup', array($this, 'addDashboardWidget'));
    }

    public function addDashboardWidget() {
        wp_add_dashboard_widget(
            'writing_status_widget',
            __('Draft Writing Status', 'writing-status'),
            array($this, 'renderDashboardWidget')
        );
    }

    public function renderDashboardWidget() {
        list($incomplete_query, $complete_query) = $this->getDashboardQueries();
        ?>
        <div class="writing-status-widget">
            <?php $this->renderDashboardIncompletePosts($incomplete_query); ?>
            <?php $this->renderDashboardCompletePosts($complete_query); ?>

            <?php if (!$incomplete_query->have_posts() && !$complete_query->have_posts()): ?>
                <output><?php esc_html_e('No drafts found. Start writing!', 'writing-status'); ?></output>
            <?php endif; ?>

            <p class="writing-status-link">
                <a href="<?php echo esc_url(admin_url('edit.php?post_status=draft&post_type=post')); ?>" aria-label="<?php esc_attr_e('View all draft posts in the posts list', 'writing-status'); ?>">
                    <?php esc_html_e('View All Drafts →', 'writing-status'); ?>
                </a>
            </p>
        </div>
        <?php
        wp_reset_postdata();
    }


}
