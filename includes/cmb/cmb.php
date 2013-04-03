<?php
/*
Plugin Name: Custom Metaboxes and Fields for WordPress
Plugin URI: http://webdevstudios.com/
Description: Custom class for registering metaboxes and storing metadata in WordPress
Author: WebDevStudios.com
Version: 1.0
Author URI: http://webdevstudios.com/
*/

if ( ! class_exists( 'cmb_Meta_Box' ) )
	require_once( plugin_dir_path(__FILE__) .'init.php' );