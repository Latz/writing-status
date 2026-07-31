<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Unit tests for WritingStatus::enqueueBlockEditorAssets().
 *
 * wp_enqueue_script is stubbed as a no-op in tests/stubs/wp-stubs.php.
 */

beforeEach(function (): void {
    $this->plugin = new WritingStatus();
});

describe('WritingStatus::enqueueBlockEditorAssets()', function (): void {

    it('executes without error', function (): void {
        $this->plugin->enqueueBlockEditorAssets();
        expect(true)->toBeTrue();
    });
});
