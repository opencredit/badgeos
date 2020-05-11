jQuery(document).ready(function ($) {

    jQuery( function($) {
        var progressbar = $( "#progressbar" ),
          progressLabel = $( ".progress-label" );
     
        progressbar.progressbar({
          value: false,
          change: function() {
            progressLabel.text( progressbar.progressbar( "value" ) + "%" );
          },
          complete: function() {
            progressLabel.text( "Complete!" );
          }
        }); 
     
        // function progress() {
        //   var val = progressbar.progressbar( "value" ) || 0;
     
        //   progressbar.progressbar( "value", val + 2 );
     
        //   if ( val < 99 ) {
        //     setTimeout( progress, 80 );
        //   }
        // }
     
        // setTimeout( progress, 2000 );
      } );
    


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
    var total_iterations = 0;
    var total_entries = null;
    $('#badgeos_convert_credly_achievements').click(function (e) {
        e.preventDefault();
        
        $('#conversion_credly_to_open_badge_in_progress').css( 'display', 'block' );
        $('#conversion_credly_to_open_badge_no_rec_found').css( 'display', 'none' );
        $('#badgeos_ob_import_processing_box').css("display","none");
        $('#badgeos_ob_normal_progress_bar').css("display","none");
                    
        $.ajax({
            url: admin_js.ajax_url,
            type: 'POST',
            data: {
                action: 'badgeos_get_convertable_credly_achievements_list_count'
            },
            dataType: 'json',
            success: function (response) {
                $('#conversion_credly_to_open_badge_in_progress').css( 'display', 'none' );
                if ( parseInt( response.total_recs ) > 0 ) {
                    $('#badgeos_ob_import_processing_box').css("display","block");
                    $('#badgeos_ob_normal_progress_bar').css("display","block");
                    
                    var progressbar = $( "#progressbar" ),
                    progressLabel = $( ".progress-label" );
                    progressbar.progressbar( "value", 0 );
                    progressbar.progressbar({
                            value: 0,
                            change: function() {
                                progressLabel.text( progressbar.progressbar( "value" ) + "%" );
                            },
                            complete: function() {
                                progressLabel.text( "Complete!" );
                            }
                        });
                    total_iterations = response.total_recs;
                    total_entries = response.recs;
                    var step = parseFloat( 100 / response.total_recs).toFixed(2);
                    process_single_achievement( 0 );

                } else {
                    $('#conversion_credly_to_open_badge_no_rec_found').css( 'display', 'block' ).addClass( 'notice notice-warning' );
                }

                
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) { 
                var progressbar = $( "#progressbar" );
                var val = progressbar.progressbar( "value" ) || 0;
                var remaining = 100 - val;
                if(remaining > step && remaining < (step*2) )
                {
                    for( var i = val; i <=100 ; i++ ) {
                        progressbar.progressbar( "value", i );
                    }
                }
                else
                {
                    var step_total = val + step;
                    for( var i = val; i <=step_total ; i++ ) {
                        progressbar.progressbar( "value", step_total );
                    }
                }
                $('#conversion_credly_to_open_badge_in_progress').css( 'display', 'none' );
            } 
        });
    });

    function process_single_achievement( iter_no ) {
        var progressbar = $( "#progressbar" ),
        progressLabel = $( ".progress-label" );

        var step = parseFloat( 100 / total_iterations).toFixed(2);
        var input_form_data = { action: 'badgeos_convert_credly_achievements_2_open_badge', ID: total_entries[ iter_no ], disable_credly: $( '#badgeos_disable_send_credly_option' ).prop( 'checked' ) };

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: input_form_data,
            success: function (returndata) {
                
                var val = progressbar.progressbar( "value" ) || 0;
                val = parseFloat(val);
                var remaining = 100 - val;
                
                if(remaining > parseFloat(step) && remaining < (parseFloat(step)*2) ) {
                    for( var i = parseFloat(val); i <=100 ; i++ ) {
                        progressbar.progressbar( "value", i );
                    }

                    var final_value = progressbar.progressbar( "value" ) || 0;
                    if( parseInt( final_value ) < 100 ) {
                        progressbar.progressbar( "value", 100 );
                    }
                } else {
                    
                    var step_total = parseFloat(val) + parseFloat(step);
                    if( ( ( 100 / total_iterations) % parseFloat(step)) == 0 ) {
                        for( var i = val; i <=step_total ; i++ ) {
                            progressbar.progressbar( "value", i );
                        } 
                    } else {
                        progressbar.progressbar( "value", parseFloat(step_total.toFixed(2)) );
                    }
                }
               
                if( total_iterations > iter_no+1 ) {
                    process_single_achievement( iter_no+1 );
                }
            },
            error : function ( returndata ) {
                var val = progressbar.progressbar( "value" ) || 0;
                var remaining = 100 - val;
                if(remaining > step && remaining < (step*2) )
                {
                    for( var i = val; i <=100 ; i++ ) {
                        progressbar.progressbar( "value", i );
                    }
                }
                else
                {
                    var step_total = val + step;
                    for( var i = val; i <=step_total ; i++ ) {
                        progressbar.progressbar( "value", step_total );
                    }
                }
            }
        });
    }

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