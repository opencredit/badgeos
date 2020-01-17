jQuery(document).ready(function($) {

	/**
	 * For Tool Page
     */

    $( '#award-achievement, #award-credits, #award-ranks' ).change( function() {
        if( $( '#award-achievement, #award-credits, #award-ranks' ).is(':checked') ) {
            $( '#badgeos-award-users' ).parents( 'tr' ).find( 'th, th label, td, td select, td span' ).slideUp( { duration: 500 } );
        } else {
            $( '#badgeos-award-users' ).parents( 'tr' ).find( 'th, th label, td, td select, td span' ).slideDown( { duration: 500 } );
        }
    } );

    $( '#revoke-achievement, #revoke-credits, #revoke-ranks' ).change( function() {
        if( $( '#revoke-achievement, #revoke-credits, #revoke-ranks' ).is(':checked') ) {
            $( '#badgeos-revoke-users' ).parents( 'tr' ).find( 'th, th label, td, td select, td span' ).slideUp( { duration: 500 } );
        } else {
            $( '#badgeos-revoke-users' ).parents( 'tr' ).find( 'th, th label, td, td select, td span' ).slideDown( { duration: 500 } );
        }
    } );

    /**
	 * For user profile page
     * Revoke Ranks
     */
    $( 'body' ).on( 'click', 'table.badgeos-rank-table .revoke-rank', function() {
		var self = $( this );

		self.siblings( '.spinner-loader' ).find( '.revoke-rank-spinner' ).show();

		var rankID = self.attr( 'data-rank_id' );
		var userID = self.attr( 'data-user_id' );
		var ajaxURL = self.attr( 'data-admin_ajax' );

        var data = {
            'action': 'user_profile_revoke_rank',
            'rankID': rankID,
            'userID': userID,
        };

        jQuery.post( ajaxURL, data, function( response ) {

            self.siblings( '.spinner-loader' ).find( '.revoke-rank-spinner' ).hide();

            if( 'true' == response ) {

                self.parents( 'tr' ).find( 'td' ).slideUp( 800, function() {
                    self.parents( 'tr' ).remove();

                    if( ! $( 'table.badgeos-rank-revoke-table > tbody tr' ).length ) {
                        $( 'table.badgeos-rank-revoke-table > tbody' ).append(
                            '<tr class="no-awarded-rank">' +
                            '<td colspan="5">' +
                            '<span class="description">'+ admin_js.no_awarded_rank +'</span>' +
                            '</td> </tr>'
                        );
                    }
                } );
			} else if( 'false' == response ) {
                self.siblings( '.spinner-loader' ).append( $( 'Try Again' ) );
			}
        });
    } );

    /**
     * For Profile Page
     * Display Ranks to award
     */
    $( 'body' ).on( 'click', '.user-profile-award-ranks .display-ranks-to-award', function() {
		var self = $( this );

        self.parents( '.user-profile-award-ranks' ).find( '.revoke-rank-spinner' ).show();
        var ajaxURL = self.attr( 'data-admin_ajax' );
        var userID = self.attr( 'data-user-id' );
        var rankType = self.attr( 'data-rank-filter' );

        var data = {
            'action': 'user_profile_display_award_list',
            'user_id': userID,
            'rank_filter': rankType
        };

        jQuery.post( ajaxURL, data, function( response ) {
            self.parents( '.user-profile-award-ranks' ).find( '.revoke-rank-spinner' ).hide();

            $( '.user-profile-award-ranks .badgeos-rank-table-to-award' ).remove();
            self.parents( '.user-profile-award-ranks' ).append( $( response ) );

        });
    } );

    /**
     * For Profile Page
     * Award rank
     */
    $( 'body' ).on( 'click', '.user-profile-award-ranks .badgeos-rank-table-to-award .award-rank', function() {
		var self = $( this );

        self.siblings( '.spinner-loader' ).find( '.award-rank-spinner' ).show();
        var ajaxURL = self.attr( 'data-admin-ajax' );
        var userID = self.attr( 'data-user-id' );
        var rankID = self.attr( 'data-rank-id' );

        var data = {
            'action': 'user_profile_award_rank',
            'user_id': userID,
            'rank_id': rankID
        };

        jQuery.post( ajaxURL, data, function( response ) {
            self.siblings( '.spinner-loader' ).find( '.award-rank-spinner' ).hide();

            if( 'true' == response ) {
                var rankID = self.attr( 'data-rank-id' );
                var userID = self.attr( 'data-user-id' );
                var ajaxURL = self.attr( 'data-admin-ajax' );
                var rankType = self.parents( 'tr' ).attr( 'class' );
                var defaultRank = self.parents( 'tr' ).attr( 'id' );
                var cloned = self.parents( 'tr' ).clone( true );

                var replaceWidth = '<td class="last-awarded" align="center"><span class="profile_ranks_last_award_field">&#10003;</span></td><td><span data-rank_id="' + rankID + '" data-user_id="' + userID + '" data-admin_ajax="'+ ajaxURL +'" class="revoke-rank">'+ admin_js.revoke_rank +'</span><span class="spinner-loader" ><img class="award-rank-spinner" src="'+ admin_js.loading_img +'" style="margin-left: 10px; display: none;" /></span></td>';

                if( 'default-rank' == defaultRank ) {
                    replaceWidth = '<td class="last-awarded" align="center"><span class="profile_ranks_last_award_field">&#10003;</span></td><td><span class="default-rank">'+ admin_js.default_rank +'</span></td>';
                }
                $( 'td.award-rank-column', cloned ).replaceWith( replaceWidth );

                /**
                 * Remove last awarded from siblings
                 */
                $.each( $( '.badgeos-rank-revoke-table > tbody > tr' ), function( index, value ) {
                    if( $( value ).hasClass( rankType ) ) {
                        $( value ).find( 'td.last-awarded' ).empty();
                    }
                } );

                $( '.badgeos-rank-revoke-table > tbody' ).append( cloned );
                $( '.badgeos-rank-revoke-table > tbody > tr.no-awarded-rank td, .badgeos-rank-revoke-table > tbody > tr.no-awarded-rank span' ).slideUp( 800,function() { $( '.badgeos-rank-revoke-table > tbody > tr.no-awarded-rank' ).remove() } );

                self.parents( 'tr' ).find( 'td' ).slideUp( 800,function() { self.parents( 'tr' ).remove() } );
            } else if( 'false' == response ) {
                self.siblings( '.spinner-loader' ).append( $( 'Try Again' ) );
            }

            $( '.user-profile-award-ranks .badgeos-rank-table-to-award' ).remove();
            self.parents( '.user-profile-award-ranks' ).append( $( response ) );
        });
    } );

	$( '.badgeos-credits .badgeos-credit-edit' ).click( function() {

        var self = $( this );
        self.siblings( '.badgeos-edit-credit-wrapper' ).show();
        self.siblings( '.badgeos-earned-credit' ).hide();
    } );

    $('#badgeos_ach_check_all').click( function( event ) {

    	//event.preventDefault();
        $('input[name="badgeos_ach_check_indis[]"]').not(this).prop('checked', this.checked);
    });
    $('#badgeos_btn_revoke_bulk_achievements').click( function( event ) {

    	var self = $(this);
        var chkArray = [];
        var badgeos_user_id = $('#badgeos_user_id').val();
        $('input[name="badgeos_ach_check_indis[]"]:checked').each(function() {
            chkArray.push($(this).val());
        });
        var data = {
            action: 'delete_badgeos_bulk_achievements', achievements: chkArray, user_id: badgeos_user_id
        };

        self.siblings( '#revoke-badges-loader' ).show();
        $.post( admin_js.ajax_url, data, function(response) {
            self.siblings( '#revoke-badges-loader' ).hide();
        	if( response == 'success' ) {
                window.location.reload();
            } else {
                $( '#wpbody-content .wrap' ).prepend( '<div class="notice notice-warning is-dismissible"><p>'+ response+'</p></div>' );
            }
        } );
        console.log(chkArray);
    });
    function click_to_dismiss( div_id ){
        $( "#".div_id ).remove();
    }

    // Dynamically show/hide achievement meta inputs based on "Award By" selection
	$("#_badgeos_earned_by").change( function() {

		// Define our potentially unnecessary inputs
		var badgeos_sequential = $('#_badgeos_sequential').parent().parent();
		var badgeos_points_required = $('#_badgeos_points_required_badgeos_points_required').parent().parent().parent();

		// // Hide our potentially unnecessary inputs
		badgeos_sequential.hide();
		badgeos_points_required.hide();

		// Determine which inputs we should show
		if ( 'triggers' == $(this).val() )
			badgeos_sequential.show();
		else if ( 'points' == $(this).val() )
			badgeos_points_required.show();

	}).change();

	$('[href="#show-api-key"]').click( function(event) {
		event.preventDefault();
		$('#credly-settings tr, #credly-settings .toggle').toggle();
	});

	// Throw a warning on Achievement Type editor if title is > 20 characters
	$('#titlewrap').on( 'keyup', 'input[name=post_title]', function() {

		// Make sure we're editing an achievement type
		if ( admin_js.achievement_post_type == $('#post_type').val() ) {
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

	// Show notification custom message input if setting is enabled
	$('#credly_badge_sendemail_add_message').change( function() {
		if ( 'true' == $(this).val() )
			$('.credly-notifications-message').show();
		else
			$('.credly-notifications-message').hide();
	}).change();

	$( '#delete_log_entries' ).click( function() {
		var confirmation = confirm( 'It will delete all the log entries' );
		if( confirmation ) {
            var data = {
                'action': 'delete_badgeos_log_entries'
            };
            $.post( admin_js.ajax_url, data, function(response) {
                $( '#wpbody-content .wrap' ).prepend( '<div class="notice notice-warning delete-log-entries"><p><img src="'+ admin_js.loading_img +'" /> &nbsp;&nbsp;BadgeOS is deleting log entries as background process, you can continue exploring badgeos</p></div>' );

                setTimeout( function() {
                	$( '#wpbody-content .wrap .delete-log-entries' ).slideUp();
				}, 10000 );
            } );
		}
	});

    $( '#badgeos_migrate_meta_to_db' ).click( function() {
        var confirmation = confirm( "It will update the users' existing achievements and points in the badgeos table." );
        if( confirmation ) {
            var data = {
                'action': 'badgeos_migrate_data_from_meta_to_db'
            };
            $.post( admin_js.ajax_url, data, function(response) {
                $( '.badgeos_migrate_meta_to_db_message' ).html( '<div class="notice notice-warning delete-log-entries"><p><img src="'+ admin_js.loading_img +'" /> &nbsp;&nbsp;BadgeOS is shifting data as a background process. You will receive a confirmation email upon successful completion. You can continue exploring badgeos.</p></div>' );
            } );
        }
    });

    $( '#badgeos_migrate_fields_single_to_multi' ).click( function() {
        var confirmation = confirm( 'It will update the existing achievement points with the point types.' );
        if( confirmation ) {
            var data = {
                'action': 'badgeos_migrate_fields_points_to_point_types'
            };
            $.post( admin_js.ajax_url, data, function(response) {
                $( '.badgeos_migrate_fields_single_to_multi_message' ).html( '<div class="notice notice-warning migrage-point-fields-entries"><p>'+response+'</p></div>' ).slideDown();

                /*setTimeout( function() {
                    $( '.badgeos_migrate_fields_single_to_multi_message' ).slideUp();
                }, 3000 );*/
            } );
        }
    });

    $( '#badgeos_notice_update_from_meta_to_db' ).click( function() {
        var confirmation = confirm( "It will update the users' existing achievements and points in the badgeos table" );
        if( confirmation ) {
            var data = {
                'action': 'badgeos_migrate_data_from_meta_to_db_notice'
            };
            $.post( admin_js.ajax_url, data, function(response) {
                $( '#wpbody-content .wrap' ).prepend( '<div class="notice notice-warning"><p><img src="'+ admin_js.loading_img +'" /> &nbsp;&nbsp;BadgeOS is shifting data as a background process. You will receive a confirmation email upon successful completion. You can continue exploring badgeos.</p></div>' );

                setTimeout( function() {
                    $( '#wpbody-content .wrap .migrate-meta-to-db' ).slideUp();
                }, 1000 );
            } );
        }
    });

});
