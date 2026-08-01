<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use Brain\Monkey\Functions;

/**
 * Unit tests for WritingStatusMetaBox::renderDraftStatusRow() — the
 * classic-editor Publish box "Draft status" link + Complete/Incomplete
 * toggle popup. Reads $post from the `global $post` set by WP core when
 * the post_submitbox_misc_actions hook fires, so tests set $GLOBALS['post']
 * directly rather than passing an argument.
 */

beforeEach(function (): void {
    $this->plugin = new WritingStatusMetaBox();

    $this->makePost = function (int $id = 1, string $post_type = 'post'): stdClass {
        $post            = new stdClass();
        $post->ID        = $id;
        $post->post_type = $post_type;
        return $post;
    };

    $this->captureOutput = function (): string {
        ob_start();
        $this->plugin->renderDraftStatusRow();
        return ob_get_clean();
    };
});

afterEach(function (): void {
    unset($GLOBALS['post']);
});

describe('WritingStatusMetaBox::renderDraftStatusRow()', function (): void {

    it('outputs nothing when there is no global post', function (): void {
        unset($GLOBALS['post']);

        expect(($this->captureOutput)())->toBe('');
    });

    it('outputs nothing for a non-post post type', function (): void {
        $GLOBALS['post'] = ($this->makePost)(1, 'page');

        expect(($this->captureOutput)())->toBe('');
    });

    it('outputs nothing once the post is no longer an actionable draft', function (): void {
        $GLOBALS['post'] = ($this->makePost)();
        Functions\when('get_post_status')->justReturn('publish');

        expect(($this->captureOutput)())->toBe('');
    });

    it('draft post defaults to Incomplete', function (): void {
        $GLOBALS['post'] = ($this->makePost)();
        Functions\when('get_post_status')->justReturn('draft');
        Functions\when('get_post_meta')->justReturn('');

        $output = ($this->captureOutput)();

        expect($output)->toContain('misc-pub-draft-status');
        expect($output)->toContain('writing-draft-status-toggle-link');
        expect($output)->toContain('is-incomplete-btn is-active');
        expect($output)->not->toContain('is-complete-btn is-active');
    });

    it('draft post marked Complete shows the Complete state active', function (): void {
        $GLOBALS['post'] = ($this->makePost)();
        Functions\when('get_post_status')->justReturn('draft');
        Functions\when('get_post_meta')->justReturn('yes');

        $output = ($this->captureOutput)();

        expect($output)->toContain('is-complete-btn is-active');
        expect($output)->not->toContain('is-incomplete-btn is-active');
    });

    it('includes the publish-warning data attribute for the classic-editor confirm', function (): void {
        $GLOBALS['post'] = ($this->makePost)();
        Functions\when('get_post_status')->justReturn('draft');
        Functions\when('get_post_meta')->justReturn('');

        expect(($this->captureOutput)())->toContain('data-publish-warning=');
    });
});
