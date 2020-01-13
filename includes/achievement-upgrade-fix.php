<?php
global $wpdb;

$badgeos_settings = get_option( 'badgeos_settings' );
$default_point_type 	= ( ! empty ( $badgeos_settings['default_point_type'] ) ) ? $badgeos_settings['default_point_type'] : 0;

$strQuery = "update ".$wpdb->prefix . "badgeos_achievements set point_type='".$default_point_type."' where point_type='0' or point_type=''";
$wpdb->query( $strQuery );

$strQuery = "select * from ".$wpdb->prefix . "badgeos_achievements where user_id!=0 and points>0 and rec_type='normal'";
$results = $wpdb->get_results( $strQuery );
$count = 0;
foreach( $results as $res ) {

    $strQuery = "select * from ".$wpdb->prefix . "badgeos_points where this_trigger like 'm2dbold:".$res->entry_id.":%'";
    $points = $wpdb->get_results( $strQuery );
    if( count( $points ) == 0 ) {
        $count++;
        $point_type = $default_point_type;
        if( intval( $res->point_type ) > 0 ) {
            $point_type = $res->point_type;
        }

        $wpdb->insert($wpdb->prefix.'badgeos_points', array(
            'credit_id' => $point_type,
            'step_id' => $res->ID,
            'admin_id' => 0,
            'user_id' => $res->user_id,
            'achievement_id' => $res->ID,
            'type' => 'Award',
            'credit' => $res->points,
            'dateadded' => $res->date_earned,
            'this_trigger' =>  "m2dbold:".$res->entry_id.":".$res->this_trigger
        ));
    }
}
echo $count;
exit;
?>