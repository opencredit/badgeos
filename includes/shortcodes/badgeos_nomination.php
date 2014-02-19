<?php

$badgeos_nomination_shortcode = badgeos_register_shortcode( array(
	'name' => __( 'BadgeOS Nomination Form', 'badgeos' ),
	'slug' => 'badgeos_nomination',
	'description' => __( 'Render a nomination form', 'badgeos' ),
	'attributes' => array(
		'achievement_id' => array(
			'name' => __( 'Achievement ID', 'badgeos' ),
			'type' => 'string',
			'description' => __( 'Achievement ID to create a nomination form for. Default: current achievement ID', 'badgeos' )
			),
	),
	'output_callback' => 'badgeos_nomination_form'
) );

/**
 * Display nomination form for awarding an achievemen
 * @since 1.0.0
 * @param  array  $atts The attributions for the meta box
 * @return string       The concatinated markup
 */
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
