<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Unit tests for the static WritingStatus::migrateMetaKeys() activation hook.
 */

class MockWpdbMigrate
{
    public string $postmeta = 'wp_postmeta';
    public array $updateCalls = [];

    public function update($table, $data, $where, $format = null, $whereFormat = null)
    {
        $this->updateCalls[] = [$table, $data, $where];
        return 1;
    }
}

beforeEach(function (): void {
    global $wpdb;
    $wpdb = new MockWpdbMigrate();
});

describe('WritingStatus::migrateMetaKeys()', function (): void {

    it('migrates all three legacy meta keys to their new names', function (): void {
        global $wpdb;

        WritingStatus::migrateMetaKeys();

        expect($wpdb->updateCalls)->toHaveCount(3);
    });

    it('renames _draft_complete to _writing_complete', function (): void {
        global $wpdb;

        WritingStatus::migrateMetaKeys();

        $call = $wpdb->updateCalls[0];
        expect($call[1])->toBe(['meta_key' => '_writing_complete']);
        expect($call[2])->toBe(['meta_key' => '_draft_complete']);
    });

    it('renames _draft_due_date to _writing_due_date', function (): void {
        global $wpdb;

        WritingStatus::migrateMetaKeys();

        $call = $wpdb->updateCalls[1];
        expect($call[1])->toBe(['meta_key' => '_writing_due_date']);
        expect($call[2])->toBe(['meta_key' => '_draft_due_date']);
    });

    it('renames _draft_priority to _writing_priority', function (): void {
        global $wpdb;

        WritingStatus::migrateMetaKeys();

        $call = $wpdb->updateCalls[2];
        expect($call[1])->toBe(['meta_key' => '_writing_priority']);
        expect($call[2])->toBe(['meta_key' => '_draft_priority']);
    });

    it('targets the wpdb postmeta table', function (): void {
        global $wpdb;

        WritingStatus::migrateMetaKeys();

        foreach ($wpdb->updateCalls as $call) {
            expect($call[0])->toBe('wp_postmeta');
        }
    });
});
