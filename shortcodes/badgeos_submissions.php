<?php

$badgeos_submissions_list_shortcode = badgeos_register_shortcode( array(
	'name' => __( 'BadgeOS Submissions List', 'badgeos' ),
	'slug' => 'badgeos_submissions',
	'description' => __( 'Display a filterable list of submissions', 'badgeos' ),
	'attributes' => array(
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
		'status' => array(
			'name' => __( 'Status', 'badgeos' ),
			'type' => 'string',
			'default' => __( 'all', 'badgeos' )
			),
		'show_filter' => array(
			'name' => __( 'Show Filter', 'badgeos' ),
			'type' => 'boolean',
			'default' => __( 'true', 'badgeos' )
			),
		'show_search' => array(
			'name' => __( 'Show Search', 'badgeos' ),
			'type' => 'boolean',
			'default' => __( 'true', 'badgeos' )
			),
		'show_attachments' => array(
			'name' => __( 'Show attachments', 'badgeos' ),
			'type' => 'boolean',
			'default' => __( 'true', 'badgeos' )
			),
		'show_comments' => array(
			'name' => __( 'Show comments', 'badgeos' ),
			'type' => 'boolean',
			'default' => __( 'true', 'badgeos' )
			),
	),
	'output_callback' => 'badgeos_display_submissions'
) );

/**
 * Shortcode to display a filterable list of Submissions
 *
 * @since  1.1.0
 * @param  array  $atts Attributes passed via shortcode
 * @return string       Concatenated output
 */
function badgeos_display_submissions( $atts = array() ) {
	global $user_ID;

	// Parse our attributes
	$atts = shortcode_atts( array(
		'type'             => 'submission',
		'limit'            => '10',
		'status'           => 'all',
		'show_filter'      => true,
		'show_search'      => true,
		'show_attachments' => true,
		'show_comments'    => true
	), $atts );

	$feedback = badgeos_render_feedback( $atts );

	// Enqueue and localize our JS
	$atts['ajax_url'] = esc_url( admin_url( 'admin-ajax.php', 'relative' ) );
	$atts['user_id']  = $user_ID;
	wp_enqueue_script( 'badgeos-achievements' );
	wp_localize_script( 'badgeos-achievements', 'badgeos_feedback', $atts );

	// Return our rendered content
	return $feedback;

}
