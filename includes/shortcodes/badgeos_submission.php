<?php

/**
 * Register the [badgeos_submission] shortcode.
 *
 * @since 1.4.0
 */
function badgeos_register_submission_shortcode() {
	badgeos_register_shortcode( array(
		'name'            => __( 'Submission Form', 'badgeos' ),
		'description'     => __( 'Render a submission form.', 'badgeos' ),
		'slug'            => 'badgeos_submission',
		'output_callback' => 'badgeos_submission_form',
		'attributes'      => array(
			'achievement_id' => array(
				'name'        => __( 'Achievement ID', 'badgeos' ),
				'description' => __( 'Achievement ID to award.', 'badgeos' ),
				'type'        => 'text',
				),
		),

	) );
}
add_action( 'init', 'badgeos_register_submission_shortcode' );

/**
 * Submission Form Shortcode.
 *
 * @since  1.0.0
 *
 * @param  array $atts Shortcode attributes.
 * @return string 	   HTML markup.
 */
function badgeos_submission_form( $atts = array() ) {

	// Parse our attributes
	$atts = shortcode_atts( array(
		'achievement_id' => get_the_ID(),
	), $atts, 'badgeos_submission' );

	// Initialize output
	$output = '';

	// Verify user is logged in to view any submission data
	if ( is_user_logged_in() ) {

		// If submission data was submitted, output success message
		if ( isset( $_REQUEST['achievement_id'] ) && $_REQUEST['achievement_id'] == $atts['achievement_id'] ) {
			// Don't award if this achievement was earned in the past 30 seconds
			$recently_earned = badgeos_get_user_achievements( array( 'user_id' => get_current_user_id(), 'achievement_id' => $_REQUEST['achievement_id'], 'since' => ( time() - 30 ) , 'display' => true ) );

            global $wp;
            if ( empty( $recently_earned )) {
                if ( badgeos_save_submission_data() ) {
                    $recent_submission_id = $_SESSION['new_added_submission_id'];
                    unset($_SESSION['new_added_submission_id']);
                    wp_redirect(home_url($wp->request.'?new_submission_id='.$recent_submission_id));
				}
			}else{
                    wp_redirect(home_url($wp->request));
            }
		}

        if(!empty($_GET['new_submission_id']) && $new_submission_id = $_GET['new_submission_id']){
            $posts = get_post( absint($new_submission_id) );
            // Checking the type of submission and display the success message based on submission type
            if($posts->post_status == 'draft'){
                $output .= sprintf( '<div class="badgeos_submission_message"><p>%s</p></div>', __( 'Thank you! Your draft has been saved.', 'badgeos' ) );
            }else {
                if (get_post_meta($atts['achievement_id'], '_badgeos_earned_by', true) == 'submission') {

                    if(get_post_meta($new_submission_id , '_badgeos_submission_status', true) == 'pending')
                    $output .= sprintf('<div class="badgeos_submission_message"><p>%s</p></div>', __('Thank you! Your submission has been received.', 'badgeos'));

                }
            }
        }

		// If user has already submitted something, show their submissions
		if ( badgeos_check_if_user_has_submission( get_current_user_id(), $atts['achievement_id'] ) ) {
			$output .= badgeos_get_user_submissions( '', $atts['achievement_id'] );
		}

		// Return either the user's submission or the submission form
		if ( badgeos_user_has_access_to_submission_form( get_current_user_id(), $atts['achievement_id'] ) ) {
			$output .= badgeos_get_submission_form( array( 'user_id' => get_current_user_id(), 'achievement_id' => $atts['achievement_id'] ) );
		}

	// Logged-out users have no access
	} else {
		$output .= sprintf( '<p><em>%s</em></p>', __( 'You must be logged in to post a submission.', 'badgeos' ) );
	}

	return $output;
}
