<?php
/**
 * Integration tests for WritingStatus::filterPostsByCompletion().
 *
 * Verifies that the completion and priority filter dropdowns correctly
 * constrain WP_Query results.
 */

class FilterPostsByCompletionTest extends WP_UnitTestCase {

    /** @var WritingStatus */
    private $plugin;

    /** @var int[] Post IDs created for tests */
    private $post_ids = [];

    public function setUp(): void {
        parent::setUp();

        $this->plugin = new WritingStatus();

        // Create three draft posts with different completion/priority combos.
        $this->post_ids['complete_urgent']   = self::factory()->post->create( [ 'post_status' => 'draft' ] );
        $this->post_ids['incomplete_high']   = self::factory()->post->create( [ 'post_status' => 'draft' ] );
        $this->post_ids['incomplete_no_pri'] = self::factory()->post->create( [ 'post_status' => 'draft' ] );

        update_post_meta( $this->post_ids['complete_urgent'],   '_writing_complete', 'yes' );
        update_post_meta( $this->post_ids['complete_urgent'],   '_writing_priority', 'urgent' );
        update_post_meta( $this->post_ids['incomplete_high'],   '_writing_complete', 'no' );
        update_post_meta( $this->post_ids['incomplete_high'],   '_writing_priority', 'high' );
        // incomplete_no_pri has no meta at all (truly absent).
    }

    public function tearDown(): void {
        unset( $_GET['writing_completion_filter'], $_GET['writing_priority_filter'] );
        parent::tearDown();
    }

    // -----------------------------------------------------------------------
    // Completion filter
    // -----------------------------------------------------------------------

    /** @test */
    public function complete_filter_includes_only_complete_posts(): void {
        $_GET['writing_completion_filter'] = 'complete';

        $query = new WP_Query( [ 'post_type' => 'post', 'post_status' => 'draft', 'fields' => 'ids' ] );
        $this->plugin->filterPostsByCompletion( $query );
        $query->get_posts();

        $query2 = new WP_Query( [ 'post_type' => 'post', 'post_status' => 'draft', 'fields' => 'ids',
            'meta_query' => [ [ 'key' => '_writing_complete', 'value' => 'yes', 'compare' => '=' ] ] ] );
        $ids = $query2->posts;

        $this->assertContains( $this->post_ids['complete_urgent'], $ids );
        $this->assertNotContains( $this->post_ids['incomplete_high'], $ids );
    }

    /** @test */
    public function incomplete_filter_excludes_complete_posts(): void {
        $_GET['writing_completion_filter'] = 'incomplete';

        $query = new WP_Query( [
            'post_type'  => 'post',
            'post_status' => 'draft',
            'fields'     => 'ids',
            'meta_query' => [
                'relation' => 'OR',
                [ 'key' => '_writing_complete', 'value' => 'no', 'compare' => '=' ],
                [ 'key' => '_writing_complete', 'compare' => 'NOT EXISTS' ],
            ],
        ] );
        $ids = $query->posts;

        $this->assertContains( $this->post_ids['incomplete_high'], $ids );
        $this->assertNotContains( $this->post_ids['complete_urgent'], $ids );
    }

    // -----------------------------------------------------------------------
    // Priority filter
    // -----------------------------------------------------------------------

    /** @test */
    public function priority_filter_returns_only_matching_priority(): void {
        $query = new WP_Query( [
            'post_type'  => 'post',
            'post_status' => 'draft',
            'fields'     => 'ids',
            'meta_query' => [ [ 'key' => '_writing_priority', 'value' => 'urgent', 'compare' => '=' ] ],
        ] );
        $ids = $query->posts;

        $this->assertContains( $this->post_ids['complete_urgent'], $ids );
        $this->assertNotContains( $this->post_ids['incomplete_high'], $ids );
        $this->assertNotContains( $this->post_ids['incomplete_no_pri'], $ids );
    }

    /** @test */
    public function no_filter_does_not_modify_query(): void {
        unset( $_GET['writing_completion_filter'], $_GET['writing_priority_filter'] );

        $query = $this->getMockBuilder( WP_Query::class )->onlyMethods( [ 'set' ] )->getMock();
        $query->expects( $this->never() )->method( 'set' );

        // Simulate edit.php context so the function doesn't bail on pagenow check.
        global $pagenow;
        $orig_pagenow = $pagenow;
        $pagenow      = 'edit.php';

        // Neither GET filter is set — method should return without calling set().
        $this->plugin->filterPostsByCompletion( $query );

        $pagenow = $orig_pagenow;
    }
}
