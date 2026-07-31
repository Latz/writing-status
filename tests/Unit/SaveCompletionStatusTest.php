<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use Brain\Monkey\Functions;

/**
 * Unit tests for WritingStatus::saveCompletionStatus() — security guards.
 *
 * Tests that the method bails early without writing post meta under the
 * three security conditions: missing nonce, autosave, insufficient capability.
 */

beforeEach(function (): void {
    $this->plugin = new WritingStatusMetaBox();
});

afterEach(function (): void {
    unset(
        $_POST['writing_completion_nonce_field'],
        $_POST['writing_complete'],
        $_POST['writing_due_date'],
        $_POST['writing_priority']
    );
});

describe('WritingStatusMetaBox::saveCompletionStatus()', function (): void {

    it('returns early when nonce field is missing', function (): void {
        unset($_POST['writing_completion_nonce_field']);

        $updateCalls = [];
        Functions\when('update_post_meta')->alias(function (...$args) use (&$updateCalls) {
            $updateCalls[] = $args;
            return true;
        });

        $this->plugin->saveCompletionStatus(42);

        expect($updateCalls)->toBe([]);
    });

    it('returns early when nonce verification fails', function (): void {
        $_POST['writing_completion_nonce_field'] = 'bad_nonce';

        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('wp_unslash')->returnArg();
        Functions\when('wp_verify_nonce')->justReturn(false);

        $updateCalls = [];
        Functions\when('update_post_meta')->alias(function (...$args) use (&$updateCalls) {
            $updateCalls[] = $args;
            return true;
        });

        $this->plugin->saveCompletionStatus(42);

        expect($updateCalls)->toBe([]);
    });

    it('returns early when user lacks edit capability', function (): void {
        if (defined('DOING_AUTOSAVE')) {
            $this->markTestSkipped('DOING_AUTOSAVE defined — autosave guard fires first, capability check cannot be reached.');
        }

        $_POST['writing_completion_nonce_field'] = 'nonce';

        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('wp_unslash')->returnArg();
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('current_user_can')->justReturn(false);

        $updateCalls = [];
        Functions\when('update_post_meta')->alias(function (...$args) use (&$updateCalls) {
            $updateCalls[] = $args;
            return true;
        });

        $this->plugin->saveCompletionStatus(99);

        expect($updateCalls)->toBe([]);
    });

    // The three "saves ..." tests below must run before "returns early during
    // autosave" — DOING_AUTOSAVE is a process-global constant, so once that
    // test defines it, every later test in the process hits the autosave
    // guard first and never reaches the save branch (they self-skip via
    // markTestSkipped as a result). Keeping these first is what lets them
    // actually exercise saveCompletionStatus()'s full success path,
    // including the delete_transient() call at the end of the method.

    it('saves no when writing_complete not in post', function (): void {
        if (defined('DOING_AUTOSAVE')) {
            $this->markTestSkipped('DOING_AUTOSAVE defined — autosave guard fires first, else branch cannot be reached.');
        }

        unset($_POST['writing_complete']);
        $_POST['writing_completion_nonce_field'] = 'nonce';

        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('wp_unslash')->returnArg();
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('current_user_can')->justReturn(true);

        $updateCalls = [];
        Functions\when('update_post_meta')->alias(function (...$args) use (&$updateCalls) {
            $updateCalls[] = $args;
            return true;
        });
        Functions\when('delete_post_meta')->justReturn(true);

        $transientCalls = [];
        Functions\when('delete_transient')->alias(function (...$args) use (&$transientCalls) {
            $transientCalls[] = $args;
            return true;
        });

        $this->plugin->saveCompletionStatus(42);

        expect($updateCalls)->toContain([42, '_writing_complete', 'no']);
        expect($transientCalls)->toBe([['writing_status_overdue_count']]);
    });

    it('saves yes when writing_complete is yes in post', function (): void {
        if (defined('DOING_AUTOSAVE')) {
            $this->markTestSkipped('DOING_AUTOSAVE defined — autosave guard fires first, save branch cannot be reached.');
        }

        $_POST['writing_completion_nonce_field'] = 'nonce';
        $_POST['writing_complete']               = 'yes';

        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('wp_unslash')->returnArg();
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('current_user_can')->justReturn(true);

        $updateCalls = [];
        Functions\when('update_post_meta')->alias(function (...$args) use (&$updateCalls) {
            $updateCalls[] = $args;
            return true;
        });
        Functions\when('delete_post_meta')->justReturn(true);
        Functions\when('delete_transient')->justReturn(true);

        $this->plugin->saveCompletionStatus(55);

        expect($updateCalls)->toContain([55, '_writing_complete', 'yes']);
    });

    it('saves no when writing_complete value is invalid', function (): void {
        if (defined('DOING_AUTOSAVE')) {
            $this->markTestSkipped('DOING_AUTOSAVE defined — autosave guard fires first, save branch cannot be reached.');
        }

        $_POST['writing_completion_nonce_field'] = 'nonce';
        $_POST['writing_complete']               = 'maybe';

        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('wp_unslash')->returnArg();
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('current_user_can')->justReturn(true);

        $updateCalls = [];
        Functions\when('update_post_meta')->alias(function (...$args) use (&$updateCalls) {
            $updateCalls[] = $args;
            return true;
        });
        Functions\when('delete_post_meta')->justReturn(true);
        Functions\when('delete_transient')->justReturn(true);

        $this->plugin->saveCompletionStatus(77);

        expect($updateCalls)->toContain([77, '_writing_complete', 'no']);
    });

    it('returns early during autosave even with valid nonce', function (): void {
        if (!defined('DOING_AUTOSAVE')) {
            define('DOING_AUTOSAVE', true);
        }

        $_POST['writing_completion_nonce_field'] = 'nonce';

        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('wp_unslash')->returnArg();
        Functions\when('wp_verify_nonce')->justReturn(1);

        $updateCalls = [];
        Functions\when('update_post_meta')->alias(function (...$args) use (&$updateCalls) {
            $updateCalls[] = $args;
            return true;
        });

        $this->plugin->saveCompletionStatus(42);

        expect($updateCalls)->toBe([]);
    });
});
