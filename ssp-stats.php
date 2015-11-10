<?php
/*
 * Plugin Name: SSP Stats
 * Version: 1.0-alpha
 * Plugin URI: http://www.seriouslysimplepodcasting.com/
 * Description: Complete analytics and stats tracking for Seriously Simple Podcasting.
 * Author: Hugh Lashbrooke
 * Author URI: http://www.hughlashbrooke.com/
 * Requires at least: 4.0
 * Tested up to: 4.3.1
 *
 * Text Domain: ssp-stats
 *
 * @package WordPress
 * @author Hugh Lashbrooke
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'is_ssp_active' ) ) {
	require_once( 'ssp-includes/ssp-functions.php' );
}

if( is_ssp_active( '1.14' ) ) {

	// Load plugin class files
	require_once( 'includes/class-ssp-stats.php' );

	/**
	 * Returns the main instance of SSP_Stats to prevent the need to use globals.
	 *
	 * @since  1.0.0
	 * @return object SSP_Stats
	 */
	function SSP_Stats () {
		$instance = SSP_Stats::instance( __FILE__, '1.0.0', '1.0.0' );
		return $instance;
	}

	SSP_Stats();

}