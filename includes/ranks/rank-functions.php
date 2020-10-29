<?php
/**
 * Ranks functions
 *
 * @package Badgeos
 * @subpackage Ranks
 * @author LearningTimes
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com/
 */

/**
 * Exit if accessed directly
 */
if( !defined( 'ABSPATH' ) ) exit;

/**
 * Return post_type pf given ID
 *
 * @param null $post_id
 * @return false|string
 */
function badgeos_get_post_type( $post_id = null ) {
    return badgeos_utilities::get_post_type( $post_id );
}

/**
 * Return true
 * If post_type is related to rank
 *
 * @param null $post
 * @return mixed|void
 */
function badgeos_is_rank( $post = null ) {

    /**
     * Assume we are working with an rank object
     */
    $return = true;

    if( gettype( $post ) === 'string' ) {
        $post_type = $post;
    } else {
        $post_type = badgeos_utilities::get_post_type( $post );
    }

    /**
     * If post type is NOT a registered rank type, it cannot be an rank
     */
    if ( ! in_array( $post_type, badgeos_get_rank_types_slugs() ) ) {
        $return = false;
    }

    /**
     * If we pass both previous tests, this is a valid rank (with filter to override)
     */
    return apply_filters( 'badgeos_is_rank', $return, $post, $post_type );
}

/**
 * Return registered ranks
 *
 * @param array $args
 * @return array
 */
function badgeos_get_ranks( $args = array() ) {

    /**
     * Setup our defaults
     */
    $defaults = array(
        'post_type'         => array_merge( badgeos_get_rank_types_slugs() ),
        'numberposts'       => -1,
        'orderby'           => 'menu_order',
        'suppress_filters'  => false,
        'rank_relationship' => 'any',
    );

    $args = wp_parse_args( $args, $defaults );

    if ( isset( $args['parent_of'] ) ) {
        $post_parent = absint( badgeos_get_post_field( 'post_parent', $args['parent_of'] ) );
        if( $post_parent === 0 ) {
            return array();
        }
        $args['post__in'] = array( $post_parent );
    }

    if ( isset( $args['children_of'] ) ) {
        $args['post_parent']    = $args['children_of'];
        $args['orderby']        = 'menu_order';
        $args['order']          = 'ASC';
    }

    /**
     * Get ranks posts
     */
    $ranks = get_posts( $args );

    return $ranks;
}

/**
 * Return user current rank id
 *
 * @param null $user_id
 * @param string $rank_type
 * @return mixed|void
 */
function badgeos_get_user_rank_id( $user_id = null, $rank_type = '' ) {

    global $wpdb;

    if( $user_id === null ) {
        $user_id = get_current_user_id();
    }

    $meta = '_badgeos_rank';

    if( ! empty( $rank_type ) ) {
        $meta = "_badgeos_{$rank_type}_rank";
    }

    $current_rank_id = badgeos_utilities::get_user_meta( $user_id, $meta );

    if( ! $current_rank_id ) {

        /**
         * Get lowest priority rank as default rank to all users
         */
        $current_rank_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT p.ID
			FROM {$wpdb->posts} AS p
			WHERE p.post_type = %s
			 AND p.post_status = %s
			ORDER BY menu_order ASC
			LIMIT 1",
            $rank_type,
            'publish'
        ) );
    }

    return apply_filters( 'badgeos_get_user_rank_id', absint( $current_rank_id ), $user_id );

}

/**
 * Return user current rank
 *
 * @param null $user_id
 * @param string $rank_type
 * @return bool|mixed|void
 */
function badgeos_get_user_rank( $user_id = null, $rank_type = '' ) {

    global $wpdb;

    if( $user_id === null ) {
        $user_id = get_current_user_id();
    }

    $current_rank_id = badgeos_get_user_rank_id( $user_id, $rank_type );
    $rank = badgeos_utilities::badgeos_get_post( $current_rank_id );

    if( $rank ) {
        return apply_filters( 'badgeos_get_user_rank', $rank, $user_id, $current_rank_id );
    }

    return false;
}

/**
 * Return user next rank id
 *
 * @param null $user_id
 * @param string $rank_type
 * @return mixed|void
 */
function badgeosx( $user_id = null, $rank_type = '' ) {

    global $wpdb;

    if( $user_id === null ) {
        $user_id = get_current_user_id();
    }

    $current_rank_id = badgeos_get_user_rank_id( $user_id, $rank_type );
    $next_rank_id = badgeos_get_next_rank_id( $current_rank_id );

    return apply_filters( 'badgeos_get_next_user_rank_id', absint( $next_rank_id ), $user_id, $rank_type, $current_rank_id );

}

/**
 * Return user next rank
 *
 * @param null $user_id
 * @param string $rank_type
 * @return bool|mixed|void
 */
function badgeos_get_next_user_rank( $user_id = null, $rank_type = '' ) {

    global $wpdb;

    if( $user_id === null ) {
        $user_id = get_current_user_id();
    }

    $current_rank_id = badgeos_get_user_rank_id( $user_id, $rank_type );

    $next_rank = badgeos_get_next_rank( $current_rank_id );

    if( $next_rank ) {
        return apply_filters( 'badgeos_get_next_user_rank', $next_rank, $user_id, $rank_type, $current_rank_id );
    }

    return false;
}

/**
 * Return next rank id based of given rank priority
 *
 * @param null $rank_id
 * @return mixed|void
 */
function badgeos_get_next_rank_id( $rank_id = null ) {

    global $wpdb;

    if( $rank_id === null ) {
        $rank_id = get_the_ID();
    }

    $rank_type = badgeos_utilities::get_post_type( $rank_id );

    /**
     * Get lowest priority rank but bigger than current one
     */
    $next_rank_id = $wpdb->get_var( $wpdb->prepare(
        "SELECT p.ID
        FROM {$wpdb->posts} AS p
        WHERE p.post_type = %s
         AND p.post_status = %s
         AND p.menu_order > %s
        ORDER BY menu_order ASC
        LIMIT 1",
        $rank_type,
        'publish',
        badgeos_get_post_field( 'menu_order', $rank_id )
    ) );

    if( absint( $next_rank_id ) === $rank_id ) {
        $next_rank_id = 0;
    }

    return apply_filters( 'badgeos_get_next_rank_id', absint( $next_rank_id ), $rank_id );
}

/**
 * Return next rank id based of given rank priority
 *
 * @param $user_id
 * @param $points
 * @param int $point_type
 * @return bool|mixed|void
 */
function badgeos_get_ranks_by_unlock_points( $user_id, $points, $point_type = 0 ) {

    global $wpdb;
    $unlockable_ranks = [];
    $ranks_types = badgeos_get_rank_types_slugs();
    if( count ($ranks_types) ) {

        $formatted_ranks_types = implode( "','", $ranks_types );

        /**
         * Get lowest priority rank but bigger than current one
         */
        $ranks = $wpdb->get_results( 
                "SELECT p.ID as post_id, pvalue.meta_value as rank_point 
                FROM {$wpdb->postmeta} AS pm
                INNER JOIN {$wpdb->posts} AS p ON ( p.ID = pm.post_id AND pm.meta_key = '_ranks_unlock_with_points' )
                INNER JOIN {$wpdb->postmeta} AS pvalue ON ( p.ID = pvalue.post_id AND pvalue.meta_key = '_ranks_points_to_unlock' )
                where p.post_status = 'publish' AND pm.meta_value = 'Yes' and p.post_type in ( '{$formatted_ranks_types}' )
                "
        );

        foreach( $ranks as $rank ) {
            $post_id = $rank->post_id;
            $rank_point = maybe_unserialize( $rank->rank_point );
            
           
            if( !badgeos_user_has_a_rank( $post_id, $user_id ) && absint( $rank_point['_ranks_points_to_unlock'] ) <= $points && absint( $rank_point['_ranks_points_to_unlock_type'] ) == $point_type ){
                $unlockable_ranks[] = [ 'rank_id' => $post_id, 'credits' => absint( $rank_point['_ranks_points_to_unlock'] ), 'credit_type_id'=>absint( $rank_point['_ranks_points_to_unlock_type'] ) ];
            }
        }

        return apply_filters( 'badgeos_get_ranks_by_unlock_points',$unlockable_ranks, $points, $point_type );
    }

    return false;
}

/**
 * Return next rank based of given rank priority
 *
 * @param null $rank_id
 * @return bool|mixed|void
 */
function badgeos_get_next_rank( $rank_id = null ) {

    if( $rank_id === null ) {
        $rank_id = get_the_ID();
    }

    $next_rank_id = badgeos_get_next_rank_id( $rank_id );

    if( $next_rank_id === 0 ) {
        return false;
    }

    $rank = badgeos_utilities::badgeos_get_post( $next_rank_id );

    if( $rank ) {
        return apply_filters( 'badgeos_get_next_rank', $rank, $next_rank_id, $rank_id );
    }

    return false;
}

/**
 * Return previous rank id based of given rank priority
 *
 * @param null $rank_id
 * @return mixed|void
 */
function badgeos_get_prev_rank_id( $rank_id = null ) {

    global $wpdb;

    if( $rank_id === null ) {
        $rank_id = get_the_ID();
    }

    $rank_type = badgeos_utilities::get_post_type( $rank_id );

    /**
     * Get highest priority rank but less than current one
     */
    $prev_rank_id = $wpdb->get_var( $wpdb->prepare(
        "SELECT p.ID
        FROM {$wpdb->posts} AS p
        WHERE p.post_type = %s
         AND p.post_status = %s
         AND p.menu_order < %s
        ORDER BY menu_order DESC
        LIMIT 1",
        $rank_type,
        'publish',
        badgeos_get_post_field( 'menu_order', $rank_id )
    ) );
   
    if( absint( $prev_rank_id ) === $rank_id ) {
        $prev_rank_id = 0;
    }

    return apply_filters( 'badgeos_get_prev_rank_id', absint( $prev_rank_id ), $rank_id );

}

/**
 * Return previous rank based of given rank priority
 *
 * @param null $rank_id
 * @return bool|mixed|void
 */
function badgeos_get_prev_rank( $rank_id = null ) {

    if( $rank_id === null ) {
        $rank_id = get_the_ID();
    }

    $prev_rank_id = badgeos_get_prev_rank_id( $rank_id );

    if( $prev_rank_id === 0 ) {
        return false;
    }

    $rank = badgeos_utilities::badgeos_get_post( $prev_rank_id );

    if( $rank ) {
        return apply_filters( 'badgeos_get_prev_rank', $rank, $prev_rank_id, $rank_id );
    }

    return false;
}

/**
 * Return previous user rank
 *
 * @param null $user_id
 * @param string $rank_type
 * @return bool|int|mixed|void
 */
function badgeos_get_prev_user_rank_id( $user_id = null, $rank_type = '' ) {

    if( $user_id === null ) {
        $user_id = get_current_user_id();
    }

    $old_meta = "_badgeos_{$rank_type}_previous_rank";
    $prev_user_rank_id = absint( badgeos_utilities::get_user_meta( $user_id, $old_meta ) );

    /**
     * Check if there is a previous rank stored, if not, try to get previous rank of current user rank
     */
    if( $prev_user_rank_id === 0 ) {

        $user_rank_id = badgeos_get_user_rank_id( $user_id, $rank_type );

        /**
         * If there is not previous rank and not current rank, return lowest priority rank ID
         */
        if( ! $user_rank_id ) {
            return badgeos_get_lowest_priority_rank_id( $rank_type );
        }

        $prev_user_rank_id = badgeos_get_prev_rank_id( $user_rank_id );
    }

    if( $prev_user_rank_id ) {
        return apply_filters( 'badgeos_get_prev_user_rank_id', $prev_user_rank_id, $user_id, $rank_type );
    }

    return false;
}

/**
 * Helper function to check if a rank is the lowest priority rank (aka the default rank to anyone)
 *
 * @param null $rank_id
 * @return bool
 */
function badgeos_is_default_rank( $rank_id = null ) {

    if( $rank_id === null ) {
        $rank_id = get_the_ID();
    }

    $priority = badgeos_get_rank_priority( $rank_id );

    /**
     * Return true if previous rank is 0 or the same that given one
     */
    return (bool) ( $priority == 0 );

}

/**
 * Get the lowest priority rank ID of a rank type
 *
 * @param null $rank_type
 * @return mixed|void
 */
function badgeos_get_lowest_priority_rank_id( $rank_type = null ) {

    global $wpdb;


    /**
     * Get the lowest priority rank
     */
    $rank_id = $wpdb->get_var( $wpdb->prepare(
        "SELECT p.ID
        FROM {$wpdb->posts} AS p
        WHERE p.post_type = %s
         AND p.post_status = %s
        ORDER BY menu_order ASC
        LIMIT 1",
        $rank_type,
        'publish'
    ) );

    /**
     * Return true if previous rank is 0 or the same that given one
     */
    return apply_filters( 'badgeos_get_lowest_priority_rank_id', absint( $rank_id ), $rank_type );

}

/**
 * Revoke rank from user account
 *
 * @param int $user_id
 * @param int $rank_id
 * @param int $new_rank_id
 * @param array $args
 * @return bool
 */
function badgeos_revoke_rank_from_user_account( $user_id = 0, $rank_id = 0 ) {

    /**
     * Bail if no user_id or rank_id found
     */
    if( $rank_id == 0 || $user_id == 0 ) {
        return false;
    }

    /**
     * If not default rank, bail here
     */

    badgeos_remove_rank( array(
        'user_id'       => $user_id,
        'rank_id'       => $rank_id,
    ) );

    /**
     * Available action for triggering other processes
     */
    do_action( 'badgeos_revoke_rank_from_user_account', $user_id, $rank_id );

    /**
     * Post revoke rank log
     */
    badgeos_log_users_ranks( $rank_id, $user_id, 'Revoked', 0  );

    return true;
}

/**
 * Log user ranks
 *
 * @param $rank_id
 * @param $user_id
 * @param $action
 * @param int $admin_id
 */
function badgeos_log_users_ranks( $rank_id, $user_id, $action, $admin_id = 0  ) {

	/**
     * Setup our user objects
     */
	$user  = get_userdata( $user_id );
	$admin = get_userdata( $admin_id );
    $rank = badgeos_utilities::badgeos_get_post( $rank_id );

    if( $action == 'Revoked'){

        /**
         * Alter our log message if this was an admin action
         */
        $log_message = sprintf( __( '"%1$s" rank is revoked from %2$s user', 'badgeos' ), $rank->post_title ,$user->user_login );
    } else {

        /**
         * Alter our log message if this was an admin action
         */
         if ( $admin_id ) {
             $log_message = sprintf( __( '%1$s has awarded "%2$s" rank to %3$s user', 'badgeos' ), $admin->user_login, $rank->post_title, $user->user_login );
         } else {
             $log_message = sprintf( __( '%1$s has earned "%2$s" rank', 'badgeos' ), $user->user_login, $rank->post_title );
         }
    }

	/**
     * Create a log entry
     */
	$log_entry_id = badgeos_post_log_entry( $rank_id, $user_id, $action, $log_message );

	/**
     * Add relevant meta to our log entry
     */
	badgeos_utilities::update_post_meta( $log_entry_id, '_badgeos_rank_id', $rank_id );
    badgeos_utilities::update_post_meta( $log_entry_id, '_badgeos_user_id', $user_id );
    badgeos_utilities::update_post_meta( $log_entry_id, '_badgeos_action', $action );
	if ( $admin_id ) {
        badgeos_utilities::update_post_meta( $log_entry_id, '_badgeos_admin_id', $admin_id );
    }
}

/**
 * Upgrade user rank
 *
 * @param int $user_id
 * @param int $rank_id
 * @param int $admin_id
 * @param null $achievement_id
 * @param string $rank_type
 * @return array|bool|null|WP_Post
 */
function badgeos_update_user_rank( $args = array() ) {

    /**
     * Setup our default args
     */
    $defaults = array(
        'user_id'           => get_current_user_id(),
        'site_id'           => get_current_blog_id(),
        'rank_id'           => 0,
        'this_trigger'      => 'admin_awarded',
        'credit_id'         => 0,
        'credit_amount'     => 0,
        'admin_id'          => ( current_user_can( 'administrator' ) ? get_current_user_id() : 0 ),
    );
    $args = wp_parse_args( $args, $defaults );

    if( 0 == $args['rank_id'] ) {
        return false;
    }

    $settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
    if( badgeos_user_has_a_rank( $args['rank_id'], $args['user_id'] ) && badgeos_utilities::get_post_type( $args['rank_id'] ) != trim( $settings['ranks_step_post_type'] ) ) {
        return false;
    }

    $rank_object = badgeos_build_rank_object( $args['rank_id'] );
    badgeos_add_rank( array(
            'user_id'        => $args['user_id'],
            'new_rank'       => $rank_object,
            'this_trigger'   => $args['this_trigger'],
            'credit_id'      => $args['credit_id'],
            'credit_amount'  => $args['credit_amount'],
            'admin_id'       => $args['admin_id'],
        )
    );

    badgeos_log_users_ranks( $args['rank_id'], $args['user_id'], 'Awarded', $args['admin_id']  );

    /**
     * Maybe award some points-based ranks
     */
    if( ! empty( badgeos_get_rank_based_achievements() ) ) {
        foreach (badgeos_get_rank_based_achievements() as $achievement ) {
            badgeos_maybe_award_achievement_to_user( $achievement->ID, $args['user_id'] );
        }
    }
}

/**
 * earn achievement by rank
 *
 * @param $user_id
 * @param $new_rank
 * @param $old_rank
 * @param $admin_id
 * @param $achievement_id
 */
function badgeos_earn_achivement_by_rank( $user_id, $new_rank, $old_rank, $admin_id, $achievement_id ) {

    global $blog_id, $wpdb;

	$site_id = $blog_id;
	$args = func_get_args();
    $achievement_types = badgeos_get_achievement_types_slugs();
    if( count ( $achievement_types ) ) {

        /**
         * Get lowest priority rank but bigger than current one
         */
        $ranks = $wpdb->get_results(  "SELECT p.ID as achievement_id, pvalue.meta_value as rank_id 
                    FROM {$wpdb->postmeta} AS pm
                    INNER JOIN {$wpdb->posts} AS p ON ( p.ID = pm.post_id AND pm.meta_key = '_badgeos_earned_by' )
                    INNER JOIN {$wpdb->postmeta} AS pvalue ON ( p.ID = pvalue.post_id AND pvalue.meta_key = '_badgeos_rank_required' )
                    where p.post_status = 'publish' AND pm.meta_value = 'rank' and p.post_type in ('".implode("','",$achievement_types)."')"
                );
        foreach( $ranks  as $rank ) {
            if( absint( $rank->rank_id ) == absint( $new_rank->ID ) ) {
                badgeos_award_achievement_to_user( absint( $rank->achievement_id ), $user_id, 'achievement_by_rank', $site_id, $args );
            }
        }
    }
}

add_action( 'badgeos_update_user_rank', 'badgeos_earn_achivement_by_rank',10, 5 );

/**
 * Return rank based achievements
 *
 * @return array
 */
function badgeos_get_rank_based_achievements() {

	global $wpdb;

	$achievements = badgeos_get_transient( 'badgeos_rank_based_achievements' );

	if ( empty( $achievements ) ) {

		$posts    	= $wpdb->prefix.'posts';
		$postmeta 	= $wpdb->prefix.'postmeta';

		/**
         * Grab posts that can be earned by unlocking the given achievement
         */
		$achievements = $wpdb->get_results( $wpdb->prepare(
			"SELECT *
			FROM   {$posts} as posts
			LEFT JOIN {$postmeta} AS m1 ON ( posts.ID = m1.post_id AND m1.meta_key = %s )
			LEFT JOIN {$postmeta} AS m2 ON ( posts.ID = m2.post_id AND m2.meta_key = %s )
			WHERE m1.meta_value = %s
				OR m2.meta_value = %s",
			'_badgeos_trigger_type',
			'_badgeos_earned_by',
			'earn-rank',
			'rank'
		) );

		/**
         * Store these posts to a transient for 1 days
         */
		badgeos_set_transient( 'badgeos_rank_based_achievements', $achievements, 60*60*24 );
	}

	return (array) maybe_unserialize( $achievements );
}

/**
 * Build rank object
 *
 * @param int $rank_id
 * @return bool|mixed|void
 */
function badgeos_build_rank_object( $rank_id = 0 ) {

	/**
     * Grab the new rank's $post data, and bail if it doesn't exist
     */
	$rank = badgeos_utilities::badgeos_get_post( $rank_id );
	if ( is_null( $rank ) )
		return false;

	/**
     * Setup a new object for the rank
     */
	$_object = new stdClass;
	$_object->ID = $rank_id;
	$_object->post_type = $rank->post_type;
    $_object->date_earned = time();

	/**
     * Return our rank object, available filter so we can extend it elsewhere
     */
	return apply_filters( 'rank_object', $_object, $rank_id );
}

/**
 * Return user ranks
 *
 * @param int $user_id
 * @param string $rank_type
 * @return array|mixed
 */
function badgeos_get_user_ranks( $args = array() ) {

    /**
     * Setup our default args
     */
    $defaults = array(
        'user_id'           => 0,
        'rank_id'           => false,
        'rank_type'         => false,
        'start_date'        => false, // A specific achievement type
		'end_date'          => false, // A specific achievement type
        'since'             => 0,
        'no_steps'          => true,
        'pagination'	    => false,// if true the pagination will be applied
		'limit'	            => 10,
        'page'	            => 1, 
        'orderby'           => 'id',
		'order'             => 'ASC',
		'total_only'        => false
    );
    $args = wp_parse_args( $args, $defaults );

    if( $args['user_id'] == 0 ) {
        $args['user_id'] = get_current_user_id();
    }

    $where = 'user_id = ' . $args['user_id'];

    if( $args['rank_id'] != false ) {
        $where .= ' AND rank_id = ' . $args['rank_id'];
    }

    if( 'all' != $args['rank_type'] ) {
        if( $args['rank_type'] != false ) {
            if( is_array( $args['rank_type'] ) ) {
                $loop_count = 1;
                $where_sub = '';
                foreach( $args['rank_type'] as $rank_type ) {
                    if( $loop_count == 1 ) {
                        $condition = 'AND';
                    } else {
                        $condition = 'OR';
                    }
                    if( $where_sub!='' ) {
                        $where_sub .= ' '.$condition;    
                    }
                    $where_sub .= ' rank_type = "' . $rank_type . '"';

                    $loop_count++;
                }
                $where .= " And (".$where_sub.")";
            } else {
                $where .= " AND ( rank_type = '" . $args['rank_type'] . "')";
            }
        }
    }

    if( absint( $args['since']) > 1 ) {
        $where .= " AND dateadded > '". date("Y-m-d H:i:s", absint($args['since'] )). "'";
    }
    
    if( $args['start_date'] ) {
        $where .= " AND dateadded >= '". $args['start_date']. "'";
    }

    if( $args['end_date'] ) {
        $where .= " AND dateadded <= '". $args['end_date']. "'";
    }

    if( $args['no_steps'] == true ) {
        $settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
        $where .= " AND ( rank_type != '" .trim( $settings['ranks_step_post_type'] ). "')";
    }
    
    global $wpdb;
    
    $user_ranks = [];
    $table_name = $wpdb->prefix . 'badgeos_ranks';
    if( $args['total_only'] == true ) {
        $user_ranks = $wpdb->get_var( "SELECT count(id) as id FROM $table_name WHERE $where" );
    } else {
        
        $paginate_str = '';
        if( $args['pagination'] == true ||  $args['pagination'] == 'true' ) { 
            $offset = (intval( $args['page'] )-1) * intval( $args['limit'] );
            if( $offset < 0 ) {
                $offset = 0;
            }
            $paginate_str = ' limit '.$offset.', '.$args['limit'];
        }
        
        $order_str = '';
        if( !empty( $args['orderby'] ) &&  !empty( $args['order'] ) ) {
            $order_str = " ORDER BY ".$args['orderby']." ".$args['order'];
        }

        $user_ranks = $wpdb->get_results( "SELECT * FROM $table_name WHERE $where ".$order_str.' '.$paginate_str );
    }

    return $user_ranks;
}

/**
 * Return rank parent ID
 *
 * @param int $rank_requirement_id
 * @return array|bool|null|WP_Post
 */
function badgeos_get_rank_requirement_rank( $rank_requirement_id = 0 ) {

    /**
     * Grab the current post ID if no rank requirement ID was specified
     */
    if ( ! $rank_requirement_id ) {
        global $post;
        $rank_requirement_id = $post->ID;
    }

    /**
     * The rank requirement's rank is the post parent
     */
    $rank_id = absint( badgeos_get_parent_id( $rank_requirement_id ) );

    if( $rank_id !== 0 ) {

        /**
         * If has parent, return his post object
         */
        return badgeos_utilities::badgeos_get_post( $rank_id );
    } else {
        return false;
    }
}

/**
 * Return rank earned time
 *
 * @param int $user_id
 * @param string $rank_type
 * @return false|int
 */
function badgeos_get_rank_earned_time( $user_id = 0, $rank_type = '' ) {

    $earned_time = absint( badgeos_utilities::get_user_meta( $user_id, "_badgeos_{$rank_type}_rank_earned_time" ) );

    /**
     * If user has not earned a rank of this type, try to get the lowest priority rank and get its publish date
     */
    if( $earned_time === 0 ) {

        $rank = badgeos_get_user_rank( $user_id, $rank_type );
        if( $rank ) {
            $earned_time = strtotime( $rank->post_date );
        }
    }

    return $earned_time;
}

/**
 * Return rank requirement
 *
 * @param int $rank_id
 * @param string $post_status
 * @return array
 */
function badgeos_get_rank_requirements( $rank_id = 0, $post_status = 'publish' ) {

    global $wpdb;

    /**
     * Grab the current post ID if no rank_id was specified
     */
    if ( ! $rank_id ) {
        global $post;
        $rank_id = $post->ID;
    }

    $settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
    $requirements = $wpdb->get_results( $wpdb->prepare( "SELECT p.*, pp.* FROM $wpdb->p2p as pp inner join $wpdb->posts as p on(pp.p2p_from = p.ID) WHERE pp.p2p_to = %d and p.post_type = %s", $rank_id, trim( $settings['ranks_step_post_type'] )  ) );

    /**
     * Return rank requirements array
     */
    return $requirements;
}

/**
 * Return rank earners list
 *
 * @param int $rank_id
 * @return mixed|void
 */
function badgeos_get_rank_earners_list( $rank_id = 0 ) {

    /**
     * Grab our users
     */
    $earners = badgeos_get_rank_earners( $rank_id );
    $output = '';

    /**
     * Only generate output if we have earners
     */
    if ( ! empty( $earners ) )  {

        /**
         * Loop through each user and build our output
         */
        $output .= '<h4>' . apply_filters( 'badgeos_earners_heading', __( 'People who have reached this rank:', 'badgeos' ) ) . '</h4>';
        $output .= '<ul class="badgeos-rank-earners-list rank-' . $rank_id . '-earners-list">';
        foreach ( $earners as $user ) {
            $user_content = '<li><a href="' . get_author_posts_url( $user->ID ) . '">' . get_avatar( $user->ID ) . '</a></li>';
            $output .= apply_filters( 'badgeos_get_rank_earners_list_user', $user_content, $rank_id, $user->ID );
        }
        $output .= '</ul>';
    }

    /**
     * Return our concatenated output
     */
    return apply_filters( 'badgeos_get_rank_earners_list', $output, $rank_id, $earners );
}

/**
 * return rank earners
 *
 * @param int $rank_id
 * @return array
 */
function badgeos_get_rank_earners( $rank_id = 0 ) {

    global $wpdb;

    $rank_type = badgeos_utilities::get_post_type( $rank_id );

    $meta = '_badgeos_rank';

    if( ! empty( $rank_type ) ) {
        $meta = "_badgeos_{$rank_type}_rank";
    }

    $earners = $wpdb->get_col( $wpdb->prepare( "
		SELECT u.user_id
		FROM {$wpdb->usermeta} AS u
		WHERE  meta_key = %s
		       AND meta_value LIKE %s
		GROUP BY u.user_id
	",
        $meta,
        $rank_id
    ) );

    /**
     * Build an array of wp users based of IDs found
     */
    $earned_users = array();

    foreach( $earners as $earner_id ) {
        $earned_users[] = new WP_User( $earner_id );
    }

    return $earned_users;
}

/**
 * Return achievement object, if user already earned
 *
 * @param int $rank_id
 * @param int $user_id
 * @return bool
 */
function badgeos_user_has_a_rank( $rank_id = 0, $user_id = 0 ) {
    return badgeos_get_user_ranks( array(
        'user_id'   => $user_id,
        'rank_id'   => $rank_id,
    ) );
}

/**
 * Return given rank priority
 *
 * @param int $rank_id
 * @return int
 */
function badgeos_get_rank_priority( $rank_id = 0 ) {

    global $wpdb;

    if( badgeos_get_post_field( 'post_status', $rank_id ) === 'auto-draft' ) {

        $rank_type = badgeos_utilities::get_post_type( $rank_id );

        /**
         * Get higher menu order
         */
        $last = $wpdb->get_var( $wpdb->prepare(
            "SELECT p.menu_order
			FROM {$wpdb->posts} AS p
			WHERE p.post_type = %s
			 AND p.post_status = %s
			ORDER BY menu_order DESC
			LIMIT 1",
            $rank_type,
            'publish'
        ) );

        return absint( $last ) + 1;
    }

    return absint( badgeos_get_post_field( 'menu_order', $rank_id ) );
}

/**
 * Flush rewrite rules on publishing a rank
 *
 * @param $new_status
 * @param $old_status
 * @param $post
 */
function badgeos_flush_rewrite_on_published_rank( $new_status, $old_status, $post ) {
    
    $settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' )) ? $exists : array();
    if ( trim( $settings['ranks_main_post_type'] ) === $post->post_type && 'publish' === $new_status && 'publish' !== $old_status ) {
        badgeos_flush_rewrite_rules();
    }

    $rank_types = get_posts( array(
        'post_type'      =>	trim( $settings['ranks_main_post_type'] ),
        'posts_per_page' =>	-1,
    ) );
    foreach ( $rank_types as $rank_type ) {
        if ( trim( $rank_type->post_name ) === $post->post_type && 'publish' === $new_status && 'publish' !== $old_status ) {
            badgeos_flush_rewrite_rules();
        }
    }
}
add_action( 'transition_post_status', 'badgeos_flush_rewrite_on_published_rank', 10, 3 );

/**
 * Update all dependent data if rank type name has changed.
 *
 * @param array $data
 * @param array $post_args
 * @return array
 */
function badgeos_maybe_update_rank_type( $data = array(), $post_args = array() ) {

    /**
     * Bail if not is a points type
     */
    $settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
    if( $post_args['post_type'] !== trim( $settings['ranks_main_post_type'] )) {
        return $data;
    }
    
    /**
     * If user set an empty slug, then generate it
     */
    if( empty( $post_args['post_name'] ) ) {
        $post_args['post_name'] = wp_unique_post_slug(
            sanitize_title( $post_args['post_title'] ),
            $post_args['ID'],
            $post_args['post_status'],
            $post_args['post_type'],
            $post_args['post_parent']
        );
    }

    if ( badgeos_rank_type_changed( $post_args ) ) {

        $original_type = badgeos_utilities::badgeos_get_post( $post_args['ID'] )->post_name;
        $new_type = $post_args['post_name'];
        $data['post_name'] = badgeos_update_rank_types( $new_type, $new_type );
        add_filter( 'redirect_post_location', 'badgeos_rank_type_rename_redirect', 99 );
    }

    return $data;
}
add_filter( 'wp_insert_post_data' , 'badgeos_maybe_update_rank_type' , 99, 2 );

/**
 * Get badgeos Ranks Type list
 *
 * @return array An array of all our registered achievement types ( empty array if none )
 */
function badgeos_get_rank_types_list() {

	$settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();

	$rank_types = get_posts( array(
		'post_type'      =>	trim( $settings['ranks_main_post_type'] ),
		'posts_per_page' =>	-1,
	) );
	
	return $rank_types;
}

/**
 * Get badgeos Ranks Type Slugs
 *
 * @return array An array of all our registered achievement type slugs ( empty array if none )
 */
function badgeos_get_rank_types_slugs_detailed() {

	$settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();

	$rank_types = get_posts( array(
		'post_type'      =>	trim( $settings['ranks_main_post_type'] ),
		'posts_per_page' =>	-1,
	) );
	$main = array();
	foreach ( $rank_types as $rtype ) {
		$plural_name = badgeos_utilities::get_post_meta( $rtype->ID, '_badgeos_plural_name', true );
		$main[ $rtype->post_name ] = array( 'singular_name' => $rtype->post_title, 'plural_name' => $plural_name );
	}
	
	return $main;
}

/**
 * Update rank type
 *
 * @param array $post_args
 * @return bool
 */
function badgeos_rank_type_changed( $post_args = array() ) {

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return false;
    }

    $original_post = ( !empty( $post_args['ID'] ) && isset( $post_args['ID'] ) ) ? badgeos_utilities::badgeos_get_post( $post_args['ID'] ) : null;
    $status = false;
    $settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
    if ( is_object( $original_post ) ) {
        if (
            trim( $settings['ranks_main_post_type'] ) === $post_args['post_type']
            && $original_post->post_status !== 'auto-draft'
            && ! empty( $original_post->post_name )
            && $original_post->post_name !== $post_args['post_name']
        ) {
            $status = true;
        }
    }

    return $status;
}

/**
 * Replace all instances of one rank type with another.
 *
 * @param string $original_type
 * @param string $new_type
 * @return string
 */
function badgeos_update_rank_types( $original_type = '', $new_type = '' ) {

    /**
     * Sanity check to prevent alterating core posts
     */
    if ( empty( $original_type ) || in_array( $original_type, array( 'post', 'page', 'attachment', 'revision', 'nav_menu_item' ) ) ) {
        return $new_type;
    }

    badgeos_update_ranks_rank_types( $original_type, $new_type );
    badgeos_update_earned_meta_rank_types( $original_type, $new_type );

    /**
     * Action triggered when a rank type gets updated (called before flush rewrite rules)
     */
    do_action( 'badgeos_update_rank_type', $original_type, $new_type );

    badgeos_flush_rewrite_rules();

    return $new_type;
}

/**
 * Change all ranks of one type to a new type.
 *
 * @param string $original_type
 * @param string $new_type
 */
function badgeos_update_ranks_rank_types( $original_type = '', $new_type = '' ) {

    $items = get_posts( array(
        'posts_per_page'    => -1,
        'post_status'       => 'any',
        'post_type'         => $original_type,
        'fields'            => 'id',
        'suppress_filters'  => false,
    ) );

    foreach ( $items as $item ) {
        set_post_type( $item->ID, $new_type );
    }
}

/**
 * Change all earned meta from one rank type to another.
 *
 * @param string $original_type
 * @param string $new_type
 */
function badgeos_update_earned_meta_rank_types( $original_type = '', $new_type = '' ) {

    global $wpdb;

    $wpdb->get_results( $wpdb->prepare(
        "
		UPDATE $wpdb->usermeta
		SET meta_key = %s
		WHERE meta_key = %s
		",
        "_badgeos_{$new_type}_rank",
        "_badgeos_{$original_type}_rank"
    ) );
}

/**
 * Redirect to include custom rename message.
 *
 * @param string $location
 * @return string
 */
function badgeos_rank_type_rename_redirect( $location = '' ) {

    remove_filter( 'redirect_post_location', __FUNCTION__, 99 );

    return add_query_arg( 'message', 99, $location );
}

/**
 * Filter the "post updated" messages to include support for rank types.
 *
 * @param $messages
 * @return mixed
 */
function badgeos_rank_type_update_messages( $messages ) {
    $settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
    $messages[ trim( $settings['ranks_main_post_type'] ) ] = array_fill( 1, 10, __( 'Rank Type saved successfully.', 'badgeos' ) );
    $messages[ trim( $settings['ranks_main_post_type'] ) ][ '99' ] = sprintf( __('Rank Type renamed successfully. <p>All ranks of this type, and all active and earned user ranks, have been updated <strong>automatically</strong>.</p> All shortcodes, %s, and URIs that reference the old rank type slug must be updated <strong>manually</strong>.', 'badgeos'), '<a href="' . esc_url( admin_url( 'widgets.php' ) ) . '">' . __( 'widgets', 'badgeos' ) . '</a>' );

    return $messages;
}
add_filter( 'post_updated_messages', 'badgeos_rank_type_update_messages' );

/**
 * Revoke ranks from user profile
 */
function badgeos_revoke_rank_from_user_profile() {

    if( isset( $_POST['action'] ) && $_POST['action'] != 'user_profile_revoke_rank' ) {
        echo 'false';
        exit;
    }

    $rank_id = ( isset( $_POST['rankID'] ) ?  $_POST['rankID'] : '' );
    $user_id = ( isset( $_POST['userID'] ) ?  $_POST['userID'] : '' );

    if( empty( $rank_id ) || empty( $user_id ) ) {
        echo 'false';
        exit;
    }

    if( badgeos_revoke_rank_from_user_account( absint( $user_id ), absint( $rank_id ) ) ) {
        echo 'true';
        exit;
    }

    echo 'false';
    exit;
}
add_action( 'wp_ajax_user_profile_revoke_rank', 'badgeos_revoke_rank_from_user_profile' );

/**
 * Award ranks from user profile
 */
function badgeos_award_rank_from_user_profile() {

    if( isset( $_POST['action'] ) && $_POST['action'] != 'user_profile_award_rank' ) {
        echo 'false';
        exit;
    }

    $rank_id = ( isset( $_POST['rank_id'] ) ?  $_POST['rank_id'] : '' );
    $user_id = ( isset( $_POST['user_id'] ) ?  $_POST['user_id'] : '' );

    if( empty( $rank_id ) || empty( $user_id ) ) {
        echo 'false';
        exit;
    }

    badgeos_update_user_rank( array(
        'user_id'           => $user_id,
        'site_id'           => get_current_blog_id(),
        'rank_id'           => (int) $rank_id,
        'this_trigger'      => 'admin_awarded',
        'credit_id'         => 0,
        'credit_amount'     => 0,
        'admin_id'          => ( current_user_can( 'administrator' ) ? get_current_user_id() : 0 ),
    )  );

    echo 'true';
    exit;
}
add_action( 'wp_ajax_user_profile_award_rank', 'badgeos_award_rank_from_user_profile' );

/**
 * Display ranks to award
 */
function badgeos_display_ranks_to_award() {

    if( isset( $_POST['action'] ) && $_POST['action'] != 'user_profile_display_award_list' ) {
        echo 'false';
        exit;
    }

    $user_id = ( isset( $_POST['user_id'] ) ? $_POST['user_id'] : '' );
    $selected = ( isset( $_POST['rank_filter'] ) ? $_POST['rank_filter'] : 'all' );

    if( empty( $selected ) ) {
        $selected = 'all';
    }

    if( empty( $user_id ) ) {
        echo 'false';
        exit;
    }

    $rank_types = badgeos_get_rank_types_slugs_detailed();
    if ( is_array( $rank_types ) && ! empty( $rank_types ) ) {

        $return = '<table class="form-table badgeos-rank-table badgeos-rank-table-to-award">';
        $return .= '<thead>';
        $return .= '<tr>';
        $return .= '<th>' . __( 'Image', 'badgeos' ) . '</th>';
        $return .= '<th>' . __( 'Name', 'badgeos' ) . '</th>';
        $return .= '<th>' . __( 'Rank Type', 'badgeos' ) . '</th>';
        $return .= '<th>' . __( 'Action', 'badgeos' ) . '</th>';
        $return .= '</tr>';
        $return .= '</thead>';
        $return .= '<tbody>';
        $earned_ranks = array();
        $user_ranks = badgeos_get_user_ranks( array(
            'user_id' => absint( $user_id ),
            'rank_type' => $selected,
        ) );

        if( $user_ranks ) {
            foreach ( $user_ranks as $user_rank ) {
                $earned_ranks[] = $user_rank->rank_id;
            }
        }

        $not_available = true;
        foreach ( $rank_types as $key => $rank_type ) {
            if( $key == $selected || 'all' == $selected ) {
                $ranks_to_award = new WP_Query( array( 'post_type' => $key, 'post__not_in' => $earned_ranks ) );

                if( $ranks_to_award->have_posts() ) {
                    $not_available = false;
                    while( $ranks_to_award->have_posts() ) {
                        $ranks_to_award->the_post();
                        $ranks_image = badgeos_get_rank_image( get_the_ID(), 50, 50 );

                        /**
                         * If not default rank, bail here
                         */
                        $rank = badgeos_utilities::badgeos_get_post( get_the_ID() );
                        $id = '';
                        if( $rank->menu_order < 1 ) {
                            $id = 'default-rank';
                        }

                        $return .= '<tr class="'. badgeos_utilities::get_post_type( get_the_ID() ) .'" id="'. $id .'">';
                        $return .= '<td>' . $ranks_image . '</td>';
                        $return .= '<td>' . get_the_title() . '</td>';
                        $return .= '<td>' . badgeos_utilities::get_post_type( get_the_ID() ) . '</td>';
                        $return .= '<td class="award-rank-column"><span class="award-rank" data-user-id="'. $user_id .'" data-admin-ajax="'. admin_url( 'admin-ajax.php' ) .'" data-rank-id="'. get_the_ID() .'">';
                        $return .= __( 'Award Rank', 'badgeos' );
                        $return .= '</span>';
                        $return .= '<span class="spinner-loader" >';
                        $return .= '<img class="award-rank-spinner" src="' . admin_url( '/images/wpspin_light.gif' ) . '" style="margin-left: 10px; display: none;" />';
                        $return .= '</span>';
                        $return .= '</td>';
                        $return .= '</tr>';
                    }
                }
            }
        }

        if( $not_available ) {
            $return .= '<tr>';
            $return .= '<td colspan="4">' . __( 'No Available Ranks', 'badgeos' ) . '</td>';
            $return .= '</tr>';
        }

        $return .= '</tbody>';
        $return .= '<tfoot>';
        $return .= '<tr>';
        $return .= '<th>' . __( 'Image', 'gamibadgeosfywp' ) . '</th>';
        $return .= '<th>' . __( 'Name', 'badgeos' ) . '</th>';
        $return .= '<th>' . __( 'Rank Type', 'badgeos' ) . '</th>';
        $return .= '<th>' . __( 'Action', 'badgeos' ) . '</th>';
        $return .= '</tr>';
        $return .= '</tfoot>';

        echo $return;
        exit;
    }
}
add_action( 'wp_ajax_user_profile_display_award_list', 'badgeos_display_ranks_to_award' );

/**
 * Award ranks using get parameters
 */
function badgeos_award_rank_using_get_param() {

    if( is_admin() ) {
        return;
    }

    $user_id = ( isset( $_GET['user_id'] ) ? $_GET['user_id'] : 0 );
    $rank_id = ( isset( $_GET['rank_id'] ) ? $_GET['rank_id'] : 0 );

    if( $user_id != 0 && $rank_id != 0 ) {
        
        badgeos_update_user_rank( array(
            'user_id'           => $user_id,
            'site_id'           => get_current_blog_id(),
            'rank_id'           => (int) $rank_id,
            'credit_id'         =>  0,
            'credit_amount'     => 0,
            'admin_id'          => ( current_user_can( 'administrator' ) ? get_current_user_id() : 0 ),
        )  );
    }
}
add_action( 'admin_init', 'badgeos_award_rank_using_get_param' );

/**
 * Award default ranks to the user
 * on Login | Registration
 *
 * @param $user_id
 */
function award_default_ranks_to_user( $user_id ) {
    if( ! is_int( $user_id ) ) {
        $user = get_user_by( 'login', $user_id );
        if($user){
            $user_id = $user->ID;
        }
    }

    $rank_types = badgeos_get_rank_types_slugs_detailed();
    if ( is_array( $rank_types ) && ! empty( $rank_types ) ) {
        foreach ( $rank_types as $key => $rank_type ) {
            $ranks_to_award = new WP_Query( array(
                'post_type'         =>  $key,
                'post_status'       =>  'publish'
            ) );

            if( $ranks_to_award->have_posts() ) {
                $earned_ranks = array();
                $user_ranks = badgeos_get_user_ranks( array(
                    'user_id' => absint( $user_id )
                ) );

                if( $user_ranks ) {
                    foreach ( $user_ranks as $user_rank ) {
                        $earned_ranks[] = $user_rank->rank_id;
                    }
                }

                while( $ranks_to_award->have_posts() ) {
                    $ranks_to_award->the_post();

                    /**
                     * If not default rank, bail here
                     */
                    $rank = badgeos_utilities::badgeos_get_post( get_the_ID() );
                    if( $rank->menu_order > 0 ) {
                        continue;
                    }

                    if( ! in_array( get_the_ID(), $earned_ranks ) ) {
                        badgeos_update_user_rank( array(
                            'user_id'           => $user_id,
                            'site_id'           => get_current_blog_id(),
                            'rank_id'           => get_the_ID(),
                            'this_trigger'      => 'default_awarded',
                            'credit_id'         => 0,
                            'credit_amount'     => 0,
                            'admin_id'          => ( current_user_can( 'administrator' ) ? get_current_user_id() : 0 ),
                        )  );
                    }
                }
            }
        }
    }
}
add_action( 'user_register', 'award_default_ranks_to_user' );
add_action( 'wp_login', 'award_default_ranks_to_user' );

/**
 * Add user ranks to the db
 *
 * @param int $rank_id
 * @param int $user_id
 * @param string $rank_type
 * @return bool|int
 */
function badgeos_add_rank( $args = array() ) {

    /**
     * Setup our default args
     */
    $defaults = array(
        'user_id'           => wp_get_current_user()->ID,
        'site_id'           => get_current_blog_id(),
        'all_ranks'         => false,
        'new_rank'          => false,
        'this_trigger'      => 'admin_awarded',
        'credit_id'         => 0,
        'credit_amount'     => 0,
        'admin_id'          => ( current_user_can( 'administrator' ) ? get_current_user_id() : 0 ),
    );
    $args = wp_parse_args( $args, $defaults );

    /**
     * Save last achievement
     */
    $new_rank = $args['new_rank'];
    if( $new_rank !== false ) {
        
        if( ( ! isset( $args['credit_id'] ) || intval( $args['credit_id'] ) == 0 ) || ( ! isset( $args['credit_amount'] ) || intval( $args['credit_amount'] ) == 0 ) ) {
            $points = badgeos_utilities::get_post_meta( $new_rank->ID, '_ranks_points', true );
            if( is_array( $points ) && count( $points ) > 0 ) {
                if( array_key_exists( '_ranks_points', $points ) && array_key_exists( '_ranks_points_type', $points ) )  {
                    $args['credit_amount']  = $points['_ranks_points'];
                    $args['credit_id']	    = $points['_ranks_points_type'];
                } 
            }
        }
        
        /**
         * Available action to trigger
         * Before awarding
         */
        do_action( 'badgeos_before_award_rank',
            absint( $args['user_id'] ),
            $new_rank->ID,
            $new_rank->post_type,
            $args['credit_id'],
            $args['credit_amount'], 
            $args['admin_id'],
            $args['this_trigger']
        );
        
        $priority = get_post_field( 'menu_order', $new_rank->ID );

        global $wpdb;
        $wpdb->insert($wpdb->prefix.'badgeos_ranks', array(
            'rank_id'               => $new_rank->ID,
            'rank_type'             => $new_rank->post_type,
            'rank_title'            => get_the_title( $new_rank->ID ),
            'credit_id'             => $args['credit_id'],
            'credit_amount'         => $args['credit_amount'],
            'user_id'               => absint( $args['user_id'] ),
            'admin_id'              => $args['admin_id'],
            'this_trigger'          => $args['this_trigger'],
            'priority'              => $priority,
            'actual_date_earned'    => badgeos_default_datetime( 'mysql_ranks' ),
            'dateadded'             => current_time( 'mysql' )
        ));

        $rank_entry_id = $wpdb->insert_id;

        badgeos_utilities::update_user_meta( absint( $args['user_id'] ), '_badgeos_'. $new_rank->post_type .'_rank', $rank_entry_id );
        badgeos_utilities::update_user_meta( absint( $args['user_id'] ), '_badgeos_'. $new_rank->post_type .'_rank_earned_time', $new_rank->date_earned );

        /**
         * Available action to trigger
         * After awarding
         */
        do_action( 'badgeos_after_award_rank',
            absint( $args['user_id'] ),
            $new_rank->ID,
            $new_rank->post_type,
            $args['credit_id'],
            $args['credit_amount'],
            $args['admin_id'],
            $args['this_trigger'],
            $rank_entry_id
        );

        if( ( isset( $args['credit_id'] ) && intval( $args['credit_id'] ) > 0 ) && ( isset( $args['credit_amount'] ) && intval( $args['credit_amount'] ) > 0 ) ) {
            badgeos_award_credit( $args['credit_id'], $args['user_id'], 'Award', $args['credit_amount'], 'user_ranks_awarded_points', $args['user_id'], '', $new_rank->ID );
        }
        

        return $wpdb->insert_id;
    }

	return false;
}

/**
 * Remove user ranks from the db
 *
 * @param $rank_id
 * @param $user_id
 * @return bool|false|int
 */
function badgeos_remove_rank( $ranks = array() ) {

    $site_id = get_current_blog_id();

    /**
     * Setup our default args
     */
    $defaults = array(
        'rank_id'        => 0,
        'entry_id'       => 0,
        'user_id'        => wp_get_current_user()->ID,
    );
    $args = wp_parse_args( $ranks, $defaults );
    $rank_id = $args['rank_id'];

    /**
     * Bail here
     */
    if( $args['rank_id'] == 0 && $args['entry_id'] == 0 ) {
        return;
    }

    /**
     * Revoke Rank
     */
    global $wpdb;

    $requirements = badgeos_get_rank_requirements( $rank_id );

    $where = array( 'user_id' => $args['user_id'] );

    if( $args['entry_id'] != 0 ) {
        $where['id'] = $args['entry_id'];
    }

    if( $args['rank_id'] != 0 ) {
        $where['rank_id'] = $args['rank_id'];
    }

    /**
     * Available action to trigger
     * Before revoking
     */
    do_action( 'badgeos_before_revoke_rank',
        absint( $args['user_id'] ),
        $args['rank_id'],
        $args['entry_id']
    );

    foreach( $requirements as $req ) {
        $recs = $wpdb->get_results( $wpdb->prepare("SELECT id, this_trigger FROM ".$wpdb->prefix."badgeos_ranks where rank_id = %d and user_id=%d", $req->ID, $args['user_id']	) );
        if( count( $recs ) > 0 ) {
            $trigger = $recs[0]->this_trigger;

            $user_triggers = badgeos_ranks_get_user_triggers( $req->ID, $args['user_id'], false  );
            $user_triggers[ $site_id ][ $trigger . "_" . $req->ID ] = 0;

            badgeos_utilities::update_user_meta( $args['user_id'], '_badgeos_ranks_triggers', $user_triggers );
        }

        $where_req = array( 'user_id' => $args['user_id'] );
        $where_req['rank_id'] = $req->ID;

        $wpdb->delete( $wpdb->prefix.'badgeos_ranks', $where_req );
    }


    $wpdb->delete($wpdb->prefix.'badgeos_ranks', $where );

    /**
     * Available action to trigger
     * After revoking
     */
    do_action( 'badgeos_after_revoke_rank',
        absint( $args['user_id'] ),
        $args['rank_id'],
        $args['entry_id']
    );
}

/**
 * Get Badgeos Ranks Type Slugs
 *
 * @return array An array of all our registered achievement type slugs (empty array if none)
 */
function badgeos_get_rank_types_slugs() {

	/**
     * Assume we have no registered achievement types
     */
	$ranks_type_slugs = array();

	/**
     * If we do have any achievement types, loop through each and add their slug to our array
     */
	if ( isset( $GLOBALS['badgeos']->ranks_types ) && ! empty( $GLOBALS['badgeos']->ranks_types ) ) {
		foreach ( $GLOBALS['badgeos']->ranks_types as $slug => $data )
			$ranks_type_slugs[] = $slug;
	}

	/**
     * Finally, return our data
     */
	return $ranks_type_slugs;
}

/**
 * Helper function to get a transient value.
 *
 * @param string    $transient
 * @return mixed Value of transient.
 */
function badgeos_get_transient($transient ) {

    /**
     * If badgeos is installed network wide, get transient from network
     */
    return get_transient( $transient );
}

/**
 * Helper function to set a transient value.
 *
 * @param string    $transient
 * @param mixed     $value
 * @param integer   $expiration
 */
function badgeos_set_transient( $transient, $value, $expiration = 0 ) {

    set_transient( $transient, $value, $expiration );
}

/**
 * Return rank default image
 *
 * @param int $rank_id
 * @param string $rank_width
 * @param string $rank_height
 * 
 * @return $ranks_image
 */
function badgeos_get_rank_image( $rank_id = 0, $rank_width = '', $rank_height = '' ) {
    
    $badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
 
    if( intval( $rank_width ) == 0 ) {
        $rank_width = '50';
        if( isset( $badgeos_settings['badgeos_rank_global_image_width'] ) && intval( $badgeos_settings['badgeos_rank_global_image_width'] ) > 0 ) {
            $rank_width = intval( $badgeos_settings['badgeos_rank_global_image_width'] );
        }
    }
    
    if( intval( $rank_height ) == 0 ) {
        $rank_height = '50';
        if( isset( $badgeos_settings['badgeos_rank_global_image_height'] ) && intval( $badgeos_settings['badgeos_rank_global_image_height'] ) > 0 ) {
            $rank_height = intval( $badgeos_settings['badgeos_rank_global_image_height'] );
        }
    }

    $ranks_image = wp_get_attachment_image( get_post_thumbnail_id( $rank_id ), array( $rank_width, $rank_height ) );
    
    $ranks_image = apply_filters( 'badgeos_rank_post_thumbnail', $ranks_image, $rank_id, $rank_width, $rank_height );
    
    if( empty( $ranks_image ) ) {
        
        $default_image = apply_filters( 'badgeos_default_rank_post_thumbnail', badgeos_get_directory_url() . 'images/rank-default.png', $rank_id, $rank_width, $rank_height );
        $ranks_image = '<img src="'.$default_image.'" width="'.$rank_width.'px" height="'.$rank_height.'px" />';
    }

    return $ranks_image;
}

/**
 * Set default ranks image on achievement post save
 *
 * @param integer $post_id The post ID of the post being saved
 * @return mixed    post ID if nothing to do, void otherwise.
 */
function badgeos_ranks_set_default_thumbnail( $post_id ) {

    global $pagenow;

    $badgeos_settings = badgeos_utilities::get_option( 'badgeos_settings' );
    if (
        ! (
            badgeos_is_rank( $post_id )
            || $badgeos_settings['ranks_main_post_type'] == badgeos_utilities::get_post_type( $post_id )
        )
        || ( defined('DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        || ! current_user_can( 'edit_post', $post_id )
        || has_post_thumbnail( $post_id )
        || 'post-new.php' == $pagenow
    ) {
        return $post_id;
    }

    $thumbnail_id = 0;

    // If there is no thumbnail set, load in our default image
    if ( empty( $thumbnail_id ) ) {
        global $wpdb;

        $rank = get_page_by_path( badgeos_utilities::get_post_type( $post_id ), OBJECT, trim( $badgeos_settings['ranks_main_post_type'] ) );

        // Grab the default image
        $file = apply_filters( 'badgeos_default_rank_post_thumbnail', badgeos_get_directory_url().'images/rank-main.png', $rank, array() );

        // Download file to temp location
        $tmp = download_url( $file );

        // Set variables for storage
        // fix file filename for query strings
        preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );
        $file_array['name']     = basename( $matches[0] );
        $file_array['tmp_name'] = $tmp;

        // If error storing temporarily, unlink
        if ( is_wp_error( $tmp ) ) {
            @unlink( $file_array['tmp_name'] );
            $file_array['tmp_name'] = '';
        }

        // Upload the image
        $thumbnail_id = media_handle_sideload( $file_array, $post_id );

        // If upload errored, unlink the image file
        if ( empty( $thumbnail_id ) || is_wp_error( $thumbnail_id ) ) {
            @unlink( $file_array['tmp_name'] );

            // Otherwise, if the achievement type truly doesn't have
            // a thumbnail already, set this as its thumbnail, too.
            // We do this so that WP won't upload a duplicate version
            // of this image for every single achievement of this type.
        } elseif (
            badgeos_is_rank( $post_id )
            && is_object( $achievement_type )
            && ! get_post_thumbnail_id( $achievement_type->ID )
        ) {
            set_post_thumbnail( $achievement_type->ID, $thumbnail_id );
            badgeos_flush_rewrite_rules();
        }
    }

    // Finally, if we have an image, set the thumbnail for our achievement
    if ( $thumbnail_id && ! is_wp_error( $thumbnail_id ) ) {
        set_post_thumbnail( $post_id, $thumbnail_id );
        badgeos_flush_rewrite_rules();
    }
}
add_action( 'save_post', 'badgeos_ranks_set_default_thumbnail' );