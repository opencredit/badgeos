<?php
/**
 * Rules Engine: The brains behind this whole operation
 *
 * @package BadgeOS
 * @subpackage Achievements
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Check if user should earn an achievement, and award it if so.
 *
 * @since  1.0.0
 * @param  integer $achievement_id The given achievement ID to possibly award
 * @param  integer $user_id        The given user's ID
 * @param  string $this_trigger    The trigger
 * @param  integer $site_id        The triggered site id
 * @param  array $args             The triggered args
 * @return mixed                   False if user has no access, void otherwise
 */
function badgeos_maybe_award_achievement_to_user( $achievement_id = 0, $user_id = 0, $this_trigger = '', $site_id = 0, $args = array() ) {

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
	if ( badgeos_check_achievement_completion_for_user( $achievement_id, $user_id, $this_trigger, $site_id, $args ) ) {
        badgeos_award_achievement_to_user( $achievement_id, $user_id, $this_trigger, $site_id, $args );
    }
}

/**
 * Return the current wordpress timezone string
 *
 * @return $tzstring
 */
function badgeos_timezone_string() {
	$current_offset = get_option( 'gmt_offset' );
	$tzstring       = get_option( 'timezone_string' );

	// Remove old Etc mappings. Fallback to gmt_offset.
	if ( false !== strpos( $tzstring, 'Etc/GMT' ) ) {
		$tzstring = '';
	}

	if ( empty( $tzstring ) ) { // Create a UTC+- zone if no timezone string exists
		if ( 0 == $current_offset ) {
			$tzstring = 'UTC+0';
		} elseif ( $current_offset < 0 ) {
			$tzstring = 'UTC' . $current_offset;
		} else {
			$tzstring = 'UTC+' . $current_offset;
		}
	}

	return $tzstring;
}

/**
 * Check if user has completed an achievement
 *
 * @since  1.0.0
 * @param  integer $achievement_id The given achievement ID to verify
 * @param  integer $user_id        The given user's ID
 * @param  string  $this_trigger   The trigger
 * @param  integer $site_id        The triggered site id
 * @param  array   $args           The triggered args
 * @return bool                    True if user has completed achievement, false otherwise
 */
function badgeos_check_achievement_completion_for_user( $achievement_id = 0, $user_id = 0, $this_trigger = '', $site_id = 0, $args = array() ) {

	// Assume the user has completed the achievement
	$return = true;

	// Set to current site id
	if ( ! $site_id )
		$site_id = get_current_blog_id();
	
	$timestamp 		= badgeos_achievement_last_user_activity( $achievement_id, $user_id );
	if( intval( $timestamp ) > 0 ) {
		$current_offset = get_option( 'gmt_offset' );
		$tzstring       = get_option( 'timezone_string' );
		if( empty( $tzstring ) || strtolower( $tzstring ) == 'utc' ) {
			$offset = get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
			$timestamp =  $timestamp + $offset;
		} else {
			$timestamp = $GLOBALS['badgeos']->start_time;
		}
	}
	
	if( intval( $timestamp ) < 1 ) {
		$timestamp = $GLOBALS['badgeos']->start_time;
	}
	
	// If the user has not already earned the achievement...
	if ( ! badgeos_get_user_achievements( array( 'user_id' => absint( $user_id ), 'achievement_id' => absint( $achievement_id ), 'since' => $timestamp ) ) ) {

		// Grab our required achievements for this achievement
		$required_achievements = badgeos_get_required_achievements_for_achievement( $achievement_id );

		// If we have requirements, loop through each and make sure they've been completed
		if ( is_array( $required_achievements ) && ! empty( $required_achievements ) ) {
			foreach ( $required_achievements as $requirement ) {
				// Has the user already earned the requirement?
				if ( ! badgeos_get_user_achievements( array( 'user_id' => $user_id, 'achievement_id' => $requirement->ID, 'since' => $timestamp-1 ) ) ) {
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
		$user_points     = intval( badgeos_get_users_points( $user_id, $achievement_id ) );
		$points_required = 0;
		$points_req = get_post_meta( $achievement_id, '_badgeos_points_required', true );
	
		if( isset( $points_req ) &&  is_array( $points_req ) && count( $points_req ) > 0 ) {
			$points_required 	= intval( $points_req['_badgeos_points_required'] );
			$points_type 		= $points_req['_badgeos_points_required_type'];
		} 
		
		if( $points_required == 0 ) {
			return false;
		}

		$last_activity   = badgeos_achievement_last_user_activity( $achievement_id );

		if ( $user_points >= $points_required )
			$return = true;
		else
			$return = false;

		// If the user just earned the badge, though, don't let them earn it again
		// This prevents an infinite loop if the badge has no maximum earnings limit
		$minimum_time = time() - 2;
		if ( $last_activity >= $minimum_time ) {
		    $return = false;
		}
	}

	// Return our eligibility status
	return $return;
}
add_filter( 'user_deserves_achievement', 'badgeos_user_meets_points_requirement', 10, 3 );

/**
 * Check if user may access/earn daily visit.
 *
 * @param  integer $return        	True / False
 * @param  integer $user_id        	The given user's ID
 * @param  integer $achievement_id 	The given achievement's post ID
 * @param  string $this_trigger    	The trigger
 * @param  integer $site_id        	The triggered site id
 * @param  array $args        		The triggered args
 * @return bool                    	True if user has access, false otherwise
 */
function badgeos_daily_visit_access( $return, $user_id, $achievement_id, $this_trigger, $site_id = 0, $args = array() ) {
		
	if( trim( $this_trigger ) == 'badgeos_daily_visit' ) {

        $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();

        if ( trim( $badgeos_settings['achievement_step_post_type'] ) != get_post_type( $achievement_id ) ) {
            return false;
        }
		
		$meta_key = badgeos_daily_visit_add_step_status( $user_id, $achievement_id, 'achievement' );
		
		$badgeos_daily_visit_awarded_achivement = get_user_meta( $user_id, $meta_key, true );
		if( trim( $badgeos_daily_visit_awarded_achivement ) == 'No' ) {
			
			$current_visit_date = get_user_meta( $user_id, 'badgeos_daily_visit_date', true );
			$today = date("Y-m-d");
			if( strtotime( $current_visit_date ) == strtotime( $today ) )  {
				$req_count 		= get_post_meta( $achievement_id, '_badgeos_count', true );
				$daily_visits 	= get_user_meta( $user_id, 'badgeos_daily_visits', true );

				if( intval( $req_count ) <= intval( $daily_visits ) ) {
					
					$trigger_count = absint( badgeos_get_user_trigger_count( $user_id, $this_trigger, $site_id, $args ) );

					for( $i = 0; $i < intval( $req_count ); $i++) {
						
						/**
                         * Grab the current count and increase it by 1
                         */
						$trigger_count += 1;

						/**
                         * Update the triggers arary with the new count
                         */
						$user_triggers = badgeos_get_user_triggers( $user_id, false );
						$user_triggers[$site_id][$this_trigger] = $trigger_count;
						update_user_meta( $user_id, '_badgeos_triggered_triggers', $user_triggers );
					}
					update_user_meta( $user_id, $meta_key, 'Yes' );
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
add_filter( 'user_has_access_to_achievement', 'badgeos_daily_visit_access', 16, 6 );

/**
 * Check if user has earned the multisteps access/earn daily visit.
 *
 * @param  integer $return        	True / False
 * @param  integer $user_id        	The given user's ID
 * @param  integer $achievement_id 	The given achievement's post ID
 * @param  string $this_trigger    	The trigger
 * @param  integer $site_id        	The triggered site id
 * @param  array $args        		The triggered args
 * @return bool                    	True if user has access, false otherwise
 */
function badgeos_check_access_of_multi_steps( $return, $user_id, $achievement_id, $this_trigger, $site_id, $args=[] ) {

    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
    if ( trim( $badgeos_settings['achievement_step_post_type'] ) == get_post_type( $achievement_id ) )
        return $return;

    $earned_achievements = badgeos_get_user_achievements( array(
        'user_id'          	=> $user_id,
        'achievement_id' 	=> $achievement_id
    ) );
    $total_earned = count( $earned_achievements );

    $children = badgeos_get_achievements( array( 'children_of' => $achievement_id, 'fields'=>'ids'  ) );
    if( count( $children ) > 0 ) {
        foreach( $children as $child_id ) {

            $child_achievements = badgeos_get_user_achievements( array(
                'user_id'          	=> $user_id,
                'achievement_id' 	=> $child_id
            ) );

            if( count($child_achievements) <= $total_earned ) {
                return false;
            }
        }
    }

    return $return;
}
add_filter( 'user_deserves_achievement', 'badgeos_check_access_of_multi_steps', 16, 6 );

/**
 * Award an achievement to a user
 *
 * @since  1.0.0
 * @param  integer $achievement_id The given achievement ID to award
 * @param  integer $user_id        The given user's ID
 * @param  string $this_trigger    The trigger
 * @param  integer $site_id        The triggered site id
 * @param  array $args             The triggered args
 * @return mixed                   False on not achievement, void otherwise
 */
function badgeos_award_achievement_to_user( $achievement_id = 0, $user_id = 0, $this_trigger = '', $site_id = 0, $args = array() ) {

	global $wp_filter, $wp_version;

	// Sanity Check: ensure we're working with an achievement post
	if ( ! badgeos_is_achievement( $achievement_id ) )
		return false;

    //Break if achievement awards twice
    if( in_array( $achievement_id, $GLOBALS['badgeos']->award_ids ) ) {
        $GLOBALS['badgeos']->award_ids = array();
        return false;
    }

    // Use the current user ID if none specified
	if ( $user_id == 0 )
		$user_id = wp_get_current_user()->ID;

	// Get the current site ID none specified
	if ( ! $site_id )
		$site_id = get_current_blog_id();

	// Setup our achievement object
	$achievement_object = badgeos_build_achievement_object( $achievement_id, 'earned' , $this_trigger );

	// Update user's earned achievements
	$entry_id = badgeos_update_user_achievements( array( 'user_id' => $user_id, 'new_achievements' => array( $achievement_object ) ) );

	// Log the earning of the award
	badgeos_post_log_entry( $achievement_id, $user_id );

	// Available hook for unlocking any achievement of this achievement type
	do_action( 'badgeos_unlock_' . $achievement_object->post_type, $user_id, $achievement_id, $this_trigger, $site_id, $args );

	// Patch for WordPress to support recursive actions, specifically for badgeos_award_achievement
	// Because global iteration is fun, assuming we can get this fixed for WordPress 3.9
	$is_recursed_filter = ( 'badgeos_award_achievement' == current_filter() );
	$current_key = null;

	// Get current position
	if ( $is_recursed_filter ) {
		$current_key = key( $wp_filter[ 'badgeos_award_achievement' ] );
	}

	// Available hook to do other things with each awarded achievement
	do_action( 'badgeos_award_achievement', $user_id, $achievement_id, $this_trigger, $site_id, $args, $entry_id );

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
function badgeos_revoke_achievement_from_user( $entry_id = 0, $achievement_id = 0, $user_id = 0 ) {

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
 * if current sequential achievement is ready for award.
 *
 * @param  $user_id
 * @param  $achievement_id
 * @return $return
 */
function all_sequential_condition_met( $user_id, $achievement_id ) {

    $return = false;

    $children = badgeos_get_achievements( array( 'children_of' => $achievement_id, 'fields'=>'ids'  ) );
    $user_achievements = badgeos_get_user_achievements( array( 'user_id' => absint( $user_id ) ) );

    $current_sequence = array();
    foreach( $user_achievements as $ach ) {

        if( in_array( $ach->ID, $children ) ) {
            $current_sequence[] = $ach->ID;
        }
    }

    $total_found = 0;
    if( count( $current_sequence ) > 0) {
        for( $i = 0; $i < count( $user_achievements ); $i++ ) {
            for( $j = 0; $j < count( $children ); $j++ ) {
                if( isset( $current_sequence[ $i+$j ] ) && isset( $children[ $j ] ) && $current_sequence[ $i+$j ] == $children[ $j ] ) {
                    if( $j+1 == count( $children ) ) {
                        $total_found += 1;
                    }
                } else {
                    break;
                }
            }
        }
    }

    if( $total_found > 0 ) {

        $achievements = badgeos_get_user_achievements(
            array(
                'user_id' => absint( $user_id ),
                'achievement_id' => $achievement_id
            )
        );
        $total_count = count( $achievements );
        if( $total_count < $total_found ) {
            $return = true;
        } else {
            $return = false;
        }
    } else {
        $return = false;
    }

    return $return;
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

    $dependent_achievements = array();
	$timestamp = 0;
	if( intval( $timestamp ) < 1 ) {
		$timestamp = $GLOBALS['badgeos']->start_time;
	}
	
    $totals = badgeos_get_user_achievements( array("user_id"=>$user_id,"achievement_id"=>$achievement_id, 'since'=>$timestamp+1) );
	
    if( count($totals) <= 1 ) {
        if( ! in_array( $achievement_id, $GLOBALS['badgeos']->award_ids ) ) {
			
            $GLOBALS['badgeos']->award_ids[] = $achievement_id;
			$dependent_achievements = badgeos_get_dependent_achievements( $achievement_id, $user_id );
			
            // See if a user has unlocked all achievements of a given type
            badgeos_maybe_trigger_unlock_all( $user_id, $achievement_id );
			
            // Loop through each dependent achievement and see if it can be awarded
            foreach ( $dependent_achievements as $achievement ) {
                if ( badgeos_is_achievement_sequential( $achievement->ID ) ) {
                    if( all_sequential_condition_met( $user_id, $achievement->ID ) ) {
                        badgeos_maybe_award_achievement_to_user( $achievement->ID, $user_id );
                    }
                } else {
					badgeos_maybe_award_achievement_to_user( $achievement->ID, $user_id );
                }
            }
        }
    } else {
        $GLOBALS['badgeos']->award_ids = array();
	}
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

    $parents = badgeos_get_achievements( array( 'parent_of' => $achievement_id ) );
    $my_ach_id = $achievement_id;
    if( count( $parents ) > 0 ) {
        $my_ach_id = $parents[0]->ID;
    }

    // Get the post type of the earned achievement
    $post_type = get_post_type( $my_ach_id );
	$post_type = get_post_type( $achievement_id );

	// Hook for unlocking all achievements of this achievement type
	if ( $all_achievements_of_type = badgeos_get_achievements( array( 'post_type' => $post_type ) ) ) {

		// Assume we can award the user for unlocking all achievements of this type
		$all_per_type = true;

		// Loop through each of our achievements of this type
		foreach ( $all_achievements_of_type as $achievement ) {

			// Assume the user hasn't earned this achievement
			$found_achievement = false;

			// Loop through each earned achievement and see if we've earned it
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
 * @param  string $this_trigger    The trigger
 * @param  integer $site_id        The triggered site id
 * @param  array $args        The triggered args
 * @return bool                    True if user has access, false otherwise
 */
function badgeos_user_has_access_to_achievement( $user_id = 0, $achievement_id = 0, $this_trigger = '', $site_id = 0, $args = array() ) {

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
            $children = badgeos_get_children_of_achievement( $parent_achievement->ID );
            foreach ( $children as $sibling ) {

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
function badgeos_user_has_access_to_not_login_step( $return = false, $user_id = 0, $step_id = 0, $this_trigger='', $site_id=0, $args=array() ) {

    // Prevent user from earning steps with no parents
    if( $this_trigger == 'badgeos_wp_not_login' ) {

        $last_login_timestamp = get_user_meta( $user_id, '_badgeos_last_login', true );
        if( !empty( $last_login_timestamp ) && intval( $last_login_timestamp ) > 0 ) {

            $earlier = new DateTime();
            $earlier->setTimestamp($last_login_timestamp);

            $later = new DateTime();

            $days 			= $later->diff($earlier)->format("%a");
            $num_of_days 	= get_post_meta( $step_id, '_badgeos_num_of_days', true );
            if( intval( $days ) > intval( $num_of_days ) ) {
                $return = true;
            } else {
                $return = false;
            }
        }
    }

    // Send back our eligigbility
    return $return;
}
add_filter( 'user_has_access_to_achievement', 'badgeos_user_has_access_to_not_login_step', 10, 6 );

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
    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
    if ( trim( $badgeos_settings['achievement_step_post_type'] ) != get_post_type( $step_id ) )
        return $return;

	// Prevent user from earning steps with no parents
	$parent_achievement = badgeos_get_parent_of_achievement( $step_id );
	if ( ! $parent_achievement ) {
		$return = false;
	}

	// Prevent user from repeatedly earning the same step
	if ( $return && $parent_achievement && badgeos_get_user_achievements( array(
			'user_id'        => absint( $user_id ),
			'achievement_id' => absint( $step_id ),
			'since'          => absint( badgeos_get_start_time() )
		) )
	)
		$return = false;

	// Send back our eligigbility
	return $return;
}
add_filter( 'user_has_access_to_achievement', 'badgeos_user_has_access_to_step', 10, 3 );

/**
 * Checks if a user is allowed to work on a given step
 *
 * @since  1.0.0
 * @param  bool    $return   The default return value
 * @param  integer $user_id  The given user's ID
 * @param  integer $step_id  The given step's post ID
 * @return bool              True if user has access to step, false otherwise
 */
function badgeos_check_if_all_enabled( $return = false, $user_id = 0, $step_id = 0 ) {

    $trigger = get_post_meta($step_id, '_badgeos_trigger_type', true );
    if( $trigger == 'all-achievements' ) {
        $type = get_post_meta($step_id, '_badgeos_achievement_type', true );
        if( !empty( $type ) )
        {
            $userachs = badgeos_get_user_achievements( array( 'user_id' => absint( $user_id ), 'achievement_id' => absint( $step_id ) ) );
            $total_awarded = count( $userachs );
            $req_count = get_post_meta( $step_id, '_badgeos_count', true );

            $times_used = intval( $req_count ) * intval( $total_awarded );

            $achivements = get_posts( array(
                'post_type'         =>	$type,
                'posts_per_page'    =>	-1,
                'suppress_filters'  => false,
            ) );

            $total_achivements = count( $achivements );
            if( $total_achivements > 0 ) {
                $earned = 0;
                foreach ( $achivements as $achievement ) {
                    $userachs = badgeos_get_user_achievements( array( 'user_id' => absint( $user_id ), 'achievement_id' => absint( $achievement->ID ) ) );
                    $remainder = count( $userachs ) - $times_used;

                    if ( intval( $req_count ) <= intval( $remainder ) && is_array( $userachs ) ) {
                        $earned++;
                    }
                }
                if ( $earned == $total_achivements ) {
                    return true;
                } else {
                    return false;
                }

            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    // Send back our eligigbility
    return $return;
}
add_filter( 'user_has_access_to_achievement', 'badgeos_check_if_all_enabled', 15, 3 );

/**
 * Validate whether or not a user has completed all requirements for a step.
 *
 * @since  1.0.0
 * @param  bool $return      True if user deserves achievement, false otherwise
 * @param  integer $user_id  The given user's ID
 * @param  integer $step_id  The post ID for our step
 * @return bool              True if user deserves step, false otherwise
 */
function badgeos_user_deserves_step( $return = false, $user_id = 0, $step_id = 0, $this_trigger = '', $site_id = 0, $args = [] ) {

	// Only override the $return data if we're working on a step
    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
    if( trim( $badgeos_settings['achievement_step_post_type'] ) == get_post_type( $step_id ) ) {
        // Get the required number of checkins for the step.
        $minimum_activity_count = absint( get_post_meta( $step_id, '_badgeos_count', true ) );
        if( ! isset( $minimum_activity_count ) || empty( $minimum_activity_count ) )
            $minimum_activity_count = 1;

        // Grab the relevent activity for this step
        $relevant_count = absint( badgeos_get_step_activity_count( $user_id, $step_id ) );

        $achievements = badgeos_get_user_achievements(
            array(
                'user_id' => absint( $user_id ),
                'achievement_id' => $step_id
            )
        );

        $total_achievments = count( $achievements );
        $used_points = intval( $minimum_activity_count ) * intval( $total_achievments );
        $remainder = intval( $relevant_count ) - $used_points;

        // If we meet or exceed the required number of checkins, they deserve the step
        if( absint( $remainder ) >= $minimum_activity_count ) {
            $return = true;
        } else {
            $return = false;
        }
    }
    
    return $return;
}
add_filter( 'user_deserves_achievement', 'badgeos_user_deserves_step', 10, 6 );


/**
 * Check if a user deserves a visit a post step
 *
 * @param $return
 * @param $step_id
 * @param $rank_id
 * @param $user_id
 * @param $this_trigger
 * @param $site_id
 * @param $args
 * 
 * @return bool|void
 */
function badgeos_user_deserves_achievement_step_posts_callback($return = false, $user_id = 0, $step_id = 0, $this_trigger = '', $site_id = 0, $args = []) {
    
    global $wpdb, $post;
    if( ! $return ) {
        return false;
    }
	
	// Only override the $return data if we're working on a step
    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
    if( trim( $badgeos_settings['achievement_step_post_type'] ) == get_post_type( $step_id ) ) {
		if( is_single() && ! empty( $GLOBALS['post'] ) && is_user_logged_in() ) {
			$my_post_id = $post->ID;
			
			$trigger_type = get_post_meta( absint( $step_id ), '_badgeos_trigger_type', true );
			if ( $trigger_type == 'badgeos_visit_a_post' ) {
				
				$visit_post = get_post_meta( absint( $step_id ), '_badgeos_visit_post', true );
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
add_filter( 'user_deserves_achievement', 'badgeos_user_deserves_achievement_step_posts_callback', 10, 6 );

/**
 * Check if a user deserves a visit a page step
 *
 * @param $return
 * @param $step_id
 * @param $rank_id
 * @param $user_id
 * @param $this_trigger
 * @param $site_id
 * @param $args
 * 
 * @return bool|void
 */
function badgeos_user_deserves_achievement_step_pages_callback($return = false, $user_id = 0, $step_id = 0, $this_trigger = '', $site_id = 0, $args = []) {
    
    global $wpdb, $post;
    if( ! $return ) {
        return false;
    }
	
	// Only override the $return data if we're working on a step
    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
    if( trim( $badgeos_settings['achievement_step_post_type'] ) == get_post_type( $step_id ) ) {
		if( is_page() && ! empty( $GLOBALS['post'] ) && is_user_logged_in() ) {
			$my_post_id = $post->ID;
			
			$trigger_type = get_post_meta( absint( $step_id ), '_badgeos_trigger_type', true );
			if ( $trigger_type == 'badgeos_visit_a_page' ) {
				
				$visit_post = get_post_meta( absint( $step_id ), '_badgeos_visit_page', true );
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
add_filter( 'user_deserves_achievement', 'badgeos_user_deserves_achievement_step_pages_callback', 10, 6 );

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

    $trigger_type = $step_requirements['trigger_type'];
    if( !empty( $step_requirements['badgeos_subtrigger_id'] ) && !empty( $step_requirements['badgeos_subtrigger_value'] ) ) {
        $trigger_type = $step_requirements['badgeos_subtrigger_value'];
    }

    // Determine which type of trigger we're using and return the corresponding activities
    switch( $trigger_type ) {
		case 'specific-achievement' :

			// Get our parent achievement
			$parent_achievement = badgeos_get_parent_of_achievement( $step_id );

			// If the user has any interaction with this achievement, only get activity since that date
			if ( $parent_achievement && $date = badgeos_achievement_last_user_activity( $parent_achievement->ID, $user_id ) )
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
			$activities = badgeos_get_user_trigger_count( $user_id, 'badgeos_unlock_all_' . $step_requirements['achievement_type'] );
			break;
		default :
            $activities = badgeos_get_user_trigger_count( $user_id, $trigger_type );
			break;
	}

	// Available filter for overriding user activity
	return absint( apply_filters( 'badgeos_step_activity', $activities, $user_id, $step_id ) );
}