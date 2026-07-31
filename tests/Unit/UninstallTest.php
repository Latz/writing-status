<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use Brain\Monkey\Functions;

describe('uninstall.php', function (): void {

    it('deletes all plugin post meta keys and the overdue-count transient', function (): void {
        if (!defined('WP_UNINSTALL_PLUGIN')) {
            define('WP_UNINSTALL_PLUGIN', true);
        }

        $deletedMetaKeys = [];
        Functions\when('delete_post_meta_by_key')->alias(function (string $key) use (&$deletedMetaKeys): bool {
            $deletedMetaKeys[] = $key;
            return true;
        });

        $deletedTransients = [];
        Functions\when('delete_transient')->alias(function (string $key) use (&$deletedTransients): bool {
            $deletedTransients[] = $key;
            return true;
        });

        require __DIR__ . '/../../uninstall.php';

        expect($deletedMetaKeys)->toBe([
            '_writing_complete',
            '_writing_due_date',
            '_writing_priority',
        ]);
        expect($deletedTransients)->toBe(['writing_status_overdue_count']);
    });

});
