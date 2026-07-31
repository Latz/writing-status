<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use Brain\Monkey\Functions;

/**
 * Unit tests for the inline sanitize_callback/auth_callback closures
 * registered by WritingStatus::registerMetaField(). register_post_meta()
 * is a no-op stub, so we capture the args it's called with and invoke the
 * closures directly to exercise their logic.
 */

beforeEach(function (): void {
    $this->plugin = new WritingStatus();
    $this->calls  = [];

    Functions\when('register_post_meta')->alias(function (...$args) {
        $this->calls[] = $args;
    });

    $this->argsFor = function (string $metaKey): array {
        foreach ($this->calls as $call) {
            if ($call[1] === $metaKey) {
                return $call[2];
            }
        }
        throw new RuntimeException("No register_post_meta call found for {$metaKey}");
    };
});

describe('WritingStatus::registerMetaField() callbacks', function (): void {

    it('_writing_complete sanitize_callback maps yes to yes', function (): void {
        $this->plugin->registerMetaField();
        $args = ($this->argsFor)('_writing_complete');

        expect(($args['sanitize_callback'])('yes'))->toBe('yes');
    });

    it('_writing_complete sanitize_callback maps anything else to no', function (): void {
        $this->plugin->registerMetaField();
        $args = ($this->argsFor)('_writing_complete');

        expect(($args['sanitize_callback'])('maybe'))->toBe('no');
    });

    it('_writing_complete auth_callback defers to current_user_can', function (): void {
        Functions\when('current_user_can')->justReturn(true);

        $this->plugin->registerMetaField();
        $args = ($this->argsFor)('_writing_complete');

        expect(($args['auth_callback'])())->toBeTrue();
    });

    it('_writing_due_date sanitize_callback accepts a valid date', function (): void {
        $this->plugin->registerMetaField();
        $args = ($this->argsFor)('_writing_due_date');

        expect(($args['sanitize_callback'])('2026-12-31'))->toBe('2026-12-31');
    });

    it('_writing_due_date sanitize_callback accepts empty string', function (): void {
        $this->plugin->registerMetaField();
        $args = ($this->argsFor)('_writing_due_date');

        expect(($args['sanitize_callback'])(''))->toBe('');
    });

    it('_writing_due_date sanitize_callback rejects invalid format', function (): void {
        $this->plugin->registerMetaField();
        $args = ($this->argsFor)('_writing_due_date');

        expect(($args['sanitize_callback'])('31-12-2026'))->toBe('');
    });

    it('_writing_due_date auth_callback defers to current_user_can', function (): void {
        Functions\when('current_user_can')->justReturn(false);

        $this->plugin->registerMetaField();
        $args = ($this->argsFor)('_writing_due_date');

        expect(($args['auth_callback'])())->toBeFalse();
    });

    it('_writing_priority sanitize_callback is the plugin sanitizer', function (): void {
        $this->plugin->registerMetaField();
        $args = ($this->argsFor)('_writing_priority');

        expect($args['sanitize_callback'])->toBe([$this->plugin, 'sanitizePriorityValue']);
    });

    it('_writing_priority auth_callback defers to current_user_can', function (): void {
        Functions\when('current_user_can')->justReturn(true);

        $this->plugin->registerMetaField();
        $args = ($this->argsFor)('_writing_priority');

        expect(($args['auth_callback'])())->toBeTrue();
    });
});
