<?php

namespace SeriouslySimpleStats\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class All_Episode_Stats {

	/**
	 * Stats table name
	 *
	 * @var string
	 */
	private $table = '';

	/**
	 * Current last three months dates
	 *
	 * @var array
	 */
	private $dates = array();

	/**
	 * Total number of posts
	 *
	 * @var int
	 */
	public $total_posts = 0;

	/**
	 * Posts per page, for pagination
	 *
	 * @var int
	 */
	public $total_per_page = 0;

	/**
	 * All_Episode_Stats constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'ssp_stats';
		$this->dates = array(
			intval( current_time( 'm' ) )                                             => current_time( 'F' ),
			intval( date( 'm', strtotime( current_time( 'Y-m-d' ) . 'FIRST DAY OF -1 MONTH' ) ) ) => date( 'F', strtotime( current_time( "Y-m-d" ) . 'FIRST DAY OF -1 MONTH' ) ),
			intval( date( 'm', strtotime( current_time( 'Y-m-d' ) . 'FIRST DAY OF -2 MONTH' ) ) ) => date( 'F', strtotime( current_time( "Y-m-d" ) . 'FIRST DAY OF -2 MONTH' ) ),
		);
	}

	/**
	 * Render episode stats for the last three months, with sorting
	 *
	 * @return false|string
	 */
	public function render_all_episodes_stats(){
		$all_episodes_stats = $this->get_all_episode_stats();
		$sort_order = $this->get_all_episodes_sort_order();
		$html = '';
		ob_start();
		require_once SSP_STATS_DIR_PATH . 'partials/stats-all-episodes.php';
		$html = ob_get_clean();

		//Only show page links if there's more than 25 episodes in the table
		if ( $this->total_posts > $this->total_per_page ) {
			if ( isset( $_GET['pagenum'] ) ) {
				$pagenum = intval( $_GET['pagenum'] );
			} else {
				$pagenum = 1;
			}
			$total_pages   = intval( ceil( $this->total_posts / $this->total_per_page ) );
			$order_by_qry  = isset( $_GET['orderby'] ) ? '&orderby=' . esc_attr( $_GET['orderby'] ) : "";
			$order         = $this->get_requested_order();
			$order_qry     = $order ? '&order=' . esc_attr( $order ) : "";
			$prev_page     = ( $pagenum <= 1 ) ? 1 : $pagenum - 1;
			$prev_page_url = admin_url( "edit.php?post_type=" . SSP_CPT_PODCAST . "&page=podcast_stats" . $order_by_qry . $order_qry . "&pagenum=" . $prev_page . "#last-three-months-container" );
			$next_page     = $pagenum + 1;
			$next_page_url = admin_url( "edit.php?post_type=" . SSP_CPT_PODCAST . "&page=podcast_stats" . $order_by_qry . $order_qry . "&pagenum=" . $next_page . "#last-three-months-container" );
			ob_start();
			require_once SSP_STATS_DIR_PATH . 'partials/stats-all-episodes-pagination.php';
			$html .= ob_get_clean();
		}

		return $html;
	}

	/**
	 *
	 * @return string|null
	 */
	private function get_requested_order() {
		if ( ! isset( $_GET['order'] ) ) {
			return null;
		}
		$order          = strtolower( $_GET['order'] );
		$allowed_orders = array( 'asc', 'desc' );

		return in_array( $order, $allowed_orders ) ? $order : null;
	}

	/**
	 * Get all episode stats to be rendered in the stats-all-episode partial
	 *
	 * @return array
	 */
	private function get_all_episode_stats() {
		$order_by           = isset( $_GET['orderby'] ) ? esc_attr( $_GET['orderby'] ) : 'date';
		$order              = $this->get_requested_order() ?: 'desc';
		$all_episodes_stats = $this->get_sorted_stats( $order_by, $order );

		$this->total_posts    = count( $all_episodes_stats );
		$this->total_per_page = apply_filters( 'ssp_stats_three_months_per_page', 25 );

		// Sets up pagination
		if ( isset( $_GET['pagenum'] ) ) {
			$pagenum = intval( $_GET['pagenum'] );
			if ( $pagenum <= 1 ) {
				$current_cursor = 0;
			} else {
				$current_cursor = ( $pagenum - 1 ) * $this->total_per_page;
			}
			$all_episodes_stats = array_slice( $all_episodes_stats, $current_cursor, $this->total_per_page, true );
		} else {
			$all_episodes_stats = array_slice( $all_episodes_stats, 0, $this->total_per_page, true );
		}

		return $all_episodes_stats;
	}

	/**
	 * @param string $order_by
	 * @param string $order
	 *
	 * @return array|void
	 */
	private function get_sorted_stats( $order_by, $order ) {

		$transient_key = sprintf( 'ssp_sorted_stats_three_months_%s_%s', $order_by, $order );

		if ( $sorted_stats = get_transient( $transient_key ) ?: array() ) {
			return $sorted_stats;
		}

		$all_episodes_stats = $this->fetch_all_episodes_stats();

		if ( empty( $all_episodes_stats ) ) {
			return array();
		}

		//Sort array based on ordering fields passed
		foreach ( $all_episodes_stats as $listen ) {
			$listen_sorting[] = $listen[ $order_by ];
		}

		if ( 'desc' === $order ) {
			array_multisort( $listen_sorting, SORT_DESC, $all_episodes_stats );
		} else {
			array_multisort( $listen_sorting, SORT_ASC, $all_episodes_stats );
		}

		set_transient( $transient_key, $all_episodes_stats, MINUTE_IN_SECONDS );

		return $all_episodes_stats;
	}

	/**
	 * Fetches all episodes stats for the last 3 months from the database
	 *
	 * @return array
	 */
	private function fetch_all_episodes_stats(){

		$all_episodes_stats = array();

		global $wpdb;

		$sql         = "SELECT COUNT(id) AS listens, post_id FROM $this->table GROUP BY post_id";
		$total_stats = $wpdb->get_results( $sql );

		$all_episodes_lifetime_stats = array();
		foreach ( $total_stats as $total_stat ) {
			$all_episodes_lifetime_stats[ $total_stat->post_id ] = intval( $total_stat->listens );
		}

		if ( ! is_array( $total_stats ) ) {
			return $all_episodes_stats;
		}

		$last_months_stats = array();

		foreach ( $this->dates as $month_number => $month_name ) {

			$year = date( 'Y' );
			if ( 12 === $month_number ) {
				$year = date( 'Y', strtotime( current_time( 'Y-m-d' ) . 'FIRST DAY OF -1 YEAR' ) );
			}

			$start_month_template = sprintf( '%s-%%s-01 00:00:00', $year );
			$end_month_template   = sprintf( '%s-%%s-%s 23:59:59', $year, date( 't' ) );
			
			$month_formatted = sprintf( "%02d", $month_number );
			$month_start     = strtotime( sprintf( $start_month_template, $month_formatted ) );
			$month_end       = strtotime( sprintf( $end_month_template, $month_formatted ) );

			$month_sql = $wpdb->prepare( "SELECT COUNT(id) as `listens`, `post_id` FROM `$this->table` WHERE `date` >= %d AND `date` <= %d GROUP BY post_id", $month_start, $month_end );

			$month_stats = $wpdb->get_results( $month_sql );

			if ( ! is_array( $month_stats ) ) {
				continue;
			}

			foreach ( $month_stats as $episode_data ) {
				$last_months_stats[ $month_name ][ $episode_data->post_id ] = intval( $episode_data->listens );
			}
		}

		foreach ( $total_stats as $episode_data ) {
			$post = get_post( intval( $episode_data->post_id ) );
			if ( ! $post ) {
				continue;
			}
			$episode_stats = array(
				'id'             => $post->ID,
				'episode_name'   => $post->post_title,
				'date'           => date( 'Y-m-d', strtotime( $post->post_date ) ),
				'slug'           => admin_url( 'post.php?post=' . $post->ID . '&action=edit' ),
				'listens'        => $all_episodes_lifetime_stats[ $post->ID ],
				'formatted_date' => date_i18n( get_option( 'date_format' ), strtotime( $post->post_date ) ),
			);

			foreach ( $this->dates as $month_name ) {
				$episode_stats[ $month_name ] = isset( $last_months_stats[ $month_name ][ $post->ID ] ) ? $last_months_stats[ $month_name ][ $post->ID ] : 0;
			}

			$all_episodes_stats[] = $episode_stats;
		}

		return apply_filters( 'ssp_stats_three_months_all_episodes', $all_episodes_stats );
	}

	/**
	 * Checks if stats sorting has been passed, otherwise sets up defaults
	 *
	 * @return array
	 */
	private function get_all_episodes_sort_order() {

		$order_by = isset( $_GET['orderby'] ) ? esc_attr( $_GET['orderby'] ) : 'episode_name';
		$order    = $this->get_requested_order() ?: 'desc';

		$publish_order_url  = admin_url( 'edit.php?post_type=' . SSP_CPT_PODCAST . '&page=podcast_stats&orderby=date&order=desc#last-three-months-container' );
		$name_order_url     = admin_url( 'edit.php?post_type=' . SSP_CPT_PODCAST . '&page=podcast_stats&orderby=episode_name&order=desc#last-three-months-container' );
		$lifetime_order_url = admin_url( 'edit.php?post_type=' . SSP_CPT_PODCAST . '&page=podcast_stats&orderby=listens&order=desc#last-three-months-container' );

		$sorting = array(
			'date'         => array( 'sortable desc', $publish_order_url ),
			'episode_name' => array( 'sortable desc', $name_order_url ),
			'listens'      => array( 'sortable desc', $lifetime_order_url ),
		);
		foreach ( $this->dates as $date ) {
			$date_order_url   = admin_url( 'edit.php?post_type=' . SSP_CPT_PODCAST . '&page=podcast_stats&orderby=' . $date . '&order=desc#last-three-months-container' );
			$sorting[ $date ] = array( 'sortable desc', $date_order_url );
		}

		$sort_order = array();
		foreach ( $sorting as $key => $sorting_item ) {
			if ( $key === $order_by ) {
				$sorting_item[0] = 'sorted ' . $order;
				if ( 'desc' === $order ) {
					$sorting_item[1] = str_replace( 'desc', 'asc', $sorting_item[1] );
				}
			}
			$sort_order[ $key ] = $sorting_item;
		}

		return $sort_order;
	}

}
