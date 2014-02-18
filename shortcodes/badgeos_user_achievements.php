<?php

$badgeos_user_achievements_shortcode = badgeos_register_shortcode( array(
	'name' => __( 'BadgeOS User Achievements List', 'badgeos' ),
	'slug' => 'badgeos_user_achievements',
	'description' => __( 'Display earned achievements for a given user', 'badgeos' ),
	'attributes' => array(
		'user' => array(
			'name' => __( 'User', 'badgeos' ),
			'type' => 'string',
			'default' => __( 'Current user', 'badgeos' )
			),
		'type' => array(
			'name' => __( 'Type', 'badgeos' ),
			'type' => 'string',
			'default' => __( 'submission', 'badgeos' )
			),
		'limit' => array(
			'name' => __( 'Limit', 'badgeos' ),
			'type' => 'string',
			'default' => '10'
			),
	),
	'output_callback' => 'badgeos_user_achievements_shortcode'
) );
/**
 * Display earned achievements for a given user
 *
 * @param  array  $atts Our attributes array
 * @return string       Concatenated markup
 */
function badgeos_user_achievements_shortcode( $atts = array() ) {

	// Parse our attributes
	$atts = shortcode_atts( array(
		'user' => get_current_user_id(),
		'type'    => '',
		'limit'   => 5
	), $atts );

	$output = '';

	// Grab the user's current achievements, without duplicates
	$achievements = array_unique( badgeos_get_user_earned_achievement_ids( $atts['user'], $atts['type'] ) );

	// Setup a counter
	$count = 0;

	$output .= '<div class="badgeos-user-badges-wrap">';

	// Loop through the achievements
	if ( ! empty( $achievements ) ) {
		foreach( $achievements as $achievement_id ) {

			// If we've hit our limit, quit
			if ( $count >= $atts['limit'] ) {
				break;
			}

			// Output our achievement image and title
			$output .= '<div class="badgeos-badge-wrap">';
			$output .= badgeos_get_achievement_post_thumbnail( $achievement_id );
			$output .= '<span class="badgeos-title-wrap">' . get_the_title( $achievement_id ) . '</span>';
			$output .= '</div>';

			// Increase our counter
			$count++;
		}
	}
	$output .= '</div>';

	return $output;
}
