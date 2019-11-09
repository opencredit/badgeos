<?php
/**
 * Points-related Functions
 *
 * @package BadgeOS
 * @subpackage Points
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Return a user's points
 *
 * @since  1.0.0
 * @param  int   	$user_id      The given user's ID
 * @return integer  $user_points  The user's current points
 */
function badgeos_get_users_points( $user_id = 0, $achievement_id = 0 ) {

	// Use current user's ID if none specified
	if ( ! $user_id )
		$user_id = wp_get_current_user()->ID;
	
	$points = get_post_meta( $achievement_id, '_badgeos_points_required', true );
	
	if( isset( $points ) &&  is_array( $points ) && count( $points ) > 0 ) {
		$point_value 	= $points['_badgeos_points_required'];
		$points_type 	= $points['_badgeos_points_required_type'];
		
		return badgeos_get_points_by_type( $points_type, $user_id );
	} else {

        $badgeos_settings = get_option( 'badgeos_settings' );
        $point_id = ( ! empty ( $badgeos_settings['default_point_type'] ) ) ? $badgeos_settings['default_point_type'] : 0;

        if( intval( $point_id ) > 0 ) {
            return badgeos_get_points_by_type( $point_id, $user_id );
        }

        return 0;
	}
}

/**
 * Flush rewrite rules on publishing a rank
 *
 * @param $new_status
 * @param $old_status
 * @param $post
 */
function badgeos_flush_rewrite_on_published_poings( $new_status, $old_status, $post ) {

    $settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
    if ( trim( $settings['points_main_post_type'] ) === $post->post_type && 'publish' === $new_status && 'publish' !== $old_status ) {
        badgeos_flush_rewrite_rules();
    }
}
add_action( 'transition_post_status', 'badgeos_flush_rewrite_on_published_poings', 10, 3 );

/**
 * Return credit types
 *
 * @return array
 */
function badgeos_get_point_types() {
	$settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
    $credits = get_posts( array(
        'post_type'         => trim( $settings['points_main_post_type'] ),
        'posts_per_page'    => -1,
        'suppress_filters'  => false,
        'post_status'       => 'publish',
    ) );

    return $credits;
}

/**
 * Updates the user points
 *
 * @since  1.0.0
 * @param  integer $user_id        The given user's ID
 * @param  integer $new_points     The new points the user is being awarded
 * @param  integer $admin_id       If being awarded by an admin, the admin's user ID
 * @param  integer $achievement_id The achievement that generated the points, if applicable
 * @return integer                 The user's updated point total
 */
function badgeos_update_users_points( $user_id = 0, $new_points = 0, $admin_id = 0, $achievement_id = null ) {

	// Use current user's ID if none specified
	if ( ! $user_id )
		$user_id = get_current_user_id();
	
	$total_points = 0;
	
	$points 		= get_post_meta( $achievement_id, '_badgeos_points', true );
	$point_value 	= $points['_badgeos_points'];
	$points_type 	= $points['_badgeos_points_type'];
    if( $points_type != 0 ) {
		$earned_credits = badgeos_get_points_by_type( $points_type, $user_id );

		badgeos_add_credit( $points_type, $user_id, 'Award', $point_value, 'achivement_based', $admin_id , 0 , $achievement_id );
	
		$total_points = badgeos_recalc_total_points( $user_id );
	
		badgeos_log_users_points( $user_id, $point_value, $total_points, $admin_id, $achievement_id,'Award', $points_type );
	
		// Available action for triggering other processes
		do_action( 'badgeos_update_users_points', $user_id, $point_value, $total_points, $admin_id, $achievement_id );
	
		/**
		 * Available action for triggering other processes
		 */
		do_action( 'badgeos_unlock_user_rank', $user_id, 'Award', $point_value, 0, $points_type, 'credit_based', $achievement_id, 0 );
	
	
		// Maybe award some points-based badges
		foreach ( badgeos_get_points_based_achievements() as $achievement ) {
			badgeos_maybe_award_achievement_to_user( $achievement->ID, $user_id );
		} 
	} 

	return $total_points;
}

/**
 * Log a user's updated points
 *
 * @since 1.2.0
 * @param integer $user_id        The user ID
 * @param integer $new_points     Points added to the user's total
 * @param integer $total_points   The user's updated total points
 * @param integer $admin_id       An admin ID (if admin-awarded)
 * @param integer $achievement_id The associated achievent ID
 */
function badgeos_log_users_points( $user_id, $new_points, $total_points, $admin_id, $achievement_id, $type='Award', $point_type=0 ) {

	// Setup our user objects
	$user  = get_userdata( $user_id );
	$admin = get_userdata( $admin_id );

	$point_type_title = get_the_title( $point_type );

	// Alter our log message if this was an admin action
	//name) earned 500 points from point type.
	if( $type == 'Deduct' ) {
		if ( $admin_id )
			$log_message = sprintf( __( '%1$s deducted %2$s %3$s points for a new total of %4$s points', 'badgeos' ), $admin->user_login, $user->user_login, number_format( $new_points ), number_format( $total_points ) );
		else
			$log_message = sprintf( __( '%1$s lost %2$s points from %3$s.', 'badgeos' ), $user->user_login, number_format( $new_points ), $point_type_title );
	} else {
		if ( $admin_id )
			$log_message = sprintf( __( '%1$s awarded %2$s %3$s points for a new total of %4$s points', 'badgeos' ), $admin->user_login, $user->user_login, number_format( $new_points ), number_format( $total_points ) );
		else
			$log_message = sprintf( __( '%1$s earned %2$s points from %3$s.', 'badgeos' ), $user->user_login, number_format( $new_points ), $point_type_title );
	}
	
	// Create a log entry
	$log_entry_id = badgeos_post_log_entry( $achievement_id, $user_id, 'points', $log_message );

	// Add relevant meta to our log entry
	update_post_meta( $log_entry_id, '_badgeos_awarded_points', $new_points );
	update_post_meta( $log_entry_id, '_badgeos_total_user_points', $total_points );
	if ( $admin_id )
		update_post_meta( $log_entry_id, '_badgeos_admin_awarded', $admin_id );

}
add_action( 'badgeos_update_users_points', 'badgeos_log_users_points', 10, 7 );

/**
 * Award new points to a user based on logged activites and earned badges
 *
 * @since  1.0.0
 * @param  integer $user_id        The given user's ID
 * @param  integer $achievement_id The given achievement's post ID
 * @return integer                 The user's updated points total
 */
function badgeos_award_user_points( $user_id = 0, $achievement_id = 0 ) {

	// Grab our points from the provided post 
	$points 				= get_post_meta( $achievement_id, '_badgeos_points', true );
	if( isset( $points ) &&  is_array( $points ) && count( $points ) > 0 ) {
		$point_value 			= $points['_badgeos_points'];
		$badgeos_points_type 	= $points['_badgeos_points_type'];
	
		if ( ! empty( $point_value ) )
			return badgeos_update_users_points( $user_id, $point_value, false, $achievement_id );
	}
}
add_action( 'badgeos_award_achievement', 'badgeos_award_user_points', 999, 2 );