<?php
/**
 * Achievement Functions
 *
 * @package BadgeOS
 * @subpackage Achievements
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

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
 */
function badgeos_get_achievements_children_join( $join, $query_object ) {
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
 */
function badgeos_get_achievements_children_where( $where, $query_object ) {
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
 */
function badgeos_get_achievements_children_orderby( $orderby ) {
	return $orderby = 'p2pm2.meta_value ASC';
}

/**
 * Modify the WP_Query Join filter for achievement parents
 *
 * @since  1.0.0
 */
function badgeos_get_achievements_parents_join( $join ) {
	global $wpdb;
	$join .= " LEFT JOIN $wpdb->p2p AS p2p ON p2p.p2p_to = $wpdb->posts.ID";
	return $join;
}

/**
 * Modify the WP_Query Where filter for achievement parents
 *
 * @since  1.0.0
 */
function badgeos_get_achievements_parents_where( $where, $query_object ) {
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
 * @since  1.0
 * @param  integer $user_id        The given user's ID
 * @param  integer $achievement_id The given achievement's post ID
 * @return bool                    True if we've exceed the max possible earnings, false if we're still eligable
 */
function badgeos_achievement_user_exceeded_max_earnings( $user_id = 0, $achievement_id = 0 ) {

	// Grab our max allowed times to earn, and if set, see how many times we've earned the badge
	if ( $max_times_allowed_to_earn = get_post_meta( $achievement_id, '_badgeos_maximum_earnings', true ) ) {
		$user_has_badge = badgeos_get_user_achievements( array( 'achievement_id' => absint( $badge_id ) ) );
		if ( ! empty( $user_has_badge ) ) {
			// If we've earned it as many (or more) times than allowed, return true
			if ( count( $user_has_badge ) >= $max_times_allowed_to_earn ) {
				return true;
			}
		}
	}

	// If we make it here, the post has no max earning limit, or we're under it
	return false;
}

/**
 * Get the UNIX timestamp for the last activity on an achievement for a given user
 *
 * @since  1.0
 * @param  integer $badge_id The given achievement's post ID
 * @param  integer $user_id  The given user's ID
 * @return integer           The UNIX timestamp for the last reported badge activity
 */
function badgeos_achievement_last_user_activity( $achievement_id = 0, $user_id = 0 ) {

	// Assume the user has no history with this badge
	$date = 0;

	// See if the user has ever earned or failed the badge
	$user_badge_history = get_posts( array(
		'author'         => $user_id,
		'post_type'      => 'badgeos-log-entry',
		'meta_key'       => '_badgeos_log_achievement_id',
		'meta_value'     => $achievement_id,
		'posts_per_page' => 1
	) );

	// If the user DOES have some history with this badge, grab the last interaction time
	if ( ! empty( $user_badge_history ) )
		$date = strtotime( $user_badge_history[0]->post_date_gmt );

	// Finally, return our time
	return $date;

}

/**
 * Helper function for building an object for our achievement
 *
 * @since  1.0.0
 * @param  integer $achievement_id The given achievement's post ID
 * @return object                  Our object containing only the relevant bits of information we want
 */
function badgeos_build_achievement_object( $achievement_id = 0 ) {

	// Grab the new achievement's $post data, and bail if it doesn't exist
	$achievement = get_post( $achievement_id );
	if ( is_null( $achievement ) )
		return false;

	// Setup a new object for the achievement
	$achievement_object              = new stdClass;
	$achievement_object->ID          = $achievement_id;
	$achievement_object->post_type   = $achievement->post_type;
	$achievement_object->date_earned = time();

	// Return our achievement object, available filter so we can extend it elsewhere
	return apply_filters( 'achievement_object', $achievement_object );

}

/**
 * Get an array of post IDs for achievements that are marked as "hidden"
 *
 * @since  1.0.0
 * @param  string  $achievement_type Limit the array to a specific type of achievement
 * @return array                     An array of hidden achivement post IDs
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
		'achievement_type' => $achievement_type
	) );

	foreach ( $earned_achievements as $achievement )
		$earned_ids[] = $achievement->ID;

	return $earned_ids;

}

/**
 * Returns achievements that may be earned when the given achievement is earned.
 *
 * @since  1.0.0
 * @param  integer $achievement_id The given achievement's post ID
 * @return array                   An array of achievements that are dependent on the given achievement
 */
function badgeos_get_dependent_achievements( $achievement_id ) {
	global $wpdb;

	// Grab the current achievement ID if none specified
	if ( ! $achievement_id ) {
		global $post;
		$achievement_id = $post->ID;
	}

	// Grab posts that can be earned by unlocking the given achievement
	$achievements = $wpdb->get_results( $wpdb->prepare(
		"
		SELECT *
		FROM   $wpdb->posts as posts,
		       $wpdb->p2p as p2p
		WHERE  posts.ID = p2p.p2p_to
		       AND p2p.p2p_from = %d
		",
		$achievement_id
	) );

	return $achievements;
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
		ORDER BY p2pmeta.meta_value
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
 * @param  integer $achievement_id The given achievement's post ID
 * @return array                   An array of achievements that are dependent on the given achievement
 */
function badgeos_get_points_based_achievements() {
	global $wpdb;

	$achievements = get_transient( 'badgeos_points_based_achievements' );

	if ( empty( $achievements ) ) {
		// Grab posts that can be earned by unlocking the given achievement
		$achievements = $wpdb->get_results( $wpdb->prepare(
			"
			SELECT *
			FROM   $wpdb->posts as posts,
			       $wpdb->postmeta as meta
			WHERE  posts.ID = meta.post_id
			       AND meta.meta_key = '_badgeos_earned_by'
			       AND meta.meta_value = 'points'
			"
		) );

		// Store these posts to a transient
		set_transient( 'badgeos_points_based_achievements', $achievements, 0 );
	}

	return $achievements;
}

/**
 * Destroy the points-based achievements transient if we edit a points-based achievement
 *
 * @since  1.0.0
 * @param  integer $post_id The given post's ID
 */
function badgeos_bust_points_based_achievements_cache( $post_id ) {

	$badgestack_settings = get_option( 'badgestack_settings' );
	$minimum_role        = ( !empty( $badgestack_settings['minimum_role'] ) ) ? $badgestack_settings['minimum_role'] : 'administrator';
	$post                = get_post($post_id);

	// If the user has the authority to do what they're doing,
	// and the post is one of our achievement types,
	// and the achievement is awarded by minimum points
	if (
		current_user_can( $minimum_role )
		&& in_array( $post->post_type, badgeos_get_achievement_types_slugs() )
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
	if ( !$image ) {

		// Grab our achievement type's post thumbnail
		$achievement = get_page_by_path( get_post_type(), OBJECT, 'achievement-type' );
		$image = get_the_post_thumbnail( $achievement->ID, $image_size, array( 'class' => $class ) );

		// If we still have no image, use one from Credly
		if ( !$image ) {

			// Attempt to grab the width/height from our specified image size
			global $_wp_additional_image_sizes;
			if ( isset( $_wp_additional_image_sizes[$image_size] ) )
				$image_sizes = $_wp_additional_image_sizes[$image_size];

			// If we can't get the defined width/height, set our own
			if ( empty( $image_sizes ) )
				$image_sizes = array(
					'width'  => 100,
					'height' => 100
				);

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
 * @since  1.0.0
 *
 * @param  int  $user_id        The ID of the user earning the achievement
 * @param  int  $achievement_id The ID of the achievement being earned
 */
function credly_issue_badge( $user_id, $achievement_id ) {

	if ( 'true' === $GLOBALS['badgeos_credly']->user_enabled ) {

		$GLOBALS['badgeos_credly']->post_credly_user_badge( $user_id, $achievement_id );

	}

}
add_action( 'badgeos_award_achievement', 'credly_issue_badge', 10, 2 );
