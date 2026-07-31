<?php
/**
 * Bootstrap for Writing Status integration tests.
 *
 * Loads the real WordPress test library, which boots the full WordPress
 * environment and creates a temporary test database.
 *
 * Requires WP_TESTS_DIR to point at a checked-out WordPress test library.
 * Run bin/install-wp-tests.sh to set it up, or set the env var manually.
 */

// Load PHPUnit from the isolated integration-runner toolchain (PHPUnit 9.5)
// if present, since WP core's WP_UnitTestCase is incompatible with the main
// toolchain's PHPUnit 12 (bundled with Pest 4). Falls back to the main
// project's autoload so `composer test:integration` still works if the
// runner hasn't been set up.
$_integration_runner_autoload = __DIR__ . '/integration-runner/vendor/autoload.php';
require_once is_file( $_integration_runner_autoload )
    ? $_integration_runner_autoload
    : dirname( __DIR__ ) . '/vendor/autoload.php';
unset( $_integration_runner_autoload );

$_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: '/tmp/wordpress-tests-lib';

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
    echo "ERROR: WordPress test library not found at {$_tests_dir}.\n";
    echo "Run: svn co https://develop.svn.wordpress.org/tags/6.9.1/tests/phpunit/includes/ {$_tests_dir}/includes\n";
    exit( 1 );
}

// Tell the WP test library where the config file is.
define( 'WP_TESTS_CONFIG_FILE_PATH', __DIR__ . '/wp-tests-config.php' );

// PHPUnit Polyfills required by WP test suite. Prefer the isolated
// integration-runner's PHPUnit 9.5 toolchain (see tests/integration-runner/)
// since WP core's WP_UnitTestCase is incompatible with PHPUnit 10+/Pest 4.
$_integration_runner_polyfills = __DIR__ . '/integration-runner/vendor/yoast/phpunit-polyfills';
define(
    'WP_TESTS_PHPUNIT_POLYFILLS_PATH',
    is_dir( $_integration_runner_polyfills )
        ? $_integration_runner_polyfills
        : dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills'
);
unset( $_integration_runner_polyfills );

// Load the WordPress test functions.
require_once $_tests_dir . '/includes/functions.php';

// Hook into the muplugins_loaded action to load our plugin before tests run.
tests_add_filter( 'muplugins_loaded', function () {
    require_once dirname( __DIR__ ) . '/writing-status.php';
} );

// Bootstrap WordPress itself — this sets up the DB, loads WP, etc.
require_once $_tests_dir . '/includes/bootstrap.php';
