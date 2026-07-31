<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Unit tests for WritingStatus::renderBulkEditBox() and
 * WritingStatus::renderQuickEditBox().
 */

beforeEach(function (): void {
    $this->plugin = new WritingStatus();
});

describe('WritingStatus::renderBulkEditBox()', function (): void {

    it('produces no output for the wrong column', function (): void {
        ob_start();
        $this->plugin->renderBulkEditBox('title', 'post');
        $output = ob_get_clean();

        expect($output)->toBe('');
    });

    it('produces no output for the wrong post type', function (): void {
        ob_start();
        $this->plugin->renderBulkEditBox('writing_completion', 'page');
        $output = ob_get_clean();

        expect($output)->toBe('');
    });

    it('outputs the bulk edit fieldset for post + writing_completion', function (): void {
        ob_start();
        $this->plugin->renderBulkEditBox('writing_completion', 'post');
        $output = ob_get_clean();

        expect($output)->toContain('writing-status-bulk-edit');
        expect($output)->toContain('writing_complete_bulk');
        expect($output)->toContain('writing_priority_bulk');
        expect($output)->toContain('writing_due_date_bulk');
    });
});

describe('WritingStatus::renderQuickEditBox()', function (): void {

    it('produces no output for the wrong column', function (): void {
        ob_start();
        $this->plugin->renderQuickEditBox('title', 'post');
        $output = ob_get_clean();

        expect($output)->toBe('');
    });

    it('produces no output for the wrong post type', function (): void {
        ob_start();
        $this->plugin->renderQuickEditBox('writing_completion', 'page');
        $output = ob_get_clean();

        expect($output)->toBe('');
    });

    it('outputs the quick edit fieldset for post + writing_completion', function (): void {
        ob_start();
        $this->plugin->renderQuickEditBox('writing_completion', 'post');
        $output = ob_get_clean();

        expect($output)->toContain('writing-status-quick-edit');
        expect($output)->toContain('name="writing_complete"');
        expect($output)->toContain('name="writing_priority"');
        expect($output)->toContain('name="writing_due_date"');
    });
});
