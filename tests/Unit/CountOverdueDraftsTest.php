<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Unit tests for WritingStatus::countOverdueDrafts() (protected, via
 * ReflectionMethod). The WP_Query stub in tests/stubs/wp-stubs.php always
 * starts with found_posts = 0, so this verifies the method builds a query
 * and casts its found_posts to int without needing real query results.
 */

beforeEach(function (): void {
    $this->plugin = new WritingStatus();

    $method = new ReflectionMethod(WritingStatus::class, 'countOverdueDrafts');
    $method->setAccessible(true);

    $this->call = fn (): int => $method->invoke($this->plugin);
});

describe('WritingStatus::countOverdueDrafts()', function (): void {

    it('returns an integer', function (): void {
        $result = ($this->call)();

        expect($result)->toBeInt();
    });

    it('returns zero when no posts match the query', function (): void {
        $result = ($this->call)();

        expect($result)->toBe(0);
    });
});
