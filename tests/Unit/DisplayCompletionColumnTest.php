<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use Brain\Monkey\Functions;

/**
 * Unit tests for WritingStatus::displayCompletionColumn().
 */

beforeEach(function (): void {
    $this->plugin = new WritingStatusColumn();
});

describe('WritingStatusColumn::displayCompletionColumn()', function (): void {

    it('wrong column produces no output', function (): void {
        ob_start();
        $this->plugin->displayCompletionColumn('title', 1);
        $output = ob_get_clean();

        expect($output)->toBe('');
    });

    it('draft column calls completion status render', function (): void {
        // get_post_status stub returns 'draft', get_post_meta stub returns ''
        // for single lookups, so is_complete is '' → incomplete branch fires.
        Functions\when('esc_attr__')->returnArg();
        Functions\when('esc_html__')->returnArg();

        ob_start();
        $this->plugin->displayCompletionColumn('writing_completion', 1);
        $output = ob_get_clean();

        expect($output)->toContain('writing-status-incomplete');
    });

    it('writing complete post shows complete span', function (): void {
        // get_post_meta stub can't easily be overridden per-call here, so we
        // call renderCompletionStatus directly via reflection to verify the
        // 'complete' path.
        $method = new ReflectionMethod(WritingStatus::class, 'renderCompletionStatus');
        $method->setAccessible(true);

        Functions\when('esc_attr__')->returnArg();
        Functions\when('esc_html__')->returnArg();

        ob_start();
        $method->invoke($this->plugin, 'yes');
        $output = ob_get_clean();

        expect($output)->toContain('writing-status-complete');
    });

    it('draft with priority shows priority badge', function (): void {
        $method = new ReflectionMethod(WritingStatus::class, 'renderPriorityBadge');
        $method->setAccessible(true);

        Functions\when('__')->returnArg();
        Functions\when('esc_attr')->returnArg();
        Functions\when('esc_html')->returnArg();

        ob_start();
        $method->invoke($this->plugin, 'high');
        $output = ob_get_clean();

        expect($output)->toContain('draft-priority-high');
    });

    it('draft incomplete shows incomplete span', function (): void {
        Functions\when('esc_attr__')->returnArg();
        Functions\when('esc_html__')->returnArg();

        ob_start();
        $this->plugin->displayCompletionColumn('writing_completion', 1);
        $output = ob_get_clean();

        expect($output)->toContain('writing-status-incomplete');
    });

    it('draft column outputs no due date for empty meta', function (): void {
        Functions\when('esc_attr__')->returnArg();
        Functions\when('esc_html__')->returnArg();

        ob_start();
        $this->plugin->displayCompletionColumn('writing_completion', 1);
        $output = ob_get_clean();

        expect($output)->not->toContain('draft-due-date');
    });

    it('draft column outputs no priority badge for empty meta', function (): void {
        Functions\when('esc_attr__')->returnArg();
        Functions\when('esc_html__')->returnArg();

        ob_start();
        $this->plugin->displayCompletionColumn('writing_completion', 1);
        $output = ob_get_clean();

        expect($output)->not->toContain('draft-priority');
    });
});
