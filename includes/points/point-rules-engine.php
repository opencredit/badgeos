<?php
/**
 * Rules Engine: The brains behind this whole operation
 * @package BadgeOS
 * @subpackage Points
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Check if user Points should be decreased, and decrease it if so.
 *
 * @param int $credit_step_id
 * @param int $credit_parent_id
 * @param int $user_id
 * @param string $this_trigger
 * @param int $site_id
 * @param array $args
 * @return bool
 */
function badgeos_maybe_deduct_points_to_user( $credit_step_id = 0, $credit_parent_id = 0, $user_id = 0, $this_trigger = '', $site_id = 0, $args = array() ) {

	/**
     * Use current user's ID if none specified
     */
	if ( ! $user_id )
		$user_id = get_current_user_id();

	/**
     * If the user does not have access to this achievement, bail here
     */
	$has_access = badgeos_points_user_has_access_to_achievement( $credit_step_id, $credit_parent_id, $user_id, 'Deduct' , $this_trigger, $site_id, $args );
	if ( ! $has_access )
		return false;
	
	/**
     * Grab the user's current points
     * If the user has completed the achievement, award it
     */
	if ( credit_deduct_step_completion_for_user( $credit_step_id, $credit_parent_id, $user_id, $this_trigger, $site_id, $args ) ) {
		$new_points 	= 	get_post_meta( $credit_step_id, '_point_value', true );

		badgeos_add_credit( $credit_parent_id, $user_id, 'Deduct',$new_points, $this_trigger, 0 , $credit_step_id , 0 );
		$total_points = badgeos_recalc_total_points( $user_id );
		
		badgeos_log_users_points( $user_id, $new_points, $total_points, 0, 0,'Deduct', $credit_parent_id );

		/**
         * Available action for triggering other processes
         */
		do_action( 'badgeos_unlock_user_rank', $user_id, 'Deduct', $new_points, $credit_step_id, $credit_parent_id, $this_trigger, 0, 0 );

		/**
         * Maybe award some points-based Achievements
         */
		foreach (badgeos_get_points_based_achievements() as $achievement ) {
			badgeos_maybe_award_achievement_to_user( $achievement->ID, $user_id );
		}
	}
}

/**
 * Check if user has completed a credit deduct
 *
 * @param $credit_step_id
 * @param $credit_parent_id
 * @param $user_id
 * @param $this_trigger
 * @param $site_id
 * @param $args
 * @return mixed|void
 */
function credit_deduct_step_completion_for_user( $credit_step_id, $credit_parent_id, $user_id, $this_trigger, $site_id, $args ) {

	/**
     * Assume the user has completed the achievement
     */
	$return = true;

	/**
     * Set to current site id
     */
	if ( ! $site_id )
		$site_id = get_current_blog_id();
	
	/**
     * If the achievement is not published, we do not have access
     */
	if ( 'publish' != get_post_status( $credit_parent_id ) ) {
		$return = false;
	}

	/**
     * Available filter to support custom earning rules
     */
	return apply_filters( 'badgeos_user_deserves_credit_deduct', $return, $credit_step_id, $credit_parent_id, $user_id, $this_trigger, $site_id, $args );
}

/**
 * Validate whether or not a user has completed all requirements for a step.
 *
 * @param $return
 * @param $credit_step_id
 * @param $credit_parent_id
 * @param $user_id
 * @param $this_trigger
 * @param $site_id
 * @param $args
 * @return bool
 */
function badgeos_deduct_step_count_is_complete( $return, $credit_step_id, $credit_parent_id, $user_id, $this_trigger, $site_id, $args ) {

	/**
     * Only override the $return data if we're working on a step
     */
	$settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
	if ( trim( $settings[ 'points_deduct_post_type' ] ) == badgeos_utilities::get_post_type( $credit_step_id ) ) {

		/**
         * Get the required number of checkins for the step.
         */
		$minimum_activity_count = absint( badgeos_utilities::get_post_meta( $credit_step_id, '_badgeos_count', true ) );

		/**
         * Grab the relevent activity for this step
         */
		$relevant_count = absint( points_get_user_trigger_count( $credit_step_id, $user_id, $this_trigger, $site_id, 'Deduct', $args ) ); 

		/**
         * If we meet or exceed the required number of checkins, they deserve the step
         */
		if( $relevant_count >= $minimum_activity_count ) {
            $return = true;
        } else {
            $return = false;
        }
	}

	return $return;
}
add_filter( 'badgeos_user_deserves_credit_deduct', 'badgeos_deduct_step_count_is_complete', 10, 7 );

/**
 * Check if user has completed a credit award
 *
 * @param  integer $credit_step_id 		The given award step ID to verify
 * @param  integer $credit_parent_id    The given step's parent credit ID
 * @param  integer $user_id    			The user id
 * @param  string  $this_trigger   		The trigger
 * @param  integer $site_id        		The triggered site id
 * @param  array   $args           		The triggered args
 * @return bool                    		True if user has completed achievement, false otherwise
 */
function credit_award_step_completion_for_user( $credit_step_id, $credit_parent_id, $user_id, $this_trigger, $site_id, $args ) {

	/**
     * Assume the user has completed the achievement
     */
	$return = true;

	/**
     * Set to current site id
     */
	if ( ! $site_id )
		$site_id = get_current_blog_id();
	
	/**
     * If the achievement is not published, we do not have access
     */
	if ( 'publish' != get_post_status( $credit_parent_id ) ) {
		$return = false;
	}

	/**
     * Available filter to support custom earning rules
     */
	return apply_filters( 'badgeos_user_deserves_credit_award', $return, $credit_step_id, $credit_parent_id, $user_id, $this_trigger, $site_id, $args );

}

/**
 * Validate whether or not a user has completed all requirements for a step.
 *
 * @param  bool $return 				status of previous step.
 * @param  integer $credit_step_id 		The given award step ID to verify
 * @param  integer $credit_parent_id    The given step's parent credit ID
 * @param  integer $user_id    			The user id
 * @param  string  $this_trigger   		The trigger
 * @param  integer $site_id        		The triggered site id
 * @param  array   $args           		The triggered args
 * @return bool                    		True if user has completed achievement, false otherwise
 */
function badgeos_award_step_count_is_complete( $return, $credit_step_id, $credit_parent_id, $user_id, $this_trigger, $site_id, $args ) {

	/**
     * Only override the $return data if we're working on a step
     */
	$settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
	if ( trim( $settings['points_award_post_type'] ) == badgeos_utilities::get_post_type( $credit_step_id ) ) {

		/**
         * Get the required number of checkins for the step.
         */
		$minimum_activity_count = absint( badgeos_utilities::get_post_meta( $credit_step_id, '_badgeos_count', true ) );

		/**
         * Grab the relevent activity for this step
         */
		$relevant_count = absint( points_get_user_trigger_count( $credit_step_id, $user_id, $this_trigger, $site_id, 'Award', $args ) );

		/**
         * If we meet or exceed the required number of checkins, they deserve the step
         */
		if( $relevant_count >= $minimum_activity_count ) {
            $return = true;
        } else {
            $return = false;
        }
	}

	return $return;
}
add_filter( 'badgeos_user_deserves_credit_award', 'badgeos_award_step_count_is_complete', 10, 7 );

/**
 * Check if user should earn a points, and award it if so.
 *
 * @param  integer $credit_id 	   The given Credit ID to possibly award
 * @param  integer $user_id        The given user's ID
 * @param  string $this_trigger    The trigger
 * @param  integer $site_id        The triggered site id
 * @param  array $args             The triggered args
 * @return mixed                   False if user has no access, void otherwise
 */
function badgeos_maybe_award_points_to_user( $credit_step_id = 0, $credit_parent_id = 0, $user_id = 0, $this_trigger = '', $site_id = 0, $args = array() ) {
	
	/**
     * Use current user's ID if none specified
     */
	if ( ! $user_id )
		$user_id = get_current_user_id();
    
	/**
     * Grab the user's current points
     * If the user does not have access to this achievement, bail here
     */

	$has_access = badgeos_points_user_has_access_to_achievement( $credit_step_id, $credit_parent_id, $user_id, 'Award' , $this_trigger, $site_id, $args );
	if ( ! $has_access )
		return false;

	/**
     * If the user has completed the achievement, award it
     */
	if ( credit_award_step_completion_for_user( $credit_step_id, $credit_parent_id, $user_id, $this_trigger, $site_id, $args ) ) {
		$new_points 	= 	get_post_meta( $credit_step_id, '_point_value', true );

		badgeos_add_credit( $credit_parent_id, $user_id, 'Award',$new_points, $this_trigger, 0 , $credit_step_id , 0 );
		
		$total_points = badgeos_recalc_total_points( $user_id);
	
		badgeos_log_users_points( $user_id, $new_points, $total_points, 0, 0,'Award', $credit_parent_id );

		/**
		 * Available action for triggering other processes
		 */
		do_action( 'badgeos_unlock_user_rank', $user_id, 'Award', $new_points, $credit_step_id, $credit_parent_id, $this_trigger, 0, 0 );
        
		/**
		 * Maybe award point based Achievements
		 */
		if( !empty( badgeos_get_points_based_achievements() ) ) {
			foreach (badgeos_get_points_based_achievements() as $achievement ) {
				badgeos_maybe_award_achievement_to_user( $achievement->ID, $user_id );
			}
		}
    }	
}

/**
 * Validate whether or not a user has completed all requirements for a step.
 *
 * @param  integer $credit_step_id 		The given award step ID to verify
 * @param  integer $credit_parent_id    The given step's parent credit ID
 * @param  integer $user_id    			The user id
 * @param  string  $this_trigger   		The trigger
 * @param  integer $site_id        		The triggered site id
 * @param  array   $args           		The triggered args
 * @return bool                    		True if user has completed achievement, false otherwise
 */
function badgeos_points_user_has_access_to_achievement( $credit_step_id, $credit_parent_id, $user_id, $type, $this_trigger = '', $site_id = 0, $args = array() ) {

	/**
     * Set to current site id
     */
	if ( ! $site_id ) {
		$site_id = get_current_blog_id();
	}

	/**
     * Assume we have access
     */
	$return = true;

	/**
     * If the achievement is not published, we do not have access
     */
	if ( 'publish' != get_post_status( $credit_step_id ) ) {
		$return = false;
	}

	/**
     * If the achievement is not published, we do not have access
     */
	if ( 'publish' != get_post_status( $credit_parent_id ) ) {
        $return = false;
	}

	/**
     * Available filter for custom overrides
     */
    $return = apply_filters( 'badgeos_user_has_access_to_points', $return, $credit_step_id, $credit_parent_id, $user_id, $type, $this_trigger, $site_id, $args );

	return $return;
}

/**
 * Validate whether or not a user has completed all requirements for a step.
 *
 * @param  integer $return        		True / False
 * @param  integer $step_id 		The given award step ID to verify
 * @param  integer $credit_parent_id    The given step's parent credit ID
 * @param  integer $user_id    			The user id
 * @param  string  $type   				The type
 * @param  string  $this_trigger   		The trigger
 * @param  integer $site_id        		The triggered site id
 * @param  array   $args           		The triggered args
 * @return bool                    		True if user has completed achievement, false otherwise
 */
function badgeos_points_daily_visit_access( $return, $step_id, $credit_parent_id, $user_id, $type, $this_trigger, $site_id, $args ) {
    
	if( trim( $this_trigger ) == 'badgeos_daily_visit' ) { 
		$settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
		$step_type = trim( badgeos_utilities::get_post_type( $step_id ) );
		if ( ! in_array( $step_type, array( trim( $settings['points_award_post_type'] ), trim( $settings['points_deduct_post_type'] ) ) ) ) {
            return $return;
        }

		$meta_key = badgeos_daily_visit_add_step_status( $user_id, $step_id, 'credit_'.$type );
        
        $badgeos_daily_visit_awarded_achivement = badgeos_utilities::get_user_meta( $user_id, $meta_key, true );
        
		if( trim( $badgeos_daily_visit_awarded_achivement ) == 'No' ) {
			
			$current_visit_date = badgeos_utilities::get_user_meta( $user_id, 'badgeos_daily_visit_date', true );
            $today = date( 'Y-m-d' );
            
			if( strtotime( $current_visit_date ) == strtotime( $today ) ) {
				$req_count 		= badgeos_utilities::get_post_meta( $step_id, '_badgeos_count', true );
				$daily_visits 	= badgeos_utilities::get_user_meta( $user_id, 'badgeos_daily_visits', true );
                
				if( intval( $req_count ) <= intval( $daily_visits ) ) {

					$trigger_count = absint( points_get_user_trigger_count( $step_id, $user_id, $this_trigger, $site_id, $type, $args ) );
                    
					for( $i = 0; $i < intval( $req_count ); $i++) {
						
						/**
                         * Grab the current count and increase it by 1
                         */
						$trigger_count += 1;

						/**
                         * Update the triggers arary with the new count
                         */
						$user_triggers = badgeos_points_get_user_triggers( $step_id, $user_id, $site_id, $type );
						$user_triggers[$site_id][$this_trigger."_".$step_id] = $trigger_count;
                        
						if( $type == 'Deduct' ) {
							badgeos_utilities::update_user_meta( $user_id, '_point_deduct_triggers', $user_triggers );
						} else {
							badgeos_utilities::update_user_meta( $user_id, '_point_award_triggers', $user_triggers );
						}
					}
                    
                    badgeos_utilities::update_user_meta( $user_id, $meta_key, 'Yes' );
                    $return = true;
				} else {
					$return = false;
				}
			} else {
				$return = false;
			}
		} else {
			$return = false;
		}
	}
	
	return $return;
}
add_filter( 'badgeos_user_has_access_to_points', 'badgeos_points_daily_visit_access', 10, 8 );


/**
 * Validate whether or not a user has completed all requirements for a visit a post step.
 *
 * @param  integer $return        		True / False
 * @param  integer $step_id 		The given award step ID to verify
 * @param  integer $credit_parent_id    The given step's parent credit ID
 * @param  integer $user_id    			The user id
 * @param  string  $type   				The type
 * @param  string  $this_trigger   		The trigger
 * @param  integer $site_id        		The triggered site id
 * @param  array   $args           		The triggered args
 * @return bool                    		True if user has completed achievement, false otherwise
 */
function badgeos_points_visit_post_access( $return, $step_id, $credit_parent_id, $user_id, $type, $this_trigger, $site_id, $args ) {
    
    global $wpdb, $post;
    if( ! $return ) {
        return $return;
    }
	
	// Only override the $return data if we're working on a step
    $settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
    $step_type = trim( badgeos_utilities::get_post_type( $step_id ) );
   
    if( in_array( $step_type, array( trim( $settings['points_award_post_type'] ), trim( $settings['points_deduct_post_type'] ) ) ) ) {
        
		if( is_single() && ! empty( $GLOBALS['post'] ) && is_user_logged_in() ) {
            
            $my_post_id = $post->ID;
            if( $step_type == trim( $settings['points_award_post_type'] ) ) {
                $trigger_type = badgeos_utilities::get_post_meta( absint( $step_id ), '_point_trigger_type', true );
            } else {
                $trigger_type = badgeos_utilities::get_post_meta( absint( $step_id ), '_deduct_trigger_type', true );
            }
			
			if ( $trigger_type == 'badgeos_visit_a_post' ) {
				
				$visit_post = badgeos_utilities::get_post_meta( absint( $step_id ), '_badgeos_visit_post', true );
				if( empty( $visit_post ) ) {
					$return = true;
				} else {
					if( $visit_post ==  $my_post_id ) {
						$return = true;
					} else {
						$return = false;
					}
				}
			}
        }
	}
    return $return;
}
add_filter( 'badgeos_user_has_access_to_points', 'badgeos_points_visit_post_access', 10, 8 );


/**
 * Validate whether or not a user has completed all requirements for a visit a page step.
 *
 * @param  integer $return        		True / False
 * @param  integer $step_id 		The given award step ID to verify
 * @param  integer $credit_parent_id    The given step's parent credit ID
 * @param  integer $user_id    			The user id
 * @param  string  $type   				The type
 * @param  string  $this_trigger   		The trigger
 * @param  integer $site_id        		The triggered site id
 * @param  array   $args           		The triggered args
 * @return bool                    		True if user has completed achievement, false otherwise
 */
function badgeos_points_visit_page_access( $return, $step_id, $credit_parent_id, $user_id, $type, $this_trigger, $site_id, $args ) {
    
    global $wpdb, $post;
    if( ! $return ) {
        return $return;
    }
    
	// Only override the $return data if we're working on a step
    $settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
    $step_type = trim( badgeos_utilities::get_post_type( $step_id ) );
   
    if( in_array( $step_type, array( trim( $settings['points_award_post_type'] ), trim( $settings['points_deduct_post_type'] ) ) ) ) {
        
		if( is_page() && ! empty( $GLOBALS['post'] ) && is_user_logged_in() ) {
            
            $my_post_id = $post->ID;
            if( $step_type == trim( $settings['points_award_post_type'] ) ) {
                $trigger_type = badgeos_utilities::get_post_meta( absint( $step_id ), '_point_trigger_type', true );
            } else {
                $trigger_type = badgeos_utilities::get_post_meta( absint( $step_id ), '_deduct_trigger_type', true );
            }
			
			if ( $trigger_type == 'badgeos_visit_a_page' ) {
				
				$visit_post = badgeos_utilities::get_post_meta( absint( $step_id ), '_badgeos_visit_page', true );
				if( empty( $visit_post ) ) {
					$return = true;
				} else {
					if( $visit_post ==  $my_post_id ) {
						$return = true;
					} else {
						$return = false;
					}
				}
			}
        }
	}
    return $return;
}
add_filter( 'badgeos_user_has_access_to_points', 'badgeos_points_visit_page_access', 10, 8 );
/**
 * Check if trigger count don't needs to be update
 *
 * @param  integer $increment       Number to be incremented in trigger count
 * @param  integer $user_id        	The given user's ID
 * @param  string $trigger    		The trigger
 * @param  integer $site_id        	The triggered site id
 * @param  integer $type        	Award/Deduct
 * @param  array $args        		The triggered args
 * @return integer $increment       
 */
function credit_cancel_update_trigger_count($increment, $user_id, $trigger, $site_id, $type, $args) {
	
	if( trim( $trigger ) == 'badgeos_daily_visit' ) {
		return 0;
	}
	
	return $increment;
}
add_filter( 'credit_update_user_trigger_count', 'credit_cancel_update_trigger_count', 0, 6 );

/**
 * Award points to users manually
 *
 * @param $credit_post_id
 * @param $user_id
 * @param $points
 * @param $this_trigger
 * @param $admin_id
 * @param $credit_step_id
 * @param $achievement_id
 */
function badgeos_award_credit( $credit_post_id, $user_id, $target, $points, $this_trigger, $admin_id, $credit_step_id, $achievement_id ) {

    badgeos_add_credit( $credit_post_id, $user_id, $target, $points, $this_trigger, $admin_id , $credit_step_id , $achievement_id );

    $total_points = badgeos_recalc_total_points( $user_id);

	badgeos_log_users_points( $user_id, $points, $total_points, $admin_id, $achievement_id, 'Award', $credit_post_id );

    /**
     * Available action for triggering other processes
     */
    do_action( 'badgeos_unlock_user_rank', $user_id, 'Award', $points, $credit_step_id, $credit_post_id, $this_trigger, 0, $admin_id );

    /**
     * Maybe award point based Achievements
     */
    if( !empty( badgeos_get_points_based_achievements() ) ) {
        foreach (badgeos_get_points_based_achievements() as $achievement ) {
            badgeos_maybe_award_achievement_to_user( $achievement->ID, $user_id );
        }
    }
}

/**
 * Validate and award a rank if applicable.
 *
 * @param $user_id
 * @param $type
 * @param $new_points
 * @param $credit_step_id
 * @param $credit_parent_id
 * @param $this_trigger
 * @param int $achievement_id
 */
function badgeos_maybe_unlock_user_rank( $user_id, $type, $points_earned, $credit_step_id, $credit_parent_id, $this_trigger, $achievement_id = 0, $admin_id = 0 ) {

	$total_points = badgeos_get_points_by_type( $credit_parent_id, $user_id );

	$ranks = badgeos_get_ranks_by_unlock_points( $user_id, $total_points, $credit_parent_id );
	
	if( $ranks ) {
        foreach( $ranks as $rank ){
            
            badgeos_update_user_rank( array(
                'user_id'           => $user_id,
                'site_id'           => get_current_blog_id(),
                'rank_id'           => $rank['rank_id'],
                'this_trigger'      => $this_trigger,
                'credit_id'         => 0,
                'credit_amount'     => 0,
                'admin_id'          => $admin_id,
            )  );
        }
	}
}
add_action( 'badgeos_unlock_user_rank', 'badgeos_maybe_unlock_user_rank',10, 8 );

/**
 * Add points to user balance and in db.
 *
 * @param  integer $credit_id      	Credit type ID
 * @param  integer $user_id      	User id to whome we will assign it
 * @param  string $type      		Action type i.e. Award and Deduct
 * @param  integer $new_points      Points which needs to be added or removed.
 * @param  string $this_trigger     Event which occured
 * @param  integer $admin_id  		Admin id if assigned by admin
 * @param  integer $step_id  		step id if it is performed due to credit type step event
 * @param  integer $achievement_id  The achievement ID if points are awarded due to an achievement.
 * @return integer record id if inserted successfully.
 */
function badgeos_add_credit( $credit_id, $user_id, $type, $new_points, $this_trigger, $admin_id=0, $step_id=0, $achievement_id=0, $check_if_allowed = true ) {
    global $wpdb;

    if( intval( $new_points ) != 0 ) {
		if( absint( $credit_id ) == 0 || empty( $credit_id ) ) {
            $credit_id = 0;
        }

        if( $check_if_allowed ) {
            if( ! apply_filters( 'badgeos_allow_award_points', true, $user_id, $credit_id, $achievement_id, $type, $new_points, $this_trigger, $step_id ) ) {
                return 0;
            }
        }

        /**
         * Available action to trigger
         * Before awarding
         */
        do_action( 'badgeos_before_award_points',
            $user_id,
            $credit_id,
            $achievement_id,
            $type,
            $new_points,
            $this_trigger,
            $step_id
        );
        $pdatetype = '';
        if( $type=='Deduct' ) {
            $pdatetype = 'mysql_deduct_points';
        } else {
            $pdatetype = 'mysql_award_points';
        }
        $wpdb->insert($wpdb->prefix.'badgeos_points', array(
            'credit_id' => $credit_id,
            'step_id' => $step_id,
            'admin_id' => $admin_id,
            'user_id' => $user_id,
            'achievement_id' => $achievement_id,
            'type' => $type,
            'credit' => $new_points,
            'actual_date_earned'    => badgeos_default_datetime( $pdatetype ),
            'dateadded' => current_time( 'mysql' ),
            'this_trigger' =>  $this_trigger
        ));

        /**
         * Available action to trigger
         * After awarding
         */
        do_action( 'badgeos_after_award_points',
            $user_id,
            $credit_id,
            $achievement_id,
            $type,
            $new_points,
            $this_trigger,
            $step_id,
            $wpdb->insert_id
        );

		return $wpdb->insert_id;
	}
	return 0;
}

/**
 * Remove points from user balance and in db.
 *
 * @param  integer $credit_id      	Credit type ID
 * @param  integer $user_id      	User id to whome we will assign it
 * @param  string $type      		Action type i.e. Award and Deduct
 * @param  integer $new_points      Points which needs to be added or removed.
 * @param  string $this_trigger     Event which occured
 * @param  integer $admin_id  		Admin id if assigned by admin
 * @param  integer $step_id  		step id if it is performed due to credit type step event
 * @param  integer $achievement_id  The achievement ID if points are awarded due to an achievement.
 * @return integer record id if inserted successfully.
 */
function badgeos_remove_credit($credit_id, $user_id, $type, $points, $this_trigger, $admin_id=0, $step_id=0, $achievement_id=0, $check_if_allowed=true ) {
	global $wpdb;
	if( intval( $points ) ) {

        if( absint($credit_id) == 0 || empty( $credit_id ) ) {
            $credit_id = 0;
        }

        if( $check_if_allowed ) {
            if( ! apply_filters( 'badgeos_allow_remove_points', true, $user_id, $credit_id, $achievement_id, $type, $points, $this_trigger, $step_id ) ) {
                return 0;
            }
        }

        /**
         * Available action to trigger
         * Before awarding
         */
        do_action( 'badgeos_before_revoke_points',
            $user_id,
            $credit_id,
            $achievement_id,
            $type,
            $points,
            $this_trigger,
            $step_id
        );
        $pdatetype = '';
        if( $type=='Deduct' ) {
            $pdatetype = 'mysql_deduct_points';
        } else {
            $pdatetype = 'mysql_award_points';
        }
        $wpdb->insert($wpdb->prefix.'badgeos_points', array(
            'credit_id' => $credit_id,
            'step_id' => $step_id,
            'admin_id' => $admin_id,
            'user_id' => $user_id,
            'achievement_id' => $achievement_id,
            'type' => $type,
            'credit' => $points,
            'actual_date_earned' => badgeos_default_datetime( $pdatetype ),
            'dateadded' => current_time( 'mysql' ),
            'this_trigger' =>  $this_trigger
        ));

        /**
         * Available action to trigger
         * After awarding
         */
        do_action( 'badgeos_after_revoke_points',
            $user_id,
            $credit_id,
            $achievement_id,
            $type,
            $points,
            $this_trigger,
            $step_id,
            $wpdb->insert_id
        );

        return $wpdb->insert_id;
    }
	return 0;
}

/**
 * Remove credit record from the db.
 *
 * @since  1.0.0
 * @param  integer $id      		Record id
 * @param  integer $user_id      	User id of point owner
 * @return bool 
 */
function badgeos_revoke_credit( $credit_post_id, $user_id, $target, $points, $this_trigger, $admin_id=0, $credit_step_id=0, $achievement_id=0 ){

    badgeos_remove_credit( $credit_post_id, $user_id, $target, $points, $this_trigger, $admin_id , $credit_step_id , $achievement_id );

    $total_points  = badgeos_recalc_total_points( $user_id );

	badgeos_log_users_points( $user_id, $points, $total_points, $admin_id, $achievement_id,'Deduct', $credit_post_id );


    /**
     * Available action for triggering other processes
     */
    do_action( 'badgeos_bulk_credit_revoked', $user_id, $points, $credit_step_id, $credit_post_id, $this_trigger, $admin_id );
}

/**
 * Calculate the total number of points of all the types.
 *
 * @param int $user_id
 * @return float|int
 */
function badgeos_recalc_total_points( $user_id = 0 ){
	global $wpdb;

	/**
     * Use current user's ID if none specified
     */
	if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }
		
	$results = $wpdb->get_results( 
				$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}badgeos_points WHERE user_id=%d", $user_id )
			);

	$total_points = 0;
	foreach( $results as $res ) {
		
		if( $res->type == 'Award' ){
			$total_points += floatval( $res->credit );
		}
		if( $res->type == 'Deduct' || $res->type == 'Utilized' ){
			$total_points -= floatval( $res->credit );
		}
	}

	badgeos_utilities::update_user_meta( $user_id, '_badgeos_points', $total_points );
	return $total_points;
}

/**
 * Calculate the total number of points of all the types.
 *
 * @param $credit_id
 * @param int $user_id
 * @param string $start
 * @param string $end
 * @return float|int
 */
function badgeos_get_points_by_type( $credit_id, $user_id = 0, $start = '', $end = '' ) {
	
	global $wpdb;

    if( ! empty($start) ) {
        if( ! empty( $end ) ) {
            $results = $wpdb->get_results(
                $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}badgeos_points WHERE credit_id =%d and user_id = %d and dateadded>=%s and dateadded<=%s", $credit_id, $user_id, $start, $end )
            );
        } else {
            $results = $wpdb->get_results(
                $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}badgeos_points WHERE credit_id =%d and user_id = %d and dateadded>=%s", $credit_id, $user_id, $start )
            );
        }
    } else {
        $results = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}badgeos_points WHERE credit_id =%d and user_id = %d", $credit_id, $user_id )
        );
    }

    $total_points = 0;

    if( !is_null( $results ) && $results ) {
        foreach( $results as $res ) {

            if( $res->type == 'Award' ){
                $total_points += floatval( $res->credit );
            }

            if( $res->type == 'Deduct' || $res->type == 'Utilized' ){
                $total_points -= floatval( $res->credit );
            }
        }
    }

    return $total_points;
}

/**
 * Validate whether or not a user has completed all requirements for a visit a page step.
 *
 * @param  integer $return        		True / False
 * @param  integer $step_id 		The given award step ID to verify
 * @param  integer $credit_parent_id    The given step's parent credit ID
 * @param  integer $user_id    			The user id
 * @param  string  $type   				The type
 * @param  string  $this_trigger   		The trigger
 * @param  integer $site_id        		The triggered site id
 * @param  array   $args           		The triggered args
 * @return bool                    		True if user has completed achievement, false otherwise
 */
function badgeos_points_author_visit_page_access( $return, $step_id, $credit_parent_id, $user_id, $type, $this_trigger, $site_id, $args ) {
    
    global $wpdb, $post;
    if( ! $return ) {
        return $return;
    }
    
	// Only override the $return data if we're working on a step
    $settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
    $step_type = trim( badgeos_utilities::get_post_type( $step_id ) );
   
    if( in_array( $step_type, array( trim( $settings['points_award_post_type'] ), trim( $settings['points_deduct_post_type'] ) ) ) ) {
        
		if( is_page() && ! empty( $GLOBALS['post'] ) ) {
            
            $my_post_id = $post->ID;
            if( $step_type == trim( $settings['points_award_post_type'] ) ) {
                $trigger_type = badgeos_utilities::get_post_meta( absint( $step_id ), '_point_trigger_type', true );
            } else {
                $trigger_type = badgeos_utilities::get_post_meta( absint( $step_id ), '_deduct_trigger_type', true );
            }
			
			if ( $trigger_type == 'badgeos_award_author_on_visit_page' ) {
				
				$visit_post = badgeos_utilities::get_post_meta( absint( $step_id ), '_badgeos_visit_page', true );
				if( empty( $visit_post ) ) {
					$return = true;
				} else {
					if( $visit_post ==  $my_post_id ) {
						$return = true;
					} else {
						$return = false;
					}
				}
			}
        }
	}
    return $return;
}
add_filter( 'badgeos_user_has_access_to_points', 'badgeos_points_author_visit_page_access', 10, 8 );

/**
 * Validate whether or not a user has completed all requirements for a visit a post step.
 *
 * @param  integer $return        		True / False
 * @param  integer $step_id 		The given award step ID to verify
 * @param  integer $credit_parent_id    The given step's parent credit ID
 * @param  integer $user_id    			The user id
 * @param  string  $type   				The type
 * @param  string  $this_trigger   		The trigger
 * @param  integer $site_id        		The triggered site id
 * @param  array   $args           		The triggered args
 * @return bool                    		True if user has completed achievement, false otherwise
 */
function badgeos_points_author_visit_post_access( $return, $step_id, $credit_parent_id, $user_id, $type, $this_trigger, $site_id, $args ) {
    
    global $wpdb, $post;
    if( ! $return ) {
        return $return;
    }
	
	// Only override the $return data if we're working on a step
    $settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
    $step_type = trim( badgeos_utilities::get_post_type( $step_id ) );
   
    if( in_array( $step_type, array( trim( $settings['points_award_post_type'] ), trim( $settings['points_deduct_post_type'] ) ) ) ) {
        
		if( is_single() && ! empty( $GLOBALS['post'] ) && is_user_logged_in() ) {
            
            $my_post_id = $post->ID;
            if( $step_type == trim( $settings['points_award_post_type'] ) ) {
                $trigger_type = badgeos_utilities::get_post_meta( absint( $step_id ), '_point_trigger_type', true );
            } else {
                $trigger_type = badgeos_utilities::get_post_meta( absint( $step_id ), '_deduct_trigger_type', true );
            }
			
			if ( $trigger_type == 'badgeos_award_author_on_visit_post' ) {
				
				$visit_post = badgeos_utilities::get_post_meta( absint( $step_id ), '_badgeos_visit_post', true );
				if( empty( $visit_post ) ) {
					$return = true;
				} else {
					if( $visit_post ==  $my_post_id ) {
						$return = true;
					} else {
						$return = false;
					}
				}
			}
        }
	}
    return $return;
}
add_filter( 'badgeos_user_has_access_to_points', 'badgeos_points_author_visit_post_access', 10, 8 );

/**
 * Validate whether or not a user has completed all requirements for a visit a page step.
 *
 * @param  integer $return        		True / False
 * @param  integer $step_id 		The given award step ID to verify
 * @param  integer $credit_parent_id    The given step's parent credit ID
 * @param  integer $user_id    			The user id
 * @param  string  $type   				The type
 * @param  string  $this_trigger   		The trigger
 * @param  integer $site_id        		The triggered site id
 * @param  array   $args           		The triggered args
 * @return bool                    		True if user has completed achievement, false otherwise
 */
function badgeos_points_validate_user_dob( $return, $step_id, $credit_parent_id, $user_id, $type, $this_trigger, $site_id, $args ) {
    
    global $wpdb;
    
    if( ! $return ) {
        return $return;
    }
    
	// Only override the $return data if we're working on a step
    $settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
    $step_type = trim( badgeos_utilities::get_post_type( $step_id ) );
   
    if( in_array( $step_type, array( trim( $settings['points_award_post_type'] ), trim( $settings['points_deduct_post_type'] ) ) ) ) {
        $trigger_type = badgeos_utilities::get_post_meta( absint( $step_id ), '_point_trigger_type', true );
        
        if ( $trigger_type == 'badgeos_points_on_birthday' ) {

            $badgeos_settings = get_option( 'badgeos_settings' );
            $date_of_birth_from 	= ( ! empty ( $badgeos_settings['date_of_birth_from'] ) ) ? $badgeos_settings['date_of_birth_from'] : 'profile';
            $dob = '';
            if( ! class_exists( 'BuddyPress' ) || $date_of_birth_from == 'profile' ) {
                $dob = badgeos_utilities::get_user_meta( absint( $user_id ), '_badgeos_date_of_birth', true );
            } else {
                $dob = bp_profile_field_data( 'field=Birthday' );
            }

            $strQuery = "select * from ".$wpdb->prefix . "badgeos_points where step_id='".$step_id."' and user_id='".$user_id."' and Year(dateadded)='".date('Y')."'";
            $points = $wpdb->get_results( $strQuery );
            if( count( $points ) > 0 ) {
                $return = false;
            } else{
                if( ! empty( $dob ) ) {
                    $dob_array = explode( '-', $dob );
                    $new_dob = date( 'Y' )."-".$dob_array[1]."-".$dob_array[2];
                    if( strtotime( $new_dob ) <= time()  ) {
                        $return = true;
                    } else {
                        $return = false;
                    }
                } else {
                    $return = false;
                }
            }
        }
	}
    return $return;
}
add_filter( 'badgeos_user_has_access_to_points', 'badgeos_points_validate_user_dob', 10, 8 );


/**
 * Validate whether or not a user has completed all requirements for a number of year step.
 *
 * @param  integer $return        		True / False
 * @param  integer $step_id 		The given award step ID to verify
 * @param  integer $credit_parent_id    The given step's parent credit ID
 * @param  integer $user_id    			The user id
 * @param  string  $type   				The type
 * @param  string  $this_trigger   		The trigger
 * @param  integer $site_id        		The triggered site id
 * @param  array   $args           		The triggered args
 * @return bool                    		True if user has completed achievement, false otherwise
 */
function badgeos_points_number_of_year_access( $return, $step_id, $credit_parent_id, $user_id, $type, $this_trigger, $site_id, $args ) {
    
    global $wpdb, $post;
    if( ! $return ) {
        return $return;
    }
	
	// Only override the $return data if we're working on a step
    $settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
    $step_type = trim( badgeos_utilities::get_post_type( $step_id ) );
   
    if( in_array( $step_type, array( trim( $settings['points_award_post_type'] ), trim( $settings['points_deduct_post_type'] ) ) ) ) {
        
        if( $step_type == trim( $settings['points_award_post_type'] ) ) {
            $trigger_type = badgeos_utilities::get_post_meta( absint( $step_id ), '_point_trigger_type', true );
        } else {
            $trigger_type = badgeos_utilities::get_post_meta( absint( $step_id ), '_deduct_trigger_type', true );
        }

		if ( $trigger_type == 'badgeos_on_completing_num_of_year' ) {
				
            $reg_date = get_userdata($user_id)->user_registered;
			$num_of_years = badgeos_utilities::get_post_meta( absint( $step_id ), '_badgeos_num_of_years', true );
			$strQuery = "select * from ".$wpdb->prefix . "badgeos_points where step_id='".$step_id."' and user_id='".$user_id."' order by actual_date_earned desc limit 1";
			$points = $wpdb->get_results( $strQuery );
			$return = false;
			if( count($points) > 0) {
				$reg_date = strtotime( "+".$num_of_years." year", strtotime( $points[0]->actual_date_earned ) );
				if( time() > $reg_date ) {
                    if( $type == 'Award' ) {
                        $GLOBALS['badgeos']->mysql_award_points = date( 'Y-m-d H:i:s', $reg_date);
                    } else {
                        $GLOBALS['badgeos']->mysql_deduct_points = date( 'Y-m-d H:i:s', $reg_date);
                    }
					
					$return = true;	
				} 
			} else if( intval( $num_of_years ) > 0 ) {

				$reg_date = strtotime( "+".$num_of_years." year", strtotime( $reg_date ) );
				if( time() > $reg_date ) {
                    if( $type == 'Award' ) {
                        $GLOBALS['badgeos']->mysql_award_points = date( 'Y-m-d H:i:s', $reg_date);
                    } else {
                        $GLOBALS['badgeos']->mysql_deduct_points = date( 'Y-m-d H:i:s', $reg_date);
                    }
					$return = true;	
				}
			}
        }
	}
    return $return;
}
add_filter( 'badgeos_user_has_access_to_points', 'badgeos_points_number_of_year_access', 10, 8 );

/**
 * Validate whether or not a user has completed all requirements for a number of month step.
 *
 * @param  integer $return        		True / False
 * @param  integer $step_id 		The given award step ID to verify
 * @param  integer $credit_parent_id    The given step's parent credit ID
 * @param  integer $user_id    			The user id
 * @param  string  $type   				The type
 * @param  string  $this_trigger   		The trigger
 * @param  integer $site_id        		The triggered site id
 * @param  array   $args           		The triggered args
 * @return bool                    		True if user has completed achievement, false otherwise
 */
function badgeos_points_number_of_month_access( $return, $step_id, $credit_parent_id, $user_id, $type, $this_trigger, $site_id, $args ) {
    
    global $wpdb, $post;
    if( ! $return ) {
        return $return;
    }
	
	// Only override the $return data if we're working on a step
    $settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
    $step_type = trim( badgeos_utilities::get_post_type( $step_id ) );
   
    if( in_array( $step_type, array( trim( $settings['points_award_post_type'] ), trim( $settings['points_deduct_post_type'] ) ) ) ) {
        
        if( $step_type == trim( $settings['points_award_post_type'] ) ) {
            $trigger_type = badgeos_utilities::get_post_meta( absint( $step_id ), '_point_trigger_type', true );
        } else {
            $trigger_type = badgeos_utilities::get_post_meta( absint( $step_id ), '_deduct_trigger_type', true );
        }

		if ( $trigger_type == 'badgeos_on_completing_num_of_month' ) {
				
            $reg_date = strtotime( get_userdata($user_id)->user_registered );
			$num_of_months = badgeos_utilities::get_post_meta( absint( $step_id ), '_badgeos_num_of_months', true );
			$strQuery = "select * from ".$wpdb->prefix . "badgeos_points where step_id='".$step_id."' and user_id='".$user_id."' order by actual_date_earned desc limit 1";
			$points = $wpdb->get_results( $strQuery );
			$return = false;
			if( count($points) > 0) {
				$reg_date = strtotime( "+".$num_of_months." month", strtotime( $points[0]->actual_date_earned ) );
				if( time() > $reg_date ) {
                    if( $type == 'Award' ) {
                        $GLOBALS['badgeos']->mysql_award_points = date( 'Y-m-d H:i:s', $reg_date);
                    } else {
                        $GLOBALS['badgeos']->mysql_deduct_points = date( 'Y-m-d H:i:s', $reg_date);
                    }
					
					$return = true;	
				} 
			} else if( intval( $num_of_months ) > 0 ) {
				$reg_date = strtotime( "+".$num_of_months." month", $reg_date );
				if( time() > $reg_date ) {
                    if( $type == 'Award' ) {
                        $GLOBALS['badgeos']->mysql_award_points = date( 'Y-m-d H:i:s', $reg_date);
                    } else {
                        $GLOBALS['badgeos']->mysql_deduct_points = date( 'Y-m-d H:i:s', $reg_date);
                    }
					$return = true;	
				}
			}
        }
	}
    return $return;
}
add_filter( 'badgeos_user_has_access_to_points', 'badgeos_points_number_of_month_access', 10, 8 );

/**
 * Validate whether or not a user has completed all requirements for a number of day step.
 *
 * @param  integer $return        		True / False
 * @param  integer $step_id 		The given award step ID to verify
 * @param  integer $credit_parent_id    The given step's parent credit ID
 * @param  integer $user_id    			The user id
 * @param  string  $type   				The type
 * @param  string  $this_trigger   		The trigger
 * @param  integer $site_id        		The triggered site id
 * @param  array   $args           		The triggered args
 * @return bool                    		True if user has completed achievement, false otherwise
 */
function badgeos_points_number_of_day_access( $return, $step_id, $credit_parent_id, $user_id, $type, $this_trigger, $site_id, $args ) {
    
    global $wpdb, $post;
    if( ! $return ) {
        return $return;
    }
	
	// Only override the $return data if we're working on a step
    $settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
    $step_type = trim( badgeos_utilities::get_post_type( $step_id ) );
   
    if( in_array( $step_type, array( trim( $settings['points_award_post_type'] ), trim( $settings['points_deduct_post_type'] ) ) ) ) {
        
        if( $step_type == trim( $settings['points_award_post_type'] ) ) {
            $trigger_type = badgeos_utilities::get_post_meta( absint( $step_id ), '_point_trigger_type', true );
        } else {
            $trigger_type = badgeos_utilities::get_post_meta( absint( $step_id ), '_deduct_trigger_type', true );
        }

		if ( $trigger_type == 'badgeos_on_completing_num_of_day' ) {
				
            $reg_date = strtotime( get_userdata($user_id)->user_registered );
			$num_of_days = badgeos_utilities::get_post_meta( absint( $step_id ), '_badgeos_num_of_days', true );
			$strQuery = "select * from ".$wpdb->prefix . "badgeos_points where step_id='".$step_id."' and user_id='".$user_id."' order by actual_date_earned desc limit 1";
			$points = $wpdb->get_results( $strQuery );
			$return = false;
			if( count($points) > 0) {
				$reg_date = strtotime( "+".$num_of_days." day", strtotime( $points[0]->actual_date_earned ) );
				if( time() > $reg_date ) {
                    if( $type == 'Award' ) {
                        $GLOBALS['badgeos']->mysql_award_points = date( 'Y-m-d H:i:s', $reg_date);
                    } else {
                        $GLOBALS['badgeos']->mysql_deduct_points = date( 'Y-m-d H:i:s', $reg_date);
                    }
					
					$return = true;	
				} 
			} else if( intval( $num_of_days ) > 0 ) {
				$reg_date = strtotime( "+".$num_of_days." day", $reg_date );
				if( time() > $reg_date ) {
                    if( $type == 'Award' ) {
                        $GLOBALS['badgeos']->mysql_award_points = date( 'Y-m-d H:i:s', $reg_date);
                    } else {
                        $GLOBALS['badgeos']->mysql_deduct_points = date( 'Y-m-d H:i:s', $reg_date);
                    }
					$return = true;	
				}
			}
        }
	}
    return $return;
}
add_filter( 'badgeos_user_has_access_to_points', 'badgeos_points_number_of_day_access', 10, 8 );

function badgeos_points_not_login_for_number_of_days( $return, $step_id, $credit_parent_id, $user_id, $type, $this_trigger, $site_id, $args ){
	// Prevent user from earning steps with no parents
	if( $this_trigger == 'badgeos_wp_not_login' ) {
		$last_login_timestamp = badgeos_utilities::get_user_meta( $user_id, '_badgeos_last_login', true );
		if( !empty( $last_login_timestamp ) && intval( $last_login_timestamp ) > 0 ) {
		    $earlier = new DateTime();
		    $earlier->setTimestamp($last_login_timestamp);

		    $later = new DateTime();

		    $days 			= $later->diff($earlier)->format("%a");
		    $num_of_days 	= badgeos_utilities::get_post_meta( $step_id, '_badgeos_num_of_days', true );
		    if( intval( $days ) > intval( $num_of_days ) ) {
		        $return = true;
		    } else {
		        $return = false;
		    }
		}
	}

	return $return;
}
add_filter( 'badgeos_user_has_access_to_points', 'badgeos_points_not_login_for_number_of_days', 10, 8 );
