( function( $ ) {

	/*
	Set the values from the inputs for the provided shortcode.
	 */
	function badgeos_set_current_attributes( shortcode ) {

		//Keep that global namespace clean.
		var result = {}, attrs = [];

		/*
		Pass the same variable into all three functions so that they are concatenated together.
		 */
		attrs = badgeos_get_text_inputs( attrs, shortcode );

		attrs = badgeos_get_select_inputs( attrs, shortcode );

		attrs = badgeos_get_select2_inputs( attrs, shortcode );

		/*
		Construct our final object for the shortcode instance.
		 */
		result.shortcode = shortcode;
		result.params = attrs;

		return result;
	}

	/*
	Get the values for all the plain text inputs
	 */
	function badgeos_get_text_inputs( attrs, shortcode ) {
		var inputs = $( '#shortcode_options input[type="text"]' );
		$.each( inputs, function( index, el ) {
			if ( el.className === shortcode && el.value !== '' ) {
				attrs.push( el.name+'="'+el.value+'"' );
			}
		});
		return attrs;
	}

	/*
	Get the values for all the normal select inputs
	 */
	function badgeos_get_select_inputs( attrs, shortcode ) {
		var selects = $( '#shortcode_options select option:selected' );
		$.each( selects, function( index, el ) {
			var $parent = $( this ).parent();
			if ( $parent.attr( 'class' ) === shortcode ) {
				attrs.push( $parent.attr( 'name' )+'="'+el.text+'"' );
			}
		});
		return attrs;
	}

	/*
	Get all the values for the select2 inputs
	 */
	function badgeos_get_select2_inputs( attrs, shortcode ) {
		var inputs = $( '#'+shortcode+'_wrapper .select2-container' );
		$.each( inputs, function( index, el ) {
			var val = $( el ).select2( 'val' );

			var theattr = $( el ).prop( 'id' );
			var trimmed = badgeos_select2_trim_id( theattr, shortcode );

			if ( val.length > 0 && val !== '' ) {
				attrs.push( trimmed+'="'+val+'"' );
			}
		});
		return attrs;
	}

	function badgeos_select2_trim_id( input, shortcode ) {
		if ( shortcode === 'badgeos_achievements_list' ) {
			return input.replace( 's2id_badgeos_achievements_list_', '' );
		} else if ( shortcode === 'badgeos_achievement' ) {
			return input.replace( 's2id_badgeos_achievement_', '' );
		} else if ( shortcode === 'badgeos_nomination' ) {
			return input.replace( 's2id_badgeos_nomination_', '' );
		} else if ( shortcode === 'badgeos_submission' ) {
			return input.replace( 's2id_badgeos_submission_', '' );
		} else if ( shortcode === 'badgeos_submissions' ) {
			return input.replace( 's2id_badgeos_submissions_achievement_', '' );
		} else if ( shortcode === 'badgeos_nominations' ) {
			return input.replace( 's2id_badgeos_nominations_achievement_', '' );
		}
	}

	/*
	Construct our final shortcode string and return it to the WP Editor. This gets called when the user clicks "Insert Shortcode".
	 */
	function badgeos_insert_shortcode() {
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

			$.trim( shortcode );
		}
		shortcode += ']';

		return shortcode;
	}

	/*
	Used with our Select2 implementation for User IDs
	 */
	function s2formatResult_users( item ) {
		return item.user_login;
	}
	/*
	Used to set the ID of the user or post
	 */
	function s2formatSelection_users( item ) {
		return item.user_login;
	}

	/*
	Used with our Select2 implementation for Post IDs
	 */
	function s2formatResult_posts( item ) {
		return item.post_title;
	}

	/*
	Used to set the ID of the user or post
	 */
	function s2formatSelection_posts( item ) {
		return item.post_title;
	}

	/*
	Used with our Select2 implementation for Post IDs
	 */
	function s2formatResult_posttypes( item ) {
		return item.post_type;
	}

	/*
	Used to set the ID of the user or post
	 */
	function s2formatSelection_posttypes( item ) {
		return item.post_type;
	}

	//Handle changing the html used for the selected shortcode.
	$( '#select_shortcode' ).on( 'change', function() {
		var selected = $( '#select_shortcode option:selected' ).val();

		$( '.badgeos_input' ).parent().hide();
		$( '.'+selected ).parent().parent().show();

	});

	//Insert constructed shortcode and close popup
	$( '#badgeos_insert' ).on( 'click', function( e ) {
		badgeos_insert_shortcode();
	});
	//Close the modal popup without doing anything.
	$( '#badgeos_cancel' ).on( 'click', function( e ) {
		e.preventDefault();
		tb_remove();
	});
	$( '#insert_badgeos_shortcodes' ).on( 'click', function(e) {
		var inputs = $( '.select2-container' );
		$.each( inputs, function( index, el ){
			$( el ).select2( 'val', '' );
		});
	});

	/*
	Add Select2 to our user ID input for the badgeos_achievements_list shortcode
	 */
	$( '#badgeos_achievements_list_user_id' ).select2({
		ajax: {
			url: ajaxurl,
			type: 'POST',
			data: function( term ) {
				return {
					q: term, // search term
					action: 'get-users'
				};
			},
			results: function( data, page ) {
				return {
					results: data.data
				};
			}
		},
		id: function( object ) {
			return object.ID;
		},
		allowClear: true,
		placeholder: badgeos_shortcode_embed_messages.id_placeholder,
		formatResult: s2formatResult_users,
		formatSelection: s2formatSelection_users
	});

	/*
	Add Select2 to our three shortcodes that only need a single ID.
	 */
	$( '#badgeos_achievement_id,#badgeos_nomination_achievement_id,#badgeos_submission_achievement_id' ).select2({
		ajax: {
			url: ajaxurl,
			type: 'POST',
			data: function( term ) {
				return {
					q: term, // search term
					action: 'get-posts'
				};
			},
			results: function( data, page ) {
				return {
					results: data.data
				};
			}
		},
		id: function( object ) {
			return object.ID;
		},
		allowClear: true,
		placeholder: badgeos_shortcode_embed_messages.id_placeholder,
		formatResult: s2formatResult_posts,
		formatSelection: s2formatSelection_posts
	});

	/*
	Add Select2 to our include/exclude inputs. Supports multiple values.
	 */
	$( '#badgeos_achievements_list_include,#badgeos_achievements_list_exclude' ).select2({
		ajax: {
			url: ajaxurl,
			type: 'POST',
			data: function( term ) {
				return {
					q: term, // search term
					action: 'get-posts',
					post_type: $( '#badgeos_achievements_list_type' ).val()
				};
			},
			results: function( data, page ) {
				return {
					results: data.data
				};
			}
		},
		id: function( object ) {
			return object.ID;
		},
		allowClear: true,
		placeholder: badgeos_shortcode_embed_messages.id_placeholder,
		multiple: true,
		formatResult: s2formatResult_posts,
		formatSelection: s2formatSelection_posts
	});

	$( '#badgeos_achievements_list_type,#badgeos_nominations_achievement_type,#badgeos_submissions_achievement_type' ).select2({
		ajax: {
			url: ajaxurl,
			type: 'POST',
			data: function( term ) {
				return {
					q: term,
					action: 'get-post-types'
				};
			},
			results: function( data, page ) {
				return {
					results: data.data
				};
			}
		},
		id: function( object ) {
			return object.post_type
		},
		allowClear: true,
		placeholder: badgeos_shortcode_embed_messages.post_type_placeholder,
		multiple: true,
		formatResult: s2formatResult_posttypes,
		formatSelection: s2formatSelection_posttypes
	});

})( jQuery );
