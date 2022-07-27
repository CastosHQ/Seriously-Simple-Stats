<?php

/**
 * SSP Dependency Checker
 *
 * Checks if Seriously Simple Podcasting is enabled and validates against a minimum required version if specified
 */
class SSP_Dependencies {

	private static $active_plugins;

	public static function init() {

		self::$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			self::$active_plugins = array_merge( self::$active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}
	}

	/**
	 * @param $minimum_version
	 *
	 * @return bool
	 */
	public static function ssp_active_check( $minimum_version = '' ) {

		if (! self::is_ssp_plugin_enabled() ) {
			return false;
		}

		if ( ! $minimum_version ) {
			return true;
		}


		$ssp_version = get_option( 'ssp_version', '1.0' );

		return version_compare( $ssp_version, $minimum_version, '>=' );
	}

	/**
	 * @return bool
	 */
	protected static function is_ssp_plugin_enabled() {
		$ssp_file_name = 'seriously-simple-podcasting.php';

		if ( ! self::$active_plugins ) {
			self::init();
		}

		foreach ( self::$active_plugins as $active_plugin ) {
			if ( false !== strpos( $active_plugin, $ssp_file_name ) ) {
				return true;
			}
		}

		return false;
	}

}
