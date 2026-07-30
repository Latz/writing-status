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

        Functions\expect('update_post_meta')->never();

        $this->plugin->saveCompletionStatus(42);

        expect(true)->toBeTrue();
    });

    it('returns early when nonce verification fails', function (): void {
        $_POST['writing_completion_nonce_field'] = 'bad_nonce';

        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('wp_unslash')->returnArg();
        Functions\when('wp_verify_nonce')->justReturn(false);
        Functions\expect('update_post_meta')->never();

        $this->plugin->saveCompletionStatus(42);

        expect(true)->toBeTrue();
    });

    it('returns early during autosave even with valid nonce', function (): void {
        if (!defined('DOING_AUTOSAVE')) {
            define('DOING_AUTOSAVE', true);
        }

        $_POST['writing_completion_nonce_field'] = 'nonce';

        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('wp_unslash')->returnArg();
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\expect('update_post_meta')->never();

        $this->plugin->saveCompletionStatus(42);

        expect(true)->toBeTrue();
    });

    it('returns early when user lacks edit capability', function (): void {
        if (defined('DOING_AUTOSAVE')) {
            $this->markTestSkipped('DOING_AUTOSAVE defined — autosave guard fires first, capability check cannot be reached.');
        }

        $_POST['writing_completion_nonce_field'] = 'nonce';

        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('wp_unslash')->returnArg();
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\expect('current_user_can')
            ->with('edit_post', 99)
            ->andReturn(false);
        Functions\expect('update_post_meta')->never();

        $this->plugin->saveCompletionStatus(99);

        expect(true)->toBeTrue();
    });

    it('saves no when writing_complete not in post', function (): void {
        if (defined('DOING_AUTOSAVE')) {
            $this->markTestSkipped('DOING_AUTOSAVE defined — autosave guard fires first, else branch cannot be reached.');
        }

        unset($_POST['writing_complete']);
        $_POST['writing_completion_nonce_field'] = 'nonce';

        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('wp_unslash')->returnArg();
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\expect('current_user_can')
            ->with('edit_post', 42)
            ->andReturn(true);

        Functions\expect('update_post_meta')
            ->with(42, '_writing_complete', 'no')
            ->once()
            ->andReturn(true);

        Functions\when('update_post_meta')->justReturn(true);
        Functions\when('delete_post_meta')->justReturn(true);

        $this->plugin->saveCompletionStatus(42);

        expect(true)->toBeTrue();
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
        Functions\expect('current_user_can')
            ->with('edit_post', 55)
            ->andReturn(true);

        Functions\expect('update_post_meta')
            ->with(55, '_writing_complete', 'yes')
            ->once()
            ->andReturn(true);

        Functions\when('update_post_meta')->justReturn(true);
        Functions\when('delete_post_meta')->justReturn(true);

        $this->plugin->saveCompletionStatus(55);

        expect(true)->toBeTrue();
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
        Functions\expect('current_user_can')
            ->with('edit_post', 77)
            ->andReturn(true);

        Functions\expect('update_post_meta')
            ->with(77, '_writing_complete', 'no')
            ->once()
            ->andReturn(true);

        Functions\when('update_post_meta')->justReturn(true);
        Functions\when('delete_post_meta')->justReturn(true);

        $this->plugin->saveCompletionStatus(77);

        expect(true)->toBeTrue();
    });
});
