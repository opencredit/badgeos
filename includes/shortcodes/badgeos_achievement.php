<?php

$badgeos_achievement_shortcode = badgeos_register_shortcode( array(
	'name' => __( 'BadgeOS Achievement', 'badgeos' ),
	'slug' => 'badgeos_achievement',
	'description' => __( 'Render a single achievement', 'badgeos' ),
	'attributes' => array(
		'id' => array(
			'name' => __( 'ID', 'badgeos' ),
			'type' => 'string',
			'default' => __( 'Current achievement ID', 'badgeos' )
			),
	),
	'output_callback' => 'badgeos_achievement_shortcode'
) );
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
