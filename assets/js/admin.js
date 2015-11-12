jQuery( document ).ready( function ( e ) {
	jQuery( '#podcast_settings .filter-option').change( function() {
		var id = jQuery( this ).attr( 'id' );

		if( 'by-series' == id ) {
			jQuery( '#by-series-selection' ).removeClass( 'hidden' );
			jQuery( '#by-episode-selection' ).addClass( 'hidden' );
		} else {
			jQuery( '#by-episode-selection' ).removeClass( 'hidden' );
			jQuery( '#by-series-selection' ).addClass( 'hidden' );
		}
	});
	jQuery( '#podcast_settings .ssp-datepicker').change( function() {
		jQuery( '#date_select_submit' ).removeClass( 'hidden' );
	});
});