(function($) {

	/*
	Set the values from the inputs for the provided shortcode. This constructs all of the attributes that have user-set values.
	 */
	function badgeos_set_current_attributes( shortcode ){
		//Keep that global namespace clean.
		var result = {}, attrs = [], params = [], input = '', value = '';

		var inputs = $('#shortcode_options input[type="text"]');
		$.each( inputs, function(index, el){
			if ( el.className === shortcode && el.value !== '' ) {
				attrs.push( el.id+'="'+el.value+'"');
			}
		});
		var selects = $('#shortcode_options select option:selected');
		$.each( selects, function(index, el){
			$parent = $(this).parent();
			if ( $parent.attr('class') === shortcode ) {
				attrs.push( $parent.attr('id')+'="'+el.text+'"');
			}
		});

		result.shortcode = shortcode;
		result.params = attrs;

		return result;
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

	//Handle changing the html used for the selected shortcode.
	$( '#select_shortcode' ).on( 'change', function(){
		var selected = $( '#select_shortcode option:selected' ).val();

		if ( 'unselected' === selected ) {
			//If we reselect the default, disable.
			$( '#badgeos_insert' ).attr( 'disabled', 'disabled' );
			$( '#shortcode_options' ).html( '<p>'+badgeos_shortcode_messages.starting+'</p>' );
			return;
		}
		//If we're at this point, we have selected an available shortcode.
		$( '#badgeos_insert' ).removeAttr( 'disabled', 'disabled' );

		inputs = badgeos_construct_inputs( selected );

		$( '#shortcode_options' ).html( inputs );
	});

	//Set/reset some values upon clicking.
	$('#insert_badgeos_shortcodes').on( 'click', function(){
		//Reset to default when re-opening
		$( '#select_shortcode' ).val( 'unselected' );
		//Disable at the start.
		$( '#badgeos_insert' ).attr( 'disabled', 'disabled' );
		//Initial message.
		$( '#shortcode_options' ).html( '<p>'+badgeos_shortcode_messages.starting+'</p>' );
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

})(jQuery);
