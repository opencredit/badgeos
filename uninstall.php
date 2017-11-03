<?php
/**
 * Uninstall file, which would delete all user metadata and configuration settings
 *
 * @since 1.0
 */
if ( !defined( "WP_UNINSTALL_PLUGIN" ) )
    exit();

$badgeos_settings = get_option( 'badgeos_settings' );
$remove_data_on_uninstall = ( isset( $badgeos_settings['remove_data_on_uninstall'] ) ) ? $badgeos_settings['remove_data_on_uninstall'] : '';

/**
 * Return - if delete option is not enabled.
 */
if( "on" != $remove_data_on_uninstall ) {
    return;
}

global $wpdb;

/**
 * Delete Achievements post types data
 */
$achievement_types = $wpdb->get_results( "SELECT `ID`, `post_title` FROM $wpdb->posts WHERE post_type = 'achievement-type';" );
if( is_array( $achievement_types ) && !empty( $achievement_types ) && !is_null( $achievement_types ) ) {
    $to_delete = array();
    $child_post_types = array();
    foreach( $achievement_types as $achievement_type ) {
        $to_delete[] = $achievement_type->ID;
        $child_post_types[] = sanitize_title( substr ( strtolower ( $achievement_type->post_title ),0, 20 ) );
    }

    if( !empty( $child_post_types ) ) {
        foreach( $child_post_types as $child_post_type ) {
            $child_posts = $wpdb->get_results( "SELECT `ID` FROM $wpdb->posts WHERE post_type = '$child_post_type';" );
            if( is_array( $child_posts ) && !empty( $child_posts ) && !is_null( $child_posts ) ) {
                foreach( $child_posts as $child_post ) {
                    $to_delete[] = $child_post->ID;
                }
            }
        }
    }

    foreach( $to_delete as $del ) {
        $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE post_id = '$del';" );
        $wpdb->query( "DELETE FROM $wpdb->posts WHERE ID = '$del';" );
    }
}

/**
 * Delete Step post type data
 */
$steps_ids = $wpdb->get_results( "SELECT `ID` FROM $wpdb->posts WHERE post_type = 'step';" );
if( is_array( $steps_ids ) && !empty( $steps_ids ) && !is_null( $steps_ids ) ) {
    foreach( $steps_ids as $steps_id ) {
        $wpdb->query( "DELETE FROM $wpdb->posts WHERE ID = '$steps_id->ID';" );
        $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE post_id = '$steps_id->ID';" );
    }
}

/**
 * Delete badgeos-log-entry post type data
 */
$badgeos_log_entry = $wpdb->get_results( "SELECT `ID` FROM $wpdb->posts WHERE post_type = 'badgeos-log-entry';" );
if( is_array( $badgeos_log_entry ) && !empty( $badgeos_log_entry ) && !is_null( $badgeos_log_entry ) ) {
    foreach( $badgeos_log_entry as $log_entry ) {
        $wpdb->query( "DELETE FROM $wpdb->posts WHERE ID = '$log_entry->ID';" );
        $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE post_id = '$log_entry->ID';" );
    }
}

/**
 * Delete submission post type data
 */
$submissions = $wpdb->get_results( "SELECT `ID` FROM $wpdb->posts WHERE post_type = 'submission';" );
if( is_array( $submissions ) && !empty( $submissions ) && !is_null( $submissions ) ) {
    foreach( $submissions as $submission ) {
        $wpdb->query( "DELETE FROM $wpdb->posts WHERE ID = '$submission->ID';" );
        $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE post_id = '$submission->ID';" );
    }
}

/**
 * Delete nomination post type data
 */
$nominations = $wpdb->get_results( "SELECT `ID` FROM $wpdb->posts WHERE post_type = 'nomination';" );
if( is_array( $nominations ) && !empty( $nominations ) && !is_null( $nominations ) ) {
    foreach( $nominations as $nomination ) {
        $wpdb->query( "DELETE FROM $wpdb->posts WHERE ID = '$nomination->ID';" );
        $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE post_id = '$nomination->ID';" );
    }
}

/**
 * Delete user BadgeOS meta
 */
$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key = 'credly_user_enable';");
$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key = '_badgeos_triggered_triggers';");
$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key = 'credly_user_enable';");
$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key = '_badgeos_can_notify_user';");
$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key = 'credly_user_id';");
$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key = '_badgeos_achievements';");
$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key = '_badgeos_active_achievements';");
$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key = '_badgeos_points';");

/**
 * Delete BadgeOS options
 */
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name = 'credly_api_key_error';");
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name = 'badgeos_settings';");
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name = 'credly_settings';");
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name ='widget_p2p';");
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name ='widget_earned_user_achievements_widget';");
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name ='widget_credly_credit_issuer_widget';");
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name ='p2p_storage';");

/**
 * Delete BadgeOS tables
 */
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "p2p" );
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "p2pmeta" );