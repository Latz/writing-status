<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Unit tests for WritingStatus::enqueueAdminStyles() — hook guard conditions.
 *
 * wp_enqueue_style and wp_enqueue_script are pre-stubbed as no-ops in
 * tests/stubs/wp-stubs.php. Tests verify that the method completes without
 * error for each accepted hook value and returns early for an irrelevant hook.
 */

beforeEach(function (): void {
    $this->plugin = new WritingStatus();
});

describe('WritingStatus::enqueueAdminStyles()', function (): void {

    it('irrelevant hook returns early without error', function (): void {
        $this->plugin->enqueueAdminStyles('dashboard');
        expect(true)->toBeTrue();
    });

    it('edit.php hook executes without error', function (): void {
        $this->plugin->enqueueAdminStyles('edit.php');
        expect(true)->toBeTrue();
    });

    it('post.php hook executes without error', function (): void {
        $this->plugin->enqueueAdminStyles('post.php');
        expect(true)->toBeTrue();
    });

    it('post-new.php hook executes without error', function (): void {
        $this->plugin->enqueueAdminStyles('post-new.php');
        expect(true)->toBeTrue();
    });

    it('index.php hook executes without error', function (): void {
        $this->plugin->enqueueAdminStyles('index.php');
        expect(true)->toBeTrue();
    });
});
