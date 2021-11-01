<?php
/**
 * Ranks Rule Engine
 *
 * @package Badgeos
 * @subpackage Ranks
 * @author LearningTimes
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com/
 */


/**
 * Check if user meets the rank requirement for a given achievement
 *
 * @param  bool    $return         The current status of whether or not the user deserves this achievement
 * @param  integer $user_id        The given user's ID
 * @param  integer $achievement_id The given achievement's post ID
 * @return bool                    Our possibly updated earning status
 */
function badgeos_user_deserves_rank_step_count_callback( $return, $step_id = 0, $rank_id = 0, $user_id = 0, $this_trigger = '', $site_id = 0, $args=array() ) {

    if( ! $return ) {
        return $return;
    }


    /**
     * Only override the $return data if we're working on a step
     */
	$settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
	if ( trim( $settings['ranks_step_post_type'] ) == badgeos_utilities::get_post_type( $step_id ) ) {

        if( ! empty( $this_trigger ) && array_key_exists( $this_trigger, get_badgeos_ranks_req_activity_triggers() ) ) {

            /**
             * Get the required number of checkins for the step.
             */
            $minimum_activity_count = absint( badgeos_utilities::get_post_meta( $step_id, '_badgeos_count', true ) );

            /**
             * Grab the relevent activity for this step
             */
            $current_trigger = badgeos_utilities::get_post_meta( $step_id, '_rank_trigger_type', true );
            $relevant_count = absint( ranks_get_user_trigger_count( $step_id, $user_id, $current_trigger, $site_id, $args ) );

            /**
             * If we meet or exceed the required number of checkins, they deserve the step
             */
            if ( $relevant_count >= $minimum_activity_count ) {
                $return = true;
            } else {
                $return = false;
            }
        }
    }

	return $return;
}
add_filter( 'badgeos_user_deserves_rank_step_count', 'badgeos_user_deserves_rank_step_count_callback', 10, 7 );


/**
 * If user has access to the rank
 *
 * @param int $step_id
 * @param int $rank_id
 * @param int $user_id
 * @param string $this_trigger
 * @param int $site_id
 * @param array $args
 * @return mixed|void
 */
function badgeos_user_has_access_to_rank( $step_id = 0, $rank_id = 0, $user_id = 0, $this_trigger = '', $site_id = 0, $args=array() ) {
	
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
     * If the step is not published, we do not have access
     */
	if ( 'publish' != get_post_status( $step_id ) ) {
		$return = false;
	}

	/**
     * If the rank is not published, we do not have access
     */
	if ( 'publish' != get_post_status( $rank_id ) ) {
		$return = false;
	}

	/**
     * Available filter for custom overrides
     */
	return apply_filters( 'badgeos_user_has_access_to_rank', $return, $step_id, $rank_id, $user_id, $this_trigger, $site_id, $args  );
	
}

/**
 * Validate whether or not a user has completed all requirements for a step.
 *
 * @param  integer $return        		True / False
 * @param  integer $step_id 			The given tep ID to verify
 * @param  integer $rank_id		    	The given rank_id parent credit ID
 * @param  integer $user_id    			The user id
 * @param  string  $type   				The type
 * @param  string  $this_trigger   		The trigger
 * @param  integer $site_id        		The triggered site id
 * @param  array   $args           		The triggered args
 * @return bool                    		True if user has completed achievement, false otherwise
 */
function badgeos_user_has_access_to_rank_handler( $return, $step_id, $rank_id, $user_id, $this_trigger, $site_id, $args ) {

	if( trim( $this_trigger ) == 'badgeos_daily_visit' ) {
		$settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
		if ( trim( $settings['ranks_step_post_type'] ) != badgeos_utilities::get_post_type( $step_id ) )
			return $return;

		$meta_key = badgeos_daily_visit_add_step_status( $user_id, $step_id, 'rank' );

		$badgeos_daily_visit_awarded_achivement = badgeos_utilities::get_user_meta( $user_id, $meta_key, true );
		if( trim( $badgeos_daily_visit_awarded_achivement ) == 'No' ) {
			
			$current_visit_date = badgeos_utilities::get_user_meta( $user_id, 'badgeos_daily_visit_date', true );
			$today = date("Y-m-d");
			if( strtotime( $current_visit_date ) == strtotime( $today ) ) {
				$req_count 		= badgeos_utilities::get_post_meta( $step_id, '_badgeos_count', true );
				$daily_visits 	= badgeos_utilities::get_user_meta( $user_id, 'badgeos_daily_visits', true );
				
				if( intval( $req_count ) <= intval( $daily_visits ) ) {

					$trigger_count = absint( ranks_get_user_trigger_count( $step_id, $user_id, $this_trigger, $site_id, $args ) );

					for( $i = 0; $i < intval( $req_count ); $i++) {
						
						/**
                         * Grab the current count and increase it by 1
                         */
						$trigger_count += 1;

						/**
                         * Update the triggers arary with the new count
                         */
						$user_triggers = badgeos_ranks_get_user_triggers( $step_id, $user_id, $site_id );
						$user_triggers[$site_id][$this_trigger."_".$step_id] = $trigger_count;

						badgeos_utilities::update_user_meta( $user_id, '_badgeos_ranks_triggers', $user_triggers );
					
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
add_filter( 'badgeos_user_has_access_to_rank', 'badgeos_user_has_access_to_rank_handler', 10, 8 );

/**
 * Check if user deserves hai visit a post step
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
function badgeos_user_deserves_rank_step_posts_callback($return, $step_id, $rank_id, $user_id, $this_trigger, $site_id, $args) {
    global $wpdb, $post;
    if( ! $return ) {
        return false;
    }
	
	// Only override the $return data if we're working on a step
    $badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
    
    if( trim( $badgeos_settings['ranks_step_post_type'] ) == badgeos_utilities::get_post_type( $step_id ) ) {
        
		if( is_single() && ! empty( $GLOBALS['post'] ) && is_user_logged_in() ) {
            
			$my_post_id = $post->ID;
			$trigger_type = badgeos_utilities::get_post_meta( absint( $step_id ), '_rank_trigger_type', true );
			
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
add_filter( 'badgeos_user_deserves_rank_step', 'badgeos_user_deserves_rank_step_posts_callback', 20, 7 );

/**
 * Check if user deserves hai visit a page step
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
function badgeos_user_deserves_rank_step_pages_callback($return, $step_id, $rank_id, $user_id, $this_trigger, $site_id, $args) {
    global $wpdb, $post;
    if( ! $return ) {
        return false;
    }
	
	// Only override the $return data if we're working on a step
    $badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' )) ? $exists : array();
    
    if( trim( $badgeos_settings['ranks_step_post_type'] ) == badgeos_utilities::get_post_type( $step_id ) ) {
        
		if( is_page() && ! empty( $GLOBALS['post'] ) && is_user_logged_in() ) {
            
			$my_post_id = $post->ID;
			$trigger_type = badgeos_utilities::get_post_meta( absint( $step_id ), '_rank_trigger_type', true );
			
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
add_filter( 'badgeos_user_deserves_rank_step', 'badgeos_user_deserves_rank_step_pages_callback', 20, 7 );

/**
 * Award new rank to the user based on logged activites and earned achievements
 *
 * @param int $step_id
 * @param int $rank_id
 * @param int $user_id
 * @param string $this_trigger
 * @param int $site_id
 * @param array $args
 * @return bool|void
 */
function badgeos_maybe_award_rank( $step_id = 0, $rank_id = 0, $user_id = 0, $this_trigger = '', $site_id = 0, $args = array() ) {
    global $wpdb;

    $settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
    if( badgeos_utilities::get_post_type( $step_id ) == trim( $settings['ranks_step_post_type'] ) ) {
        if ( apply_filters( 'badgeos_user_deserves_rank_step', $return_val=true, $step_id, $rank_id, $user_id, $this_trigger, $site_id, $args ) ) {

            $minimum_activity_count = absint( badgeos_utilities::get_post_meta( $step_id, '_badgeos_count', true ) );
            $recs = $wpdb->get_results( $wpdb->prepare("SELECT id FROM ".$wpdb->prefix."badgeos_ranks where rank_id = %d", $step_id	) );
            if ( count( $recs ) <= $minimum_activity_count ) {
				
				badgeos_update_user_rank( array(
                    'user_id'           => $user_id,
                    'site_id'           => get_current_blog_id(),
                    'rank_id'           => $step_id,
                    'this_trigger'      => $this_trigger,
                    'credit_id'         => 0,
                    'credit_amount'     => 0,
                    'admin_id'          => ( current_user_can( 'administrator' ) ? get_current_user_id() : 0 ),
                )  );
            }
        } else {
            
            return 0;
        }
    } else {
        return 0;
    }
    
    /**
     * Get the requirement rank
     */
	$rank = badgeos_get_rank_requirement_rank( $step_id );

	if( ! $rank ) {
        return;
    }
	
	$has_access = badgeos_user_has_access_to_rank( $step_id, $rank_id, $user_id, $this_trigger, $site_id, $args );
	if ( ! $has_access ) {
        return false;
    }

	/**
     * Get all requirements of this rank
     */
	$requirements = badgeos_get_rank_requirements( $rank->ID );

	$completed = true;

	foreach( $requirements as $requirement ) {

	    /**
         * Check if rank requirement has been earned
         */
        if( ! apply_filters( 'badgeos_user_deserves_rank_step_count', $completed, $requirement->ID, $rank_id, $user_id, $this_trigger, $site_id, $args ) ) {
			$completed = false;
            return $completed;
		}
	}
	
	/**
     * If all rank requirements has been completed, award the rank
     */
	if ( apply_filters( 'badgeos_user_deserves_rank_award', $completed, $step_id, $rank_id, $user_id, $this_trigger, $site_id, $args ) ) {
		badgeos_update_user_rank( array(
            'user_id'           => $user_id,
            'site_id'           => get_current_blog_id(),
            'rank_id'           => $rank->ID,
            'this_trigger'      => $this_trigger,
            'credit_id'         => 0,
            'credit_amount'     => 0,
            'admin_id'          => ( current_user_can( 'administrator' ) ? get_current_user_id() : 0 ),
        )  );
	}

}

/**
 * Check if trigger count don't needs to be update
 *
 * @param  integer $increment       Number to be incremented in trigger count
 * @param  integer $user_id        	The given user's ID
 * @param  integer $step_id        	The given event step ID
 * @param  integer $rank_id        	The given rank id ID
 * @param  string $trigger    		The trigger
 * @param  integer $site_id        	The triggered site id
 * @param  integer $type        	Award/Deduct
 * @param  array $args        		The triggered args
 * @return integer $increment       
 */
function badgeos_ranks_cancel_update_trigger_count($increment, $user_id, $step_id, $rank_id, $trigger, $site_id, $args) {
	
	if( trim( $trigger ) == 'badgeos_daily_visit' ) {
		return 0;
	}
	
	return $increment;
}
add_filter( 'badgeos_ranks_update_user_trigger_count', 'badgeos_ranks_cancel_update_trigger_count', 0, 7 );

/**
 * Check if user deserves hai visit a page step
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
function badgeos_user_deserves_author_rank_step_pages_callback($return, $step_id, $rank_id, $user_id, $this_trigger, $site_id, $args) {
    global $wpdb, $post;
    if( ! $return ) {
        return false;
    }
	
	// Only override the $return data if we're working on a step
    $badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' )) ? $exists : array();
    
    if( trim( $badgeos_settings['ranks_step_post_type'] ) == badgeos_utilities::get_post_type( $step_id ) ) {
        
		if( is_page() && ! empty( $GLOBALS['post'] ) ) {
            
			$my_post_id = $post->ID;
			$trigger_type = badgeos_utilities::get_post_meta( absint( $step_id ), '_rank_trigger_type', true );
			
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
add_filter( 'badgeos_user_deserves_rank_step', 'badgeos_user_deserves_author_rank_step_pages_callback', 20, 7 );

/**
 * Check if user deserves hai visit a post step
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
function badgeos_user_deserves_author_rank_step_posts_callback($return, $step_id, $rank_id, $user_id, $this_trigger, $site_id, $args) {
    global $wpdb, $post;
    if( ! $return ) {
        return false;
    }
	
	// Only override the $return data if we're working on a step
    $badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
    
    if( trim( $badgeos_settings['ranks_step_post_type'] ) == badgeos_utilities::get_post_type( $step_id ) ) {
        
		if( is_single() && ! empty( $GLOBALS['post'] ) ) {
            
			$my_post_id = $post->ID;
			$trigger_type = badgeos_utilities::get_post_meta( absint( $step_id ), '_rank_trigger_type', true );
			
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
add_filter( 'badgeos_user_deserves_rank_step', 'badgeos_user_deserves_author_rank_step_posts_callback', 20, 7 );

/**
 * Check if user deserves a number of years step
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
function badgeos_user_deserves_number_of_year_rank_step_callback($return, $step_id, $rank_id, $user_id, $this_trigger, $site_id, $args) {
    global $wpdb, $post;
    if( ! $return ) {
        return false;
    }
	
	// Only override the $return data if we're working on a step
    $badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
    
    if( trim( $badgeos_settings['ranks_step_post_type'] ) == badgeos_utilities::get_post_type( $step_id ) ) {
		$trigger_type = badgeos_utilities::get_post_meta( absint( $step_id ), '_rank_trigger_type', true );
		if ( $trigger_type == 'badgeos_on_completing_num_of_year' ) {
				
			$reg_date = get_userdata($user_id)->user_registered;
			$num_of_years = badgeos_utilities::get_post_meta( absint( $step_id ), '_badgeos_num_of_years', true );
			$strQuery = "select * from ".$wpdb->prefix . "badgeos_ranks where rank_id='".$step_id."' and user_id='".$user_id."' order by actual_date_earned desc limit 1";
			$ranks = $wpdb->get_results( $strQuery );
			$return = false;
			if( count($ranks) > 0) {
				$reg_date = strtotime( "+".$num_of_years." year", strtotime( $ranks[0]->actual_date_earned ) );
				if( time() > $reg_date ) {
					$GLOBALS['badgeos']->rank_date = date( 'Y-m-d H:i:s', $reg_date);
					$return = true;	
				} 
			} else if( intval( $num_of_years ) > 0 ) {
				$reg_date = strtotime( "+".$num_of_years." year", strtotime( $reg_date ) );
				if( time() > $reg_date ) {
					$GLOBALS['badgeos']->rank_date = date( 'Y-m-d H:i:s', $reg_date );
					$return = true;	
				}
			}
		}
	}
    return $return;
}
add_filter( 'badgeos_user_deserves_rank_step', 'badgeos_user_deserves_number_of_year_rank_step_callback', 20, 7 );

function badgeos_user_deserves_number_of_month_rank_step_callback($return, $step_id, $rank_id, $user_id, $this_trigger, $site_id, $args) {
    global $wpdb, $post;
    if( ! $return ) {
        return false;
    }
    
    // Only override the $return data if we're working on a step
    $badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
    
    if( trim( $badgeos_settings['ranks_step_post_type'] ) == badgeos_utilities::get_post_type( $step_id ) ) {
        $trigger_type = badgeos_utilities::get_post_meta( absint( $step_id ), '_rank_trigger_type', true );
        if ( $trigger_type == 'badgeos_on_completing_num_of_month' ) {
                
            $reg_date = strtotime( get_userdata($user_id)->user_registered );
            $num_of_months = badgeos_utilities::get_post_meta( absint( $step_id ), '_badgeos_num_of_months', true );
            $strQuery = "select * from ".$wpdb->prefix . "badgeos_ranks where rank_id='".$step_id."' and user_id='".$user_id."' order by actual_date_earned desc limit 1";
            $ranks = $wpdb->get_results( $strQuery );
            $return = false;
            if( count($ranks) > 0) {
                $reg_date = strtotime( "+".$num_of_months." month", strtotime( $ranks[0]->actual_date_earned ) );
                if( time() > $reg_date ) {
                    $GLOBALS['badgeos']->rank_date = date( 'Y-m-d H:i:s', $reg_date);
                    $return = true; 
                } 
            } else if( intval( $num_of_months ) > 0 ) {
                $reg_date = strtotime( "+".$num_of_months." month", $reg_date );
                if( time() > $reg_date ) {
                    $GLOBALS['badgeos']->rank_date = date( 'Y-m-d H:i:s', $reg_date );
                    $return = true; 
                }
            }
        }
    }
    return $return;
}
add_filter( 'badgeos_user_deserves_rank_step', 'badgeos_user_deserves_number_of_month_rank_step_callback', 20, 7 );

function badgeos_user_deserves_number_of_day_rank_step_callback($return, $step_id, $rank_id, $user_id, $this_trigger, $site_id, $args) {
    global $wpdb, $post;
    if( ! $return ) {
        return false;
    }
    
    // Only override the $return data if we're working on a step
    $badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
    
    if( trim( $badgeos_settings['ranks_step_post_type'] ) == badgeos_utilities::get_post_type( $step_id ) ) {
        $trigger_type = badgeos_utilities::get_post_meta( absint( $step_id ), '_rank_trigger_type', true );
        if ( $trigger_type == 'badgeos_on_completing_num_of_day' ) {
                
            $reg_date = strtotime( get_userdata($user_id)->user_registered );
            $num_of_days = badgeos_utilities::get_post_meta( absint( $step_id ), '_badgeos_num_of_days', true );
            $strQuery = "select * from ".$wpdb->prefix . "badgeos_ranks where rank_id='".$step_id."' and user_id='".$user_id."' order by actual_date_earned desc limit 1";
            $ranks = $wpdb->get_results( $strQuery );
            $return = false;
            if( count($ranks) > 0) {
                $reg_date = strtotime( "+".$num_of_days." day", strtotime( $ranks[0]->actual_date_earned ) );
                if( time() > $reg_date ) {
                    $GLOBALS['badgeos']->rank_date = date( 'Y-m-d H:i:s', $reg_date);
                    $return = true; 
                } 
            } else if( intval( $num_of_days ) > 0 ) {
                $reg_date = strtotime( "+".$num_of_days." day", $reg_date );
                if( time() > $reg_date ) {
                    $GLOBALS['badgeos']->rank_date = date( 'Y-m-d H:i:s', $reg_date );
                    $return = true; 
                }
            }
        }
    }
    return $return;
}
add_filter( 'badgeos_user_deserves_rank_step', 'badgeos_user_deserves_number_of_day_rank_step_callback', 20, 7 );
