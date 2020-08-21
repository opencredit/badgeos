jQuery(document).ready(function ($) {

    /**
     * For Tool Page
     */
    $('select#achievement_types_to_award, select#badgeos-award-users, select#achievement_types_to_revoke, select#badgeos-revoke-users, select#rank_types_to_award, select#rank_types_to_revoke').select2({ width: 'resolve', minimumInputLength: 0 });

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

    $('#badgeos_tools_disable_earned_achievement_email, .badgeos_tools_disable_email_checkboxes').change(function () {
        if ($(this).is(':checked')) {
            $(this).parents('table').find('.badgeos_tools_email_achievement_field').parents('tr').find('th, th label, td, td select, td span').slideUp({ duration: 500 });
        } else {
            $(this).parents('table').find('.badgeos_tools_email_achievement_field').parents('tr').find('th, th label, td, td select, td span').slideDown({ duration: 500 });
        }
    }).trigger('change');

    $(function () {

        $("#achievement-tabs, #credit-tabs, #rank-tabs, #email-tabs, #system-tabs, #badgeos-setting-tabs").tabs().addClass("ui-tabs-vertical ui-helper-clearfix");
        $("#achievement-tabs li, #credit-tabs li, #rank-tabs li, #email-tabs li, #system-tabs li, #badgeos-setting-tabs li").removeClass("ui-corner-top").addClass("ui-corner-left");
        if (admin_js.badgeos_tools_email_tab != '') {
            $('#' + admin_js.badgeos_tools_email_tab + '_link').click();
        }

        var active_site_tab = $('#badgeos_admin_side_tab').val();
        if (active_site_tab != '') {
            $('.badgeos_sidebar_tab_links').find('a[href="' + active_site_tab + '"]').click();
        }

        if ($('.badgeos_mini_color_picker_ctrl').length > 0) {
            $('.badgeos_mini_color_picker_ctrl').each(function () {
                $(this).minicolors({
                    control: $(this).attr('data-control') || 'hue',
                    defaultValue: $(this).attr('data-defaultValue') || '',
                    format: $(this).attr('data-format') || 'hex',
                    keywords: $(this).attr('data-keywords') || '',
                    inline: $(this).attr('data-inline') === 'true',
                    letterCase: $(this).attr('data-letterCase') || 'lowercase',
                    opacity: $(this).attr('data-opacity'),
                    position: $(this).attr('data-position') || 'bottom',
                    swatches: $(this).attr('data-swatches') ? $(this).attr('data-swatches').split('|') : [],
                    change: function (value, opacity) {
                        if (!value) return;
                        if (opacity) value += ', ' + opacity;
                        if (typeof console === 'object') {
                            console.log(value);
                        }
                    },
                    theme: 'bootstrap'
                });
            });
        }
    });

    $('.badgeos_sidebar_tab_links a').on('click', function () {
        $('#badgeos_admin_side_tab').val($(this).attr('href'));
    });
});