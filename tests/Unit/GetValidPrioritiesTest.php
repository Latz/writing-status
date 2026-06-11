<?php
/**
 * Unit tests for WritingStatus::getValidPriorities(), getPriorityLabels(),
 * and registerMetaField().
 *
 * These tests run entirely with WP_Mock — no database required.
 */

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class GetValidPrioritiesTest extends TestCase {

    /** @var WritingStatus */
    private $plugin;

    public function setUp(): void {
        WP_Mock::setUp();
        $this->plugin = new WritingStatus();
    }

    public function tearDown(): void {
        WP_Mock::tearDown();
    }

    // -------------------------------------------------------------------------
    // getValidPriorities
    // -------------------------------------------------------------------------

    #[Test]
    public function returns_array_with_five_priorities(): void {
        $method = new ReflectionMethod( WritingStatus::class, 'getValidPriorities' );
        $method->setAccessible( true );

        $result = $method->invoke( $this->plugin );

        $this->assertCount( 5, $result );
    }

    #[Test]
    public function contains_all_expected_priorities(): void {
        $method = new ReflectionMethod( WritingStatus::class, 'getValidPriorities' );
        $method->setAccessible( true );

        $result = $method->invoke( $this->plugin );

        foreach ( [ 'none', 'low', 'medium', 'high', 'urgent' ] as $priority ) {
            $this->assertContains( $priority, $result );
        }
    }

    #[Test]
    public function urgent_is_in_valid_priorities(): void {
        $method = new ReflectionMethod( WritingStatus::class, 'getValidPriorities' );
        $method->setAccessible( true );

        $result = $method->invoke( $this->plugin );

        $this->assertTrue( in_array( 'urgent', $result, true ) );
    }

    // -------------------------------------------------------------------------
    // getPriorityLabels
    // -------------------------------------------------------------------------

    #[Test]
    public function returns_four_labels(): void {
        $method = new ReflectionMethod( WritingStatus::class, 'getPriorityLabels' );
        $method->setAccessible( true );

        $result = $method->invoke( $this->plugin );

        $this->assertCount( 4, $result );
    }

    #[Test]
    public function does_not_contain_none_key(): void {
        $method = new ReflectionMethod( WritingStatus::class, 'getPriorityLabels' );
        $method->setAccessible( true );

        $result = $method->invoke( $this->plugin );

        $this->assertFalse( array_key_exists( 'none', $result ) );
    }

    #[Test]
    public function contains_high_key(): void {
        $method = new ReflectionMethod( WritingStatus::class, 'getPriorityLabels' );
        $method->setAccessible( true );

        $result = $method->invoke( $this->plugin );

        $this->assertTrue( array_key_exists( 'high', $result ) );
    }

    // -------------------------------------------------------------------------
    // registerMetaField
    // -------------------------------------------------------------------------

    #[Test]
    public function register_meta_field_executes_without_error(): void {
        $this->plugin->registerMetaField();

        $this->assertTrue( true );
    }
}
