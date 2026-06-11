<?php
/**
 * Unit tests for WritingStatus::filterPostsByCompletion() and its private helpers.
 *
 * The public method is tested via the is_admin() guard (returns false in
 * bootstrap). Private helpers are exercised directly via ReflectionMethod.
 */

use PHPUnit\Framework\TestCase;

class MockWPQueryFilter {
    public array $data = [];
    public bool $_is_main = true;
    public function is_main_query(): bool { return $this->_is_main; }
    public function get(string $key, $default = '') { return $this->data[$key] ?? $default; }
    public function set(string $key, $value): void { $this->data[$key] = $value; }
}

class FilterPostsByCompletionUnitTest extends TestCase {

    /** @var WritingStatus */
    private $plugin;

    public function setUp(): void {
        WP_Mock::setUp();
        $this->plugin = new WritingStatus();
    }

    public function tearDown(): void {
        WP_Mock::tearDown();
        unset( $_GET['writing_completion_filter'], $_GET['writing_priority_filter'] );
        unset( $GLOBALS['_writing_status_is_admin'] );
        global $pagenow;
        $pagenow = '';
    }

    /** @test */
    public function returns_early_when_not_admin(): void {
        global $pagenow;
        $pagenow = 'edit.php';
        $_GET['writing_completion_filter'] = 'complete';

        $query = new MockWPQueryFilter();
        $this->plugin->filterPostsByCompletion( $query );

        $this->assertArrayNotHasKey( 'meta_query', $query->data );
    }

    /** @test */
    public function apply_completion_filter_complete_sets_meta_query(): void {
        $_GET['writing_completion_filter'] = 'complete';

        $method = new ReflectionMethod( WritingStatus::class, 'applyCompletionFilter' );
        $method->setAccessible( true );

        $query = new MockWPQueryFilter();
        $filter_meta_query = [ 'relation' => 'AND' ];

        $method->invokeArgs( $this->plugin, [ $query, &$filter_meta_query ] );

        $this->assertCount( 2, $filter_meta_query );
        $this->assertSame( 'draft', $query->get( 'post_status' ) );
    }

    /** @test */
    public function apply_completion_filter_incomplete_sets_or_meta_query(): void {
        $_GET['writing_completion_filter'] = 'incomplete';

        $method = new ReflectionMethod( WritingStatus::class, 'applyCompletionFilter' );
        $method->setAccessible( true );

        $query = new MockWPQueryFilter();
        $filter_meta_query = [ 'relation' => 'AND' ];

        $method->invokeArgs( $this->plugin, [ $query, &$filter_meta_query ] );

        $this->assertCount( 2, $filter_meta_query );
        $this->assertSame( 'draft', $query->get( 'post_status' ) );
    }

    /** @test */
    public function apply_completion_filter_unknown_value_does_nothing(): void {
        $_GET['writing_completion_filter'] = 'unknown';

        $method = new ReflectionMethod( WritingStatus::class, 'applyCompletionFilter' );
        $method->setAccessible( true );

        $query = new MockWPQueryFilter();
        $filter_meta_query = [ 'relation' => 'AND' ];

        $method->invokeArgs( $this->plugin, [ $query, &$filter_meta_query ] );

        $this->assertCount( 1, $filter_meta_query );
    }

    /** @test */
    public function apply_priority_filter_valid_priority_adds_clause(): void {
        $_GET['writing_priority_filter'] = 'high';

        $method = new ReflectionMethod( WritingStatus::class, 'applyPriorityFilter' );
        $method->setAccessible( true );

        $query = new MockWPQueryFilter();
        $filter_meta_query = [ 'relation' => 'AND' ];
        $has_completion_filter = false;

        $method->invokeArgs( $this->plugin, [ $query, &$filter_meta_query, $has_completion_filter ] );

        $this->assertCount( 2, $filter_meta_query );
        $this->assertSame( 'draft', $query->get( 'post_status' ) );
    }

    /** @test */
    public function apply_priority_filter_invalid_priority_does_nothing(): void {
        $_GET['writing_priority_filter'] = 'invalid';

        $method = new ReflectionMethod( WritingStatus::class, 'applyPriorityFilter' );
        $method->setAccessible( true );

        $query = new MockWPQueryFilter();
        $filter_meta_query = [ 'relation' => 'AND' ];
        $has_completion_filter = false;

        $method->invokeArgs( $this->plugin, [ $query, &$filter_meta_query, $has_completion_filter ] );

        $this->assertCount( 1, $filter_meta_query );
    }

    /** @test */
    public function apply_priority_filter_with_completion_filter_does_not_set_post_status(): void {
        $_GET['writing_priority_filter'] = 'high';

        $method = new ReflectionMethod( WritingStatus::class, 'applyPriorityFilter' );
        $method->setAccessible( true );

        $query = new MockWPQueryFilter();
        $filter_meta_query = [ 'relation' => 'AND' ];
        $has_completion_filter = true;

        $method->invokeArgs( $this->plugin, [ $query, &$filter_meta_query, $has_completion_filter ] );

        $this->assertArrayNotHasKey( 'post_status', $query->data );
    }

    /** @test */
    public function sets_meta_query_when_admin_with_completion_filter(): void {
        $GLOBALS['_writing_status_is_admin'] = true;
        global $pagenow;
        $pagenow = 'edit.php';
        $_GET['writing_completion_filter'] = 'complete';

        $query = new MockWPQueryFilter();
        $this->plugin->filterPostsByCompletion( $query );

        $this->assertArrayHasKey( 'meta_query', $query->data );
    }

    /** @test */
    public function returns_early_when_no_filters_set(): void {
        $GLOBALS['_writing_status_is_admin'] = true;
        global $pagenow;
        $pagenow = 'edit.php';

        $query = new MockWPQueryFilter();
        $this->plugin->filterPostsByCompletion( $query );

        $this->assertArrayNotHasKey( 'meta_query', $query->data );
    }

    /** @test */
    public function returns_early_when_pagenow_is_not_edit_php(): void {
        $GLOBALS['_writing_status_is_admin'] = true;
        global $pagenow;
        $pagenow = 'post.php';
        $_GET['writing_completion_filter'] = 'complete';

        $query = new MockWPQueryFilter();
        $this->plugin->filterPostsByCompletion( $query );

        $this->assertArrayNotHasKey( 'meta_query', $query->data );
    }

    /** @test */
    public function sets_meta_query_when_admin_with_priority_filter(): void {
        $GLOBALS['_writing_status_is_admin'] = true;
        global $pagenow;
        $pagenow = 'edit.php';
        $_GET['writing_priority_filter'] = 'high';

        $query = new MockWPQueryFilter();
        $this->plugin->filterPostsByCompletion( $query );

        $this->assertArrayHasKey( 'meta_query', $query->data );
    }

    /** @test */
    public function sets_meta_query_when_both_filters_set(): void {
        $GLOBALS['_writing_status_is_admin'] = true;
        global $pagenow;
        $pagenow = 'edit.php';
        $_GET['writing_completion_filter'] = 'incomplete';
        $_GET['writing_priority_filter'] = 'urgent';

        $query = new MockWPQueryFilter();
        $this->plugin->filterPostsByCompletion( $query );

        $this->assertArrayHasKey( 'meta_query', $query->data );
    }
}
