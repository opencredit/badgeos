<?php

$credly_assertion_shortcode = badgeos_register_shortcode( array(
	'name' => __( 'Credly Assertion Page', 'badgeos' ),
	'slug' => 'credly_assertion_page',
	'description' => __( 'Shortcode for rendering Credly Assertion page content.', 'badgeos' ),
	'attributes' => array(
		'CID' => array(
			'name' => __( 'CID', 'badgeos' ),
			'type' => 'string',
			'description' => __( 'Credly assertion ID to display. Default: CID $_GET parameter or 0', 'badgeos' )
			),
		'width' => array(
			'name' => __( 'Width', 'badgeos' ),
			'type' => 'string',
			'description' => __( 'Width to display the rendered shortcode at. Default: $content_width value if set or 560', 'badgeos' )
			),
		'height' => array(
			'name' => __( 'Height', 'badgeos' ),
			'type' => 'string',
			'description' => __( 'Height to display the rendered shortcode. Default: 1000', 'badgeos' )
			),
	),
	'output_callback' => 'badgeos_credly_assertion_page'
) );

/**
 * Shortcode for rendering Credly Assertion page content.
 *
 * @since  1.3.0
 *
 * @param  array $atts Attributes passed via shortcode
 * @return string      iframe displaying Credly data, or nothing.
 */
function badgeos_credly_assertion_page( $atts = array() ) {
	global $content_width;

	// Setup defaults
	$defaults = array(
		'CID'    => isset( $_GET['CID'] ) ? absint( $_GET['CID'] ) : 0,
		'width'  => isset( $content_width ) ? $content_width : 560,
		'height' => 1000,
	);

	// Parse attributes against the defaults
	$atts = shortcode_atts( $defaults, $atts );

	// If passed an ID, render the iframe, otherwise render nothing
	if ( absint( $atts['CID'] ) )
		return '<iframe class="credly-assertion" src="http://credly.com/credit/' . absint( $atts['CID'] ) . '/embed" align="top" marginwidth="0" width="' . absint( $atts['width'] ) . 'px" height="' . absint( $atts['height'] ) . 'px" scrolling="no" frameborder="no"></iframe>';
	else
		return '';

}
