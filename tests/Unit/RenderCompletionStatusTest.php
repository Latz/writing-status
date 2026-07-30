<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use Brain\Monkey\Functions;

/**
 * Unit tests for WritingStatusRenderer::renderCompletionStatus() — output verification.
 */

beforeEach(function (): void {
    $this->plugin = new WritingStatus();

    $method = new ReflectionMethod(WritingStatus::class, 'renderCompletionStatus');
    $method->setAccessible(true);

    $this->call = function (string $status) use ($method): string {
        ob_start();
        $method->invoke($this->plugin, $status);
        return ob_get_clean();
    };
});

describe('WritingStatusRenderer::renderCompletionStatus()', function (): void {

    it('complete status outputs complete span', function (): void {
        Functions\when('esc_attr__')->returnArg();
        Functions\when('esc_html__')->returnArg();

        $output = ($this->call)('yes');

        expect($output)->toContain('writing-status-complete');
    });

    it('complete status outputs checkmark', function (): void {
        Functions\when('esc_attr__')->returnArg();
        Functions\when('esc_html__')->returnArg();

        $output = ($this->call)('yes');

        expect($output)->toContain('✓');
    });

    it('incomplete status outputs incomplete span', function (): void {
        Functions\when('esc_attr__')->returnArg();
        Functions\when('esc_html__')->returnArg();

        $output = ($this->call)('no');

        expect($output)->toContain('writing-status-incomplete');
    });

    it('incomplete status outputs x symbol', function (): void {
        Functions\when('esc_attr__')->returnArg();
        Functions\when('esc_html__')->returnArg();

        $output = ($this->call)('no');

        expect($output)->toContain('✗');
    });

    it('empty string status outputs incomplete span', function (): void {
        Functions\when('esc_attr__')->returnArg();
        Functions\when('esc_html__')->returnArg();

        $output = ($this->call)('');

        expect($output)->toContain('writing-status-incomplete');
    });
});
