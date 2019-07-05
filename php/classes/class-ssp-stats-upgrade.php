<?php

namespace SeriouslySimpleStats\Classes;

class Stats_Upgrade {

	public $version = '1.0.0';

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 */
	public function __construct( $version ) {
		$this->version = $version;
		$this->bootstrap();
	} // End __construct ()

	public function bootstrap(){
		// Run any updates
		add_action( 'init', array( $this, 'update' ), 11 );

		// Show the Crawler detect upgrade notice
		add_action( 'admin_notices', array( $this, 'show_crawler_update_notice' ) );
	}

	/**
	 * Checks the current stats version and runs any upgrades as required
	 */
	public function update() {
		$previous_version = get_option( 'ssp_stats_version', '1.0.0' );

		/**
		 * Trigger the stats Crawler Detect update for 1.2.3
		 */
		if ( version_compare( $previous_version, '1.2.3', '<=' ) ) {
			$show_crawler_update_notice = get_option( 'ssp_stats_show_crawler_update_notice', '' );
			if ( empty( $show_crawler_update_notice ) ) {
				update_option( 'ssp_stats_show_crawler_update_notice', 'true' );
			}
		}

		update_option( 'ssp_stats_version', $this->version );

	}

	/**
	 * Shows a notice to the user that the stats plugin has been updated.
	 * Only show it once on the stats screen
	 */
	public function show_crawler_update_notice() {
		$screen = get_current_screen();
		if ($screen->base !== 'podcast_page_podcast_stats'){
			return;
		}
		$show_crawler_update_notice = get_option( 'ssp_stats_show_crawler_update_notice', 'false' );
		if ( 'false' === $show_crawler_update_notice ) {
			return;
		}
		?>
		<div class="notice is-dismissible">
			<p><?php _e( 'The Seriously Simple Stats plugin has been upgraded with the latest crawler detection algorithms, for improved bot detection and exceptions.', 'seriously-simple-stats' ); ?></p>
		</div>
		<?php
		update_option( 'ssp_stats_show_crawler_update_notice', 'false' );
	}
}
