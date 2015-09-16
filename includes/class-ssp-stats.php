<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class SSP_Stats {

	/**
	 * The single instance of SSP_Stats.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_version;

	/**
	 * The database version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_db_version;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_token;

	/**
	 * The name of the cusotm database table.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_table;

	/**
	 * The main plugin file.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * The main plugin directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_url;

	/**
	 * Suffix for Javascripts.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $script_suffix;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 */
	public function __construct ( $file = '', $version = '1.0.0', $db_version = '1.0.0' ) {
		global $wpdb;

		// Load plugin constants
		$this->_version = $version;
		$this->_db_version = $db_version;
		$this->_token = 'ssp_stats';
		$this->_table = $wpdb->prefix . $this->_token;

		// Load plugin environment variables
		$this->file = $file;
		$this->dir = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );
		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		register_activation_hook( $this->file, array( $this, 'install' ) );

		// Update database to latest schema
		add_action( 'init', array( $this, 'update_database' ), 0 );

		// Track episode download
		add_action( 'ssp_file_download', array( $this, 'track_download' ), 10, 3 );

		// Add stats meta box to episodes edit screen
		add_action( 'ssp_meta_boxes', array( $this, 'post_meta_box' ), 10, 1 );

		// Load admin JS & CSS
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

		// Handle localisation
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );
	} // End __construct ()

	public function track_download ( $file = '', $episode = 0, $referrer = '' ) {
		global $wpdb;

		session_start();

		if ( ! $file || ! $episode || ! isset( $episode->ID ) ) {
			return;
		}

		// Make sure that the referrer is always a string (even if empty)
		$referrer = (string) $referrer;

		// Get request user agent
		$user_agent = (string) $_SERVER['HTTP_USER_AGENT'];

		// Check for specific podcasting services in user agent
		if ( stripos( 'itunes', $user_agent ) !== false ) {
			$referrer = 'itunes';
		} elseif ( stripos( 'stitcher', $user_agent ) !== false ) {
			$referrer = 'stitcher';
		}

		// Get additional values for database insert
		$episode_id = $episode->ID;
		$ip_address = $_SERVER['REMOTE_ADDR'];
		$time = $_SERVER['REQUEST_TIME'];

		// Create transiet name from episode ID, IP address and referrer
		$transient = 'sspdl_' . $episode_id . '_' . str_replace( '.', '', $ip_address ) . '_' . $referrer;

		// Allow forced transient refresh
		if ( isset( $_GET['force'] ) ) {
			delete_transient( $transient );
		}

		// Check trasnient to prevent excessive tracking
		if ( get_transient( $transient ) ) {
			return;
		}

		// Insert data into database
		$insert_row = $wpdb->insert(
			$this->_table,
			array(
				'post_id' => $episode_id,
				'ip_address' => $ip_address,
				'referrer' => $referrer,
				'date' => $time,
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
			set_transient( $transient, $time, HOUR_IN_SECONDS );
		}

	}

	public function get_episode_stats ( $episode_id = 0, $fields = '*' ) {
		global $wpdb;

		if( ! $episode_id ) {
			return;
		}

		if( is_array( $fields ) ) {
			$fields = implode( ', ', $fields );
		}

		$results = $wpdb->get_results( $wpdb->prepare( 'SELECT ' . $fields . ' FROM ' . $this->_table.' WHERE post_id = %d', $episode_id ) );

		return $results;
	}

	public function post_meta_box ( $post ) {
		add_meta_box( 'podcast-episode-stats', __( 'Episode Stats' , 'ssp-stats' ), array( $this, 'stats_meta_box_content' ), $post->post_type, 'side', 'default' );
	}

	public function stats_meta_box_content ( $post ) {

		if( ! isset( $post->ID ) ) {
			return;
		}

		$stats = $this->get_episode_stats( $post->ID, array( 'ip_address', 'referrer') );

		$total_downloads = count( $stats );

		$html = '<p class="episode-stat-data total-downloads">' . __( 'Total downloads', 'ssp-stats' ) . ': <b>' . $total_downloads . '</b></p>';

		if( $total_downloads ) {

			$users = array();
			$itunes = $stitcher = $direct = $player = $unknown = 0;

			foreach( $stats as $stat ) {
				$listeners[ $stat->ip_address ] = $stat->ip_address;

				switch( $stat->referrer ) {
					case 'itunes':
						++$itunes;
					break;
					case 'stitcher':
						++$stitcher;
					break;
					case 'download':
						++$direct;
					break;
					case 'player':
						++$player;
					break;
					default:
						++$unknown;
					break;
				}
			}

			$total_listeners = count( $listeners );

			$html .= '<p class="episode-stat-data total-listeners">' . __( 'Total listeners', 'ssp-stats' ) . ': <b>' . $total_listeners . '</b></p>';
			$html .= '<p class="episode-stat-data sources">' . __( 'Download sources', 'ssp-stats' ) . ':</p>';
			$html .= '<ul class="sources-list">';
				if( $itunes) {
					$html .= '<li class="itunes">' . __( 'iTunes', 'ssp-stats' ) . ': <b>' . $itunes . '</b></li>';
				}
				if( $stitcher) {
					$html .= '<li class="stitcher">' . __( 'Stitcher', 'ssp-stats' ) . ': <b>' . $stitcher . '</b></li>';
				}
				if( $direct) {
					$html .= '<li class="direct">' . __( 'Direct download', 'ssp-stats' ) . ': <b>' . $direct . '</b></li>';
				}
				if( $player) {
					$html .= '<li class="player">' . __( 'Audio player', 'ssp-stats' ) . ': <b>' . $player . '</b></li>';
				}
				if( $unknown) {
					$html .= '<li class="unknown">' . __( 'Unknown', 'ssp-stats' ) . ': <b>' . $unknown . '</b></li>';
				}
			$html .= '</ul>';
		}

		echo $html;
	}


	/**
	 * Update the database to latest schema for stats storage
	 * @return void
	 */
	public function update_database () {

		// Get existing database version (default to 0 for initial setup)
		$current_db_version = get_option( 'ssp_stats_db_version', '0' );

		if ( '0' == $current_db_version ) {

			// Setup SQL query for table creation
			$sql = "CREATE TABLE " . $this->_table . " (
				id int(11) NOT NULL AUTO_INCREMENT,
				post_id int(11) DEFAULT NULL,
				ip_address varchar(255) DEFAULT NULL,
				referrer varchar(255) DEFAULT NULL,
				date int(25) DEFAULT NULL,
				UNIQUE KEY id (id)
			);";

			// Load database functions if necessary
			if( ! function_exists( 'dbDelta' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			}

			// Create table
			dbDelta( $sql );

			// Update database verion option
			update_option( 'ssp_stats_db_version', $this->_db_version );
		}

	}

	/**
	 * Load admin CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_styles ( $hook = '' ) {
		wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-admin' );
	} // End admin_enqueue_styles ()

	/**
	 * Load admin Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_scripts ( $hook = '' ) {
		wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script( $this->_token . '-admin' );
	} // End admin_enqueue_scripts ()

	/**
	 * Load plugin localisation
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_localisation () {
		load_plugin_textdomain( 'ssp-stats', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain () {
	    $domain = 'ssp-stats';

	    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_plugin_textdomain ()

	/**
	 * Main SSP_Stats Instance
	 *
	 * Ensures only one instance of SSP_Stats is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see SSP_Stats()
	 * @return Main SSP_Stats instance
	 */
	public static function instance ( $file = '', $version = '1.0.0', $db_version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version, $db_version );
		}
		return self::$_instance;
	} // End instance ()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __wakeup ()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install () {
		$this->_log_version_number();
	} // End install ()

	/**
	 * Log the plugin version number.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number () {
		update_option( $this->_token . '_version', $this->_version );
	} // End _log_version_number ()

}
