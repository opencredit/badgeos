<?php

class BadgeOS_Shortcode {

	public $name            = '';
	public $slug            = '';
	public $description     = '';
	public $attributes      = array();
	public $output_callback = '';

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
			'slug'            => '',
			'description'     => '',
			'attributes'      => array(),
			'output_callback' => '',
		);
		$args = wp_parse_args( $_args, $defaults );

		$this->name = $args['name'];
		$this->slug = $args['slug'];
		$this->description = $args['description'];
		$this->attributes = $args['attributes'];
		$this->output_callback = $args['output_callback'];
	}

	public function register_shortcode( $shortcodes = array() ) {
		$shortcodes[ $this->slug ] = $this;
		return $shortcodes;
	}

	public function get_name() {
		return $this->name;
	}

	public function get_slug() {
		return $this->slug;
	}

	public function get_description() {
		return $this->description;
	}

	public function get_attributes() {
		return $this->attributes;
	}

	public function get_output_callback() {
		return $this->output_callback;
	}

}

function badgeos_register_shortcode( $args ) {
	return new BadgeOS_Shortcode( $args );
}

