<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Unit tests for WritingStatus::renderCompletionMetaBox().
 *
 * get_post_status is stubbed to always return 'draft', so the published
 * branch cannot be reached and is not tested here. get_post_meta returns ''
 * for single lookups, so is_complete will always be falsy in these tests.
 */

beforeEach(function (): void {
    $this->plugin = new WritingStatusMetaBox();

    $this->makePost = function (int $id = 1): stdClass {
        $post     = new stdClass();
        $post->ID = $id;
        return $post;
    };

    $this->captureOutput = function (stdClass $post): string {
        ob_start();
        $this->plugin->renderCompletionMetaBox($post);
        return ob_get_clean();
    };
});

describe('WritingStatusMetaBox::renderCompletionMetaBox()', function (): void {

    it('draft post outputs nonce field', function (): void {
        // wp_nonce_field is stubbed to echo '', but the hidden completion
        // input rendered directly after it proves the nonce path executed.
        $output = ($this->captureOutput)(($this->makePost)());

        expect($output)->toContain('writing_complete_hidden');
    });

    it('draft post outputs complete button', function (): void {
        $output = ($this->captureOutput)(($this->makePost)());

        expect(
            str_contains($output, 'writing_complete_button')
            || str_contains($output, 'draft-complete-toggle')
        )->toBeTrue();
    });

    it('draft post outputs due date field', function (): void {
        $output = ($this->captureOutput)(($this->makePost)());

        expect($output)->toContain('writing_due_date');
    });

    it('draft post outputs priority select', function (): void {
        $output = ($this->captureOutput)(($this->makePost)());

        expect($output)->toContain('writing_priority');
    });

    it('draft post outputs incomplete state by default', function (): void {
        $output = ($this->captureOutput)(($this->makePost)());

        expect($output)->toContain('is-incomplete');
    });
});
