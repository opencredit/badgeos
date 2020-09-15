<?php
/**
 * Point Post Types
 *
 * @package Badgeos
 * @subpackage Point
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */


/**
 * Register all of our Point CPTs
 *
 * @return void
 */
function badgeos_register_points_post_types() {
	global $badgeos;
	
	$settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
	if ( !empty( $settings ) ) {
		$minimum_role = badgeos_get_manager_capability();

		/**
         * Register Point Type
         */
		register_post_type( trim( $settings['points_main_post_type'] ), array(
			'labels' => array(
				'name'               	=> __( 'Point Types', 'badgeos' ),
				'singular_name'      	=> __( 'Point Type', 'badgeos' ),
				'add_new'            	=> __( 'Add New', 'badgeos' ),
				'add_new_item'       	=> __( 'Add New Point Type', 'badgeos' ),
				'edit_item'          	=> __( 'Edit Point Type', 'badgeos' ),
				'new_item'           	=> __( 'New Point Type', 'badgeos' ),
				'all_items'          	=> __( 'Point Types', 'badgeos' ),
				'view_item'          	=> __( 'View Point Type', 'badgeos' ),
				'search_items'       	=> __( 'Search Point Types', 'badgeos' ),
				'not_found'          	=> __( 'No point types found', 'badgeos' ),
				'not_found_in_trash' 	=> __( 'No point types found in Trash', 'badgeos' ),
				'parent_item_colon'  	=> '',
				'menu_name'          	=> __( 'Point Types', 'badgeos' ),
				'featured_image'     	=> __( ' Point Image', 'badgeos' ),
				'set_featured_image'    => __( 'Set point image', 'badgeos' ),
				'remove_featured_image' => __( 'Remove point image', 'badgeos' ),
				'use_featured_image'    => __( 'Use point image', 'badgeos' ),
			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => current_user_can( badgeos_get_manager_capability() ),
			'show_in_menu'       => 'badgeos_badgeos',
			'query_var'          => true,
			'rewrite'            => array( 'slug' => str_replace( '_', '-', $settings[ 'points_main_post_type' ] ) ),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'thumbnail' )
		) );

		/**
         * Register a sub point type post type i.e. Point Award
         */
		register_post_type( trim( $settings['points_award_post_type'] ), array(
			'labels' => array(
				'name'               	=> __( 'Point Award', 'badgeos' ),
				'singular_name'      	=> __( 'Point Award', 'badgeos' ),
				'add_new'            	=> __( 'Add New', 'badgeos' ),
				'add_new_item'       	=> __( 'Add New Point Award', 'badgeos' ),
				'edit_item'          	=> __( 'Edit Point Award', 'badgeos' ),
				'new_item'           	=> __( 'New Point Award', 'badgeos' ),
				'all_items'          	=> __( 'Point Award', 'badgeos' ),
				'view_item'          	=> __( 'View Point Award', 'badgeos' ),
				'search_items'       	=> __( 'Search Point Award', 'badgeos' ),
				'not_found'          	=> __( 'No point Awards found', 'badgeos' ),
				'not_found_in_trash' 	=> __( 'No point Awards found in Trash', 'badgeos' ),
				'parent_item_colon'  	=> '',
				'menu_name'          	=> __( 'Point Award', 'badgeos' ),
				'featured_image'     	=> __( 'Point Image', 'badgeos' ),
				'set_featured_image'    => __( 'Set point image', 'badgeos' ),
				'remove_featured_image' => __( 'Remove point image', 'badgeos' ),
				'use_featured_image'    => __( 'Use point image', 'badgeos' ),
			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => current_user_can( badgeos_get_manager_capability() ),
			'show_in_menu'       => false,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => str_replace( '_', '-', $settings[ 'points_award_post_type' ] ) ),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title' )
		) );

		/**
         * Register a sub point type post type i.e. Point deduct
         */
		register_post_type( trim( $settings['points_deduct_post_type'] ), array(
			'labels' => array(
				'name'               	=> __( 'Point Deduct', 'badgeos' ),
				'singular_name'      	=> __( 'Point Deduct', 'badgeos' ),
				'add_new'            	=> __( 'Add New', 'badgeos' ),
				'add_new_item'       	=> __( 'Add New Point Deduct', 'badgeos' ),
				'edit_item'          	=> __( 'Edit Point Deduct', 'badgeos' ),
				'new_item'           	=> __( 'New Point Deduct', 'badgeos' ),
				'all_items'          	=> __( 'Point Deduct', 'badgeos' ),
				'view_item'          	=> __( 'View Point Deduct', 'badgeos' ),
				'search_items'       	=> __( 'Search Point Deduct', 'badgeos' ),
				'not_found'          	=> __( 'No Point Deducts found', 'badgeos' ),
				'not_found_in_trash' 	=> __( 'No Point Deducts found in Trash', 'badgeos' ),
				'parent_item_colon'  	=> '',
				'menu_name'          	=> __( 'Point Deduct', 'badgeos' ),
				'featured_image'     	=> __( 'Badge Point Image', 'badgeos' ),
				'set_featured_image'    => __( 'Set badge point image', 'badgeos' ),
				'remove_featured_image' => __( 'Remove badge point image', 'badgeos' ),
				'use_featured_image'    => __( 'Use badge point image', 'badgeos' ),
			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => current_user_can( badgeos_get_manager_capability() ),
			'show_in_menu'       => false,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => str_replace( '_', '-', $settings[ 'points_deduct_post_type' ] ) ),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title' )
		) );
	}
}
add_action( 'init', 'badgeos_register_points_post_types' );