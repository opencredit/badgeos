<?php
/**
 * Content Filters
 *
 * @package BadgeOS
 * @subpackage Front-end
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Displays the submission form on achievement type single pages if the meta option is enabled
 *
 * @since  1.0.0
 * @param  string $content The page content before meta box insertion
 * @return string          The page content after meta box insertion
 */
function badgeos_achievement_submissions( $content = '' ) {
	global $post;

	if ( is_single() ) {

		// get achievement object for the current post type
		$post_type = get_post_type( $post );
		$achievement = get_page_by_title( $post_type, 'OBJECT', 'achievement-type' );
		if ( !$achievement ) {
			global $wp_post_types;

			$labels = array( 'name', 'singular_name' );
			// check for other variations
			foreach ( $labels as $label ) {
				$achievement = get_page_by_title( $wp_post_types[$post_type]->labels->$label, 'OBJECT', 'achievement-type' );
				if ( $achievement )
					break;
			}
		}

		if ( !$achievement )
			return $content;


		// check if submission or nomination is set
		$earned_by = get_post_meta( $post->ID, '_badgeos_earned_by', true );

		if ( ( $earned_by == 'submission' || $earned_by == 'submission_auto' ) && is_user_logged_in() ) {

			$submission = badgeos_submission_form();

			//return the content with the submission shortcode data
			return $content . $submission;

		} elseif ( $earned_by == 'nomination' && is_user_logged_in() ) {

			$nomination = badgeos_nomination_form();

			//return the content with the nomination shortcode data
			return $content . $nomination;

		}

	}

	return $content;

}
add_filter( 'the_content', 'badgeos_achievement_submissions' );

/**
 * Add filters to remove stuff from our singular pages and add back in how we want it
 *
 * @since 1.0.0
 * @return null
 */
function badgeos_do_single_filters() {
	// check we're in the right place
	badgeos_is_main_loop();
	// enqueue our stylesheet
	wp_enqueue_style( 'badgeos-single' );
	// no worries.. we'll add back later
	remove_filter( 'the_content', 'wpautop' );
	// filter out the post title
	// add_filter( 'the_title', 'badgeos_remove_to_reformat_entries_title', 10, 2 );
	// and filter out the post image
	add_filter( 'post_thumbnail_html', 'badgeos_remove_to_reformat_entries_title', 10, 2 );
}
add_action( 'wp_enqueue_scripts', 'badgeos_do_single_filters' );

/**
 * Filter out the post title/post image and add back (later) how we want it
 *
 * @since 1.0.0
 * @param  string  $html The page content prior to filtering
 * @param  integer $id   The page id
 * @return string        The page content after being filtered
 */
function badgeos_remove_to_reformat_entries_title( $html = '', $id = 0 ) {

	// remove, but only on the main loop!
	if ( badgeos_is_main_loop( $id ) )
		return '';

	// nothing to see here... move along
	return $html;
}

/**
 * Filter badge content to add our removed content back
 *
 * @since  1.0.0
 * @param  string $content The page content
 * @return string          The page content after reformat
 */
function badgeos_reformat_entries( $content ) {

	wp_enqueue_style( 'badgeos-front' );

	$badge_id = get_the_ID();

	// filter, but only on the main loop!
	if ( !badgeos_is_main_loop( $badge_id ) )
		return wpautop( $content );

	// now that we're where we want to be, tell the filters to stop removing
	$GLOBALS['badgeos_reformat_content'] = true;

	// do badge title markup
	// $title = '<h1 class="badge-title">'. get_the_title() .'</h1>';

	// check if user has earned this Achievement, and add an 'earned' class
	$class = badgeos_get_user_achievements( array( 'achievement_id' => absint( $badge_id ) ) ) ? ' earned' : '';

	// wrap our content, add the thumbnail and title and add wpautop back
	$newcontent = '<div class="achievement-wrap'. $class .'">';

	// Check if current user has earned this achievement
	$newcontent .= badgeos_render_earned_achievement_text( $badge_id, get_current_user_id() );

	$newcontent .= '<div class="alignleft badgeos-item-image">'. badgeos_get_achievement_post_thumbnail( $badge_id ) .'</div>';
	// $newcontent .= $title;

	// Points for badge
	$newcontent .= badgeos_achievement_points_markup();
	$newcontent .= wpautop( $content );

	// Include output for our steps
	$newcontent .= badgeos_get_required_achievements_for_achievement_list( $badge_id );

	// Include achievement earners, if this achievement supports it
	if ( $show_earners = get_post_meta( $badge_id, '_badgeos_show_earners', true ) )
		$newcontent .= badgeos_get_achievement_earners_list( $badge_id );

	$newcontent .= '</div><!-- .achievement-wrap -->';

	// Ok, we're done reformating
	$GLOBALS['badgeos_reformat_content'] = false;

	return $newcontent;
}
add_filter( 'the_content', 'badgeos_reformat_entries', 9 );

/**
 * Helper function tests that we're in the main loop
 *
 * @since  1.0.0
 * @param  bool|integer $id The page id
 * @return boolean     A boolean determining if the function is in the main loop
 */
function badgeos_is_main_loop( $id = false ) {

	$slugs = badgeos_get_achievement_types_slugs();
	// only run our filters on the badgeos singular pages
	if ( is_admin() || empty( $slugs ) || !is_singular( $slugs ) )
		return false;
	// w/o id, we're only checking template context
	if ( !$id )
		return true;

	// Checks several variables to be sure we're in the main loop (and won't effect things like post pagination titles)
	return ( ( $GLOBALS['post']->ID == $id ) && in_the_loop() && empty( $GLOBALS['badgeos_reformat_content'] ) );
}

/**
 * Gets achivement's required steps and returns HTML markup for these steps
 *
 * @since  1.0.0
 * @param  integer $achievement_id The given achievement's post ID
 * @param  integer $user_id        A given user's ID
 * @return string                  The markup for our list
 */
function badgeos_get_required_achievements_for_achievement_list( $achievement_id = 0, $user_id = 0 ) {

	// Grab the current post ID if no achievement_id was specified
	if ( ! $achievement_id ) {
		global $post;
		$achievement_id = $post->ID;
	}

	// Grab the current user's ID if none was specifed
	if ( ! $user_id )
		$user_id = wp_get_current_user()->ID;

	// Grab our achievement's required steps
	$steps = badgeos_get_required_achievements_for_achievement( $achievement_id );

	// Return our markup output
	return badgeos_get_required_achievements_for_achievement_list_markup( $steps, $user_id );

}

/**
 * Generate HTML markup for an achievement's required steps
 *
 * This will generate an unorderd list (<ul>) if steps are non-sequential
 * and an ordered list (<ol>) if steps require sequentiality.
 *
 * @since  1.0.0
 * @param  array   $steps           An achievement's required steps
 * @param  integer $achievement_id  The given achievement's ID
 * @param  integer $user_id         The given user's ID
 * @return string                   The markup for our list
 */
function badgeos_get_required_achievements_for_achievement_list_markup( $steps = array(), $achievement_id = 0, $user_id = 0 ) {

	// If we don't have any steps, or our steps aren't an array, return nothing
	if ( ! $steps || ! is_array( $steps ) )
		return null;

	// Grab the current post ID if no achievement_id was specified
	if ( ! $achievement_id ) {
		global $post;
		$achievement_id = $post->ID;
	}

	$count = count( $steps );

	// If we have no steps, return nothing
	if ( ! $count )
		return null;

	// Grab the current user's ID if none was specifed
	if ( ! $user_id )
		$user_id = wp_get_current_user()->ID;

	// Setup our variables
	$output = $step_output = '';
	$container = badgeos_is_achievement_sequential() ? 'ol' : 'ul';

	// Concatenate our output
	foreach ( $steps as $step ) {

		// check if user has earned this Achievement, and add an 'earned' class
		$earned_status = badgeos_get_user_achievements( array(
			'user_id' => absint( $user_id ),
			'achievement_id' => absint( $step->ID ),
			'since' => absint( badgeos_achievement_last_user_activity( $achievement_id, $user_id ) )
		) ) ? 'user-has-earned' : 'user-has-not-earned';

		// get step title and if it doesn't have a title get the step trigger type post-meta
		$title = !empty( $step->post_title ) ? $step->post_title : get_post_meta( $step->ID, '_badgeos_trigger_type', true );
		$step_output .= '<li class="'. apply_filters( 'badgeos_step_class', $earned_status, $step ) .'">'. apply_filters( 'badgeos_step_title_display', $title, $step ) . '</li>';
	}
	$post_type_object = get_post_type_object( $step->post_type );

	$output .= '<h4>' . apply_filters( 'badgeos_steps_heading', sprintf( __( '%1$d Required %2$s', 'badgeos' ), $count, $post_type_object->labels->name ), $steps ) . '</h4>';
	$output .= '<' . $container .' class="badgeos-required-achievements">';
	$output .= $step_output;
	$output .= '</'. $container .'><!-- .badgeos-required-achievements -->';

	// Return our output
	return $output;

}

/**
 * Filter our step titles to link to achievements and achievement type archives
 *
 * @since  1.0.0
 * @param  string $title Our step title
 * @param  object $step  Our step's post object
 * @return string        Our potentially udated title
 */
function badgeos_step_link_title_to_achievement( $title = '', $step = null ) {

	// Grab our step requirements
	$step_requirements = badgeos_get_step_requirements( $step->ID );

	// Setup a URL to link to a specific achievement or an achievement type
	if ( ! empty( $step_requirements['achievement_post'] ) )
		$url = get_permalink( $step_requirements['achievement_post'] );
	// elseif ( ! empty( $step_requirements['achievement_type'] ) )
	//  $url = get_post_type_archive_link( $step_requirements['achievement_type'] );

	// If we have a URL, update the title to link to it
	if ( isset( $url ) && ! empty( $url ) )
		$title = '<a href="' . esc_url( $url ) . '">' . $title . '</a>';

	return $title;
}
add_filter( 'badgeos_step_title_display', 'badgeos_step_link_title_to_achievement', 10, 2 );

/**
 * Generate markup for an achievement's points output
 *
 * @since  1.0.0
 * @param  integer $achievement_id The given achievment's ID
 * @return string                  The HTML markup for our points
 */
function badgeos_achievement_points_markup( $achievement_id = 0 ) {

	// Grab the current post ID if no achievement_id was specified
	if ( ! $achievement_id ) {
		global $post;
		$achievement_id = $post->ID;
	}

	// Return our markup
	return ( $points = get_post_meta( $achievement_id, '_badgeos_points', true ) ) ? '<div class="badgeos-item-points">' . sprintf( __( '%d Points', 'badgeos' ), $points ) . '</div>' : '';
}

/**
 * Adds "earned"/"not earned" post_class based on viewer's status
 *
 * @param  array $classes Post classes
 * @return array          Updated post classes
 */
function badgeos_add_earned_class_single( $classes = array() ) {
	global $user_ID;

	// check if current user has earned the achievement they're viewing
	$classes[] = badgeos_get_user_achievements( array( 'user_id' => $user_ID, 'achievement_id' => get_the_ID() ) ) ? 'user-has-earned' : 'user-has-not-earned';

	return $classes;
}
add_filter( 'post_class', 'badgeos_add_earned_class_single' );

/**
 * Returns a message if user has earned the achievement.
 *
 * @since  1.1.0
 *
 * @param  integer $achievement_id Achievement ID.
 * @param  integer $user_id        User ID.
 * @return string                  HTML Markup.
 */
function badgeos_render_earned_achievement_text( $achievement_id = 0, $user_id = 0 ) {

	$earned_message = '';

	if ( badgeos_has_user_earned_achievement( $achievement_id, $user_id ) ) {
		$earned_message .= '<div class="badgeos-achievement-earned"><p>' . __( 'You have earned this achievement!', 'badgeos' ) . '</p></div>';
		if ( $congrats_text = get_post_meta( $achievement_id, '_badgeos_congratulations_text', true ) ) {
			$earned_message .= '<div class="badgeos-achievement-congratulations">' . wpautop( $congrats_text ) . '</div>';
		}
	}

	return apply_filters( 'badgeos_earned_achievement_message', $earned_message, $achievement_id, $user_id );
}

/**
 * Check if user has earned a given achievement.
 *
 * @since  alpha
 *
 * @param  integer $achievement_id Achievement ID.
 * @param  integer $user_id        User ID.
 * @return bool                    True if user has earned the achievement, otherwise false.
 */
function badgeos_has_user_earned_achievement( $achievement_id = 0, $user_id = 0 ) {
	$earned_achievements = badgeos_get_user_achievements( array( 'user_id' => absint( $user_id ), 'achievement_id' => absint( $achievement_id ) ) );
	$earned_achievement = ! empty( $earned_achievements );
	return apply_filters( 'badgeos_has_user_earned_achievement', $earned_achievement, $achievement_id, $user_id );
}

/**
 * Render an achievement
 *
 * @since  1.0.0
 * @param  integer $achievement The achievement's post ID
 * @return string               Concatenated markup
 */
function badgeos_render_achievement( $achievement = 0 ) {
	global $user_ID;

	// If we were given an ID, get the post
	if ( is_numeric( $achievement ) )
		$achievement = get_post( $achievement );

	// make sure our JS and CSS is enqueued
	wp_enqueue_script( 'badgeos-achievements' );
	wp_enqueue_style( 'badgeos-widget' );

	// check if user has earned this Achievement, and add an 'earned' class
	$earned_status = badgeos_get_user_achievements( array( 'user_id' => $user_ID, 'achievement_id' => absint( $achievement->ID ) ) ) ? 'user-has-earned' : 'user-has-not-earned';

	// Setup our credly classes
	$credly_class = '';
	$credly_ID = '';

	// If the achievement is earned and givable, override our credly classes
	if ( 'user-has-earned' == $earned_status && $giveable = credly_is_achievement_giveable( $achievement->ID, $user_ID ) ) {
		$credly_class = ' share-credly addCredly';
		$credly_ID = 'data-credlyid="'. absint( $achievement->ID ) .'"';
	}

	// Each Achievement
	$output = '';
	$output .= '<div id="badgeos-achievements-list-item-' . $achievement->ID . '" class="badgeos-achievements-list-item '. $earned_status . $credly_class .'"'. $credly_ID .'>';

		// Achievement Image
		$output .= '<div class="badgeos-item-image">';
		$output .= '<a href="' . get_permalink( $achievement->ID ) . '">' . badgeos_get_achievement_post_thumbnail( $achievement->ID ) . '</a>';
		$output .= '</div><!-- .badgeos-item-image -->';

		// Achievement Content
		$output .= '<div class="badgeos-item-description">';

			// Achievement Title
			$output .= '<h2 class="badgeos-item-title"><a href="' . get_permalink( $achievement->ID ) . '">' . get_the_title( $achievement->ID ) .'</a></h2>';

			// Achievement Short Description
			$output .= '<div class="badgeos-item-excerpt">';
			$output .= badgeos_achievement_points_markup( $achievement->ID );
			$excerpt = !empty( $achievement->post_excerpt ) ? $achievement->post_excerpt : $achievement->post_content;
			$output .= wpautop( apply_filters( 'get_the_excerpt', $excerpt ) );
			$output .= '</div><!-- .badgeos-item-excerpt -->';

			// Render our Steps
			if ( $steps = badgeos_get_required_achievements_for_achievement( $achievement->ID ) ) {
				$output.='<div class="badgeos-item-attached">';
					$output.='<div id="show-more-'.$achievement->ID.'" class="badgeos-open-close-switch"><a class="show-hide-open" data-badgeid="'. $achievement->ID .'" data-action="open" href="#">' . __( 'Show Details', 'badgeos' ) . '</a></div>';
					$output.='<div id="badgeos_toggle_more_window_'.$achievement->ID.'" class="badgeos-extras-window">'. badgeos_get_required_achievements_for_achievement_list_markup( $steps, $achievement->ID ) .'</div><!-- .badgeos-extras-window -->';
				$output.= '</div><!-- .badgeos-item-attached -->';
			}

		$output .= '</div><!-- .badgeos-item-description -->';

	$output .= '</div><!-- .badgeos-achievements-list-item -->';

	// Return our filterable markup
	return apply_filters( 'badgeos_render_achievement', $output, $achievement->ID );

}

/**
 * Render a filterable list of feedback
 *
 * @since  1.1.0
 * @param  array  $atts Shortcode attributes
 * @return string       Contatenated markup
 */
function badgeos_render_feedback( $atts = array() ) {

	$atts = wp_parse_args( $atts, array(
		'type'             => 'submission',
		'limit'            => '10',
		'status'           => 'all',
		'show_filter'      => true,
		'show_search'      => true,
		'show_attachments' => true,
		'show_comments'    => true
	) );

	$feedback = badgeos_get_feedback( array(
		'post_type'        => $atts['type'],
		'posts_per_page'   => $atts['limit'],
		'show_attachments' => $atts['show_attachments'],
		'show_comments'    => $atts['show_comments'],
		'status'           => $atts['status']
	) );

	$output = '';
	$output .= badgeos_render_feedback_search( $atts );
	$output .= badgeos_render_feedback_filters( $atts );
	$output .= '<div class="badgeos-spinner" style="display:none;"></div>';
	$output .= '<div class="badgeos-feedback-container">';
	$output .= $feedback;
	$output .= '</div>';

	return apply_filters( 'badgeos_render_feedback', $output, $atts );

}

/**
 * Render feedback search input.
 *
 * @since  1.4.0
 *
 * @param  array  $atts Shortcode attributes.
 * @return string       HTML Markup.
 */
function badgeos_render_feedback_search( $atts = array() ) {
	$output = '';
	$search = isset( $_POST['feedback_search'] ) ? $_POST['feedback_search'] : '';

	if ( 'false' !== $atts['show_search'] ) {
		$output .= '<div class="badgeos-feedback-search">';
			$output .= '<form class="badgeos-feedback-search-form" action="" method="POST">';
			$output .= '<input type="text" class="badgeos-feedback-search-input" name="feedback_search" value="'. $search .'">';
			$output .= '<input type="submit" class="badgeos-feedback-search-button" name="feedback_search_button" value="' . __( 'Search', 'badgeos' ) . '">';
			$output .= '</form>';
		$output .= '</div><!-- .badgeos-feedback-search -->';
	}

	return apply_filters( 'badgeos_render_feedback_search', $output, $atts, $search );
}

/**
 * Render feedback filter inputs.
 *
 * @since  1.4.0
 *
 * @param  array  $atts Shortcode atts.
 * @return string       HTML Markup.
 */
function badgeos_render_feedback_filters( $atts = array() ) {
	$output = '';

	if ( 'false' !== $atts['show_filter'] ) {

		$output .= '<div class="badgeos-feedback-filter">';
			$output .= '<label for="status_filter">' . __( 'Status:', 'badgeos' ) . '</label>';
			$output .= ' <select name="status_filter" id="status_filter">';
				$output .= '<option' . selected( $atts['status'], 'all', false ) . ' value="all">' . __( 'All', 'badgeos' ) . '</option>';
				$output .= '<option' . selected( $atts['status'], 'pending', false ) . ' value="pending">' . __( 'Pending', 'badgeos' ) . '</option>';
				$output .= '<option' . selected( $atts['status'], 'approved', false ) . ' value="approved">' . __( 'Approved', 'badgeos' ) . '</option>';
				if ( 'submission' == $atts['type'] )
					$output .= '<option' . selected( $atts['status'], 'auto-approved', false ) . ' value="auto-approved">' . __( 'Auto-approved', 'badgeos' ) . '</option>';
				$output .= '<option' . selected( $atts['status'], 'denied', false ) . ' value="denied">' . __( 'Denied', 'badgeos' ) . '</option>';
			$output .= '</select>';
		$output .= '</div>';

	} else {
		$output .= '<input type="hidden" name="status_filter" id="status_filter" value="' . esc_attr( $atts['status'] ) . '">';
	}

	return apply_filters( 'badgeos_render_feedback_filters', $output, $atts );
}

/**
 * Render a given nomination
 *
 * @since  1.1.0
 * @param  object $nomination A nomination post object
 * @param  array  $args       Additional args for content options
 * @return string             Concatenated output
 */
function badgeos_render_nomination( $nomination = null, $args = array() ) {
	global $post;

	// If we weren't given a nomination, use the current post
	if ( empty( $nomination ) ) {
		$nomination = $post;
	}

	// Grab the connected achievement
	$achievement_id = get_post_meta( $nomination->ID, '_badgeos_nomination_achievement_id', true );

	// Concatenate our output
	$output = '<div class="badgeos-nomination badgeos-feedback badgeos-feedback-' . $nomination->ID . '">';

		// Title
		$output .= '<h4>';
		$output .= sprintf( __( '%1$s nominated for %2$s', 'badgeos' ),
			get_userdata( $nomination->post_author )->display_name,
			'<a href="' . get_permalink( $achievement_id ) .'">' . get_the_title( $achievement_id ) . '</a>'
		);
		$output .= '</h4>';

		// Content
		$output .= wpautop( $nomination->post_content );

		// Approval Status
		$output .= '<p class="badgeos-comment-date-by">';
			$output .= '<span class="badgeos-status-label">' . __( 'Status:', 'badgeos' ) . '</span> ';
			$output .= get_post_meta( $nomination->ID, '_badgeos_nomination_status', true );
		$output .= '</p>';

		// Approve/Deny Buttons for admins only
		if ( badgeos_user_can_manage_submissions() ) {
			$output .= badgeos_render_feedback_buttons( $nomination->ID );
		}

	$output .= '</div><!-- .badgeos-original-submission -->';

	// Return our filterable output
	return apply_filters( 'badgeos_render_nomination', $output, $nomination );
}

/**
 * Render a given submission
 *
 * @since  1.1.0
 * @param  object $submission A submission post object
 * @param  array  $args       Additional args for content options
 * @return string             Concatenated output
 */
function badgeos_render_submission( $submission = null, $args = array() ) {
	global $post;

	// If we weren't given a submission, use the current post
	if ( empty( $submission ) ) {
		$submission = $post;
	}

	// Get the connected achievement ID
	$achievement_id = get_post_meta( $submission->ID, '_badgeos_submission_achievement_id', true );
	$status = get_post_meta( $submission->ID, '_badgeos_submission_status', true );
	$submission_author = get_userdata( $submission->post_author );
	$display_name = is_object( $submission_author ) ? $submission_author->display_name : '';

	// Concatenate our output
	$output = '<div class="badgeos-submission badgeos-feedback badgeos-feedback-' . $submission->ID . '">';

		// Submission Title
		$output .= '<h2>' . sprintf( __( 'Submission: "%1$s"', 'badgeos' ), get_the_title( $achievement_id )) . '</h2>';
		// Submission Meta
		$output .= '<p class="badgeos-submission-meta">';
			$output .= sprintf( '<strong class="label">%1$s</strong> <span class="badgeos-feedback-author">%2$s</span><br/>', __( 'Author:', 'badgeos' ), $display_name );
			$output .= sprintf( '<strong class="label">%1$s</strong> <span class="badgeos-feedback-date">%2$s</span><br/>', __( 'Date:', 'badgeos' ), get_the_time( 'F j, Y h:i a', $submission ) );
			if ( $achievement_id != $submission->ID ) {
				$output .= sprintf( '<strong class="label">%1$s</strong> <span class="badgeos-feedback-link">%2$s</span><br/>', __( 'Achievement:', 'badgeos' ), '<a href="' . get_permalink( $achievement_id ) .'">' . get_the_title( $achievement_id ) . '</a>' );
			}
			$output .= sprintf( '<strong class="label">%1$s</strong> <span class="badgeos-feedback-status">%2$s</span><br/>', __( 'Status:', 'badgeos' ), ucfirst( $status ) );
		$output .= '</p>';

		// Submission Content
		$output .= '<div class="badgeos-submission-content">';
		$output .= html_entity_decode(  $submission->post_content , ENT_QUOTES, 'UTF-8' );
		$output .= '</div>';

		// Include any attachments
		if ( isset( $args['show_attachments'] ) && 'false' !== $args['show_attachments'] ) {
			$output .= badgeos_get_submission_attachments( $submission->ID );
		}

		// Approve/Deny Buttons for admins only
		if ( badgeos_user_can_manage_submissions() ) {
			$output .= badgeos_render_feedback_buttons( $submission->ID );
		}

		// Include comments and comment form
		if ( isset( $args['show_comments'] ) && 'false' !== $args['show_comments'] ) {
			$output .= badgeos_get_comments_for_submission( $submission->ID );
			$output .= badgeos_get_comment_form( $submission->ID );
		}

	$output .= '</div><!-- .badgeos-submission -->';


	if( badgeos_get_hidden_achievement_by_id( $achievement_id ) ){
		$output = '';
	}

	// Return our filterable output
	return apply_filters( 'badgeos_render_submission', $output, $submission );
}

/**
 * Renter a given submission attachment
 *
 * @since  1.1.0
 * @param  object $attachment The attachment post object
 * @return string             Concatenated markup
 */
function badgeos_render_submission_attachment( $attachment = null ) {
	// If we weren't given an attachment, use the current post
	if ( empty( $attachment ) ) {
		global $post;
		$attachment = $post;
	}

	$userdata = get_userdata( $attachment->post_author );
	$display_name = is_object( $userdata ) ? $userdata->display_name : '';

	// Concatenate the markup
	$output = '<li class="badgeos-attachment">';
	$output .= sprintf( __( '%1$s - uploaded %2$s by %3$s', 'badgeos' ),
		wp_get_attachment_link( $attachment->ID, 'full', false, null, $attachment->post_title ),
		get_the_time( 'F j, Y g:i a', $attachment ),
		$display_name
	);
	$output .= '</li><!-- .badgeos-attachment -->';

	// Return our filterable output
	return apply_filters( 'badgeos_render_submission_attachment', $output, $attachment );
}

/**
 * Render a given submission comment
 *
 * @since  1.1.0
 * @param  object $comment  The comment object
 * @param  string $odd_even Custom class to use for alternating comments (e.g. "odd" or "even")
 * @return string           The concatenated markup
 */
function badgeos_render_submission_comment( $comment = null, $odd_even = 'odd' ) {

	// Concatenate our output
	$output = '<li class="badgeos-submission-comment ' . $odd_even . '">';

		// Author and Meta info
		$output .= '<p class="badgeos-comment-date-by">';
		$output .= sprintf( __( '%1$s on %2$s', 'badgeos' ),
			'<cite class="badgeos-comment-author">' . get_userdata( $comment->user_id )->display_name . '</cite>',
			'<span class="badgeos-comment-date">' . get_comment_date( 'F j, Y g:i a', $comment->comment_ID ) . '<span>'
		);
		$output .= '</p>';

		// Content
		$output .= '<div class="badgeos-comment-text">';
		$output .= html_entity_decode( wpautop( $comment->comment_content ), ENT_QUOTES, 'UTF-8' );
		$output .= '</div>';

	$output .= '</li><!-- badgeos-submission-comment -->';

	// Return our filterable output
	return apply_filters( 'badgeos_render_submission_comment', $output, $comment, $odd_even );
}

/**
 * Render the approve/deny buttons for a given piece of feedback
 *
 * @since  1.0.0
 * @param  integer $feedback_id The feedback's post ID
 * @return string               The concatinated markup
 */
function badgeos_render_feedback_buttons( $feedback_id = 0 ) {
	global $post, $user_ID;

	// Use the current post ID if no ID provided
	$feedback_id    = ! empty( $feedback_id ) ? ( is_array( $feedback_id ) ? $feedback_id[0] : $feedback_id ) : ( isset( $post->ID ) ? $post->ID : 0 );
	$feedback       = get_post( $feedback_id );
	$feedback_type  = get_post_type( $feedback_id );
	$achievement_id = get_post_meta( $feedback_id, "_badgeos_{$feedback_type}_achievement_id", true );
	$user_id        = isset( $feedback->post_author ) ? $feedback->post_author : 0;

	// Concatenate our output
	$output = '';
	$output .= '<p class="badgeos-feedback-buttons">';
		$output .= '<a href="#" class="button approve" data-feedback-id="' . absint( $feedback_id ) . '" data-action="approve">' . __( 'Approve', 'badgeos' ) . '</a> ';
		$output .= '<a href="#" class="button deny" data-feedback-id="' . absint( $feedback_id ) . '" data-action="denied">' . __( 'Deny', 'badgeos' ) . '</a>';
		$output .= wp_nonce_field( 'review_feedback', 'badgeos_feedback_review', true, false );
		$output .= '<input type="hidden" name="user_id" value="' . absint( $user_id ) . '">';
		$output .= '<input type="hidden" name="feedback_type" value="' . esc_attr( $feedback_type ) . '">';
		$output .= '<input type="hidden" name="achievement_id" value="' . absint( $achievement_id ) . '">';
	$output .= '</p>';

	// Enqueue and localize our JS
	$atts['ajax_url'] = esc_url( admin_url( 'admin-ajax.php', 'relative' ) );
	$atts['user_id']  = $user_ID;
	wp_enqueue_script( 'badgeos-achievements' );
	wp_localize_script( 'badgeos-achievements', 'badgeos_feedback_buttons', $atts );

	// Return our filterable output
	return apply_filters( 'badgeos_render_feedback_buttons', $output, $feedback_id );
}

/*
 * Get current page post id
 *
 * @param null
 * @return integer
 */
function badgeos_get_current_page_post_id() {

	global $posts;

	$current_post_id = null;

	foreach($posts as $post){
		if($post->post_type != 'page') {
			//Get current page achievement id
			$current_post_id = $post->ID;
		}
	}

	//Return current post id
    return $current_post_id;
}


/**
 * Hide the hidden achievement post link from next post link
 *
 * @param $link
 * @return string
 */
function badgeos_hide_next_hidden_achievement_link($link) {

	if($link) {

		//Get current achievement id
		$achievement_id = badgeos_get_current_page_post_id();

		//Get post link , without hidden achievement
		$link = badgeos_get_post_link_without_hidden_achievement($achievement_id, 'next');

	}

	return $link;

}
add_filter('next_post_link', 'badgeos_hide_next_hidden_achievement_link');

/**
 * Hide the hidden achievement post link from previous post link
 *
 * @param $link
 * @return string
 */
function badgeos_hide_previous_hidden_achievement_link($link) {


	if($link) {

		//Get current achievement id
		$achievement_id = badgeos_get_current_page_post_id();

		//Get post link , without hidden achievement
		$link = badgeos_get_post_link_without_hidden_achievement($achievement_id, 'prev');

	}

	return $link;

}

add_filter('previous_post_link', 'badgeos_hide_previous_hidden_achievement_link');


/*
 * Get post link without hidden achievement link
 *
 * @param $achievement_id
 * @param $rel
 * @return string
 */
function badgeos_get_post_link_without_hidden_achievement($achievement_id, $rel) {


	$link = null;

	$post = get_post($achievement_id);

	//Check the ahievement
	$achievement_id = ( badgeos_is_achievement($post) )? $post->ID : "";

	//Get next post id without hidden achievement id
	$next_post_id = badgeos_get_next_previous_achievement_id($achievement_id, $rel);

	if ($next_post_id)
		//Generate post link
		$link = badgeos_generate_post_link_by_post_id($next_post_id, $rel);


	return $link;

}

/**
 * Get next or previous post id , without hidden achievement id
 *
 * @param $current_achievement_id
 * @param $flag
 * @return integer
 */
function badgeos_get_next_previous_achievement_id($achievement_id , $rel ){


	$nested_post_id = null;

	$access = false;

	// Redirecting user page based on achievements
	$post = get_post( absint( $achievement_id ));

	//Get hidden achievements ids
	$hidden = badgeos_get_hidden_achievement_ids( $post->post_type );

	// Fetching achievement types
	$param = array(
		'posts_per_page'   => -1, // All achievements
		'offset'           => 0,  // Start from first achievement
		'post_type'=> $post->post_type, // set post type as achievement to filter only achievements
		'orderby' => 'ID',
		'order' => 'ASC',
	);

	$param['order'] = ($rel == 'next') ? 'ASC' : 'DESC';


	$achievement_types = get_posts($param);

	foreach ($achievement_types as $achievement){

		$check = false;

		//Compare next achievement
		if($achievement->ID > $achievement_id && $rel == 'next') {
			$check = true;
		}

		//Compare previous achievement
		if($achievement->ID < $achievement_id && $rel == 'prev') {
			$check = true;
		}

		if($check){
			//Checks achievement in hidden badges
			if (in_array($achievement->ID, $hidden)) {
				continue;
			} else {
				$access = true;
			}
		}

		if($access) {
			//Get next or previous achievement without hidden badges
			if (!in_array($achievement->ID, $hidden) && !$nested_post_id) {
				$nested_post_id = $achievement->ID;
			}
		}
	}

	//rerurn next or previous achievement without hidden badge id
	return $nested_post_id;

}

/**
 * Generate the post link based on custom post object
 *
 * @param $link
 * @return string
 */
function badgeos_generate_post_link_by_post_id( $post_id , $rel) {

	global $post;

	if(!empty($post_id))
		$post = get_post($post_id);

    //Title of the post
	$title = get_the_title( $post->ID );

	if ( empty( $post->post_title ) && $rel == 'next')
		$title = __( 'Next Post' );

	if ( empty( $post->post_title ) && $rel == 'prev')
		$title = __( 'Previous Post' );


	$rel =  ($rel == 'prev') ? 'prev' : 'next';

	$nav_prev = ($rel == 'prev') ? '<span class="meta-nav">←</span> ' : '';
	$nav_next = ($rel == 'next') ? ' <span class="meta-nav">→</span>' : '';

	//Build link
	$link = '<a href="' . get_permalink( $post ) . '" rel="'.$rel.'">' . $nav_prev . $title . $nav_next. '</a>';

	return $link;

}