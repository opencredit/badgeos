<?php

/**
 * Handles the migrate points to points fields ajax call
 *
 * @since 3.2
 */
function badgeos_migrate_fields_points_to_point_types_callback() {
    $action = ( isset( $_POST['action'] ) ? $_POST['action'] : '' );
    if( $action !== 'badgeos_migrate_fields_points_to_point_types' &&  $action !== 'badgeos_migrate_fields_points_to_point_types' ) {
        exit;
    }
    $badgeos_settings       = badgeos_utilities::get_option( 'badgeos_settings' );
    $default_point_type 	= ( ! empty ( $badgeos_settings['default_point_type'] ) ) ? $badgeos_settings['default_point_type'] : '';

    if( ! empty( $default_point_type ) ) {
        if ( ! wp_next_scheduled ( 'cron_badgeos_update_old_points_to_point_types' ) ) {
            wp_schedule_single_event( time(), 'cron_badgeos_update_old_points_to_point_types' );
            echo '<img src="'.admin_url( 'images/spinner.gif' ).'" /> &nbsp;&nbsp;'.__( 'BadgeOS is updating points data as a background process. You will receive a confirmation email upon successful completion. You can continue exploring badgeos.', 'badgeos' );
        } else {
            echo __( 'Points updation process is already under process. Thanks.', 'badgeos' );
        }

    } else {
        echo __( 'Default point type field is not defined. Kindly, configure the default point type field type first. Thanks.', 'badgeos' );
    }
    exit;
}

add_action( 'wp_ajax_badgeos_migrate_fields_points_to_point_types', 'badgeos_migrate_fields_points_to_point_types_callback' );

function cron_badgeos_update_old_points_to_point_types_callback() {

    badgeos_update_old_points_to_point_types();
}
add_action( 'cron_badgeos_update_old_points_to_point_types', 'cron_badgeos_update_old_points_to_point_types_callback' );

function badgeos_update_old_points_to_point_types() {

    $badgeos_settings = badgeos_utilities::get_option( 'badgeos_settings' );
    $default_point_type 	= ( ! empty ( $badgeos_settings['default_point_type'] ) ) ? $badgeos_settings['default_point_type'] : '';
    if( ! empty( $default_point_type ) ) {
        // Grab all of our achievement type posts
        $achievement_types = get_posts( array(
            'post_type'      =>	$badgeos_settings['achievement_main_post_type'],
            'posts_per_page' =>	-1,
        ) );

        // Loop through each achievement type post and register it as a CPT
        foreach ( $achievement_types as $achievement_type ) {

            $achievements = get_posts( array(
                'post_type'      =>	$achievement_type->post_name,
                'posts_per_page' =>	-1,
            ) );

            foreach( $achievements as $achievement ) {

                $achievement_id = $achievement->ID;
                $points            = badgeos_utilities::get_post_meta( $achievement_id, '_badgeos_points', true );
                if( isset( $points ) && ( ! is_array( $points ) || empty($points) ) ) {
                    $points_array = array();
                    $points_array[ '_badgeos_points' ] = intval( $points );
                    $points_array[ '_badgeos_points_type' ] = $default_point_type;
                    badgeos_utilities::update_post_meta( $achievement_id, '_badgeos_points', $points_array );
                }

                $points_required   = badgeos_utilities::get_post_meta( $achievement_id, '_badgeos_points_required', true );
                if( isset( $points_required ) && ( ! is_array( $points_required ) || empty($points_required) ) ) {
                    $points_req_array = array();
                    $points_req_array[ '_badgeos_points_required' ] = intval( $points_required );
                    $points_req_array[ '_badgeos_points_required_type' ] = $default_point_type;
                    badgeos_utilities::update_post_meta( $achievement_id, '_badgeos_points_required', $points_req_array );
                }
            }
        }

        $from_title = get_bloginfo( 'name' );
        $from_email = get_bloginfo( 'admin_email' );
        $headers[] = 'From: '.$from_title.' <'.$from_email.'>';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';

        $body = '<table border="0" cellspacing="0" cellpadding="0" align="center">';
        $body .= '<tr valign="top">';
        $body .= '<td>';
        $body .= '<p>'.__( 'Hi', 'badgeos' ).' '.$from_title.'</p>';
        $body .= '<p>'.__( "Your site existing badges have been updated with regards to the point type.", 'badgeos' ).'</p>';
        $body .= '</td></tr>';
        $body .= '<tr valign="top"><td></td></tr>';
        $body .= '<tr valign="top"><td>'.__( 'Thanks', 'badgeos' ).'</td></tr>';
        $body .= '</table>';

        wp_mail( $from_email, 'BadgeOS Points Format Upgrade', $body, $headers );

    }
}