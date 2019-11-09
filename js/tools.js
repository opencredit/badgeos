jQuery(document).ready(function($) {

    /**
     * For Tool Page
     */
    $( 'select#achievement_types_to_award, select#badgeos-award-users, select#achievement_types_to_revoke, select#badgeos-revoke-users, select#rank_types_to_award, select#rank_types_to_revoke' ).select2( { width: 'resolve', minimumInputLength: 0 } );

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

    $( function() {

        $( "#achievement-tabs, #credit-tabs, #rank-tabs, #system-tabs" ).tabs().addClass( "ui-tabs-vertical ui-helper-clearfix" );
        $( "#achievement-tabs li, #credit-tabs li, #rank-tabs li, #system-tabs li" ).removeClass( "ui-corner-top" ).addClass( "ui-corner-left" );
    } );
} );