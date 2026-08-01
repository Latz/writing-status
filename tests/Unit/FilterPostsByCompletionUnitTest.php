<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use Brain\Monkey\Functions;

/**
 * Unit tests for WritingStatus::filterPostsByCompletion() and its private helpers.
 */

class MockWPQueryFilter
{
    public array $data = [];
    public bool $_is_main = true;
    public function is_main_query(): bool { return $this->_is_main; }
    public function get(string $key, $default = '') { return $this->data[$key] ?? $default; }
    public function set(string $key, $value): void { $this->data[$key] = $value; }
}

beforeEach(function (): void {
    $this->plugin = new WritingStatusFilters();
});

afterEach(function (): void {
    unset($_GET['writing_completion_filter'], $_GET['writing_priority_filter']);
    global $pagenow;
    $pagenow = '';
});

describe('WritingStatusFilters::filterPostsByCompletion()', function (): void {

    it('returns early when not admin', function (): void {
        Functions\when('is_admin')->justReturn(false);

        global $pagenow;
        $pagenow = 'edit.php';
        $_GET['writing_completion_filter'] = 'complete';

        $query = new MockWPQueryFilter();
        $this->plugin->filterPostsByCompletion($query);

        expect($query->data)->not->toHaveKey('meta_query');
    });

    it('apply completion filter complete sets meta_query', function (): void {
        $_GET['writing_completion_filter'] = 'complete';

        $method = new ReflectionMethod(WritingStatusFilters::class, 'applyCompletionFilter');

        $query              = new MockWPQueryFilter();
        $filter_meta_query  = ['relation' => 'AND'];

        $method->invokeArgs($this->plugin, [$query, &$filter_meta_query]);

        expect($filter_meta_query)->toHaveCount(2);
        expect($query->get('post_status'))->toBe('draft');
    });

    it('apply completion filter incomplete sets or meta_query', function (): void {
        $_GET['writing_completion_filter'] = 'incomplete';

        $method = new ReflectionMethod(WritingStatusFilters::class, 'applyCompletionFilter');

        $query              = new MockWPQueryFilter();
        $filter_meta_query  = ['relation' => 'AND'];

        $method->invokeArgs($this->plugin, [$query, &$filter_meta_query]);

        expect($filter_meta_query)->toHaveCount(2);
        expect($query->get('post_status'))->toBe('draft');
    });

    it('apply completion filter unknown value does nothing', function (): void {
        $_GET['writing_completion_filter'] = 'unknown';

        $method = new ReflectionMethod(WritingStatusFilters::class, 'applyCompletionFilter');

        $query              = new MockWPQueryFilter();
        $filter_meta_query  = ['relation' => 'AND'];

        $method->invokeArgs($this->plugin, [$query, &$filter_meta_query]);

        expect($filter_meta_query)->toHaveCount(1);
    });

    it('apply priority filter valid priority adds clause', function (): void {
        $_GET['writing_priority_filter'] = 'high';

        $method = new ReflectionMethod(WritingStatusFilters::class, 'applyPriorityFilter');

        $query                  = new MockWPQueryFilter();
        $filter_meta_query      = ['relation' => 'AND'];
        $has_completion_filter  = false;

        $method->invokeArgs($this->plugin, [$query, &$filter_meta_query, $has_completion_filter]);

        expect($filter_meta_query)->toHaveCount(2);
        expect($query->get('post_status'))->toBe('draft');
    });

    it('apply priority filter invalid priority does nothing', function (): void {
        $_GET['writing_priority_filter'] = 'invalid';

        $method = new ReflectionMethod(WritingStatusFilters::class, 'applyPriorityFilter');

        $query                  = new MockWPQueryFilter();
        $filter_meta_query      = ['relation' => 'AND'];
        $has_completion_filter  = false;

        $method->invokeArgs($this->plugin, [$query, &$filter_meta_query, $has_completion_filter]);

        expect($filter_meta_query)->toHaveCount(1);
    });

    it('apply priority filter with completion filter does not set post_status', function (): void {
        $_GET['writing_priority_filter'] = 'high';

        $method = new ReflectionMethod(WritingStatusFilters::class, 'applyPriorityFilter');

        $query                  = new MockWPQueryFilter();
        $filter_meta_query      = ['relation' => 'AND'];
        $has_completion_filter  = true;

        $method->invokeArgs($this->plugin, [$query, &$filter_meta_query, $has_completion_filter]);

        expect($query->data)->not->toHaveKey('post_status');
    });

    it('sets meta_query when admin with completion filter', function (): void {
        Functions\when('is_admin')->justReturn(true);
        global $pagenow;
        $pagenow = 'edit.php';
        $_GET['writing_completion_filter'] = 'complete';

        $query = new MockWPQueryFilter();
        $this->plugin->filterPostsByCompletion($query);

        expect($query->data)->toHaveKey('meta_query');
    });

    it('returns early when no filters set', function (): void {
        Functions\when('is_admin')->justReturn(true);
        global $pagenow;
        $pagenow = 'edit.php';

        $query = new MockWPQueryFilter();
        $this->plugin->filterPostsByCompletion($query);

        expect($query->data)->not->toHaveKey('meta_query');
    });

    it('returns early when pagenow is not edit.php', function (): void {
        Functions\when('is_admin')->justReturn(true);
        global $pagenow;
        $pagenow = 'post.php';
        $_GET['writing_completion_filter'] = 'complete';

        $query = new MockWPQueryFilter();
        $this->plugin->filterPostsByCompletion($query);

        expect($query->data)->not->toHaveKey('meta_query');
    });

    it('sets meta_query when admin with priority filter', function (): void {
        Functions\when('is_admin')->justReturn(true);
        global $pagenow;
        $pagenow = 'edit.php';
        $_GET['writing_priority_filter'] = 'high';

        $query = new MockWPQueryFilter();
        $this->plugin->filterPostsByCompletion($query);

        expect($query->data)->toHaveKey('meta_query');
    });

    it('sets meta_query when both filters set', function (): void {
        Functions\when('is_admin')->justReturn(true);
        global $pagenow;
        $pagenow = 'edit.php';
        $_GET['writing_completion_filter'] = 'incomplete';
        $_GET['writing_priority_filter']   = 'urgent';

        $query = new MockWPQueryFilter();
        $this->plugin->filterPostsByCompletion($query);

        expect($query->data)->toHaveKey('meta_query');
    });
});
