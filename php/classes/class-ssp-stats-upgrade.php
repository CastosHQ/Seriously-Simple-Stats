<?php

class Stats_Upgrade {
	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 */
	public function __construct() {
		$this->bootstrap();
	} // End __construct ()

	public function bootstrap(){
		// Run any updates
		add_action( 'init', array( $this, 'update' ), 11 );
	}

	public function update(){
		$previous_version = get_option( 'ssp_version', '1.0' );

		if ( version_compare( $previous_version, '1.20.3', '<=' ) ) {
			$this->upgrade_handler->upgrade_stitcher_subscribe_link_option();
		}
	}
}
