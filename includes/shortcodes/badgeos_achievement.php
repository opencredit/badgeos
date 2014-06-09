<?php

/**
 * Register the [badgeos_achievement] shortcode.
 *
 * @since 1.4.0
 */
function badgeos_register_achievement_shortcode() {
	badgeos_register_shortcode( array(
		'name'            => __( 'Single Achievement', 'badgeos' ),
		'slug'            => 'badgeos_achievement',
		'output_callback' => 'badgeos_achievement_shortcode',
		'description'     => __( 'Render a single achievement.', 'badgeos' ),
		'attributes'      => array(
			'id' => array(
				'name'        => __( 'Achievement ID', 'badgeos' ),
				'description' => __( 'The ID of the achievement to render.', 'badgeos' ),
				'type'        => 'text',
				),
		),
	) );
}
add_action( 'init', 'badgeos_register_achievement_shortcode' );

/**
 * Single Achievement Shortcode.
 *
 * @since  1.0.0
 *
 * @param  array $atts Shortcode attributes.
 * @return string 	   HTML markup.
 */
function badgeos_achievement_shortcode( $atts = array() ) {

	// get the post id
	$atts = shortcode_atts( array(
	  'id' => get_the_ID(),
	), $atts, 'badgeos_achievement' );

	// return if post id not specified
	if ( empty($atts['id']) )
	  return;

	wp_enqueue_style( 'badgeos-front' );
	wp_enqueue_script( 'badgeos-achievements' );

	// get the post content and format the badge display
	$achievement = get_post( $atts['id'] );
	$output = '';

	// If we're dealing with an achievement post
	if ( badgeos_is_achievement( $achievement ) ) {
		$output .= '<div id="badgeos-achievements-container" class="badgeos-single-achievement">';  // necessary for the jquery click handler to be called
		$output .= badgeos_render_achievement( $achievement );
		$output .= '</div>';
	}

	// Return our rendered achievement
	return $output;
}
