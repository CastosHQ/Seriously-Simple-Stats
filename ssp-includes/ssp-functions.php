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

/**
 * Anonymises IP address data by replacing the last octet with 0 (zero)
 */
if ( ! function_exists( 'ss_stats_anonymise_ip' ) ) {
	function ss_stats_anonymise_ip( $ip = '' ) {
		if ( empty( $ip ) ) {
			return $ip;
		}
		$ip_octets    = explode( '.', $ip );
		$ip_octets[3] = '0';
		$ip           = implode( '.', $ip_octets );
		return $ip;
	}
}
