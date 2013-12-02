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

		var old_api_key = $api_key.val(),
			$credly_user = $( '#credly_user' ),
			$credly_password = $( '#credly_password' ),
			$submit = $( '#submit' );

		function credly_user_check( $this, credly_action, is_username ) {

			var $credly_user_response = $( '#' + $this.attr( 'id' ) + '_response' ),
				$credly_user_response_message = $credly_user_response.find( 'strong' ),
				credly_user = $credly_user.val(),
				credly_password = $credly_password.val();

			$credly_user_response.removeClass( 'valid' ).removeClass( 'invalid' );

			if ( 'api_key' == $this.attr( 'id' ) || ( '' != credly_user && ( is_username || '' != credly_password ) ) ) {
				$credly_user_response.show();
				$credly_user_response_message.text( $credly_user_response.data( 'msg-validating' ) );
				$submit.prop( 'disabled', false );

				$.ajax( ajaxurl, {
					type: 'POST',
					dataType: 'json',
					data: {
						action: credly_action,
						api_key: $api_key.val(),
						credly_is_username: is_username,
						credly_user: credly_user,
						credly_password: credly_password
					},
					success: function( response ) {
						if ( response.success ) {
							$credly_user_response.addClass( 'valid' );
							$credly_user_response_message.text( $credly_user_response.data( 'msg-valid' ) );

							if ( 'api_key' == $this.attr( 'id' ) && 0 < response.message.length ) {
								if ( confirm( response.message ) ) {
									// OK
								}
								else {
									$credly_user.val( old_api_key );
									$credly_user.trigger( 'change' );

									return true;
								}
							}
							else {
								// OK
							}
						}
						else {
							$credly_user_response.addClass( 'invalid' );
							$credly_user_response_message.text( response.message );

							$submit.prop( 'disabled', true );
						}

						return true;
					}
				} );
			}
			else {
				$credly_user_response.hide();

				$submit.removeClass( 'button-disabled' );
			}

		}

		$api_key.on( 'change', function() {

			// Clear user / pass
			$credly_user.val( '' );
			$credly_password.val( '' );

			// Clear message
			$credly_user.trigger( 'change' );

			credly_user_check( $( this ), 'credly_check_api_key', false );

		} );

		$credly_user.on( 'change', function() {

			// Clear password
			$credly_password.val( '' );

			credly_user_check( $( this ), 'credly_check_credly_user', true );

		} );

		// @todo Check password via AJAX in a way that doesn't generate two tokens (AJAX and saving of form)
		/*$credly_password.on( 'change', function() {

			credly_user_check( $( this ), 'credly_check_credly_user', false );

		} );*/
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
