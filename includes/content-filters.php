<?php
/**
 * Content Filters
 *
 * @package BadgeOS
 * @subpackage Front-end
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

add_filter( 'the_content', 'badgeos_achievement_submissions' );

/**
 * Displays the submission form on achievement type single pages if the meta option is enabled
 *
 *
 *
 */
function badgeos_achievement_submissions( $content ) {
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

			$submission = do_shortcode( '[badgeos_submission]' );

			//return the content with the submission shortcode data
			return $content . $submission;

		}elseif ( $earned_by == 'nomination' && is_user_logged_in() ) {

			$nomination = do_shortcode( '[badgeos_nomination]' );

			//return the content with the nomination shortcode data
			return $content . $nomination;

		}

	}

	return $content;

}

// add_filter( 'the_content', 'badgeos_steps_single' );

// Step single page filter
function badgeos_steps_single( $content ) {
	global $post, $current_user;

	if ( get_post_type( $post ) == 'step' && is_single() ) {
		//load badge unlock options
		$badgeos_unlock_options = get_post_meta( absint( $post->ID ), '_badgeos_step_unlock_options', true );

		//check if step unlock option is set to submission review
		if ( $badgeos_unlock_options == 'submission-review' ) {
			get_currentuserinfo();

			//check if user already has a submission for this achievement type
			if ( !badgeos_check_if_user_has_submission( $current_user->ID, $post->ID ) ) {
				//load step metadata for single step pages
				$badgeos_step_description = get_post_meta( $post->ID, '_badgeos_step_description', true );
				$badgeos_completing_step_means  = get_post_meta( $post->ID, '_badgeos_completing_step_means', true );
				$badgeos_submission_instructions = get_post_meta( $post->ID, '_badgeos_submission_instructions', true );
				$badgeos_discuss_after = get_post_meta( $post->ID, '_badgeos_discuss_after', true );
				$badgeos_discussforum_prompt     = get_post_meta( $post->ID, '_badgeos_discussforum_prompt', true );
				$badgeos_learn_even_more = get_post_meta( $post->ID, '_badgeos_learn_even_more', true );

				$badgeos_step_color = get_post_meta( $post->ID, '_badgeos_step_color', true );
				$badgeos_step_color = ( $badgeos_step_color ) ? $badgeos_step_color : '#';

				$new_content = null;

				// Step Description metadata
				if ( $badgeos_step_description ) {
					$new_content .= '<p><strong>Step Description</strong><br />';
					$new_content .= $badgeos_step_description;
					$new_content .= '</p>';
				}

				//load submission form
				$submission_form = badgeos_get_submission_form( $post->ID );
				$new_content    .= $new_content .$submission_form;

				$content = $content . $new_content;
			} else {
				//user has an active submission, so show content and comments

				$args = array(
					'post_type'			=>	'submission',
					'author'			=>	$current_user->ID,
					'post_status'	=>	'publish',
					'meta_key'		=>	'_badgeos_submission_achievement_id',
					'meta_value'	=>	absint( $post->ID ),
				);

				$submissions = get_posts( $args );

				foreach( $submissions as $post ) :	setup_postdata( $post );

					echo '<p>';

					echo '<strong>' .__( 'Original Submission', 'badgeos' ). ':</strong><br />';
					echo get_the_content() .'<br />';

					echo '<strong>' .__( 'Date', 'badgeos' ). ':</strong>&nbsp;&nbsp;';
					echo get_the_date() .'<br />';

					echo '<strong>' .__( 'Status', 'badgeos' ). ':</strong>&nbsp;&nbsp;';
					echo get_post_meta( get_the_ID(), '_badgeos_submission_status', true );

					echo '</p>';

					echo '<p>';
					echo '<strong>Submission Comments</strong>';

					//display any comments that exist
					badgeos_get_comments( $post->ID );

					//display a form to add new comments
					echo badgeos_get_comment_form( $post->ID );

					echo '</p>';

				endforeach;
			}
		}
	} elseif ( get_post_type( $post ) == 'badge' && is_single() ) {
		$new_content = null;

		//load badge unlock options
		$badgeos_unlock_options = get_post_meta( absint( $post->ID ), '_badgeos_badge_unlock_options', true );

		//check if badge unlock option is set to reward/nomination
		if ( $badgeos_unlock_options == 'giving' ) {
			//load nomination form
			$submission_form = badgeos_get_submission_form( $post->ID, 'nomination' );
			$new_content .= $new_content . $submission_form;
			$content     = $content . $new_content;
		}
	}

	return $content;

}

add_action( 'wp_enqueue_scripts', 'badgeos_do_single_filters' );
/**
 * Add filters to remove stuff from our singular pages and add back in how we want it
 */
function badgeos_do_single_filters() {
	// check we're in the right place
	badgeos_is_main_loop();
	// enqueue our stylesheet
	wp_enqueue_style( 'badgeos-single' );
	// no worries.. we'll add back later
	remove_filter( 'the_content', 'wpautop' );
	// filter out the post title
	// add_filter( 'the_title', 'badgeos_remove_to_reformat_entries_title', 10 ,2 );
	// and filter out the post image
	add_filter( 'post_thumbnail_html', 'badgeos_remove_to_reformat_entries_title', 10, 2 );
}

/**
 * Filter out the post title/post image and add back (later) how we want it
 */
function badgeos_remove_to_reformat_entries_title( $html, $id ) {

	// remove, but only on the main loop!
	if ( badgeos_is_main_loop( $id ) )
		return '';

	// nothing to see here... move along
	return $html;
}

add_filter( 'the_content', 'badgeos_reformat_entries', 9 );
/**
 * Filter badge content to add our removed content back
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

/**
 * helper function tests that we're in the main loop
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
 * @param  array   $steps 	 An achievement's required steps
 * @param  integer $user_id A given user's ID
 * @return string           The markup for our list
 */
function badgeos_get_required_achievements_for_achievement_list_markup( $steps, $achievement_id = 0, $user_id = 0 ) {

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
function badgeos_step_link_title_to_achievement( $title, $step ) {

	// Grab our step requirements
	$step_requirements = badgeos_get_step_requirements( $step->ID );

	// Setup a URL to link to a specific achievement or an achievement type
	if ( ! empty( $step_requirements['achievement_post'] ) )
		$url = get_permalink( $step_requirements['achievement_post'] );
	// elseif ( ! empty( $step_requirements['achievement_type'] ) )
	// 	$url = get_post_type_archive_link( $step_requirements['achievement_type'] );

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

add_filter( 'post_class', 'badgeos_add_earned_class_single' );
/**
 * Adds "earned"/"not earned" post_class based on viewer's status
 * @param  array $classes Post classes
 * @return array          Updated post classes
 */
function badgeos_add_earned_class_single( $classes ) {
	global $user_ID;

	// check if current user has earned the achievement they're viewing
	$classes[] = badgeos_get_user_achievements( array( 'user_id' => $user_ID, 'achievement_id' => get_the_ID() ) ) ? 'user-has-earned' : 'user-has-not-earned';

	return $classes;
}
