<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Unit tests for WritingStatus::getValidPriorities(), getPriorityLabels(),
 * and registerMetaField().
 */

beforeEach(function (): void {
    $this->plugin = new WritingStatus();
});

describe('WritingStatus::getValidPriorities()', function (): void {

    it('returns array with five priorities', function (): void {
        $method = new ReflectionMethod(WritingStatus::class, 'getValidPriorities');
        $method->setAccessible(true);

        $result = $method->invoke($this->plugin);

        expect($result)->toHaveCount(5);
    });

    it('contains all expected priorities', function (): void {
        $method = new ReflectionMethod(WritingStatus::class, 'getValidPriorities');
        $method->setAccessible(true);

        $result = $method->invoke($this->plugin);

        foreach (['none', 'low', 'medium', 'high', 'urgent'] as $priority) {
            expect($result)->toContain($priority);
        }
    });

    it('urgent is in valid priorities', function (): void {
        $method = new ReflectionMethod(WritingStatus::class, 'getValidPriorities');
        $method->setAccessible(true);

        $result = $method->invoke($this->plugin);

        expect(in_array('urgent', $result, true))->toBeTrue();
    });
});

describe('WritingStatus::getPriorityLabels()', function (): void {

    it('returns four labels', function (): void {
        $method = new ReflectionMethod(WritingStatus::class, 'getPriorityLabels');
        $method->setAccessible(true);

        $result = $method->invoke($this->plugin);

        expect($result)->toHaveCount(4);
    });

    it('does not contain none key', function (): void {
        $method = new ReflectionMethod(WritingStatus::class, 'getPriorityLabels');
        $method->setAccessible(true);

        $result = $method->invoke($this->plugin);

        expect(array_key_exists('none', $result))->toBeFalse();
    });

    it('contains high key', function (): void {
        $method = new ReflectionMethod(WritingStatus::class, 'getPriorityLabels');
        $method->setAccessible(true);

        $result = $method->invoke($this->plugin);

        expect(array_key_exists('high', $result))->toBeTrue();
    });
});

describe('WritingStatus::registerMetaField()', function (): void {

    it('executes without error', function (): void {
        $this->plugin->registerMetaField();
        expect(true)->toBeTrue();
    });
});
