<?php

/**
 * Register the [badgeos_submissions] shortcode.
 *
 * @since 1.4.0
 */
function badgeos_register_submissions_list_shortcode() {
	badgeos_register_shortcode( array(
		'name'            => __( 'Submissions List', 'badgeos' ),
		'description'     => __( 'Display a filterable list of submissions', 'badgeos' ),
		'slug'            => 'badgeos_submissions',
		'output_callback' => 'badgeos_display_submissions',
		'attributes'      => array(
			'limit' => array(
				'name'        => __( 'Limit', 'badgeos' ),
				'description' => __( 'Number of submissions to display.', 'badgeos' ),
				'type'        => 'text',
				'default'     => 10,
				),
			'status' => array(
				'name'        => __( 'Status to Display', 'badgeos' ),
				'description' => __( 'Display only submissions of this status.', 'badgeos' ),
				'type'        => 'select',
				'values'      => array(
					'all'      => __( 'All', 'badgeos' ),
					'approved' => __( 'Approved', 'badgeos' ),
					'denied'   => __( 'Denied', 'badgeos' ),
					'pending'  => __( 'Pending', 'badgeos' ),
					),
				'default'     => 'all',
				),
			'show_filter' => array(
				'name'        => __( 'Show Filter', 'badgeos' ),
				'description' => __( 'Display filter controls.', 'badgeos' ),
				'type'        => 'select',
				'values'      => array(
					'true'  => __( 'True', 'badgeos' ),
					'false' => __( 'False', 'badgeos' )
					),
				'default'     => 'true',
				),
			'show_search' => array(
				'name'        => __( 'Show Search', 'badgeos' ),
				'description' => __( 'Display a search input.', 'badgeos' ),
				'type'        => 'select',
				'values'      => array(
					'true'  => __( 'True', 'badgeos' ),
					'false' => __( 'False', 'badgeos' )
					),
				'default'     => 'true',
				),
			'show_attachments' => array(
				'name'        => __( 'Show attachments', 'badgeos' ),
				'description' => __( 'Display submission attachments.', 'badgeos' ),
				'type'        => 'select',
				'values'      => array(
					'true'  => __( 'True', 'badgeos' ),
					'false' => __( 'False', 'badgeos' )
					),
				'default'     => 'true',
				),
			'show_comments' => array(
				'name'        => __( 'Show comments', 'badgeos' ),
				'description' => __( 'Display submission comments.', 'badgeos' ),
				'type'        => 'select',
				'values'      => array(
					'true'  => __( 'True', 'badgeos' ),
					'false' => __( 'False', 'badgeos' )
					),
				'default'     => 'true',
				),
		),
	) );
}
add_action( 'init', 'badgeos_register_submissions_list_shortcode');

/**
 * Submissions List Shortcode.
 *
 * @since  1.1.0
 *
 * @param  array $atts Shortcode attributes.
 * @return string 	   HTML markup.
 */
function badgeos_display_submissions( $atts = array() ) {

	$atts = shortcode_atts( array(
		'type'             => 'submission',
		'limit'            => '10',
		'status'           => 'all',
		'show_filter'      => true,
		'show_search'      => true,
		'show_attachments' => true,
		'show_comments'    => true
	), $atts, 'badgeos_submissions' );

	$atts['ajax_url'] = admin_url( 'admin-ajax.php', 'relative' );
	$atts['user_id']  = get_current_user_id();
	wp_enqueue_script( 'badgeos-achievements' );
	wp_localize_script( 'badgeos-achievements', 'badgeos_feedback', $atts );

	$feedback = badgeos_render_feedback( $atts );

	return $feedback;
}
