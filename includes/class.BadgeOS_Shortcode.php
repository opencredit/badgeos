<?php

class BadgeOS_Shortcode {

	public $name            = '';
	public $description     = '';
	public $slug            = '';
	public $output_callback = '';
	public $attributes      = array();

	public function __construct( $_args = array() ) {

		// Setup this shortcode's properties
		$this->_set_properties( $_args );

		// Register this shortcode with WP and BadgeOS
		add_shortcode( $this->slug, $this->output_callback );
		add_filter( 'badgeos_shortcodes', array( $this, 'register_shortcode' ) );
	}

	private function _set_properties( $_args = array() ) {

		$defaults = array(
			'name'            => '',
			'description'     => '',
			'slug'            => '',
			'output_callback' => '',
			'attributes'      => array(),
		);
		$args = wp_parse_args( $_args, $defaults );

		$this->name            = $args['name'];
		$this->description     = $args['description'];
		$this->slug            = $args['slug'];
		$this->output_callback = $args['output_callback'];
		$this->attributes      = $this->_set_attribute_defaults( $args['attributes'] );
	}

	private function _set_attribute_defaults( $attributes = array() ) {
		foreach ( $attributes as $attribute => $details ) {
			$attr_defaults = array(
				'name'        => '',
				'type'        => '',
				'description' => '',
				'values'      => '',
				'default'     => '',
			);
			$attributes[ $attribute ] = wp_parse_args( $details, $attr_defaults );
		}
		return $attributes;
	}

	public function register_shortcode( $shortcodes = array() ) {
		$shortcodes[ $this->slug ] = $this;
		return $shortcodes;
	}

}
