(function($) {

	/*
	Construct and return our attributes list for the provided shortcode. Grabs all results from a localized variable.
	 */
	function badgeos_get_attributes( shortcode ) {
		var result = {};

		result.shortcode = shortcode;

		//If the localized variable has the requested property aka requested shortcode, add those to our params property.
		if ( badgeos_shortcodes.hasOwnProperty(shortcode) ) {
			result.params = badgeos_shortcodes[shortcode];
		}
		return result;
	}

	/*
	Set the values from the inputs for the provided shortcode. This constructs all of the attributes that have user-set values.
	 */
	function badgeos_set_current_attributes( shortcode ){
		//Keep that global namespace clean.
		var attributes = badgeos_get_attributes( shortcode ), result = {}, params = [], input = '', value = '';

		result.shortcode = attributes.shortcode;

		//If we have any available parameters
		if ( attributes.params ) {
			for( i = 0; i < attributes.params.length; i++ ) {
				//Had to grab the values out of the select input differently.
				if ( 'bool' === attributes.params[i].type ) {
					input = $('#badgeos_'+attributes.params[i].param+' option:selected' ).val();
				} else {
					input = $('#badgeos_'+attributes.params[i].param ).val();
				}
				value = ( input.length > 0 ) ? input : null;

				//If we had a value at all, push it intou our params array.
				if ( value ) {
					params.push( attributes.params[i].param+'="'+value+'"' );
				}
			}
			//assign the params property with our array of found parameters.
			result.params = params;
		}
		return result;
	}

	/*
	Construct our final shortcode string and return it to the WP Editor. This gets called when the user clicks "Insert Shortcode".
	 */
	function badgeos_insert_shortcode(){
		var shortcode, attributes;

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
	Create all of the inputs for the requested shortcode. Inputs will provide no default values.
	 */
	function badgeos_construct_inputs( shortcode ) {
		var inputs = '', pingpong = '', requested;
		requested = badgeos_get_attributes( shortcode );
		pingpong = 'odd';

		for( i = 0; i < requested.params.length; i++ ) {

			inputs+= '<div class="badgeos_input alignleft '+pingpong+'">';
			if ( 'bool' === requested.params[i].type ) {

				inputs += badgeos_construct_bool_input( requested );

			} else {

				inputs += badgeos_construct_text_input( requested );

			}
			inputs += '</div>';
			pingpong = ( 'odd' == pingpong ) ? 'even' : 'odd';
		}

		return inputs;
	}

	/*
	Put together a single true/false select input
	 */
	function badgeos_construct_bool_input( requested ) {
		var input = '';

		input += '<div><label for="badgeos_'+requested.params[i].param+'">'+requested.params[i].param+'</label><br/>';
		input += '<select id="badgeos_'+requested.params[i].param+'" name="badgeos_'+requested.params[i].param+'">';
		if ( requested.params[i].default == badgeos_shortcode_bool[0].toLowerCase() ) {
			input += '<option selected="selected" value="1">'+badgeos_shortcode_bool[0]+'</option>';
		} else {
			input += '<option value="1">'+badgeos_shortcode_bool[0]+'</option>';
		}
		if ( requested.params[i].default == badgeos_shortcode_bool[1].toLowerCase() ) {
			input += '<option selected="selected" value="0">'+badgeos_shortcode_bool[1]+'</option>';
		} else {
			input += '<option value="0">'+badgeos_shortcode_bool[1]+'</option>';
		}

		input += '</select>';
		if ( requested.params[i].default_text ) {
			input += '<br/>' + requested.params[i].default_text;
		}
		input += '</div>';

		return input;
	}

	/*
	Put together a single text input
	 */
	function badgeos_construct_text_input( requested ) {
		var input = '';

		input += '<div><label for="badgeos_'+requested.params[i].param+'">'+requested.params[i].param+'</label><br/>';
		input += '<input id="badgeos_'+requested.params[i].param+'" name="badgeos_'+requested.params[i].param+'" type="text" />';
		if ( requested.params[i].default_text ) {
			input += '<br/>' + requested.params[i].default_text;
		}
		input += '</div>';

		return input;
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

	//Set/reset some values upon clicking.
	$('#insert_badgeos_shortcodes').on( 'click', function(){
		$( '#select_shortcode' ).val( 'unselected' );
		$( '#shortcode_options' ).html('');
	});
	//Insert constructed shortcode and close popup
	$( '#badgeos_insert' ).on( 'click', function(){
		badgeos_insert_shortcode();
	});
	//Close the modal popup without doing anything.
	$( '#badgeos_cancel' ).on( 'click', function(e){
		e.preventDefault();
		tb_remove();
	});

})(jQuery);
