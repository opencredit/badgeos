jQuery(document).ready(function($){

	// Our main achievement list AJAX call
	function badgeos_ajax_achievement_list(){
		$.ajax({
			url: badgeos.ajax_url,
			data: {
				'action':      'achievements_list_load_more',
				'type':        badgeos.type,
				'limit':       badgeos.limit,
				'show_parent': badgeos.show_parent,
				'show_child':  badgeos.show_child,
				'group_id':    badgeos.group_id,
				'offset':      $('#badgeos_achievements_offset').val(),
				'count':       $('#badgeos_achievements_count').val(),
				'filter':      $('#achievements_list_filter').val(),
				'search':      $('#achievements_list_search').val()
			},
			dataType: 'json',
			success: function( response ) {
				$('.badgeos-spinner').hide();
				if ( response.message === null ) {
					//alert("That's all folks!");
				} else {
					$('#badgeos-achievements-container').append( response.message );
					$('#badgeos_achievements_offset').val( response.offset );
					$('#badgeos_achievements_count').val( response.badge_count );
					//hide/show load more button
					if( response.query_count <= response.badge_count ){
						$('#achievements_list_load_more').hide();
					}else{
						$('#achievements_list_load_more').show();
					}
				}
			}
		});

	}

	// Reset all our base query vars and run an AJAX call
	function badgeos_ajax_achievement_list_reset(){
		$('#badgeos_achievements_offset').val( 0 );
		$('#badgeos_achievements_count').val( 0 );
		$('#badgeos-achievements-container').html('');
		$('#achievements_list_load_more').hide();
		badgeos_ajax_achievement_list();
	}

	// Listen for changes to the achievement filter
	$('#achievements_list_filter').change(function(){
		badgeos_ajax_achievement_list_reset();
	}).change();

	// Listen for search queries
	$('#achievements_list_search_go_form').submit(function(event){
		event.preventDefault();
		badgeos_ajax_achievement_list_reset();
	});

	// Listen for users clicking the "Load More" button
	$('#achievements_list_load_more').click(function(){
		$('.badgeos-spinner').show();
		badgeos_ajax_achievement_list();
	});

	// Listen for users clicking the show/hide details link
	$('#badgeos-achievements-container').on( 'click', '.badgeos-open-close-switch a', function(event){
		event.preventDefault();

		var link = $(this);
		if ( 'close' == link.data('action') ) {
			link.parent().siblings('.badgeos-extras-window').slideUp(300);
			link.data('action', 'open').attr('class', 'show-hide-open').text('Show Details');
		} else {
			link.parent().siblings('.badgeos-extras-window').slideDown(300);
			link.data('action', 'close').attr('class', 'show-hide-close').text('Hide Details');
		}
	});

	// Credly popup functionality

	// credly markup
	var credly = '<div class="credly-share-popup" style="display: none;"><div class="credly-wrap"><span>' + BadgeosCredlyData.message + '</span><div class="badgeos-spinner"></div><span class="credly-message" style="display: none;"><strong class="error">' + BadgeosCredlyData.errormessage + '</strong></span><input type="submit" class="credly-button credly-send" value="' + BadgeosCredlyData.confirm + '"/><a class="credly-button credly-cancel" href="#">' + BadgeosCredlyData.cancel + '</a><div style="clear: both;"></div></div><div style="clear: both;"></div></div>';
	// add credly popup to dom
	$('body').append(credly);
	credly = $('.credly-share-popup');
	var credlyMessage = $('.credly-message');
	var credlySpinner = $('.badgeos-spinner');
	var credlySend = $('.credly-button.credly-send');
	var spinnerTimer;

	// add credly share button to achievements in widget
	$('.widget-achievements-listing-item.share-credly').append('<a class="credly-share" style="display: none;" href="#" title="' + BadgeosCredlyData.share + '"></a>').on( 'click', '.credly-share', function(event) {
		event.preventDefault();

		credlyHide();
		var el = $(this);
		var parent = el.parent();
		var width = parent.outerWidth();
		var elPosition = el.offset();
		var id = parent.attr('id').replace('widget-achievements-listing-item-', '');

		console.log( id );

		// move credly popup
		credly
			.width(width)
			.css({
				top: Math.floor(elPosition.top - credly.outerHeight() ), left: Math.floor(elPosition.left)
			})
			.show()
			.data('credlyID',id);
	});

	$('body')
		.on( 'click', '.credly-button.credly-cancel', function(event) {
			// Cancel Credly popup

			event.preventDefault();
			credlyHide();
		})
		.on( 'click', '.credly-button.credly-send', function(event) {
			// Our Credly button handler

			event.preventDefault();

			credlySend.hide();
			credlySpinner.show();

			console.log( credly.data('credlyID') );
			badgeos_send_to_credly( credly.data('credlyID') );

		});

	// reset hidden credly elements
	function credlyHide() {
		clearTimeout( spinnerTimer );
		credly.hide();
		credlySend.show();
		credlySpinner.hide();
		credlyMessage.hide().css({opacity:'1'}).html(BadgeosCredlyData.errormessage);
	}

	// Our Credly AJAX call
	function badgeos_send_to_credly( ID ) {
		$.ajax({
			type : 'post',
			dataType : 'json',
			url: badgeos.ajax_url,
			data: {
				'action': 'achievement_send_to_credly',
				'ID': ID
			},
			success: function( response ) {
				console.log(response);
				// hide our loading spinner
				credlySpinner.hide();
				// show our 'saved' notification briefly
				credlyMessage.fadeTo('fast', 1).animate({opacity: 1.0}, 2000).fadeTo('slow', 0).html(response);
				setTimeout( function() {
					credlyHide();
				}, 2200);

			},
			error: function(x, t, m) {
				console.log(x);
				console.log(t);
				console.log(m);

				if( t === 'timeout' ) {

					credlySpinner.hide();
					credlyMessage.show();

					setTimeout( function() {
						credlyMessage.animate({opacity:0}, 500);

						setTimeout( function() {
							credlyMessage.hide().css({opacity:'1'});
							credlySend.show();
						}, 600);
					}, 1100);

				} else {

					// hide our loading spinner
					credlySpinner.hide();
					// show our 'saved' notification briefly
					var html = '<strong class="error">' + BadgeosCredlyData.localized_error + ' ' + t +'</strong>';
					credlyMessage.fadeTo('fast', 1).animate({opacity: 1.0}, 2000).fadeTo('slow', 0).html(html);
					setTimeout( function() {
						credlyHide();
					}, 2200);

				}
			}
		});
	}

});