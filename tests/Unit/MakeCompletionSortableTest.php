<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Unit tests for WritingStatus::makeCompletionSortable().
 */

beforeEach(function (): void {
    $this->plugin = new WritingStatusColumn();
});

describe('WritingStatusColumn::makeCompletionSortable()', function (): void {

    it('adds writing_completion to sortable columns', function (): void {
        $result = $this->plugin->makeCompletionSortable([]);
        expect($result)->toHaveKey('writing_completion');
    });

    it('sortable key maps to writing_completion orderby', function (): void {
        $result = $this->plugin->makeCompletionSortable([]);
        expect($result['writing_completion'])->toBe('writing_completion');
    });

    it('preserves existing sortable columns', function (): void {
        $existing = ['title' => 'title', 'date' => 'date'];
        $result   = $this->plugin->makeCompletionSortable($existing);

        expect($result)->toHaveKey('title');
        expect($result)->toHaveKey('date');
    });
});
