/*
 * Javascript Code to enhance the admin options page
 * Relies on JQuery being available
 */
jQuery(document).ready(function($){

	// update page as necessary on load
	citation_aggregator_show_hide();

	// detect a change in the value of the check boxes
	$('#delicious_enable').click(function(event) {
		if (event.target == this) {
			citation_aggregator_show_hide();
		}
	});
	
	$('#connotea_enable').click(function(event) {
		if (event.target == this) {
			citation_aggregator_show_hide();
		}
	});
	
	$('#google_reader_enable').click(function(event) {
		if (event.target == this) {
			citation_aggregator_show_hide();
		}
	});

	// function to show / hide table rows
	function citation_aggregator_show_hide() {
	
		// delicious options
		if ($('#delicious_enable').is(':checked')) { 	
	  		// show the table rows for the delicious items if the checkbox is ticked
	  		$('.delicious_options').show();
		} else {
			// hide the table rows for the delicious items
	  		$('.delicious_options').hide();
	  	}
	  	
	  	// connotea options		
		if ($('#connotea_enable').is(':checked')) { 	
	  		// show the table rows for the delicious items if the checkbox is ticked
	  		$('.connotea_options').show();
		} else {
			// hide the table rows for the delicious items
	  		$('.connotea_options').hide();
	  	}
	  	
	  	// google reader options
		if ($('#google_reader_enable').is(':checked')) { 	
	  		// show the table rows for the delicious items if the checkbox is ticked
	  		$('.google_reader').show();
		} else {
			// hide the table rows for the delicious items
	  		$('.google_reader').hide();
	  	}
	}

}); // end function to hide the delicious ui components
