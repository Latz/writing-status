<?php
/**
 * Unit tests for WritingStatus::makeCompletionSortable().
 */

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MakeCompletionSortableTest extends TestCase {

    /** @var WritingStatus */
    private $plugin;

    public function setUp(): void {
        WP_Mock::setUp();
        $this->plugin = new WritingStatusColumn();
    }

    public function tearDown(): void {
        WP_Mock::tearDown();
    }

    #[Test]
    public function adds_writing_completion_to_sortable_columns(): void {
        $result = $this->plugin->makeCompletionSortable( [] );
        $this->assertArrayHasKey( 'writing_completion', $result );
    }

    #[Test]
    public function sortable_key_maps_to_writing_completion_orderby(): void {
        $result = $this->plugin->makeCompletionSortable( [] );
        $this->assertSame( 'writing_completion', $result['writing_completion'] );
    }

    #[Test]
    public function preserves_existing_sortable_columns(): void {
        $existing = [ 'title' => 'title', 'date' => 'date' ];
        $result   = $this->plugin->makeCompletionSortable( $existing );

        $this->assertArrayHasKey( 'title', $result );
        $this->assertArrayHasKey( 'date', $result );
    }
}
