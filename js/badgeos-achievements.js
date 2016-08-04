jQuery( function( $ ) {

	var $body = $( 'body' );

	// Retrieve feedback posts when an approriate action is taken
	$body.on( 'change', '.badgeos-feedback-filter select', badgeos_get_feedback );
	$body.on( 'submit', '.badgeos-feedback-search-form', function( event ) {
		event.preventDefault();
		badgeos_get_feedback();
	} );

	function badgeos_hide_submission_comments( submissions_wrapper ) {
		submissions_wrapper.find( '.badgeos-submission-comments-wrap' ).hide();
		submissions_wrapper.find( '.badgeos-comment-form' ).hide();
		//submissions_wrapper.find( '.submission-comment-toggle' ).show();
	}

	// Hide comment form on feedback posts with toggle
	var $submissions_wrapper = $('.badgeos-feedback-container');
    if($('div.badgeos-feedback-container .badgeos-submissions').length){
        $submissions_wrapper = $('.badgeos-feedback-container');
    }else{
        $submissions_wrapper = $('.badgeos-submissions');
    }

	badgeos_hide_submission_comments( $submissions_wrapper );
	$submissions_wrapper.on( 'click', '.submission-comment-toggle', function( event ) {
		event.preventDefault();

		var $button = $(this);
		$button.siblings( '.badgeos-submission-comments-wrap' ).fadeIn('fast');
		$button.siblings( '.badgeos-comment-form' ).fadeIn('fast');
		$button.hide();

        var value = $button.siblings('.badgeos-comment-form').find('.badgeos_comment').val();
        if(value){
            alert('Comment is saved in draft mode.');
        }
	});

	// Get feedback posts
	function badgeos_get_feedback() {
		$( '.badgeos-spinner' ).show();

		badgeos_setup_feedback_filters();

		$.ajax( {
			url : badgeos_feedback.ajax_url,
			data : badgeos_feedback,
			dataType : 'json',
			success : function( response ) {
				$( '.badgeos-spinner' ).hide();
				$submissions_wrapper.html( response.data.feedback );
				badgeos_hide_submission_comments( $submissions_wrapper );
			},
			error : function( x, t, m ) {
				if ( window.console ) {
					console.log( [t, m] );
				}
			}
		} );
	}

	// Setup feedback filters
	function badgeos_setup_feedback_filters() {
		if ( 'undefined' != typeof badgeos_feedback.filters && badgeos_feedback.filters ) {
			for ( var field_selector in badgeos_feedback.filters ) {
				if ( '' !== badgeos_feedback.filters[ field_selector ] ) {
					badgeos_feedback[ field_selector ] = $( badgeos_feedback.filters[ field_selector ] ).val();
				}
			}
		}
	}

	// Approve/deny feedback
	$body.on( 'click', '.badgeos-feedback-buttons .button', function( event ) {
		event.preventDefault();
		var $button = $( this );
		$.ajax( {
			url : badgeos_feedback_buttons.ajax_url,
			data : {
				'action' : 'update-feedback',
				'status' : $button.data( 'action' ),
				'feedback_id' : $button.data( 'feedback-id' ),
				'feedback_type' : $button.siblings( 'input[name=feedback_type]' ).val(),
				'achievement_id' : $button.siblings( 'input[name=achievement_id]' ).val(),
				'user_id' : $button.siblings( 'input[name=user_id]' ).val(),
				'wpms' : $button.siblings( 'input[name=wpms]' ).val(),
				'nonce' : $button.siblings( 'input[name=badgeos_feedback_review]' ).val(),
			},
			dataType : 'json',
			success : function( response ) {
				$( '.badgeos-feedback-response', $button.parent() ).remove();
				$( response.data.message ).appendTo( $button.parent() ).fadeOut( 3000 );
				$( '.badgeos-feedback-' + $button.data( 'feedback-id' ) + ' .badgeos-feedback-status' ).html( response.data.status );
				$( '.cmb_id__badgeos_submission_current td, .cmb_id__badgeos_nomination_current td' ).html( response.data.status );
				$( '.badgeos-comment-date-by' ).html( '<span class="badgeos-status-label">Status:</span> '+ response.data.status );

                var feed_back_id = $button.data( 'feedback-id' );

                if(response.data.status != 'Approved'){
                    $('.comment-toggle-'+feed_back_id+'').show();
                }else{
                    $('.comment-toggle-'+feed_back_id+'').hide();
                }
			}
		} );
	} );

	// Our main achievement list AJAX call
	function badgeos_ajax_achievement_list() {
		$.ajax( {
					url : badgeos.ajax_url,
					data : {
						'action' : 'get-achievements',
						'type' : badgeos.type,
						'limit' : badgeos.limit,
						'show_parent' : badgeos.show_parent,
						'show_child' : badgeos.show_child,
						'group_id' : badgeos.group_id,
						'user_id' : badgeos.user_id,
						'wpms' : badgeos.wpms,
						'offset' : $( '#badgeos_achievements_offset' ).val(),
						'count' : $( '#badgeos_achievements_count' ).val(),
						'filter' : $( '#achievements_list_filter' ).val(),
						'search' : $( '#achievements_list_search' ).val(),
						'orderby' : badgeos.orderby,
						'order' : badgeos.order,
						'include' : badgeos.include,
						'exclude' : badgeos.exclude,
						'meta_key' : badgeos.meta_key,
						'meta_value' : badgeos.meta_value
					},
					dataType : 'json',
					success : function( response ) {
						$( '.badgeos-spinner' ).hide();
						if ( response.data.message === null ) {
							//alert("That's all folks!");
						}
						else {
							$( '#badgeos-achievements-container' ).append( response.data.message );
							$( '#badgeos_achievements_offset' ).val( response.data.offset );
							$( '#badgeos_achievements_count' ).val( response.data.badge_count );
							credlyize();
							//hide/show load more button
							if ( response.data.query_count <= response.data.badge_count ) {
								$( '#achievements_list_load_more' ).hide();
							}
							else {
								$( '#achievements_list_load_more' ).show();
							}
						}
					}
				} );

	}

	// Reset all our base query vars and run an AJAX call
	function badgeos_ajax_achievement_list_reset() {

		$( '#badgeos_achievements_offset' ).val( 0 );
		$( '#badgeos_achievements_count' ).val( 0 );

		$( '#badgeos-achievements-container' ).html( '' );
		$( '#achievements_list_load_more' ).hide();

		badgeos_ajax_achievement_list();

	}

	// Listen for changes to the achievement filter
	$( '#achievements_list_filter' ).change(function() {

		badgeos_ajax_achievement_list_reset();

	} ).change();

	// Listen for search queries
	$( '#achievements_list_search_go_form' ).submit( function( event ) {

		event.preventDefault();

		badgeos_ajax_achievement_list_reset();

		//Disabled submit button
		$('#achievements_list_search_go').attr('disabled','disabled');
	});

	//Enabled submit button
	$('#achievements_list_search').focus(function (e) {

		$('#achievements_list_search_go').removeAttr('disabled');
	} );

	// Listen for users clicking the "Load More" button
	$( '#achievements_list_load_more' ).click( function() {

		$( '.badgeos-spinner' ).show();

		badgeos_ajax_achievement_list();

	} );

	// Listen for users clicking the show/hide details link
	$( '#badgeos-achievements-container,.badgeos-single-achievement' ).on( 'click', '.badgeos-open-close-switch a', function( event ) {

		event.preventDefault();

		var link = $( this );

		if ( 'close' == link.data( 'action' ) ) {
			link.parent().siblings( '.badgeos-extras-window' ).slideUp( 300 );
			link.data( 'action', 'open' ).prop( 'class', 'show-hide-open' ).text( 'Show Details' );
		}
		else {
			link.parent().siblings( '.badgeos-extras-window' ).slideDown( 300 );
			link.data( 'action', 'close' ).prop( 'class', 'show-hide-close' ).text( 'Hide Details' );
		}

	} );

	// Credly popup functionality
	if ( 'undefined' != typeof BadgeosCredlyData ) {
		// credly markup
		var credly = '<div class="credly-share-popup" style="display: none;"><div class="credly-wrap"><span>'
						 + BadgeosCredlyData.message + '</span><div class="badgeos-spinner"></div>'
						 + '<span class="credly-message" style="display: none;"><strong class="error">'
						 + BadgeosCredlyData.errormessage + '</strong></span>'
						 + '<input type="submit" class="credly-button credly-send" value="' + BadgeosCredlyData.confirm + '"/>'
						 + '<a class="credly-button credly-cancel" href="#">' + BadgeosCredlyData.cancel + '</a>'
						 + '<div style="clear: both;"></div></div><div style="clear: both;"></div></div>';

		// add credly popup to dom
		$body.append( credly );
		credly = $( '.credly-share-popup' );

		var credlyMessage = $( '.credly-message' );
		var credlySpinner = $( '.badgeos-spinner' );
		var credlySend = $( '.credly-button.credly-send' );
		var spinnerTimer;

		// make a function so we can call it from our ajax success functions
		function credlyize() {

			$( '.share-credly.addCredly' ).append( '<a class="credly-share" style="display: none;" href="#" title="'
													   + BadgeosCredlyData.share + '"></a>' ).removeClass( 'addCredly' );

		}

		credlyize();

		// add credly share button to achievements in widget
		$body.on( 'click', '.credly-share', function( event ) {

			event.preventDefault();

			credlyHide();
			var el = $( this );
			var parent = el.parent();
			var width = parent.outerWidth();
			var elPosition = el.offset();
			var id = parent.data( 'credlyid' );

			// move credly popup
			credly.width( width ).css( {
				top : Math.floor( elPosition.top - credly.outerHeight() ), left : Math.floor( elPosition.left )
			} ).show().data( 'credlyid', id );

		} );

		$body.on( 'click', '.credly-button.credly-cancel', function( event ) {

			// Cancel Credly popup
			event.preventDefault();

			credlyHide();

		} ).on( 'click', '.credly-button.credly-send', function( event ) {

			// Our Credly button handler
			event.preventDefault();

			credlySend.hide();
			credlySpinner.show();

			badgeos_send_to_credly( credly.data( 'credlyid' ) );

		} );

		// reset hidden credly elements
		function credlyHide() {

			clearTimeout( spinnerTimer );

			credly.hide();
			credlySend.show();
			credlySpinner.hide();

			credlyMessage.hide().css( { opacity : 1.0 } ).html( BadgeosCredlyData.errormessage );

		}

		// Our Credly AJAX call
		function badgeos_send_to_credly( ID ) {

			$.ajax( {
				type : 'post',
				dataType : 'json',
				url : BadgeosCredlyData.ajax_url,
				data : {
					'action' : 'achievement_send_to_credly',
					'ID' : ID
				},
				success : function( response ) {

					// hide our loading spinner
					credlySpinner.hide();

					// show our 'saved' notification briefly
					credlyMessage.fadeTo( 'fast', 1 ).animate( { opacity : 1.0 }, 2000 ).fadeTo( 'slow', 0 ).html( response );

					setTimeout( function() {

						credlyHide();

					}, 2200 );

				},
				error : function( x, t, m ) {

					if ( t === 'timeout' ) {
						credlySpinner.hide();
						credlyMessage.show();

						setTimeout( function() {

							credlyMessage.animate( { opacity : 0 }, 500 );

							setTimeout( function() {

								credlyMessage.hide().css( { opacity : 1.0 } );
								credlySend.show();

							}, 600 );

						}, 1100 );
					}
					else {
						// hide our loading spinner
						credlySpinner.hide();

						// show our 'saved' notification briefly
						var html = '<strong class="error">' + BadgeosCredlyData.localized_error + ' ' + t + '</strong>';

						credlyMessage.fadeTo( 'fast', 1 ).animate( { opacity : 1.0 }, 2000 ).fadeTo( 'slow', 0 ).html( html );

						setTimeout( function() {

							credlyHide();

						}, 2200 );
					}

				}

			} );

		}
	}

} );
