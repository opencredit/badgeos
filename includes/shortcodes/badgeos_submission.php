<?php

$badgeos_submission_shortcode = badgeos_register_shortcode( array(
	'name' => __( 'BadgeOS Submission Form', 'badgeos' ),
	'slug' => 'badgeos_submission',
	'description' => __( 'Render a submission form', 'badgeos' ),
	'attributes' => array(
		'achievement_id' => array(
			'name' => __( 'Achievement ID', 'badgeos' ),
			'type' => 'string',
			'default' => __( 'Current achievement ID', 'badgeos' )
			),
	),
	'output_callback' => 'badgeos_submission_form'
) );

/**
 * Submission Form
 * @since 1.0.0
 * @param  array  $atts The attributes
 * @return string       The concatinated submission form markup
 */
function badgeos_submission_form( $atts = array() ) {
	global $user_ID, $post;

	// Parse our attributes
	$atts = shortcode_atts( array(
		'achievement_id' => $post->ID
	), $atts );

	// Initialize output
	$output = '';

	// Verify user is logged in to view any submission data
	if ( is_user_logged_in() ) {

		// If submission data was saved, output success message
		if ( badgeos_save_submission_data() ) {
			$output .= sprintf( '<p>%s</p>', __( 'Submission saved successfully.', 'badgeos' ) );
		}

		// If user has already submitted something, show their submissions
		if ( badgeos_check_if_user_has_submission( $user_ID, $atts['achievement_id'] ) ) {
			$output .= badgeos_get_user_submissions( '', $atts['achievement_id'] );
		}

		// Return either the user's submission or the submission form
		if ( badgeos_user_has_access_to_submission_form( $user_ID, $atts['achievement_id'] ) ) {
			$output .= badgeos_get_submission_form( array( 'user_id' => $user_ID, 'achievement_id' => $atts['achievement_id'] ) );
		}

	// Logged-out users have no access
	} else {
		$output .= sprintf( '<p><i>%s</i></p>', __( 'You must be logged in to post a submission.', 'badgeos' ) );
	}

	return $output;
}
