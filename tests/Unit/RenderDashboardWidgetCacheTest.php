<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use Brain\Monkey\Functions;

/**
 * Unit tests for WritingStatusDashboard::renderDashboardWidget()'s transient
 * caching — added as part of the performance review that found this method
 * ran two uncached WP_Query calls (with a per-row correlated-subquery
 * ORDER BY) on every dashboard page load.
 */

beforeEach(function (): void {
    global $wpdb;
    $wpdb           = new stdClass();
    $wpdb->postmeta = 'wp_postmeta';
    $wpdb->posts    = 'wp_posts';
    $this->plugin   = new WritingStatusDashboard();
});

describe('WritingStatusDashboard::renderDashboardWidget() caching', function (): void {

    it('renders and caches on a cold cache', function (): void {
        Functions\when('get_transient')->justReturn(false);

        $setCalls = [];
        Functions\when('set_transient')->alias(function (...$args) use (&$setCalls) {
            $setCalls[] = $args;
            return true;
        });

        ob_start();
        $this->plugin->renderDashboardWidget();
        $output = ob_get_clean();

        expect($output)->toContain('writing-status-widget');
        expect($setCalls)->toHaveCount(1);
        expect($setCalls[0][0])->toBe('writing_status_dashboard_html');
        expect($setCalls[0][1])->toBe($output);
        expect($setCalls[0][2])->toBe(HOUR_IN_SECONDS);
    });

    it('echoes the cached value and skips the query path on a warm cache', function (): void {
        Functions\when('get_transient')->justReturn('<div class="writing-status-widget">cached</div>');

        $setCalls = [];
        Functions\when('set_transient')->alias(function (...$args) use (&$setCalls) {
            $setCalls[] = $args;
            return true;
        });

        ob_start();
        $this->plugin->renderDashboardWidget();
        $output = ob_get_clean();

        // A warm cache echoes the stored string verbatim and never
        // re-renders — confirmed by the absence of "View All Drafts", which
        // only the live render path outputs, and by set_transient() never
        // being called again.
        expect($output)->toBe('<div class="writing-status-widget">cached</div>');
        expect($output)->not->toContain('View All Drafts');
        expect($setCalls)->toBe([]);
    });
});
