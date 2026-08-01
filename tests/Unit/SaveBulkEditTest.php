<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use Brain\Monkey\Functions;

/**
 * Unit tests for WritingStatus::saveBulkEdit() and its private guard
 * isValidBulkEditRequest().
 *
 * update_post_meta() is always mocked via Functions\when() + a captured
 * call list rather than Functions\expect()->with(), because Brain
 * Monkey's expect()->with() expectations have been observed to leak
 * across unrelated tests/files in this suite (registering a function
 * under the "expect" type blocks a later when() registration for the
 * same function elsewhere in the same process).
 */

beforeEach(function (): void {
    $this->plugin = new WritingStatus();
});

afterEach(function (): void {
    unset(
        $_REQUEST['_writing_status_bulk_nonce'],
        $_REQUEST['writing_complete_bulk'],
        $_REQUEST['writing_priority_bulk'],
        $_REQUEST['writing_due_date_bulk']
    );
});

describe('WritingStatus::saveBulkEdit()', function (): void {

    it('does nothing when nonce field is missing', function (): void {
        unset($_REQUEST['_writing_status_bulk_nonce']);

        $updateCalls = [];
        Functions\when('update_post_meta')->alias(function (...$args) use (&$updateCalls) {
            $updateCalls[] = $args;
            return true;
        });

        $this->plugin->saveBulkEdit(1);

        expect($updateCalls)->toBe([]);
    });

    it('does nothing when nonce verification fails', function (): void {
        $_REQUEST['_writing_status_bulk_nonce'] = 'bad-nonce';

        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('wp_unslash')->returnArg();
        Functions\when('wp_verify_nonce')->justReturn(false);

        $updateCalls = [];
        Functions\when('update_post_meta')->alias(function (...$args) use (&$updateCalls) {
            $updateCalls[] = $args;
            return true;
        });

        $this->plugin->saveBulkEdit(1);

        expect($updateCalls)->toBe([]);
    });

    it('does nothing when user lacks edit capability', function () {
        if (defined('DOING_AUTOSAVE')) {
            $this->markTestSkipped('DOING_AUTOSAVE defined in a prior test — capability guard cannot be isolated.');
        }

        $_REQUEST['_writing_status_bulk_nonce'] = 'nonce';

        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('wp_unslash')->returnArg();
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('current_user_can')->justReturn(false);

        $updateCalls = [];
        Functions\when('update_post_meta')->alias(function (...$args) use (&$updateCalls) {
            $updateCalls[] = $args;
            return true;
        });

        $this->plugin->saveBulkEdit(1);

        expect($updateCalls)->toBe([]);
    });

    it('saves completion, priority, and due date when all fields are valid', function () {
        if (defined('DOING_AUTOSAVE')) {
            $this->markTestSkipped('DOING_AUTOSAVE defined in a prior test — save path cannot be reached.');
        }

        $_REQUEST['_writing_status_bulk_nonce'] = 'nonce';
        $_REQUEST['writing_complete_bulk']      = 'yes';
        $_REQUEST['writing_priority_bulk']      = 'high';
        $_REQUEST['writing_due_date_bulk']      = '2026-12-31';

        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('wp_unslash')->returnArg();
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('current_user_can')->justReturn(true);

        $transientCalls = [];
        Functions\when('delete_transient')->alias(function (...$args) use (&$transientCalls) {
            $transientCalls[] = $args;
            return true;
        });

        $updateCalls = [];
        Functions\when('update_post_meta')->alias(function (...$args) use (&$updateCalls) {
            $updateCalls[] = $args;
            return true;
        });

        $this->plugin->saveBulkEdit(42);

        expect($updateCalls)->toContain([42, '_writing_complete', 'yes']);
        expect($updateCalls)->toContain([42, '_writing_priority', 'high']);
        expect($updateCalls)->toContain([42, '_writing_due_date', '2026-12-31']);
        expect($transientCalls)->toContain(['writing_status_overdue_count']);
        expect($transientCalls)->toContain(['writing_status_dashboard_html']);
    });

    it('skips priority update when priority is invalid', function () {
        if (defined('DOING_AUTOSAVE')) {
            $this->markTestSkipped('DOING_AUTOSAVE defined in a prior test — save path cannot be reached.');
        }

        $_REQUEST['_writing_status_bulk_nonce'] = 'nonce';
        $_REQUEST['writing_priority_bulk']      = 'critical';

        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('wp_unslash')->returnArg();
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('delete_transient')->justReturn(true);

        $updateCalls = [];
        Functions\when('update_post_meta')->alias(function (...$args) use (&$updateCalls) {
            $updateCalls[] = $args;
            return true;
        });

        $this->plugin->saveBulkEdit(42);

        expect($updateCalls)->toBe([]);
    });

    it('skips due date update when format is invalid', function () {
        if (defined('DOING_AUTOSAVE')) {
            $this->markTestSkipped('DOING_AUTOSAVE defined in a prior test — save path cannot be reached.');
        }

        $_REQUEST['_writing_status_bulk_nonce'] = 'nonce';
        $_REQUEST['writing_due_date_bulk']      = '31-12-2026';

        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('wp_unslash')->returnArg();
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('delete_transient')->justReturn(true);

        $updateCalls = [];
        Functions\when('update_post_meta')->alias(function (...$args) use (&$updateCalls) {
            $updateCalls[] = $args;
            return true;
        });

        $this->plugin->saveBulkEdit(42);

        expect($updateCalls)->toBe([]);
    });
});
