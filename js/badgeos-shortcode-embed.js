(function ($) {

	function badgeos_insert_shortcode() {

		$('.ui-autocomplete-input').val('');
		var shortcode = badgeos_get_selected_shortcode();
		var attributes = badgeos_get_attributes(shortcode);
		var constructed = badgeos_construct_shortcode(shortcode, attributes);

		window.send_to_editor(constructed);
	}

	function badgeos_get_selected_shortcode() {
		return $('#select_shortcode').val();
	}

	function badgeos_get_attributes(shortcode) {
		var attrs = [];
		var inputs = badgeos_get_shortcode_inputs(shortcode);
		$.each(inputs, function (index, el) {
			if ('' !== el.value && undefined !== el.value) {
				if ($(this).hasClass('select2-hidden-accessible')) {
					var selections = ($(el).select2('data'));
					var terms = [];
					$.each(selections, function (id, text) {
						terms.push(text.id);
					});
					attrs.push(el.name + '="' + terms + '"');
					return attrs;
				} else {
					attrs.push(el.name + '="' + el.value + '"');
					return attrs;
				}

			}
		});
		return attrs;
	}

	function badgeos_get_shortcode_inputs(shortcode) {
		return $('.text, .select', '#' + shortcode + '_wrapper');
	}

	function badgeos_construct_shortcode(shortcode, attributes) {
		var output = '[';
		output += shortcode;

		if (attributes) {
			for (i = 0; i < attributes.length; i++) {
				output += ' ' + attributes[i];
			}

			$.trim(output);
		}
		output += ']';

		return output;
	}

	function badgeos_shortcode_hide_all_sections() {
		$('.shortcode-section').hide();
	}

	function badgeos_shortcode_show_section(section_name) {
		$('#' + section_name + '_wrapper').show();
	}

	// Listen for changes to the selected shortcode
	$('#select_shortcode').on('change', function () {
		badgeos_shortcode_hide_all_sections();
		badgeos_shortcode_show_section(badgeos_get_selected_shortcode());
	}).change();

	// Listen for clicks on the "insert" button
	$('#badgeos_insert').on('click', function (e) {
		e.preventDefault();
		badgeos_insert_shortcode();
	});

	// Listen for clicks on the "cancel" button
	$('#badgeos_cancel').on('click', function (e) {
		e.preventDefault();
		tb_remove();
	});

	// Listen for clicks to open the shortcode picker
	$('#insert_badgeos_shortcodes').on('click', function (e) {
		var inputs = $('.select2-container');
		$.each(inputs, function (index, el) {
			$(el).val('');
		});
	});

	var select2_post_defaults = {
		language: {
			noResults: function () {
				return "No Results";
			},
			errorLoading: function () {
				return "Searching..."
			}
		},
		escapeMarkup: function (markup) {
			return markup;
		},
		placeholder: badgeos_shortcode_embed_messages.id_placeholder,
		allowClear: true
	};

	var select2_post_multiples = $.extend(true, {}, select2_post_defaults, {});
	$('#badgeos_achievement_id,#badgeos_rank_id, #badgeos_nomination_achievement_id, #badgeos_submission_achievement_id, #badgeos_user_earned_points_point_type, #badgeos_evidence_achievement').select2(select2_post_defaults);
	$('#badgeos_achievements_list_include, #badgeos_achievements_list_exclude, #badgeos_user_earned_achievements_include, #badgeos_user_earned_achievements_exclude,#badgeos_user_earned_ranks_rank_type').attr('multiple', true).select2(select2_post_multiples);
	$('#badgeos_achievements_list_type, #badgeos_user_earned_achievements_type').html(badgeos_shortcode_embed_messages.achievements_select_options).attr('multiple', true).select2({
		language: {
			noResults: function () {
				return "No Results";
			},
			errorLoading: function () {
				return "Searching..."
			}
		},
		escapeMarkup: function (markup) {
			return markup;
		},
		placeholder: badgeos_shortcode_embed_messages.id_multiple_placeholder,
		allowClear: true
	});

	$('#badgeos_ranks_list_types').attr('multiple', true).select2({
		language: {
			noResults: function () {
				return "No Results";
			},
			errorLoading: function () {
				return "Searching..."
			}
		},
		escapeMarkup: function (markup) {
			return markup;
		},
		placeholder: badgeos_shortcode_embed_messages.id_multiple_placeholder,
		allowClear: true
	});
	function split(val) {
		return val.split(/,\s*/);
	}
	function extractLast(term) {
		return split(term).pop();
	}

	$('#badgeos_achievements_list_user_id1, #badgeos_user_earned_achievements_user_id1, #badgeos_user_earned_ranks_user_id1, #badgeos_user_earned_points_user_id1, #badgeos_ranks_list_user_id1, #badgeos_evidence_user_id1').autocomplete({
		source: function (request, response) {
			$.getJSON(ajaxurl, {
				q: extractLast(request.term), action: 'badgeos-get-users-list'
			}, response);
		},
		multiselect: false,
		search: function () {
			// custom minLength
			var term = extractLast(this.value);
			if (term.length < 3) {
				return false;
			}
		},
		focus: function () {
			// prevent value inserted on focus
			return false;
		},
		change: function (event, ui) {
			var auto_field = jQuery(this);
			if (auto_field.val() == '') {
				var dep_field = auto_field.data('fieldname');
				var dep_field_type = auto_field.data('type');
				if (dep_field_type == 'autocomplete') {
					jQuery('#' + dep_field).val('');
				}
			}
		},
		select: function (event, ui) {

			var auto_field = jQuery(this);

			var dep_field = auto_field.data('fieldname');
			var dep_field_type = auto_field.data('type');
			if (dep_field_type == 'autocomplete') {

				if (ui.item.value != '')
					jQuery('#' + dep_field).val(ui.item.value);
				else
					jQuery('#' + dep_field).val(ui.item.id);
			}

			this.value = ui.item.label;
			return false;
		}
	});

	$('#badgeos_evidence_award_id1').autocomplete({
		source: function (request, response) {
			$.getJSON(ajaxurl, {
				q: extractLast(request.term), action: 'get-achievements-award-list', achievement_id: $('#badgeos_evidence_achievement').val(), user_id: $('#badgeos_evidence_user_id').val()
			}, response);
		},
		multiselect: false,
		search: function () {
			// custom minLength
			var term = extractLast(this.value);
			if (term.length < 3) {
				return false;
			}
		},
		focus: function () {
			// prevent value inserted on focus
			return false;
		},
		change: function (event, ui) {
			var auto_field = jQuery(this);
			if (auto_field.val() == '') {
				var dep_field = auto_field.data('fieldname');
				var dep_field_type = auto_field.data('type');
				if (dep_field_type == 'autocomplete') {
					jQuery('#' + dep_field).val('');
				}
			}
		},
		select: function (event, ui) {

			var auto_field = jQuery(this);

			var dep_field = auto_field.data('fieldname');
			var dep_field_type = auto_field.data('type');
			if (dep_field_type == 'autocomplete') {

				if (ui.item.value != '')
					jQuery('#' + dep_field).val(ui.item.value);
				else
					jQuery('#' + dep_field).val(ui.item.id);
			}

			this.value = ui.item.label;
			return false;
		}
	});
	// Resize ThickBox when "Add BadgeOS Shortcode" link is clicked
	$('body').on('click', '#insert_badgeos_shortcodes', function (e) {
		e.preventDefault();
		badgeos_shortcode_setup_thickbox($(this));
		$('.ui-autocomplete-multiselect').css('width', '90%');
	});

	// Resize shortcode thickbox on window resize
	$(window).resize(function () {
		badgeos_shortcode_resize_tb($('#insert_badgeos_shortcodes'));
	});

	// Add a custom class to our shortcode thickbox, then resize
	function badgeos_shortcode_setup_thickbox(link) {
		setTimeout(function () {
			$('#TB_window').addClass('badgeos-shortcode-thickbox');
			badgeos_shortcode_resize_tb(link);
		}, 0);
	}

	// Force shortcode thickboxes to our specified width/height
	function badgeos_shortcode_resize_tb(link) {
		setTimeout(function () {

			var width = link.attr('data-width');

			$('.badgeos-shortcode-thickbox').width(width);

			var containerheight = $('.badgeos-shortcode-thickbox').height();

			$('.badgeos-shortcode-thickbox #TB_ajaxContent').width((width - 30)).height((containerheight - 50));
			$('.badgeos-shortcode-thickbox .wrap').width((width - 50)).height((containerheight - 50));


		}, 0);
	}

}(jQuery));
