<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use Brain\Monkey\Functions;

/**
 * Unit tests for WritingStatus::customPriorityOrderby().
 */

class MockWPQuery2
{
    public array $data = [];
    public bool $_is_main = true;
    public function is_main_query(): bool { return $this->_is_main; }
    public function get(string $key, $default = '') { return $this->data[$key] ?? $default; }
    public function set(string $key, $value): void { $this->data[$key] = $value; }
}

beforeEach(function (): void {
    $this->plugin = new WritingStatusColumn();
});

describe('WritingStatusColumn::customPriorityOrderby()', function (): void {

    it('returns original orderby when not admin', function (): void {
        Functions\when('is_admin')->justReturn(false);

        $query = new MockWPQuery2();
        $query->_is_main = true;
        $query->set('orderby', 'writing_completion');

        $result = $this->plugin->customPriorityOrderby('original', $query);

        expect($result)->toBe('original');
    });

    it('returns original orderby when not main query', function (): void {
        Functions\when('is_admin')->justReturn(true);

        $query = new MockWPQuery2();
        $query->_is_main = false;
        $query->set('orderby', 'writing_completion');

        $result = $this->plugin->customPriorityOrderby('original_2', $query);

        expect($result)->toBe('original_2');
    });

    it('returns sql orderby when admin and main query', function (): void {
        global $wpdb;
        $wpdb           = new stdClass();
        $wpdb->postmeta = 'wp_postmeta';
        $wpdb->posts    = 'wp_posts';

        Functions\when('is_admin')->justReturn(true);

        $query           = new MockWPQuery2();
        $query->_is_main = true;

        $result = $this->plugin->customPriorityOrderby('original', $query);

        expect($result)->toContain('CASE');
        expect($result)->not->toBe('original');
    });

    it('sql contains urgent priority ordering', function (): void {
        global $wpdb;
        $wpdb           = new stdClass();
        $wpdb->postmeta = 'wp_postmeta';
        $wpdb->posts    = 'wp_posts';

        Functions\when('is_admin')->justReturn(true);

        $query           = new MockWPQuery2();
        $query->_is_main = true;

        $result = $this->plugin->customPriorityOrderby('original', $query);

        expect($result)->toContain('urgent');
    });

    it('sql contains asc order by default', function (): void {
        global $wpdb;
        $wpdb           = new stdClass();
        $wpdb->postmeta = 'wp_postmeta';
        $wpdb->posts    = 'wp_posts';

        Functions\when('is_admin')->justReturn(true);

        $query           = new MockWPQuery2();
        $query->_is_main = true;

        $result = $this->plugin->customPriorityOrderby('original', $query);

        expect($result)->toContain('ASC');
    });

    it('sql contains desc order when query order is desc', function (): void {
        global $wpdb;
        $wpdb           = new stdClass();
        $wpdb->postmeta = 'wp_postmeta';
        $wpdb->posts    = 'wp_posts';

        Functions\when('is_admin')->justReturn(true);

        $query                = new MockWPQuery2();
        $query->_is_main      = true;
        $query->data['order'] = 'DESC';

        $result = $this->plugin->customPriorityOrderby('original', $query);

        expect($result)->toContain('DESC');
    });

    it('returns original orderby when orderby is not writing_completion', function (): void {
        Functions\when('is_admin')->justReturn(false);

        $query = new MockWPQuery2();
        $query->_is_main = true;
        $query->set('orderby', 'date');

        $result = $this->plugin->customPriorityOrderby('original_3', $query);

        expect($result)->toBe('original_3');
    });
});
