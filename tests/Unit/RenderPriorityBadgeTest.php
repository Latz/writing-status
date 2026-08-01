<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use Brain\Monkey\Functions;

/**
 * Unit tests for WritingStatusRenderer::renderPriorityBadge() and
 * WritingStatusRenderer::renderPriorityBadgeForDashboard().
 */

beforeEach(function (): void {
    $this->plugin = new WritingStatus();

    $ref              = new ReflectionClass(WritingStatus::class);
    $badgeMethod      = $ref->getMethod('renderPriorityBadge');
    $dashboardMethod  = $ref->getMethod('renderPriorityBadgeForDashboard');

    $this->callBadge = function (string $priority) use ($badgeMethod): string {
        ob_start();
        $badgeMethod->invoke($this->plugin, $priority);
        return ob_get_clean();
    };

    $this->callDashboardBadge = function (string $priority) use ($dashboardMethod): string {
        ob_start();
        $dashboardMethod->invoke($this->plugin, $priority);
        return ob_get_clean();
    };
});

describe('WritingStatusRenderer::renderPriorityBadge()', function (): void {

    it('empty priority produces no output', function (): void {
        $output = ($this->callBadge)('');

        expect($output)->toBe('');
    });

    it('none priority produces no output', function (): void {
        $output = ($this->callBadge)('none');

        expect($output)->toBe('');
    });

    it('valid priority outputs badge span', function (): void {
        Functions\when('__')->returnArg();
        Functions\when('esc_attr')->returnArg();
        Functions\when('esc_html')->returnArg();

        $output = ($this->callBadge)('high');

        expect($output)->toContain('draft-priority-high');
    });

    it('unknown priority produces no output', function (): void {
        Functions\when('__')->returnArg();

        $output = ($this->callBadge)('invalid');

        expect($output)->toBe('');
    });
});

describe('WritingStatusRenderer::renderPriorityBadgeForDashboard()', function (): void {

    it('dashboard badge has no br tag', function (): void {
        Functions\when('__')->returnArg();
        Functions\when('esc_attr')->returnArg();
        Functions\when('esc_html')->returnArg();

        $output = ($this->callDashboardBadge)('urgent');

        expect($output)->not->toContain('<br>');
    });

    it('dashboard badge outputs span', function (): void {
        Functions\when('__')->returnArg();
        Functions\when('esc_attr')->returnArg();
        Functions\when('esc_html')->returnArg();

        $output = ($this->callDashboardBadge)('low');

        expect($output)->toContain('draft-priority-low');
    });
});
