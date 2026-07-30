<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Unit tests for WritingStatus::addCompletionColumn().
 */

beforeEach(function (): void {
    $this->plugin = new WritingStatusColumn();
});

describe('WritingStatusColumn::addCompletionColumn()', function (): void {

    it('adds writing_completion key to columns', function (): void {
        $result = $this->plugin->addCompletionColumn([]);
        expect($result)->toHaveKey('writing_completion');
    });

    it('preserves existing columns', function (): void {
        $existing = ['title' => 'Title', 'date' => 'Date'];
        $result   = $this->plugin->addCompletionColumn($existing);

        expect($result)->toHaveKey('title');
        expect($result)->toHaveKey('date');
    });

    it('column label is writing status', function (): void {
        $result = $this->plugin->addCompletionColumn([]);
        expect($result['writing_completion'])->toBe('Writing Status');
    });

    it('works with empty columns array', function (): void {
        $result = $this->plugin->addCompletionColumn([]);
        expect($result)->toHaveCount(1);
    });
});
