<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Unit tests for WritingStatus::dashboardWidgetOrderby().
 */

class MockWPQuery3
{
    public array $data = [];
    public function get(string $key, $default = '') { return $this->data[$key] ?? $default; }
    public function set(string $key, $value): void { $this->data[$key] = $value; }
    public function is_main_query(): bool { return true; }
}

beforeEach(function (): void {
    $this->plugin = new WritingStatusDashboard();
});

describe('WritingStatusDashboard::dashboardWidgetOrderby()', function (): void {

    it('returns original orderby when not priority_then_modified', function (): void {
        $query = new MockWPQuery3();
        $query->set('orderby', 'date');

        $result = $this->plugin->dashboardWidgetOrderby('original', $query);

        expect($result)->toBe('original');
    });

    it('returns sql when orderby is priority_then_modified', function (): void {
        global $wpdb;
        $wpdb           = new stdClass();
        $wpdb->postmeta = 'wp_postmeta';
        $wpdb->posts    = 'wp_posts';

        $query = new MockWPQuery3();
        $query->set('orderby', 'priority_then_modified');

        $result = $this->plugin->dashboardWidgetOrderby('original', $query);

        expect($result)->toContain('CASE');
    });

    it('sql orders urgent first', function (): void {
        global $wpdb;
        $wpdb           = new stdClass();
        $wpdb->postmeta = 'wp_postmeta';
        $wpdb->posts    = 'wp_posts';

        $query = new MockWPQuery3();
        $query->set('orderby', 'priority_then_modified');

        $result = $this->plugin->dashboardWidgetOrderby('original', $query);

        expect($result)->toContain("WHEN 'urgent' THEN 1");
    });

    it('sql orders by modified date desc', function (): void {
        global $wpdb;
        $wpdb           = new stdClass();
        $wpdb->postmeta = 'wp_postmeta';
        $wpdb->posts    = 'wp_posts';

        $query = new MockWPQuery3();
        $query->set('orderby', 'priority_then_modified');

        $result = $this->plugin->dashboardWidgetOrderby('original', $query);

        expect($result)->toContain('post_modified DESC');
    });
});
