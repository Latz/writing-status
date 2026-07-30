<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Unit tests for WritingStatus::addCompletionFilterDropdown().
 */

beforeEach(function (): void {
    $this->plugin = new WritingStatusFilters();
});

afterEach(function (): void {
    unset($_GET['writing_completion_filter'], $_GET['writing_priority_filter']);
});

describe('WritingStatusFilters::addCompletionFilterDropdown()', function (): void {

    it('wrong post type produces no output', function (): void {
        ob_start();
        $this->plugin->addCompletionFilterDropdown('page');
        $output = ob_get_clean();

        expect($output)->toBe('');
    });

    it('post type outputs completion select', function (): void {
        ob_start();
        $this->plugin->addCompletionFilterDropdown('post');
        $output = ob_get_clean();

        expect($output)->toContain('writing_completion_filter');
    });

    it('post type outputs priority select', function (): void {
        ob_start();
        $this->plugin->addCompletionFilterDropdown('post');
        $output = ob_get_clean();

        expect($output)->toContain('writing_priority_filter');
    });

    it('outputs complete and incomplete options', function (): void {
        ob_start();
        $this->plugin->addCompletionFilterDropdown('post');
        $output = ob_get_clean();

        expect($output)->toContain('complete');
        expect($output)->toContain('incomplete');
    });

    it('outputs priority options', function (): void {
        ob_start();
        $this->plugin->addCompletionFilterDropdown('post');
        $output = ob_get_clean();

        expect($output)->toContain('urgent');
        expect($output)->toContain('high');
    });
});
