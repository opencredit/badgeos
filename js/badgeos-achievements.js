jQuery(function ($) {

	var $body = $('body');

	// Retrieve feedback posts when an approriate action is taken
	$body.on('change', '.badgeos-feedback-filter select', badgeos_get_feedback);
	$body.on('submit', '.badgeos-feedback-search-form', function (event) {
		event.preventDefault();
		badgeos_get_feedback();
	});

	function badgeos_hide_submission_comments(submissions_wrapper) {
		submissions_wrapper.find('.badgeos-submission-comments-wrap').hide();
		submissions_wrapper.find('.badgeos-comment-form').hide();
		//submissions_wrapper.find( '.submission-comment-toggle' ).show();
	}

	function badgeos_hide_submission_comments(submissions_wrapper) {
		submissions_wrapper.find('.badgeos-submission-comments-wrap').hide();
		submissions_wrapper.find('.badgeos-comment-form').hide();
		//submissions_wrapper.find( '.submission-comment-toggle' ).show();
	}

	// Hide comment form on feedback posts with toggle
	var $submissions_wrapper = $('.badgeos-feedback-container');
	if ($('div.badgeos-feedback-container .badgeos-submissions').length) {
		$submissions_wrapper = $('.badgeos-feedback-container');
	} else {
		$submissions_wrapper = $('.badgeos-submissions');
	}

	badgeos_hide_submission_comments($submissions_wrapper);
	$submissions_wrapper.on('click', '.submission-comment-toggle', function (event) {
		event.preventDefault();

		var $button = $(this);
		$button.parent().siblings('.badgeos-submission-comments-wrap').fadeIn('fast');
		$button.parent().siblings('.badgeos-comment-form').fadeIn('fast');
		$button.hide();
		$button.parent().siblings('.badgeos-comment-form').fadeIn('fast');
		var value = $button.parent().siblings('.badgeos-comment-form').find('.badgeos_comment').val();
		if (value) {
			alert('Comment is saved in draft mode.');
		}
	});

	// Get feedback posts
	function badgeos_get_feedback() {
		$('.badgeos-spinner').show();

		badgeos_setup_feedback_filters();

		$.ajax({
			url: badgeos_feedback.ajax_url,
			data: badgeos_feedback,
			dataType: 'json',
			success: function (response) {
				$('.badgeos-spinner').hide();
				$submissions_wrapper.html(response.data.feedback);
				badgeos_hide_submission_comments($submissions_wrapper);
			},
			error: function (x, t, m) {
				if (window.console) {
					console.log([t, m]);
				}
			}
		});
	}

	// Setup feedback filters
	function badgeos_setup_feedback_filters() {
		if ('undefined' != typeof badgeos_feedback.filters && badgeos_feedback.filters) {
			for (var field_selector in badgeos_feedback.filters) {
				if ('' !== badgeos_feedback.filters[field_selector]) {
					badgeos_feedback[field_selector] = $(badgeos_feedback.filters[field_selector]).val();
				}
			}
		}
	}

	// Approve/deny feedback
	$body.on('click', '.badgeos-feedback-buttons .button', function (event) {
		event.preventDefault();
		var $button = $(this);
		$.ajax({
			url: badgeos_feedback_buttons.ajax_url,
			data: {
				'action': 'update-feedback',
				'status': $button.data('action'),
				'feedback_id': $button.data('feedback-id'),
				'feedback_type': $button.siblings('input[name=feedback_type]').val(),
				'achievement_id': $button.siblings('input[name=achievement_id]').val(),
				'user_id': $button.siblings('input[name=user_id]').val(),
				'wpms': $button.siblings('input[name=wpms]').val(),
				'nonce': $button.siblings('input[name=badgeos_feedback_review]').val(),
			},
			dataType: 'json',
			success: function (response) {
				$('.badgeos-feedback-response', $button.parent()).remove();
				$(response.data.message).appendTo($button.parent()).fadeOut(3000);
				$('.badgeos-feedback-' + $button.data('feedback-id') + ' .badgeos-feedback-status').html(response.data.status);
				$('.cmb2-id--badgeos-submission-current .cmb-td, .cmb2-id--badgeos-nomination-current .cmb-td').html(response.data.status);
				$('.badgeos-comment-date-by').html('<span class="badgeos-status-label">Status:</span> ' + response.data.status);

				var feed_back_id = $button.data('feedback-id');

				if (response.data.status != 'Approved') {
					$('.comment-toggle-' + feed_back_id + '').show();
				} else {
					$('.comment-toggle-' + feed_back_id + '').hide();
				}
			}
		});
	});

	function show_earned_rank_list_html($mainobj) {

		var data_ajaxurl = $mainobj.attr("data-url");
		var data_type = $mainobj.attr("data-rank_type");
		var data_limit = $mainobj.attr("data-limit");
		var data_show_search = $mainobj.attr("data-show_search");
		var data_user_id = $mainobj.attr("data-user_id");
		var data_orderby = $mainobj.attr("data-orderby");
		var data_order = $mainobj.attr("data-order");
		var data_show_title = $mainobj.attr("data-show_title");
		var data_show_thumb = $mainobj.attr("data-show_thumb");
		var data_show_description = $mainobj.attr("data-show_description");
		var data_default_view = $mainobj.attr("data-default_view");
		var data_image_width = $mainobj.attr("data-image_width");
		var data_image_height = $mainobj.attr("data-image_height");
		$mainobj.find('div.badgeos-earned-spinner').show();

		$.ajax({
			url: data_ajaxurl,
			data: {
				'action': 'get-earned-ranks',
				'rank_type': data_type,
				'limit': data_limit,
				'user_id': data_user_id,
				'offset': $mainobj.find('#badgeos_earned_ranks_offset').val(),
				'count': $mainobj.find('#badgeos_ranks_count').val(),
				'search': $mainobj.find('#earned_ranks_list_search').val(),
				'orderby': data_orderby,
				'order': data_order,
				'show_title': data_show_title,
				'show_thumb': data_show_thumb,
				'default_view': data_default_view,
				'show_description': data_show_description,
				'image_width': data_image_width,
				'image_height': data_image_height,
			},
			dataType: 'json',
			success: function (response) {
				$mainobj.find('div.badgeos-earned-spinner').hide();
				if (response.data.message === null) {
					//alert("That's all folks!");
				} else {
					var offset_val = $mainobj.find('#badgeos_earned_ranks_offset').val();
					if (parseInt(offset_val) > 0) {
						$mainobj.find('div#badgeos-earned-ranks-container .ls_grid_container').append(response.data.message);
					}
					else
						$mainobj.find('div#badgeos-earned-ranks-container').append(response.data.message);

					$mainobj.find('#badgeos_earned_ranks_offset').val(response.data.offset);
					$mainobj.find('#badgeos_ranks_count').val(response.data.badge_count);

					//hide/show load more button
					if (response.data.query_count <= response.data.offset) {
						$mainobj.find('.earned_ranks_list_load_more').hide();

					} else {
						$mainobj.find('.earned_ranks_list_load_more').show();
					}

					$('.badgeos-arrange-buttons button').on('click', function () {
						$('.badgeos-arrange-buttons button').removeClass('selected');
						$(this).addClass('selected');
						if ($(this).hasClass('grid')) {
							$('#badgeos-earned-ranks-container ul').removeClass('list').addClass('grid');
						}
						else if ($(this).hasClass('list')) {
							$('#badgeos-earned-ranks-container ul').removeClass('grid').addClass('list');
						}
					});
				}
			}
		});
	}

	function show_earned_achievement_list_html($mainobj) {
		var data_ajaxurl = $mainobj.attr("data-url");
		var data_type = $mainobj.attr("data-type");
		var data_limit = $mainobj.attr("data-limit");
		var data_show_search = $mainobj.attr("data-show_search");
		var data_user_id = $mainobj.attr("data-user_id");
		var data_wpms = $mainobj.attr("data-wpms");
		var data_orderby = $mainobj.attr("data-orderby");
		var data_order = $mainobj.attr("data-order");
		var data_include = $mainobj.attr("data-include");
		var data_exclude = $mainobj.attr("data-exclude");
		var data_show_title = $mainobj.attr("data-show_title");
		var data_show_thumb = $mainobj.attr("data-show_thumb");
		var data_show_description = $mainobj.attr("data-show_description");
		var data_default_view = $mainobj.attr("data-default_view");
		var data_image_width = $mainobj.attr("data-image_width");
		var data_image_height = $mainobj.attr("data-image_height");

		$mainobj.find('div.badgeos-earned-spinner').show();

		$.ajax({
			url: data_ajaxurl,
			data: {
				'action': 'get-earned-achievements',
				'type': data_type,
				'limit': data_limit,
				'user_id': data_user_id,
				'wpms': data_wpms,
				'offset': $mainobj.find('#badgeos_achievements_offset').val(),
				'count': $mainobj.find('#badgeos_achievements_count').val(),
				'search': $mainobj.find('#earned_achievements_list_search').val(),
				'orderby': data_orderby,
				'order': data_order,
				'include': data_include,
				'exclude': data_exclude,
				'show_title': data_show_title,
				'show_thumb': data_show_thumb,
				'show_description': data_show_description,
				'default_view': data_default_view,
				'image_width': data_image_width,
				'image_height': data_image_height
			},
			dataType: 'json',
			success: function (response) {
				$mainobj.find('div.badgeos-earned-spinner').hide();
				if (response.data.message === null) {
					//alert("That's all folks!");
				} else {
					var offset_val = $mainobj.find('#badgeos_achievements_offset').val();
					if (parseInt(offset_val) > 0) {
						$mainobj.find('div#badgeos-earned-achievements-container .ls_grid_container').append(response.data.message);
					} else
						$mainobj.find('div#badgeos-earned-achievements-container').append(response.data.message);
					$mainobj.find('#badgeos_achievements_offset').val(response.data.offset);
					$mainobj.find('#badgeos_achievements_count').val(response.data.badge_count);

					//hide/show load more button
					if (response.data.query_count <= response.data.offset) {
						$mainobj.find('.earned_achievements_list_load_more').hide();

					} else {
						$mainobj.find('.earned_achievements_list_load_more').show();
					}
				}

				$('.badgeos-arrange-buttons button').on('click', function () {
					$('.badgeos-arrange-buttons button').removeClass('selected');
					$(this).addClass('selected');
					if ($(this).hasClass('grid')) {
						$('#badgeos-earned-achievements-container ul').removeClass('list').addClass('grid');
					}
					else if ($(this).hasClass('list')) {
						$('#badgeos-earned-achievements-container ul').removeClass('grid').addClass('list');
					}
				});

				$('.badgeos-ob-verification-buttons').on('click', function () {
					badgeos_ob_verification_process(this);
				});
			}
		});
	}

	function show_list_html($mainobj) {
		var data_ajaxurl = $mainobj.attr("data-url");
		var data_type = $mainobj.attr("data-type");
		var data_limit = $mainobj.attr("data-limit");
		var data_show_child = $mainobj.attr("data-show_child");
		var data_show_parent = $mainobj.attr("data-show_parent");
		var data_show_filter = $mainobj.attr("data-show_filter");
		var data_show_search = $mainobj.attr("data-show_search");
		var data_group_id = $mainobj.attr("data-group_id");
		var data_user_id = $mainobj.attr("data-user_id");
		var data_wpms = $mainobj.attr("data-wpms");
		var data_orderby = $mainobj.attr("data-orderby");
		var data_order = $mainobj.attr("data-order");
		var data_include = $mainobj.attr("data-include");
		var data_exclude = $mainobj.attr("data-exclude");
		var data_meta_key = $mainobj.attr("data-meta_key");
		var data_meta_value = $mainobj.attr("data-meta_value");
		var data_show_title = $mainobj.attr("data-show_title");
		var data_show_thumb = $mainobj.attr("data-show_thumb");
		var data_show_description = $mainobj.attr("data-show_description");
		var data_show_steps = $mainobj.attr("data-show_steps");
		var data_default_view = $mainobj.attr("data-default_view");
		var data_image_width = $mainobj.attr("data-image_width");
		var data_image_height = $mainobj.attr("data-image_height");

		$mainobj.find('div.badgeos-spinner').show();

		$.ajax({
			url: data_ajaxurl,
			data: {
				'action': 'get-achievements',
				'type': data_type,
				'limit': data_limit,
				'show_parent': data_show_child,
				'show_child': data_show_parent,
				'group_id': data_group_id,
				'user_id': data_user_id,
				'wpms': data_wpms,
				'offset': $mainobj.find('#badgeos_achievements_offset').val(),
				'count': $mainobj.find('#badgeos_achievements_count').val(),
				'filter': $mainobj.find('#achievements_list_filter').val(),
				'search': $mainobj.find('#achievements_list_search').val(),
				'orderby': data_orderby,
				'order': data_order,
				'include': data_include,
				'exclude': data_exclude,
				'meta_key': data_meta_key,
				'meta_value': data_meta_value,
				'show_title': data_show_title,
				'show_thumb': data_show_thumb,
				'show_description': data_show_description,
				'show_steps': data_show_steps,
				'default_view': data_default_view,
				'image_width': data_image_width,
				'image_height': data_image_height,
			},
			dataType: 'json',
			success: function (response) {
				$mainobj.find('div.badgeos-spinner').hide();
				if (response.data.message === null) {
					//alert("That's all folks!");
				} else {
					var offset_val = $mainobj.find('#badgeos_achievements_offset').val();
					if (parseInt(offset_val) > 0) {
						$mainobj.find('div#badgeos-achievements-container .ls_grid_container').append(response.data.message);
					}
					else
						$mainobj.find('div#badgeos-achievements-container').append(response.data.message);

					$mainobj.find('#badgeos_achievements_offset').val(response.data.offset);
					$mainobj.find('#badgeos_achievements_count').val(response.data.badge_count);

					credlyize();
					//hide/show load more button
					if (response.data.query_count <= response.data.offset) {
						$mainobj.find('.achievements_list_load_more').hide();

					} else {
						$mainobj.find('.achievements_list_load_more').show();
					}
				}

				$('.badgeos-arrange-buttons button').on('click', function () {
					$('.badgeos-arrange-buttons button').removeClass('selected');
					$(this).addClass('selected');
					if ($(this).hasClass('grid')) {
						$('#badgeos-achievements-container ul').removeClass('list').addClass('grid');
					}
					else if ($(this).hasClass('list')) {
						$('#badgeos-achievements-container ul').removeClass('grid').addClass('list');
					}
				});
			}
		});
	}

	// Our main achievement list AJAX call
	function badgeos_ajax_earned_achievement_list() {
		$('.badgeos_earned_achievements_offset').val('0');
		$(".badgeos_earned_achievement_main_container").each(function (index) {
			var $mainobj = $(this);
			show_earned_achievement_list_html($mainobj)
		});
	}

	// Our main achievement list AJAX call
	function badgeos_ajax_earned_ranks_list() {
		$('.badgeos_earned_ranks_offset').val('0');
		$(".badgeos_earned_rank_main_container").each(function (index) {
			var $mainobj = $(this);
			show_earned_rank_list_html($mainobj)
		});
	}

	// Our main achievement list AJAX call
	function badgeos_ajax_achievement_list() {
		$(".badgeos_achievement_main_container").each(function (index) {
			var $mainobj = $(this);
			show_list_html($mainobj)
		});
	}

	// Reset all our base query vars and run an AJAX call
	function badgeos_ajax_earned_achievement_list_reset($parentdiv) {

		$parentdiv.find('#badgeos_achievements_offset').val(0);
		$parentdiv.find('#badgeos_achievements_count').val(0);

		$parentdiv.find('div#badgeos-earned-achievements-container').html('');
		$parentdiv.find('.achievements_list_load_more').hide();
		show_earned_achievement_list_html($parentdiv)

	}

	// Reset all our base query vars and run an AJAX call
	function badgeos_ajax_ranks_list_reset($parentdiv) {

		$parentdiv.find('#badgeos_earned_ranks_offset').val(0);
		$parentdiv.find('#badgeos_ranks_count').val(0);

		$parentdiv.find('div#badgeos-earned-ranks-container').html('');
		$parentdiv.find('.earned_ranks_list_load_more').hide();
		show_earned_rank_list_html($parentdiv)

	}

	// Reset all our base query vars and run an AJAX call
	function badgeos_ajax_achievement_list_reset($parentdiv) {

		$parentdiv.find('#badgeos_achievements_offset').val(0);
		$parentdiv.find('#badgeos_achievements_count').val(0);

		$parentdiv.find('div#badgeos-achievements-container').html('');
		$parentdiv.find('.achievements_list_load_more').hide();
		show_list_html($parentdiv)

	}

	// Listen for changes to the achievement filter
	$('.achievements_list_filter').change(function () {
		var $div = $(this).parents('div[class^="badgeos_achievement_main_container"]').eq(0);
		badgeos_ajax_achievement_list_reset($div);

	}).change();

	// Listen for search queries
	$('.earned_ranks_list_search_go').click(function (event) {

		event.preventDefault();

		var $div = $(this).parents('div[class^="badgeos_earned_rank_main_container"]').eq(0);

		badgeos_ajax_ranks_list_reset($div);

		//Disabled submit button
		//$div.find('.earned_ranks_list_search_go').attr('disabled','disabled');
	});

	// Listen for search queries
	$('.achievements_list_search_go_form').submit(function (event) {

		event.preventDefault();

		var $div = $(this).parents('div[class^="badgeos_achievement_main_container"]').eq(0);

		badgeos_ajax_achievement_list_reset($div);

		//Disabled submit button
		$div.find('.achievements_list_search_go').attr('disabled', 'disabled');
	});

	// Listen for search queries
	$('.earned_achievements_list_search_go').click(function (event) {
		event.preventDefault();

		var $div = $(this).parents('div[class^="badgeos_earned_achievement_main_container"]').eq(0);

		badgeos_ajax_earned_achievement_list_reset($div);

		//Disabled submit button
		//$div.find('.earned_achievements_list_search_go').attr('disabled','disabled');
	});

	//Enabled submit button
	$('.achievements_list_search').focus(function (e) {

		$(this).removeAttr('disabled');
		$('.achievements_list_search_go').removeAttr('disabled');

	});

	// Listen for users clicking the "Load More" button
	$('.achievements_list_load_more').click(function () {
		var $loadmoreparent = $(this).parents('div[class^="badgeos_achievement_main_container"]').eq(0);
		$loadmoreparent.find('.badgeos-spinner').show();
		show_list_html($loadmoreparent);

	});

	// Listen for users clicking the "Load More" button
	$('.earned_achievements_list_load_more').click(function () {
		var $loadmoreparent = $(this).parents('div[class^="badgeos_earned_achievement_main_container"]').eq(0);
		$loadmoreparent.find('.badgeos-earned-spinner').show();
		show_earned_achievement_list_html($loadmoreparent);

	});

	// Listen for users clicking the "Load More" button
	$('.earned_ranks_list_load_more').click(function () {
		var $loadmoreparent = $(this).parents('div[class^="badgeos_earned_rank_main_container"]').eq(0);
		$loadmoreparent.find('.badgeos-earned-ranks-spinner').show();
		show_earned_rank_list_html($loadmoreparent);
	});

	// Listen for users clicking the show/hide details link
	$('#badgeos-achievements-container,.badgeos-single-achievement').on('click', '.badgeos-open-close-switch a', function (event) {

		event.preventDefault();

		var link = $(this);

		if ('close' == link.data('action')) {
			link.parent().siblings('.badgeos-extras-window').slideUp(300);
			link.data('action', 'open').prop('class', 'show-hide-open').text('Show Details');
		}
		else {
			link.parent().siblings('.badgeos-extras-window').slideDown(300);
			link.data('action', 'close').prop('class', 'show-hide-close').text('Hide Details');
		}

	});

	// Credly popup functionality
	if ('undefined' != typeof BadgeosCredlyData) {
		// credly markup
		var credly = '<div class="credly-share-popup" style="display: none;"><div class="credly-wrap"><span>'
			+ BadgeosCredlyData.message + '</span><div class="badgeos-spinner"></div>'
			+ '<span class="credly-message" style="display: none;"><strong class="error">'
			+ BadgeosCredlyData.errormessage + '</strong></span>'
			+ '<input type="submit" class="credly-button credly-send" value="' + BadgeosCredlyData.confirm + '"/>'
			+ '<a class="credly-button credly-cancel" href="#">' + BadgeosCredlyData.cancel + '</a>'
			+ '<div style="clear: both;"></div></div><div style="clear: both;"></div></div>';

		// add credly popup to dom
		$body.append(credly);
		credly = $('.credly-share-popup');

		var credlyMessage = $('.credly-message');
		var credlySpinner = $('.badgeos-spinner');
		var credlySend = $('.credly-button.credly-send');
		var spinnerTimer;

		// make a function so we can call it from our ajax success functions
		function credlyize() {

			$('.share-credly.addCredly').append('<a class="credly-share" style="display: none;" href="#" title="'
				+ BadgeosCredlyData.share + '"></a>').removeClass('addCredly');

		}

		credlyize();

		// add credly share button to achievements in widget
		$body.on('click', '.credly-share', function (event) {

			event.preventDefault();

			credlyHide();
			var el = $(this);
			var parent = el.parent();
			var width = parent.outerWidth();
			var elPosition = el.offset();
			var id = parent.data('credlyid');

			// move credly popup
			credly.width(width).css({
				top: Math.floor(elPosition.top - credly.outerHeight()), left: Math.floor(elPosition.left)
			}).show().data('credlyid', id);

		});

		$body.on('click', '.credly-button.credly-cancel', function (event) {

			// Cancel Credly popup
			event.preventDefault();

			credlyHide();

		}).on('click', '.credly-button.credly-send', function (event) {

			// Our Credly button handler
			event.preventDefault();

			credlySend.hide();
			credlySpinner.show();

			badgeos_send_to_credly(credly.data('credlyid'));

		});

		// reset hidden credly elements
		function credlyHide() {

			clearTimeout(spinnerTimer);

			credly.hide();
			credlySend.show();
			credlySpinner.hide();

			credlyMessage.hide().css({ opacity: 1.0 }).html(BadgeosCredlyData.errormessage);

		}

		// Our Credly AJAX call
		function badgeos_send_to_credly(ID) {

			$.ajax({
				type: 'post',
				dataType: 'json',
				url: BadgeosCredlyData.ajax_url,
				data: {
					'action': 'achievement_send_to_credly',
					'ID': ID
				},
				success: function (response) {

					// hide our loading spinner
					credlySpinner.hide();

					// show our 'saved' notification briefly
					credlyMessage.fadeTo('fast', 1).animate({ opacity: 1.0 }, 2000).fadeTo('slow', 0).html(response);

					setTimeout(function () {

						credlyHide();

					}, 2200);

				},
				error: function (x, t, m) {

					if (t === 'timeout') {
						credlySpinner.hide();
						credlyMessage.show();

						setTimeout(function () {

							credlyMessage.animate({ opacity: 0 }, 500);

							setTimeout(function () {

								credlyMessage.hide().css({ opacity: 1.0 });
								credlySend.show();

							}, 600);

						}, 1100);
					}
					else {
						// hide our loading spinner
						credlySpinner.hide();

						// show our 'saved' notification briefly
						var html = '<strong class="error">' + BadgeosCredlyData.localized_error + ' ' + t + '</strong>';

						credlyMessage.fadeTo('fast', 1).animate({ opacity: 1.0 }, 2000).fadeTo('slow', 0).html(html);

						setTimeout(function () {

							credlyHide();

						}, 2200);
					}

				}

			});

		}
	}

	badgeos_ajax_earned_achievement_list();
	badgeos_ajax_earned_ranks_list();

	$(document).on('click', '.bos_ob_convert_to_ob_btn', function (e) {
		e.preventDefault();
		var button = $(this);
		var entry_id = button.val();

		return $.ajax({
			url: BadgeosCredlyData.ajax_url,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'bos_ob_convert_to_open_badge',
				entry_id: entry_id
			},
			beforeSend: function (response) {
				button.attr('disabled', true).find('.bos_ob_btn_fa').show();
			},
			success: function (response) {
				if (response.status == 'success') {
					button.text(response.message).delay(200).fadeOut();
				}
			},
			error: function (error) {

			},
			complete: function () {
				button.attr('disabled', false).find('.bos_ob_btn_fa').hide();
			}
		});
	});

	$('#open_badge_enable_baking').change(function () {
		if ('0' == $(this).val())
			$('#open-badge-setting-section').hide();
		else
			$('#open-badge-setting-section').show();
	}).change();


	function badgeos_ob_verification_process($this) {
		$('#badgeos-ob-verification-res-list').html('');

		var achievement_id = $($this).data('bg');
		var entry_id = $($this).data('eid');
		var user_id = $($this).data('uid');

		tb_show('Verification', '#TB_inline?width=250&height=200&inlineId=badgeos-open-badge-verification-popup-box');
		$.ajax({
			url: BadgeosCredlyData.ajax_url,
			type: 'POST',
			data: {
				action: 'badgeos_validate_open_badge',
				bg: achievement_id,
				eid: entry_id,
				uid: user_id,
			},
			dataType: 'json',
			success: function (returndata1) {
				if (returndata1.type == 'success')
					$('#badgeos-ob-verification-res-list').html('<li class="success">' + returndata1.message + '</li>');
				else
					$('#badgeos-ob-verification-res-list').html('<li class="error">' + returndata1.message + '</li>');
				$.ajax({
					url: BadgeosCredlyData.ajax_url,
					type: 'POST',
					data: {
						action: 'badgeos_validate_revoked',
						bg: achievement_id,
						eid: entry_id,
						uid: user_id,
					},
					dataType: 'json',
					success: function (returndata2) {

						if (returndata2.type == 'success')
							$('#badgeos-ob-verification-res-list').append('<li class="success">' + returndata2.message + '</li>');
						else
							$('#badgeos-ob-verification-res-list').append('<li class="error">' + returndata2.message + '</li>');
						$.ajax({
							url: BadgeosCredlyData.ajax_url,
							type: 'POST',
							data: {
								action: 'badgeos_validate_expiry',
								bg: achievement_id,
								eid: entry_id,
								uid: user_id,
							},
							dataType: 'json',
							success: function (returndata3) {
								if (returndata3.type == 'success')
									$('#badgeos-ob-verification-res-list').append('<li class="success">' + returndata3.message + '</li>');
								else
									$('#badgeos-ob-verification-res-list').append('<li class="error">' + returndata3.message + '</li>');
							}
						});
					}
				});
			}
		});
	}

	$('#open-badgeos-verification').on('click', function () {
		badgeos_ob_verification_process(this);
	});
});
