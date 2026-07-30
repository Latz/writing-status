<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Unit tests for WritingStatus meta box and dashboard widget registration methods.
 */

beforeEach(function (): void {
    $this->metaBox   = new WritingStatusMetaBox();
    $this->column    = new WritingStatusColumn();
    $this->dashboard = new WritingStatusDashboard();
});

describe('WritingStatus meta box / dashboard registration', function (): void {

    it('add completion meta box executes without error', function (): void {
        $this->metaBox->addCompletionMetaBox();
        expect(true)->toBeTrue();
    });

    it('add dashboard widget executes without error', function (): void {
        $this->dashboard->addDashboardWidget();
        expect(true)->toBeTrue();
    });

    it('add completion column adds writing_completion key', function (): void {
        $result = $this->column->addCompletionColumn(['title' => 'Title']);
        expect($result)->toHaveKey('writing_completion');
    });

    it('add completion column preserves existing columns', function (): void {
        $result = $this->column->addCompletionColumn(['title' => 'Title', 'date' => 'Date']);
        expect($result)->toHaveKey('title');
    });

    it('make completion sortable adds writing_completion', function (): void {
        $result = $this->column->makeCompletionSortable([]);
        expect($result)->toHaveKey('writing_completion');
        expect($result['writing_completion'])->toBe('writing_completion');
    });

    it('make completion sortable preserves existing', function (): void {
        $result = $this->column->makeCompletionSortable(['title' => 'Title']);
        expect($result)->toHaveKey('title');
    });
});
