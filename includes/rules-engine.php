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
 * @since  1.0.0
 * @param  integer $achievement_id The given achievement ID to possibly award
 * @param  integer $user_id        The given user's ID
 * @param  string $trigger         The trigger
 * @param  integer $site_id        The triggered site id
 * @param  array $args             The triggered args
 * @return void
 */
function badgeos_maybe_award_achievement_to_user( $achievement_id = 0, $user_id = 0, $this_trigger = '', $site_id = '', $args = array() ) {

	// Set to current site id
	if ( ! $site_id )
		$site_id = get_current_blog_id();

	// Grab current user ID if one isn't specified
	if ( ! $user_id )
		$user_id = wp_get_current_user()->ID;

	// If the user does not have access to this achievement, bail here
	if ( ! badgeos_user_has_access_to_achievement( $user_id, $achievement_id, $this_trigger, $site_id, $args ) )
		return false;

	// If the user has completed the achievement, award it
	if ( badgeos_check_achievement_completion_for_user( $achievement_id, $user_id, $this_trigger, $site_id, $args ) )
		badgeos_award_achievement_to_user( $achievement_id, $user_id, $this_trigger, $site_id, $args );
}

/**
 * Check if user has completed an achievement
 *
 * @since  1.0.0
 * @param  integer $achievement_id The given achievement ID to verify
 * @param  integer $user_id        The given user's ID
 * @param  string  $trigger        The trigger
 * @param  integer $site_id        The triggered site id
 * @param  array   $args           The triggered args
 * @return bool                    True if user has completed achievement, false otherwise
 */
function badgeos_check_achievement_completion_for_user( $achievement_id = 0, $user_id = 0, $this_trigger = '', $site_id = '', $args = array() ) {

	// Assume the user has completed the achievement
	$return = true;

	// Set to current site id
	if ( ! $site_id )
		$site_id = get_current_blog_id();

	// If the user has not already earned the achievement...
	if ( ! badgeos_get_user_achievements( array( 'user_id' => absint( $user_id ), 'achievement_id' => absint( $achievement_id ), 'since' => 1 + badgeos_achievement_last_user_activity( $achievement_id, $user_id ) ) ) ) {

		// Grab our required achievements for this achievement
		$required_achievements = badgeos_get_required_achievements_for_achievement( $achievement_id );

		// If we have requirements, loop through each and make sure they've been completed
		if ( is_array( $required_achievements ) && ! empty( $required_achievements ) ) {
			foreach ( $required_achievements as $requirement ) {
				// Has the user already earned the requirement?
				if ( ! badgeos_get_user_achievements( array( 'user_id' => $user_id, 'achievement_id' => $requirement->ID, 'since' => badgeos_achievement_last_user_activity( $achievement_id, $user_id ) ) ) ) {
					$return = false;
					break;
				}
			}
		}
	}

	// Available filter to support custom earning rules
	return apply_filters( 'user_deserves_achievement', $return, $user_id, $achievement_id, $this_trigger, $site_id, $args );

}

/**
 * Check if user meets the points requirement for a given achievement
 *
 * @since  1.0.0
 * @param  bool    $return         The current status of whether or not the user deserves this achievement
 * @param  integer $user_id        The given user's ID
 * @param  integer $achievement_id The given achievement's post ID
 * @return bool                    Our possibly updated earning status
 */
function badgeos_user_meets_points_requirement( $return = false, $user_id = 0, $achievement_id = 0 ) {

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
 * @param  string $trigger         The trigger
 * @param  integer $site_id        The triggered site id
 * @param  array $args             The triggered args
 * @return void
 */
function badgeos_award_achievement_to_user( $achievement_id = 0, $user_id = 0, $this_trigger = '', $site_id = '', $args = array() ) {

	global $wp_filter, $wp_version;

	// Sanity Check: ensure we're working with an achievement post
	if ( ! badgeos_is_achievement( $achievement_id ) )
		return false;

	// Use the current user ID if none specified
	if ( $user_id == 0 )
		$user_id = wp_get_current_user()->ID;

	// Get the current site ID none specified
	if ( ! $site_id )
		$site_id = get_current_blog_id();

	// Setup our achievement object
	$achievement_object = badgeos_build_achievement_object( $achievement_id );

	// Update user's earned achievements
	badgeos_update_user_achievements( array( 'user_id' => $user_id, 'new_achievements' => array( $achievement_object ) ) );

	// Log the earning of the award
	badgeos_post_log_entry( $achievement_id, $user_id );

	// Available hook for unlocking any achievement of this achievement type
	do_action( 'badgeos_unlock_' . $achievement_object->post_type, $user_id, $achievement_id, $this_trigger, $site_id, $args );

	// Patch for WordPress to support recursive actions, specifically for badgeos_award_achievement
	// Because global iteration is fun, assuming we can get this fixed for WordPress 3.9
	$is_recursed_filter = ( 'badgeos_award_achievement' == current_filter() && version_compare( $wp_version, '3.9', '<' ) );
	$current_key = null;

	// Get current position
	if ( $is_recursed_filter ) {
		$current_key = key( $wp_filter[ 'badgeos_award_achievement' ] );
	}

	// Available hook to do other things with each awarded achievement
	do_action( 'badgeos_award_achievement', $user_id, $achievement_id, $this_trigger, $site_id, $args );

	if ( $is_recursed_filter ) {
		reset( $wp_filter[ 'badgeos_award_achievement' ] );

		while ( key( $wp_filter[ 'badgeos_award_achievement' ] ) !== $current_key ) {
			next( $wp_filter[ 'badgeos_award_achievement' ] );
		}
	}

}

/**
 * Revoke an achievement from a user
 *
 * @since  1.0.0
 * @param  integer $achievement_id The given achievement's post ID
 * @param  integer $user_id        The given user's ID
 * @return void
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
 * @return void
 */
function badgeos_maybe_award_additional_achievements_to_user( $user_id = 0, $achievement_id = 0 ) {

	// Get achievements that can be earned from completing this achievement
	$dependent_achievements = badgeos_get_dependent_achievements( $achievement_id );

	// Loop through each dependent achievement and see if it can be awarded
	foreach ( $dependent_achievements as $achievement )
		badgeos_maybe_award_achievement_to_user( $achievement->ID, $user_id );

	// See if a user has unlocked all achievements of a given type
	badgeos_maybe_trigger_unlock_all( $user_id, $achievement_id );

}
add_action( 'badgeos_award_achievement', 'badgeos_maybe_award_additional_achievements_to_user', 10, 2 );

/**
 * Check if a user has unlocked all achievements of a given type
 *
 * Triggers hook badgeos_unlock_all_{$post_type}
 *
 * @since  1.2.0
 * @param  integer $user_id        The given user's ID
 * @param  integer $achievement_id The given achievement's post ID
 * @return void
 */
function badgeos_maybe_trigger_unlock_all( $user_id = 0, $achievement_id = 0 ) {

	// Grab our user's (presumably updated) earned achievements
	$earned_achievements = badgeos_get_user_achievements( array( 'user_id' => $user_id ) );

	// Get the post type of the earned achievement
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
			do_action( 'badgeos_unlock_all_' . $post_type, $user_id, $achievement_id );
		}
	}
}

/**
 * Check if user may access/earn achievement.
 *
 * @since  1.0.0
 * @param  integer $user_id        The given user's ID
 * @param  integer $achievement_id The given achievement's post ID
 * @param  string $trigger        The trigger
 * @param  integer $site_id        The triggered site id
 * @param  array $args        The triggered args
 * @return bool                    True if user has access, false otherwise
 */
function badgeos_user_has_access_to_achievement( $user_id = 0, $achievement_id = 0, $this_trigger = '', $site_id = '', $args = array() ) {

	// Set to current site id
	if ( ! $site_id ) {
		$site_id = get_current_blog_id();
	}

	// Assume we have access
	$return = true;

	// If the achievement is not published, we do not have access
	if ( 'publish' != get_post_status( $achievement_id ) ) {
		$return = false;
	}

	// If we've exceeded the max earnings, we do not have acces
	if ( $return && badgeos_achievement_user_exceeded_max_earnings( $user_id, $achievement_id ) ) {
		$return = false;
	}

	// If we have access, and the achievement has a parent...
	if ( $return && $parent_achievement = badgeos_get_parent_of_achievement( $achievement_id ) ) {

		// If we don't have access to the parent, we do not have access to this
		if ( ! badgeos_user_has_access_to_achievement( $user_id, $parent_achievement->ID, $this_trigger, $site_id, $args ) ) {
			$return = false;
		}

		// If the parent requires sequential steps, confirm we've earned all previous steps
		if ( $return && badgeos_is_achievement_sequential( $parent_achievement->ID ) ) {
			foreach ( badgeos_get_children_of_achievement( $parent_achievement->ID ) as $sibling ) {
				// If this is the current step, we're good to go
				if ( $sibling->ID == $achievement_id ) {
					break;
				}
				// If we haven't earned any previous step, we can't earn this one
				if ( ! badgeos_get_user_achievements( array( 'user_id' => absint( $user_id ), 'achievement_id' => absint( $sibling->ID ) ) ) ) {
					$return = false;
					break;
				}
			}
		}
	}

	// Available filter for custom overrides
	return apply_filters( 'user_has_access_to_achievement', $return, $user_id, $achievement_id, $this_trigger, $site_id, $args );

}

/**
 * Checks if a user is allowed to work on a given step
 *
 * @since  1.0.0
 * @param  bool    $return   The default return value
 * @param  integer $user_id  The given user's ID
 * @param  integer $step_id  The given step's post ID
 * @return bool              True if user has access to step, false otherwise
 */
function badgeos_user_has_access_to_step( $return = false, $user_id = 0, $step_id = 0 ) {

	// If we're not working with a step, bail here
	if ( 'step' != get_post_type( $step_id ) )
		return $return;

	// Prevent user from earning steps with no parents
	if ( ! $parent_achievement = badgeos_get_parent_of_achievement( $step_id ) )
		$return = false;

	// Prevent user from repeatedly earning the same step
	if ( $return && badgeos_get_user_achievements( array(
			'user_id'        => absint( $user_id ),
			'achievement_id' => absint( $step_id ),
			'since'          => absint( badgeos_achievement_last_user_activity( $parent_achievement->ID, $user_id ) )
		) )
	)
		$return = false;

	// Send back our eligigbility
	return $return;
}
add_filter( 'user_has_access_to_achievement', 'badgeos_user_has_access_to_step', 10, 3 );

/**
 * Validate whether or not a user has completed all requirements for a step.
 *
 * @since  1.0.0
 * @param  bool $return      True if user deserves achievement, false otherwise
 * @param  integer $user_id  The given user's ID
 * @param  integer $step_id  The post ID for our step
 * @return bool              True if user deserves step, false otherwise
 */
function badgeos_user_deserves_step( $return = false, $user_id = 0, $step_id = 0 ) {

	// Only override the $return data if we're working on a step
	if ( 'step' == get_post_type( $step_id ) ) {

		// Get the required number of checkins for the step.
		$minimum_activity_count = absint( get_post_meta( $step_id, '_badgeos_count', true ) );

		// Grab the relevent activity for this step
		$relevant_count = absint( badgeos_get_step_activity_count( $user_id, $step_id ) );

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
 * Count a user's relevant actions for a given step
 *
 * @since  1.0.0
 * @param  integer $user_id The given user's ID
 * @param  integer $step_id The given step's ID
 * @return integer          The total activity count
 */
function badgeos_get_step_activity_count( $user_id = 0, $step_id = 0 ) {

	// Assume the user has no relevant activities
	$activities = array();

	// Grab the requirements for this step
	$step_requirements = badgeos_get_step_requirements( $step_id );

	// Determine which type of trigger we're using and return the corresponding activities
	switch( $step_requirements['trigger_type'] ) {
		case 'specific-achievement' :

			// Get our parent achievement
			$parent_achievement = badgeos_get_parent_of_achievement( $step_id );

			// If the user has any interaction with this achievement, only get activity since that date
			if ( $date = badgeos_achievement_last_user_activity( $parent_achievement->ID, $user_id ) )
				$since = $date;
			else
				$since = 0;

			// Get our achievement activity
			$achievements = badgeos_get_user_achievements( array(
				'user_id'        => absint( $user_id ),
				'achievement_id' => absint( $step_requirements['achievement_post'] ),
				'since'          => $since
			) );
			$activities = count( $achievements );
			break;
		case 'any-achievement' :
			$activities = badgeos_get_user_trigger_count( $user_id, 'badgeos_unlock_' . $step_requirements['achievement_type'] );
			break;
		case 'all-achievements' :
			$activities = badgeos_get_user_trigger_count( $user_id, 'badgeos_unlock_all' . $step_requirements['achievement_type'] );
			break;
		default :
			$activities = badgeos_get_user_trigger_count( $user_id, $step_requirements['trigger_type'] );
			break;
	}

	// Available filter for overriding user activity
	return absint( apply_filters( 'badgeos_step_activity', $activities, $user_id, $step_id ) );
}
