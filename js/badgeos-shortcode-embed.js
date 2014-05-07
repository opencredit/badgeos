(function ($) {

	function badgeos_insert_shortcode() {

		var shortcode = badgeos_get_selected_shortcode();
		var attributes = badgeos_get_attributes( shortcode );
		var constructed = badgeos_construct_shortcode( shortcode, attributes );

		window.send_to_editor( constructed );
	}

	function badgeos_get_selected_shortcode() {
		return $( '#select_shortcode' ).val();
	}

	function badgeos_get_attributes( shortcode ) {
		var attrs = [];
		var inputs = badgeos_get_shortcode_inputs( shortcode );
		$.each( inputs, function( index, el ) {
			if ( '' !== el.value && undefined !== el.value ) {
				attrs.push( el.name + '="' + el.value + '"' );
			}
		});
		return attrs;
	}

	function badgeos_get_shortcode_inputs( shortcode ) {
		return $( '.text, .select', '#' + shortcode + '_wrapper' );
	}

	function badgeos_construct_shortcode( shortcode, attributes ) {
		var output = '[';
		output += shortcode;

		if ( attributes ) {
			for( i = 0; i < attributes.length; i++ ) {
				output += ' ' + attributes[i];
			}

			$.trim( output );
		}
		output += ']';

		return output;
	}

	function badgeos_shortcode_hide_all_sections() {
		$( '.shortcode-section' ).hide();
	}

	function badgeos_shortcode_show_section( section_name ) {
		$( '#' + section_name + '_wrapper' ).show();
	}

	// Listen for changes to the selected shortcode
	$( '#select_shortcode' ).on( 'change', function() {
		badgeos_shortcode_hide_all_sections();
		badgeos_shortcode_show_section( badgeos_get_selected_shortcode() );
	}).change();

	// Listen for clicks on the "insert" button
	$( '#badgeos_insert' ).on( 'click', function( e ) {
		e.preventDefault();
		badgeos_insert_shortcode();
	});

	// Listen for clicks on the "cancel" button
	$( '#badgeos_cancel' ).on( 'click', function( e ) {
		e.preventDefault();
		tb_remove();
	});

	// Listen for clicks to open the shortcode picker
	$( '#insert_badgeos_shortcodes' ).on( 'click', function(e) {
		var inputs = $( '.select2-container' );
		$.each( inputs, function( index, el ){
			$( el ).select2( 'val', '' );
		});
	});

	var select2_post_defaults = {
		ajax: {
			url: ajaxurl,
			type: 'POST',
			data: function( term ) {
				return {
					q: term,
					action: 'get-achievements-select2',
				};
			},
			results: function( results, page ) {
				console.log(results);
				return {
					results: results.data
				};
			}
		},
		id: function( item ) {
			return item.ID;
		},
		formatResult: function ( item ) {
			return item.post_title;
		},
		formatSelection: function ( item ) {
			return item.post_title;
		},
		placeholder: badgeos_shortcode_embed_messages.id_placeholder,
		allowClear: true,
		multiple: false
	};
	var select2_post_multiples = $.extend( true, {}, select2_post_defaults, { multiple: true } );

	$( '#badgeos_achievement_id, #badgeos_nomination_achievement_id, #badgeos_submission_achievement_id' ).select2( select2_post_defaults );
	$( '#badgeos_achievements_list_include, #badgeos_achievements_list_exclude' ).select2( select2_post_multiples );

	$( '#badgeos_achievements_list_type' ).select2({
		ajax: {
			url: ajaxurl,
			type: 'POST',
			data: function ( term ) {
				return {
					q: term,
					action: 'get-achievement-types'
				};
			},
			results: function ( results, page ) {
				return {
					results: results.data
				};
			}
		},
		id: function ( item ) {
			return item.name;
		},
		formatResult: function ( item ) {
			return item.label;
		},
		formatSelection: function ( item ) {
			return item.label;
		},
		placeholder: badgeos_shortcode_embed_messages.post_type_placeholder,
		allowClear: true,
		multiple: true
	});

	$( '#badgeos_achievements_list_user_id' ).select2({
		ajax: {
			url: ajaxurl,
			type: 'POST',
			data: function( term ) {
				return {
					q: term,
					action: 'get-users'
				};
			},
			results: function( results, page ) {
				return {
					results: results.data
				};
			}
		},
		id: function( item ) {
			return item.ID;
		},
		formatResult: function ( item ) {
			return item.user_login;
		},
		formatSelection: function ( item ) {
			return item.user_login;
		},
		placeholder: badgeos_shortcode_embed_messages.user_placeholder,
		allowClear: true
	});

	// Resize ThickBox when "Add BadgeOS Shortcode" link is clicked
	$('body').on( 'click', '#insert_badgeos_shortcodes', function(e) {
		e.preventDefault();
		badgeos_shortcode_setup_thickbox( $(this) );
	});

	// Resize shortcode thickbox on window resize
	$(window).resize(function() {
		badgeos_shortcode_resize_tb( $('#insert_badgeos_shortcodes') );
	});

	// Add a custom class to our shortcode thickbox, then resize
	function badgeos_shortcode_setup_thickbox( link ) {
		setTimeout( function() {
		$('#TB_window').addClass('badgeos-shortcode-thickbox');
			badgeos_shortcode_resize_tb( link );
		}, 0 );
	}

	// Force shortcode thickboxes to our specified width/height
	function badgeos_shortcode_resize_tb( link ) {
		setTimeout( function() {

		var width = link.attr('data-width');

		$('.badgeos-shortcode-thickbox').width( width );

		var containerheight = $('.badgeos-shortcode-thickbox').height();

		$(
			'.badgeos-shortcode-thickbox #TB_ajaxContent,' +
			'.badgeos-shortcode-thickbox .wrap'
		).width( ( width - 50 ) )
		 .height( ( containerheight - 50 ) );

		}, 0 );
	}

}(jQuery));
