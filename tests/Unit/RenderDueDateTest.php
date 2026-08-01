<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use Brain\Monkey\Functions;

/**
 * Unit tests for WritingStatusRenderer::renderDueDate() — output guard and span rendering.
 */

beforeEach(function (): void {
    $this->plugin = new WritingStatus();

    $method = new ReflectionMethod(WritingStatus::class, 'renderDueDate');

    $this->call = function (string $date) use ($method): string {
        ob_start();
        $method->invoke($this->plugin, $date);
        return ob_get_clean();
    };
});

describe('WritingStatusRenderer::renderDueDate()', function (): void {

    it('empty due date produces no output', function (): void {
        $output = ($this->call)('');

        expect($output)->toBe('');
    });

    it('non empty due date outputs span', function (): void {
        $future_date = date('Y-m-d', strtotime('+10 days'));

        Functions\when('current_time')->justReturn(time());
        Functions\when('get_option')->justReturn('Y-m-d');
        Functions\when('date_i18n')->justReturn('2099-01-01');
        Functions\when('esc_attr')->returnArg();
        Functions\when('esc_html')->returnArg();
        Functions\when('esc_html__')->returnArg();

        $output = ($this->call)($future_date);

        expect($output)->toContain('<span');
    });

    it('future date span has due date class', function (): void {
        $future_date = date('Y-m-d', strtotime('+10 days'));

        Functions\when('current_time')->justReturn(time());
        Functions\when('get_option')->justReturn('Y-m-d');
        Functions\when('date_i18n')->justReturn('2099-01-01');
        Functions\when('esc_attr')->returnArg();
        Functions\when('esc_html')->returnArg();
        Functions\when('esc_html__')->returnArg();

        $output = ($this->call)($future_date);

        expect($output)->toContain('draft-due-date');
    });

    it('overdue date span has overdue class', function (): void {
        Functions\when('current_time')->justReturn(time());
        Functions\when('get_option')->justReturn('Y-m-d');
        Functions\when('date_i18n')->justReturn('2000-01-01');
        Functions\when('esc_attr')->returnArg();
        Functions\when('esc_html')->returnArg();
        Functions\when('esc_html__')->returnArg();

        $output = ($this->call)('2000-01-01');

        expect($output)->toContain('draft-due-overdue');
    });
});
