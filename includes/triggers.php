<?php
/**
 * Activity Triggers, used for triggering achievement earning
 *
 * @package BadgeOS
 * @subpackage Achievements
 * @author LearningTimes
 * @license http://www.opensource.org/licenses/gpl-license.php GPL v2.0 (or later)
 * @link http://www.LearningTimes.com
 */

/**
 * Helper function for returning our available actvity triggers
 *
 * @since  1.0.0
 * @return array An array of all our activity triggers stored as 'value' => 'Display Name'
 */
function badgeos_get_activity_triggers() {
	global $badgeos;

	$badgeos->activity_triggers = apply_filters( 'badgeos_activity_triggers',
		array(
			// WordPress-specific
			'wp_login'             => __( 'Logged-in to Website', 'badgeos' ),
			'comment_post'         => __( 'Commented on a post', 'badgeos' ),

			// BadgeOS-specific
			'specific-achievement' => __( 'Specific Achievement of Type', 'badgeos' ),
			'any-achievement'      => __( 'Any Achievement of Type', 'badgeos' ),
			'all-achievements'     => __( 'All Achievements of Type', 'badgeos' ),
		)
	);

	return $badgeos->activity_triggers;
}

/**
 * Load up our activity triggers so we can add actions to them
 *
 * @since 1.0.0
 */
function badgeos_load_activity_triggers() {

	// Grab our activity triggers
	$activity_triggers = badgeos_get_activity_triggers();

	// Loop through each achievement type and add triggers for unlocking them
	foreach ( badgeos_get_achievement_types_slugs() as $achievement_type ) {

		// Grab the post type object, and bail if it's not actually an object
		$post_type_object = get_post_type_object( $achievement_type );
		if ( ! is_object( $post_type_object ) )
			continue;

		// Add trigger for unlocking ANY and ALL posts for each achievement type
		$activity_triggers['badgeos_unlock_'.$achievement_type] = sprintf( __( 'Unlocked a %s', 'badgeos' ), $post_type_object->labels->singular_name );
		$activity_triggers['badgeos_unlock_all_'.$achievement_type] = sprintf( __( 'Unlocked all %s', 'badgeos' ), $post_type_object->labels->name );

	}

	// Loop through each trigger and add our trigger event to the hook
	foreach ( $activity_triggers as $trigger => $label )
		add_action( $trigger, 'badgeos_trigger_event', 10, 10 );

}
add_action( 'init', 'badgeos_load_activity_triggers' );

/**
 * Handle each of our activity triggers
 *
 * @since 1.0.0
 * @param mixed $args Args that are passed through from the hook (only relevant for the wp_login hook presently)
 */
function badgeos_trigger_event( $args ) {

	// Setup all our globals
	global $user_ID, $blog_id, $wpdb;

	// Grab our current trigger
	$this_trigger = current_filter();

	// Special case: when logging in (which is an activity trigger event),
	// global $user_ID is not yet available so it must be gotten from the
	// user login name that IS passed to this function.
	if ( 'wp_login' == $this_trigger ) {
		$user_data = get_user_by( 'login', $args );
		$user_id = $user_data->ID;
	} else {
		$user_data = get_user_by( 'id', $user_ID );
		$user_id = $user_ID;
	}

	// Update hook count for this user
	$new_count = badgeos_update_user_trigger_count( $user_id, $this_trigger, $blog_id );

	// Mark the count in the log entry
	badgeos_post_log_entry( null, $user_id, null, "{$user_data->user_login} triggered $this_trigger ({$new_count}x)" );

	// Now determine if any badges are earned based on this trigger event
	$triggered_achievements = $wpdb->get_results( $wpdb->prepare(
		"
		SELECT post_id
		FROM   $wpdb->postmeta
		WHERE  meta_key = '_badgeos_trigger_type'
		       AND meta_value = %s
		",
		$this_trigger
	) );
	foreach ( $triggered_achievements as $achievement ) {
		badgeos_maybe_award_achievement_to_user( $achievement->post_id, $user_id );
	}

}

/**
 * Wrapper function for returning a user's array of sprung triggers
 *
 * @since  1.0.0
 * @param  integer $user_id The given user's ID
 * @param  integer $site_id The desired Site ID to check
 * @return array            An array of the triggers a user has triggered
 */
function badgeos_get_user_triggers( $user_id = 0, $site_id = 0 ) {

	// Grab all of the user's triggers
	$user_triggers = ( $array_exists = get_user_meta( $user_id, '_badgeos_triggered_triggers', true ) ) ? $array_exists : array( $site_id => array() );

	// Return only the triggers that are relevant to the provided $site_id
	if ( $site_id )
		return $user_triggers[$site_id];

	// Otherwise, return the full array of all triggers across all sites
	else
		return $user_triggers;
}

/**
 * Get the count for the number of times a user has triggered a particular trigger
 *
 * @since  1.0.0
 * @param  integer $user_id The given user's ID
 * @param  string  $trigger The given trigger we're checking
 * @return integer          The total number of times a user has triggered the trigger
 */
function badgeos_get_user_trigger_count( $user_id, $trigger, $site_id = 1 ) {

	// Grab the user's logged triggers
	$user_triggers = badgeos_get_user_triggers( $user_id, $site_id );

	// If we have any triggers, return the current count for the given trigger
	if ( ! empty( $user_triggers ) && isset( $user_triggers[$trigger] ) )
		return absint( $user_triggers[$trigger] );

	// Otherwise, they've never hit the trigger
	else
		return 0;

}

/**
 * Update the user's trigger count for a given trigger by 1
 *
 * @since  1.0.0
 * @param  integer $user_id The given user's ID
 * @param  string  $trigger The trigger we're updating
 * @param  integer $site_id The desired Site ID to update
 * @return integer          The updated trigger count
 */
function badgeos_update_user_trigger_count( $user_id, $trigger, $site_id = 1 ) {

	// Grab the current count and increase it by 1
	$trigger_count = absint( badgeos_get_user_trigger_count( $user_id, $trigger, $site_id ) );
	$trigger_count++;

	// Update the triggers arary with the new count
	$user_triggers = badgeos_get_user_triggers( $user_id );
	$user_triggers[$site_id][$trigger] = $trigger_count;
	update_user_meta( $user_id, '_badgeos_triggered_triggers', $user_triggers );

	// Send back our trigger count for other purposes
	return $trigger_count;

}

/**
 * Reset a user's trigger count for a given trigger to 0 or reset ALL triggers
 *
 * @since  1.0.0
 * @param  integer $user_id The given user's ID
 * @param  string  $trigger The trigger we're updating (or "all" to dump all triggers)
 * @param  integer $site_id The desired Site ID to update (or "all" to dump across all sites)
 * @return integer          The updated trigger count
 */
function badgeos_reset_user_trigger_count( $user_id, $trigger, $site_id = 1 ) {

	// Grab the user's current triggers
	$user_triggers = badgeos_get_user_triggers( $user_id );

	// If we're deleteing all triggers...
	if ( 'all' == $trigger ) {
		// For all sites
		if ( 'all' == $site_id )
			$user_triggers = array();
		// For a specific site
		else
			$user_triggers[$site_id] = array();
	// Otherwise, reset the specific trigger back to zero
	} else {
		$user_triggers[$site_id][$trigger] = 0;
	}

	// Finally, update our user meta
	update_user_meta( $user_id, '_badgeos_triggered_triggers', $user_triggers );

}