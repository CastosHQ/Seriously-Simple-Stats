<?php

namespace SeriouslySimpleStats\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Jaybizzle\CrawlerDetect\CrawlerDetect;

class Stats_Hit {

	/**
	 * The single instance of SSP_Stats_Hit.
	 * @var    object
	 * @access  private
	 * @since    1.2.3
	 */
	private static $_instance = null;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.2.3
	 */
	public $_version;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.2.3
	 */
	public $_token;

	/**
	 * The name of the cusotm database table.
	 * @var     string
	 * @access  public
	 * @since   1.2.3
	 */
	public $_table;

	/**
	 * The current time
	 *
	 * @var
	 */
	public $current_time;

	public function __construct( $version = '1.0.0' ) {
		$this->bootstrap( $version );
	}

	/**
	 * Set up any required class parameters or action/filter hooks
	 */
	public function bootstrap( $version ) {
		global $wpdb;

		$this->_version = $version;
		$this->_token = 'ssp_stats';
		$this->_table = $wpdb->prefix . $this->_token;

		// Set current time based on WordPress time zone settings
		$this->current_time = current_time( 'timestamp' );

		// Track episode download
		add_action( 'ssp_file_download', array( $this, 'track_hit' ), 10, 3 );
	}

	/**
	 * Anonymises IP address data by replacing the last octet with 0 (zero)
	 */
	public function anonymise_ip( $ip = '' ) {
		if ( empty( $ip ) ) {
			return $ip;
		}
		$ip_octets    = explode( '.', $ip );
		$ip_octets[3] = '0';
		$ip           = implode( '.', $ip_octets );

		return $ip;
	}

	/**
	 * Legacy wrapper function for track_hit
	 * track_download is deprecated since 1.2.3 and should not be used
	 *
	 * @param string $file
	 * @param int $episode
	 * @param string $referrer
	 */
	public function track_download( $file = '', $episode = 0, $referrer = '' ) {
		trigger_error( 'Deprecated function track_download in Seriously Simple Stats plugin called.', E_USER_NOTICE );
		$this->track_hit( $file, $episode, $referrer );
	}

	/**
	 * Track a hit on an audio file, either via download or play
	 *
	 * @param string $file
	 * @param int $episode
	 * @param string $referrer
	 */
	public function track_hit( $file = '', $episode = 0, $referrer = '' ) {
		global $wpdb;

		session_start();

		if ( ! $file || ! $episode || ! isset( $episode->ID ) ) {
			return;
		}

		// Make sure that the referrer is always a string (even if empty)
		$referrer = (string) $referrer;

		// Get request user agent
		$user_agent = (string) $_SERVER['HTTP_USER_AGENT'];

		// Check if this user agent is a crawler/bot to prevent false stats
		$CrawlerDetect = new CrawlerDetect();
		if ( $CrawlerDetect->isCrawler( $user_agent ) ) {
			return;
		}

		// Check for specific podcasting services in user agent
		// The iOS Podcasts app makes a HEAD request with user agent Podcasts/2.4 and then a GET request with user agent AppleCoreMedia

		if ( stripos( $user_agent, 'podcasts/' ) !== false ) {
			// This conditional will prevent double tracking from that app
			return;
		}

		if ( stripos( $user_agent, 'itunes' ) !== false || stripos( $user_agent, 'AppleCoreMedia' ) !== false ) {
			$referrer = 'itunes';
		} else if ( stripos( $user_agent, 'stitcher' ) !== false ) {
			$referrer = 'stitcher';
		} else if ( stripos( $user_agent, 'overcast' ) !== false ) {
			$referrer = 'overcast';
		} else if ( stripos( $user_agent, 'Pocket Casts' ) !== false ) {
			$referrer = 'pocketcasts';
		} else if ( stripos( $user_agent, 'Android' ) !== false ) {
			$referrer = 'android';
		} else if ( stripos( $user_agent, 'PodcastAddict' ) !== false ) {
			$referrer = 'podcast_addict';
		} else if ( stripos( $user_agent, 'Player FM' ) !== false ) {
			$referrer = 'playerfm';
		} else if ( stripos( $user_agent, 'Google-Play' ) !== false ) {
			$referrer = 'google_play';
		}

		// Get episode ID for database insert
		$episode_id = $episode->ID;

		// Get remote client IP address
		// If server is behind a reverse proxy (e.g. Cloudflare or Nginx), we need to get the client's IP address from HTTP headers
		// Cloudflare headers reference: https://support.cloudflare.com/hc/en-us/articles/200170986
		// The order of precedence here being Cloudflare, then other reverse proxies such as Nginx, then remote_addr
		if ( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			$ip_address = $_SERVER['HTTP_CF_CONNECTING_IP'];
		} elseif ( isset( $_SERVER['CF-Connecting-IP'] ) ) {
			$ip_address = $_SERVER['CF-Connecting-IP'];
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif ( isset( $_SERVER['X-Forwarded-For'] ) ) {
			$ip_address = $_SERVER['X-Forwarded-For'];
		} else {
			// None of the above headers are present (meaning we're not behind a reverse proxy) so fallback to using REMOTE_ADDR
			$ip_address = $_SERVER['REMOTE_ADDR'];
		}

		// Exit if there is no detectable IP address
		if ( ! $ip_address ) {
			return;
		}

		// Anonymise the ip address
		$ip_address = $this->anonymise_ip( $ip_address );

		// Create transient name from episode ID, IP address and referrer
		$transient = 'sspdl_' . $episode_id . '_' . str_replace( '.', '', $ip_address ) . '_' . $referrer;

		// Allow forced transient refresh
		if ( isset( $_GET['force'] ) ) {
			delete_transient( $transient );
		}

		// Check transient to prevent excessive tracking
		if ( get_transient( $transient ) ) {
			return;
		}

		// Insert data into database
		$insert_row = $wpdb->insert(
			$this->_table,
			array(
				'post_id'    => $episode_id,
				'ip_address' => $ip_address,
				'referrer'   => $referrer,
				'date'       => $this->current_time,
			),
			array(
				'%d',
				'%s',
				'%s',
				'%d',
			)
		);

		// Set transient if successful to prevent excessive tracking
		if ( $insert_row ) {
			set_transient( $transient, $this->current_time, HOUR_IN_SECONDS );
		}

	}

	/**
	 * Main SSP_Stats_Hit Instance
	 *
	 * Ensures only one instance of SSP_Stats_Hit is loaded or can be loaded.
	 *
	 * @return Main SSP_Stats_Hit instance
	 * @see SSP_Stats()
	 * @since 1.0.0
	 * @static
	 */
	public static function instance( $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $version );
		}

		return self::$_instance;
	} // End instance ()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __wakeup ()

}
