<?php

/**
 * Register the [badgeos_achievement] shortcode.
 *
 * @since 1.4.0
 */
function badgeos_register_achievement_shortcode() {

    $achievements = badgeos_get_achievements_id_title_pair();
	badgeos_register_shortcode( array(
		'name'            => __( 'Single Achievement', 'badgeos' ),
		'slug'            => 'badgeos_achievement',
		'output_callback' => 'badgeos_achievement_shortcode',
		'description'     => __( 'Render a single achievement.', 'badgeos' ),
		'attributes'      => array(
			'id' => array(
				'name'        => __( 'Achievement ID', 'badgeos' ),
				'description' => __( 'The ID of the achievement to render.', 'badgeos' ),
                'type'        => 'select',
                'values'      => $achievements,
                'default'     => '',
            ),
            'show_title' => array (
                'name'        => __( 'Show Title', 'badgeos' ),
                'description' => __( 'Display Achievement Title.', 'badgeos' ),
                'type'        => 'select',
                'values'      => array (
                    'true'  => __( 'True', 'badgeos' ),
                    'false' => __( 'False', 'badgeos' )
                ),
                'default'     => 'true',
            ),
            'show_thumb' => array (
                'name'        => __( 'Show Thumbnail', 'badgeos' ),
                'description' => __( 'Display Thumbnail Image.', 'badgeos' ),
                'type'        => 'select',
                'values'      => array(
                    'true'  => __( 'True', 'badgeos' ),
                    'false' => __( 'False', 'badgeos' )
                ),
                'default'     => 'true',
            ),
            'show_description' => array (
                'name'        => __( 'Show Description', 'badgeos' ),
                'description' => __( 'Display Short Description.', 'badgeos' ),
                'type'        => 'select',
                'values'      => array(
                    'true'  => __( 'True', 'badgeos' ),
                    'false' => __( 'False', 'badgeos' )
                ),
                'default'     => 'true',
            ),
            'show_steps' => array (
                'name'        => __( 'Show Steps', 'badgeos' ),
                'description' => __( 'Display Steps after the Description.', 'badgeos' ),
                'type'        => 'select',
                'values'      => array(
                    'true'  => __( 'True', 'badgeos' ),
                    'false' => __( 'False', 'badgeos' )
                ),
                'default'     => 'true',
            ),
            'image_width' => array (
                'name'        => __( 'Thumnail Width', 'badgeos' ),
                'description' => __( "Achievement's image width.", 'badgeos' ),
                'type'        => 'text',
                'default'     => '',
            ),
            'image_height' => array (
                'name'        => __( 'Thumnail Height', 'badgeos' ),
                'description' => __( "Achievement's image height.", 'badgeos' ),
                'type'        => 'text',
                'default'     => '',
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
    global $wpdb;
	// get the post id
	$atts = shortcode_atts( array(
        'id' => '',
        'show_title'  => 'true',
        'show_thumb'  => 'true',
        'show_description'  => 'true',
        'show_steps'  => 'true',
        'image_width' => '',
        'image_height' => '',
    ), $atts, 'badgeos_achievement' );

    // return if post id not specified
    $settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
	if ( empty($atts['id']) ) {
        $achievements = $wpdb->get_results( $wpdb->prepare( "select * from ".$wpdb->prefix."badgeos_achievements where post_type!=%s and user_id=%d order by actual_date_earned desc limit 1", trim( $settings['achievement_step_post_type'] ), get_current_user_id() ) );
        if( count( $achievements ) > 0 ) {
            $atts['id'] = $achievements[0]->ID;
        }
    }

	// return if post id not specified
	if ( empty($atts['id']) )
	  return;

	wp_enqueue_style( 'badgeos-front' );
	wp_enqueue_script( 'badgeos-achievements' );

	// get the post content and format the badge display
	$achievement = badgeos_utilities::badgeos_get_post( $atts['id'] );
	$output = '';

	// If we're dealing with an achievement post
	if ( badgeos_is_achievement( $achievement ) ) {
		$output .= '<div id="badgeos-single-achievement-container-'.$atts['id'].'" class="badgeos-single-achievement badgeos-single-achievement-'.$atts['id'].'">';  // necessary for the jquery click handler to be called
        $output .= badgeos_render_achievement( $achievement, $atts['show_title'], $atts['show_thumb'], $atts['show_description'], $atts['show_steps'], $atts['image_width'], $atts['image_height'] );
		$output .= '</div>';
	}

	// Return our rendered achievement
	return $output;
}
