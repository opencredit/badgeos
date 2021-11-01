<?php
/**
 * Activity Triggers, used for triggering achievement earning
 *
 * @package BadgeOS
 * @subpackage Achievements
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
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
            'badgeos_wp_login'             => __( 'Log in to Website', 'badgeos' ),
			'badgeos_wp_login_x_days'      => __( 'Log in for X days', 'badgeos' ),
            'badgeos_wp_not_login'         => __( 'Not Login for X days', 'badgeos' ),
			'badgeos_new_comment'          => __( 'Comment on a post', 'badgeos' ),
			'badgeos_specific_new_comment' => __( 'Comment on a specific post', 'badgeos' ),
			'badgeos_new_post'             => __( 'Publish a new post', 'badgeos' ), 
			'badgeos_visit_a_post'         => __( 'Visit a Post', 'badgeos' ),
			'badgeos_award_author_on_visit_post'   => __( 'Award author when a user visits post', 'badgeos' ),
			'badgeos_new_page'             => __( 'Publish a new page', 'badgeos' ),
			'badgeos_visit_a_page'         => __( 'Visit a Page', 'badgeos' ),
			'badgeos_award_author_on_visit_page'   => __( 'Award author when a user visits page', 'badgeos' ),
			'user_register'     	=> __( 'Register to the website', 'badgeos' ),
			'badgeos_daily_visit'   => __( 'Daily visit website', 'badgeos' ),
            'badgeos_on_completing_num_of_day' => __( 'After completing the number of days', 'badgeos' ),
            'badgeos_on_completing_num_of_month' => __( 'After completing the number of months', 'badgeos' ),
			'badgeos_on_completing_num_of_year' => __( 'After completing the number of years', 'badgeos' ),
			// BadgeOS-specific
			'specific-achievement' => __( 'Specific Achievement of Type', 'badgeos' ),
			'any-achievement'      => __( 'Any Achievement of Type', 'badgeos' ),
			'all-achievements'     => __( 'All Achievements of Type', 'badgeos' ),
		)
	);

    return apply_filters( 'badgeos_activity_triggers_for_all', $badgeos->activity_triggers );
}

/**
 * Load up our activity triggers so we can add actions to them
 *
 * @since 1.0.0
 * @return void
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
    foreach ( $activity_triggers as $trigger => $trigger_data ) {
        if( is_array( $trigger_data ) ) {
            if( count( $trigger_data['sub_triggers'] ) > 0 ) {
                foreach( $trigger_data['sub_triggers'] as $tdata ) {
                    add_action( trim( $tdata['trigger'] ), 'badgeos_dynamic_trigger_event', 10, 20 );
                }
            }
        } else {
            add_action( $trigger, 'badgeos_trigger_event', 10, 20 );
        }
    }
}
add_action( 'init', 'badgeos_load_activity_triggers' );

/**
 * Handle each of our activity triggers
 *
 * @since 1.0.0
 * @return mixed
 */
function badgeos_dynamic_trigger_event() {

    // Setup all our globals
    global $user_ID, $blog_id, $wpdb;

    $site_id = $blog_id;

    $args = func_get_args();

    // Grab our current trigger
    $this_trigger = current_filter();

    // Grab the user ID
    $user_id = $args[0];
    if( intval( $user_id ) < 1 ) {
        $user_id = get_current_user_id();
    }
    $user_data = get_user_by( 'id', $user_id );

    // Sanity check, if we don't have a user object, bail here
    if ( ! is_object( $user_data ) ) {
        return $args[ 0 ];
    }

    // If the user doesn't satisfy the trigger requirements, bail here
    if ( ! apply_filters( 'badgeos_user_deserves_trigger', true, $user_id, $this_trigger, $site_id, $args ) ) {
        return $args[ 0 ];
    }

    // Now determine if any badges are earned based on this trigger event
    $triggered_achievements = $wpdb->get_results( $wpdb->prepare( "SELECT pm.post_id FROM $wpdb->postmeta as pm inner join $wpdb->posts as p on( pm.post_id = p.ID ) WHERE p.post_status = 'publish' and pm.meta_key = '_badgeos_subtrigger_value' AND pm.meta_value = %s", $this_trigger) );

    $is_any_or_all_trigger = false;
    if ( 'badgeos_unlock_all_' == substr( $this_trigger, 0, 19 ) ) {
        $is_any_or_all_trigger = true;
    } else if ( 'badgeos_unlock_' == substr( $this_trigger, 0, 15 ) ) {
        $is_any_or_all_trigger = true;
    }

    if( count( $triggered_achievements ) > 0 || $is_any_or_all_trigger == true ) {
        // Update hook count for this user
        $new_count = badgeos_update_user_trigger_count( $user_id, $this_trigger, $site_id, $args );

        // Mark the count in the log entry
        badgeos_post_log_entry( null, $user_id, null, sprintf( __( '%1$s triggered %2$s (%3$dx)', 'badgeos' ), $user_data->user_login, $this_trigger, $new_count ) );
    }

    foreach ( $triggered_achievements as $achievement ) {

        $parents = badgeos_get_achievements( array( 'parent_of' => $achievement->post_id ) );

        if( count( $parents ) > 0 ) {

            if( $parents[0]->post_status == 'publish' ) {

                $step_params = badgeos_utilities::get_post_meta( $achievement->post_id, '_badgeos_fields_data', true );
                $step_params = badgeos_extract_array_from_query_params( $step_params );

                if( apply_filters("badgeos_check_dynamic_trigger_filter", true, 'achivement', $this_trigger, $step_params, $args[ 1 ]) ) {
                    badgeos_maybe_award_achievement_to_user( $achievement->post_id, $user_id, $this_trigger, $site_id, $args );
                }
            } 
        }
    }

return $args[ 0 ];

}

/**
 * Converts query string parameter to array of key/value pair
 *
 * @since 1.0.0
 * @return mixed
 */
function badgeos_extract_array_from_query_params( $params ) {

    $final_data = array();
    $raw_data = explode( '&', $params );
    foreach( $raw_data as $item ) {
        $data = explode( "=", $item );
        $final_data[ $data[0] ] = $data[1];
    }

    return $final_data;
}

/**
 * Handle each of our activity triggers
 *
 * @since 1.0.0
 * @return mixed
 */
function badgeos_trigger_event() {
	
	// Setup all our globals
	global $user_ID, $blog_id, $wpdb;
	
	$site_id = $blog_id;

	$args = func_get_args();

	// Grab our current trigger
	$this_trigger = current_filter();

	// Grab the user ID
	$user_id = badgeos_trigger_get_user_id( $this_trigger, $args );

    if( 'all-achievements' == $this_trigger || 'any-achievement' == $this_trigger) {
        $user_id = $args[ 0 ];
    }

    $user_data = get_user_by( 'id', $user_id );

	// Sanity check, if we don't have a user object, bail here
    if ( ! is_object( $user_data ) ) {
        return $args[ 0 ];
    }

    // If the user doesn't satisfy the trigger requirements, bail here
    if ( ! apply_filters( 'badgeos_user_deserves_trigger', true, $user_id, $this_trigger, $site_id, $args ) ) {
        return $args[ 0 ];
    }

    // Now determine if any badges are earned based on this trigger event
    $triggered_achievements = $wpdb->get_results( $wpdb->prepare( "SELECT pm.post_id FROM $wpdb->postmeta as pm inner join $wpdb->posts as p on( pm.post_id = p.ID ) WHERE p.post_status = 'publish' and pm.meta_key = '_badgeos_trigger_type' AND pm.meta_value = %s", $this_trigger) );
	
    $is_any_or_all_trigger = false;
    if ( 'badgeos_unlock_all_' == substr( $this_trigger, 0, 19 ) ) {
        $is_any_or_all_trigger = true;
    } else if ( 'badgeos_unlock_' == substr( $this_trigger, 0, 15 ) ) {
        $is_any_or_all_trigger = true;
    }
	
	$specific_triggers = [ 'badgeos_visit_a_post', 'badgeos_visit_a_page', 'badgeos_award_author_on_visit_post', 'badgeos_award_author_on_visit_page' ]; 
	if( count( $triggered_achievements ) > 0 || $is_any_or_all_trigger == true ) {
		// Update hook count for this user
		if( ! in_array( $this_trigger, $specific_triggers) ) {
			$new_count = badgeos_update_user_trigger_count( $user_id, $this_trigger, $site_id, $args );
			
			// Mark the count in the log entry
			badgeos_post_log_entry( null, $user_id, null, sprintf( __( '%1$s triggered %2$s (%3$dx)', 'badgeos' ), $user_data->user_login, $this_trigger, $new_count ) );
		}
	}
	$not_triggerred = true;
	foreach ( $triggered_achievements as $achievement ) {
        $parents = badgeos_get_achievements( array( 'parent_of' => $achievement->post_id ) );
        if( count( $parents ) > 0 ) {
			if( $parents[0]->post_status == 'publish' ) {
				$new_trigger_label = $this_trigger;
				
				if( $not_triggerred ) {
					if( in_array( $this_trigger, $specific_triggers ) ) {
					
						$new_count = badgeos_update_user_trigger_count( $user_id, $this_trigger, $site_id, $args );
						
						// Mark the count in the log entry
						badgeos_post_log_entry( null, $user_id, null, sprintf( __( '%1$s triggered %2$s (%3$dx)', 'badgeos' ), $user_data->user_login, $this_trigger, $new_count ) );
			
					}

					$not_triggerred = false;
				}
				
				
				badgeos_maybe_award_achievement_to_user( $achievement->post_id, $user_id, $this_trigger, $site_id, $args );
            }
        }
    }

	return $args[ 0 ];

}

/**
 * Get user for a given trigger action.
 *
 * @since  1.3.4
 *
 * @param  string  $trigger Trigger name.
 * @param  array   $args    Passed trigger args.
 * @return integer          User ID.
 */
function badgeos_trigger_get_user_id( $trigger = '', $args = array() ) {

	switch ( $trigger ) {
		case 'badgeos_wp_login' :
        case 'badgeos_wp_login_x_days':
        case 'badgeos_wp_not_login':
			$user_data = get_user_by( 'login', $args[ 0 ] );
			$user_id = $user_data->ID;
			break;
		case 'badgeos_unlock_' == substr( $trigger, 0, 15 ) :
			$user_id = $args[0];
			break;
		case 'badgeos_daily_visit':
			$user_data = get_user_by( 'login', $args[ 0 ] );
			$user_id = $user_data->ID;
			break;
		case 'user_register':
			$user_id = $args[0]; 
			break;	
		case 'badgeos_new_post' :
		case 'badgeos_new_page' :
		case 'badgeos_new_comment' :
		case 'badgeos_specific_new_comment' :
			$user_id = $args[1];
			break;
		case 'badgeos_points_on_birthday':
        case 'badgeos_on_completing_num_of_day':
        case 'badgeos_on_completing_num_of_month':
		case 'badgeos_on_completing_num_of_year':
		case 'badgeos_award_author_on_visit_post': 
		case 'badgeos_award_author_on_visit_page': 
		case 'badgeos_visit_a_post': 
		case 'badgeos_visit_a_page':
			$user_id = $args[0];
			break;	
		default :
			$user_id = get_current_user_id();
			break;
	}

	return apply_filters( 'badgeos_trigger_get_user_id', $user_id, $trigger, $args );
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
	$user_triggers = ( $array_exists = badgeos_utilities::get_user_meta( $user_id, '_badgeos_triggered_triggers', true ) ) ? $array_exists : array( $site_id => array() );

	// Use current site ID if site ID is not set, AND not explicitly set to false
	if ( ! $site_id && false !== $site_id ) {
		$site_id = get_current_blog_id();
	}

	// Return only the triggers that are relevant to the provided $site_id
	if ( $site_id && isset( $user_triggers[ $site_id ] ) ) {
		return $user_triggers[ $site_id ];

	// Otherwise, return the full array of all triggers across all sites
	} else {
		return $user_triggers;
	}
}

/**
 * Get the count for the number of times a user has triggered a particular trigger
 *
 * @since  1.0.0
 * @param  integer $user_id The given user's ID
 * @param  string  $trigger The given trigger we're checking
 * @param  integer $site_id The desired Site ID to check
 * @param  array $args        The triggered args
 * @return integer          The total number of times a user has triggered the trigger
 */
function badgeos_get_user_trigger_count( $user_id, $trigger, $site_id = 0, $args = array() ) {

	// Set to current site id
	if ( ! $site_id )
		$site_id = get_current_blog_id();

	// Grab the user's logged triggers
	$user_triggers = badgeos_get_user_triggers( $user_id, $site_id );

	$trigger = apply_filters( 'badgeos_get_user_trigger_name', $trigger, $user_id, $site_id, $args );

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
 * @param  array $args        The triggered args
 * @return integer          The updated trigger count
 */
function badgeos_update_user_trigger_count( $user_id, $trigger, $site_id = 0, $args = array() ) {

	// Set to current site id
	if ( ! $site_id )
		$site_id = get_current_blog_id();

	// Grab the current count and increase it by 1
	$trigger_count = absint( badgeos_get_user_trigger_count( $user_id, $trigger, $site_id, $args ) );
	$trigger_count += (int) apply_filters( 'badgeos_update_user_trigger_count', 1, $user_id, $trigger, $site_id, $args );

	// Update the triggers arary with the new count
	$user_triggers = badgeos_get_user_triggers( $user_id, false );
	$user_triggers[$site_id][$trigger] = $trigger_count;

    if( ! isset( $user_triggers[$site_id]['all-achievements'] ) )
        $user_triggers[$site_id]['all-achievements'] = 0;
    if( ! isset( $user_triggers[$site_id]['any-achievement'] ) )
        $user_triggers[$site_id]['any-achievement'] = 0;

	badgeos_utilities::update_user_meta( $user_id, '_badgeos_triggered_triggers', $user_triggers );

	// Send back our trigger count for other purposes
	return $trigger_count;

}

/**
 * decrement the user's trigger count
 *
 * @since  1.0.0
 * @param  integer $user_id The given user's ID
 * @param  string  $trigger The trigger we're updating
 * @param  integer $site_id The desired Site ID to update
 * @param  array $args        The triggered args
 * @return integer          The updated trigger count
 */
function badgeos_decrement_user_trigger_count( $user_id, $step_id, $del_ach_id ) {

    // Set to current site id
    $site_id = get_current_blog_id();


    $args = array();

    $times 			= absint( badgeos_utilities::get_post_meta( $step_id, '_badgeos_count', true ) );
    $trigger 		= badgeos_utilities::get_post_meta( $step_id, '_badgeos_trigger_type', true );

    // Grab the current count and increase it by 1
    $trigger_count = absint( badgeos_get_user_trigger_count( $user_id, $trigger, $site_id, $args ) );
    $trigger_count -= (int) apply_filters( 'badgeos_decrement_user_trigger_count', $times, $user_id, $step_id, $trigger, $site_id, $args );

    if( $trigger_count < 0 )
        $trigger_count = 0;

    // Update the triggers arary with the new count
    $user_triggers = badgeos_get_user_triggers( $user_id, false );
    $user_triggers[$site_id][$trigger] = $trigger_count;
    badgeos_utilities::update_user_meta( $user_id, '_badgeos_triggered_triggers', $user_triggers );

    do_action( 'badgeos_decrement_user_trigger_count', $user_id, $step_id, $trigger, $del_ach_id, $site_id );

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
function badgeos_reset_user_trigger_count( $user_id, $trigger, $site_id = 0 ) {

	// Set to current site id
	if ( ! $site_id )
		$site_id = get_current_blog_id();

	// Grab the user's current triggers
	$user_triggers = badgeos_get_user_triggers( $user_id, false );

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
	badgeos_utilities::update_user_meta( $user_id, '_badgeos_triggered_triggers', $user_triggers );

}

/**
 * Listener function for post/page publishing
 *
 * This triggers a separate hook, badgeos_new_{$post_type},
 * only if the published content is brand new
 *
 * @since  1.1.0
 * @param  integer $post_id The post ID
 * @return void
 */
function badgeos_publish_listener( $post_id = 0 ) {

	// Bail if we're not intentionally saving a post
	if (
		defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE // If we're autosaving,
		|| wp_is_post_revision( $post_id )            // or this is a revision
	)
		return;

	// Bail if we have more than the single, ititial revision
	$revisions = wp_get_post_revisions( $post_id );
	if ( count( $revisions ) > 1 )
		return;

	// Trigger a badgeos_new_{$post_type} action
	$post = badgeos_utilities::badgeos_get_post( $post_id );
	do_action( "badgeos_new_{$post->post_type}", $post_id, $post->post_author, $post );

}
add_action( 'publish_post', 'badgeos_publish_listener', 0 );
add_action( 'publish_page', 'badgeos_publish_listener', 0 );

/**
 * Listener function for comment publishing
 *
 * This triggers separate hooks: badgeos_new_comment, badgeos_specific_new_comment
 *
 * @since  1.4.0
 * @param  integer $comment_ID The comment ID
 * @param  array|object $comment The comment array
 * @return void
 */
function badgeos_approved_comment_listener( $comment_ID, $comment ) {

    global $wpdb;

	// Enforce array for both hooks (wp_insert_comment uses object, comment_{status}_comment uses array)
	if ( is_object( $comment ) ) {
		$comment = get_object_vars( $comment );
	}

	if ( get_user_meta( (int) $comment[ 'user_id' ], 'comment_approved_'.$comment_ID, true ) == $comment_ID ) {
		return;
	}

	// Check if comment is approved
	if ( 1 != (int) $comment[ 'comment_approved' ] ) {
		return;
	}
	
	$trigger_data = $wpdb->get_results( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE ( meta_key = '_badgeos_trigger_type' or meta_key = '_point_trigger_type' or meta_key = '_deduct_trigger_type' or meta_key = '_rank_trigger_type' ) AND meta_value = 'badgeos_specific_new_comment'" );

    if( $trigger_data ) {
        foreach( $trigger_data as $data ) {
            $post_specific_id = badgeos_utilities::get_post_meta( absint( $data->post_id ), '_badgeos_achievement_post', true );
            if( absint( $post_specific_id ) == absint($comment[ 'comment_post_ID' ]) ) {
                do_action( 'badgeos_specific_new_comment', (int) $comment_ID, (int) $comment[ 'user_id' ], $comment[ 'comment_post_ID' ], $comment );
                break;
            }
        }
    }

	do_action( 'badgeos_new_comment', (int) $comment_ID, (int) $comment[ 'user_id' ], $comment[ 'comment_post_ID' ], $comment );
	update_user_meta( (int) $comment[ 'user_id' ], 'comment_approved_'.$comment_ID, (int) $comment_ID );
}
add_action( 'comment_approved_comment', 'badgeos_approved_comment_listener', 0, 2 );
add_action( 'wp_insert_comment', 'badgeos_approved_comment_listener', 0, 2 );

/**
 * Listner function for login trigger
 *
 * @param $user_login
 * @param $user
 */
function badgeos_login_trigger( $user_login, $user ) {

    if( empty( $user_login ) ) {
        return;
    }

    if( !is_object( $user ) || is_null( $user ) || empty( $user ) ) {
        return;
	}
	global $wpdb;
    $user_id = intval( $user->ID );
    do_action( 'badgeos_wp_not_login', $user_login, $user );
    do_action( 'badgeos_wp_login', $user_login, $user );
    do_action( 'badgeos_wp_login_x_days', $user_login, $user );

	badgeos_utilities::update_user_meta( $user_id, '_badgeos_last_login', time() );
	$trigger_data = $wpdb->get_results( "SELECT p.ID as post_id FROM $wpdb->postmeta AS pm INNER JOIN $wpdb->posts AS p ON ( p.ID = pm.post_id AND pm.meta_key = '_point_trigger_type' ) where p.post_status = 'publish' AND pm.meta_value = 'badgeos_points_on_birthday'" );
	if( count( $trigger_data ) > 0 ) { 
		do_action( 'badgeos_points_on_birthday', $user_id, $user );
	}

	$num_of_years_data = $wpdb->get_results( "SELECT p.ID as post_id FROM $wpdb->postmeta AS pm INNER JOIN $wpdb->posts AS p ON ( p.ID = pm.post_id ) where  ( pm.meta_key = '_badgeos_trigger_type' or pm.meta_key = '_point_trigger_type' or pm.meta_key = '_deduct_trigger_type' or pm.meta_key = '_rank_trigger_type' ) and  p.post_status = 'publish' AND pm.meta_value = 'badgeos_on_completing_num_of_year'" );
	if( count( $num_of_years_data ) > 0 ) { 
		do_action( 'badgeos_on_completing_num_of_year', $user_id, $user );
	}

    $num_of_months_data = $wpdb->get_results( "SELECT p.ID as post_id FROM $wpdb->postmeta AS pm INNER JOIN $wpdb->posts AS p ON ( p.ID = pm.post_id ) where  ( pm.meta_key = '_badgeos_trigger_type' or pm.meta_key = '_point_trigger_type' or pm.meta_key = '_deduct_trigger_type' or pm.meta_key = '_rank_trigger_type' ) and  p.post_status = 'publish' AND pm.meta_value = 'badgeos_on_completing_num_of_month'" );
    if( count( $num_of_months_data ) > 0 ) { 
        do_action( 'badgeos_on_completing_num_of_month', $user_id, $user );
    }

    $num_of_days_data = $wpdb->get_results( "SELECT p.ID as post_id FROM $wpdb->postmeta AS pm INNER JOIN $wpdb->posts AS p ON ( p.ID = pm.post_id ) where  ( pm.meta_key = '_badgeos_trigger_type' or pm.meta_key = '_point_trigger_type' or pm.meta_key = '_deduct_trigger_type' or pm.meta_key = '_rank_trigger_type' ) and  p.post_status = 'publish' AND pm.meta_value = 'badgeos_on_completing_num_of_day'" );
    if( count( $num_of_days_data ) > 0 ) { 
        do_action( 'badgeos_on_completing_num_of_day', $user_id, $user );
    }
}
add_action( 'wp_login', 'badgeos_login_trigger', 0, 2 );

/**
 * Listner function for login trigger
 *
 * @param $user_login
 * @param $user
*/
function badgeos_daily_visit_trigger( $user_login, $user ) {
	
	if( empty( $user_login ) ) {
		return;
	}

	if( !is_object( $user ) || is_null( $user ) || empty( $user ) ) {
		return;
	}
	$user_id = $user->ID;
	$current_visit_date = badgeos_utilities::get_user_meta( $user_id, 'badgeos_daily_visit_date', true );
	$today = date("Y-m-d");
	
	if( !empty( $current_visit_date ) ) {
		if( strtotime( $current_visit_date ) == strtotime($today) ) {
			$daily_visits = badgeos_utilities::get_user_meta( $user_id, 'badgeos_daily_visits', true );
			badgeos_utilities::update_user_meta( $user_id, 'badgeos_daily_visits', intval( $daily_visits ) + 1 );
		} else {
			badgeos_utilities::update_user_meta( $user_id,'badgeos_daily_visit_date', $today);
			badgeos_utilities::update_user_meta( $user_id,'badgeos_daily_visits', 1 );
			badgeos_daily_visit_steps_reset( $user_id );
		}
	} else {
		badgeos_utilities::update_user_meta( $user_id,'badgeos_daily_visit_date', $today);
		badgeos_utilities::update_user_meta( $user_id,'badgeos_daily_visits', 1 );
		badgeos_daily_visit_steps_reset( $user_id );
	}
	
	do_action( 'badgeos_daily_visit', $user_login, $user );
}
add_action( 'wp_login', 'badgeos_daily_visit_trigger', 20, 2 );


/**
 * update daily visit step's meta key
 *
 * @param $user_id
 * @return none
*/
function badgeos_daily_visit_steps_reset( $user_id ) {
	
	global $wpdb;
	$recs = $wpdb->get_results(  "SELECT * FROM {$wpdb->usermeta} where meta_key like 'badgeos_daily_visit_step_%' and user_id=".intval($user_id) );
	if( count( $recs ) > 0 ) {
		foreach( $recs  as $rec ){
			badgeos_utilities::update_user_meta( intval($user_id), trim( $rec->meta_key), 'No' );
		}
	}
}

/**
 * Check if trigger count don't needs to be update
 *
 * @param  integer $user_id        	The given user's ID
 * @param  string $step_id   		The step id
 * @param  integer $type        	Type of step
 * @return string  $key 			The meta key of current item    
 */
function badgeos_daily_visit_add_step_status( $user_id, $step_id, $type='achievement' ) {

	$key = 'badgeos_daily_visit_step_'.$step_id.'_'.$type;

	$badgeos_visit = badgeos_utilities::get_user_meta( $user_id, $key, true );
	
	if( empty( $badgeos_visit ) ) {
		badgeos_utilities::update_user_meta( $user_id, $key, 'No' );
	}
	return $key;
}

/**
 * Fires a badgeos trigger
 *
 * @param  $trigger
 * @param  $args
 */
function badgeos_perform_event( $trigger, $user_id = 0, $args = array()) {
    if( ! empty( $trigger ) ) {
        if( has_action( $trigger ) )
            if( $user_id > 0 )
                do_action( $trigger, $user_id, $args);
    }
}

/**
 * Fires a badgeos trigger when user visits a post
 *
 * @param  $trigger
 * @param  $args
 */
function badgeos_fire_visit_a_post_trigger($content) {
	global $wpdb, $post;
    if( is_single() && ! empty( $GLOBALS['post'] ) && is_user_logged_in() ) {
		$my_post_id = $post->ID;
        if ( $GLOBALS['post']->ID == $my_post_id ) {
			$trigger_data = $wpdb->get_results( "SELECT p.post_id, pv.meta_value as visit_post FROM $wpdb->postmeta as p inner join $wpdb->postmeta as pv on( p.post_id=pv.post_id ) WHERE ( p.meta_key = '_badgeos_trigger_type' or p.meta_key = '_point_trigger_type' or p.meta_key = '_deduct_trigger_type' or p.meta_key = '_rank_trigger_type' ) and pv.meta_key = '_badgeos_visit_post' AND p.meta_value = 'badgeos_visit_a_post'" );
			if( count( $trigger_data ) > 0 ) { 
				$bool_empty = false;
				foreach( $trigger_data as $data ) {
					if( $data->visit_post == $my_post_id ) {
						do_action( 'badgeos_visit_a_post', get_current_user_id(), absint( $data->post_id ), $my_post_id );
						return $content;
					}

					if( empty( $data->visit_post ) ) {
						$bool_empty = true;
					}
				}

				if( $bool_empty ) {
					do_action( 'badgeos_visit_a_post', get_current_user_id(), absint( $data->post_id ), $my_post_id );
					return $content;
				}
			}
        }
	}
	return $content;
}
add_filter('the_content', 'badgeos_fire_visit_a_post_trigger');


/**
 * Fires a badgeos trigger when user visits a post and awards badge to author
 *
 * @param  $trigger
 * @param  $args
 */
function badgeos_fire_award_author_on_visit_a_post_trigger($content) {
	global $wpdb, $post;
    if( is_single() && ! empty( $GLOBALS['post'] )) {
		$my_post_id = $post->ID;
		$post_author = $post->post_author;
        if ( $GLOBALS['post']->ID == $my_post_id ) {
			$trigger_data = $wpdb->get_results( "SELECT p.post_id, pv.meta_value as visit_post FROM $wpdb->postmeta as p inner join $wpdb->postmeta as pv on( p.post_id=pv.post_id ) WHERE ( p.meta_key = '_badgeos_trigger_type' or p.meta_key = '_point_trigger_type' or p.meta_key = '_deduct_trigger_type' or p.meta_key = '_rank_trigger_type' ) and pv.meta_key = '_badgeos_visit_post' AND p.meta_value = 'badgeos_award_author_on_visit_post'" );
			if( count( $trigger_data ) > 0 ) { 
				$bool_empty = false;
				foreach( $trigger_data as $data ) {
					if( $data->visit_post == $my_post_id ) {
						do_action( 'badgeos_award_author_on_visit_post', $post_author, absint( $data->post_id ), $my_post_id );
						return $content;
					}

					if( empty( $data->visit_post ) ) {
						$bool_empty = true;
					}
				}

				if( $bool_empty ) {
					do_action( 'badgeos_award_author_on_visit_post', $post_author, absint( $data->post_id ), $my_post_id );
					return $content;
				}
			}
        }
	}
	return $content;
}
add_filter('the_content', 'badgeos_fire_award_author_on_visit_a_post_trigger');

/**
 * Fires a badgeos trigger when user visits a page
 *
 * @param  $trigger
 * @param  $args
 */
function badgeos_fire_visit_a_page_trigger($content) {
	
	global $wpdb, $post;
    if( is_page() && ! empty( $GLOBALS['post'] ) && is_user_logged_in() ) {
		$my_post_id = $post->ID;
        if ( $GLOBALS['post']->ID == $my_post_id ) {
			$trigger_data = $wpdb->get_results( "SELECT p.post_id, pv.meta_value as visit_post FROM $wpdb->postmeta as p inner join $wpdb->postmeta as pv on( p.post_id=pv.post_id ) WHERE ( p.meta_key = '_badgeos_trigger_type' or p.meta_key = '_point_trigger_type' or p.meta_key = '_deduct_trigger_type' or p.meta_key = '_rank_trigger_type' ) and pv.meta_key = '_badgeos_visit_page' AND p.meta_value = 'badgeos_visit_a_page'" );
			if( count( $trigger_data ) > 0 ) { 
				$bool_empty = false;
				foreach( $trigger_data as $data ) {
					if( $data->visit_post == $my_post_id ) {
						do_action( 'badgeos_visit_a_page', get_current_user_id(), absint( $data->post_id ), $my_post_id );
						return $content;
					}

					if( empty( $data->visit_post ) ) {
						$bool_empty = true;
					}
				}
				
				if( $bool_empty ) {
					do_action( 'badgeos_visit_a_page', get_current_user_id(), absint( $data->post_id ), $my_post_id );
					return $content;
				}
			}
        }
	}
	
	return $content;
}
add_filter('the_content', 'badgeos_fire_visit_a_page_trigger');

/**
 * Fires a badgeos trigger to award author when user visits a page
 *
 * @param  $trigger
 * @param  $args
 */
function badgeos_fire_award_author_on_visit_a_page_trigger($content) {
	
	global $wpdb, $post;
    if( is_page() && ! empty( $GLOBALS['post'] ) ) {
		$my_post_id = $post->ID;
		$post_author = $post->post_author;
        if ( $GLOBALS['post']->ID == $my_post_id ) {
			$trigger_data = $wpdb->get_results( "SELECT p.post_id, pv.meta_value as visit_post FROM $wpdb->postmeta as p inner join $wpdb->postmeta as pv on( p.post_id=pv.post_id ) WHERE ( p.meta_key = '_badgeos_trigger_type' or p.meta_key = '_point_trigger_type' or p.meta_key = '_deduct_trigger_type' or p.meta_key = '_rank_trigger_type' ) and pv.meta_key = '_badgeos_visit_page' AND p.meta_value = 'badgeos_award_author_on_visit_page'" );
			if( count( $trigger_data ) > 0 ) { 
				$bool_empty = false;
				foreach( $trigger_data as $data ) {
					if( $data->visit_post == $my_post_id ) {
						do_action( 'badgeos_award_author_on_visit_page', $post_author, absint( $data->post_id ), $my_post_id );
						return $content;
					}

					if( empty( $data->visit_post ) ) {
						$bool_empty = true;
					}
				}
				
				if( $bool_empty ) {
					do_action( 'badgeos_award_author_on_visit_page', $post_author, absint( $data->post_id ), $my_post_id );
					return $content;
				}
			}
        }
	}
	
	return $content;
}
add_filter('the_content', 'badgeos_fire_award_author_on_visit_a_page_trigger');