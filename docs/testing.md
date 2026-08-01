# Testing

Writing Status has two PHP test layers: **Unit** (Pest + Brain Monkey, no
database) and **Integration** (real WordPress + test database), plus a
**JS** layer (Vitest) covering `writing-status.js`.

## Dependencies

- [Pest](https://pestphp.com/) 4.x — test runner and assertion DSL
- [Brain Monkey](https://brain-wp.github.io/BrainMonkey/) 2.x — mocks
  WordPress functions/hooks (built on Mockery + Patchwork)
- `yoast/phpunit-polyfills` — cross-version PHPUnit assertion polyfills,
  required by the WordPress core test library

Install with:

```bash
composer install
```

## Running tests

```bash
composer test              # unit suite (alias for test:unit)
composer test:unit         # unit suite only
composer test:integration  # integration suite (requires WP_TESTS_DIR)
composer install-wp-tests  # sets up the WordPress test library
```

Integration tests need a checked-out WordPress test library and a MySQL
database. Run `bin/install-wp-tests.sh` once, or set `WP_TESTS_DIR` to an
existing checkout.

## Unit suite (`tests/Unit/`)

Runs without a database. Bootstrap load order
(`tests/bootstrap-unit.php`), critical and must not change:

1. `vendor/antecedent/patchwork/Patchwork.php` — must load first. Registers
   a stream wrapper that lets Brain Monkey redefine WordPress functions per
   test.
2. `vendor/autoload.php` — Composer autoload (Brain Monkey, Mockery, Pest).
3. `tests/stubs/wp-stubs.php` — WordPress function/class stubs, patchable
   because Patchwork already loaded.
4. One-shot `Brain\Monkey\setUp()` / `require writing-status.php` /
   `Brain\Monkey\tearDown()` — absorbs the plugin's top-level `add_action`/
   `add_filter` calls without needing a real WordPress environment.

Per-test `beforeEach`/`afterEach` hooks (wiring `Brain\Monkey\setUp()`/
`tearDown()` + `Mockery::close()` around every test) are configured in the
root `pest.php`.

`tests/stubs/wp-stubs.php` intentionally leaves `wp_verify_nonce()` and
`current_user_can()` undefined — Brain Monkey's `Functions\when()`/
`expect()` intercepts them per test.

### Writing a new unit test

```php
<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use Brain\Monkey\Functions;

beforeEach(function (): void {
    $this->plugin = new WritingStatusColumn();
});

describe('WritingStatusColumn::someMethod()', function (): void {

    it('does the expected thing', function (): void {
        Functions\when('some_wp_function')->justReturn('value');

        $result = $this->plugin->someMethod();

        expect($result)->toBe('expected');
    });
});
```

Use `Functions\when('fn')->justReturn($value)` for a default return value,
or `Functions\expect('fn')->once()->with(...)->andReturn($value)` when the
call count or arguments matter.

## Integration suite (`tests/Integration/`)

Extends `WP_UnitTestCase` and boots a real WordPress instance against a
test database (`tests/integration-bootstrap.php` → `tests/wp-tests-config.php`).
No mocking framework — these tests exercise the plugin against actual
`WP_Query`, post meta, and hooks.

**Runs under an isolated PHPUnit 9.5 toolchain**
(`tests/integration-runner/`, its own `composer.json`/`vendor/`), separate
from the main Pest 4 toolchain. WordPress core's `WP_UnitTestCase`
(`abstract-testcase.php`) calls `\PHPUnit\Util\Test::parseTestMethodAnnotations()`
inside `expectDeprecated()`, a method PHPUnit removed in 10.0 — since Pest 4
bundles PHPUnit 12, every `WP_UnitTestCase`-based test fails immediately in
`setUp()` under the main toolchain. This was confirmed against both WP
trunk and the 6.7 tag, so it isn't a WP-version issue.

Because of this, test methods use plain `test`-prefixed method names
(PHPUnit's original discovery convention, unchanged since PHPUnit 4) rather
than `@test` docblocks (dropped in PHPUnit 10) or `#[Test]` attributes
(don't exist before PHPUnit 10) — the one style that works identically
across both toolchains.

`composer test:integration` installs the runner's dependencies and invokes
its `vendor/bin/phpunit` automatically.

## JS suite (`tests/js/`)

`writing-status.js` is a single unbundled IIFE enqueued as-is (no build
step) — its `init*` functions aren't exported, they just run immediately
against the live DOM/`wp` globals on load. So instead of importing named
functions like a normal ES module, each test dynamically re-imports the raw
script fresh (`tests/js/helpers/dom.js`'s `loadScript()`, which calls
`vi.resetModules()` then `await import('../../../writing-status.js')`)
against a jsdom fixture it builds first, then asserts on the resulting DOM
state or mocked `wp`/`inlineEditPost` calls. `tests/js/mocks/wp.js` provides
a minimal `wp` global stub (`plugins`, `editPost`, `element`, `data`,
`components`, `i18n`) for the Gutenberg-panel tests, with
`element.createElement` returning a plain `{ type, props, children }`
descriptor rather than rendering real React.

```bash
npm install
npm run test:js            # run once
npm run test:js:watch       # watch mode
npm run test:js:coverage    # with coverage (bin/reports/coverage-js/)
```
