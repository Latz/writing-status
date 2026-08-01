<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Unit tests for WritingStatus::getDueDateDisplay() (private, called via
 * reflection). Covers overdue, due-today, due-soon, and due-later states.
 */

beforeEach(function (): void {
    $this->plugin = new WritingStatus();

    $ref          = new ReflectionClass(WritingStatus::class);
    $method       = $ref->getMethod('getDueDateDisplay');

    $this->call = fn (string $date): array => $method->invoke($this->plugin, $date);
});

describe('WritingStatus::getDueDateDisplay()', function (): void {

    it('overdue date returns overdue css class', function (): void {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $result    = ($this->call)($yesterday);

        expect($result['class'])->toContain('draft-due-overdue');
    });

    it('overdue date label contains overdue text', function (): void {
        $yesterday = date('Y-m-d', strtotime('-7 days'));
        $result    = ($this->call)($yesterday);

        expect($result['label'])->toContain('Overdue');
    });

    it('today returns due today css class', function (): void {
        $today  = date('Y-m-d');
        $result = ($this->call)($today);

        expect($result['class'])->toContain('draft-due-today');
    });

    it('today label says due today', function (): void {
        $today  = date('Y-m-d');
        $result = ($this->call)($today);

        expect($result['label'])->toBe('Due today');
    });

    it('three days away returns due soon css class', function (): void {
        $soon   = date('Y-m-d', strtotime('+3 days'));
        $result = ($this->call)($soon);

        expect($result['class'])->toContain('draft-due-soon');
    });

    it('one day away returns due soon css class', function (): void {
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $result   = ($this->call)($tomorrow);

        expect($result['class'])->toContain('draft-due-soon');
    });

    it('four days away returns base class only', function (): void {
        $later  = date('Y-m-d', strtotime('+4 days'));
        $result = ($this->call)($later);

        expect($result['class'])->not->toContain('draft-due-overdue');
        expect($result['class'])->not->toContain('draft-due-today');
        expect($result['class'])->not->toContain('draft-due-soon');
        expect($result['class'])->toContain('draft-due-date');
    });

    it('four days away label contains due prefix', function (): void {
        $later  = date('Y-m-d', strtotime('+4 days'));
        $result = ($this->call)($later);

        expect($result['label'])->toContain('Due:');
    });

    it('result always contains class and label keys', function (): void {
        foreach (['-5 days', 'today', '+2 days', '+10 days'] as $offset) {
            $date   = date('Y-m-d', strtotime($offset));
            $result = ($this->call)($date);

            expect($result)->toHaveKey('class');
            expect($result)->toHaveKey('label');
        }
    });
});
