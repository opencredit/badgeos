/**
 * Created with JetBrains PhpStorm.
 * User: user1
 * Date: 1/28/15
 * Time: 10:19 AM
 * To change this template use File | Settings | File Templates.
 */

(function($){

    $(window).load(function(){
        $('.badgeos_comment').each(function(){
            //Load ckeditor with multiple instances
            var editor_id = $(this).attr('id');
            if(editor_id!=null || editor_id!=undefined){
                CKEDITOR.replace(editor_id);
            }
        });

        //submission content
        if($('#badgeos_submission_content').length>0) {
            CKEDITOR.replace('badgeos_submission_content');
        }

        $("span.cke_toolbox .cke_toolbar_break").css('clear','inherit');

    });

})(jQuery);