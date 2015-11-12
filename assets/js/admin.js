jQuery( document ).ready( function ( e ) {

	jQuery( '#podcast_settings #content-filter-select').change( function() {
		var value = jQuery( this ).val();

		if( 'series' == value ) {
			jQuery( '#by-series-selection' ).removeClass( 'hidden' );
			jQuery( '#by-episode-selection' ).addClass( 'hidden' );
		} else if( 'episode' == value ) {
			jQuery( '#by-episode-selection' ).removeClass( 'hidden' );
			jQuery( '#by-series-selection' ).addClass( 'hidden' );
		} else {
			jQuery( '#by-series-selection' ).addClass( 'hidden' );
			jQuery( '#by-episode-selection' ).addClass( 'hidden' );
		}
	});

	jQuery( '#podcast_settings #content-filter-container select').change( function() {
		jQuery( '#content-filter-button' ).removeClass( 'hidden' );
	});

	jQuery( '#podcast_settings .ssp-datepicker').change( function() {
		jQuery( '#date_select_submit' ).removeClass( 'hidden' );
	});

});