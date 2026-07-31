<?php
/**
 * Writing Status Meta Box
 *
 * Registers and renders the Completion Status meta box in the Classic Editor,
 * and handles saving post meta from the meta box form.
 *
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WritingStatusMetaBox extends WritingStatusRenderer {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'addCompletionMetaBox' ) );
        add_action( 'save_post', array( $this, 'saveCompletionStatus' ) );
        add_action( 'post_submitbox_misc_actions', array( $this, 'renderDraftStatusRow' ) );
    }

    public function renderDraftStatusRow() {
        global $post;

        if ( ! $post || $post->post_type !== 'post' ) {
            return;
        }

        if ( get_post_status( $post->ID ) === 'publish' ) {
            return;
        }

        $is_complete = get_post_meta( $post->ID, '_writing_complete', true );
        $is_complete = $is_complete === 'yes' ? 'yes' : 'no';
        $complete_label   = esc_html__( 'Draft status: Complete', 'writing-status' );
        $incomplete_label = esc_html__( 'Draft status: Incomplete', 'writing-status' );
        ?>
        <div class="misc-pub-section misc-pub-draft-status">
            <input type="hidden" id="writing_complete_hidden" name="writing_complete" value="<?php echo esc_attr( $is_complete ); ?>">
            <a href="#" id="writing-draft-status-toggle-link" class="writing-draft-status-link" aria-expanded="false" aria-controls="writing-draft-status-box" data-complete-label="<?php echo esc_attr( $complete_label ); ?>" data-incomplete-label="<?php echo esc_attr( $incomplete_label ); ?>">
                <span class="writing-draft-status-link-icon"><?php echo $is_complete === 'yes' ? '✓' : '✗'; ?></span>
                <span class="writing-draft-status-link-text"><?php echo $is_complete === 'yes' ? $complete_label : $incomplete_label; ?></span>
            </a>
            <div id="writing-draft-status-box" class="writing-draft-status-box">
                <button type="button" id="writing_complete_button" class="button draft-complete-toggle is-complete-btn <?php echo esc_attr( $is_complete === 'yes' ? 'is-active' : '' ); ?>" aria-pressed="<?php echo esc_attr( $is_complete === 'yes' ? 'true' : 'false' ); ?>">
                    <span class="writing-status-icon">✓</span>
                    <span class="writing-status-text"><?php esc_html_e( 'Complete', 'writing-status' ); ?></span>
                </button>
                <button type="button" id="writing_incomplete_button" class="button draft-complete-toggle is-incomplete-btn <?php echo esc_attr( $is_complete === 'yes' ? '' : 'is-active' ); ?>" aria-pressed="<?php echo esc_attr( $is_complete === 'yes' ? 'false' : 'true' ); ?>">
                    <span class="writing-status-icon">✗</span>
                    <span class="writing-status-text"><?php esc_html_e( 'Incomplete', 'writing-status' ); ?></span>
                </button>
            </div>
        </div>
        <?php
    }

    public function addCompletionMetaBox() {
        // skipcq: PHP-W1020
        add_meta_box(
            'writing_completion_box',
            __( 'Completion Status', 'writing-status' ),
            array( $this, 'renderCompletionMetaBox' ),
            'post',
            'side',
            'default',
            array( '__back_compat_meta_box' => true )
        );
    }

    public function renderCompletionMetaBox( $post ) {
        $post_status = get_post_status( $post->ID );

        if ( $post_status === 'publish' ) {
            ?>
            <p class="writing-status-metabox-published">
                <span class="writing-status-indicator writing-status-published">● <?php esc_html_e( 'Published', 'writing-status' ); ?></span>
            </p>
            <p class="description">
                <?php esc_html_e( 'This post has been published.', 'writing-status' ); ?>
            </p>
            <?php
            return;
        }

        wp_nonce_field( 'writing_completion_nonce', 'writing_completion_nonce_field' );

        $due_date = get_post_meta( $post->ID, '_writing_due_date', true );
        ?>
        <p>
            <label for="writing_due_date">
                <strong><?php esc_html_e( 'Due Date', 'writing-status' ); ?></strong>
            </label>
        </p>
        <p>
            <input type="date"
                    id="writing_due_date"
                    name="writing_due_date"
                    value="<?php echo esc_attr( $due_date ); ?>"
                    style="width: 100%;">
        </p>
        <p class="description">
            <?php esc_html_e( 'Set a target completion date for this draft.', 'writing-status' ); ?>
        </p>

        <hr style="margin: 15px 0;">

        <p>
            <label for="writing_priority">
                <strong><?php esc_html_e( 'Priority', 'writing-status' ); ?></strong>
            </label>
        </p>
        <p>
            <?php
            $priority = get_post_meta( $post->ID, '_writing_priority', true );
            if ( empty( $priority ) ) {
                $priority = 'none';
            }
            ?>
            <select id="writing_priority" name="writing_priority" style="width: 100%;">
                <option value="none" <?php selected( $priority, 'none' ); ?>><?php esc_html_e( 'None', 'writing-status' ); ?></option>
                <option value="low" <?php selected( $priority, 'low' ); ?>><?php esc_html_e( 'Low', 'writing-status' ); ?></option>
                <option value="medium" <?php selected( $priority, 'medium' ); ?>><?php esc_html_e( 'Medium', 'writing-status' ); ?></option>
                <option value="high" <?php selected( $priority, 'high' ); ?>><?php esc_html_e( 'High', 'writing-status' ); ?></option>
                <option value="urgent" <?php selected( $priority, 'urgent' ); ?>><?php esc_html_e( 'Urgent', 'writing-status' ); ?></option>
            </select>
        </p>
        <p class="description">
            <?php esc_html_e( 'Set the priority level for this draft.', 'writing-status' ); ?>
        </p>
        <?php
    }

    public function saveCompletionStatus( $post_id ) {
        if ( ! isset( $_POST['writing_completion_nonce_field'] ) ||
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['writing_completion_nonce_field'] ) ), 'writing_completion_nonce' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['writing_complete'] ) ) {
            $writing_complete = sanitize_text_field( wp_unslash( $_POST['writing_complete'] ) );
            $value            = ( $writing_complete === 'yes' ) ? 'yes' : 'no';
            update_post_meta( $post_id, '_writing_complete', $value );
        } else {
            update_post_meta( $post_id, '_writing_complete', 'no' );
        }

        $this->saveDraftDueDate( $post_id );
        $this->saveDraftPriority( $post_id );
        delete_transient( 'writing_status_overdue_count' );
    }
}
