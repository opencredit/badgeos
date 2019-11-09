<?php
/**
 * Ranks Triggers
 *
 * @package Badgeos
 * @subpackage Ranks
 * @author LearningTimes
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com/
 */

/**
 * Helper function for returning our available ranks actvity triggers
 *
 * @return array An array of all our activity triggers stored as 'value' => 'Display Name'
 */
function get_badgeos_ranks_req_activity_triggers() {
	
	$GLOBALS['badgeos']->badgeos_ranks_req_activity_triggers = apply_filters( 'badgeos_ranks_req_activity_triggers',
		array(
			'badgeos_wp_login'     			=> __( 'Log in to Website', 'badgeos' ),
			'badgeos_new_comment'  			=> __( 'Comment on a post', 'badgeos' ),
			'badgeos_specific_new_comment' 	=> __( 'Comment on a specific post', 'badgeos' ),
			'badgeos_new_post'     			=> __( 'Publish a new post', 'badgeos' ),
			'badgeos_new_page'     			=> __( 'Publish a new page', 'badgeos' ),
			'user_register'     			=> __( 'Register to the website', 'badgeos' ),
			'badgeos_daily_visit'     		=> __( 'Daily visit website', 'badgeos' ),
		)
	);
	return $GLOBALS['badgeos']->badgeos_ranks_req_activity_triggers;
}

/**
 * Load up our activity triggers so we can add actions to them
 *
 * @return void
 */
function badgeos_ranks_load_activity_triggers() {

	/**
     * Grab our activity triggers
     */
	$activity_triggers = get_badgeos_ranks_req_activity_triggers();
    
    /**
     * Loop through each trigger and add our trigger event to the hook
     */
	foreach ( $activity_triggers as $trigger => $label ) {
		add_action( $trigger, 'badgeos_ranks_req_trigger_event', 10, 20 );
    }
    
}
add_action( 'init', 'badgeos_ranks_load_activity_triggers' );

/**
 * Handle each of our ranks activity triggers
 *
 * @return mixed
 */
function badgeos_ranks_req_trigger_event() {

	/**
     * Setup all our globals
     */
	global $user_ID, $blog_id, $wpdb;

	$site_id = $blog_id;

	$args = func_get_args();

	/**
     * Grab our current trigger
     */
	$this_trigger = current_filter();

	
	/**
     * Grab the user ID
     */
	$user_id = badgeos_trigger_get_user_id( $this_trigger, $args );
	$user_data = get_user_by( 'id', $user_id );

	/**
     * Sanity check, if we don't have a user object, bail here
     */
	if ( ! is_object( $user_data ) )
		return $args[ 0 ];

	/**
     * If the user doesn't satisfy the trigger requirements, bail here
     */
	if ( ! apply_filters( 'badgeos_user_rank_deserves_trigger', true, $user_id, $this_trigger, $site_id, $args ) )
		return $args[ 0 ];

	/**
     * Now determine if any Achievements are earned based on this trigger event
     */
	$triggered_ranks = $wpdb->get_results( $wpdb->prepare(
							"SELECT p.ID as post_id 
							FROM $wpdb->postmeta AS pm
							INNER JOIN $wpdb->posts AS p ON ( p.ID = pm.post_id AND pm.meta_key = '_rank_trigger_type' ) where p.post_status = 'publish' AND pm.meta_value = %s
							",
							$this_trigger
						) );
	
	if( !empty( $triggered_ranks ) ) {
		foreach ( $triggered_ranks as $rank ) { 
			$parent_id = badgeos_get_parent_id( $rank->post_id );
			if( absint($parent_id) > 0) { 
				$new_count = badgeos_ranks_update_user_trigger_count( $rank->post_id, $parent_id,$user_id, $this_trigger, $site_id, $args );
				badgeos_maybe_award_rank( $rank->post_id,$parent_id,$user_id, $this_trigger, $site_id, $args );
				
			} 
		}
	}
	
	return $args[ 0 ];
}

/**
 * Wrapper function for returning a user's array of credit triggers
 *
 * @param  integer $user_id The given user's ID
 * @param  integer $site_id The desired Site ID to check
 * @return array            An array of the triggers a user has triggered
 */
function badgeos_ranks_get_user_triggers( $step_id, $user_id = 0, $site_id = 0 ) {

	/**
     * Grab all of the user's triggers
     */
	$user_triggers = ( $array_exists = get_user_meta( $user_id, '_badgeos_ranks_triggers', true ) ) ? $array_exists : array();


	/**
     * Use current site ID if site ID is not set, AND not explicitly set to false
     */
	if ( ! $site_id && false !== $site_id ) {
		$site_id = get_current_blog_id();
	}

	/**
     * Return only the triggers that are relevant to the provided $site_id
     */
	if ( $site_id && isset( $user_triggers[ $site_id ] ) ) {
		return $user_triggers[ $site_id ];
	} else {
		return $user_triggers;
	}
}

/**
 * Get the count for the number of times a user has triggered a particular credit trigger
 *
 * @param  integer $user_id The given user's ID
 * @param  string  $trigger The given trigger we're checking
 * @param  integer $site_id The desired Site ID to check
 * @param  array $args        The triggered args
 * @return integer          The total number of times a user has triggered the trigger
 */
function ranks_get_user_trigger_count( $step_id, $user_id, $trigger, $site_id = 0, $args = array() ) {

	/**
     * Set to current site id
     */
	if ( ! $site_id )
		$site_id = get_current_blog_id();

	/**
     * Grab the user's logged triggers
     */
	$user_triggers = badgeos_ranks_get_user_triggers( $step_id, $user_id, $site_id );

	$trigger = apply_filters( 'ranks_get_user_trigger_name', $trigger, $user_id, $site_id, $step_id, $args );

	/**
     * If we have any triggers, return the current count for the given trigger
     */
	if ( ! empty( $user_triggers ) && isset( $user_triggers[$trigger."_".$step_id] ) ) {
        return absint( $user_triggers[$trigger."_".$step_id] );
    } else {
        return 0;
    }

}
 
/**
 * Update the user's credit trigger count for a given trigger by 1
 *
 * @param  integer $step_id The given step ID
 * @param  integer $rank_id The given rank ID
 * @param  integer $user_id The given user's ID
 * @param  string  $trigger The trigger we're updating
 * @param  integer $site_id The desired Site ID to update
 * @param  array   $args    The triggered args
 * @return integer          The updated trigger count
 */

function badgeos_ranks_update_user_trigger_count( $step_id, $rank_id, $user_id, $trigger, $site_id = 0, $args = array() ) {

    /**
     * Set to current site id
     */
	if ( ! $site_id )
		$site_id = get_current_blog_id();

	/**
     * Grab the current count and increase it by 1
     */
	$trigger_count = absint( ranks_get_user_trigger_count( $step_id, $user_id, $trigger, $site_id, $args ) );

	$increment = 1;
	if( $increment = apply_filters( 'badgeos_ranks_update_user_trigger_count', $increment, $user_id, $step_id, $rank_id, $trigger, $site_id, $args ) )
		$trigger_count += (int) $increment;
	else	
		return $increment;


	/**
     * Update the triggers arary with the new count
     */
	$user_triggers = badgeos_ranks_get_user_triggers( $step_id, $user_id, false  );

	$user_triggers[$site_id][$trigger."_".$step_id] = $trigger_count;

	update_user_meta( $user_id, '_badgeos_ranks_triggers', $user_triggers );

	/**
     * Send back our trigger count for other purposes
     */
	return $trigger_count;
}