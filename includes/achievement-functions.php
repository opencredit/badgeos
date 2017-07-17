<?php
/**
 * Achievement Functions
 *
 * @package BadgeOS
 * @subpackage Achievements
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Check if post is a registered BadgeOS achievement.
 *
 * @since  1.3.0
 *
 * @param  object|int $post Post object or ID.
 * @return bool             True if post is an achievement, otherwise false.
 */
function badgeos_is_achievement( $post = null ) {

	// Assume we are working with an achievment object
	$return = true;

	// If post type is NOT a registered achievement type, it cannot be an achievement
	if ( ! in_array( get_post_type( $post ), badgeos_get_achievement_types_slugs() ) ) {
		$return = false;
	}

	// If we pass both previous tests, this is a valid achievement (with filter to override)
	return apply_filters( 'badgeos_is_achievement', $return, $post );
}

/**
 * Get an array of achievements
 *
 * @since  1.0.0
 * @param  array $args An array of our relevant arguments
 * @return array       An array of the queried achievements
 */
function badgeos_get_achievements( $args = array() ) {

	// Setup our defaults
	$defaults = array(
		'post_type'                => badgeos_get_achievement_types_slugs(),
		'suppress_filters'         => false,
		'achievement_relationsihp' => 'any',
	);
	$args = wp_parse_args( $args, $defaults );

	// Hook join functions for joining to P2P table to retrieve the parent of an acheivement
	if ( isset( $args['parent_of'] ) ) {
		add_filter( 'posts_join', 'badgeos_get_achievements_parents_join' );
		add_filter( 'posts_where', 'badgeos_get_achievements_parents_where', 10, 2 );
	}

	// Hook join functions for joining to P2P table to retrieve the children of an acheivement
	if ( isset( $args['children_of'] ) ) {
		add_filter( 'posts_join', 'badgeos_get_achievements_children_join', 10, 2 );
		add_filter( 'posts_where', 'badgeos_get_achievements_children_where', 10, 2 );
		add_filter( 'posts_orderby', 'badgeos_get_achievements_children_orderby' );
	}

	// Get our achievement posts
	$achievements = get_posts( $args );

	// Remove all our filters
	remove_filter( 'posts_join', 'badgeos_get_achievements_parents_join' );
	remove_filter( 'posts_where', 'badgeos_get_achievements_parents_where' );
	remove_filter( 'posts_join', 'badgeos_get_achievements_children_join' );
	remove_filter( 'posts_where', 'badgeos_get_achievements_children_where' );
	remove_filter( 'posts_orderby', 'badgeos_get_achievements_children_orderby' );

	return $achievements;
}

/**
 * Modify the WP_Query Join filter for achievement children
 *
 * @since  1.0.0
 * @param  string $join         The query "join" string
 * @param  object $query_object The complete query object
 * @return string 				The updated "join" string
 */
function badgeos_get_achievements_children_join( $join = '', $query_object = null ) {
	global $wpdb;
	$join .= " LEFT JOIN $wpdb->p2p AS p2p ON p2p.p2p_from = $wpdb->posts.ID";
	if ( isset( $query_object->query_vars['achievement_relationship'] ) && $query_object->query_vars['achievement_relationship'] != 'any' )
		$join .= " LEFT JOIN $wpdb->p2pmeta AS p2pm1 ON p2pm1.p2p_id = p2p.p2p_id";
	$join .= " LEFT JOIN $wpdb->p2pmeta AS p2pm2 ON p2pm2.p2p_id = p2p.p2p_id";
	return $join;
}

/**
 * Modify the WP_Query Where filter for achievement children
 *
 * @since  1.0.0
 * @param  string $where        The query "where" string
 * @param  object $query_object The complete query object
 * @return string 				The updated query "where" string
 */
function badgeos_get_achievements_children_where( $where = '', $query_object ) {
	global $wpdb;
	if ( isset( $query_object->query_vars['achievement_relationship'] ) && $query_object->query_vars['achievement_relationship'] == 'required' )
		$where .= " AND p2pm1.meta_key ='Required'";

	if ( isset( $query_object->query_vars['achievement_relationship'] ) && $query_object->query_vars['achievement_relationship'] == 'optional' )
		$where .= " AND p2pm1.meta_key ='Optional'";
	// ^^ TODO, add required and optional. right now just returns all achievemnts.
	$where .= " AND p2pm2.meta_key ='order'";
	$where .= $wpdb->prepare( ' AND p2p.p2p_to = %d', $query_object->query_vars['children_of'] );
	return $where;
}

/**
 * Modify the WP_Query OrderBy filter for achievement children
 *
 * @since  1.0.0
 * @param  string $orderby The query "orderby" string
 * @return string 		   The updated "orderby" string
 */
function badgeos_get_achievements_children_orderby( $orderby = '' ) {
	return $orderby = 'p2pm2.meta_value ASC';
}

/**
 * Modify the WP_Query Join filter for achievement parents
 *
 * @since  1.0.0
 * @param  string $join The query "join" string
 * @return string 	    The updated "join" string
 */
function badgeos_get_achievements_parents_join( $join = '' ) {
	global $wpdb;
	$join .= " LEFT JOIN $wpdb->p2p AS p2p ON p2p.p2p_to = $wpdb->posts.ID";
	return $join;
}

/**
 * Modify the WP_Query Where filter for achievement parents
 *
 * @since  1.0.0
 * @param  string $where The query "where" string
 * @param  object $query_object The complete query object
 * @return string        appended sql where statement
 */
function badgeos_get_achievements_parents_where( $where = '', $query_object = null ) {
	global $wpdb;
	$where .= $wpdb->prepare( ' AND p2p.p2p_from = %d', $query_object->query_vars['parent_of'] );
	return $where;
}

/**
 * Get BadgeOS Achievement Types
 *
 * Returns a multidimensional array of slug, single name and
 * plural name for all achievement types.
 *
 * @since  1.0.0
 * @return array An array of our registered achievement types
 */
function badgeos_get_achievement_types() {
	return $GLOBALS['badgeos']->achievement_types;
}

/**
 * Get BadgeOS Achievement Type Slugs
 *
 * @since  1.0.0
 * @return array An array of all our registered achievement type slugs (empty array if none)
 */
function badgeos_get_achievement_types_slugs() {

	// Assume we have no registered achievement types
	$achievement_type_slugs = array();

	// If we do have any achievement types, loop through each and add their slug to our array
	if ( isset( $GLOBALS['badgeos']->achievement_types ) && ! empty( $GLOBALS['badgeos']->achievement_types ) ) {
		foreach ( $GLOBALS['badgeos']->achievement_types as $slug => $data )
			$achievement_type_slugs[] = $slug;
	}

	// Finally, return our data
	return $achievement_type_slugs;
}

/**
 * Get an achievement's parent posts
 *
 * @since  1.0.0
 * @param  integer     $achievement_id The given achievment's post ID
 * @return object|bool                 The post object of the achievement's parent, or false if none
 */
function badgeos_get_parent_of_achievement( $achievement_id = 0 ) {

	// Grab the current post ID if no achievement_id was specified
	if ( ! $achievement_id ) {
		global $post;
		$achievement_id = $post->ID;
	}

	// Grab our achievement's parent
	$parents = badgeos_get_achievements( array( 'parent_of' => $achievement_id ) );

	// If it has a parent, return it, otherwise return false
	if ( ! empty( $parents ) )
		return $parents[0];
	else
		return false;
}

/**
 * Get an achievement's children posts
 *
 * @since  1.0.0
 * @param  integer $achievement_id The given achievment's post ID
 * @return array                   An array of our achievment's children (empty if none)
 */
function badgeos_get_children_of_achievement( $achievement_id = 0 ) {

	// Grab the current post ID if no achievement_id was specified
	if ( ! $achievement_id ) {
		global $post;
		$achievement_id = $post->ID;
	}

	// Grab and return our achievement's children
	return badgeos_get_achievements( array( 'children_of' => $achievement_id, 'achievement_relationship' => 'required' ) );
}

/**
 * Check if the achievement's child achievements must be earned sequentially
 *
 * @since  1.0.0
 * @param  integer $achievement_id The given achievment's post ID
 * @return bool                    True if steps are sequential, false otherwise
 */
function badgeos_is_achievement_sequential( $achievement_id = 0 ) {

	// Grab the current post ID if no achievement_id was specified
	if ( ! $achievement_id ) {
		global $post;
		$achievement_id = $post->ID;
	}

	// If our achievement requires sequential steps, return true, otherwise false
	if ( get_post_meta( $achievement_id, '_badgeos_sequential', true ) )
		return true;
	else
		return false;
}

/**
 * Check if user has already earned an achievement the maximum number of times
 *
 * @since  1.0.0
 * @param  integer $user_id        The given user's ID
 * @param  integer $achievement_id The given achievement's post ID
 * @return bool                    True if we've exceed the max possible earnings, false if we're still eligable
 */
function badgeos_achievement_user_exceeded_max_earnings( $user_id = 0, $achievement_id = 0 ) {

	$max_earnings = get_post_meta( $achievement_id, '_badgeos_maximum_earnings', true);

	//Infinite maximum earnings check
    if($max_earnings == '-1'){
		return false;
	}

	// If the badge has an earning limit, and we've earned it bdfore...
	if ( $max_earnings && $user_has_badge = badgeos_get_user_achievements( array( 'user_id' => absint( $user_id ), 'achievement_id' => absint( $achievement_id ) ) ) ) {
		// If we've earned it as many (or more) times than allowed,
		// then we have exceeded maximum earnings, thus true
		if ( count( $user_has_badge ) >= $max_earnings ) {
			return true;
		}
	}

	// The post has no limit, or we're under it
	return false;
}

/**
 * Helper function for building an object for our achievement
 *
 * @since  1.0.0
 * @param  integer $achievement_id The given achievement's post ID
 * @param  string  $context        The context in which we're creating this object
 * @return object                  Our object containing only the relevant bits of information we want
 */
function badgeos_build_achievement_object( $achievement_id = 0, $context = 'earned' ) {

	// Grab the new achievement's $post data, and bail if it doesn't exist
	$achievement = get_post( $achievement_id );
	if ( is_null( $achievement ) )
		return false;

	// Setup a new object for the achievement
	$achievement_object            = new stdClass;
	$achievement_object->ID        = $achievement_id;
	$achievement_object->post_type = $achievement->post_type;
	$achievement_object->points    = get_post_meta( $achievement_id, '_badgeos_points', true );

	// Store the current timestamp differently based on context
	if ( 'earned' == $context ) {
		$achievement_object->date_earned = time();
	} elseif ( 'started' == $context ) {
		$achievement_object->date_started = $achievement_object->last_activity_date = time();
	}

	// Return our achievement object, available filter so we can extend it elsewhere
	return apply_filters( 'achievement_object', $achievement_object, $achievement_id, $context );

}

/**
 * Get an array of post IDs for achievements that are marked as "hidden"
 *
 * @since  1.0.0
 * @param  string $achievement_type Limit the array to a specific type of achievement
 * @return array                    An array of hidden achivement post IDs
 */
function badgeos_get_hidden_achievement_ids( $achievement_type = '' ) {

	// Assume we have no hidden achievements
	$hidden_ids = array();

	// Grab our hidden achievements
	$hidden_achievements = get_posts( array(
		'post_type'      => $achievement_type,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_key'       => '_badgeos_hidden',
		'meta_value'     => 'hidden'
	) );

	foreach ( $hidden_achievements as $achievement )
		$hidden_ids[] = $achievement->ID;

	// Return our results
	return $hidden_ids;
}

/**
 * Get an array of post IDs for achievements that are marked as "hidden"
 *
 * @since  1.0.0
 * @param  integer $achievement_id Limit the array to a specific id of achievement
 * @return array  An array of hidden achivement post IDs
 */

function badgeos_get_hidden_achievement_by_id( $achievement_id ) {

	// Grab our hidden achievements
	global $wpdb;

	//Get hidden submission posts.
	$hidden_achievements = $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM {$wpdb->posts} AS p
                                 JOIN {$wpdb->postmeta} AS pm
                                 ON p.ID = pm.post_id
                                 WHERE p.ID = %d
                                 AND pm.meta_key = '_badgeos_hidden'
                                 AND pm.meta_value = 'hidden'
                                 ",
		$achievement_id));

	// Return our results
	return $hidden_achievements;
}

/**
 * Get an array of post IDs for a user's earned achievements
 *
 * @since  1.0.0
 * @param  integer $user_id          The given user's ID
 * @param  string  $achievement_type Limit the array to a specific type of achievement
 * @return array                     Our user's array of earned achivement post IDs
 */
function badgeos_get_user_earned_achievement_ids( $user_id = 0, $achievement_type = '' ) {

	// Assume we have no earned achievements
	$earned_ids = array();

	// Grab our earned achievements
	$earned_achievements = badgeos_get_user_achievements( array(
		'user_id'          => $user_id,
		'achievement_type' => $achievement_type,
		'display' => true
	) );

	foreach ( $earned_achievements as $achievement )
		$earned_ids[] = $achievement->ID;

	return $earned_ids;

}

/**
 * Get an array of unique achievement types a user has earned
 *
 * @since  1.0.1
 *
 * @param  int  $user_id The ID of the user earning the achievement
 * @return array 		 The array of achievements the user has earned
 */
function badgeos_get_user_earned_achievement_types( $user_id = 0 ){

	$achievements = badgeos_get_user_achievements( array( 'user_id' => $user_id ) );

	$achievement_types = wp_list_pluck( $achievements, 'post_type' );

	return array_unique( $achievement_types );
}

/**
 * Returns achievements that may be earned when the given achievement is earned.
 *
 * @since  1.0.0
 * @param  integer $achievement_id The given achievement's post ID
 * @return array                   An array of achievements that are dependent on the given achievement
 */
function badgeos_get_dependent_achievements( $achievement_id = 0 ) {
	global $wpdb;

	// Grab the current achievement ID if none specified
	if ( ! $achievement_id ) {
		global $post;
		$achievement_id = $post->ID;
	}

	// Grab posts that can be earned by unlocking the given achievement
	$specific_achievements = $wpdb->get_results( $wpdb->prepare(
		"
		SELECT *
		FROM   $wpdb->posts as posts,
		       $wpdb->p2p as p2p
		WHERE  posts.ID = p2p.p2p_to
		       AND p2p.p2p_from = %d
		",
		$achievement_id
	) );

	// Grab posts triggered by unlocing any/all of the given achievement's type
	$type_achievements = $wpdb->get_results( $wpdb->prepare(
		"
		SELECT *
		FROM   $wpdb->posts as posts,
		       $wpdb->postmeta as meta
		WHERE  posts.ID = meta.post_id
		       AND meta.meta_key = '_badgeos_achievement_type'
		       AND meta.meta_value = %s
		",
		get_post_type( $achievement_id )
	) );

	// Merge our dependent achievements together
	$achievements = array_merge( $specific_achievements, $type_achievements );

	// Available filter to modify an achievement's dependents
	return apply_filters( 'badgeos_dependent_achievements', $achievements, $achievement_id );
}

/**
 * Returns achievements that must be earned to earn given achievement.
 *
 * @since  1.0.0
 * @param  integer $achievement_id The given achievement's post ID
 * @return array                   An array of achievements that are dependent on the given achievement
 */
function badgeos_get_required_achievements_for_achievement( $achievement_id = 0 ) {
	global $wpdb;

	// Grab the current achievement ID if none specified
	if ( ! $achievement_id ) {
		global $post;
		$achievement_id = $post->ID;
	}

	// Don't retrieve requirements if achievement is not earned by steps
	if ( get_post_meta( $achievement_id, '_badgeos_earned_by', true ) != 'triggers' )
		return false;

	// Grab our requirements for this achievement
	$requirements = $wpdb->get_results( $wpdb->prepare(
		"
		SELECT   *
		FROM     $wpdb->posts as posts
		         LEFT JOIN $wpdb->p2p as p2p
		                   ON p2p.p2p_from = posts.ID
		         LEFT JOIN $wpdb->p2pmeta AS p2pmeta
		                   ON p2p.p2p_id = p2pmeta.p2p_id
		WHERE    p2p.p2p_to = %d
		         AND p2pmeta.meta_key = %s
		ORDER BY CAST( p2pmeta.meta_value as SIGNED ) ASC
		",
		$achievement_id,
		'order'
	) );

	return $requirements;
}

/**
 * Returns achievements that may be earned when the given achievement is earned.
 *
 * @since  1.0.0
 * @return array An array of achievements that are dependent on the given achievement
 */
function badgeos_get_points_based_achievements() {
	global $wpdb;

	$achievements = get_transient( 'badgeos_points_based_achievements' );

	if ( empty( $achievements ) ) {
		// Grab posts that can be earned by unlocking the given achievement
		$achievements = $wpdb->get_results(
			"
			SELECT *
			FROM   $wpdb->posts as posts,
			       $wpdb->postmeta as meta
			WHERE  posts.ID = meta.post_id
			       AND meta.meta_key = '_badgeos_earned_by'
			       AND meta.meta_value = 'points'
			"
		);

		// Store these posts to a transient for 7 days
		set_transient( 'badgeos_points_based_achievements', $achievements, 60*60*24*7 );
	}

	return (array) maybe_unserialize( $achievements );
}

/**
 * Destroy the points-based achievements transient if we edit a points-based achievement
 *
 * @since 1.0.0
 * @param integer $post_id The given post's ID
 */
function badgeos_bust_points_based_achievements_cache( $post_id ) {

	$post = get_post($post_id);

	// If the post is one of our achievement types,
	// and the achievement is awarded by minimum points
	if (
		badgeos_is_achievement( $post )
		&& (
			'points' == get_post_meta( $post_id, '_badgeos_earned_by', true )
			|| ( isset( $_POST['_badgeos_earned_by'] ) && 'points' == $_POST['_badgeos_earned_by'] )
		)
	) {
		delete_transient( 'badgeos_points_based_achievements' );
	}

}
add_action( 'save_post', 'badgeos_bust_points_based_achievements_cache' );
add_action( 'trash_post', 'badgeos_bust_points_based_achievements_cache' );

/**
 * Helper function to retrieve an achievement post thumbnail
 *
 * Falls back first to parent achievement type's thumbnail,
 * and finally to a default BadgeOS icon from Credly.
 *
 * @since  1.0.0
 * @param  integer $post_id    The achievement's post ID
 * @param  string  $image_size The name of a registered custom image size
 * @param  string  $class      A custom class to use for the image tag
 * @return string              Our formatted image tag
 */
function badgeos_get_achievement_post_thumbnail( $post_id = 0, $image_size = 'badgeos-achievement', $class = 'badgeos-item-thumbnail' ) {

	// Get our badge thumbnail
	$image = get_the_post_thumbnail( $post_id, $image_size, array( 'class' => $class ) );

	// If we don't have an image...
	if ( ! $image ) {

		// Grab our achievement type's post thumbnail
		$achievement = get_page_by_path( get_post_type(), OBJECT, 'achievement-type' );
		$image = is_object( $achievement ) ? get_the_post_thumbnail( $achievement->ID, $image_size, array( 'class' => $class ) ) : false;

		// If we still have no image, use one from Credly
		if ( ! $image ) {

			// If we already have an array for image size
			if ( is_array( $image_size ) ) {
				// Write our sizes to an associative array
				$image_sizes['width'] = $image_size[0];
				$image_sizes['height'] = $image_size[1];

			// Otherwise, attempt to grab the width/height from our specified image size
			} else {
				global $_wp_additional_image_sizes;
				if ( isset( $_wp_additional_image_sizes[$image_size] ) )
					$image_sizes = $_wp_additional_image_sizes[$image_size];
			}

			// If we can't get the defined width/height, set our own
			if ( empty( $image_sizes ) ) {
				$image_sizes = array(
					'width'  => 100,
					'height' => 100
				);
			}

			// Available filter: 'badgeos_default_achievement_post_thumbnail'
			$image = '<img src="' . apply_filters( 'badgeos_default_achievement_post_thumbnail', 'https://credlyapp.s3.amazonaws.com/badges/af2e834c1e23ab30f1d672579d61c25a_15.png' ) . '" width="' . $image_sizes['width'] . '" height="' . $image_sizes['height'] . '" class="' . $class .'">';

		}
	}

	// Finally, return our image tag
	return $image;
}

/**
 * Attempt to send an achievement to Credly if the user has send enabled
 *
 * @since 1.0.0
 *
 * @param int $user_id        The ID of the user earning the achievement
 * @param int $achievement_id The ID of the achievement being earned
 */
function credly_issue_badge( $user_id, $achievement_id ) {

	if ( 'true' === $GLOBALS['badgeos_credly']->user_enabled ) {

		$GLOBALS['badgeos_credly']->post_credly_user_badge( $user_id, $achievement_id );

	}

}
add_action( 'badgeos_award_achievement', 'credly_issue_badge', 10, 2 );

/**
 * Get an array of all users who have earned a given achievement
 *
 * @since  1.1.0
 * @param  integer $achievement_id The given achievement's post ID
 * @return array                   Array of user objects
 */
function badgeos_get_achievement_earners( $achievement_id = 0 ) {

	// Grab our earners
	$earners = new WP_User_Query( array(
		'meta_key'     => '_badgeos_achievements',
		'meta_value'   => $achievement_id,
		'meta_compare' => 'LIKE'
	) );

	$earned_users = array();
	foreach( $earners->results as $earner ) {
		if ( badgeos_has_user_earned_achievement( $achievement_id, $earner->ID ) ) {
			$earned_users[] = $earner;
		}
	}
	// Send back our query results
	return $earned_users;
}

/**
 * Build an unordered list of users who have earned a given achievement
 *
 * @since  1.1.0
 * @param  integer $achievement_id The given achievement's post ID
 * @return string                  Concatenated markup
 */
function badgeos_get_achievement_earners_list( $achievement_id = 0 ) {

	// Grab our users
	$earners = badgeos_get_achievement_earners( $achievement_id );
	$output = '';

	// Only generate output if we have earners
	if ( ! empty( $earners ) )  {
		// Loop through each user and build our output
		$output .= '<h4>' . apply_filters( 'badgeos_earners_heading', __( 'People who have earned this:', 'badgeos' ) ) . '</h4>';
		$output .= '<ul class="badgeos-achievement-earners-list achievement-' . $achievement_id . '-earners-list">';
		foreach ( $earners as $user ) {
			$user_content = '<li><a href="' . get_author_posts_url( $user->ID ) . '">' . get_avatar( $user->ID ) . '</a></li>';
			$output .= apply_filters( 'badgeos_get_achievement_earners_list_user', $user_content, $user->ID );
		}
		$output .= '</ul>';
	}

	// Return our concatenated output
	return apply_filters( 'badgeos_get_achievement_earners_list', $output, $achievement_id, $earners );
}

/**
 * Check if admin settings are set to show all achievements across a multisite network
 *
 * @since  1.2.0
 * @return boolean
 */
function badgeos_ms_show_all_achievements(){
	$ms_show_all_achievements = NULL;
	if ( is_multisite() ) {
    	$badgeos_settings = get_option( 'badgeos_settings' );
    	$ms_show_all_achievements = ( isset( $badgeos_settings['ms_show_all_achievements'] ) ) ? $badgeos_settings['ms_show_all_achievements'] : 'disabled';
    	if( 'enabled' == $ms_show_all_achievements )
    		return true;
    }
    return false;
}

/**
 * Create array of blog ids in the network if multisite setting is on
 *
 * @since  1.2.0
 * @return array Array of blog_ids
 */
function badgeos_get_network_site_ids() {
	global $wpdb;
    if( badgeos_ms_show_all_achievements() ) {
        $blog_ids = $wpdb->get_results( "SELECT blog_id FROM " . $wpdb->base_prefix . "blogs" );
		foreach ($blog_ids as $key => $value ) {
            $sites[] = $value->blog_id;
        }
    } else {
    	$sites[] = get_current_blog_id();
    }
    return $sites;
}

/**
 * Set default achievement image on achievement post save
 *
 * @since 1.2.0
 * @param integer $post_id The post ID of the post being saved
 * @return mixed    post ID if nothing to do, void otherwise.
 */
function badgeos_achievement_set_default_thumbnail( $post_id ) {
	global $pagenow;

	// Bail early if:
	// this IS NOT an achievement or achievement-type post
	// OR this IS an autosave
	// OR current user CAN NOT edit this post
	// OR the post already has a thumbnail
	// OR we've just loaded the new post page
	if (
		! (
			badgeos_is_achievement( $post_id )
			|| 'achievement-type' == get_post_type( $post_id )
		)
		|| ( defined('DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		|| ! current_user_can( 'edit_post', $post_id )
		|| has_post_thumbnail( $post_id )
		|| 'post-new.php' == $pagenow
	) {
		return $post_id;
	}

	$thumbnail_id = 0;
	$achievement_type = '';

	// Get the thumbnail of our parent achievement
	if ( 'achievement-type' !== get_post_type( $post_id ) ) {
		$achievement_type = get_page_by_path( get_post_type( $post_id ), OBJECT, 'achievement-type' );

		if ( $achievement_type ) {
			$thumbnail_id = get_post_thumbnail_id( $achievement_type->ID );
		}
	}

	// If there is no thumbnail set, load in our default image
	if ( empty( $thumbnail_id ) ) {
		global $wpdb;

		// Grab the default image
		$file = apply_filters( 'badgeos_default_achievement_post_thumbnail', 'https://credlyapp.s3.amazonaws.com/badges/af2e834c1e23ab30f1d672579d61c25a_15.png' );

		// Check for an existing copy of our default image
		$file_name = 'af2e834c1e23ab30f1d672579d61c25a_15';
		$attachment = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE post_type = '%s' AND guid LIKE '%%af2e834c1e23ab30f1d672579d61c25a_15%%' ", 'attachment'
			)
		);

		if ( !empty( $attachment[0] ) ) {
			$thumbnail_id = $attachment[0];
		} else {
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
		}
		// If upload errored, unlink the image file
		if ( empty( $thumbnail_id ) || is_wp_error( $thumbnail_id ) ) {
			@unlink( $file_array['tmp_name'] );

		// Otherwise, if the achievement type truly doesn't have
		// a thumbnail already, set this as its thumbnail, too.
		// We do this so that WP won't upload a duplicate version
		// of this image for every single achievement of this type.
		} elseif (
			badgeos_is_achievement( $post_id )
			&& is_object( $achievement_type )
			&& ! get_post_thumbnail_id( $achievement_type->ID )
		) {
			set_post_thumbnail( $achievement_type->ID, $thumbnail_id );
		}
	}

	// Finally, if we have an image, set the thumbnail for our achievement
	if ( $thumbnail_id && ! is_wp_error( $thumbnail_id ) ) {
		set_post_thumbnail( $post_id, $thumbnail_id );
	}

}
add_action( 'save_post', 'badgeos_achievement_set_default_thumbnail' );

/**
 * Flush rewrite rules whenever an achievement type is published.
 *
 * @since 1.4.0
 *
 * @param string $new_status New status.
 * @param string $old_status Old status.
 * @param object $post       Post object.
 */
function badgeos_flush_rewrite_on_published_achievement( $new_status, $old_status, $post ) {
	if ( 'achievement-type' === $post->post_type && 'publish' === $new_status && 'publish' !== $old_status ) {
		badgeos_flush_rewrite_rules();
	}
}
add_action( 'transition_post_status', 'badgeos_flush_rewrite_on_published_achievement', 10, 3 );

/**
 * Register achievement types and flush rewrite rules.
 *
 * @since 1.4.0
 */
function badgeos_flush_rewrite_rules() {
	badgeos_register_post_types();
	badgeos_register_achievement_type_cpt();
	flush_rewrite_rules();
}

/**
 * Update all dependent data if achievement type name has changed.
 *
 * @since  1.4.0
 *
 * @param  array $data      Post data.
 * @param  array $post_args Post args.
 * @return array            Updated post data.
 */
function badgeos_maybe_update_achievement_type( $data = array(), $post_args = array() ) {
	if ( badgeos_achievement_type_changed( $post_args ) ) {
		$original_type = get_post( $post_args['ID'] )->post_name;
		$new_type = wp_unique_post_slug( sanitize_title( $post_args['post_title'] ), $post_args['ID'], $post_args['post_status'], $post_args['post_type'], $post_args['post_parent'] );
		$data['post_name'] = badgeos_update_achievement_types( $original_type, $new_type );
		add_filter( 'redirect_post_location', 'badgeos_achievement_type_rename_redirect', 99 );
	}
	return $data;
}
add_filter( 'wp_insert_post_data' , 'badgeos_maybe_update_achievement_type' , '99', 2 );

/**
 * Check if an achievement type name has changed.
 *
 * @since  1.4.0
 *
 * @param  array $post_args Post args.
 * @return bool             True if name has changed, otherwise false.
 */
function badgeos_achievement_type_changed( $post_args = array() ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return false;
	}

	$original_post = ( !empty( $post_args['ID'] ) && isset( $post_args['ID'] ) ) ? get_post( $post_args['ID'] ) : null;
	$status = false;
	if ( is_object( $original_post ) ) {
		if (
			'achievement-type' === $post_args['post_type']
			&& $original_post->post_status !== 'auto-draft'
			&& ! empty( $original_post->post_name )
			&& $original_post->post_title !== $post_args['post_title']
		) {
			$status = true;
		}
	}

	return $status;
}

/**
 * Replace all instances of one achievement type with another.
 *
 * @since  1.4.0
 *
 * @param  string $original_type Original achievement type.
 * @param  string $new_type      New achievement type.
 * @return string                New achievement type.
 */
function badgeos_update_achievement_types( $original_type = '', $new_type = '' ) {

	// Sanity check to prevent alterating core posts
	if ( empty( $original_type ) || in_array( $original_type, array( 'post', 'page', 'attachment', 'revision', 'nav_menu_item' ) ) ) {
		return $new_type;
	}

	badgeos_update_achievements_achievement_types( $original_type, $new_type );
	badgeos_update_p2p_achievement_types( $original_type, $new_type );
	badgeos_update_earned_meta_achievement_types( $original_type, $new_type );
	badgeos_update_active_meta_achievement_types( $original_type, $new_type );
	badgeos_flush_rewrite_rules();
	return $new_type;
}

/**
 * Change all achievements of one type to a new type.
 *
 * @since 1.4.0
 *
 * @param string $original_type Original achievement type.
 * @param string $new_type      New achievement type.
 */
function badgeos_update_achievements_achievement_types( $original_type = '', $new_type = '' ) {
	$items = get_posts( array(
		'posts_per_page' => -1,
		'post_status'    => 'any',
		'post_type'      => $original_type,
		'fields'         => 'id',
	) );
	foreach ( $items as $item ) {
		set_post_type( $item->ID, $new_type );
	}
}

/**
 * Change all p2p connections of one achievement type to a new type.
 *
 * @since 1.4.0
 *
 * @param string $original_type Original achievement type.
 * @param string $new_type      New achievement type.
 */
function badgeos_update_p2p_achievement_types( $original_type = '', $new_type = '' ) {
	global $wpdb;
	$p2p_relationships = array(
		"step-to-{$original_type}" => "step-to-{$new_type}",
		"{$original_type}-to-step" => "{$new_type}-to-step",
	);
	foreach ( $p2p_relationships as $old => $new ) {
		$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->p2p SET p2p_type = %s WHERE p2p_type = %s", $new, $old ) );
	}
}

/**
 * Change all earned meta from one achievement type to another.
 *
 * @since 1.4.0
 *
 * @param string $original_type Original achievement type.
 * @param string $new_type      New achievement type.
 */
function badgeos_update_earned_meta_achievement_types( $original_type = '', $new_type = '' ) {
	$metas = badgeos_get_unserialized_achievement_metas( '_badgeos_achievements', $original_type );
	if ( ! empty( $metas ) ) {
		foreach ( $metas as $meta ) {
			foreach ( $meta->meta_value as $site_id => $achievements ) {
				$meta->meta_value[ $site_id ] = badgeos_update_meta_achievement_types( $achievements, $original_type, $new_type );
			}
			update_user_meta( $meta->user_id, $meta->meta_key, $meta->meta_value );
		}
	}
}

/**
 * Change all active meta from one achievement type to another.
 *
 * @since 1.4.0
 *
 * @param string $original_type Original achievement type.
 * @param string $new_type      New achievement type.
 */
function badgeos_update_active_meta_achievement_types( $original_type = '', $new_type = '' ) {
	$metas = badgeos_get_unserialized_achievement_metas( '_badgeos_active_achievements', $original_type );
	if ( ! empty( $metas ) ) {
		foreach ( $metas as $meta ) {
			$meta->meta_value = badgeos_update_meta_achievement_types( $meta->meta_value, $original_type, $new_type );
			update_user_meta( $meta->user_id, $meta->meta_key, $meta->meta_value );
		}
	}
}

/**
 * Get unserialized user achievement metas.
 *
 * @since  1.4.0
 *
 * @param  string $meta_key      Meta key.
 * @param  string $original_type Achievement type.
 * @return array                 User achievement metas.
 */
function badgeos_get_unserialized_achievement_metas( $meta_key = '', $original_type = '' ) {
	$metas = badgeos_get_achievement_metas( $meta_key, $original_type );
	if ( ! empty( $metas ) ) {
		foreach ( $metas as $key => $meta ) {
			$metas[ $key ]->meta_value = maybe_unserialize( $meta->meta_value );
		}
	}
	return $metas;
}

/**
 * Get serialized user achievement metas.
 *
 * @since  1.4.0
 *
 * @param  string $meta_key      Meta key.
 * @param  string $original_type Achievement type.
 * @return array                 User achievement metas.
 */
function badgeos_get_achievement_metas( $meta_key = '', $original_type = '' ) {
	global $wpdb;
	return $wpdb->get_results( $wpdb->prepare(
		"
		SELECT *
		FROM   $wpdb->usermeta
		WHERE  meta_key = %s
		       AND meta_value LIKE '%%%s%%'
		",
		$meta_key,
		$original_type
	) );
}

/**
 * Change user achievement meta from one achievement type to another.
 *
 * @since 1.4.0
 *
 * @param array  $achievements  Array of achievements.
 * @param string $original_type Original achievement type.
 * @param string $new_type      New achievement type.
 *
 * @return array $achievements
 */
function badgeos_update_meta_achievement_types( $achievements = array(), $original_type = '', $new_type = '' ) {
	if ( is_array( $achievements ) && ! empty( $achievements ) ) {
		foreach ( $achievements as $key => $achievement ) {
			if ( $achievement->post_type === $original_type ) {
				$achievements[ $key ]->post_type = $new_type;
			}
		}
	}
	return $achievements;
}

/**
 * Redirect to inclue custom rename message.
 *
 * @since  1.4.0
 *
 * @param  string $location Original URI.
 * @return string           Updated URI.
 */
function badgeos_achievement_type_rename_redirect( $location = '' ) {
	remove_filter( 'redirect_post_location', __FUNCTION__, 99 );
	return add_query_arg( 'message', 99, $location );
}

/**
 * Filter the "post updated" messages to include support for achievement types.
 *
 * @since 1.4.0
 *
 * @param array $messages Array of messages to display.
 *
 * @return array $messages Compiled list of messages.
 */
function badgeos_achievement_type_update_messages( $messages ) {
	$messages['achievement-type'] = array_fill( 1, 10, __( 'Achievement Type saved successfully.', 'badgeos' ) );
	$messages['achievement-type']['99'] = sprintf( __('Achievement Type renamed successfully. <p>All achievements of this type, and all active and earned user achievements, have been updated <strong>automatically</strong>.</p> All shortcodes, %s, and URIs that reference the old achievement type slug must be updated <strong>manually</strong>.', 'badgeos'), '<a href="' . esc_url( admin_url( 'widgets.php' ) ) . '">' . __( 'widgets', 'badgeos' ) . '</a>' );
	return $messages;
}
add_filter( 'post_updated_messages', 'badgeos_achievement_type_update_messages' );
