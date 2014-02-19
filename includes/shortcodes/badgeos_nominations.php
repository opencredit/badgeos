<?php

$badgeos_nominations_list_shortcode = badgeos_register_shortcode( array(
	'name' => __( 'BadgeOS Nominations List', 'badgeos' ),
	'slug' => 'badgeos_nominations',
	'description' => __( 'Display a filterable list of nominations', 'badgeos' ),
	'attributes' => array(
		'type' => array(
			'name' => __( 'Achievement type', 'badgeos' ),
			'type' => 'string',
			'description' => __( 'Achievement type to list nominations for. Default: nomination', 'badgeos' )
			),
		'limit' => array(
			'name' => __( 'Limit', 'badgeos' ),
			'type' => 'integery',
			'description' => __( 'How many nominations to list at a time. Default: 10', 'badgeos' )
			),
		'status' => array(
			'name' => __( 'Status', 'badgeos' ),
			'type' => 'string',
			'description' => __( 'Achievement status to query for. Default: all', 'badgeos' )
			),
		'show_filter' => array(
			'name' => __( 'Show Filter', 'badgeos' ),
			'type' => 'boolean',
			'description' => __( 'Whether or not to render filter controls. Default: true', 'badgeos' )
			),
		'show_search' => array(
			'name' => __( 'Show Search', 'badgeos' ),
			'type' => 'boolean',
			'description' => __( 'Whether or not to render search controls. Default: true', 'badgeos' )
			),
		'show_attachments' => array(
			'name' => __( 'Show attachments', 'badgeos' ),
			'type' => 'boolean',
			'description' => __( 'Whether or not to display submitted attachments. Default: true', 'badgeos' )
			),
		'show_comments' => array(
			'name' => __( 'Show comments', 'badgeos' ),
			'type' => 'boolean',
			'description' => __( 'Whether or not to display submitted comments. Default: true', 'badgeos' )
			),
	),
	'output_callback' => 'badgeos_display_nominations'
) );

/**
 * Shortcode to display a filterable list of Nominations
 *
 * @since  1.1.0
 * @param  array  $atts Attributes passed via shortcode
 * @return string       Concatenated output
 */
function badgeos_display_nominations( $atts = array() ) {
	global $user_ID;

	// Parse our attributes
	$atts = shortcode_atts( array(
		'type'             => 'nomination',
		'limit'            => '10',
		'status'           => 'all',
		'show_filter'      => true,
		'show_search'      => true,
		'show_attachments' => false,
		'show_comments'    => false
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
