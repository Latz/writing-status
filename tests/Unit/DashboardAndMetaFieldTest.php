<?php
/**
 * Unit tests for WritingStatus dashboard widget and meta field registration.
 *
 * Covers:
 * - addDashboardWidget()
 * - registerMetaField()
 * - renderDashboardWidget()
 * - renderDashboardIncompletePosts() / renderDashboardCompletePosts()
 * - private renderDashboardPostSection() (via ReflectionMethod)
 */

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DashboardAndMetaFieldTest extends TestCase {

    /** @var WritingStatus */
    private $plugin;

    public function setUp(): void {
        WP_Mock::setUp();
        global $wpdb;
        $wpdb            = new stdClass();
        $wpdb->postmeta  = 'wp_postmeta';
        $wpdb->posts     = 'wp_posts';
        $this->plugin = new WritingStatus();
    }

    public function tearDown(): void {
        WP_Mock::tearDown();
    }

    // -------------------------------------------------------------------------
    // addDashboardWidget
    // -------------------------------------------------------------------------

    #[Test]
    public function add_dashboard_widget_executes_without_error(): void {
        // wp_add_dashboard_widget() is a no-op stub defined in bootstrap.php.
        $this->plugin->addDashboardWidget();
        $this->assertTrue( true );
    }

    // -------------------------------------------------------------------------
    // registerMetaField
    // -------------------------------------------------------------------------

    #[Test]
    public function register_meta_field_executes_without_error(): void {
        // register_post_meta() is a no-op stub; it should be called three times
        // (_writing_complete, _writing_due_date, _writing_priority) without throwing.
        $this->plugin->registerMetaField();
        $this->assertTrue( true );
    }

    // -------------------------------------------------------------------------
    // renderDashboardWidget
    // -------------------------------------------------------------------------

    #[Test]
    public function render_dashboard_widget_outputs_div_wrapper(): void {
        ob_start();
        $this->plugin->renderDashboardWidget();
        $output = ob_get_clean();

        $this->assertStringContainsString( 'writing-status-widget', $output );
    }

    #[Test]
    public function render_dashboard_widget_outputs_no_drafts_message_when_empty(): void {
        // Both queries will have no posts (WP_Query stub starts empty).
        ob_start();
        $this->plugin->renderDashboardWidget();
        $output = ob_get_clean();

        // The plugin outputs "No drafts found. Start writing!" when both queries
        // return no posts.
        $this->assertStringContainsString( 'No drafts found', $output );
    }

    // -------------------------------------------------------------------------
    // renderDashboardIncompletePosts (protected — accessed via ReflectionMethod)
    // -------------------------------------------------------------------------

    #[Test]
    public function render_dashboard_incomplete_posts_produces_no_output_when_query_is_empty(): void {
        $query = new WP_Query();

        $method = new ReflectionMethod( WritingStatus::class, 'renderDashboardIncompletePosts' );
        $method->setAccessible( true );

        ob_start();
        $method->invokeArgs( $this->plugin, [ $query ] );
        $output = ob_get_clean();

        // Empty query — renderDashboardPostSection returns early, so output is empty.
        $this->assertSame( '', $output );
    }

    #[Test]
    public function render_dashboard_incomplete_posts_outputs_section_when_posts_exist(): void {
        $query = new WP_Query();
        // One fake post so have_posts() returns true once.
        $query->set_posts( [ 1 ] );

        $method = new ReflectionMethod( WritingStatus::class, 'renderDashboardIncompletePosts' );
        $method->setAccessible( true );

        ob_start();
        $method->invokeArgs( $this->plugin, [ $query ] );
        $output = ob_get_clean();

        $this->assertStringContainsString( '<section', $output );
        $this->assertStringContainsString( 'writing-status-incomplete', $output );
    }

    // -------------------------------------------------------------------------
    // renderDashboardCompletePosts (protected — accessed via ReflectionMethod)
    // -------------------------------------------------------------------------

    #[Test]
    public function render_dashboard_complete_posts_produces_no_output_when_query_is_empty(): void {
        $query = new WP_Query();

        $method = new ReflectionMethod( WritingStatus::class, 'renderDashboardCompletePosts' );
        $method->setAccessible( true );

        ob_start();
        $method->invokeArgs( $this->plugin, [ $query ] );
        $output = ob_get_clean();

        $this->assertSame( '', $output );
    }

    #[Test]
    public function render_dashboard_complete_posts_outputs_section_when_posts_exist(): void {
        $query = new WP_Query();
        $query->set_posts( [ 1 ] );

        $method = new ReflectionMethod( WritingStatus::class, 'renderDashboardCompletePosts' );
        $method->setAccessible( true );

        ob_start();
        $method->invokeArgs( $this->plugin, [ $query ] );
        $output = ob_get_clean();

        $this->assertStringContainsString( '<section', $output );
        $this->assertStringContainsString( 'writing-status-complete', $output );
    }

    // -------------------------------------------------------------------------
    // Private renderDashboardPostSection (via ReflectionMethod)
    // -------------------------------------------------------------------------

    #[Test]
    public function render_dashboard_post_section_outputs_section_when_posts_exist(): void {
        $query = new WP_Query();
        $query->set_posts( [ 1 ] );

        $method = new ReflectionMethod( WritingStatus::class, 'renderDashboardPostSection' );
        $method->setAccessible( true );

        ob_start();
        $method->invokeArgs(
            $this->plugin,
            [ $query, 'Test (%d)', 'test-id', 'test-class', '✓' ]
        );
        $output = ob_get_clean();

        $this->assertStringContainsString( '<section', $output );
    }

    #[Test]
    public function render_dashboard_post_section_includes_section_id_in_output(): void {
        $query = new WP_Query();
        $query->set_posts( [ 1 ] );

        $method = new ReflectionMethod( WritingStatus::class, 'renderDashboardPostSection' );
        $method->setAccessible( true );

        ob_start();
        $method->invokeArgs(
            $this->plugin,
            [ $query, 'Test (%d)', 'my-section-id', 'my-class', '✗' ]
        );
        $output = ob_get_clean();

        $this->assertStringContainsString( 'my-section-id', $output );
    }

    #[Test]
    public function render_dashboard_post_section_returns_empty_string_when_no_posts(): void {
        $query = new WP_Query(); // No posts set — have_posts() returns false.

        $method = new ReflectionMethod( WritingStatus::class, 'renderDashboardPostSection' );
        $method->setAccessible( true );

        ob_start();
        $method->invokeArgs(
            $this->plugin,
            [ $query, 'Test (%d)', 'test-id', 'test-class', '✓' ]
        );
        $output = ob_get_clean();

        $this->assertSame( '', $output );
    }
}
