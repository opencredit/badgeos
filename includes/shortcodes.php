<?php
/**
 * Custom Shortcodes
 *
 * @package BadgeOS
 * @subpackage Front-end
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Master Achievement List Short Code
 *
 * @since 1.0.0
 */
function badgeos_achievements_list_shortcode($atts){

	// check if shortcode has already been run
	if ( isset( $GLOBALS['badgeos_achievements_list'] ) )
		return;

	global $user_ID;
	extract( shortcode_atts( array(
		'type'        => 'all',
		'limit'       => '10',
		'show_filter' => 'true',
		'show_search' => 'true',
		'group_id'    => '0',
		'user_id'     => '0',
	), $atts ) );

	wp_enqueue_style( 'badgeos-front' );
	wp_enqueue_script( 'badgeos-achievements' );

	$data = array(
		'ajax_url'    => esc_url( admin_url( 'admin-ajax.php', 'relative' ) ),
		'type'        => $type,
		'limit'       => $limit,
		'show_filter' => $show_filter,
		'show_search' => $show_search,
		'group_id'    => $group_id,
		'user_id'     => $user_id,
	);
	wp_localize_script( 'badgeos-achievements', 'badgeos', $data );

	$post_type_plural = get_post_type_object( $type )->labels->name;

	$badges = null;

	$badges .= '<div id="badgeos-achievements-filters-wrap">';
		// Filter
		if ( $show_filter == 'false' ) {

			$filter_value = 'all';
			if( $user_id ){
				$filter_value = 'completed';
				$badges .= '<input type="hidden" name="user_id" id="user_id" value="'.$user_id.'">';
			}
			$badges .= '<input type="hidden" name="achievements_list_filter" id="achievements_list_filter" value="'.$filter_value.'">';

		}else{

			$badges .= '<div id="badgeos-achievements-filter">';

				$badges .= 'Filter: <select name="achievements_list_filter" id="achievements_list_filter">';

					$badges .= '<option value="all">All '.$post_type_plural;
					// If logged in
					if ( $user_ID >0 ) {
						$badges .= '<option value="completed">Completed '.$post_type_plural;
						$badges .= '<option value="not-completed">Not Completed '.$post_type_plural;
					}
					// TODO: if show_points is true "Badges by Points"
					// TODO: if dev adds a custom taxonomy to this post type then load all of the terms to filter by

				$badges .= '</select>';

			$badges .= '</div>';

		}

		// Search
		if ( $show_search != 'false' ) {

			$search = isset( $_POST['achievements_list_search'] ) ? $_POST['achievements_list_search'] : '';
			$badges .= '<div id="badgeos-achievements-search">';
				$badges .= '<form id="achievements_list_search_go_form" action="'. get_permalink( get_the_ID() ) .'" method="post">';
				$badges .= 'Search: <input type="text" id="achievements_list_search" name="achievements_list_search" value="'. $search .'">';
				$badges .= '<input type="submit" id="achievements_list_search_go" name="achievements_list_search_go" value="Go">';
				$badges .= '</form>';
			$badges .= '</div>';

		}

	$badges .= '</div><!-- #badgeos-achievements-filters-wrap -->';

	// AJAX Container
	$badges .= '<div id="badgeos-achievements-container"></div>';
	// AJAX handler: achievements_list_load_more()

	// Hidden fields and Load More button
	$badges .= '<input type="hidden" id="badgeos_achievements_offset" value="0">';
	$badges .= '<input type="hidden" id="badgeos_achievements_count" value="0">';
	$badges .= '<input type="button" id="achievements_list_load_more" value="Load More" style="display:none;">';
	$badges .= '<div class="badgeos-spinner"></div>';

	// Reset Post Data
	wp_reset_postdata();

	// Save a global to prohibit multiple shortcodes
	$GLOBALS['badgeos_achievements_list'] = true;
	return $badges;

}
add_shortcode( 'badgeos_achievements_list', 'badgeos_achievements_list_shortcode' );

/**
 * Master Achievement List AJAX
 *
 * @since 1.0.0
 */
function achievements_list_load_more(){
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

	$badges = null;

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

	$the_badges = new WP_Query( $args );

	// Loop Achievements
	while ( $the_badges->have_posts() ) : $the_badges->the_post();

		$badge_id = get_the_ID();

		// check if user has earned this Achievement, and add an 'earned' class
		$earned_status = badgeos_get_user_achievements( array( 'user_id' => $user_id, 'achievement_id' => absint( $badge_id ) ) ) ? 'user-has-earned' : 'user-has-not-earned';

		// Only include the achievement if it HAS been earned -or- it is NOT hidden
		// if ( 'user-has-earned' == $earned_status || 'show' == get_post_meta( $badge_id, '_badgeos_hidden', true ) ) {

			$credly_class = '';
			$credly_ID = '';

			// check if current user has completed this achievement and check if Credly giveable
			if ( 'user-has-earned' == $earned_status ) {
				// Credly Share
				$giveable = credly_is_achievement_giveable( $badge_id );
				if ( $giveable ) {
					// make sure our JS and CSS is enqueued
					wp_enqueue_script( 'badgeos-achievements' );
					wp_enqueue_style( 'badgeos-widget' );
					$credly_class = ' share-credly addCredly';
					$credly_ID = 'data-credlyid="'. absint( $badge_id ) .'"';
				}
			}

			// Each Achievement
			$badges .= '<div id="badgeos-achievements-list-item-' . $badge_id . '" class="badgeos-achievements-list-item '. $earned_status . $credly_class .'"'. $credly_ID .'>';

				// Achievement Image
				$badges .= '<div class="badgeos-item-image">';
					$badges .= '<a href="'.get_permalink().'">' . badgeos_get_achievement_post_thumbnail( $badge_id ) . '</a>';
				$badges .= '</div><!-- .badgeos-item-image -->';

				$badges .= '<div class="badgeos-item-description">';

					// Achievement Title
					$badges .= '<h2 class="badgeos-item-title"><a href="'.get_permalink().'">' .get_the_title() .'</a></h2>';

					// Achievement Short Description
					$badges .= '<div class="badgeos-item-excerpt">';
						$badges .= badgeos_achievement_points_markup();
						$badges .= wpautop( get_the_excerpt() );
					$badges .= '</div><!-- .badgeos-item-excerpt -->';


					if ( $steps = badgeos_get_required_achievements_for_achievement( $badge_id ) ) {
						$badges.='<div class="badgeos-item-attached">';
							$badges.='<div id="show-more-'.$badge_id.'" class="badgeos-open-close-switch"><a class="show-hide-open" data-badgeid="'. $badge_id .'" data-action="open" href="#">Show Details</a></div>';
							$badges.='<div id="badgeos_toggle_more_window_'.$badge_id.'" class="badgeos-extras-window">'. badgeos_get_required_achievements_for_achievement_list_markup( $steps ) .'</div><!-- .badgeos-extras-window -->';
						$badges.= '</div><!-- .badgeos-item-attached -->';
					}

				$badges .= '</div><!-- .badgeos-item-description -->';

			$badges .= '</div><!-- .badgeos-achievements-list-item -->';

			$count +=1;

		// }

	endwhile;

	// Sanity helper: if we're filtering for complete and we have no
	// earned achievements, $badge_id should definitely be false
	if ( 'completed' == $filter && empty( $earned_ids ) )
		$badge_id = false;

	//display a message for no results
	if ( !$badge_id ) {
		$post_type_plural = get_post_type_object( $type )->labels->name;
		$badges .= '<div class="badgeos-no-results">';
		if ( 'completed' == $filter ) {
			$badges .= sprintf( __( 'No completed %s yet.', 'badgeos' ), strtolower( $post_type_plural ) );
		}else{
			$badges .= sprintf( __( 'There are no %s at this time.', 'badgeos' ), strtolower( $post_type_plural ) );
		}
		$badges .= '</div><!-- .badgeos-no-results -->';
	}

	$response['message']     = $badges;
	$response['offset']      = $offset + $limit;
	$response['query_count'] = $the_badges->found_posts;
	$response['badge_count'] = $count;

	echo json_encode( $response );
	die();
}
add_action( 'wp_ajax_achievements_list_load_more', 'achievements_list_load_more' );
add_action( 'wp_ajax_nopriv_achievements_list_load_more', 'achievements_list_load_more' );

add_shortcode( 'badgeos_nomination', 'badgeos_nomination_form' );

function badgeos_nomination_form() {
	global $current_user, $post;

	//verify user is logged in to view any submission data
	if ( is_user_logged_in() ) {

		// check if step unlock option is set to submission review
		get_currentuserinfo();

		if ( badgeos_save_nomination_data() )
			printf( '<p>%s</p>', __( 'Nomination saved successfully.', 'badgeos' ) );

		// check if user already has a submission for this achievement type
		if ( ! badgeos_check_if_user_has_submission( $current_user->ID, $post->ID ) ) {

			// Step Description metadata
			// TODO: Check if this meta is still in use
			if ( $step_description = get_post_meta( $post->ID, '_badgeos_step_description', true ) )
				printf( '<p><span class="badgeos-submission-label">%s:</span></p>%s', __( 'Step Description', 'badgeos' ), wpautop( $step_description ) );

			return badgeos_get_nomination_form();

		}
		// user has an active submission, so show content and comments
		else {

			return badgeos_get_user_submissions();

		}

	}else{

		return '<p><i>' .__( 'You must be logged in to post a nomination.', 'badgeos' ) .'</i></p>';

	}

}

function badgeos_get_nomination_form( $args = array() ) {

	$defaults = array(
		'heading' => sprintf( '<h4>%s</h4>', __( 'Nomination Form', 'badgeos' ) ),
		'submit' => __( 'Submit', 'badgeos' )
	);
	// filter our text
	$new_defaults = apply_filters( 'badgeos_submission_form_language', $defaults );
	// fill in missing data
	$language = wp_parse_args( $new_defaults, $defaults );

	$sub_form = '<form class="badgeos-nomination-form" method="post" enctype="multipart/form-data">';
		$sub_form .= wp_nonce_field( 'badgeos_nomination_form', 'submit_nomination', true, false );
		// nomination form heading
		$sub_form .= '<legend>'. $language['heading'] .'</legend>';
		// nomination user
		$sub_form .= '<label>'.__( 'User to nominate', 'badgeos' ).'</label>';
		$sub_form .= '<p>' .wp_dropdown_users( array( 'name' => 'badgeos_nomination_user_id', 'echo' => '0' ) ). '</p>';
		// nomination content
		$sub_form .= '<label>'.__( 'Reason for nomination', 'badgeos' ).'</label>';
		$sub_form .= '<fieldset class="badgeos-nomination-content">';
		$sub_form .= '<p><textarea name="badgeos_nomination_content"></textarea></p>';
		$sub_form .= '</fieldset>';
		// submit button
		$sub_form .= '<p class="badgeos-nomination-submit"><input type="submit" name="badgeos_nomination_submit" value="'. esc_attr( $language['submit'] ) .'" /></p>';
	$sub_form .= '</form>';

	return apply_filters( 'badgeos_get_nomination_form', $sub_form );
}

add_shortcode( 'badgeos_submission', 'badgeos_submission_form' );

function badgeos_submission_form() {
	global $current_user, $post;

	//verify user is logged in to view any submission data
	if ( is_user_logged_in() ) {

		// check if step unlock option is set to submission review
		get_currentuserinfo();

		if ( badgeos_save_submission_data() )
			printf( '<p>%s</p>', __( 'Submission saved successfully.', 'badgeos' ) );

		// check if user already has a submission for this achievement type
		if ( ! badgeos_check_if_user_has_submission( $current_user->ID, $post->ID ) ) {

			// Step Description metadata
			// TODO: Check if this meta is still in use
			if ( $step_description = get_post_meta( $post->ID, '_badgeos_step_description', true ) )
				printf( '<p><span class="badgeos-submission-label">%s:</span></p>%s', __( 'Step Description', 'badgeos' ), wpautop( $step_description ) );

			return badgeos_get_submission_form();

		}
		// user has an active submission, so show content and comments
		else {

			return badgeos_get_user_submissions();

		}

	}else{

		return '<p><i>' .__( 'You must be logged in to post a submission.', 'badgeos' ) .'</i></p>';

	}
}

function badgeos_get_submission_form( $args = array() ) {


	$defaults = array(
		'heading'    => sprintf( '<h4>%s</h4>', __( 'Submission Form', 'badgeos' ) ),
		'attachment' => __( 'Attachment:', 'badgeos' ),
		'submit'     => __( 'Submit', 'badgeos' )
	);
	// filter our text
	$new_defaults = apply_filters( 'badgeos_submission_form_language', $defaults );
	// fill in missing data
	$language = wp_parse_args( $new_defaults, $defaults );

	$sub_form = '<form class="badgeos-submission-form" method="post" enctype="multipart/form-data">';
		$sub_form .= wp_nonce_field( 'badgeos_submission_form', 'submit_submission', true, false );
		// submission form heading
		$sub_form .= '<legend>'. $language['heading'] .'</legend>';
		// submission file upload
		$sub_form .= '<fieldset class="badgeos-file-submission">';
		$sub_form .= '<p><label>'. $language['attachment'] .' <input type="file" name="document_file" id="document_file" /></label></p>';
		$sub_form .= '</fieldset>';
		// submission comment
		$sub_form .= '<fieldset class="badgeos-submission-comment">';
		$sub_form .= '<p><textarea name="badgeos_submission_content"></textarea></p>';
		$sub_form .= '</fieldset>';
		// submit button
		$sub_form .= '<p class="badgeos-submission-submit"><input type="submit" name="badgeos_submission_submit" value="'. $language['submit'] .'" /></p>';
	$sub_form .= '</form>';

	return apply_filters( 'badgeos_get_submission_form', $sub_form );
}

/**
 * Get achievement-based feedback
 *
 * @since  1.1.0
 * @param  array  $args An array of arguments to limit or alter output
 * @return string       Conatenated output for feedback
 */
function badgeos_get_feedback( $args = array() ) {

	// Setup our default args
	$defaults = array(
		'post_status' => 'publish',
		'post_type'   => 'submission'
	);
	$args = wp_parse_args( $args, $defaults );

	// If we want feedback connected to a specific achievement
	if ( isset( $args['achievement_id'] ) ) {
		$args['meta_key']   = '_badgeos_submission_achievement_id';
		$args['meta_value'] = absint( $args['achievement_id'] );
	}

	// Get our feedback
	$feedback = get_posts( $args );

	if ( ! empty( $feedback ) ) {

		$output = '<div class="badgeos-submissions">';

		foreach( $feedback as $submission ) {

			// Save submitted comment data
			// @TODO: Make this an AJAX process
			badgeos_save_comment_data( $submission->ID );

			// Setup our output
			$output .= badgeos_render_submission( $submission );

			// Include any attachments
			if ( isset( $args['show_attachments'] ) && $args['show_attachments'] ) {
				$output .= badgeos_get_submission_attachments( $submission->ID );
			}

			// Include comments and comment form
			if ( isset( $args['show_comments'] ) && $args['show_comments'] ) {
				$output .= badgeos_get_comments_for_submission( $submission->ID );
				$output .= badgeos_get_comment_form( $submission->ID );
			}

		}; // End: foreach( $feedback )

		$output .= '</div><!-- badgeos-submissions -->';

	} // End: if ( $feedback )

	// Return our filterable output
	return apply_filters( 'badgeos_get_submissions', $output, $args, $submissions );
}

/**
 * Get achievement-based submission posts
 *
 * @since  1.1.0
 * @param  array  $args An array of arguments to limit or alter output
 * @return string       Conatenated output for submission, attachments and comments
 */
function badgeos_get_submissions( $args = array() ) {

	// Setup our default args
	$defaults = array(
		'post_type'        => 'submission',
		'show_attachments' => true,
		'show_comments'    => true
	);
	$args = wp_parse_args( $args, $defaults );

	// Grab our submissions
	$submissions = badgeos_get_feedback( $args );

	// Return our filterable output
	return apply_filters( 'badgeos_get_submissions', $submissions, $args );
}

/**
 * Get submissions attached to a specific achievement by a specific user
 *
 * @since  1.0.0
 * @param  integer $achievement_id The achievement's post ID
 * @param  integer $user_id        The user's ID
 * @return string                  Conatenated output for submission, attachments and comments
 */
function badgeos_get_user_submissions( $achievement_id = 0, $user_id = 0 ) {
	global $user_ID, $post;

	// If we were not given an achievement ID,
	// use the current post's ID
	if ( empty( $achievement_id ) )
		$achievement_id = $post->ID;

	// If we were not passed a user ID,
	// use the current user's ID
	if ( empty( $user_id ) )
		$user_id = $user_ID;

	// Grab our submissions for the current user
	$submissions = badgeos_get_submissions( array(
		'post_author'    => $user_id,
		'achievement_id' => $achievement_id,
	) );

	// Return filterable output
	return apply_filters( 'badgeos_get_user_submissions', $submissions, $achievement_id, $user_id );
}
