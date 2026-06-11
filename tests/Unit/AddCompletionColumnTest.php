<?php
/**
 * Unit tests for WritingStatus::addCompletionColumn().
 */

use PHPUnit\Framework\TestCase;

class AddCompletionColumnTest extends TestCase {

    /** @var WritingStatus */
    private $plugin;

    public function setUp(): void {
        WP_Mock::setUp();
        $this->plugin = new WritingStatus();
    }

    public function tearDown(): void {
        WP_Mock::tearDown();
    }

    /** @test */
    public function adds_writing_completion_key_to_columns(): void {
        $result = $this->plugin->addCompletionColumn( [] );
        $this->assertArrayHasKey( 'writing_completion', $result );
    }

    /** @test */
    public function preserves_existing_columns(): void {
        $existing = [ 'title' => 'Title', 'date' => 'Date' ];
        $result   = $this->plugin->addCompletionColumn( $existing );

        $this->assertArrayHasKey( 'title', $result );
        $this->assertArrayHasKey( 'date', $result );
    }

    /** @test */
    public function column_label_is_writing_status(): void {
        $result = $this->plugin->addCompletionColumn( [] );
        $this->assertSame( 'Writing Status', $result['writing_completion'] );
    }

    /** @test */
    public function works_with_empty_columns_array(): void {
        $result = $this->plugin->addCompletionColumn( [] );
        $this->assertCount( 1, $result );
    }
}
