<?php
/**
 * Rules Engine: The brains behind this whole operation
 *
 * @package BadgeOS
 * @subpackage Achievements
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Check if user should earn an achievement, and award it if so.
 *
 * @since 1.0.0
 * @param integer $achievement_id The given achievement ID to possibly award
 * @param integer $user_id        The given user's ID
 */
function badgeos_maybe_award_achievement_to_user( $achievement_id, $user_id = 0 ) {

	// Grab current user ID if one isn't specified
	if ( ! $user_id )
		$user_id = wp_get_current_user()->ID;

	// See if the user has completed the achievement, and award if so
	if ( badgeos_check_achievement_completion_for_user( $achievement_id, $user_id ) )
		badgeos_award_achievement_to_user( $achievement_id, $user_id );
}

/**
 * Check if user has completed an achievement
 *
 * @since  1.0.0
 * @param  integer $achievement_id The given achievement ID to verify
 * @param  integer $user_id        The given user's ID
 * @return bool                    True if user has completed achievement, false otherwise
 */
function badgeos_check_achievement_completion_for_user( $achievement_id, $user_id = 0 ) {

	// Grab the current user ID if one is not specified
	if ( ! $user_id )
		$user_id = wp_get_current_user()->ID;

	// Assume the user has completed the achievement
	$has_achievement = badgeos_get_user_achievements( array( 'user_id' => absint( $user_id ), 'achievement_id' => absint( $achievement_id ) ) );
	$return = true;

	// If the user already has the achievement, return true
	if ( $has_achievement )
		$return = true;

	// Get all required achievements linked to achievement
	$required_achievements = badgeos_get_required_achievements_for_achievement( $achievement_id );

	// Check if user has completed all required achievements for this achievement
	if ( ! empty( $required_achievements ) ) {
		foreach ( $required_achievements as $achievement ) {
			// If the user doesn't have the achievement, and they haven't completed it just now, fail
			if (
				! $has_achievement
				&& ! badgeos_check_achievement_completion_for_user( $achievement->ID, $user_id )
			) {
				$return = false;
			}
		}
	}

	// Run our return through a filter to support custom achievement types
	return apply_filters( 'user_deserves_achievement', $return, $user_id, $achievement_id );
}

/**
 * Check if user meets the points requirement for a given achievement
 *
 * @since  1.0.0
 * @param  bool $return            The current status of whether or not the user deserves this achievement
 * @param  integer $user_id        The given user's ID
 * @param  integer $achievement_id The given achievement's post ID
 * @return bool                    Our possibly updated earning status
 */
function badgeos_user_meets_points_requirement( $return, $user_id, $achievement_id ) {

	// First, see if the achievement requires a minimum amount of points
	if ( 'points' == get_post_meta( $achievement_id, '_badgeos_earned_by', true ) ) {

		// Grab our user's points and see if they at least as many as required
		$user_points     = absint( badgeos_get_users_points( $user_id ) );
		$points_required = absint( get_post_meta( $achievement_id, '_badgeos_points_required', true ) );
		$last_activity   = badgeos_achievement_last_user_activity( $achievement_id );

		if ( $user_points >= $points_required )
			$return = true;
		else
			$return = false;

		// If the user just earned the badge, though, don't let them earn it again
		// This prevents an infinite loop if the badge has no maximum earnings limit
		if ( $last_activity >= time('-2 seconds') )
			$return = false;
	}

	// Return our eligibility status
	return $return;
}
add_filter( 'user_deserves_achievement', 'badgeos_user_meets_points_requirement', 10, 3 );

/**
 * Award an achievement to a user
 *
 * @since  1.0.0
 * @param  integer $achievement_id The given achievement ID to award
 * @param  integer $user_id        The given user's ID
 */
function badgeos_award_achievement_to_user( $achievement_id = 0, $user_id = 0 ) {

	// Use the current user ID if none specified
	if ( $user_id == 0 )
		$user_id = wp_get_current_user()->ID;

	// Setup our achievement object
	$achievement_object = badgeos_build_achievement_object( $achievement_id );

	// Update user's earned achievements
	badgeos_update_user_achievements( array( 'user_id' => $user_id, 'new_achievements' => array( $achievement_object ) ) );

	// Log the earning of the award
	badgeos_post_log_entry( $achievement_id, $user_id );

	// Available hook to do other things with each awarded achievement
	do_action( 'badgeos_award_achievement', $user_id, $achievement_id );

	// Available hook for unlocking any achievement of this achievement type
	do_action( 'badgeos_unlock_' . $achievement_object->post_type );

}

/**
 * Revoke an achievement from a user
 *
 * @since 1.0.0
 * @param integer $achievement_id The given achievement's post ID
 * @param integer $user_id        The given user's ID
 *
 */
function badgeos_revoke_achievement_from_user( $achievement_id = 0, $user_id = 0 ) {

	// Use the current user's ID if none specified
	if ( ! $user_id )
		$user_id = wp_get_current_user()->ID;

	// Grab the user's earned achievements
	$earned_achievements = badgeos_get_user_achievements( array( 'user_id' => $user_id ) );

	// Loop through each achievement and drop the achievement we're revoking
	foreach ( $earned_achievements as $key => $achievement ) {
		if ( $achievement->ID == $achievement_id ) {

			// Drop the achievement from our earnings
			unset( $earned_achievements[$key] );

			// Re-key our array
			$earned_achievements = array_values( $earned_achievements );

			// Update user's earned achievements
			badgeos_update_user_achievements( array( 'user_id' => $user_id, 'all_achievements' => $earned_achievements ) );

			// Available hook for taking further action when an achievement is revoked
			do_action( 'badgeos_revoke_achievement', $user_id, $achievement_id );

			// Stop after dropping one, because we don't want to delete ALL instances
			break;
		}
	}

}

/**
 * Award additional achievements to user
 *
 * @since  1.0.0
 * @param  integer $user_id        The given user's ID
 * @param  integer $achievement_id The given achievement's post ID
 */
function badgeos_maybe_award_additional_achievements_to_user( $user_id = 0, $achievement_id = 0 ) {

	// Get achievements that can be earned from completing this achievement
	$dependent_achievements = badgeos_get_dependent_achievements( $achievement_id );

	// Loop through each dependent achievement and see if it can be awarded
	foreach ( $dependent_achievements as $achievement )
		badgeos_maybe_award_achievement_to_user( $achievement->ID, $user_id );

	// Grab our user's (presumably updated) earned achievements
	$earned_achievements = badgeos_get_user_achievements( array( 'user_id' => $user_id ) );
	$post_type = get_post_type( $achievement_id );

	// Hook for unlocking all achievements of this achievement type
	if ( $all_achievements_of_type = badgeos_get_achievements( array( 'post_type' => $post_type ) ) ) {

		// Assume we can award the user for unlocking all achievements of this type
		$all_per_type = true;

		// Loop through each of our achievements of this type
		foreach ( $all_achievements_of_type as $achievement ) {

			// Assume the user hasn't earned this achievement
			$found_achievement = false;

			// Loop through each eacrned achivement and see if we've earned it
			foreach ( $earned_achievements as $earned_achievement ) {
				if ( $earned_achievement->ID == $achievement->ID ) {
					$found_achievement = true;
					break;
				}
			}

			// If we haven't earned this single achievement, we haven't earned them all
			if ( ! $found_achievement ) {
				$all_per_type = false;
				break;
			}
		}

		// If we've earned all achievements of this type, trigger our hook
		if ( $all_per_type ) {
			do_action( 'badgeos_unlock_all_' . $post_type );
		}
	}
}
add_action( 'badgeos_award_achievement', 'badgeos_maybe_award_additional_achievements_to_user', 10, 2 );

/**
 * Check if user may access/earn achievement.
 *
 * @since  1.0.0
 * @param  integer $user_id        The given user's ID
 * @param  integer $achievement_id The given achievement's post ID
 * @return bool                    True if user has access, false otherwise
 */
function badgeos_user_has_access_to_achievement( $user_id = 0, $achievement_id = 0 ) {

	// Grab our current user's ID if none specified
	if ( ! $user_id )
		$user_id = wp_get_current_user()->ID;

	// Grab the current post's ID if none specifed
	if ( ! $achievement_id ) {
		global $post;
		$achievement_id = $post->ID;
	}
	// If there is no parent for the achievement, bail true.
	if ( ! $parent_achievement = badgeos_get_parent_of_achievement( $achievement_id ) )
		return true;
	if ( ! badgeos_user_has_access_to_achievement( $user_id, $parent_achievement->ID ) )
		return false;

	// Assume the user has access to the achievment
	$return = true;

	// If we're dealing with a sequential achievement, let's look at each step's siblings
	if ( badgeos_is_achievement_sequential( $parent_achievement->ID ) ) {
		foreach ( badgeos_get_children_of_achievement( $parent_achievement->ID ) as $sibling ) {
			// If the sibling ID is our achievement ID we're good to go
			if ( $sibling->ID == $achievement_id ) {
				$return = true;
				break;
			}
			// If the user hasn't earned the sibling, their inelligible for the current achievement
			if ( ! badgeos_get_user_achievements( array( 'user_id' => absint( $user_id ), 'achievement_id' => absint( $sibling->ID ) ) ) ) {
				$return = false;
				break;
			}
		}
	}

	// Available filter for custom overrides
	return apply_filters( 'user_has_access_to_achievement', $return, $user_id, $achievement_id );
}

/**
 * Checks if a user is allowed to work on a given step
 *
 * @since  1.0
 * @param  bool    $return   The default return value
 * @param  integer $user_id  The given user's ID
 * @param  integer $step_id  The given step's post ID
 * @return bool              True if user has access to step, false otherwise
 */
function badgeos_user_has_access_to_step( $return, $user_id, $step_id ) {

	// Get current user's ID if none specified
	if ( !$user_id )
		$user_id = wp_get_current_user()->ID;

	// Grab the parent badge of the step
	$parent_achievement = badgestack_get_parent_of_achievement( $step_id );

	// If step doesn't have a parent, bail
	if ( empty( $parent_achievement ) )
		return false;

	// If the badge has a max earning limit, stop them from perpetually earning it's steps
	if ( badgeos_achievement_user_exceeded_max_earnings( $user_id, $parent_achievement->ID ) )
		return false;

	// Check if user has already earned step while working on badge.
	if ( badgeos_get_user_achievements( array( 'user_id' => absint( $user_id ), 'achievement_id' => absint( $step_id ), 'since' => absint( badgeos_achievement_last_user_activity( $parent_achievement->ID, $user_id ) ) ) ) )
		return false;

	// If we passed everything else, the user has access to this step
	return true;
}
add_filter( 'user_has_access_to_achievement', 'badgeos_user_has_access_to_step', 10, 3 );


/**
 * Validate whether or not a user has completed all requirements for a step.
 *
 * @since  1.0
 * @param  bool $return      True if user deserves achievement, false otherwise
 * @param  integer $user_id  The given user's ID
 * @param  integer $step_id  The post ID for our step
 * @return bool              True if user deserves step, false otherwise
 */
function badgeos_user_deserves_step( $return, $user_id, $step_id ) {

	// Only override the $return data if we're working on a step
	if ( 'step' == get_post_type( $step_id ) ) {

		// Grab the parent badge for our step
		$parent_achievement = badgeos_get_parent_of_achievement( $step_id );

		// sanity check for bad data relations.
		if ( empty( $parent_achievement ) )
			return false;

		// Prevent users from earning more than the maximum allowed times
		if ( badgeos_achievement_user_exceeded_max_earnings( $user_id, $parent_achievement->ID ) )
			$return = false;

		// Get the required number of checkins for the step.
		$minimum_activity_count = get_post_meta( $step_id, '_badgeos_count', true );

		// Grab the relevent activity for this step
		$relevant_activities = badgeos_find_relevant_activity_for_step( $user_id, $step_id );
		$relevant_count = is_array( $relevant_activities ) ? count( $relevant_activities ) : absint( $relevant_activities );

		// If we meet or exceed the required number of checkins, they deserve the step
		if ( $relevant_count >= $minimum_activity_count )
			$return = true;
		else
			$return = false;
	}

	return $return;
}
add_filter( 'user_deserves_achievement', 'badgeos_user_deserves_step', 10, 3 );

/**
 * Given a step, find the relevant check-ins that count towards earning the step.
 */
function badgeos_find_relevant_activity_for_step( $user_id, $step_id ) {

	// If we don't have a user ID, use the current user's ID
	if ( ! $user_id )
		$user_id = wp_get_current_user()->ID;

	// Grab the badge that owns this step
	$parent_achievement = badgeos_get_parent_of_achievement( $step_id );

	// If this step doesn't have a parent badge, bail here
	if ( empty( $parent_achievement ) )
		return false;

	// Assume the user has no relevant activities
	$activities = array();

	// If we have any activity for this achievement, we only want the most recent activities
	if ( $date = badgeos_achievement_last_user_activity( $parent_achievement->ID, $user_id ) )
		$since = gmdate( 'Y-m-d H:i:s', ( $date + ( get_option( 'gmt_offset' ) * 3600 ) ) );
	else
		$since = 0;

	// Setup our $post object for the step
	$step = get_post( $step_id );

	// If step depends on other achievements...
	$step_requirements = badgeos_get_step_requirements( $step_id );
	if ( in_array( $step_requirements['trigger_type'], array( 'specific-achievement', 'any-achievement' ) ) ) {

		// Grab our user's relevant earned achievements
		$activities = badgeos_get_user_achievements( array(
			'user_id'          => absint( $user_id ),
			'achievement_id'   => absint( $step_requirements['achievement_post'] ),
			'achievement_type' => $step_requirements['achievement_type']
		) );

	// Otherwise, the step depends on triggering various action hooks...
	} else {

		// If we're looking for ALL achievements of a particular type
		if ( 'all-achievements' == $step_requirements['trigger_type'] )
			$step_requirements['trigger_type'] = 'badgeos_unlock_all_' . $step_requirements['achievement_type'];

		// Determine how man times the person has completed the action hook
		$activities = badgeos_get_user_trigger_count( $user_id, $step_requirements['trigger_type'] );

	}

	// Finally, return our relevant activities
	return $activities;
}
