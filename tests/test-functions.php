<?php
/**
 * Class FunctionsTest
 *
 * Used to test all generic functions
 *
 * @package Seriously_Simple_Stats
 */

/**
 * Sample test case.
 */
class FunctionsTest extends WP_UnitTestCase {

	/**
	 * A single example test.
	 */
	public function test_ss_stats_anonymise_ip() {
		$ip     = '192.168.0.233';
		$ssp_stats = SSP_Stats();

		$new_ip = $ssp_stats->anonymise_ip( $ip );
		// check that the string still resembles an ip address
		$this->assertStringMatchesFormat( '%x.%x.%x.%x', $new_ip, 'The address does not match a valid ip address format.' );
		// assert that the string ends with .0
		$this->assertStringEndsWith( '.0', $new_ip, 'The address does not end with a zero.' );
	}
}
