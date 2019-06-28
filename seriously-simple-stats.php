<?php
/*
 * Plugin Name: Seriously Simple Stats
 * Version: 1.2.3
 * Plugin URI: https://wordpress.org/plugins/seriously-simple-stats
 * Description: Integrated analytics and stats tracking for Seriously Simple Podcasting.
 * Author: Castos
 * Author URI: https://www.castos.com/
 * Requires at least: 4.4
 * Tested up to: 5.1.1
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
use SeriouslySimpleStats\Classes\Stats_Hit;

require_once 'vendor/autoload.php';

define( 'SSP_STATS_VERSION', '1.2.3' );

if ( ! function_exists( 'is_ssp_active' ) ) {
	require_once 'ssp-includes/ssp-functions.php';
}

if ( is_ssp_active( '1.13.1' ) ) {
	$ssp_stats     = Stats::instance( __FILE__, SSP_STATS_VERSION, '1.0.0' );
	$ssp_stats_hit = Stats_Hit::instance( SSP_STATS_VERSION );
}
