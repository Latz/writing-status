<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Unit tests for WritingStatus::saveDraftDueDate() and WritingStatus::saveDraftPriority().
 *
 * Both methods are protected. They are exercised via ReflectionMethod.
 * update_post_meta, delete_post_meta, sanitize_text_field, and wp_unslash are
 * defined as real no-op stubs in tests/stubs/wp-stubs.php, so these tests
 * assert that each code path executes without error rather than verifying
 * call arguments.
 */

beforeEach(function (): void {
    $this->plugin = new WritingStatus();

    $this->saveDraftDueDate = new ReflectionMethod(WritingStatus::class, 'saveDraftDueDate');
    $this->saveDraftDueDate->setAccessible(true);

    $this->saveDraftPriority = new ReflectionMethod(WritingStatus::class, 'saveDraftPriority');
    $this->saveDraftPriority->setAccessible(true);
});

afterEach(function (): void {
    unset($_POST['writing_due_date'], $_POST['writing_priority']);
});

describe('WritingStatus::saveDraftDueDate()', function (): void {

    it('valid date executes without error', function (): void {
        $_POST['writing_due_date'] = '2026-12-31';

        $this->saveDraftDueDate->invoke($this->plugin, 1);

        expect(true)->toBeTrue();
    });

    it('empty string executes without error', function (): void {
        $_POST['writing_due_date'] = '';

        $this->saveDraftDueDate->invoke($this->plugin, 1);

        expect(true)->toBeTrue();
    });

    it('invalid format executes without error', function (): void {
        $_POST['writing_due_date'] = 'not-a-date';

        $this->saveDraftDueDate->invoke($this->plugin, 1);

        expect(true)->toBeTrue();
    });

    it('without post key executes without error', function (): void {
        $this->saveDraftDueDate->invoke($this->plugin, 1);

        expect(true)->toBeTrue();
    });
});

describe('WritingStatus::saveDraftPriority()', function (): void {

    it('valid priority executes without error', function (): void {
        $_POST['writing_priority'] = 'high';

        $this->saveDraftPriority->invoke($this->plugin, 1);

        expect(true)->toBeTrue();
    });

    it('invalid priority executes without error', function (): void {
        $_POST['writing_priority'] = 'invalid';

        $this->saveDraftPriority->invoke($this->plugin, 1);

        expect(true)->toBeTrue();
    });

    it('none executes without error', function (): void {
        $_POST['writing_priority'] = 'none';

        $this->saveDraftPriority->invoke($this->plugin, 1);

        expect(true)->toBeTrue();
    });

    it('without post key executes without error', function (): void {
        $this->saveDraftPriority->invoke($this->plugin, 1);

        expect(true)->toBeTrue();
    });
});
