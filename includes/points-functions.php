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
 * @param  int   $user_id      The given user's ID
 * @return integer  $user_points  The user's current points
 */
function badgeos_get_users_points( $user_id = 0 ) {

	// Use current user's ID if none specified
	if ( ! $user_id )
		$user_id = wp_get_current_user()->ID;

	// Return our user's points as an integer (sanely falls back to 0 if empty)
	return absint( get_user_meta( $user_id, '_badgeos_points', true ) );
}

/**
 * Posts a log entry when a user earns points
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

	// Grab the user's current points
	$current_points = badgeos_get_users_points( $user_id );

	// If we're getting an admin ID, $new_points is actually the final total, so subtract the current points
	if ( $admin_id ) $new_points = $new_points - $current_points;

	// Update our user's total
	$total_points = absint( $current_points + $new_points );
	update_user_meta( $user_id, '_badgeos_points', $total_points );

	// Available action for triggering other processes
	do_action( 'badgeos_update_users_points', $user_id, $new_points, $total_points, $admin_id, $achievement_id );

	// Maybe award some points-based badges
	foreach ( badgeos_get_points_based_achievements() as $achievement )
		badgeos_maybe_award_achievement_to_user( $achievement->ID );

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
function badgeos_log_users_points( $user_id, $new_points, $total_points, $admin_id, $achievement_id ) {

	// Setup our user objects
	$user  = get_userdata( $user_id );
	$admin = get_userdata( $admin_id );

	// Alter our log message if this was an admin action
	if ( $admin_id )
		$log_message = sprintf( __( '%1$s awarded %2$s %3$s points for a new total of %4$s points', 'badgeos' ), $admin->user_login, $user->user_login, number_format( $new_points ), number_format( $total_points ) );
	else
		$log_message = sprintf( __( '%1$s earned %2$s points for a new total of %3$s points', 'badgeos' ), $user->user_login, number_format( $new_points ), number_format( $total_points ) );

	// Create a log entry
	$log_entry_id = badgeos_post_log_entry( $achievement_id, $user_id, 'points', $log_message );

	// Add relevant meta to our log entry
	update_post_meta( $log_entry_id, '_badgeos_awarded_points', $new_points );
	update_post_meta( $log_entry_id, '_badgeos_total_user_points', $total_points );
	if ( $admin_id )
		update_post_meta( $log_entry_id, '_badgeos_admin_awarded', $admin_id );

}
add_action( 'badgeos_update_users_points', 'badgeos_log_users_points', 10, 5 );

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
	$points = absint( get_post_meta( $achievement_id, '_badgeos_points', true ) );

	if ( ! empty( $points ) )
		return badgeos_update_users_points( $user_id, $points, false, $achievement_id );
}
add_action( 'badgeos_award_achievement', 'badgeos_award_user_points', 999, 2 );
