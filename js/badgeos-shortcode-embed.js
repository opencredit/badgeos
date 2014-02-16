(function($) {

	/*
	Construct and return our attributes list for the provided shortcode.
	 */
	function badgeos_get_attributes( shortcode ) {
		//badgeos_shortcodes will be a localized variable
		var result = {};

		result.shortcode = shortcode;

		if ( badgeos_shortcodes.hasOwnProperty(shortcode) ) {
			result.params = badgeos_shortcodes[shortcode];

			return result;
		}
	}

	/*
	Set the values from the inputs for the provided shortcode.
	 */
	function badgeos_set_current_attributes( shortcode ){
		var attributes = badgeos_get_attributes( shortcode ), result = {}, params = [], input = '', value = '';

		result.shortcode = attributes.shortcode;

		if ( attributes.params ) {
			for( i = 0; i < attributes.params.length; i++ ) {
				if ( 'bool' === attributes.params[i].type ) {
					input = $('#badgeos_'+attributes.params[i].param+' option:selected' ).val();
				} else {
					input = $('#badgeos_'+attributes.params[i].param ).val();
				}
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
			if ( 'bool' === requested.params[i].type ) {
				inputs += '<p><label for="badgeos_'+requested.params[i].param+'">'+requested.params[i].param+'</label><br/>';
				inputs += '<select name="badgeos_'+requested.params[i].param+'"">';
				inputs += '<option value="1">'+badgeos_shortcode_bool[0]+'</option>';
				inputs += '<option value="0">'+badgeos_shortcode_bool[1]+'</option>';
				inputs += '</select></p>';
			} else {
				inputs += '<p><label for="badgeos_'+requested.params[i].param+'">'+requested.params[i].param+'</label><br/>';
				inputs += '<input id="badgeos_'+requested.params[i].param+'" name="badgeos_'+requested.params[i].param+'" type="text" /></p>';
			}

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
