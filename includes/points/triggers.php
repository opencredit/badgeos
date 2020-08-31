<?php
 /**
 * Activity Triggers, used for triggering point earning
 *
 * @package badgeos
 * @subpackage points
 * @author wooninja
 * @link https://wooninja.com
 */

/**
 * Helper function for returning our available actvity triggers
 *
 * @return mixed|void
 */
function get_badgeos_points_award_activity_triggers() {

	$GLOBALS['badgeos']->award_points_activity_triggers = apply_filters( 'badgeos_award_points_activity_triggers',
		array(
			'badgeos_wp_login'     			=> __( 'Log in to Website', 'badgeos' ),
			'badgeos_new_comment'  			=> __( 'Comment on a post', 'badgeos' ),
			'badgeos_specific_new_comment' 	=> __( 'Comment on a specific post', 'badgeos' ),
            'badgeos_new_post'     			=> __( 'Publish a new post', 'badgeos' ),
            'badgeos_visit_a_post'          => __( 'Visit a Post', 'badgeos' ),
            'badgeos_new_page'     			=> __( 'Publish a new page', 'badgeos' ),
            'badgeos_visit_a_page'          => __( 'Visit a Page', 'badgeos' ),
			'user_register'     			=> __( 'Register to the website', 'badgeos' ),
			'badgeos_daily_visit'     		=> __( 'Daily visit website', 'badgeos' ),
		)
	);
    return apply_filters( 'badgeos_activity_triggers_for_all', $GLOBALS['badgeos']->award_points_activity_triggers );
}

/**
 * Helper function for returning our available actvity triggers
 *
 * @return array An array of all our activity triggers stored as 'value' => 'Display Name'
 */
function get_badgeos_points_deduct_activity_triggers() {
	
	$GLOBALS['badgeos']->deduct_points_activity_triggers = apply_filters( 'badgeos_deduct_points_activity_triggers',
		array(
			'badgeos_wp_login'     			=> __( 'Log in to Website', 'badgeos' ),
			'badgeos_new_comment'  			=> __( 'Comment on a post', 'badgeos' ),
			'badgeos_specific_new_comment' 	=> __( 'Comment on a specific post', 'badgeos' ),
            'badgeos_new_post'     			=> __( 'Publish a new post', 'badgeos' ),
            'badgeos_visit_a_post'          => __( 'Visit a Post', 'badgeos' ),
            'badgeos_new_page'     			=> __( 'Publish a new page', 'badgeos' ),
            'badgeos_visit_a_page'          => __( 'Visit a Page', 'badgeos' ),
			'user_register'     			=> __( 'Register to the website', 'badgeos' ),
			'badgeos_daily_visit'     		=> __( 'Daily visit website', 'badgeos' ),
		)
	);
    return apply_filters( 'badgeos_activity_triggers_for_all', $GLOBALS['badgeos']->deduct_points_activity_triggers );
}

/**
 * Load up our activity triggers so we can add actions to them
 *
 * @return void
 */
function badgeos_points_load_activity_triggers() {

	/**
     * Grab our activity triggers
     */
    $award_activity_triggers = get_badgeos_points_award_activity_triggers();
    $deduct_activity_triggers = get_badgeos_points_deduct_activity_triggers();
    
    /**
     * Loop through each trigger and add our trigger event to the hook
     */
    foreach ( $award_activity_triggers as $trigger => $trigger_data ) {

        if( is_array( $trigger_data ) ) {
            if( count( $trigger_data['sub_triggers'] ) > 0 ) {
                foreach( $trigger_data['sub_triggers'] as $tdata ) {
                    add_action( trim( $tdata['trigger'] ), 'badgeos_dynamic_points_award_trigger_event', 10, 20 );
                }
            }
        } else
            add_action( $trigger, 'badgeos_points_award_trigger_event', 10, 20 );
    }

    /**
     * Loop through each trigger and add our trigger event to the hook
     */
    foreach ( $deduct_activity_triggers as $trigger => $trigger_data ) {

        if( is_array( $trigger_data ) ) {
            if( count( $trigger_data['sub_triggers'] ) > 0 ) {
                foreach( $trigger_data['sub_triggers'] as $tdata ) {
                    add_action( trim( $tdata['trigger'] ), 'badgeos_dynamic_points_deduct_trigger_event', 10, 20 );
                }
            }
        } else
            add_action( $trigger, 'badgeos_points_deduct_trigger_event', 10, 20 );
    }
}
add_action( 'init', 'badgeos_points_load_activity_triggers' );

/**
 * Handle each of our award point activity triggers
 *
 * @return mixed
 */
function badgeos_dynamic_points_award_trigger_event() {
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
    $user_id = $args[0];
    if( intval( $user_id ) < 1 ) {
        $user_id = get_current_user_id();
    }
    $user_data = get_user_by( 'id', $user_id );

    /**
     * Sanity check, if we don't have a user object, bail here
     */
    if ( ! is_object( $user_data ) )
        return $args[ 0 ];

    /**
     * If the user doesn't satisfy the trigger requirements, bail here\
     */
    if ( ! apply_filters( 'user_deserves_point_award_trigger', true, $user_id, $this_trigger, $site_id, $args ) ) {
        return $args[ 0 ];
    }

    $triggered_points = $wpdb->get_results( $wpdb->prepare(
        "SELECT p.ID as post_id FROM $wpdb->postmeta AS pm INNER JOIN $wpdb->posts AS p ON ( p.ID = pm.post_id AND pm.meta_key = '_badgeos_paward_subtrigger_value' ) where p.post_status = 'publish' AND pm.meta_value = %s",
        $this_trigger
    ) );

    if( !empty( $triggered_points ) ) {
        foreach ( $triggered_points as $point ) {

            $parent_point_id = badgeos_get_parent_id( $point->post_id );

            $step_params = get_post_meta( $point->post_id, '_badgeos_fields_data', true );
            $step_params = badgeos_extract_array_from_query_params( $step_params );

            /**
             * Update hook count for this user
             */
            if( apply_filters("badgeos_check_dynamic_trigger_filter", true, 'point_award', $this_trigger, $step_params, $args[ 1 ]) ) {
                $new_count = badgeos_points_update_user_trigger_count( $point->post_id, $parent_point_id, $user_id, $this_trigger, $site_id, 'Award', $args );
                badgeos_maybe_award_points_to_user( $point->post_id, $parent_point_id , $user_id, $this_trigger, $site_id, $args );
            }
        }
    }

    return $args[ 0 ];
}

/**
 * Handle each of our deduct point activity triggers
 *
 * @return mixed
 */
function badgeos_dynamic_points_deduct_trigger_event() {
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
    $user_id = $args[0];
    if( intval( $user_id ) < 1 ) {
        $user_id = get_current_user_id();
    }
    $user_data = get_user_by( 'id', $user_id );

    /**
     * Sanity check, if we don't have a user object, bail here
     */
    if ( ! is_object( $user_data ) ) {
        return $args[ 0 ];
    }

    /**
     * If the user doesn't satisfy the trigger requirements, bail here
     */
    if ( ! apply_filters( 'user_deserves_point_deduct_trigger', true, $user_id, $this_trigger, $site_id, $args ) ) {
        return $args[ 0 ];
    }

    /**
     * Now determine if any Achievements are earned based on this trigger event
     */
    $triggered_deducts = $wpdb->get_results( $wpdb->prepare(
        "SELECT p.ID as post_id FROM $wpdb->postmeta AS pm INNER JOIN $wpdb->posts AS p ON ( p.ID = pm.post_id AND pm.meta_key = '_badgeos_pdeduct_subtrigger_value' ) where p.post_status = 'publish' AND pm.meta_value = %s",
        $this_trigger
    ) );

    if( !empty( $triggered_deducts ) ) {
        foreach ( $triggered_deducts as $point ) {

            $parent_point_id = badgeos_get_parent_id( $point->post_id );

            $step_params = get_post_meta( $point->post_id, '_badgeos_fields_data', true );
            $step_params = badgeos_extract_array_from_query_params( $step_params );

            /**
             * Update hook count for this user
             */
            if( apply_filters("badgeos_check_dynamic_trigger_filter", true, 'point_deduct', $this_trigger, $step_params, $args[ 1 ]) ) {
                $new_count = badgeos_points_update_user_trigger_count( $point->post_id, $parent_point_id, $user_id, $this_trigger, $site_id, 'Deduct', $args );

                badgeos_maybe_deduct_points_to_user( $point->post_id, $parent_point_id , $user_id, $this_trigger, $site_id, $args );
            }
        }
    }

    return $args[ 0 ];
}

/**
 * Handle each of our award point activity triggers
 *
 * @return mixed
 */
function badgeos_points_award_trigger_event() {
	
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
     * If the user doesn't satisfy the trigger requirements, bail here\
     */
	if ( ! apply_filters( 'user_deserves_point_award_trigger', true, $user_id, $this_trigger, $site_id, $args ) ) {
        return $args[ 0 ];
    }

    $triggered_points = '';
	if( 'badgeos_specific_new_comment' == $this_trigger ) {
        $trigger_data = $wpdb->get_results( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE ( meta_key = '_badgeos_trigger_type' or meta_key = '_point_trigger_type' ) AND meta_value = 'badgeos_specific_new_comment'" );
        if( $trigger_data ) {
            $comment_post_id = $args[3]['comment_post_ID'];
            foreach( $trigger_data as $data ) {
                $post_specific_id = get_post_meta( absint( $data->post_id ), '_badgeos_achievement_post', true );
                if( absint( $post_specific_id ) == absint($comment_post_id) ) {
                    /**
                     * Now determine if any badges are earned based on this trigger event
                     */
                    $triggered_points = $wpdb->get_results( $wpdb->prepare(
                        "SELECT p.ID as post_id 
        FROM $wpdb->postmeta AS pm
        INNER JOIN $wpdb->posts AS p ON ( p.ID = pm.post_id AND pm.meta_key = '_point_trigger_type' ) where p.post_status = 'publish' AND pm.meta_value = %s
        ",
                        $this_trigger
                    ) );
                    break;
                }
            }
        }
    } else {

        /**
         * Now determine if any badges are earned based on this trigger event
         */
        $triggered_points = $wpdb->get_results( $wpdb->prepare(
            "SELECT p.ID as post_id 
        FROM $wpdb->postmeta AS pm
        INNER JOIN $wpdb->posts AS p ON ( p.ID = pm.post_id AND pm.meta_key = '_point_trigger_type' ) where p.post_status = 'publish' AND pm.meta_value = %s
        ",
            $this_trigger
        ) );
    }

	if( !empty( $triggered_points ) ) {
		foreach ( $triggered_points as $point ) { 

			$parent_point_id = badgeos_get_parent_id( $point->post_id );

			/**
			 * Update hook count for this user
			 */
			$new_count = badgeos_points_update_user_trigger_count( $point->post_id, $parent_point_id, $user_id, $this_trigger, $site_id, 'Award', $args );
			
			badgeos_maybe_award_points_to_user( $point->post_id, $parent_point_id , $user_id, $this_trigger, $site_id, $args );
		}
	}
	
	return $args[ 0 ];
}

/**
 * Return parent achievement.
 *
 * @param int $child_id  Child achievement.
 * @return int  $parent_id     parent id.
 */
function badgeos_get_parent_id( $child_id = 0 ) {

	global $wpdb;
	$parent_id = $wpdb->get_var( $wpdb->prepare( "SELECT p2p_to FROM $wpdb->p2p WHERE p2p_from = %d", $child_id  ) );
	if( ! $parent_id ) {
	    return 0;
	}
 
	return $parent_id;
 }

/**
 * Handle each of our point deduct activity triggers
 *
 * @return mixed
 */
function badgeos_points_deduct_trigger_event() {

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
	if ( ! is_object( $user_data ) ) {
        return $args[ 0 ];
    }

	/**
     * If the user doesn't satisfy the trigger requirements, bail here
     */
	if ( ! apply_filters( 'user_deserves_point_deduct_trigger', true, $user_id, $this_trigger, $site_id, $args ) ) {
        return $args[ 0 ];
    }

    $triggered_deducts = '';
    if( 'badgeos_specific_new_comment' == $this_trigger ) {
        $trigger_data = $wpdb->get_results( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE ( meta_key = '_badgeos_trigger_type' or meta_key = '_deduct_trigger_type' ) AND meta_value = 'badgeos_specific_new_comment'" );
        if( $trigger_data ) {
            $comment_post_id = $args[3]['comment_post_ID'];
            foreach( $trigger_data as $data ) {
                $post_specific_id = get_post_meta( absint( $data->post_id ), '_badgeos_achievement_post', true );
                if( absint( $post_specific_id ) == absint($comment_post_id) ) {
                    /**
                     * Now determine if any Achievements are earned based on this trigger event
                     */
                    $triggered_deducts = $wpdb->get_results( $wpdb->prepare(
                        "SELECT p.ID as post_id 
        FROM $wpdb->postmeta AS pm
        INNER JOIN $wpdb->posts AS p ON ( p.ID = pm.post_id AND pm.meta_key = '_deduct_trigger_type' ) where p.post_status = 'publish' AND pm.meta_value = %s
        ",
                        $this_trigger
                    ) );
                    break;
                }
            }
        }
    } else {

        /**
         * Now determine if any Achievements are earned based on this trigger event
         */
        $triggered_deducts = $wpdb->get_results( $wpdb->prepare(
            "SELECT p.ID as post_id 
        FROM $wpdb->postmeta AS pm
        INNER JOIN $wpdb->posts AS p ON ( p.ID = pm.post_id AND pm.meta_key = '_deduct_trigger_type' ) where p.post_status = 'publish' AND pm.meta_value = %s
        ",
            $this_trigger
        ) );
    }

	if( !empty( $triggered_deducts ) ) {
		foreach ( $triggered_deducts as $point ) { 
			
			$parent_point_id = badgeos_get_parent_id( $point->post_id );

			/**
             * Update hook count for this user
             */
			$new_count = badgeos_points_update_user_trigger_count( $point->post_id, $parent_point_id, $user_id, $this_trigger, $site_id, 'Deduct', $args );
			
			badgeos_maybe_deduct_points_to_user( $point->post_id, $parent_point_id , $user_id, $this_trigger, $site_id, $args );

		}
	}	
	
	return $args[ 0 ];
}

/**
 * Wrapper function for returning a user's array of point triggers
 *
 * @param  integer $user_id The given user's ID
 * @param  integer $site_id The desired Site ID to check
 * @return array            An array of the triggers a user has triggered
 */
function badgeos_points_get_user_triggers( $point_step_id, $user_id = 0, $site_id = 0, $type = 'Award' ) {

	/**
     * Grab all of the users triggers
     */
	if( $type == 'Deduct' ) {
		$user_triggers = ( $array_exists = get_user_meta( $user_id, '_point_deduct_triggers', true ) ) ? $array_exists : array();
	} else {
		$user_triggers = ( $array_exists = get_user_meta( $user_id, '_point_award_triggers', true ) ) ? $array_exists : array();
	}


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
		/**
         * Otherwise, return the full array of all triggers across all sites
         */
	} else {
		return $user_triggers;
	}
}

/**
 * Get the count for the number of times a user has triggered a particular point trigger
 *
 * @param  integer $user_id The given user's ID
 * @param  string  $trigger The given trigger we're checking
 * @param  integer $site_id The desired Site ID to check
 * @param  array $args        The triggered args
 * @return integer          The total number of times a user has triggered the trigger
 */
function points_get_user_trigger_count( $point_step_id, $user_id, $trigger, $site_id = 0, $type = 'Award', $args = array() ) {

	/**
     * Set to current site id
     */
	if ( ! $site_id )
		$site_id = get_current_blog_id();

	/**
     * Grab the user's logged triggers
     */
	$user_triggers = badgeos_points_get_user_triggers( $point_step_id, $user_id, $site_id, $type );
	
	$trigger = apply_filters( 'points_get_user_trigger_name',  $trigger, $point_step_id, $user_id, $site_id, $type, $args );

	/**
     * If we have any triggers, return the current count for the given trigger
     */
	if ( ! empty( $user_triggers ) && isset( $user_triggers[$trigger."_".$point_step_id] ) ) {
		return absint( $user_triggers[$trigger."_".$point_step_id] );
	} else {
        return 0;
    }
}

/**
 * Update the user's point trigger count for a given trigger by 1
 *
 * @param  integer $user_id The given user's ID
 * @param  string  $trigger The trigger we're updating
 * @param  integer $site_id The desired Site ID to update
 * @param  array $args        The triggered args
 * @return integer          The updated trigger count
 */
function badgeos_points_update_user_trigger_count( $point_step_id = 0, $point_parent_id = 0, $user_id, $trigger, $site_id = 0, $type='Award', $args = array() ) {

	/**
     * Set to current site id
     */
	if ( ! $site_id )
		$site_id = get_current_blog_id();

	/**
     * Grab the current count and increase it by 1
     */
	$trigger_count = absint( points_get_user_trigger_count( $point_step_id, $user_id, $trigger, $site_id, $type, $args ) );
	
	$increment = 1;
	if( $increment = apply_filters( 'point_update_user_trigger_count', $increment, $user_id, $trigger, $site_id, $type, $args ) ) {
        $trigger_count += (int) $increment;
    } else {
        return $increment;
    }

	/**
     * Update the triggers arary with the new count
     */
	$user_triggers = badgeos_points_get_user_triggers( $point_step_id, $user_id, false, $type );

	$user_triggers[$site_id][$trigger."_".$point_step_id] = $trigger_count;

	if( $type == 'Deduct' ) {
		update_user_meta( $user_id, '_point_deduct_triggers', $user_triggers );
	} else {
		update_user_meta( $user_id, '_point_award_triggers', $user_triggers );
	}

	/**
     * Send back our trigger count for other purposes\
     */
	return $trigger_count;
}
