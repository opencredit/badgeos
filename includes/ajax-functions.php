<?php
/**
 * AJAX Support Functions
 *
 * @package BadgeOS
 * @subpackage AJAX
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

// Setup our badgeos AJAX actions
$badgeos_ajax_actions = array(
	'get-achievements',
	'get-feedback',
	'get-achievements-select2',
	'get-achievement-types',
	'get-users',
	'update-feedback',
);

// Register core Ajax calls.
foreach ( $badgeos_ajax_actions as $action ) {
	add_action( 'wp_ajax_' . $action, 'badgeos_ajax_' . str_replace( '-', '_', $action ), 1 );
	add_action( 'wp_ajax_nopriv_' . $action, 'badgeos_ajax_' . str_replace( '-', '_', $action ), 1 );
}


/**
 * AJAX Helper for returning achievements
 *
 * @since 1.0.0
 * @return void
 */
function badgeos_ajax_get_achievements() {
	global $user_ID, $blog_id;

	// Setup our AJAX query vars
	$type       = isset( $_REQUEST['type'] )       ? $_REQUEST['type']       : false;
	$limit      = isset( $_REQUEST['limit'] )      ? $_REQUEST['limit']      : false;
	$offset     = isset( $_REQUEST['offset'] )     ? $_REQUEST['offset']     : false;
	$count      = isset( $_REQUEST['count'] )      ? $_REQUEST['count']      : false;
	$filter     = isset( $_REQUEST['filter'] )     ? $_REQUEST['filter']     : false;
	$search     = isset( $_REQUEST['search'] )     ? $_REQUEST['search']     : false;
	$user_id    = isset( $_REQUEST['user_id'] )    ? $_REQUEST['user_id']    : false;
	$orderby    = isset( $_REQUEST['orderby'] )    ? $_REQUEST['orderby']    : false;
	$order      = isset( $_REQUEST['order'] )      ? $_REQUEST['order']      : false;
	$wpms       = isset( $_REQUEST['wpms'] )       ? $_REQUEST['wpms']       : false;
	$include    = isset( $_REQUEST['include'] )    ? $_REQUEST['include']    : array();
	$exclude    = isset( $_REQUEST['exclude'] )    ? $_REQUEST['exclude']    : array();
	$meta_key   = isset( $_REQUEST['meta_key'] )   ? $_REQUEST['meta_key']   : '';
	$meta_value = isset( $_REQUEST['meta_value'] ) ? $_REQUEST['meta_value'] : '';

	// Convert $type to properly support multiple achievement types
	if ( 'all' == $type ) {
		$type = badgeos_get_achievement_types_slugs();
		// Drop steps from our list of "all" achievements
		$step_key = array_search( 'step', $type );
		if ( $step_key )
			unset( $type[$step_key] );
	} else {
		$type = explode( ',', $type );
	}

	// Get the current user if one wasn't specified
	if( ! $user_id )
		$user_id = $user_ID;

	// Build $include array
	if ( !is_array( $include ) ) {
		$include = explode( ',', $include );
	}

	// Build $exclude array
	if ( !is_array( $exclude ) ) {
		$exclude = explode( ',', $exclude );
	}

    // Initialize our output and counters
    $achievements = '';
    $achievement_count = 0;
    $query_count = 0;

    // Grab our hidden badges (used to filter the query)
	$hidden = badgeos_get_hidden_achievement_ids( $type );

	// If we're polling all sites, grab an array of site IDs
	if( $wpms && $wpms != 'false' )
		$sites = badgeos_get_network_site_ids();
	// Otherwise, use only the current site
	else
		$sites = array( $blog_id );

	// Loop through each site (default is current site only)
	foreach( $sites as $site_blog_id ) {

		// If we're not polling the current site, switch to the site we're polling
		if ( $blog_id != $site_blog_id ) {
			switch_to_blog( $site_blog_id );
		}

		// Grab our earned badges (used to filter the query)
		$earned_ids = badgeos_get_user_earned_achievement_ids( $user_id, $type );

		// Query Achievements
		$args = array(
			'post_type'      =>	$type,
			'orderby'        =>	$orderby,
			'order'          =>	$order,
			'posts_per_page' =>	$limit,
			'offset'         => $offset,
			'post_status'    => 'publish',
			'post__not_in'   => array_diff( $hidden, $earned_ids )
		);

		// Filter - query completed or non completed achievements
		if ( $filter == 'completed' ) {
			$args[ 'post__in' ] = array_merge( array( 0 ), $earned_ids );
		}elseif( $filter == 'not-completed' ) {
			$args[ 'post__not_in' ] = array_merge( $hidden, $earned_ids );
		}

		if ( '' !== $meta_key && '' !== $meta_value ) {
			$args[ 'meta_key' ] = $meta_key;
			$args[ 'meta_value' ] = $meta_value;
		}

		// Include certain achievements
		if ( !empty( $include ) ) {
			$args[ 'post__not_in' ] = array_diff( $args[ 'post__not_in' ], $include );
			$args[ 'post__in' ] = array_merge( array( 0 ), array_diff( $include, $args[ 'post__in' ] ) );
		}

		// Exclude certain achievements
		if ( !empty( $exclude ) ) {
			$args[ 'post__not_in' ] = array_merge( $args[ 'post__not_in' ], $exclude );
		}

		// Search
		if ( $search ) {
			$args[ 's' ] = $search;
		}

		// Loop Achievements
		$achievement_posts = new WP_Query( $args );
		$query_count += $achievement_posts->found_posts;
		while ( $achievement_posts->have_posts() ) : $achievement_posts->the_post();
			$achievements .= badgeos_render_achievement( get_the_ID() );
			$achievement_count++;
		endwhile;

		// Sanity helper: if we're filtering for complete and we have no
		// earned achievements, $achievement_posts should definitely be false
		/*if ( 'completed' == $filter && empty( $earned_ids ) )
			$achievements = '';*/

		// Display a message for no results
		if ( empty( $achievements ) ) {
			$current = current( $type );
			// If we have exactly one achivement type, get its plural name, otherwise use "achievements"
			$post_type_plural = ( 1 == count( $type ) && ! empty( $current ) ) ? get_post_type_object( $curret )->labels->name : __( 'achievements' , 'badgeos' );

			// Setup our completion message
			$achievements .= '<div class="badgeos-no-results">';
			if ( 'completed' == $filter ) {
				$achievements .= '<p>' . sprintf( __( 'No completed %s to display at this time.', 'badgeos' ), strtolower( $post_type_plural ) ) . '</p>';
			}else{
				$achievements .= '<p>' . sprintf( __( 'No %s to display at this time.', 'badgeos' ), strtolower( $post_type_plural ) ) . '</p>';
			}
			$achievements .= '</div><!-- .badgeos-no-results -->';
		}

		if ( $blog_id != $site_blog_id ) {
			// Come back to current blog
			restore_current_blog();
		}

	}

	// Send back our successful response
	wp_send_json_success( array(
		'message'     => $achievements,
		'offset'      => $offset + $limit,
		'query_count' => $query_count,
		'badge_count' => $achievement_count,
		'type'        => $type,
	) );
}

/**
 * AJAX Helper for returning feedback posts
 *
 * @since 1.1.0
 * @return void
 */
function badgeos_ajax_get_feedback() {

	$feedback = badgeos_get_feedback( array(
		'post_type'        => isset( $_REQUEST['type'] ) ? esc_html( $_REQUEST['type'] ) : '',
		'posts_per_page'   => isset( $_REQUEST['limit'] ) ? esc_html( $_REQUEST['limit'] ) : '',
		'status'           => isset( $_REQUEST['status'] ) ? esc_html( $_REQUEST['status'] ) : '',
		'show_attachments' => isset( $_REQUEST['show_attachments'] ) ? esc_html( $_REQUEST['show_attachments'] ) : '',
		'show_comments'    => isset( $_REQUEST['show_comments'] ) ? esc_html( $_REQUEST['show_comments'] ) : '',
		's'                => isset( $_REQUEST['search'] ) ? esc_html( $_REQUEST['search'] ) : '',
	) );

	wp_send_json_success( array(
		'feedback' => $feedback
	) );
}

/**
 * AJAX Helper for approving/denying feedback
 *
 * @since 1.1.0
 * @return void
 */
function badgeos_ajax_update_feedback() {

	// Verify our nonce
	check_ajax_referer( 'review_feedback', 'nonce' );

	// Status workflow
	$status_args = array(
		'achievement_id' => $_REQUEST[ 'achievement_id' ],
		'user_id' => $_REQUEST[ 'user_id' ],
		'submission_type' => $_REQUEST[ 'feedback_type' ]
	);

	// Setup status
	$status = ( in_array( $_REQUEST[ 'status' ], array( 'approve', 'approved' ) ) ) ? 'approved' : 'denied';

	badgeos_set_submission_status( $_REQUEST[ 'feedback_id' ], $status, $status_args );

	// Send back our successful response
	wp_send_json_success( array(
		'message' => '<p class="badgeos-feedback-response success">' . __( 'Status Updated!', 'badgeos' ) . '</p>',
		'status' => ucfirst( $status )
	) );

}

/**
 * AJAX Helper for selecting users in Shortcode Embedder
 *
 * @since 1.4.0
 */
function badgeos_ajax_get_users() {

	// If no query was sent, die here
	if ( ! isset( $_REQUEST['q'] ) ) {
		die();
	}

	global $wpdb;

	// Pull back the search string
	$search = esc_sql( like_escape( $_REQUEST['q'] ) );

	$sql = "SELECT ID, user_login FROM {$wpdb->users}";

	// Build our query
	if ( !empty( $search ) ) {
		$sql .= " WHERE user_login LIKE '%{$search}%'";
	}

	// Fetch our results (store as associative array)
	$results = $wpdb->get_results( $sql, 'ARRAY_A' );

	// Return our results
	wp_send_json_success( $results );
}

/**
 * AJAX Helper for selecting posts in Shortcode Embedder
 *
 * @since 1.4.0
 */
function badgeos_ajax_get_achievements_select2() {
	global $wpdb;

	// Pull back the search string
	$search = isset( $_REQUEST['q'] ) ? like_escape( $_REQUEST['q'] ) : '';
	$achievement_types = isset( $_REQUEST['post_type'] ) && 'all' !== $_REQUEST['post_type']
		? array( esc_sql( $_REQUEST['post_type'] ) )
		: array_diff( badgeos_get_achievement_types_slugs(), array( 'step' ) );
	$post_type = sprintf( 'AND post_type IN(\'%s\')', implode( "','", $achievement_types ) );

	$results = $wpdb->get_results( $wpdb->prepare(
		"
		SELECT ID, post_title
		FROM   $wpdb->posts
		WHERE  post_title LIKE %s
		       {$post_type}
		       AND post_status = 'publish'
		",
		"%%{$search}%%"
	) );

	// Return our results
	wp_send_json_success( $results );
}

/**
 * AJAX Helper for selecting achievement types in Shortcode Embedder
 *
 * @since 1.4.0
 */
function badgeos_ajax_get_achievement_types() {

	// If no query was sent, die here
	if ( ! isset( $_REQUEST['q'] ) ) {
		die();
	}

	$achievement_types = array_diff( badgeos_get_achievement_types_slugs(), array( 'step' ) );
	$matches = preg_grep( "/{$_REQUEST['q']}/", $achievement_types );
	$found = array_map( 'get_post_type_object', $matches );

	// Include an "all" option as the first option
	array_unshift( $found, (object) array( 'name' => 'all', 'label' => __( 'All', 'badgeos') ) );

	// Return our results
	wp_send_json_success( $found );
}
