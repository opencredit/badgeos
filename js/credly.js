jQuery(document).ready(function($) {
	$('#credly_category_search_submit').click( function( event ) {

		// Stop the default submission from happening
		event.preventDefault();

		// Grab our form values
		var search_terms = $('#credly_category_search').val();

		$.ajax({
			type : "post",
			dataType : "json",
			url : ajaxurl,
			data : { "action": "search_credly_categories", "search_terms": search_terms },
			success : function(response) {
				 $('#credly-badge-settings fieldset').append(response);
				 $('#credly_search_results').show();
			}
		});

	});

});