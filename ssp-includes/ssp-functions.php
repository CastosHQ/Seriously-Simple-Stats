<?php
/**
 * Functions used by plugins
 */

if ( ! class_exists( 'SSP_Dependencies' ) ) {
	require_once 'class-ssp-dependencies.php';
}

/**
 * SSP Detection
 */
if ( ! function_exists( 'is_ssp_active' ) ) {
	function is_ssp_active( $minimum_version = '' ) {
		return SSP_Dependencies::ssp_active_check( $minimum_version );
	}
}