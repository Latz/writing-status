<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Unit tests for WritingStatusMetaBox::addCompletionMetaBox() and
 * renderCompletionMetaBox(). The Complete/Incomplete toggle markup lives in
 * renderDraftStatusRow() instead — see RenderDraftStatusRowTest.php.
 *
 * get_post_status() must be mocked per-test (it's intentionally left
 * undefined in tests/stubs/wp-stubs.php).
 */

use Brain\Monkey\Functions;

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

describe('WritingStatusMetaBox::addCompletionMetaBox()', function (): void {

    it('registers the meta box without error', function (): void {
        $this->plugin->addCompletionMetaBox();
        expect(true)->toBeTrue();
    });
});

describe('WritingStatusMetaBox::renderCompletionMetaBox()', function (): void {

    it('draft post verifies the completion nonce', function (): void {
        Functions\when('get_post_status')->justReturn('draft');
        Functions\expect('wp_nonce_field')
            ->once()
            ->with('writing_completion_nonce', 'writing_completion_nonce_field');

        ($this->captureOutput)(($this->makePost)());
    });

    it('draft post outputs due date field', function (): void {
        Functions\when('get_post_status')->justReturn('draft');

        $output = ($this->captureOutput)(($this->makePost)());

        expect($output)->toContain('writing_due_date');
    });

    it('draft post outputs priority select', function (): void {
        Functions\when('get_post_status')->justReturn('draft');

        $output = ($this->captureOutput)(($this->makePost)());

        expect($output)->toContain('writing_priority');
    });

    it('published post outputs the published indicator and nothing else', function (): void {
        Functions\when('get_post_status')->justReturn('publish');

        $output = ($this->captureOutput)(($this->makePost)());

        expect($output)->toContain('writing-status-published');
        expect($output)->toContain('Published');
        expect($output)->not->toContain('writing_due_date');
        expect($output)->not->toContain('writing_priority');
    });
});
