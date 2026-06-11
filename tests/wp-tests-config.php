<?php
/**
 * WordPress test configuration for Writing Status integration tests.
 *
 * Values fall back to local-dev defaults when env vars are not set.
 * In CI (GitHub Actions) the workflow sets WP_ABSPATH, DB_HOST,
 * DB_NAME, DB_USER, and DB_PASSWORD via the job's env block.
 */

/* Path to the WordPress codebase you'd like to test. Add a trailing slash. */
define( 'ABSPATH', getenv( 'WP_ABSPATH' ) ?: '/home/latz/www/wp/' );

/* Path to the theme directory */
define( 'WP_DEFAULT_THEME', 'default' );

/* Test with multisite enabled. Comment out to test without multisite. */
// define( 'WP_TESTS_MULTISITE', true );

/* Force known bugs to be run. Tests will be marked as failed even if they are in a known bug list. */
// define( 'WP_TESTS_FORCE_KNOWN_BUGS', true );

/* The hostname of the database server */
define( 'DB_HOST', getenv( 'DB_HOST' ) ?: '127.0.0.1' );

/* The name of the database */
define( 'DB_NAME', getenv( 'DB_NAME' ) ?: 'wordpress_tests' );

/* The database username */
define( 'DB_USER', getenv( 'DB_USER' ) ?: 'latz' );

/* The database password */
define( 'DB_PASSWORD', getenv( 'DB_PASSWORD' ) ?: 'x' );

/* The table prefix */
$table_prefix = 'wptests_';

define( 'WP_TESTS_DOMAIN', 'localhost' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );

define( 'WP_PHP_BINARY', 'php' );

define( 'WPLANG', '' );
