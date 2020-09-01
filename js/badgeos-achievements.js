jQuery(function ($) {

	var $body = $('body');

	function show_ranks_list_html($mainobj) {

		var data_ajaxurl = $mainobj.attr("data-url");
		var types = $mainobj.attr("data-types");
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
		$mainobj.find('div.badgeos-rank-lists-spinner').show();

		$.ajax({
			url: data_ajaxurl,
			data: {
				'action': 'get-ranks-list',
				'types': types,
				'limit': data_limit,
				'user_id': data_user_id,
				'offset': $mainobj.find('#badgeos_rank_lists_offset').val(),
				'count': $mainobj.find('#badgeos_ranks_count').val(),
				'search': $mainobj.find('#rank_lists_list_search').val(),
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
				$mainobj.find('div.badgeos-rank-lists-spinner').hide();
				if (response.data.message === null) {
					//alert("That's all folks!");
				} else {
					var offset_val = $mainobj.find('#badgeos_rank_lists_offset').val();
					if (parseInt(offset_val) > 0) {
						$mainobj.find('div#badgeos-list-ranks-container .ls_grid_container').append(response.data.message);
					}
					else
						$mainobj.find('div#badgeos-list-ranks-container').append(response.data.message);

					$mainobj.find('#badgeos_rank_lists_offset').val(response.data.offset);
					$mainobj.find('#badgeos_ranks_count').val(response.data.badge_count);

					//hide/show load more button
					if (response.data.query_count <= response.data.offset) {
						$mainobj.find('.rank_lists_list_load_more').hide();

					} else {
						$mainobj.find('.rank_lists_list_load_more').show();
					}

					$('.badgeos-arrange-buttons button').on('click', function () {
						$('.badgeos-arrange-buttons button').removeClass('selected');
						$(this).addClass('selected');
						if ($(this).hasClass('grid')) {
							$('#badgeos-list-ranks-container ul').removeClass('list').addClass('grid');
						}
						else if ($(this).hasClass('list')) {
							$('#badgeos-list-ranks-container ul').removeClass('grid').addClass('list');
						}
					});
				}
			}
		});
	}

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
	function badgeos_ajax_ranks_list() {
		$('.badgeos_rank_lists_offset').val('0');
		$(".badgeos_ranks_list_main_container").each(function (index) {
			var $mainobj = $(this);
			show_ranks_list_html($mainobj)
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

	// Reset all our base query vars and run an AJAX call
	function badgeos_ajax_rank_lists_shortcode_reset($parentdiv) {

		$parentdiv.find('#badgeos_rank_lists_offset').val(0);
		$parentdiv.find('#badgeos_ranks_count').val(0);

		$parentdiv.find('div#badgeos-list-ranks-container').html('');
		$parentdiv.find('.rank_lists_list_load_more').hide();
		show_ranks_list_html($parentdiv)
	}

	// Listen for search queries
	$('.rank_lists_list_search_go_form').submit(function (event) {

		event.preventDefault();

		var $div = $(this).parents('div[class^="badgeos_ranks_list_main_container"]').eq(0);

		badgeos_ajax_rank_lists_shortcode_reset($div);

		//Disabled submit button
		//$div.find('.rank_lists_list_search_go').attr('disabled', false );
	});

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

	// Listen for users clicking the "Load More" button
	$('.rank_lists_list_load_more').click(function () {
		var $loadmoreparent = $(this).parents('div[class^="badgeos_ranks_list_main_container"]').eq(0);
		$loadmoreparent.find('.badgeos-rank-lists-spinner').show();
		show_ranks_list_html($loadmoreparent);
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
	badgeos_ajax_ranks_list();

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

	$('.badgeos_verification_close').click(function () {
		$(".badgeos_verification_modal_popup").fadeToggle();
		$(".badgeos_verification_modal_popup").css({ "visibility": "hidden", "display": "none" });
	});
	function badgeos_ob_verification_process($this) {
		$('#badgeos-ob-verification-res-list').html('');

		var achievement_id = $($this).data('bg');
		var entry_id = $($this).data('eid');
		var user_id = $($this).data('uid');

		$(".badgeos_verification_modal_popup").fadeToggle();
		$(".badgeos_verification_modal_popup").css({ "visibility": "visible", "display": "block" });

		var return_result = 0;
		$.ajax({
			url: BadgeosCredlyData.ajax_url,
			type: 'POST',
			data: {
				action: 'badgeos_validate_open_badge',
				bg: achievement_id,
				eid: entry_id,
				uid: user_id,
				type: 'issued_on'
			},
			dataType: 'json',
			success: function (returndata1) {
				if (returndata1.type == 'success' && parseInt(returndata1.result) > 0)
					$('.badgeos_verification_modal_panel').html('<div class="badgeos_modal_badge"><div class="badgeos_verification_checkbox"><i class="fas fa-check"></i></div><div class="badgeos_verification_badge_title"><span>' + returndata1.message + '</span></div></div>');
				else
					$('.badgeos_verification_modal_panel').html('<div class="badgeos_modal_badge"><div class="badgeos_verification_checkbox"><i class="fas fa-times"></i></div><div class="badgeos_verification_badge_title"><span>' + returndata1.message + '</span></div></div>');

				return_result += parseInt(returndata1.result);
				$.ajax({
					url: BadgeosCredlyData.ajax_url,
					type: 'POST',
					data: {
						action: 'badgeos_validate_open_badge',
						bg: achievement_id,
						eid: entry_id,
						uid: user_id,
						type: 'issued_by'
					},
					dataType: 'json',
					success: function (returndata2) {

						if (returndata2.type == 'success' && parseInt(returndata2.result) > 0)
							$('.badgeos_verification_modal_panel').append('<div class="badgeos_modal_badge"><div class="badgeos_verification_checkbox"><i class="fas fa-check"></i></div><div class="badgeos_verification_badge_title"><span>' + returndata2.message + '</span></div></div>');
						else
							$('.badgeos_verification_modal_panel').append('<div class="badgeos_modal_badge"><div class="badgeos_verification_checkbox"><i class="fas fa-times"></i></div><div class="badgeos_verification_badge_title"><span>' + returndata2.message + '</span></div></div>');

						return_result += parseInt(returndata2.result);

						$.ajax({
							url: BadgeosCredlyData.ajax_url,
							type: 'POST',
							data: {
								action: 'badgeos_validate_open_badge',
								bg: achievement_id,
								eid: entry_id,
								uid: user_id,
								type: 'issued_using'
							},
							dataType: 'json',
							success: function (returndata3) {

								if (returndata3.type == 'success' && parseInt(returndata3.result) > 0)
									$('.badgeos_verification_modal_panel').append('<div class="badgeos_modal_badge"><div class="badgeos_verification_checkbox"><i class="fas fa-check"></i></div><div class="badgeos_verification_badge_title"><span>' + returndata3.message + '</span></div></div>');
								else
									$('.badgeos_verification_modal_panel').append('<div class="badgeos_modal_badge"><div class="badgeos_verification_checkbox"><i class="fas fa-times"></i></div><div class="badgeos_verification_badge_title"><span>' + returndata3.message + '</span></div></div>');

								return_result += parseInt(returndata3.result);
								$.ajax({
									url: BadgeosCredlyData.ajax_url,
									type: 'POST',
									data: {
										action: 'badgeos_validate_open_badge',
										bg: achievement_id,
										eid: entry_id,
										uid: user_id,
										type: 'issued_to'
									},
									dataType: 'json',
									success: function (returndata4) {

										if (returndata4.type == 'success' && parseInt(returndata4.result) > 0)
											$('.badgeos_verification_modal_panel').append('<div class="badgeos_modal_badge"><div class="badgeos_verification_checkbox"><i class="fas fa-check"></i></div><div class="badgeos_verification_badge_title"><span>' + returndata4.message + '</span></div></div>');
										else
											$('.badgeos_verification_modal_panel').append('<div class="badgeos_modal_badge"><div class="badgeos_verification_checkbox"><i class="fas fa-times"></i></div><div class="badgeos_verification_badge_title"><span>' + returndata4.message + '</span></div></div>');

										return_result += parseInt(returndata4.result);
										$.ajax({
											url: BadgeosCredlyData.ajax_url,
											type: 'POST',
											data: {
												action: 'badgeos_validate_open_badge',
												bg: achievement_id,
												eid: entry_id,
												uid: user_id,
												type: 'expiry_date'
											},
											dataType: 'json',
											success: function (returndata5) {
												if (returndata5.type == 'success' && parseInt(returndata5.result) > 0)
													$('.badgeos_verification_modal_panel').append('<div class="badgeos_modal_badge"><div class="badgeos_verification_checkbox"><i class="fas fa-check"></i></div><div class="badgeos_verification_badge_title"><span>' + returndata5.message + '</span></div></div>');
												else
													$('.badgeos_verification_modal_panel').append('<div class="badgeos_modal_badge"><div class="badgeos_verification_checkbox"><i class="fas fa-times"></i></div><div class="badgeos_verification_badge_title"><span>' + returndata5.message + '</span></div></div>');

												return_result += parseInt(returndata5.result);

												if (return_result < 5) {
													$('.badgeos_verification_modal_panel').append('<div class="badgeos_modal_badge"><div class="badgeos_verification_checkbox"><i class="fas fa-times"></i></div><div class="badgeos_verification_badge_title"><span class="badgeos_verified">' + returndata5.notverified_label + '</span></div></div>');
												} else {
													$('.badgeos_verification_modal_panel').append('<div class="badgeos_modal_badge"><div class="badgeos_verification_checkbox"><i class="fas fa-check"></i></div><div class="badgeos_verification_badge_title"><span class="badgeos_verified">' + returndata5.verified_label + '</span></div></div>');
												}
											}
										});
									}
								});
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
