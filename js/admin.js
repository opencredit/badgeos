jQuery(document).ready(function ($) {
    $('.open_badge_enable_baking_blk_field').change(function () {
        if ($(this).val() == '1') {
            $(this).parents('fieldset').find('.sub_ob_badgeos_blk_fields').css('display', 'block');
        } else {
            $(this).parents('fieldset').find('.sub_ob_badgeos_blk_fields').css('display', 'none');
        }
    }).trigger("change");

    $('#badgeos_tools_email_allow_unsubscribe_email').change(function () {
        if ($(this).val() == 'Yes') {
            $('.badgeos_tools_email_unsubscribe_page_fields').css('display', 'block');
        } else {
            $('.badgeos_tools_email_unsubscribe_page_fields').css('display', 'none');
        }
    }).trigger("change");

    $('.btn_badgeos_download_assets').click(function () {
        var self = $(this);
        self.attr("disabled", true);
        self.parent().find('#btn_badgeos_download_assets_loader').css('visibility', 'visible');
        var assets_id = self.parent().find('.badgeos_assets_id').val();
        var ajaxURL = self.data('ajax_url');

        self.parent().parent().find('.badgeos_download_asset_success_message').css('display', 'none');
        self.parent().parent().find('.badgeos_download_asset_failed_message').css('display', 'none');

        var data = {
            'action': 'badgeos_download_asset',
            'assets_id': assets_id
        };

        jQuery.post(ajaxURL, data, function (response) {
            var popup_id = self.parent().parent().find('.badgeos_template_info_log').attr('id');
            self.parent().find('#btn_badgeos_download_assets_loader').css('visibility', 'hidden');

            if (response == 'done') {
                self.parent().parent().find('.badgeos_download_asset_success_message').css('display', 'block');
            } else {
                self.parent().parent().find('.badgeos_download_asset_failed_message').css('display', 'block').html(response);
            }

            self.attr("disabled", false);
        });

        return false;
    });


    $('.badgeos-profile-points-update-button').click(function () {
        var self = $(this);
        var points_id = self.data('points_id');
        var field_id = self.data('field_id');
        var user_id = self.data('user_id');

        var points = $("#" + field_id).val();
        var ajaxURL = self.data('admin_ajax');

        var data = {
            'action': 'badgeos_user_profile_update_points',
            'points_id': points_id,
            'points': points,
            'user_id': user_id
        };

        jQuery.post(ajaxURL, data, function (response) {
            if (response.success) {
                console.log(response.data.new_points);
                $("#" + field_id + '-profile-label').html(response.data.new_points);
            }

            self.parents('.badgeos-credits').find('.badgeos-edit-credit-wrapper').hide();
            self.parents('.badgeos-credits').find('.badgeos-earned-credit').show();
        });
    });
    $('.badgeos-profile-points-cancel-button').click(function () {
        var self = $(this);
        self.parents('.badgeos-credits').find('.badgeos-edit-credit-wrapper').hide();
        self.parents('.badgeos-credits').find('.badgeos-earned-credit').show();
    });

    $('#award-achievement, #award-credits, #award-ranks').change(function () {
        if ($('#award-achievement, #award-credits, #award-ranks').is(':checked')) {
            $('#badgeos-award-users').parents('tr').find('th, th label, td, td select, td span').slideUp({ duration: 500 });
        } else {
            $('#badgeos-award-users').parents('tr').find('th, th label, td, td select, td span').slideDown({ duration: 500 });
        }
    });

    $('#revoke-achievement, #revoke-credits, #revoke-ranks').change(function () {
        if ($('#revoke-achievement, #revoke-credits, #revoke-ranks').is(':checked')) {
            $('#badgeos-revoke-users').parents('tr').find('th, th label, td, td select, td span').slideUp({ duration: 500 });
        } else {
            $('#badgeos-revoke-users').parents('tr').find('th, th label, td, td select, td span').slideDown({ duration: 500 });
        }
    });

    /**
	 * For user profile page
     * Revoke Ranks
     */
    $('body').on('click', 'table.badgeos-rank-table .revoke-rank', function () {
        var self = $(this);

        self.siblings('.spinner-loader').find('.revoke-rank-spinner').show();

        var rankID = self.attr('data-rank_id');
        var userID = self.attr('data-user_id');
        var ajaxURL = self.attr('data-admin_ajax');

        var data = {
            'action': 'user_profile_revoke_rank',
            'rankID': rankID,
            'userID': userID,
        };

        jQuery.post(ajaxURL, data, function (response) {

            self.siblings('.spinner-loader').find('.revoke-rank-spinner').hide();

            if ('true' == response) {

                self.parents('tr').find('td').slideUp(800, function () {
                    self.parents('tr').remove();

                    if (!$('table.badgeos-rank-revoke-table > tbody tr').length) {
                        $('table.badgeos-rank-revoke-table > tbody').append(
                            '<tr class="no-awarded-rank">' +
                            '<td colspan="5">' +
                            '<span class="description">' + admin_js.no_awarded_rank + '</span>' +
                            '</td> </tr>'
                        );
                    }
                });
            } else if ('false' == response) {
                self.siblings('.spinner-loader').append($('Try Again'));
            }
        });
    });

    /**
     * For Profile Page
     * Display Ranks to award
     */
    $('body').on('click', '.user-profile-award-ranks .display-ranks-to-award', function () {
        var self = $(this);

        self.parents('.user-profile-award-ranks').find('.revoke-rank-spinner').show();
        var ajaxURL = self.attr('data-admin_ajax');
        var userID = self.attr('data-user-id');
        var rankType = self.attr('data-rank-filter');

        var data = {
            'action': 'user_profile_display_award_list',
            'user_id': userID,
            'rank_filter': rankType
        };

        jQuery.post(ajaxURL, data, function (response) {
            self.parents('.user-profile-award-ranks').find('.revoke-rank-spinner').hide();

            $('.user-profile-award-ranks .badgeos-rank-table-to-award').remove();
            self.parents('.user-profile-award-ranks').append($(response));

        });
    });

    /**
     * For Profile Page
     * Award rank
     */
    $('body').on('click', '.user-profile-award-ranks .badgeos-rank-table-to-award .award-rank', function () {
        var self = $(this);

        self.siblings('.spinner-loader').find('.award-rank-spinner').show();
        var ajaxURL = self.attr('data-admin-ajax');
        var userID = self.attr('data-user-id');
        var rankID = self.attr('data-rank-id');

        var data = {
            'action': 'user_profile_award_rank',
            'user_id': userID,
            'rank_id': rankID
        };

        jQuery.post(ajaxURL, data, function (response) {
            self.siblings('.spinner-loader').find('.award-rank-spinner').hide();

            if ('true' == response) {
                var rankID = self.attr('data-rank-id');
                var userID = self.attr('data-user-id');
                var ajaxURL = self.attr('data-admin-ajax');
                var rankType = self.parents('tr').attr('class');
                var defaultRank = self.parents('tr').attr('id');
                var cloned = self.parents('tr').clone(true);

                var replaceWidth = '<td class="last-awarded" align="center"><span class="profile_ranks_last_award_field">&#10003;</span></td><td><span data-rank_id="' + rankID + '" data-user_id="' + userID + '" data-admin_ajax="' + ajaxURL + '" class="revoke-rank">' + admin_js.revoke_rank + '</span><span class="spinner-loader" ><img class="award-rank-spinner" src="' + admin_js.loading_img + '" style="margin-left: 10px; display: none;" /></span></td>';

                if ('default-rank' == defaultRank) {
                    replaceWidth = '<td class="last-awarded" align="center"><span class="profile_ranks_last_award_field">&#10003;</span></td><td><span class="default-rank">' + admin_js.default_rank + '</span></td>';
                }
                $('td.award-rank-column', cloned).replaceWith(replaceWidth);

                /**
                 * Remove last awarded from siblings
                 */
                $.each($('.badgeos-rank-revoke-table > tbody > tr'), function (index, value) {
                    if ($(value).hasClass(rankType)) {
                        $(value).find('td.last-awarded').empty();
                    }
                });

                $('.badgeos-rank-revoke-table > tbody').append(cloned);
                $('.badgeos-rank-revoke-table > tbody > tr.no-awarded-rank td, .badgeos-rank-revoke-table > tbody > tr.no-awarded-rank span').slideUp(800, function () { $('.badgeos-rank-revoke-table > tbody > tr.no-awarded-rank').remove() });

                self.parents('tr').find('td').slideUp(800, function () { self.parents('tr').remove() });
            } else if ('false' == response) {
                self.siblings('.spinner-loader').append($('Try Again'));
            }

            $('.user-profile-award-ranks .badgeos-rank-table-to-award').remove();
            self.parents('.user-profile-award-ranks').append($(response));
        });
    });

    $('.badgeos-credits .badgeos-credit-edit').click(function () {

        var self = $(this);
        self.siblings('.badgeos-edit-credit-wrapper').show();
        self.siblings('.badgeos-earned-credit').hide();
    });

    $('#badgeos_ach_check_all').click(function (event) {

        //event.preventDefault();
        $('input[name="badgeos_ach_check_indis[]"]').not(this).prop('checked', this.checked);
    });
    $('#badgeos_btn_revoke_bulk_achievements').click(function (event) {

        var self = $(this);
        var chkArray = [];
        var badgeos_user_id = $('#badgeos_user_id').val();
        $('input[name="badgeos_ach_check_indis[]"]:checked').each(function () {
            chkArray.push($(this).val());
        });
        var data = {
            action: 'delete_badgeos_bulk_achievements', achievements: chkArray, user_id: badgeos_user_id
        };

        self.siblings('#revoke-badges-loader').show();
        $.post(admin_js.ajax_url, data, function (response) {
            self.siblings('#revoke-badges-loader').hide();
            if (response == 'success') {
                window.location.reload();
            } else {
                $('#wpbody-content .wrap').prepend('<div class="notice notice-warning is-dismissible"><p>' + response + '</p></div>');
            }
        });
        console.log(chkArray);
    });
    function click_to_dismiss(div_id) {
        $("#".div_id).remove();
    }

    // Dynamically show/hide achievement meta inputs based on "Award By" selection
    $("#_badgeos_earned_by").change(function () {

        // Define our potentially unnecessary inputs
        var badgeos_sequential = $('#_badgeos_sequential').parent().parent();
        var badgeos_points_required = $('#_badgeos_points_required_badgeos_points_required').parent().parent().parent();

        // // Hide our potentially unnecessary inputs
        badgeos_sequential.hide();
        badgeos_points_required.hide();

        // Determine which inputs we should show
        if ('triggers' == $(this).val())
            badgeos_sequential.show();
        else if ('points' == $(this).val())
            badgeos_points_required.show();

    }).change();

    // Throw a warning on Achievement Type editor if title is > 20 characters
    $('#titlewrap').on('keyup', 'input[name=post_title]', function () {

        // Make sure we're editing an achievement type
        if (admin_js.achievement_post_type == $('#post_type').val()) {
            // Cache the title input selector
            var $title = $(this);
            if ($title.val().length > 20) {
                // Set input to look like danger
                $title.css({ 'background': '#faa', 'color': '#a00', 'border-color': '#a55' });

                // Output a custom warning (and delete any existing version of that warning)
                $('#title-warning').remove();
                $title.parent().append('<p id="title-warning">Achievement Type supports a maximum of 20 characters. Please choose a shorter title.</p>');
            } else {
                // Set the input to standard style, hide our custom warning
                $title.css({ 'background': '#fff', 'color': '#333', 'border-color': '#DFDFDF' });
                $('#title-warning').remove();
            }
        }
    });

    $('#delete_log_entries').click(function () {
        var confirmation = confirm('It will delete all the log entries');
        if (confirmation) {
            var data = {
                'action': 'delete_badgeos_log_entries'
            };
            $.post(admin_js.ajax_url, data, function (response) {
                $('#wpbody-content .wrap').prepend('<div class="notice notice-warning delete-log-entries"><p><img src="' + admin_js.loading_img + '" /> &nbsp;&nbsp;BadgeOS is deleting log entries as background process, you can continue exploring badgeos</p></div>');

                setTimeout(function () {
                    $('#wpbody-content .wrap .delete-log-entries').slideUp();
                }, 10000);
            });
        }
    });

    $('#badgeos_migrate_meta_to_db').click(function () {
        var confirmation = confirm("It will update the users' existing achievements and points in the badgeos table.");
        if (confirmation) {
            var data = {
                'action': 'badgeos_migrate_data_from_meta_to_db'
            };
            $.post(admin_js.ajax_url, data, function (response) {
                $('.badgeos_migrate_meta_to_db_message').html('<div class="notice notice-warning delete-log-entries"><p><img src="' + admin_js.loading_img + '" /> &nbsp;&nbsp;BadgeOS is shifting data as a background process. You will receive a confirmation email upon successful completion. You can continue exploring badgeos.</p></div>');
            });
        }
    });

    $('#badgeos_migrate_fields_single_to_multi').click(function () {
        var confirmation = confirm('It will update the existing achievement points with the point types.');
        if (confirmation) {
            var data = {
                'action': 'badgeos_migrate_fields_points_to_point_types'
            };
            $.post(admin_js.ajax_url, data, function (response) {
                $('.badgeos_migrate_fields_single_to_multi_message').html('<div class="notice notice-warning migrage-point-fields-entries"><p>' + response + '</p></div>').slideDown();

                /*setTimeout( function() {
                    $( '.badgeos_migrate_fields_single_to_multi_message' ).slideUp();
                }, 3000 );*/
            });
        }
    });

    $('#badgeos_notice_update_from_meta_to_db').click(function () {
        var confirmation = confirm("It will update the users' existing achievements and points in the badgeos table");
        if (confirmation) {
            var data = {
                'action': 'badgeos_migrate_data_from_meta_to_db_notice'
            };
            $.post(admin_js.ajax_url, data, function (response) {
                $('#wpbody-content .wrap').prepend('<div class="notice notice-warning"><p><img src="' + admin_js.loading_img + '" /> &nbsp;&nbsp;BadgeOS is shifting data as a background process. You will receive a confirmation email upon successful completion. You can continue exploring badgeos.</p></div>');

                setTimeout(function () {
                    $('#wpbody-content .wrap .migrate-meta-to-db').slideUp();
                }, 1000);
            });
        }
    });

    $('#badgeos_date_of_birth').datepicker({
        dateFormat: "yy-mm-dd",
        changeMonth: true,
        changeYear: true,
    });

    // Uploading files
    var file_frame;

    // not earned image js
    $.fn.upload_listing_image = function (button) {
        var button_id = button.attr('id');
        var field_id = button_id.replace('_button', '');

        // If the media frame already exists, reopen it.
        if (file_frame) {
            file_frame.open();
            return;
        }

        // Create the media frame.
        file_frame = wp.media.frames.file_frame = wp.media({
            title: $(this).data('uploader_title'),
            button: {
                text: $(this).data('uploader_button_text'),
            },
            multiple: false
        });

        // When an image is selected, run a callback.
        file_frame.on('select', function () {
            var attachment = file_frame.state().get('selection').first().toJSON();
            $('#' + field_id).val(attachment.id);
            $('#not_earned_image_div img').attr('src', attachment.url);
            $('#not_earned_image_div img').show();
            $('#' + button_id).attr('id', 'remove_not_earned_image_button');
            $('#remove_not_earned_image_button').text('Remove image');
        });

        // Finally, open the modal
        file_frame.open();
    };

    $('#not_earned_image_div').on('click', '#upload_not_earned_image_button', function (event) {
        event.preventDefault();
        $.fn.upload_listing_image($(this));
    });

    $('#not_earned_image_div').on('click', '#remove_not_earned_image_button', function (event) {
        event.preventDefault();
        $('#upload_not_earned_image').val('');
        $('#not_earned_image_div img').attr('src', '');
        $('#not_earned_image_div img').attr('srcset', '');
        $('#not_earned_image_div img').hide();
        $(this).attr('id', 'upload_not_earned_image_button');
        $('#upload_not_earned_image_button').text('Set image');
    });
});