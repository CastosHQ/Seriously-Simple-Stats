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
	 * Current time.
	 * @var     string
	 * @access  public
	 * @since   1.0.1
	 */
	public $current_time;

	/**
	 * Chart start date.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $start_date;

	/**
	 * Chart end date.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $end_date;

	/**
	 * Data series selection.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $series = 'all';

	/**
	 * Data episode selection.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $episode = 'all';

	/**
	 * Data filter selection.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $filter = '';

	/**
	 * Episode IDs for data filtering.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $episode_ids = '';

	/**
	 * WHERE clause for data filtering
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $episode_id_where = '';

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

		// Set current time based on WordPress time zone settings
		$this->current_time = current_time( 'timestamp' );

		// Set start date for charts
		if( isset( $_GET['start'] ) ) {
			$this->start_date = strtotime( sanitize_text_field( $_GET['start'] ) );
		} else {
			$this->start_date = strtotime( '1 month ago', $this->current_time );
		}

		// Set end date for charts
		if( isset( $_GET['end'] ) ) {
			$this->end_date = strtotime( date( 'Y-m-d 23:59:59', strtotime( sanitize_text_field( $_GET['end'] ) ) ) );
		} else {
			$this->end_date = $this->current_time;
		}

		// Set series selection for charts
		if( isset( $_GET['series'] ) ) {
			$this->series = sanitize_text_field( $_GET['series'] );
		}

		// Set episode selection for charts
		if( isset( $_GET['episode'] ) ) {
			$this->episode = sanitize_text_field( $_GET['episode'] );
		}

		// Set filter selection for charts
		if( isset( $_GET['filter'] ) ) {
			$this->filter = sanitize_text_field( $_GET['filter'] );
		}

		register_activation_hook( $this->file, array( $this, 'install' ) );

		// Update database to latest schema
		add_action( 'init', array( $this, 'update_database' ), 1 );

		// Get required episode IDs for stats
		add_action( 'init', array( $this, 'load_episode_ids' ), 10 );

		// Track episode download
		add_action( 'ssp_file_download', array( $this, 'track_download' ), 10, 3 );

		// Add stats meta box to episodes edit screen
		add_action( 'ssp_meta_boxes', array( $this, 'post_meta_box' ), 10, 1 );

		// Add menu item
		add_action( 'admin_menu', array( $this , 'add_menu_item' ) );

		// Load necessary javascript for charts
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_print_scripts', array( $this, 'chart_data' ), 30 );

		// Load admin CSS
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

		// Handle localisation
		add_action( 'plugins_loaded', array( $this, 'load_localisation' ) );
	} // End __construct ()

	public function load_episode_ids () {

		switch( $this->filter ) {
			case 'series':
				if( 'all' != $this->series ) {

					$episodes = ssp_episodes( -1, $this->series, false, 'stats' );
					foreach( $episodes as $episode ) {
						if( $this->episode_ids ) {
							$this->episode_ids .= ',';
						}
						$this->episode_ids .= $episode->ID;
					}
				}
			break;

			case 'episode':
				if( 'all' != $this->episode ) {
					$this->episode_ids = $this->episode;
				}
			break;
		}

		// Set up WHERE clause if episode/series is selected
		if( $this->episode_ids ) {
			$this->episode_id_where = 'post_id IN (' . $this->episode_ids . ')';
		}
	}

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

		// Include Crawler Detect library
		require_once( 'lib/CrawlerDetect/CrawlerDetect.php' );

		// Check if this user agent is a crawler/bot to prevent false stats
		$CrawlerDetect = new CrawlerDetect();
		if( $CrawlerDetect->isCrawler( $user_agent ) ) {
		    return;
		}

		// Check for specific podcasting services in user agent
		// The iOS Podcasts app makes a HEAD request with user agent Podcasts/2.4 and then a GET request with user agent AppleCoreMedia
		// This conditional will prevent double tracking from that app
		if( stripos( $user_agent, 'podcasts/' ) !== false ) {
			return;
		}

		if ( stripos( $user_agent, 'itunes' ) !== false || stripos( $user_agent, 'AppleCoreMedia' ) !== false ) {
			$referrer = 'itunes';
		} elseif ( stripos( $user_agent, 'stitcher' ) !== false ) {
			$referrer = 'stitcher';
		} elseif ( stripos(  $user_agent, 'overcast' ) !== false ) {
			$referrer = 'overcast';
		} elseif ( stripos( $user_agent, 'Pocket Casts' ) !== false ) {
			$referrer = 'pocketcasts';
		}

		// Get episode ID for database insert
		$episode_id = $episode->ID;

		// Get remote client IP address
		// If server is behind a reverse proxy (e.g. Cloudflare or Nginx), we need to get the client's IP address from HTTP headers
		// Cloudflare headers reference: https://support.cloudflare.com/hc/en-us/articles/200170986
		// The order of precedence here being Cloudflare, then other reverse proxies such as Nginx, then remote_addr
		$ip_address = '';
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
		if( ! $ip_address ) {
			return;
		}

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
				'post_id' => $episode_id,
				'ip_address' => $ip_address,
				'referrer' => $referrer,
				'date' => $this->current_time,
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
		add_meta_box( 'podcast-episode-stats', __( 'Episode Stats' , 'seriously-simple-stats' ), array( $this, 'stats_meta_box_content' ), $post->post_type, 'side', 'low' );
	}

	public function stats_meta_box_content ( $post ) {

		if( ! isset( $post->ID ) ) {
			return;
		}

		$stats = $this->get_episode_stats( $post->ID, array( 'ip_address', 'referrer') );

		$total_downloads = count( $stats );

		$html = '';
		if( $total_downloads ) {

			$html .= '<p class="episode-stat-data total-downloads">' . __( 'Total listens', 'seriously-simple-stats' ) . ': <b>' . $total_downloads . '</b></p>';

			$users = array();
			$itunes = $stitcher = $overcast = $pocketcasts = $direct = $new_window = $player = $unknown = 0;

			foreach( $stats as $stat ) {
				$listeners[ $stat->ip_address ] = $stat->ip_address;

				switch( $stat->referrer ) {
					case 'itunes':
						++$itunes;
					break;
					case 'stitcher':
						++$stitcher;
					break;
					case 'overcast':
						++$overcast;
					break;
					case 'pocketcasts':
						++$pocketcasts;
					break;
					case 'download':
						++$direct;
					break;
					case 'new_window':
						++$new_window;
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

			$html .= '<p class="episode-stat-data total-listeners">' . __( 'Total listeners', 'seriously-simple-stats' ) . ': <b>' . $total_listeners . '</b></p>';
			$html .= '<p class="episode-stat-data sources">' . __( 'Listening sources', 'seriously-simple-stats' ) . ':</p>';
			$html .= '<ul class="sources-list">';
				if( $itunes ) {
					$html .= '<li class="itunes">' . __( 'iTunes', 'seriously-simple-stats' ) . ': <b>' . $itunes . '</b></li>';
				}
				if( $stitcher ) {
					$html .= '<li class="stitcher">' . __( 'Stitcher', 'seriously-simple-stats' ) . ': <b>' . $stitcher . '</b></li>';
				}
				if( $overcast ) {
					$html .= '<li class="overcast">' . __( 'Overcast', 'seriously-simple-stats' ) . ': <b>' . $overcast . '</b></li>';
				}
				if( $pocketcasts ) {
					$html .= '<li class="pocketcasts">' . __( 'Pocket Casts', 'seriously-simple-stats' ) . ': <b>' . $pocketcasts . '</b></li>';
				}
				if( $direct ) {
					$html .= '<li class="direct">' . __( 'Direct download', 'seriously-simple-stats' ) . ': <b>' . $direct . '</b></li>';
				}
				if( $new_window ) {
					$html .= '<li class="new_window">' . __( 'Played in new window', 'seriously-simple-stats' ) . ': <b>' . $new_window . '</b></li>';
				}
				if( $player ) {
					$html .= '<li class="player">' . __( 'Audio player', 'seriously-simple-stats' ) . ': <b>' . $player . '</b></li>';
				}
				if( $unknown ) {
					$html .= '<li class="unknown">' . __( 'Other', 'seriously-simple-stats' ) . ': <b>' . $unknown . '</b></li>';
				}
			$html .= '</ul>';

			$html .= '<p>' . sprintf( __( '%1$sSee more detail %2$s%3$s', 'seriously-simple-stats' ), '<a href="' . admin_url( 'edit.php?post_type=podcast&page=podcast_stats&filter=episode&episode=' . $post->ID ) . '">', '&raquo;', '</a>' ) . '<p>';
		} else {
			$html .= '<div class="no-activity">' . "\n";
				$html .= '<p class="smiley"></p>' . "\n";
				$html .= '<p>' . __( 'No stats for this episode yet!', 'seriously-simple-stats' ) . '</p>' . "\n";
			$html .= '</div>' . "\n";
		}

		echo $html;
	}

	/**
	 * Add stats page to menu
	 * @access public
	 * @since  1.0.0
	 * @return  void
	 */
	public function add_menu_item() {
		add_submenu_page( 'edit.php?post_type=podcast' , __( 'Podcast Stats', 'seriously-simple-stats' ) , __( 'Stats', 'seriously-simple-stats' ), 'manage_podcast' , 'podcast_stats' , array( $this , 'stats_page' ) );
	}

	/**
	 * Load content for stats page
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function stats_page () {
		global $wp_version, $wpdb;

		$metabox_title = 'h2';
		if( version_compare( $wp_version, '4.4', '<' ) ) {
			$metabox_title = 'h3';
		}

		$title_tail = $no_stats_filler = '';
		switch( $this->filter ) {
			case 'series':
				if( 'all' != $this->series ) {
					$series_obj = get_term_by( 'slug', $this->series, 'series' );
					$series_name = $series_obj->name;
					if( $series_name ) {
						$title_tail = sprintf( __( 'for Series: %s', 'seriously-simple-stats' ), '<u>' . $series_name . '</u>' );
						$no_stats_filler = __( 'for this series', 'seriously-simple-stats' );
					}
				}
			break;
			case 'episode':
				if( 'all' != $this->episode ) {
					$episode_name = get_the_title( $this->episode );
					if( $episode_name ) {
						$title_tail = sprintf( __( 'for Episode: %s', 'seriously-simple-stats' ), '<u>' . $episode_name . '</u>' );
						$no_stats_filler = __( 'for this episode', 'seriously-simple-stats' );
					}
				}
			break;
		}

		$page_title = sprintf( __( 'Podcast Stats %s' , 'seriously-simple-stats' ), $title_tail );

		$html = '<div class="wrap" id="podcast_settings">' . "\n";
			$html .= '<h1>' . $page_title . '</h1>' . "\n";

			$html .= '<div class="metabox-holder">' . "\n";

				// Count total entries for episode selection
				$count_entries_sql = "SELECT COUNT(id) FROM $this->_table";
				if( $this->episode_id_where ) {
					$count_stats_episode_id_where = 'WHERE ' . $this->episode_id_where;
					$count_entries_sql = $count_entries_sql . " $count_stats_episode_id_where";
				}
				$total_entries = $wpdb->get_var( $count_entries_sql );

				if( ! $total_entries ) {
					$html .= '<div class="" id="no-stats-container">' . "\n";
						$html .= '<div class="inside no-activity">' . "\n";
							$html .= '<p class="smiley"></p>' . "\n";
							$html .= '<p>' . sprintf( __( 'No stats %s yet!', 'seriously-simple-stats' ), $no_stats_filler ) . '</p>' . "\n";
						$html .= '</div>' . "\n";
					$html .= '</div>' . "\n";
				} else {

					$html .= '<div class="postbox" id="content-filter-container">' . "\n";
						$html .= '<div class="inside">' . "\n";
							$html .= '<form action="" method="get" name="ssp-stats-content-filter">' . "\n";
								$html .= '<strong>' . "\n";
								foreach( $_GET as $param => $value ) {
									if( in_array( $param, array( 'post_type', 'page', 'start', 'end' ) ) ) {
										$html .= '<input type="hidden" name="' . esc_attr( $param ) . '" value="' . esc_attr( $value ) . '" />';
									}
								}

								switch( $this->filter ) {
									case 'episode':
										$series_class = 'hidden';
										$episode_class = '';
									break;
									case 'series':
										$series_class = '';
										$episode_class = 'hidden';
									break;
									default:
										$series_class = 'hidden';
										$episode_class = 'hidden';
									break;
								}

								$html .= __( 'View stats for', 'seriously-simple-stats' ) . "\n";
								$html .= ' <select name="filter" id="content-filter-select">' . "\n";
									$html .= '<option value="" ' . selected( '', $this->filter, false ) . '>' . __( 'All episodes', 'seriously-simple-stats' ) . '</option>' . "\n";
									$html .= '<option value="series" ' . selected( 'series', $this->filter, false ) . '>' . __( 'An individual series', 'seriously-simple-stats' ) . '</option>' . "\n";
									$html .= '<option value="episode" ' . selected( 'episode', $this->filter, false ) . '>' . __( 'An individual episode', 'seriously-simple-stats' ) . '</option>' . "\n";
								$html .= '</select>' . "\n";

								// Series selection
								$html .= '<span id="by-series-selection" class="' . esc_attr( $series_class ) . '">' . "\n";
									$series = get_terms( 'series' );
									$html .= '&raquo;' . "\n";
									$html .= '<select name="series">' . "\n";
										foreach( $series as $s ) {
											$html .= '<option value="' . esc_attr( $s->slug ) . '" ' . selected( $this->series, $s->slug, false ) . '>' . esc_html( $s->name ) . '</option>' . "\n";
										}
									$html .= '</select>' . "\n";
								$html .= '</span>' . "\n";

								// Episode selection
								$html .= '<span id="by-episode-selection" class="' . esc_attr( $episode_class ) . '">' . "\n";
									$episodes_args = ssp_episodes( -1, '', true, 'stats' );
									$episodes_args['orderby'] = 'title';
									$episodes_args['order'] = 'ASC';
									$episodes = get_posts( $episodes_args );
									$html .= '&raquo;' . "\n";
									$html .= '<select name="episode">' . "\n";
										foreach( $episodes as $episode ) {
											$html .= '<option value="' . esc_attr( $episode->ID ) . '" ' . selected( $this->episode, $episode->ID, false ) . '>' . esc_html( $episode->post_title ) . '</option>' . "\n";
										}
									$html .= '</select>' . "\n";
								$html .= '</span>' . "\n";

								$html .= '<input type="submit" id="content-filter-button" class="hidden button" value="' . __( 'Apply', 'seriously-simple-stats' ) . '" />' . "\n";
								$html .= '</strong>' . "\n";
							$html .= '</form>' . "\n";
						$html .= '</div>' . "\n";
					$html .= '</div>' . "\n";

					$html .= '<div class="postbox">' . "\n";
						$html .= '<' . $metabox_title . ' class="hndle ui-sortable-handle">' . "\n";
					    	$html .= '<span>' . __( 'At a Glance', 'seriously-simple-stats' ) . '</span>' . "\n";
						$html .= '</' . $metabox_title . '>' . "\n";
						$html .= '<div class="inside">' . "\n";

							$episode_id_where = '';
							if( $this->episode_id_where ) {
								$episode_id_where = 'AND ' . $this->episode_id_where;
							}

							// Listens today
							$start_of_day = strtotime( date( 'Y-m-d 00:00:00', $this->current_time ) );
							$listens_today = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $this->_table WHERE date BETWEEN %d AND %d $episode_id_where", $start_of_day, $this->current_time ) );
							$html .= $this->daily_stat( $listens_today, __( 'Listens today', 'seriously-simple-stats' ) );

							// Listens this week
							$one_week_ago = strtotime( '-1 week', $this->current_time );
							$listens_this_week = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $this->_table WHERE date BETWEEN %d AND %d $episode_id_where", $one_week_ago, $this->current_time ) );
							$html .= $this->daily_stat( $listens_this_week, __( 'Listens this week', 'seriously-simple-stats' ) );

							// Listens last week
							$two_weeks_ago = strtotime( '-1 week', $one_week_ago );
							$listens_last_week = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $this->_table WHERE date BETWEEN %d AND %d $episode_id_where", $two_weeks_ago, $one_week_ago ) );
							$html .= $this->daily_stat( $listens_last_week, __( 'Listens last week', 'seriously-simple-stats' ) );

							// Change from last week
							if( ! $listens_last_week ) {
								$week_diff = '-';
							} else {
								$week_diff = round( ( $listens_this_week / $listens_last_week * 100 ), 1 );
								if( $week_diff < 100 ) {
									$week_diff = '-' . ( 100 - $week_diff ) . '%';
								} elseif( $week_diff > 100 ) {
									$week_diff = '+' . ( $week_diff - 100 ) . '%';
								} else {
									$week_diff = '0%';
								}
							}
							$html .= $this->daily_stat( $week_diff, __( 'Change from last week', 'seriously-simple-stats' ) );

							$html .= '<br class="clear" />';

						$html .= '</div>' . "\n";
					$html .= '</div>' . "\n";

					$html .= '<div class="postbox" id="daily-listens-container">' . "\n";

						// Date range selection
						$start_date_select = '<input type="text" id="start-date-filter_display" class="ssp-datepicker" placeholder="' . __( 'Start date', 'seriously-simple-stats' ) . '" value="' . esc_attr( date( 'j F, Y', $this->start_date ) ) . '" /><input type="hidden" id="start-date-filter" name="start" value="' . esc_attr( date( 'd-m-Y', $this->start_date ) ) . '" />';
						$end_date_select = '<input type="text" id="end-date-filter_display" class="ssp-datepicker" placeholder="' . __( 'End date', 'seriously-simple-stats' ) . '" value="' . esc_attr( date( 'j F, Y', $this->end_date ) ) . '" /><input type="hidden" id="end-date-filter" name="end" value="' . esc_attr( date( 'd-m-Y', $this->end_date ) ) . '" />';
						$date_select_submit = '<input id="date_select_submit" class="hidden button" type="submit" value="' . __( 'Apply', 'seriously-simple-stats' ) . '" />';
						$date_select_hidden = '';
						foreach( $_GET as $param => $value ) {
							if( in_array( $param, array( 'post_type', 'page', 'filter', 'episode', 'series' ) ) ) {
								$date_select_hidden .= '<input type="hidden" name="' . esc_attr( $param ) . '" value="' . esc_attr( $value ) . '" />';
							}
						}

						$html .= '<' . $metabox_title . ' class="hndle ui-sortable-handle">' . "\n";
							$html .= '<form action="" method="get" name="ssp-stats-date-filter" class="hasDatepicker">' . "\n";
								$html .= $date_select_hidden;
					    		$html .= '<span>' . sprintf( __( 'Stats for %s to %s %s', 'seriously-simple-stats' ), $start_date_select, $end_date_select, $date_select_submit ) . '</span>' . "\n";
					    	$html .= '</form>' . "\n";
						$html .= '</' . $metabox_title . '>' . "\n";

						$html .= '<div class="inside">' . "\n";

							$html .= '<' . $metabox_title . ' class="hndle ui-sortable-handle">' . "\n";
						    	$html .= '<span>' . __( 'Daily Listens', 'seriously-simple-stats' ) . '</span>' . "\n";
							$html .= '</' . $metabox_title . '>' . "\n";
							$html .= '<div id="daily_listens"></div>' . "\n";

							$html .= '<' . $metabox_title . ' class="hndle ui-sortable-handle">' . "\n";
						    	$html .= '<span>' . __( 'Listening Sources', 'seriously-simple-stats' ) . '</span>' . "\n";
							$html .= '</' . $metabox_title . '>' . "\n";
							$html .= '<div id="listening-sources"></div>' . "\n";

						$html .= '</div>' . "\n";
					$html .= '</div>' . "\n";

					$html .= '<div class="postbox" id="top-ten-container">' . "\n";
						$html .= '<' . $metabox_title . ' class="hndle ui-sortable-handle">' . "\n";
					    	$html .= '<span>' . __( 'Top Ten Episodes of All Time', 'seriously-simple-stats' ) . '</span>' . "\n";
						$html .= '</' . $metabox_title . '>' . "\n";
						$html .= '<div class="inside">' . "\n";

							$sql = "SELECT COUNT(id) AS listens, post_id FROM $this->_table GROUP BY post_id ORDER BY listens DESC LIMIT 10";
							$results = $wpdb->get_results( $sql );

							$html .= '<ul>' . "\n";
								$li_class = 'alternate';
								foreach( $results as $result ) {
									$episode = get_post( $result->post_id );
									$episode_link = admin_url( 'post.php?post=' . $episode->ID . '&action=edit' );
									$html .= '<li class="' . esc_attr( $li_class ) . '"><span class="first-col top-ten-count">' . sprintf( _n( '%d %slisten%s', '%d %slistens%s', $result->listens, 'seriously-simple-stats' ), $result->listens, '<span>', '</span>' ) . '</span> <span class="top-ten-title"><a href="' . $episode_link . '">' . esc_html( $episode->post_title ) . '</a></span></li>' . "\n";
									if( '' == $li_class ) {
										$li_class = 'alternate';
									} else {
										$li_class = '';
									}
								}
							$html .= '</ul>' . "\n";

						$html .= '</div>' . "\n";
					$html .= '</div>' . "\n";
				}

			$html .= '</div>' . "\n";
		$html .= '</div>' . "\n";

		$wpdb->flush();

		echo $html;
	}

	private function daily_stat ( $number = '', $description = '' ) {

		$html = '<div class="overview-stat">' . "\n";
			$html .= '<div class="stat-total">' . $number . '</div>' . "\n";
			$html .= '<div class="stat-description">' . $description . '</div>' . "\n";
		$html .= '</div>' . "\n";

		return $html;
	}

	/**
	 * Output data for generating charts
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function chart_data () {

		$output = '';

		$output .= $this->daily_listens_chart();
		$output .= $this->referrers_chart();

		echo $output;
	}

	/**
	 * Return data for Daily Listens chart
	 * @access private
	 * @since  1.0.0
	 * @param  integer $start_date Start date of chart data
	 * @param  integer $end_date   End date of chart data
	 * @return string              Javascript for chart generation
	 */
	private function daily_listens_chart () {
		global $wpdb;

		$columns = array(
			__( 'Date', 'seriously-simple-stats' ) => 'string',
			__( 'Listens', 'seriously-simple-stats' ) => 'number',
		);

		$episode_id_where = '';
		if( $this->episode_id_where ) {
			$episode_id_where = 'AND ' . $this->episode_id_where;
		}

		$sql = $wpdb->prepare( "SELECT date FROM $this->_table WHERE date BETWEEN %d AND %d $episode_id_where", $this->start_date, $this->end_date );
		$results = $wpdb->get_results( $sql );

		$date_data = array();
		foreach( $results as $timestamp ) {
			$date = date( get_option( 'date_format' ), $timestamp->date );
			if( isset( $date_data[ $date ] ) ) {
				++$date_data[ $date ];
			} else {
				$date_data[ $date ] = 1;
			}
		}

		$data = array();
		foreach( $date_data as $date => $listens ) {
			$data[] = array( $date, $listens );
		}

		return $this->generate_chart( 'LineChart', '', $columns, $data, 'daily_listens' );
	}

	/**
	 * Return data for Referrers chart
	 * @access private
	 * @since  1.0.0
	 * @param  integer $start_date Start date of chart data
	 * @param  integer $end_date   End date of chart data
	 * @return string              Javascript for chart generation
	 */
	private function referrers_chart () {
		global $wpdb;

		$columns = array(
			__( 'Sources', 'seriously-simple-stats' ) => 'string',
			__( 'Listens', 'seriously-simple-stats' ) => 'number',
		);

		$episode_id_where = '';
		if( $this->episode_id_where ) {
			$episode_id_where = 'AND ' . $this->episode_id_where;
		}

		// Get all tracked data fro selected criteria
		$sql = $wpdb->prepare( "SELECT referrer FROM $this->_table WHERE date BETWEEN %d AND %d $episode_id_where", $this->start_date, $this->end_date );
		$results = $wpdb->get_results( $sql );

		$referrer_data = array();
		foreach( $results as $ref ) {

			$referrer = $ref->referrer;

			// Label empty referrer as 'other'
			if( '' == $referrer ) {
				$referrer = 'other';
			}

			if( isset( $referrer_data[ $referrer ] ) ) {
				++$referrer_data[ $referrer ];
			} else {
				$referrer_data[ $referrer ] = 1;
			}

		}

		// Give human readable labels to referrers
		$referrer_labels = array(
			'download' => __( 'Direct download', 'seriously-simple-stats' ),
			'player' => __( 'Audio player', 'seriously-simple-stats' ),
			'new_window' => __( 'Played in new window', 'seriously-simple-stats' ),
			'itunes' => __( 'iTunes', 'seriously-simple-stats' ),
			'stitcher' => __( 'Stitcher', 'seriously-simple-stats' ),
			'overcast' => __( 'Overcast', 'seriously-simple-stats' ),
			'pocketcasts' => __( 'Pocket Casts', 'seriously-simple-stats' ),
			'other' => __( 'Other', 'seriously-simple-stats' ),
		);

		$data = array();
		foreach( $referrer_data as $ref => $listens ) {
			$ref_label = '';

			// Set values and labels for pie chart
			if( isset( $referrer_labels[ $ref ] ) ) {
				$ref_label = $referrer_labels[ $ref ];
			} else {
				// Allow 'Unknown' label as a backup, even though this probably won't ever get used
				$ref_label = __( 'Unknown', 'seriously-simple-stats' );
			}

			$data[] = array( $ref_label, $listens );
		}

		// Generate pie chart
		return $this->generate_chart( 'PieChart', '', $columns, $data, 'listening-sources', 500 );
	}

	/**
	 * Return chart generation javascript
	 * @access private
	 * @since  1.0.0
	 * @param  string  $type    Chart type
	 * @param  string  $title   Chart title
	 * @param  array   $columns Chart columns
	 * @param  array   $data    Chart data
	 * @param  string  $target  Target element in which to draw chart
	 * @param  integer $height  Chart height
	 * @param  string  $width   Chart width
	 * @return string           Chart javascript
	 */
	private function generate_chart ( $type = '', $title = '', $columns = array(), $data = array(), $target = '', $height = 400, $width = '100%' ) {

		if( ! $type || ! $target || ! is_array( $columns )  || ! is_array( $data ) ) {
			return;
		}

		$column_data = '';
		foreach( $columns as $column_label => $column_type ) {
			$column_data .= "data.addColumn('$column_type', '$column_label');" . "\n";
		}

		$chart_data = '';
		foreach( $data as $data_set ) {
			$chart_data .= "[";
			foreach( $data_set as $item ) {
				if( is_numeric( $item ) ) {
					$chart_data .= "$item,";
				} else {
					$chart_data .= "'$item',";
				}
			}
			$chart_data .= "]," . "\n";
		}

		// Get comprehensively unique ID for chart
		$chartid = uniqid() . '_' . mt_rand( 0, 9999 );

		$options = '';
		switch( $type ) {
			case 'LineChart':
				$options .= "legend: 'none'," . "\n";
				$options .= "vAxis: { minValue: 0 }" . "\n";
			break;
			case 'PieChart':
				$options .= "is3D: true" . "\n";
			break;
		}

		if( $options ) {
			$options .= ',';
		}

		ob_start();
		?>
		<script type="text/javascript">

			// Set a callback to run when the Google Visualization API is loaded
			google.setOnLoadCallback(draw_chart_<?php echo $chartid; ?>);

			// Draw chart using supplied data
			function draw_chart_<?php echo $chartid; ?>() {

				// Create the data table
				var data = new google.visualization.DataTable();
				<?php echo $column_data; ?>
				data.addRows([ <?php echo $chart_data; ?> ]);

				// Set chart options
				var options = {
					title: '<?php echo $title; ?>',
					width: '<?php echo $width; ?>',
					height: '<?php echo $height; ?>',
					<?php echo $options; ?>
				};

				// Instantiate and draw the chart
				var chart = new google.visualization.<?php echo $type; ?>( document.getElementById( '<?php echo $target; ?>' ) );
				chart.draw( data, options );
				// chart.draw( data, google.charts.Line.convertOptions(options) );
			}

	    </script>
	    <?php

	    return ob_get_clean();
	}

	/**
	 * Load admin Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_scripts ( $hook = '' ) {

		if( 'podcast_page_podcast_stats' == $hook ) {

			// Include Google Charts scripts
			wp_enqueue_script( 'google-charts', "//www.google.com/jsapi?autoload={
										            'modules':[{
										              'name':'visualization',
										              'version':'1',
										              'packages':['corechart']
										            }]
										          }", array(), $this->_version, false );

			// Load custom scripts
			wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
			wp_enqueue_script( $this->_token . '-admin' );
		}
	} // End admin_enqueue_scripts ()

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
	 * Load plugin localisation
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_localisation () {
		load_plugin_textdomain( 'seriously-simple-stats', false, basename( $this->dir ) . '/languages/' );
	} // End load_localisation ()

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
