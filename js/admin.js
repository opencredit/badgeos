jQuery(document).ready(function($) {

	// Dynamically show/hide achievement meta inputs based on "Award By" selection
	$("#_badgeos_earned_by").change( function() {

		// Define our potentially unnecessary inputs
		var badgeos_sequential = $('#_badgeos_sequential').parent().parent();
		var badgeos_points_required = $('#_badgeos_points_required').parent().parent();

		// // Hide our potentially unnecessary inputs
		badgeos_sequential.hide();
		badgeos_points_required.hide();

		// Determine which inputs we should show
		if ( 'triggers' == $(this).val() )
			badgeos_sequential.show();
		else if ( 'points' == $(this).val() )
			badgeos_points_required.show();

	}).change();

	// Credly Settings page
	var $show_api_key = $( '[href="#show-api-key"]' ),
		$api_key = $( '#api_key' );

	if ( $show_api_key[ 0 ] || $api_key[ 0 ] ) {
		$show_api_key.on( 'click', function( e ) {
			e.preventDefault();

			$( '#credly-settings tr, #credly-settings .toggle' ).toggle();
		} );

		var old_api_key = $api_key.val();

		$api_key.on( 'change', function() {
			var $api_key_response = $( '#api_key_response' ),
				$api_key_response_message = $api_key_response.find( 'strong' );

			$api_key_response.removeClass( 'valid' ).removeClass( 'invalid' );

			if ( '' != $api_key.val() ) {
				$api_key_response.show();
				$api_key_response_message.text( $api_key_response.data( 'msg-validating' ) );

				$.ajax( ajaxurl, {
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'credly_check_api_key',
						api_key: $api_key.val()
					},
					success: function( response ) {
						if ( response.success ) {
							$api_key_response.addClass( 'valid' );
							$api_key_response_message.text( $api_key_response.data( 'msg-valid' ) );

							if ( 0 < response.message.length ) {
								if ( confirm( response.message ) ) {
									// OK
								}
								else {
									$api_key.val( old_api_key );
									console.log( old_api_key );
									console.log( typeof old_api_key );
									$api_key.trigger( 'change' );

									return true;
								}
							}
							else {
								// OK
							}
						}
						else {
							$api_key_response.addClass( 'invalid' );
							$api_key_response_message.text( response.message );
						}

						return true;
					}
				} );
			}
			else {
				$api_key_response.hide();
			}


		} );
	}

	// Throw a warning on Achievement Type editor if title is > 20 characters
	$('#titlewrap').on( 'keyup', 'input[name=post_title]', function() {

		// Make sure we're editing an achievement type
		if ( 'achievement-type' == $('#post_type').val() ) {
			// Cache the title input selector
			var $title = $(this);
			if ( $title.val().length > 20 ) {
				// Set input to look like danger
				$title.css({'background':'#faa', 'color':'#a00', 'border-color':'#a55' });

				// Output a custom warning (and delete any existing version of that warning)
				$('#title-warning').remove();
				$title.parent().append('<p id="title-warning">Achievement Type supports a maximum of 20 characters. Please choose a shorter title.</p>');
			} else {
				// Set the input to standard style, hide our custom warning
				$title.css({'background':'#fff', 'color':'#333', 'border-color':'#DFDFDF'});
				$('#title-warning').remove();
			}
		}
	} );

	// Show credly notification settings if notifications are enabled
	$('#credly_badge_sendemail').change( function() {
		if ( 'true' == $(this).val() )
			$('.credly-notifications-enable-message').show();
		else
			$('.credly-notifications-enable-message').hide();

		$('#credly_badge_sendemail_add_message').change();
	}).change();

	// Show notification custom message input if setting is enabled
	$('#credly_badge_sendemail_add_message').change( function() {
		if ( 'true' == $(this).val() && 'true' == $('#credly_badge_sendemail').val() )
			$('.credly-notifications-message').show();
		else
			$('.credly-notifications-message').hide();

	}).change();

});
