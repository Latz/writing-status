<?php
/**
 * Unit tests for WritingStatus::sanitizePriorityValue().
 *
 * These tests run entirely with WP_Mock — no database required.
 */

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SanitizePriorityValueTest extends TestCase {

    /** @var WritingStatus */
    private $plugin;

    public function setUp(): void {
        WP_Mock::setUp();
        $this->plugin = new WritingStatus();
    }

    public function tearDown(): void {
        WP_Mock::tearDown();
    }

    #[Test]
    public function returns_value_unchanged_for_each_valid_priority(): void {
        foreach ( [ 'none', 'low', 'medium', 'high', 'urgent' ] as $priority ) {
            $this->assertSame(
                $priority,
                $this->plugin->sanitizePriorityValue( $priority ),
                "Expected '$priority' to pass through unchanged"
            );
        }
    }

    #[Test]
    public function returns_none_for_unknown_string(): void {
        $this->assertSame( 'none', $this->plugin->sanitizePriorityValue( 'critical' ) );
    }

    #[Test]
    public function returns_none_for_empty_string(): void {
        $this->assertSame( 'none', $this->plugin->sanitizePriorityValue( '' ) );
    }

    #[Test]
    public function returns_none_for_sql_injection_attempt(): void {
        $this->assertSame( 'none', $this->plugin->sanitizePriorityValue( "' OR 1=1 --" ) );
    }

    #[Test]
    public function is_case_sensitive_and_rejects_uppercase(): void {
        $this->assertSame( 'none', $this->plugin->sanitizePriorityValue( 'URGENT' ) );
        $this->assertSame( 'none', $this->plugin->sanitizePriorityValue( 'High' ) );
    }
}
