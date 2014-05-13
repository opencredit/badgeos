<?php

/**
 * Register the [credly_assertion_page] shortcode.
 *
 * @since 1.4.0
 */
function badgeos_register_credly_assertion_shortcode() {
	badgeos_register_shortcode( array(
		'name'            => __( 'Credly Assertion Page', 'badgeos' ),
		'description'     => sprintf( __( 'Adds support for Credly\'s "Custom Assertion Location" feature, available to Credly Pro members only. After placing this shortcode on a page, copy that page\'s URL and append "?CID={id}" to the end (e.g. %1$s). Paste this full URL in the "Custom Assertion Location" field in your Credly Account Settings. All of your Credly badges will be linked back to this site where the official badge information is displayed automatically.', 'badgeos' ), '<code>' . site_url( '/assertion/?CID={id}' ) . '</code>' ),
		'slug'            => 'credly_assertion_page',
		'output_callback' => 'badgeos_credly_assertion_page',
		'attributes'      => array(
			'width' => array(
				'name'        => __( 'Width', 'badgeos' ),
				'description' => __( 'Content width.', 'badgeos' ),
				'type'        => 'text',
				'default'     => 560,
				),
			'height' => array(
				'name'        => __( 'Height', 'badgeos' ),
				'description' => __( 'Content height.', 'badgeos' ),
				'type'        => 'text',
				'default'     => 1000,
				),
			'CID' => array(
				'name'        => __( 'Credly Assertion ID', 'badgeos' ),
				'description' => __( 'Optional Credly Badge ID to display instead of dynamically passed ID.', 'badgeos' ),
				'type'        => 'text',
				),
		),
	) );
}
add_action( 'init', 'badgeos_register_credly_assertion_shortcode' );

/**
 * Credly Assertion Page Shortcode.
 *
 * @since  1.3.0
 *
 * @param  array $atts Shortcode attributes.
 * @return string 	   HTML markup.
 */
function badgeos_credly_assertion_page( $atts = array() ) {
	global $content_width;

	$atts = shortcode_atts( array(
		'CID'    => isset( $_GET['CID'] ) ? absint( $_GET['CID'] ) : 0,
		'width'  => isset( $content_width ) ? $content_width : 560,
		'height' => 1000,
	), $atts, 'credly_assertion_page' );

	if ( absint( $atts['CID'] ) ) {
		return '<iframe class="credly-assertion" src="http://credly.com/credit/' . absint( $atts['CID'] ) . '/embed" align="top" marginwidth="0" width="' . absint( $atts['width'] ) . 'px" height="' . absint( $atts['height'] ) . 'px" scrolling="no" frameborder="no"></iframe>';
	} else {
		return '';
	}
}
