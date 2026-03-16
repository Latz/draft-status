<?php
/**
 * Bootstrap for Draft Status integration tests.
 *
 * Loads the real WordPress test library, which boots the full WordPress
 * environment and creates a temporary test database.
 *
 * Required environment: must run inside the wp-wordpress-1 Docker container
 * (or any environment where /var/www/html is the WordPress root and the
 * wp-db-1 database host is reachable as "db").
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: '/tmp/wordpress-tests-lib';

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
    echo "ERROR: WordPress test library not found at {$_tests_dir}.\n";
    echo "Run: svn co https://develop.svn.wordpress.org/tags/6.9.1/tests/phpunit/includes/ {$_tests_dir}/includes\n";
    exit( 1 );
}

// Tell the WP test library where the config file is.
define( 'WP_TESTS_CONFIG_FILE_PATH', __DIR__ . '/wp-tests-config.php' );

// PHPUnit Polyfills required by WP test suite.
define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills' );

// Load the WordPress test functions.
require_once $_tests_dir . '/includes/functions.php';

// Hook into the muplugins_loaded action to load our plugin before tests run.
tests_add_filter( 'muplugins_loaded', function () {
    require_once dirname( __DIR__ ) . '/draft-status.php';
} );

// Bootstrap WordPress itself — this sets up the DB, loads WP, etc.
require_once $_tests_dir . '/includes/bootstrap.php';
