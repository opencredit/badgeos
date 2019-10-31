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

	/**
     * Only override the $return data if we're working on a step
     */
	$settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
	if ( trim( $settings['ranks_step_post_type'] ) == get_post_type( $step_id ) ) {

        if( ! empty( $this_trigger ) && in_array( $this_trigger, get_badgeos_ranks_req_activity_triggers() ) ) {

            /**
             * Get the required number of checkins for the step.
             */
            $minimum_activity_count = absint( get_post_meta( $step_id, '_badgeos_count', true ) );

            /**
             * Grab the relevent activity for this step
             */
            $current_trigger = get_post_meta( $step_id, '_rank_trigger_type', true );
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
		$settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
		if ( trim( $settings['ranks_step_post_type'] ) != get_post_type( $step_id ) )
			return $return;

		$meta_key = badgeos_daily_visit_add_step_status( $user_id, $step_id, 'rank' );

		$badgeos_daily_visit_awarded_achivement = get_user_meta( $user_id, $meta_key, true );
		if( trim( $badgeos_daily_visit_awarded_achivement ) == 'No' ) {
			
			$current_visit_date = get_user_meta( $user_id, 'badgeos_daily_visit_date', true );
			$today = date("Y-m-d");
			if( strtotime( $current_visit_date ) == strtotime( $today ) ) {
				$req_count 		= get_post_meta( $step_id, '_badgeos_count', true );
				$daily_visits 	= get_user_meta( $user_id, 'badgeos_daily_visits', true );
				
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

						update_user_meta( $user_id, '_badgeos_ranks_triggers', $user_triggers );
					
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
add_filter( 'badgeos_user_has_access_to_rank', 'badgeos_user_has_access_to_rank_handler', 10, 8 );

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

    $settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
    if( get_post_type( $step_id ) == trim( $settings['ranks_step_post_type'] ) ) {
        if ( apply_filters( 'badgeos_user_deserves_rank_step', $return_val=true, $step_id, $rank_id, $user_id, $this_trigger, $site_id, $args ) ) {

            $minimum_activity_count = absint( get_post_meta( $step_id, '_badgeos_count', true ) );
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