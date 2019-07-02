<?php

namespace SeriouslySimpleStats\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class All_Episode_Stats {

	private $table = '';

	private $dates = array();

	public $total_posts = 0;

	public $total_per_page = 0;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'ssp_stats';
		$this->dates = array(
			intval( current_time( 'm' ) )                                             => current_time( 'F' ),
			intval( date( 'm', strtotime( current_time( 'Y-m-d' ) . ' -1 MONTH' ) ) ) => date( 'F', strtotime( current_time( "Y-m-d" ) . '-1 MONTH' ) ),
			intval( date( 'm', strtotime( current_time( 'Y-m-d' ) . ' -2 MONTH' ) ) ) => date( 'F', strtotime( current_time( "Y-m-d" ) . '-2 MONTH' ) ),
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
		if ( $this->total_posts >= $this->total_per_page ) {
			if ( isset( $_GET['pagenum'] ) ) {
				$pagenum = intval( $_GET['pagenum'] );
			} else {
				$pagenum = 1;
			}
			$total_pages   = ceil( $this->total_posts / $this->total_per_page );
			$prev_page     = ( $pagenum <= 1 ) ? 1 : $pagenum - 1;
			$order_by      = isset( $_GET['orderby'] ) ? '&orderby=' . sanitize_text_field( $_GET['orderby'] ) : "";
			$order         = isset( $_GET['order'] ) ? '&order=' . sanitize_text_field( $_GET['order'] ) : "";
			$month_num     = isset( $_GET['month_num'] ) ? '&month_num=' . sanitize_text_field( $_GET['month_num'] ) : "";
			$next_page_url = admin_url( "edit.php?post_type=podcast&page=podcast_stats" . $order_by . $month_num . $order . "&pagenum=" . $prev_page . "#last-three-months-container" );
			$next_page     = $pagenum + 1;
			ob_start();
			require_once SSP_STATS_DIR_PATH . 'partials/stats-all-episodes-pagination.php';
			$html .= ob_get_clean();
		}

		return $html;
	}

	/**
	 * Get all episode stats to be rendered in the stats-all-episode partial
	 *
	 * @return array
	 */
	private function get_all_episode_stats(){
		global $wpdb;
		$date_keys = array_keys( $this->dates );

		$all_episodes_stats = array();
		//$this->start_date = strtotime(current_time('Y-m-d') . ' -2 MONTH');
		$sql         = "SELECT COUNT(id) AS listens, post_id FROM $this->table GROUP BY post_id";
		$results     = $wpdb->get_results( $sql );
		$this->total_posts = count( $results );
		if ( is_array( $results ) ) {
			foreach ( $results as $result ) {
				$total_listens_array = array_combine( $date_keys, array( 0, 0, 0 ) );
				$post                = get_post( intval( $result->post_id ) );
				if ( ! $post ) {
					continue;
				}
				$sql             = "SELECT `date` FROM $this->table WHERE `post_id` = '" . $result->post_id . "'";
				$episode_results = $wpdb->get_results( $sql );
				$lifetime_count  = count( $episode_results );
				foreach ( $episode_results as $ref ) {
					//Increase the count of listens per month
					if ( isset( $total_listens_array[ intval( date( 'm', intval( $ref->date ) ) ) ] ) ) {
						++ $total_listens_array[ intval( date( 'm', intval( $ref->date ) ) ) ];
					}
				}
				$all_episodes_stats[] = apply_filters( 'ssp_stats_three_months_all_episodes', array(
					'episode_name'  => $post->post_title,
					'date'          => date( 'm-d-Y', strtotime( $post->post_date ) ),
					'slug'          => admin_url( 'post.php?post=' . $post->ID . '&action=edit' ),
					'listens'       => $lifetime_count,
					'listens_array' => $total_listens_array,
				) );
			}
		}

		//24 because we're counting an array
		$this->total_per_page = apply_filters( 'ssp_stats_three_months_per_page', 24 );

		if( !empty( $all_episodes_stats ) && is_array( $all_episodes_stats ) ){
			//Sort list by episode name
			foreach( $all_episodes_stats as $listen ){
				$listen_sorting[] = $listen['episode_name'];
			}
			array_multisort( $listen_sorting, SORT_DESC, $all_episodes_stats );
			if( isset( $_GET['pagenum'] ) ){
				$pagenum = intval( $_GET['pagenum'] );
				if( $pagenum <= 1){
					$current_cursor = 0;
				} else {
					$current_cursor = ($pagenum - 1)* $this->total_per_page;
				}
				$all_episodes_stats = array_slice( $all_episodes_stats, $current_cursor, $this->total_per_page , true );
			} else {
				$all_episodes_stats = array_slice( $all_episodes_stats, 0, $this->total_per_page, true );
			}
		}
		return $all_episodes_stats;
	}

	/**
	 * Checks if stats sorting has been passed, otherwise sets up defaults
	 *
	 * @return array
	 */
	private function get_all_episodes_sort_order() {
		$sort_order = array(
			'publish'  => 'sortable desc',
			'name'     => 'sortable desc',
			'lifetime' => 'sortable desc',
		);
		foreach ( $this->dates as $date_key => $date ) {
			$sort_order[ $date ] = 'sortable desc';
		}

		return $sort_order;
	}

}
