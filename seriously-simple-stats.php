<?php
/*
 * Plugin Name: Seriously Simple Stats
 * Version: 1.2.0
 * Plugin URI: https://wordpress.org/plugins/seriously-simple-stats
 * Description: Integrated analytics and stats tracking for Seriously Simple Podcasting.
 * Author: PodcastMotor
 * Author URI: https://www.podcastmotor.com/
 * Requires at least: 4.4
 * Tested up to: 4.8.2
 *
 * Text Domain: seriously-simple-stats
 * Domain Path: /languages
 *
 * @package WordPress
 * @author Hugh Lashbrooke
 * @since 1.0.0
 *
 * @author PodcastMotor
 * @since 1.2.0
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SSP_STATS_VERSION', '1.2.0' );

if ( ! function_exists( 'is_ssp_active' ) ) {
	require_once( 'ssp-includes/ssp-functions.php' );
}

if( is_ssp_active( '1.13.1' ) ) {

	// Load plugin class files
	require_once( 'includes/class-ssp-stats.php' );

	/**
	 * Returns the main instance of SSP_Stats to prevent the need to use globals.
	 *
	 * @since  1.0.0
	 * @return object SSP_Stats
	 */
	function SSP_Stats () {
		$instance = SSP_Stats::instance( __FILE__, SSP_STATS_VERSION, '1.0.0' );
		return $instance;
	}

	SSP_Stats();

}
