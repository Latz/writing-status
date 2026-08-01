<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use Brain\Monkey\Functions;

/**
 * Unit tests for WritingStatus::saveDraftDueDate() and WritingStatus::saveDraftPriority().
 *
 * Both methods are protected and exercised via ReflectionMethod. Spies on
 * update_post_meta/delete_post_meta assert the actual meta key/value written
 * (or that nothing was written), rather than just that the call didn't throw.
 */

beforeEach(function (): void {
    $this->plugin = new WritingStatus();

    $this->saveDraftDueDate = new ReflectionMethod(WritingStatus::class, 'saveDraftDueDate');

    $this->saveDraftPriority = new ReflectionMethod(WritingStatus::class, 'saveDraftPriority');

    $this->updateCalls = [];
    Functions\when('update_post_meta')->alias(function (...$args) {
        $this->updateCalls[] = $args;
        return true;
    });

    $this->deleteCalls = [];
    Functions\when('delete_post_meta')->alias(function (...$args) {
        $this->deleteCalls[] = $args;
        return true;
    });
});

afterEach(function (): void {
    unset($_POST['writing_due_date'], $_POST['writing_priority']);
});

describe('WritingStatus::saveDraftDueDate()', function (): void {

    it('saves a valid date', function (): void {
        $_POST['writing_due_date'] = '2026-12-31';

        $this->saveDraftDueDate->invoke($this->plugin, 1);

        expect($this->updateCalls)->toBe([[1, '_writing_due_date', '2026-12-31']]);
        expect($this->deleteCalls)->toBe([]);
    });

    it('deletes the meta when the date is cleared', function (): void {
        $_POST['writing_due_date'] = '';

        $this->saveDraftDueDate->invoke($this->plugin, 1);

        expect($this->deleteCalls)->toBe([[1, '_writing_due_date']]);
        expect($this->updateCalls)->toBe([]);
    });

    it('writes nothing for an invalid, non-empty format', function (): void {
        $_POST['writing_due_date'] = 'not-a-date';

        $this->saveDraftDueDate->invoke($this->plugin, 1);

        expect($this->updateCalls)->toBe([]);
        expect($this->deleteCalls)->toBe([]);
    });

    it('writes nothing when the field is absent', function (): void {
        $this->saveDraftDueDate->invoke($this->plugin, 1);

        expect($this->updateCalls)->toBe([]);
        expect($this->deleteCalls)->toBe([]);
    });
});

describe('WritingStatus::saveDraftPriority()', function (): void {

    it('saves a valid priority as-is', function (): void {
        $_POST['writing_priority'] = 'high';

        $this->saveDraftPriority->invoke($this->plugin, 1);

        expect($this->updateCalls)->toBe([[1, '_writing_priority', 'high']]);
    });

    it('falls back to none for an invalid priority', function (): void {
        $_POST['writing_priority'] = 'invalid';

        $this->saveDraftPriority->invoke($this->plugin, 1);

        expect($this->updateCalls)->toBe([[1, '_writing_priority', 'none']]);
    });

    it('saves none as-is', function (): void {
        $_POST['writing_priority'] = 'none';

        $this->saveDraftPriority->invoke($this->plugin, 1);

        expect($this->updateCalls)->toBe([[1, '_writing_priority', 'none']]);
    });

    it('writes nothing when the field is absent', function (): void {
        $this->saveDraftPriority->invoke($this->plugin, 1);

        expect($this->updateCalls)->toBe([]);
    });
});
