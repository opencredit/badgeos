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

	// Content Container
	$badges .= '<div id="badgeos-achievements-container"></div>';

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
 * Render a single achievement
 *
 * Usage: [badgeos_achievement id=12]
 *
 * @since  1.1.0
 * @param  array  $atts Our attributes array
 * @return string       Concatenated markup
 */
function badgeos_achievement_shortcode( $atts = array() ) {
	global $post, $badgeos;

	// get the post id
	$atts = shortcode_atts( array(
	  'id' => $post->ID,
	), $atts );

	// return if post id not specified
	if ( empty($atts['id']) )
	  return;

	wp_enqueue_style( 'badgeos-front' );
	wp_enqueue_script( 'badgeos-achievements' );

	// get the post content and format the badge display
	$achievement = get_post($atts['id']);
	$output = '';

	// If we're dealing with an achievement post
	if ( in_array( $achievement->post_type, array_keys( $badgeos->achievement_types ) ) ) {
		$output .= '<div id=badgeos-achievements-container>';  // necessary for the jquery click handler to be called
		$output .= badgeos_render_achievement( $achievement );
		$output .= '</div>';
	}

	// Return our rendered achievement
	return $output;
}
add_shortcode( 'badgeos_achievement', 'badgeos_achievement_shortcode' );

function badgeos_nomination_form( $atts = array() ) {
	global $user_ID, $post;

	// Parse our attributes
	$atts = shortcode_atts( array(
		'achievement_id' => $post->ID
	), $atts );

	$output = '';

	// Verify user is logged in to view any submission data
	if ( is_user_logged_in() ) {

		// If we've just saved nomination data
		if ( badgeos_save_nomination_data() )
			$output .= sprintf( '<p>%s</p>', __( 'Nomination saved successfully.', 'badgeos' ) );

		// Return the user's nominations
		if ( badgeos_check_if_user_has_nomination( $user_ID, $atts['achievement_id'] ) )
			$output .= badgeos_get_user_nominations( '', $atts['achievement_id'] );

		// Include the nomination form
		$output .= badgeos_get_nomination_form( array( 'user_id' => $user_ID, 'achievement_id' => $atts['achievement_id'] ) );

	} else {

		$output = '<p><i>' . __( 'You must be logged in to post a nomination.', 'badgeos' ) . '</i></p>';

	}

	return $output;

}
add_shortcode( 'badgeos_nomination', 'badgeos_nomination_form' );

function badgeos_submission_form( $atts = array() ) {
	global $user_ID, $post;

	// Parse our attributes
	$atts = shortcode_atts( array(
		'achievement_id' => $post->ID
	), $atts );

	// Verify user is logged in to view any submission data
	if ( is_user_logged_in() ) {

		if ( badgeos_save_submission_data() )
			printf( '<p>%s</p>', __( 'Submission saved successfully.', 'badgeos' ) );

		// Return either the user's submission or the submission form
		if ( badgeos_check_if_user_has_submission( $user_ID, $atts['achievement_id'] ) )
			return badgeos_get_user_submissions( '', $atts['achievement_id'] );
		else
			return badgeos_get_submission_form( array( 'user_id' => $user_ID, 'achievement_id' => $atts['achievement_id'] ) );

	} else {

		return '<p><i>' . __( 'You must be logged in to post a submission.', 'badgeos' ) . '</i></p>';

	}
}
add_shortcode( 'badgeos_submission', 'badgeos_submission_form' );

/**
 * Shortcode to display a filterable list of Submissions
 *
 * @since  1.1.0
 * @param  array  $atts Attributes passed via shortcode
 * @return string       Concatenated output
 */
function badgeos_display_submissions( $atts = array() ) {
	global $user_ID;

	// Parse our attributes
	$atts = shortcode_atts( array(
		'type'             => 'submission',
		'limit'            => '10',
		'status'           => 'all',
		'show_filter'      => true,
		'show_search'      => true,
		'show_attachments' => true,
		'show_comments'    => true
	), $atts );

	$feedback = badgeos_render_feedback( $atts );

	// Enqueue and localize our JS
	$atts['ajax_url'] = esc_url( admin_url( 'admin-ajax.php', 'relative' ) );
	$atts['user_id']  = $user_ID;
	wp_enqueue_script( 'badgeos-achievements' );
	wp_localize_script( 'badgeos-achievements', 'badgeos_feedback', $atts );

	// Return our rendered content
	return $feedback;

}
add_shortcode( 'badgeos_submissions', 'badgeos_display_submissions' );

/**
 * Shortcode to display a filterable list of Nominations
 *
 * @since  1.1.0
 * @param  array  $atts Attributes passed via shortcode
 * @return string       Concatenated output
 */
function badgeos_display_nominations( $atts = array() ) {
	global $user_ID;

	// Parse our attributes
	$atts = shortcode_atts( array(
		'type'             => 'nomination',
		'limit'            => '10',
		'status'           => 'all',
		'show_filter'      => true,
		'show_search'      => true,
		'show_attachments' => 'false',
		'show_comments'    => 'false'
	), $atts );

	$feedback = badgeos_render_feedback( $atts );

	// Enqueue and localize our JS
	$atts['ajax_url'] = esc_url( admin_url( 'admin-ajax.php', 'relative' ) );
	$atts['user_id']  = $user_ID;
	wp_enqueue_script( 'badgeos-achievements' );
	wp_localize_script( 'badgeos-achievements', 'badgeos_feedback', $atts );

	// Return our rendered content
	return $feedback;

}
add_shortcode( 'badgeos_nominations', 'badgeos_display_nominations' );
