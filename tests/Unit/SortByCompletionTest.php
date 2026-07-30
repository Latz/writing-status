<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use Brain\Monkey\Functions;

/**
 * Unit tests for WritingStatus::sortByCompletion() — query modification guards.
 */

class MockWPQuery
{
    public array $data = [];
    public bool $_is_main = true;

    public function is_main_query(): bool
    {
        return $this->_is_main;
    }

    public function get(string $key, $default = '')
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }
}

beforeEach(function (): void {
    $this->plugin = new WritingStatusColumn();
});

describe('WritingStatusColumn::sortByCompletion()', function (): void {

    it('does nothing when not admin', function (): void {
        Functions\when('is_admin')->justReturn(false);

        $query                  = new MockWPQuery();
        $query->_is_main        = true;
        $query->data['orderby'] = 'writing_completion';

        $this->plugin->sortByCompletion($query);

        expect($query->data)->not->toHaveKey('meta_query');
    });

    it('does nothing when not main query', function (): void {
        Functions\when('is_admin')->justReturn(true);

        $query                  = new MockWPQuery();
        $query->_is_main        = false;
        $query->data['orderby'] = 'writing_completion';

        $this->plugin->sortByCompletion($query);

        expect($query->data)->not->toHaveKey('meta_query');
    });

    it('does nothing when orderby is not writing_completion', function (): void {
        Functions\when('is_admin')->justReturn(true);

        $query                  = new MockWPQuery();
        $query->_is_main        = true;
        $query->data['orderby'] = 'date';

        $this->plugin->sortByCompletion($query);

        expect($query->data)->not->toHaveKey('meta_query');
    });

    it('sets meta_query clauses when admin main query with writing_completion orderby', function (): void {
        Functions\when('is_admin')->justReturn(true);

        $query                  = new MockWPQuery();
        $query->_is_main        = true;
        $query->data['orderby'] = 'writing_completion';

        $this->plugin->sortByCompletion($query);

        expect($query->data)->toHaveKey('meta_query');
        expect($query->data['meta_query'])->toHaveKey('priority_clause');
    });

    it('sets orderby array when admin main query with writing_completion orderby', function (): void {
        Functions\when('is_admin')->justReturn(true);

        $query                  = new MockWPQuery();
        $query->_is_main        = true;
        $query->data['orderby'] = 'writing_completion';

        $this->plugin->sortByCompletion($query);

        expect($query->data['orderby'])->toBeArray();
    });

    it('preserves existing meta_query when sorting', function (): void {
        Functions\when('is_admin')->justReturn(true);

        $query                     = new MockWPQuery();
        $query->_is_main           = true;
        $query->data['orderby']    = 'writing_completion';
        $query->data['meta_query'] = [
            'existing_clause' => [
                'key'     => '_some_meta_key',
                'compare' => 'EXISTS',
            ],
        ];

        $this->plugin->sortByCompletion($query);

        expect($query->data['meta_query'])->toHaveKey('priority_clause');
        expect($query->data['meta_query'])->toHaveKey('existing_clause');
    });

    it('does nothing when all three guards fail', function (): void {
        Functions\when('is_admin')->justReturn(false);

        $query                  = new MockWPQuery();
        $query->_is_main        = true;
        $query->data['orderby'] = 'writing_completion';

        $this->plugin->sortByCompletion($query);

        expect($query->data)->not->toHaveKey('meta_query');
    });
});
