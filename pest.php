<?php

/**
 * Pest configuration for Writing Status plugin.
 *
 * Unit suite  — Brain Monkey mocks WP functions; no real WordPress.
 * Integration — loads a real WordPress + test DB.
 */

uses()
    ->beforeEach(function (): void {
        Brain\Monkey\setUp();
    })
    ->afterEach(function (): void {
        Brain\Monkey\tearDown();
        Mockery::close();
    })
    ->in('tests/Unit');
