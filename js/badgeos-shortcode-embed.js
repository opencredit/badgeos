(function($) {
	/*
	Construct all of our possible attributes.
	 */
	function badgeos_get_attributes_object() {
		attributes = {};
        attributes.badgeos_achievements_list    = [ 'type', 'limit', 'show_filter', 'show_search', 'wpms', 'orderby', 'order', 'include', 'exclude', 'meta_key', 'meta_value' ];
        attributes.badgeos_user_achievements    = [ 'user', 'type', 'limit' ];
        attributes.badgeos_achievement          = [ 'id' ];
        attributes.badgeos_nomination           = [ 'achievement_id' ];
        attributes.badgeos_submission           = [ 'achievement_id' ];
        attributes.badgeos_submissions          = [ 'limit', 'status', 'show_filter', 'show_search', 'show_attachments', 'show_comments' ];
        attributes.badgeos_nominations          = [ 'limit', 'status', 'show_filter', 'show_search' ];

		return attributes;
	}

	/*
	Construct and return our attributes list for the provided shortcode.
	 */
	function badgeos_get_attributes( shortcode ) {
		var result = {};
		var defaults = badgeos_get_attributes_object();

		result.shortcode = shortcode;

		switch( shortcode ) {
			case 'badgeos_achievements_list':
				result.params = defaults.badgeos_achievements_list;
				break;
			case 'badgeos_user_achievements':
				result.params = defaults.badgeos_user_achievements;
				break;
			case 'badgeos_achievement':
				result.params = defaults.badgeos_achievement;
				break;
			case 'badgeos_nomination':
				result.params = defaults.badgeos_nomination;
				break;
			case 'badgeos_submission':
				result.params = defaults.badgeos_submission;
				break;
			case 'badgeos_nominations':
				result.params = defaults.badgeos_nominations;
				break;
			case 'badgeos_submissions':
				result.params = defaults.badgeos_submissions;
				break;
			default:
				break;
		}

		return result;
	}

	/*
	Set the values from the inputs for the provided shortcode.
	 */
	function badgeos_set_current_attributes( shortcode ){
		var attributes = badgeos_get_attributes( shortcode ), result = {}, params = [], input = '', value = '';

		result.shortcode = attributes.shortcode;

		if ( attributes.params ) {
			for( i = 0; i < attributes.params.length; i++ ) {
				input = $('#badgeos_'+attributes.params[i] ).val();
				value = ( input.length > 0 ) ? input : null;

				if ( value ) {
					params.push( attributes.params[i]+'="'+value+'"' );
				}
			}
			result.params = params;
		}
		return result;
	}

	/*
	Construct our final shortcode string and return it to the WP Editor.
	 */
	function badgeos_insert_shortcode(){
		var shortcode, attributes;

		shortcode = $( '#select_shortcode option:selected' ).val();
		requested = badgeos_set_current_attributes( shortcode );

		window.send_to_editor( badgeos_construct_shortcode( requested ) );
	}

	/*
	Concatenate all of our attributes into one string.
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
	Construct the html inputs and append to our output
	 */
	function badgeos_construct_input( shortcode ) {
		inputs = '';
		requested = badgeos_get_attributes( shortcode );
		for( i = 0; i < requested.params.length; i++ ) {
			inputs += '<p><label for="badgeos_'+requested.params[i]+'">'+requested.params[i]+'</label><br/>';
			inputs += '<input id="badgeos_'+requested.params[i]+'" name="badgeos_'+requested.params[i]+'" type="text" /></p>';
		}

		return inputs;
	}

	//Handle changing the html used for the selected shortcode.
	$( '#select_shortcode' ).on( 'change', function(){
		var selected = $( '#select_shortcode option:selected' ).val(), html = '';

		if ( 'credly_assertion_page' == selected || 'unselected' === selected ) {
			$( '#shortcode_options' ).html('');
			return;
		}

		inputs = badgeos_construct_input( selected );
		$( '#shortcode_options' ).html( inputs );
	});

	//Reset some values upon clicking.
	$('#insert_badgeos_shortcodes').on( 'click', function(){
		$( '#select_shortcode' ).val( 'unselected' );
		$( '#shortcode_options' ).html('');
	});
	//Handle click events in our modal popup.
	$( '#badgeos_insert' ).on( 'click', function(){
		badgeos_insert_shortcode();
	});
	$( '#badgeos_cancel' ).on( 'click', function(e){
		e.preventDefault();
		tb_remove();
	});

})(jQuery);
