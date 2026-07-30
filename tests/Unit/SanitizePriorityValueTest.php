<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Unit tests for WritingStatus::sanitizePriorityValue().
 */

beforeEach(function (): void {
    $this->plugin = new WritingStatus();
});

describe('WritingStatus::sanitizePriorityValue()', function (): void {

    it('returns value unchanged for each valid priority', function (): void {
        foreach (['none', 'low', 'medium', 'high', 'urgent'] as $priority) {
            expect($this->plugin->sanitizePriorityValue($priority))->toBe($priority);
        }
    });

    it('returns none for unknown string', function (): void {
        expect($this->plugin->sanitizePriorityValue('critical'))->toBe('none');
    });

    it('returns none for empty string', function (): void {
        expect($this->plugin->sanitizePriorityValue(''))->toBe('none');
    });

    it('returns none for sql injection attempt', function (): void {
        expect($this->plugin->sanitizePriorityValue("' OR 1=1 --"))->toBe('none');
    });

    it('is case sensitive and rejects uppercase', function (): void {
        expect($this->plugin->sanitizePriorityValue('URGENT'))->toBe('none');
        expect($this->plugin->sanitizePriorityValue('High'))->toBe('none');
    });
});
