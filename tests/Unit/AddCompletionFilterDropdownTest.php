<?php
/**
 * Unit tests for WritingStatus::addCompletionFilterDropdown().
 */

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AddCompletionFilterDropdownTest extends TestCase {

    /** @var WritingStatus */
    private $plugin;

    public function setUp(): void {
        WP_Mock::setUp();
        $this->plugin = new WritingStatusFilters();
    }

    public function tearDown(): void {
        WP_Mock::tearDown();
        unset( $_GET['writing_completion_filter'], $_GET['writing_priority_filter'] );
    }

    #[Test]
    public function wrong_post_type_produces_no_output(): void {
        ob_start();
        $this->plugin->addCompletionFilterDropdown( 'page' );
        $output = ob_get_clean();

        $this->assertSame( '', $output );
    }

    #[Test]
    public function post_type_outputs_completion_select(): void {
        ob_start();
        $this->plugin->addCompletionFilterDropdown( 'post' );
        $output = ob_get_clean();

        $this->assertStringContainsString( 'writing_completion_filter', $output );
    }

    #[Test]
    public function post_type_outputs_priority_select(): void {
        ob_start();
        $this->plugin->addCompletionFilterDropdown( 'post' );
        $output = ob_get_clean();

        $this->assertStringContainsString( 'writing_priority_filter', $output );
    }

    #[Test]
    public function outputs_complete_and_incomplete_options(): void {
        ob_start();
        $this->plugin->addCompletionFilterDropdown( 'post' );
        $output = ob_get_clean();

        $this->assertStringContainsString( 'complete', $output );
        $this->assertStringContainsString( 'incomplete', $output );
    }

    #[Test]
    public function outputs_priority_options(): void {
        ob_start();
        $this->plugin->addCompletionFilterDropdown( 'post' );
        $output = ob_get_clean();

        $this->assertStringContainsString( 'urgent', $output );
        $this->assertStringContainsString( 'high', $output );
    }
}
