<?php
/**
 * Integration tests for WritingStatus post-meta save behaviour.
 *
 * These tests use the real WordPress database (WP_UnitTestCase) so every
 * update_post_meta / get_post_meta call hits actual MySQL.
 *
 * Run inside the wp-wordpress-1 container:
 *   vendor/bin/phpunit --bootstrap tests/integration-bootstrap.php --testsuite integration
 */

class SaveMetaIntegrationTest extends WP_UnitTestCase {

    /** @var WritingStatus */
    private $plugin;

    /** @var int Editor-role user ID */
    private $editor_id;

    public function setUp(): void {
        parent::setUp();

        $this->plugin = new WritingStatusMetaBox();
        $this->editor_id = self::factory()->user->create( [ 'role' => 'editor' ] );
    }

    // -----------------------------------------------------------------------
    // Helper: simulate a form submission with a valid nonce
    // -----------------------------------------------------------------------

    private function postWith( array $fields, int $post_id ): void {
        wp_set_current_user( $this->editor_id );

        $_POST = array_merge( [
            'writing_completion_nonce_field' => wp_create_nonce( 'writing_completion_nonce' ),
        ], $fields );

        $this->plugin->saveCompletionStatus( $post_id );

        $_POST = [];
    }

    // -----------------------------------------------------------------------
    // Completion status (_writing_complete)
    // -----------------------------------------------------------------------

    /** @test */
    public function saves_yes_when_writing_complete_is_yes(): void {
        $post_id = self::factory()->post->create( [ 'post_status' => 'draft' ] );

        $this->postWith( [ 'writing_complete' => 'yes' ], $post_id );

        $this->assertSame( 'yes', get_post_meta( $post_id, '_writing_complete', true ) );
    }

    /** @test */
    public function saves_no_when_writing_complete_is_anything_else(): void {
        $post_id = self::factory()->post->create( [ 'post_status' => 'draft' ] );

        $this->postWith( [ 'writing_complete' => 'maybe' ], $post_id );

        $this->assertSame( 'no', get_post_meta( $post_id, '_writing_complete', true ) );
    }

    /** @test */
    public function saves_no_when_writing_complete_field_is_absent(): void {
        $post_id = self::factory()->post->create( [ 'post_status' => 'draft' ] );

        $this->postWith( [], $post_id );

        $this->assertSame( 'no', get_post_meta( $post_id, '_writing_complete', true ) );
    }

    // -----------------------------------------------------------------------
    // Due date (_writing_due_date)
    // -----------------------------------------------------------------------

    /** @test */
    public function saves_valid_due_date(): void {
        $post_id = self::factory()->post->create( [ 'post_status' => 'draft' ] );

        $this->postWith( [ 'writing_due_date' => '2026-12-31' ], $post_id );

        $this->assertSame( '2026-12-31', get_post_meta( $post_id, '_writing_due_date', true ) );
    }

    /** @test */
    public function rejects_malformed_due_date(): void {
        $post_id = self::factory()->post->create( [ 'post_status' => 'draft' ] );

        $this->postWith( [ 'writing_due_date' => '31-12-2026' ], $post_id );

        // Meta should not have been written.
        $this->assertEmpty( get_post_meta( $post_id, '_writing_due_date', true ) );
    }

    /** @test */
    public function deletes_due_date_meta_when_empty_string_submitted(): void {
        $post_id = self::factory()->post->create( [ 'post_status' => 'draft' ] );
        update_post_meta( $post_id, '_writing_due_date', '2026-01-01' );

        $this->postWith( [ 'writing_due_date' => '' ], $post_id );

        $this->assertEmpty( get_post_meta( $post_id, '_writing_due_date', true ) );
    }

    // -----------------------------------------------------------------------
    // Priority (_writing_priority)
    // -----------------------------------------------------------------------

    /** @test */
    public function saves_valid_priority(): void {
        $post_id = self::factory()->post->create( [ 'post_status' => 'draft' ] );

        foreach ( [ 'none', 'low', 'medium', 'high', 'urgent' ] as $priority ) {
            $this->postWith( [ 'writing_priority' => $priority ], $post_id );
            $this->assertSame(
                $priority,
                get_post_meta( $post_id, '_writing_priority', true ),
                "Priority '$priority' was not saved correctly"
            );
        }
    }

    /** @test */
    public function saves_none_for_invalid_priority(): void {
        $post_id = self::factory()->post->create( [ 'post_status' => 'draft' ] );

        $this->postWith( [ 'writing_priority' => 'critical' ], $post_id );

        $this->assertSame( 'none', get_post_meta( $post_id, '_writing_priority', true ) );
    }

    // -----------------------------------------------------------------------
    // Security guards (real WP environment)
    // -----------------------------------------------------------------------

    /** @test */
    public function does_not_save_without_nonce(): void {
        $post_id = self::factory()->post->create( [ 'post_status' => 'draft' ] );
        wp_set_current_user( $this->editor_id );

        $_POST = [ 'writing_complete' => 'yes' ];
        $this->plugin->saveCompletionStatus( $post_id );
        $_POST = [];

        $this->assertEmpty( get_post_meta( $post_id, '_writing_complete', true ) );
    }

    /** @test */
    public function does_not_save_for_subscriber_without_edit_capability(): void {
        $post_id      = self::factory()->post->create( [ 'post_status' => 'draft' ] );
        $subscriber   = self::factory()->user->create( [ 'role' => 'subscriber' ] );
        wp_set_current_user( $subscriber );

        $_POST = [
            'writing_completion_nonce_field' => wp_create_nonce( 'writing_completion_nonce' ),
            'writing_complete'               => 'yes',
        ];

        $this->plugin->saveCompletionStatus( $post_id );
        $_POST = [];

        $this->assertEmpty( get_post_meta( $post_id, '_writing_complete', true ) );
    }
}
