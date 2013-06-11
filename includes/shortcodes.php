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
add_shortcode( 'badgeos_nomination', 'badgeos_nomination_form' );

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
add_shortcode( 'badgeos_submission', 'badgeos_submission_form' );

/**
 * Shortcode for displaying a filterable list of submissions
 *
 * @since  1.1.0
 * @param  array  $atts Shortcode attributes
 * @return string       Contatenated markup
 */
function badgeos_display_feedback( $atts = array() ) {
	global $current_user;

	// Parse our attributes
	$atts = shortcode_atts( array(
		'type'             => 'submission',
		'limit'            => '10',
		'status'           => 'pending',
		'show_filter'      => true,
		'show_search'      => true,
		'show_attachments' => true,
		'show_comments'    => true
	), $atts );

	// Setup our feedback args
	$args = array(
		'post_type'        => $atts['type'],
		'posts_per_page'   => $atts['limit'],
		'meta_key'         => '_badgeos_submission_status',
		'meta_value'       => $atts['status'],
		'show_attachments' => $atts['show_attachments'],
		'show_comments'    => $atts['show_comments']
	);

	// If we're not an admin, limit results to the current user
	$badgeos_settings = get_option( 'badgeos_settings' );
	if ( ! current_user_can( $badgeos_settings['minimum_role'] ) ) {
		$args['author'] = $current_user->ID;
	}

	// Get our feedback
	$feedback = badgeos_get_feedback( $args );

	// Search
	if ( 'false' !== $show_search ) {

		$search = isset( $_POST['feedback_search'] ) ? $_POST['feedback_search'] : '';
		$output .= '<div id="badgeos-feedback-search">';
			$output .= '<form id="feedback_search_form" action="'. get_permalink( get_the_ID() ) .'" method="post">';
			$output .= __( 'Search:', 'badgeos' ) . ' <input type="text" id="feedback_search" name="feedback_search" value="'. $search .'">';
			$output .= '<input type="submit" id="achievements_list_search_go" name="achievements_list_search_go" value="' . __( 'Search', 'badgeos' ) . '">';
			$output .= '</form>';
		$output .= '</div><!-- .badgeos-feedback-search -->';

	}

	// Filter
	if ( 'false' !== $show_filter ) {

		$output .= '<div id="badgeos-feedback-filter">';
			$output .= __( 'Filter:', 'badgeos' );
			$output .= ' <select name="status_filter" id="status_filter">';
				$output .= '<option value="">' . __( 'All', 'badgeos' ) . '</option>';
				$output .= '<option value="pending">' . __( 'Pending', 'badgeos' ) . '</option>';
				$output .= '<option value="approved">' . __( 'Approved', 'badgeos' ) . '</option>';
				$output .= '<option value="denied">' . __( 'Denied', 'badgeos' ) . '</option>';
			$output .= '</select>';
		$output .= '</div>';

	} else {

		$output .= '<input type="hidden" name="status_filter" id="status_filter" value="' . esc_attr( $atts['status'] ) . '">';

	}

	$output .= '<div class="badgeos-feedback-container">';
	$output .= $feedback;
	$output .= '</div>';

	// Return our filterable output
	return apply_filters( 'badgeos_display_feedback', $output, $atts );

}
add_shortcode( 'badgeos_submissions', 'badgeos_display_feedback' );
