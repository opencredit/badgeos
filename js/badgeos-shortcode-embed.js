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
				if($(this).hasClass('select2-hidden-accessible')){
					var selections = ($(el).select2('data'));
					var terms=[];
					$.each(selections,function(id,text){
						terms.push(text.id);
					});
					attrs.push( el.name + '="' + terms+'"' );
					return attrs;
				}else{
					attrs.push( el.name + '="' + el.value + '"' );
					return attrs;
				}

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
			$( el ).val('');
		});
	});

	var select2_post_defaults = {
		language :{
			noResults: function(){
			return "No Results";
				},
			errorLoading:function(){ return "Searching..."
				}
			},
		escapeMarkup: function (markup) {
			return markup;
		},
		placeholder: badgeos_shortcode_embed_messages.id_placeholder,
		allowClear: true
	};
	var select2_post_multiples = $.extend( true, {}, select2_post_defaults, { } );
	$( '#badgeos_achievement_id, #badgeos_nomination_achievement_id, #badgeos_submission_achievement_id' ).select2( select2_post_defaults );
	$( '#badgeos_achievements_list_include, #badgeos_achievements_list_exclude' ).attr('multiple', true).select2( select2_post_multiples );
	$( '#badgeos_achievements_list_post_types' ).attr('multiple', true).select2({
		language :{
			noResults: function(){ return "No Results"; },
			errorLoading:function(){ return "Searching...";}
		},
		escapeMarkup: function (markup) { return markup; },
		placeholder: badgeos_shortcode_embed_messages.post_type_placeholder,
		allowClear: true
	});

	$( '#badgeos_achievements_list_user_id' ).select2({
		language :{
			noResults: function(){
				return "No Results";
			},
			errorLoading:function(){ return "Searching..."
			}
		},
		escapeMarkup: function (markup) {
			return markup;
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

			$('.badgeos-shortcode-thickbox #TB_ajaxContent').width( ( width - 30 ) ).height( ( containerheight - 50 ) );
			$('.badgeos-shortcode-thickbox .wrap').width( ( width - 50 ) ) .height( ( containerheight - 50 ) );


		}, 0 );
	}

}(jQuery));
