<?php
/**
 * Unit tests for WritingStatus meta box and dashboard widget registration methods.
 */

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AddCompletionMetaBoxTest extends TestCase {

    /** @var WritingStatusMetaBox */
    private $metaBox;

    /** @var WritingStatusColumn */
    private $column;

    /** @var WritingStatusDashboard */
    private $dashboard;

    public function setUp(): void {
        WP_Mock::setUp();
        $this->metaBox   = new WritingStatusMetaBox();
        $this->column    = new WritingStatusColumn();
        $this->dashboard = new WritingStatusDashboard();
    }

    public function tearDown(): void {
        WP_Mock::tearDown();
    }

    #[Test]
    public function add_completion_meta_box_executes_without_error(): void {
        $this->metaBox->addCompletionMetaBox();
        $this->assertTrue( true );
    }

    #[Test]
    public function add_dashboard_widget_executes_without_error(): void {
        $this->dashboard->addDashboardWidget();
        $this->assertTrue( true );
    }

    #[Test]
    public function add_completion_column_adds_writing_completion_key(): void {
        $result = $this->column->addCompletionColumn( [ 'title' => 'Title' ] );
        $this->assertArrayHasKey( 'writing_completion', $result );
    }

    #[Test]
    public function add_completion_column_preserves_existing_columns(): void {
        $result = $this->column->addCompletionColumn( [ 'title' => 'Title', 'date' => 'Date' ] );
        $this->assertArrayHasKey( 'title', $result );
    }

    #[Test]
    public function make_completion_sortable_adds_writing_completion(): void {
        $result = $this->column->makeCompletionSortable( [] );
        $this->assertArrayHasKey( 'writing_completion', $result );
        $this->assertSame( 'writing_completion', $result['writing_completion'] );
    }

    #[Test]
    public function make_completion_sortable_preserves_existing(): void {
        $result = $this->column->makeCompletionSortable( [ 'title' => 'Title' ] );
        $this->assertArrayHasKey( 'title', $result );
    }
}
