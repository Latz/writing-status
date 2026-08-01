<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use Brain\Monkey\Functions;

/**
 * Unit tests for WritingStatus::showOverdueNotice().
 */

beforeEach(function (): void {
    $this->plugin = new WritingStatus();
});

describe('WritingStatus::showOverdueNotice()', function (): void {

    it('returns early when there is no current screen', function (): void {
        Functions\when('get_current_screen')->justReturn(false);

        ob_start();
        $this->plugin->showOverdueNotice();
        $output = ob_get_clean();

        expect($output)->toBe('');
    });

    it('returns early on an irrelevant screen', function (): void {
        $screen     = new stdClass();
        $screen->id = 'edit-page';

        Functions\when('get_current_screen')->justReturn($screen);

        ob_start();
        $this->plugin->showOverdueNotice();
        $output = ob_get_clean();

        expect($output)->toBe('');
    });

    it('outputs nothing when overdue count is zero', function (): void {
        $screen     = new stdClass();
        $screen->id = 'edit-post';

        Functions\when('get_current_screen')->justReturn($screen);
        Functions\when('get_transient')->justReturn(0);

        ob_start();
        $this->plugin->showOverdueNotice();
        $output = ob_get_clean();

        expect($output)->toBe('');
    });

    it('outputs a warning notice when overdue count is cached and positive', function (): void {
        $screen     = new stdClass();
        $screen->id = 'dashboard';

        Functions\when('get_current_screen')->justReturn($screen);
        Functions\when('get_transient')->justReturn(3);
        Functions\when('admin_url')->justReturn('http://example.com/wp-admin/edit.php');
        Functions\when('esc_url')->returnArg();
        Functions\when('_n')->justReturn('Writing Status: <strong>%1$d incomplete drafts are overdue.</strong> <a href="%2$s">View drafts &rarr;</a>');
        Functions\when('wp_kses')->returnArg();

        ob_start();
        $this->plugin->showOverdueNotice();
        $output = ob_get_clean();

        expect($output)->toContain('notice-warning');
        expect($output)->toContain('3');
    });

    it('computes and caches the count when no transient is set', function (): void {
        $screen     = new stdClass();
        $screen->id = 'edit-post';

        Functions\when('get_current_screen')->justReturn($screen);
        Functions\when('get_transient')->justReturn(false);

        $setCalls = [];
        Functions\when('set_transient')->alias(function (...$args) use (&$setCalls) {
            $setCalls[] = $args;
            return true;
        });

        ob_start();
        $this->plugin->showOverdueNotice();
        $output = ob_get_clean();

        expect($output)->toBe('');
        expect($setCalls)->toBe([['writing_status_overdue_count', 0, HOUR_IN_SECONDS]]);
    });
});
