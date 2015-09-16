<?php
/**
 * SSP Dependency Checker
 *
 * Checks if Seriously Simple Podcasting is enabled
 */
class SSP_Dependencies {

	private static $active_plugins;

	public static function init() {

		self::$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			self::$active_plugins = array_merge( self::$active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}
	}

	public static function ssp_active_check() {

		if ( ! self::$active_plugins ) {
			self::init();
		}

		return in_array( 'seriously-simple-podcasting/seriously-simple-podcasting.php', self::$active_plugins ) || array_key_exists( 'seriously-simple-podcasting/seriously-simple-podcasting.php', self::$active_plugins );
	}

}