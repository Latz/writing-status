<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

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

beforeEach(function (): void {
    global $wpdb;
    $wpdb            = new stdClass();
    $wpdb->postmeta  = 'wp_postmeta';
    $wpdb->posts     = 'wp_posts';
    $this->plugin    = new WritingStatus();
    $this->dashboard = new WritingStatusDashboard();
});

describe('addDashboardWidget', function (): void {
    it('executes without error', function (): void {
        $this->dashboard->addDashboardWidget();
        expect(true)->toBeTrue();
    });
});

describe('registerMetaField', function (): void {
    it('executes without error', function (): void {
        $this->plugin->registerMetaField();
        expect(true)->toBeTrue();
    });
});

describe('renderDashboardWidget', function (): void {
    it('outputs div wrapper', function (): void {
        ob_start();
        $this->dashboard->renderDashboardWidget();
        $output = ob_get_clean();

        expect($output)->toContain('writing-status-widget');
    });

    it('outputs no drafts message when empty', function (): void {
        ob_start();
        $this->dashboard->renderDashboardWidget();
        $output = ob_get_clean();

        expect($output)->toContain('No drafts found');
    });
});

describe('renderDashboardIncompletePosts (protected, via ReflectionMethod)', function (): void {
    it('produces no output when query is empty', function (): void {
        $query = new WP_Query();

        $method = new ReflectionMethod(WritingStatus::class, 'renderDashboardIncompletePosts');
        $method->setAccessible(true);

        ob_start();
        $method->invokeArgs($this->plugin, [$query]);
        $output = ob_get_clean();

        expect($output)->toBe('');
    });

    it('outputs section when posts exist', function (): void {
        $query = new WP_Query();
        $query->set_posts([1]);

        $method = new ReflectionMethod(WritingStatus::class, 'renderDashboardIncompletePosts');
        $method->setAccessible(true);

        ob_start();
        $method->invokeArgs($this->plugin, [$query]);
        $output = ob_get_clean();

        expect($output)->toContain('<section');
        expect($output)->toContain('writing-status-incomplete');
    });
});

describe('renderDashboardCompletePosts (protected, via ReflectionMethod)', function (): void {
    it('produces no output when query is empty', function (): void {
        $query = new WP_Query();

        $method = new ReflectionMethod(WritingStatus::class, 'renderDashboardCompletePosts');
        $method->setAccessible(true);

        ob_start();
        $method->invokeArgs($this->plugin, [$query]);
        $output = ob_get_clean();

        expect($output)->toBe('');
    });

    it('outputs section when posts exist', function (): void {
        $query = new WP_Query();
        $query->set_posts([1]);

        $method = new ReflectionMethod(WritingStatus::class, 'renderDashboardCompletePosts');
        $method->setAccessible(true);

        ob_start();
        $method->invokeArgs($this->plugin, [$query]);
        $output = ob_get_clean();

        expect($output)->toContain('<section');
        expect($output)->toContain('writing-status-complete');
    });
});

describe('private renderDashboardPostSection (via ReflectionMethod)', function (): void {
    it('outputs section when posts exist', function (): void {
        $query = new WP_Query();
        $query->set_posts([1]);

        $method = new ReflectionMethod(WritingStatus::class, 'renderDashboardPostSection');
        $method->setAccessible(true);

        ob_start();
        $method->invokeArgs(
            $this->plugin,
            [$query, 'Test (%d)', 'test-id', 'test-class', '✓']
        );
        $output = ob_get_clean();

        expect($output)->toContain('<section');
    });

    it('includes section id in output', function (): void {
        $query = new WP_Query();
        $query->set_posts([1]);

        $method = new ReflectionMethod(WritingStatus::class, 'renderDashboardPostSection');
        $method->setAccessible(true);

        ob_start();
        $method->invokeArgs(
            $this->plugin,
            [$query, 'Test (%d)', 'my-section-id', 'my-class', '✗']
        );
        $output = ob_get_clean();

        expect($output)->toContain('my-section-id');
    });

    it('returns empty string when no posts', function (): void {
        $query = new WP_Query();

        $method = new ReflectionMethod(WritingStatus::class, 'renderDashboardPostSection');
        $method->setAccessible(true);

        ob_start();
        $method->invokeArgs(
            $this->plugin,
            [$query, 'Test (%d)', 'test-id', 'test-class', '✓']
        );
        $output = ob_get_clean();

        expect($output)->toBe('');
    });
});
