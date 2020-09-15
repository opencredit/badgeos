<?php

function badgeos_migrate_data_from_meta_to_db_callback() {

    $action = ( isset( $_POST['action'] ) ? $_POST['action'] : '' );

    if( $action == 'badgeos_migrate_data_from_meta_to_db' ) {
        $users = get_users( 'fields=ids' );
        foreach( $users as $user_id ) {
            badgeos_utilities::update_user_meta( $user_id, 'updated_achivements_meta_to_db', 'No' );
        }
    }
    if ( ! wp_next_scheduled ( 'cron_migrate_data_from_meta_to_db' ) ) {
        wp_schedule_single_event( time(), 'cron_migrate_data_from_meta_to_db' );
    }
}
add_action( 'wp_ajax_badgeos_migrate_data_from_meta_to_db', 'badgeos_migrate_data_from_meta_to_db_callback' );

function cron_migrate_data_from_meta_to_db_callback() {

    global $wpdb;

    $table_name = $wpdb->prefix . "badgeos_achievements";
    $wpdb->query( "DELETE FROM ".$wpdb->prefix."badgeos_achievements WHERE rec_type = 'normal_old'" );
    $wpdb->query( "DELETE FROM ".$wpdb->prefix."badgeos_points WHERE this_trigger like 'm2dbold:%'" );

    echo badgeos_update_from_meta_to_db_callback();
}
add_action( 'cron_migrate_data_from_meta_to_db', 'cron_migrate_data_from_meta_to_db_callback' );

function badgeos_update_achievement_from_meta_to_db() {
    if( ! is_admin() ) {
        return;
    }

    $class = 'notice is-dismissible error';
    $message = __( 'Please <a id="badgeos_notice_update_from_meta_to_db" href="javascript:;" >click</a> here to update BadgeOS achievements from meta to data.', 'badgeos' );
    printf ( '<div id="message" class="%s migrate-meta-to-db"> <p>%s</p></div>', $class, $message );

}
add_action( 'admin_post_badgeos_update_from_meta_to_db', 'badgeos_update_from_meta_to_db_callback' );

function badgeos_update_from_meta_to_db_callback() {

    $site_id = get_current_blog_id();
    $users = get_users( 'fields=ids' );
    $updated_count = 0;

    foreach( $users as $user_id ) {

        global $wpdb;

        $meta_to_db = badgeos_utilities::get_user_meta( $user_id, 'updated_achivements_meta_to_db', true );
        if( $meta_to_db != 'Yes' ) {
            $achievements = badgeos_utilities::get_user_meta( $user_id, '_badgeos_achievements', true );

            if( is_array( $achievements ) && count( $achievements ) > 0 ) {
                foreach( $achievements[$site_id] as $achievement ) {

                    $badgeos_settings = badgeos_utilities::get_option( 'badgeos_settings' );
                    $point_id = 0;
                    $point_type = 0;
                    if( isset( $achievement->points ) ) {
                        $point_rec = $achievement->points;

                        if( is_array( $point_rec ) && count( $point_rec ) ) {
                            $point_id = intval( $point_rec['_badgeos_points'] );
                            $point_type = intval( $point_rec['_badgeos_points_type'] );
                        } else {
                            $point_id = intval( $point_rec );
                            $point_type = $badgeos_settings['default_point_type'];
                        }
                    }

                    $badge_title = $achievement->title;
                    if( empty( $badge_title ) ) {
                        $badge_title = get_the_title( $achievement->ID );
                    }

                    $wpdb->insert($wpdb->prefix.'badgeos_achievements', array(
                        'ID'        			=> $achievement->ID,
                        'post_type'      		=> $achievement->post_type,
                        'achievement_title'     => $badge_title,
                        'points'             	=> $point_id,
                        'point_type'			=> $point_type,
                        'this_trigger'         	=> $achievement->trigger,
                        'user_id'               => absint( $user_id ),
                        'site_id'               => $site_id,
                        'image'          		=> '',
                        'rec_type'          	=> 'normal_old',
                        'date_earned'           => date("Y-m-d H:i:s", $achievement->date_earned)
                    ));

                    $lastid = $wpdb->insert_id;
                    if( intval( $lastid ) > 0 && intval( $point_id ) > 0 ) {
                        $wpdb->insert($wpdb->prefix.'badgeos_points', array(
                            'credit_id' => $point_type,
                            'step_id' => $achievement->ID,
                            'admin_id' => 0,
                            'user_id' => absint( $user_id ),
                            'achievement_id' => $achievement->ID,
                            'type' => 'Award',
                            'credit' => $point_id,
                            'dateadded' => date("Y-m-d H:i:s", $achievement->date_earned),
                            'this_trigger' =>  "m2dbold:".$lastid.":".$achievement->trigger
                        ));

                    }

                }
            }

            badgeos_utilities::update_user_meta( $user_id, 'updated_achivements_meta_to_db', 'Yes' );
        }

        $updated_count += 1;
    }

    if( count( $users ) == $updated_count) {
        badgeos_utilities::update_option( 'badgeos_all_achievement_db_updated', 'Yes' );
    }

    $from_title = get_bloginfo( 'name' );
    $from_email = get_bloginfo( 'admin_email' );
    $headers[] = 'From: '.$from_title.' <'.$from_email.'>';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';

    $body = '<table border="0" cellspacing="0" cellpadding="0" align="center">';
    $body .= '<tr valign="top">';
    $body .= '<td>';
    $body .= '<p>'.__( 'Hi', 'badgeos' ).' '.$from_title.'</p>';
    $body .= '<p>'.__( "Your site users' existing achievements and points have been updated to the BadgeOS table.", 'badgeos' ).'</p>';
    $body .= '</td></tr>';
    $body .= '<tr valign="top"><td></td></tr>';
    $body .= '<tr valign="top"><td>'.__( 'Thanks', 'badgeos' ).'</td></tr>';
    $body .= '</table>';

    wp_mail( $from_email, 'BadgeOS DB Upgrade', $body, $headers );

    return $updated_count;
}
?>