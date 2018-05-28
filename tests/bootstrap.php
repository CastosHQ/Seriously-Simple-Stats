<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Seriously_Simple_Stats
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	throw new Exception( "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Recreated the SSP_Stats function from the main plugin file
 * This is because the plugin file checks to ensure that a verion of SSP is installed
 * and for unit tests that check would fail
 *
 * @return Main
 */
function SSP_Stats() {
	$instance = SSP_Stats::instance( __FILE__, SSP_STATS_VERSION, '1.0.0' );

	return $instance;
}

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/seriously-simple-stats.php';
	// Manually include the class-ssp-stats.php file
	require_once( 'includes/class-ssp-stats.php' );
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
