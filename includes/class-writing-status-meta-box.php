<?php
/**
 * Writing Status Meta Box
 *
 * Registers and renders the Completion Status meta box in the Classic Editor,
 * and handles saving post meta from the meta box form.
 *
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WritingStatusMetaBox extends WritingStatusRenderer {

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'addCompletionMetaBox'));
        add_action('save_post',      array($this, 'saveCompletionStatus'));
    }

    public function addCompletionMetaBox() {
        add_meta_box(
            'writing_completion_box',
            __('Completion Status', 'writing-status'),
            array($this, 'renderCompletionMetaBox'),
            'post', 'side', 'default',
            array('__back_compat_meta_box' => true)
        );
    }

    public function renderCompletionMetaBox($post) {
        $post_status = get_post_status($post->ID);

        if ($post_status === 'publish') {
            ?>
            <p class="writing-status-metabox-published">
                <span class="writing-status-indicator writing-status-published">● <?php esc_html_e('Published', 'writing-status'); ?></span>
            </p>
            <p class="description">
                <?php esc_html_e('This post has been published.', 'writing-status'); ?>
            </p>
            <?php
            return;
        }

        wp_nonce_field('writing_completion_nonce', 'writing_completion_nonce_field');

        $is_complete = get_post_meta($post->ID, '_writing_complete', true);
        $due_date    = get_post_meta($post->ID, '_writing_due_date', true);
        ?>
        <input type="hidden" id="writing_complete_hidden" name="writing_complete" value="<?php echo esc_attr($is_complete === 'yes' ? 'yes' : 'no'); ?>">
        <p>
            <button type="button" id="writing_complete_button" class="button button-large draft-complete-toggle <?php echo esc_attr($is_complete === 'yes' ? 'is-complete' : 'is-incomplete'); ?>" aria-describedby="writing_complete_description" aria-pressed="<?php echo esc_attr($is_complete === 'yes' ? 'true' : 'false'); ?>" data-complete-text="<?php echo esc_attr__('Complete', 'writing-status'); ?>" data-incomplete-text="<?php echo esc_attr__('Incomplete', 'writing-status'); ?>">
                <span class="writing-status-icon"><?php echo $is_complete === 'yes' ? '✓' : '✗'; ?></span>
                <span class="writing-status-text"><?php echo $is_complete === 'yes' ? esc_html__('Complete', 'writing-status') : esc_html__('Incomplete', 'writing-status'); ?></span>
            </button>
        </p>
        <p class="description" id="writing_complete_description">
            <?php esc_html_e('Click to toggle the completion status of this draft. This helps you sort and track your writing progress.', 'writing-status'); ?>
        </p>

        <hr style="margin: 15px 0;">

        <p>
            <label for="writing_due_date">
                <strong><?php esc_html_e('Due Date', 'writing-status'); ?></strong>
            </label>
        </p>
        <p>
            <input type="date"
                   id="writing_due_date"
                   name="writing_due_date"
                   value="<?php echo esc_attr($due_date); ?>"
                   style="width: 100%;">
        </p>
        <p class="description">
            <?php esc_html_e('Set a target completion date for this draft.', 'writing-status'); ?>
        </p>

        <hr style="margin: 15px 0;">

        <p>
            <label for="writing_priority">
                <strong><?php esc_html_e('Priority', 'writing-status'); ?></strong>
            </label>
        </p>
        <p>
            <?php
            $priority = get_post_meta($post->ID, '_writing_priority', true);
            if (empty($priority)) {
                $priority = 'none';
            }
            ?>
            <select id="writing_priority" name="writing_priority" style="width: 100%;">
                <option value="none" <?php selected($priority, 'none'); ?>><?php esc_html_e('None', 'writing-status'); ?></option>
                <option value="low" <?php selected($priority, 'low'); ?>><?php esc_html_e('Low', 'writing-status'); ?></option>
                <option value="medium" <?php selected($priority, 'medium'); ?>><?php esc_html_e('Medium', 'writing-status'); ?></option>
                <option value="high" <?php selected($priority, 'high'); ?>><?php esc_html_e('High', 'writing-status'); ?></option>
                <option value="urgent" <?php selected($priority, 'urgent'); ?>><?php esc_html_e('Urgent', 'writing-status'); ?></option>
            </select>
        </p>
        <p class="description">
            <?php esc_html_e('Set the priority level for this draft.', 'writing-status'); ?>
        </p>
        <?php
    }

    public function saveCompletionStatus($post_id) {
        if (!isset($_POST['writing_completion_nonce_field']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['writing_completion_nonce_field'])), 'writing_completion_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['writing_complete'])) {
            $writing_complete = sanitize_text_field(wp_unslash($_POST['writing_complete']));
            $value = ($writing_complete === 'yes') ? 'yes' : 'no';
            update_post_meta($post_id, '_writing_complete', $value);
        } else {
            update_post_meta($post_id, '_writing_complete', 'no');
        }

        $this->saveDraftDueDate($post_id);
        $this->saveDraftPriority($post_id);
        delete_transient('writing_status_overdue_count');
    }
}
