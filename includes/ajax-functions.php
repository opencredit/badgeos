<?php
/**
 * AJAX Support Functions
 *
 * @package BadgeOS
 * @subpackage AJAX
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

// Setup our MVP AJAX actions
$badgeos_ajax_actions = array(
	'get-achievements',
	'get-feedback',
	'update-feedback'
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
 */
function badgeos_ajax_get_achievements(){
	global $user_ID;

	// Setup our AJAX query vars
	$type    = isset( $_REQUEST['type'] )    ? $_REQUEST['type']    : false;
	$limit   = isset( $_REQUEST['limit'] )   ? $_REQUEST['limit']   : false;
	$offset  = isset( $_REQUEST['offset'] )  ? $_REQUEST['offset']  : false;
	$count   = isset( $_REQUEST['count'] )   ? $_REQUEST['count']   : false;
	$filter  = isset( $_REQUEST['filter'] )  ? $_REQUEST['filter']  : false;
	$search  = isset( $_REQUEST['search'] )  ? $_REQUEST['search']  : false;
	$user_id = isset( $_REQUEST['user_id'] ) ? $_REQUEST['user_id'] : false;
	if( !$user_id )
		$user_id = $user_ID;

	$achievements = '';

	// Grab our hidden and earned badges (used to filter the query)
	$hidden = badgeos_get_hidden_achievement_ids( $type );
	$earned_ids = badgeos_get_user_earned_achievement_ids( $user_id, $type );

	// Query Achievements
	$args = array(
		'post_type'      =>	$type,
		'orderby'        =>	'menu_order',
		'order'          =>	'ASC',
		'posts_per_page' =>	$limit,
		'offset'         => $offset,
		'post_status'    => 'publish',
		'post__not_in'   => array_diff( $hidden, $earned_ids )
	);

	// Filter - query completed or non completed achievements
	if ( $filter == 'completed' ) {
		$args = array_merge( $args, array( 'post__in' => array_merge( array(0), $earned_ids ) ) );
	}elseif( $filter == 'not-completed' ) {
		$args = array_merge( $args, array( 'post__not_in' => array_merge( $hidden, $earned_ids ) ) );
	}

	// Search
	if ( $search ) {
		$args = array_merge( $args, array( 's' => $search ) );
	}

	// Loop Achievements
	$achievement_posts = new WP_Query( $args );
	$achievement_count = 0;
	while ( $achievement_posts->have_posts() ) : $achievement_posts->the_post();
		$achievements .= badgeos_render_achievement( get_the_ID() );
		$achievement_count++;
	endwhile;

	// Sanity helper: if we're filtering for complete and we have no
	// earned achievements, $achievement_posts should definitely be false
	if ( 'completed' == $filter && empty( $earned_ids ) )
		$achievements = '';

	// Display a message for no results
	if ( empty( $achievements ) ) {
		$post_type_plural = get_post_type_object( $type )->labels->name;
		$achievements .= '<div class="badgeos-no-results">';
		if ( 'completed' == $filter ) {
			$achievements .= '<p>' . sprintf( __( 'No completed %s to display at this time.', 'badgeos' ), strtolower( $post_type_plural ) ) . '</p>';
		}else{
			$achievements .= '<p>' . sprintf( __( 'No %s to display at this time.', 'badgeos' ), strtolower( $post_type_plural ) ) . '</p>';
		}
		$achievements .= '</div><!-- .badgeos-no-results -->';
	}

	// Send back our successful response
	wp_send_json_success( array(
		'message'     => $achievements,
		'offset'      => $offset + $limit,
		'query_count' => $achievement_posts->found_posts,
		'badge_count' => $achievement_count
	) );
}

/**
 * AJAX Helper for returning feedback posts
 *
 * @since 1.1.0
 */
function badgeos_ajax_get_feedback() {

	// Parse our passed args
	$args = array(
		'post_type'        => $_REQUEST['type'],
		'posts_per_page'   => $_REQUEST['limit'],
		'status'           => $_REQUEST['status'],
		'show_attachments' => $_REQUEST['show_attachments'],
		'show_comments'    => $_REQUEST['show_comments']
	);

	// If we're searching, include the search param
	if ( ! empty( $_REQUEST['search'] ) )
		$args['s'] = $_REQUEST['search'];

	// If user doesn't have access to settings,
	// restrict posts to ones they've authored
	$badgeos_settings = get_option( 'badgeos_settings' );
	if ( ! user_can( absint( $_REQUEST['user_id'] ), $badgeos_settings['minimum_role'] ) ) {
		$args['author'] = absint( $_REQUEST['user_id'] );
	}

	$feedback = badgeos_get_feedback( $args );

	// Send back our successful response
	wp_send_json_success( array(
		'message'  => 'Success!',
		'feedback' => $feedback,
		'search'   => $_REQUEST['search']
	) );
}

/**
 * AJAX Helper for approving/denying feedback
 *
 * @since 1.1.0
 */
function badgeos_ajax_update_feedback() {

	// Verify our nonce
	check_ajax_referer( 'review_feedback', 'nonce' );

	// Get the feedback post type
	$feedback_type = $_REQUEST['feedback_type'];
	$status = ( 'approve' == $_REQUEST['status'] ) ? 'approved' : 'denied';

	// Update our meta
	update_post_meta( absint( $_REQUEST['feedback_id'] ), "_badgeos_{$feedback_type}_status", $status );

	// If our status was just approved, award the achievement
	if ( 'approved' == $status ) {
		badgeos_award_achievement_to_user( absint( $_REQUEST['achievement_id'] ), absint( $_REQUEST['user_id'] ) );
	}

	// Send back our successful response
	wp_send_json_success( array(
		'message' => '<p class="badgeos-feedback-response success">' . __( 'Status Updated!', 'badgeos' ) . '</p>',
		'status'  => ucfirst( $status )
	) );

}
