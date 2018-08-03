<?php
/**
 * Class FunctionsTest
 *
 * Used to test all generic functions
 *
 * @package Seriously_Simple_Stats
 */

/**
 * SSP Stats test case.
 */
class SSPStatsTest extends WP_UnitTestCase {

	/**
	 * Tests the anonymise ip function that it
	 * returns a valid ip address format and
	 * returns the last octet as a zero
	 */
	public function test_ss_stats_anonymise_ip() {
		$ssp_stats = SSP_Stats();

		$ip     = '192.168.0.233';
		$new_ip = $ssp_stats->anonymise_ip( $ip );

		// check that the string still resembles an ip address
		$this->assertStringMatchesFormat( '%x.%x.%x.%x', $new_ip, 'The address does not match a valid ip address format.' );

		// assert that the string ends with .0
		$this->assertStringEndsWith( '.0', $new_ip, 'The address does not end with a zero.' );
	}
}
