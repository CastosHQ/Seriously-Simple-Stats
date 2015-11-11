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
	 * Chart series selection.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $series = 'all';

	/**
	 * Chart episode selection.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $episode = 'all';

	/**
	 * Chart filter selection.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $filter = 'series';

	/**
	 * Chart episode IDs for filtering.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $episode_ids = '';

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

		// Set start date for charts
		if( isset( $_GET['start'] ) ) {
			$this->start_date = strtotime( sanitize_text_field( $_GET['start'] ) );
		} else {
			$this->start_date = strtotime( '1 month ago' );
		}

		// Set end date for charts
		if( isset( $_GET['end'] ) ) {
			$this->end_date = strtotime( date( 'Y-m-d 23:59:59', strtotime( sanitize_text_field( $_GET['end'] ) ) ) );
		} else {
			$this->end_date = time();
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
		add_action( 'init', array( $this, 'update_database' ), 0 );

		// Track episode download
		add_action( 'ssp_file_download', array( $this, 'track_download' ), 10, 3 );

		// Add stats meta box to episodes edit screen
		add_action( 'ssp_meta_boxes', array( $this, 'post_meta_box' ), 10, 1 );

		// Load admin JS & CSS

		add_action( 'admin_menu', array( $this , 'add_menu_item' ) );

		// Load necessary javascript for charts
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_print_scripts', array( $this, 'chart_data' ), 30 );

		// Load admin CSS
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
			// return;
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
		add_meta_box( 'podcast-episode-stats', __( 'Episode Stats' , 'seriously-simple-stats' ), array( $this, 'stats_meta_box_content' ), $post->post_type, 'side', 'default' );
	}

	public function stats_meta_box_content ( $post ) {

		if( ! isset( $post->ID ) ) {
			return;
		}

		$stats = $this->get_episode_stats( $post->ID, array( 'ip_address', 'referrer') );

		$total_downloads = count( $stats );

		$html = '<p class="episode-stat-data total-downloads">' . __( 'Total downloads', 'seriously-simple-stats' ) . ': <b>' . $total_downloads . '</b></p>';

		if( $total_downloads ) {

			$users = array();
			$itunes = $stitcher = $direct = $new_window = $player = $unknown = 0;

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
			$html .= '<p class="episode-stat-data sources">' . __( 'Download sources', 'seriously-simple-stats' ) . ':</p>';
			$html .= '<ul class="sources-list">';
				if( $itunes ) {
					$html .= '<li class="itunes">' . __( 'iTunes', 'seriously-simple-stats' ) . ': <b>' . $itunes . '</b></li>';
				}
				if( $stitcher ) {
					$html .= '<li class="stitcher">' . __( 'Stitcher', 'seriously-simple-stats' ) . ': <b>' . $stitcher . '</b></li>';
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
					$html .= '<li class="unknown">' . __( 'Unknown', 'seriously-simple-stats' ) . ': <b>' . $unknown . '</b></li>';
				}
			$html .= '</ul>';
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
		global $wp_version;

		$html = '<div class="wrap" id="podcast_settings">' . "\n";
			$html .= '<h1>' . __( 'Podcast Stats' , 'seriously-simple-stats' ) . '</h1>' . "\n";
			$html .= '<div class="metabox-holder">' . "\n";

				$html .= '<div class="wp-filter">' . "\n";
					$html .= '<form action="" method="get" name="ssp-stats-date-filter" class="filter-items hasDatepicker">' . "\n";

						$html .= '<input type="hidden" name="post_type" value="podcast" />' . "\n";
						$html .= '<input type="hidden" name="page" value="podcast_stats" />' . "\n";

						// Date range selection
						$html .= '<p>' . "\n";

							$html .= __( 'Date range:', 'seriously-simple-stats' ) . ' ' . "\n";

							$html .= '<label for="start-date-filter" class="screen-reader-text">' . __( 'Start date', 'seriously-simple-stats' ) . '</label>' . "\n";
							$html .= '<input type="text" id="start-date-filter_display" class="ssp-datepicker" placeholder="' . __( 'Start date', 'seriously-simple-stats' ) . '" value="' . esc_attr( date( 'j F, Y', $this->start_date ) ) . '" />' . "\n";
							$html .= '<input type="hidden" id="start-date-filter" name="start" value="' . esc_attr( date( 'd-m-Y', $this->start_date ) ) . '" />' . "\n";

							$html .= ' ' . __( 'to', 'seriously-simple-stats' ) . ' ' . "\n";

							$html .= '<label for="start-date-filter" class="screen-reader-text">' . __( 'End date', 'seriously-simple-stats' ) . '</label>' . "\n";
							$html .= '<input type="text" id="end-date-filter_display" class="ssp-datepicker" placeholder="' . __( 'End date', 'seriously-simple-stats' ) . '" value="' . esc_attr( date( 'j F, Y', $this->end_date ) ) . '" />' . "\n";
							$html .= '<input type="hidden" id="end-date-filter" name="end" value="' . esc_attr( date( 'd-m-Y', $this->end_date ) ) . '" />' . "\n";

						$html .= '</p>' . "\n";

						$html .= '<hr/>' . "\n";

						$html .= '<p>' . "\n";
							$html .= __( 'Filter by:', 'seriously-simple-stats' ) . "\n";
							$html .= '<input type="radio" name="filter" value="series" class="filter-option" id="by-series" ' . checked( 'series', $this->filter, false ) . ' /><label for="by-series">' . __( 'Series or', 'seriously-simple-stats' ) . '</label>' . "\n";
							$html .= '<input type="radio" name="filter" value="episode" class="filter-option" id="by-episode" ' . checked( 'episode', $this->filter, false ) . ' /><label for="by-episode">' . __( 'Episode', 'seriously-simple-stats' ) . '</label>' . "\n";
						$html .= '</p>' . "\n";

						switch( $this->filter ) {
							case 'series':
								$series_class = '';
								$episode_class = 'hidden';
							break;
							case 'episode':
								$series_class = 'hidden';
								$episode_class = '';
							break;
						}

						// Series selection
						$html .= '<p id="by-series-selection" class="' . esc_attr( $series_class ) . '">' . "\n";
							$series = get_terms( 'series' );
							$html .= __( 'Select a series:', 'seriously-simple-stats' ) . "\n";
							$html .= '<select name="series">' . "\n";
								$html .= '<option value="all">' . __( 'All series', 'seriously-simple-stats' ) . '</option>' . "\n";
								foreach( $series as $s ) {
									$html .= '<option value="' . esc_attr( $s->slug ) . '" ' . selected( $this->series, $s->slug, false ) . '>' . esc_html( $s->name ) . '</option>' . "\n";
								}
							$html .= '</select>' . "\n";
						$html .= '</p>' . "\n";

						// Episode selection
						$html .= '<p id="by-episode-selection" class="' . esc_attr( $episode_class ) . '">' . "\n";
							$episodes_args = ssp_episodes( -1, '', true, 'stats' );
							$episodes_args['orderby'] = 'title';
							$episodes_args['order'] = 'ASC';
							$episodes = get_posts( $episodes_args );
							$html .= __( 'Select an episode:', 'seriously-simple-stats' ) . "\n";
							$html .= '<select name="episode">' . "\n";
								$html .= '<option value="all">' . __( 'All episodes', 'seriously-simple-stats' ) . '</option>' . "\n";
								foreach( $episodes as $episode ) {
									$html .= '<option value="' . esc_attr( $episode->ID ) . '" ' . selected( $this->episode, $episode->ID, false ) . '>' . esc_html( $episode->post_title ) . '</option>' . "\n";
								}
							$html .= '</select>' . "\n";
						$html .= '</p>' . "\n";

						$html .= '<hr/>' . "\n";

						$html .= '<p>' . "\n";
							$html .= '<input type="submit" class="button" value="' . __( 'Filter', 'seriously-simple-stats' ) . '">' . "\n";
						$html .= '</p>' . "\n";

					$html .= '</form>' . "\n";
				$html .= '</div>' . "\n";

				$metabox_title = 'h2';
				if( version_compare( $wp_version, '4.4', '<' ) ) {
					$metabox_title = 'h3';
				}

				$html .= '<div class="postbox" id="daily-listens-container">' . "\n";
					$html .= '<' . $metabox_title . ' class="hndle ui-sortable-handle">' . "\n";
				    	$html .= '<span>' . __( 'Daily Listens', 'seriously-simple-stats' ) . '</span>' . "\n";
					$html .= '</' . $metabox_title . '>' . "\n";
					$html .= '<div class="inside">' . "\n";
						$html .= '<div id="daily_listens"></div>';
					$html .= '</div>' . "\n";
				$html .= '</div>' . "\n";

				$html .= '<div class="postbox" id="referrers-container">' . "\n";
					$html .= '<' . $metabox_title . ' class="hndle ui-sortable-handle">' . "\n";
				    	$html .= '<span>' . __( 'Referrers', 'seriously-simple-stats' ) . '</span>' . "\n";
					$html .= '</' . $metabox_title . '>' . "\n";
					$html .= '<div class="inside">' . "\n";
						$html .= '<div id="referrers"></div>';
					$html .= '</div>' . "\n";
				$html .= '</div>' . "\n";

			$html .= '</div>' . "\n";
		$html .= '</div>' . "\n";

		echo $html;
	}

	/**
	 * Output data for generating charts
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function chart_data () {

		$output = '';

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

		// Set up WHERE clause if episode/series is selected
		$episode_id_where = '';
		if( $this->episode_ids ) {
			$episode_id_where = 'AND post_id IN (' . $this->episode_ids . ')';
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
			__( 'Referrers', 'seriously-simple-stats' ) => 'string',
			__( 'Listens', 'seriously-simple-stats' ) => 'number',
		);

		// Set up WHERE clause if episode/series is selected
		$episode_id_where = '';
		if( $this->episode_ids ) {
			$episode_id_where = 'AND post_id IN (' . $this->episode_ids . ')';
		}

		$sql = $wpdb->prepare( "SELECT referrer FROM $this->_table WHERE date BETWEEN %d AND %d $episode_id_where", $this->start_date, $this->end_date );
		$results = $wpdb->get_results( $sql );

		$referrer_data = array();
		foreach( $results as $ref ) {
			$referrer = $ref->referrer;
			if( isset( $referrer_data[ $referrer ] ) ) {
				++$referrer_data[ $referrer ];
			} else {
				$referrer_data[ $referrer ] = 1;
			}
		}

		$referrer_labels = array(
			'download' => __( 'Direct download', 'seriously-simple-stats' ),
			'player' => __( 'Audio player', 'seriously-simple-stats' ),
			'new_window' => __( 'Played in new window', 'seriously-simple-stats' ),
			'itunes' => __( 'iTunes', 'seriously-simple-stats' ),
			'stitcher' => __( 'Stitcher', 'seriously-simple-stats' ),
			'' => __( 'Other', 'seriously-simple-stats' ),
		);

		$data = array();
		foreach( $referrer_data as $ref => $listens ) {
			$ref_label = '';
			if( isset( $referrer_labels[ $ref ] ) ) {
				$ref_label = $referrer_labels[ $ref ];
			}
			$data[] = array( $ref_label, $listens );
		}

		return $this->generate_chart( 'PieChart', '', $columns, $data, 'referrers', 500 );
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
