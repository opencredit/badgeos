( function( $ ) {

	/*
	Set the values from the inputs for the provided shortcode.
	 */
	function badgeos_set_current_attributes( shortcode ) {

		//Keep that global namespace clean.
		var result = {}, attrs = [];

		attrs = badgeos_get_text_inputs( attrs, shortcode );

		attrs = badgeos_get_select_inputs( attrs, shortcode );

		attrs = badgeos_get_select2_inputs( attrs, shortcode );

		result.shortcode = shortcode;
		result.params = attrs;

		return result;
	}

	function badgeos_get_text_inputs( attrs, shortcode ){
		var inputs = $('#shortcode_options input[type="text"]');
		$.each( inputs, function(index, el){
			if ( el.className === shortcode && el.value !== '' ) {
				attrs.push( el.name+'="'+el.value+'"');
			}
		});
		return attrs;
	}

	function badgeos_get_select_inputs( attrs, shortcode ){
		var selects = $('#shortcode_options select option:selected');
		$.each( selects, function(index, el){
			var $parent = $(this).parent();
			if ( $parent.attr('class') === shortcode ) {
				attrs.push( $parent.attr('name')+'="'+el.text+'"');
			}
		});
		return attrs;
	}

	function badgeos_get_select2_inputs( attrs, shortcode ){
		var inputs = $('.select2-input');
		console.log($('.select2-chosen').html());
		/*$.each( inputs, function(index, el){
			console.log($(el).select2("val"));
		});*/
		return attrs;
	}

	/*
	Construct our final shortcode string and return it to the WP Editor. This gets called when the user clicks "Insert Shortcode".
	 */
	function badgeos_insert_shortcode(){
		var shortcode, requested;

		//Grab our requested shortcode from the dropdown, set the attributes for the chosen.
		shortcode = $( '#select_shortcode option:selected' ).val();
		requested = badgeos_set_current_attributes( shortcode );

		//Insert our final result into the post editor, after construction.
		window.send_to_editor( badgeos_construct_shortcode( requested ) );
	}

	/*
	Concatenate all of our attributes into one string. This will set only user-set attributes
	 */
	function badgeos_construct_shortcode( requested ) {
		var shortcode = '[';
		shortcode += requested.shortcode;

		if ( requested.params ) {
			for( i = 0; i < requested.params.length; i++ ) {
				shortcode += ' ' + requested.params[i];
			}

			$.trim(shortcode);
		}
		shortcode += ']';

		return shortcode;
	}

	/*
	Used with our Select2 implementation for User IDs
	 */
	function s2formatResult_users(item) {
		return item.user_login;
	}
	/*
	Used with our Select2 implementation for Post IDs
	 */
	function s2formatResult_posts(item) {
		return item.post_title;
	}

	/*
	Used to set the ID of the user or post
	 */
	function s2formatSelection(item) {
		return item.ID;
	}

	//Handle changing the html used for the selected shortcode.
	$( '#select_shortcode' ).on( 'change', function(){
		var selected = $( '#select_shortcode option:selected' ).val();

		$('.badgeos_input').parent().hide();
		$('.'+selected).parent().parent().show();

	});

	//Insert constructed shortcode and close popup
	$( '#badgeos_insert' ).on( 'click', function(e){
		badgeos_insert_shortcode();
	});
	//Close the modal popup without doing anything.
	$( '#badgeos_cancel' ).on( 'click', function(e){
		e.preventDefault();
		tb_remove();
	});

	/*
	Add Select2 to our user ID input for the badgeos_achievements_list shortcode
	 */
	$("#badgeos_achievements_list_user_id").select2({
		ajax: {
			url: ajaxurl,
			type: 'POST',
			data: function (term) {
				return {
					q: term, // search term
					action: 'get-users'
				};
			},
			results: function (data, page) {
				return {
					results: data.data
				};
			}
		},
		id: function (object) {
			return object.user_login;
		},
		formatResult: s2formatResult_users,
		formatSelection: s2formatSelection
	});

	/*
	Add Select2 to our three shortcodes that only need a single ID.
	 */
	$('#badgeos_achievement_id,#badgeos_nomination_achievement_id,#badgeos_submission_achievement_id').select2({
		ajax: {
			url: ajaxurl,
			type: 'POST',
			data: function (term) {
				return {
					q: term, // search term
					action: 'get-posts'
				};
			},
			results: function (data, page) {
				return {
					results: data.data
				};
			}
		},
		id: function (object) {
			return object.post_title;
		},
		formatResult: s2formatResult_posts,
		formatSelection: s2formatSelection
	});

	//TODO: figure out how to get multiples int one input.
	$('#badgeos_achievements_list_include,#badgeos_achievements_list_exclude').select2({
		ajax: {
			url: ajaxurl,
			type: 'POST',
			data: function (term) {
				return {
					q: term, // search term
					action: 'get-posts'
				};
			},
			results: function (data, page) {
				return {
					results: data.data
				};
			}
		},
		id: function (object) {
			return object.post_title;
		},
		formatResult: s2formatResult_posts,
		formatSelection: s2formatSelection
	});

})(jQuery);
