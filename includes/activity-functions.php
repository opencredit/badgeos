<?php
/**
 * Achievement Activity Functions
 *
 * @package BadgeOS
 * @subpackage Achievements
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Get the UNIX timestamp for the last activity on an achievement for a given user
 *
 * @since  1.0.0
 *
 * @param  integer $achievement_id  The given achievements post ID
 * @param  integer $user_id  		The given user's ID
 * @return integer           		The UNIX timestamp for the last reported badge activity
 */
function badgeos_achievement_last_user_activity( $achievement_id = 0, $user_id = 0 ) {

	// Assume the user has no history with this achievement
	$since = 0;

	// Attempt to grab the last activity date from active achievement meta
	if ( $achievement = badgeos_user_get_active_achievement( $user_id, $achievement_id ) ) {
		$since = $achievement->date_started - 1;

	// Otherwise, attempt to grab the achievement date from earned achievement meta
	} elseif ( $achievements = badgeos_get_user_achievements( array( 'user_id' => $user_id, 'achievement_id' => $achievement_id ) ) ) {
		$achievement = array_pop( $achievements );
		if ( is_object( $achievement ) )
			$since = $achievement->date_earned + 1;
	}

	// Finally, return our time
	return $since;

}

/**
 * Get a user's active achievements
 *
 * @since  1.2.0
 *
 * @param  integer $user_id User ID
 * @return array            An array of the user's active achievements
 */
function badgeos_user_get_active_achievements( $user_id ) {

	// Get the user's active achievements from meta
	$achievements = get_user_meta( $user_id, '_badgeos_active_achievements', true );

	// If there are no achievements
	if ( empty( $achievements ) )
		return array();

	// Otherwise, we DO have achievements and should return them cast as an array
	return (array) $achievements;
}

/**
 * Update a user's active achievements
 *
 * @since  1.2.0
 *
 * @param  integer $user_id      User ID
 * @param  array   $achievements An array of achievements to pass to meta
 * @param  boolean $update       True to update to exsiting active achievements, false to replace entire array (Default: false)
 * @return array                 The updated achievements array
 */
function badgeos_user_update_active_achievements( $user_id = 0, $achievements = array(), $update = false ) {

	// If we're not replacing, append the passed array to our existing array
	if ( true == $update ) {
		$existing_achievements = badgeos_user_get_active_achievements( $user_id );
		$achievements = (array) $achievements + (array) $existing_achievements;
	}

	// Update the user's active achievements meta
	update_user_meta( $user_id, '_badgeos_active_achievements', $achievements );

	// Return our updated achievements array
	return (array) $achievements;
}

/**
 * Get a user's active achievement details
 *
 * @since  1.2.0
 *
 * @param  integer $user_id        User ID
 * @param  integer $achievement_id Achievement post ID
 * @return mixed                   An achievement object if it exists, false if not
 */
function badgeos_user_get_active_achievement( $user_id = 0, $achievement_id = 0 ) {

	// Get the user's active achievements
	$achievements = badgeos_user_get_active_achievements( $user_id );

	// Return the achievement if it exists, or false if not
	return ( isset( $achievements[$achievement_id] ) ) ? $achievements[$achievement_id] : false;
}

/**
 * Add an achievement to a user's active achievements
 *
 * @since  1.2.0
 *
 * @param  integer $user_id        User ID
 * @param  integer $achievement_id Achievement post ID
 * @return object                  The active Achievement object
 */
function badgeos_user_add_active_achievement( $user_id = 0, $achievement_id = 0 ) {

	// If achievement is a step, bail here
	if ( 'step' == get_post_type( $achievement_id ) )
		return false;

	// Get the user's active achievements
	$achievements = badgeos_user_get_active_achievements( $user_id );

	// If it doesn't exist, add the achievement to the array
	if ( ! isset( $achievements[$achievement_id] ) ) {
		$achievements[$achievement_id] = badgeos_build_achievement_object( $achievement_id, 'started' );
		badgeos_user_update_active_achievements( $user_id, $achievements );
	}

	// Send back the added achievement object
	return $achievements[$achievement_id];
}

/**
 * Update the stored data for an active achievement
 *
 * @since  1.2.0
 *
 * @param  integer $user_id        User ID
 * @param  integer $achievement_id Achievement post ID
 * @param  object  $achievement    Achievement object to insert into user meta
 * @return object                  The final updated achievement object
 */
function badgeos_user_update_active_achievement( $user_id = 0, $achievement_id = 0, $achievement = null ) {

	// If achievement is a step, bail here
	if ( 'step' == get_post_type( $achievement_id ) )
		return false;

	// If we weren't passed an object, get the latest version from meta
	if ( ! is_object( $achievement ) )
		$achievement = badgeos_user_get_active_achievement( $user_id, $achievement_id );

	// If we still don't have an object, build one
	if ( ! is_object( $achievement ) )
		$achievement = badgeos_build_achievement_object( $achievement_id, 'started' );

	// Update our last activity date
	$achievement->last_activity_date = time();

	// Available filter for manipulating the achievement object
	$achievement = apply_filters( 'badgeos_user_update_active_achievement', $achievement, $user_id, $achievement_id );

	// Update the user's active achievements
	badgeos_user_update_active_achievements( $user_id, array( $achievement_id => $achievement ), true );

	// Return the updated achievement object
	return $achievement;
}

/**
 * Remove an achievement from a user's list of active achievements
 *
 * @since  1.2.0
 *
 * @param  integer $user_id        User ID
 * @param  integer $achievement_id Achievement post ID
 * @return array                   The user's active achievements
 */
function badgeos_user_delete_active_achievement( $user_id = 0, $achievement_id = 0 ) {

	// Get the user's active achievements
	$achievements = badgeos_user_get_active_achievements( $user_id );

	// If the achievement exists, unset it
	if ( isset( $achievements[$achievement_id] ) )
		unset( $achievements[$achievement_id] );

	// Update the user's active achievements
	return badgeos_user_update_active_achievements( $user_id, $achievements );
}

/**
 * Update the user's active achievement meta with each earned achievement
 *
 * @since  1.2.0
 *
 * @param  integer $user_id         The given user's ID
 * @param  integer $achievement_id  The given achievement's post ID
 * @return object                   The final achievement object
 */
function badgeos_user_update_active_achievement_on_earnings( $user_id, $achievement_id ) {

	// If achievement is a step, update its parent activity
	if ( 'step' == get_post_type( $achievement_id ) ) {
		$parent_achievement = badgeos_get_parent_of_achievement( $achievement_id );
		if ( $parent_achievement ) {
			badgeos_user_update_active_achievement( $user_id, $parent_achievement->ID );
		}

	// Otherwise, drop the earned achievement form the user's active achievement array
	} else {
		badgeos_user_delete_active_achievement( $user_id, $achievement_id );
	}

}
add_action( 'badgeos_award_achievement', 'badgeos_user_update_active_achievement_on_earnings', 10, 2 );
