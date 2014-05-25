<?php

/**
 * Register the [badgeos_nominations] shortcode.
 *
 * @since 1.4.0
 */
function badgeos_register_nominations_list_shortcode() {
	badgeos_register_shortcode( array(
		'name'            => __( 'Nominations List', 'badgeos' ),
		'description'     => __( 'Display a filterable list of nominations.', 'badgeos' ),
		'slug'            => 'badgeos_nominations',
		'output_callback' => 'badgeos_display_nominations',
		'attributes'      => array(
			'limit' => array(
				'name'        => __( 'Limit', 'badgeos' ),
				'description' => __( 'Number of nominations to display.', 'badgeos' ),
				'type'        => 'text',
				'default'     => '10',
				),
			'status' => array(
				'name'        => __( 'Status', 'badgeos' ),
				'description' => __( 'Nomination statuses to display.', 'badgeos' ),
				'type'        => 'select',
				'values'      => array(
					'all'      => __( 'All', 'badgeos' ),
					'approved' => __( 'Approved', 'badgeos' ),
					'denied'   => __( 'Denied', 'badgeos' ),
					'pending'  => __( 'Pending', 'badgeos' ),
					),
				'default'     => 'all'
				),
			'show_filter' => array(
				'name'        => __( 'Show Filter', 'badgeos' ),
				'description' => __( 'Display filter controls.', 'badgeos' ),
				'type'        => 'select',
				'values'      => array(
					'true'  => __( 'True', 'badgeos' ),
					'false' => __( 'False', 'badgeos' )
					),
				'default'     => 'true'
				),
			'show_search' => array(
				'name'        => __( 'Show Search', 'badgeos' ),
				'description' => __( 'Display a search input.', 'badgeos' ),
				'type'        => 'select',
				'values'      => array(
					'true'  => __( 'True', 'badgeos' ),
					'false' => __( 'False', 'badgeos' )
					),
				'default'     => 'true'
				),
		),
	) );
}
add_action( 'init', 'badgeos_register_nominations_list_shortcode' );

/**
 * Nominations List Shortcode.
 *
 * @since  1.1.0
 *
 * @param  array $atts Shortcode attributes.
 * @return string 	   HTML markup.
 */
function badgeos_display_nominations( $atts = array() ) {

	$defaults = array(
		'type'             => 'nomination',
		'show_attachments' => false,
		'show_comments'    => false
	);

	$atts = wp_parse_args( $defaults, $atts );

	// Call submissions shortcode handler
	return badgeos_shortcode_submissions_handler( $atts, 'badgeos_nominations' );

}
