<?php
/*
 * Plugin Name: Seriously Simple Stats
 * Version: 1.4.0
 * Plugin URI: https://wordpress.org/plugins/seriously-simple-stats
 * Description: Integrated analytics and stats tracking for Seriously Simple Podcasting.
 * Author: Castos
 * Author URI: https://www.castos.com/
 * Requires at least: 4.4
 * Tested up to: 5.5
 *
 * Text Domain: seriously-simple-stats
 * Domain Path: /languages
 *
 * @package WordPress
 * @author Hugh Lashbrooke
 * @since 1.0.0
 *
 * @author Castos
 * @since 1.2.0
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SeriouslySimpleStats\Classes\Stats;

define( 'SSP_STATS_VERSION', '1.4.0' );
define( 'SSP_STATS_DIR_PATH', plugin_dir_path( __FILE__ ) );

require_once SSP_STATS_DIR_PATH . 'vendor/autoload.php';

if ( ! function_exists( 'is_ssp_active' ) ) {
	require_once SSP_STATS_DIR_PATH . 'ssp-includes/ssp-functions.php';
}

if ( ! defined( 'SSP_CPT_PODCAST' ) ) {
	define( 'SSP_CPT_PODCAST', 'podcast' );
}

if ( is_ssp_active( '1.13.1' ) ) {

	/**
	 * Returns the main instance of SSP_Stats to prevent the need to use globals.
	 *
	 * @return Object Stats
	 * @since  1.0.0
	 */
	function SSP_Stats() {
		$ssp_stats = Stats::instance( __FILE__, SSP_STATS_VERSION, '1.0.0' );

		return $ssp_stats;
	}

	SSP_Stats();

}
