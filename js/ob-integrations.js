jQuery(document).ready(function ($) {

    if ($('#open_badge_conversion_in_progress').length) {
        $('#open_badge_conversion_in_progress').addClass('notice notice-warning');
    }

    if ($('#no-non-ob').length) {
        $('#no-non-ob').addClass('notice notice-warning');
    }

    $('#convert_non_open_achievements').click(function (e) {
        e.preventDefault();

        $.ajax({
            url: admin_js.ajax_url,
            type: 'POST',
            data: {
                action: 'convert_badges_to_open_standards'
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    $('#non_ob_button_message').slideUp();
                    $('#open_badge_conversion_in_progress').slideDown();
                }
            }
        });
    });

    // Show badge sharing options only if "Enable Badge Baking" is enabled
    $('#open_badge_enable_baking').change(function () {
        if ('0' == $(this).val())
            $('#open-badge-setting-section').hide();
        else
            $('#open-badge-setting-section').show();
    }).change();

    $('.badgeos_share_popup').on('click', function () {
        var eid = $(this).data('eid');
        $(".open_badge_share_box_id").hide();
        $("#open_badge_share_box_id" + eid).slideDown("slow");

    });
    $('.open_badge_share_box_id .close').on('click', function () {
        $(".open_badge_share_box_id").hide();
    });

    $('#open-badgeos-verification').on('click', function () {

        $('#verification-res-list').html('');

        var achievement_id = $(this).data('bg');

        var entry_id = $(this).data('eid');
        var user_id = $(this).data('uid');

        tb_show('Verification', '#TB_inline?width=200&height=150&inlineId=open-badge-id');
        $.ajax({
            url: admin_js.ajax_url,
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
                    $('#verification-res-list').html('<li class="success">' + returndata1.message + '</li>');
                else
                    $('#verification-res-list').html('<li class="error">' + returndata1.message + '</li>');
                $.ajax({
                    url: admin_js.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'badgeos_validate_revoked',
                        bg: achievement_id,
                        eid: entry_id,
                        uid: user_id,
                    },
                    dataType: 'json',
                    success: function (returndata2) {
                        console.log(returndata2);
                        if (returndata2.type == 'success')
                            $('#verification-res-list').append('<li class="success">' + returndata2.message + '</li>');
                        else
                            $('#verification-res-list').append('<li class="error">' + returndata2.message + '</li>');
                        $.ajax({
                            url: admin_js.ajax_url,
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
                                    $('#verification-res-list').append('<li class="success">' + returndata3.message + '</li>');
                                else
                                    $('#verification-res-list').append('<li class="error">' + returndata3.message + '</li>');
                            }
                        });
                    }
                });
            }
        });

    });
});