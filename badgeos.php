<?php
/**
* Plugin Name: BadgeOS
* Plugin URI: http://www.badgeos.org/
* Description: BadgeOS lets your site’s users complete tasks and earn badges that recognize their achievement.  Define achievements and choose from a range of options that determine when they're complete.  Badges are Mozilla Open Badges (OBI) compatible through integration with the “Open Credit” API by Credly, the free web service for issuing, earning and sharing badges for lifelong achievement.
* Author: Credly
* Version: 1.0.1
* Author URI: https://credly.com/
* License: GNU AGPL
*/

/*
Copyright © 2012-2013 Credly, LLC

This program is free software: you can redistribute it and/or modify it
under the terms of the GNU Affero General Public License, version 3,
as published by the Free Software Foundation.

This program is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU Affero General
Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>;.
*/

class BadgeOS {

	function __construct() {
		// Define plugin constants
		$this->basename       = plugin_basename( __FILE__ );
		$this->directory_path = plugin_dir_path( __FILE__ );
		$this->directory_url  = plugin_dir_url( __FILE__ );

		// Load translations
		load_plugin_textdomain( 'badgeos', false, 'badgeos/languages' );

		// Setup our activation and deactivation hooks
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		//include Posts to Posts core
		require( $this->directory_path . 'includes/p2p/load.php' );

		// Hook in all our important pieces
		add_action( 'plugins_loaded', array( &$this, 'includes' ) );
		add_action( 'init', array( &$this, 'include_cmb' ), 999 );
		add_action( 'init', array( &$this, 'register_achievement_relationships' ) );
		add_action( 'init', array( &$this, 'register_image_sizes' ) );
		add_action( 'admin_menu', array( &$this, 'plugin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'admin_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'frontend_scripts' ) );
		add_action( 'init', array( $this, 'credly_init' ) );

	}

	/**
	 * Include all our important files.
	 */
	function includes() {
		require_once( $this->directory_path . 'includes/post-types.php' );
		require_once( $this->directory_path . 'includes/admin-settings.php' );
		require_once( $this->directory_path . 'includes/achievement-functions.php' );
		require_once( $this->directory_path . 'includes/meta-boxes.php' );
		require_once( $this->directory_path . 'includes/triggers.php' );
		require_once( $this->directory_path . 'includes/steps-ui.php' );
		require_once( $this->directory_path . 'includes/shortcodes.php' );
		require_once( $this->directory_path . 'includes/content-filters.php' );
		require_once( $this->directory_path . 'includes/submission-actions.php' );
		require_once( $this->directory_path . 'includes/rules-engine.php' );
		require_once( $this->directory_path . 'includes/user.php' );
		require_once( $this->directory_path . 'includes/credly.php' );
		require_once( $this->directory_path . 'includes/widgets.php' );
	}

	/**
	 * Initialize the custom metabox class.
	 */
	function include_cmb() {

		if ( ! class_exists( 'cmb_Meta_Box' ) )
			require_once( $this->directory_path . 'includes/cmb/init.php' );

	}

	/**
	 * Register custom Post2Post relationships
	 */
	function register_achievement_relationships() {

		// Grab all our registered achievement types and loop through them
		$achievement_types = badgeos_get_achievement_types_slugs();
		if ( is_array( $achievement_types ) && ! empty( $achievement_types ) ) {
			foreach ( $achievement_types as $achievement_type ) {

				// Connect steps to each achievement type
				// Used to get an achievement's required steps (e.g. This badge requires these 3 steps)
				p2p_register_connection_type( array(
					'name'      => 'step-to-' . $achievement_type,
					'from'      => 'step',
					'to'        => $achievement_type,
					'admin_box' => false,
					'fields'    => array(
						'order'   => array(
							'title'   => 'Order',
							'type'    => 'text',
							'default' => 0,
						),
					),
				) );

				// Connect each achievement type to a step
				// Used to get a step's required achievement (e.g. this step requires earning Level 1)
				p2p_register_connection_type( array(
					'name'      => $achievement_type . '-to-step',
					'from'      => $achievement_type,
					'to'        => 'step',
					'admin_box' => false,
					'fields'    => array(
						'order'   => array(
							'title'   => 'Order',
							'type'    => 'text',
							'default' => 0,
						),
					),
				) );

			}
		}

	}

	/**
	 * Register custom WordPress image size(s)
	 */
	function register_image_sizes() {
		add_image_size( 'badgeos-achievement', 100, 100 );
	}

	/**
	 * Activation hook for the plugin.
	 */
	function activate() {

		// Include our important bits
		$this->includes();

		// Create Badges achievement type
		if ( !get_page_by_title( 'Badges', 'OBJECT', 'achievement-type' ) ) {
			$badge_post_id = wp_insert_post( array(
				'post_title'   => __( 'Badges', 'badgeos'),
				'post_content' => 'Badges badge type',
				'post_status'  => 'publish',
				'post_author'  => 1,
				'post_type'    => 'achievement-type',
			) );
			update_post_meta( $badge_post_id, '_badgeos_singular_name', __( 'Badge', 'badgeos' ) );
			update_post_meta( $badge_post_id, '_badgeos_show_in_menu', true );
		}

		// Setup our option defaults
		$options = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
		update_option( 'badgeos_settings', $options );

		// Setup default Credly options
		$credly_settings = array();
		$credly_settings['credly_enable'] = 'true';
		$credly_settings['credly_badge_title'] = 'post_title';
		$credly_settings['credly_badge_description'] = 'post_body';
		$credly_settings['credly_badge_short_description'] = 'post_excerpt';
		$credly_settings['credly_badge_image'] = 'featured_image';
		$credly_settings['credly_badge_testimonial'] = '_badgeos_congratulations_text';
		$credly_settings['credly_badge_evidence'] = 'permalink';
		$credly_settings['credly_badge_sendemail'] = 'true';
		$credly_settings['credly_badge_criteria'] = '';
		update_option( 'credly_settings', $credly_settings );

		// Register our post types and flush rewrite rules
		badgeos_register_post_types();
		flush_rewrite_rules();
	}

	/**
	 * Create BadgeOS Settings menus
	 */
	function plugin_menu() {

		// Get minimum role setting for menus
		$badgeos_settings = get_option( 'badgeos_settings' );
		$minimum_role = ( !empty( $badgeos_settings['minimum_role'] ) ) ? $badgeos_settings['minimum_role'] : 'administrator';

		// Create main menu
		add_menu_page( 'BadgeOS', 'BadgeOS', $minimum_role, 'badgeos_badgeos', 'badgeos_settings', $this->directory_url . 'images/badgeos_icon.png' );

		// Create submenu items
		add_submenu_page( 'badgeos_badgeos', 'BadgeOS Settings', 'Settings', $minimum_role, 'badgeos_settings', 'badgeos_settings_page' );
		add_submenu_page( 'badgeos_badgeos', 'Credly Integration', 'Credly Integration', $minimum_role, 'badgeos_sub_credly_integration', 'badgeos_credly_options_page' );
		add_submenu_page( 'badgeos_badgeos', 'Add-Ons', 'Add-Ons', $minimum_role, 'badgeos_sub_add_ons', 'badgeos_add_ons_page' );
		add_submenu_page( 'badgeos_badgeos', 'Help / Support', 'Help / Support', $minimum_role, 'badgeos_sub_help_support', 'badgeos_help_support_page' );

	}

	/**
	 * Admin scripts and styles
	 */
	function admin_scripts() {

		wp_enqueue_script( 'badgeos-admin-js', $this->directory_url . 'js/admin.js', array( 'jquery' ) );
		wp_enqueue_style( 'badgeos-admin-styles', $this->directory_url . 'css/admin.css' );
		wp_enqueue_script( 'badgeos-credly', $this->directory_url . 'js/credly.js' );

	}

	/**
	 * Frontend scripts and styles
	 */
	function frontend_scripts() {
		wp_register_script( 'badgeos-achievements', $this->directory_url . 'js/badgeos-achievements.js', array( 'jquery' ), '1.0', true );

		$data = array(
			'message' => __( 'Would you like to display this badge on social networks and add it to your lifelong badge collection?', 'badgeos' ),
			'confirm' => __( 'Yes, send to Credly', 'badgeos' ),
			'cancel' => __( 'Cancel', 'badgeos' ),
			'share' => __( 'Share on Credly!', 'badgeos' ),
			'localized_error' => __( 'Error:', 'badgeos' ),
			'errormessage' => __( 'Error: Timed out', 'badgeos' )
		);
		wp_localize_script( 'badgeos-achievements', 'BadgeosCredlyData', $data );

		$css_file = file_exists( get_stylesheet_directory() .'/badgeos.css' )
			? get_stylesheet_directory_uri() .'/badgeos.css' :
			$this->directory_url . 'css/badgeos-front.css';

		wp_register_style( 'badgeos-front', $css_file, null, '1.0' );

		$css_single_file = file_exists( get_stylesheet_directory() .'/badgeos-single.css' )
			? get_stylesheet_directory_uri() .'/badgeos-single.css'
			: $this->directory_url . 'css/badgeos-single.css';

		wp_register_style( 'badgeos-single', $css_single_file, null, '1.0' );

		$css_widget_file = file_exists( get_stylesheet_directory() .'/badgeos-widgets.css' )
			? get_stylesheet_directory_uri() .'/badgeos-widgets.css'
			: $this->directory_url . 'css/badgeos-widgets.css';

		wp_register_style( 'badgeos-widget', $css_widget_file, null, '1.0' );
	}

	/**
	 * Deactivation hook for the plugin.
	 */
	function deactivate() {
		global $wp_rewrite;
		flush_rewrite_rules();
	}

	/**
	 * Initialize Credly API
	 */
	function credly_init() {

		// Initalize the CredlyAPI class
		$GLOBALS['badgeos_credly'] = new BadgeOS_Credly();

	}

}
$GLOBALS['badgeos'] = new BadgeOS();

/**
 * Get our plugin's directory path
 *
 * @since 1.0.0
 * @return string The filepath of the BadgeOS plugin root directory
 */
function badgeos_get_directory_path() {
	return $GLOBALS['badgeos']->directory_path;
}

/**
 * Get our plugin's directory URL
 *
 * @since 1.0.0
 * @return string The URL for the BadgeOS plugin root directory
 */
function badgeos_get_directory_url() {
	return $GLOBALS['badgeos']->directory_url;
}

/**
 * Check if debug mode is enabled
 *
 * @since  1.0.0
 * @return bool True if debug mode is enabled, false otherwise
 */
function badgeos_is_debug_mode() {

	//get setting for debug mode
	$badgeos_settings = get_option( 'badgeos_settings' );
	$debug_mode = ( !empty( $badgeos_settings['debug_mode'] ) ) ? $badgeos_settings['debug_mode'] : 'disabled';

	if ( $debug_mode == 'enabled' ) {
		return true;
	}

	return false;

}

/**
 * Posts a log entry when a user unlocks any achievement post
 *
 * @since  1.0
 * @param  integer $post_id    The post id of the activity we're logging
 * @param  integer $user_id    The user ID
 * @param  string  $action     The action word to be used for the generated title
 * @param  string  $title      An optional default title for the log post
 * @return integer             The post ID of the newly created log entry
 */
function badgeos_post_log_entry( $post_id, $user_id = 0, $action = 'unlocked', $title = '' ) {
	global $user_ID;
	if ( $user_id == 0 ) {
		$user_id = $user_ID;
	}

	$user              = get_userdata( $user_id );
	$achievement       = get_post( $post_id );
	$achievement_types = badgeos_get_achievement_types();
	$achievement_type  = $achievement ? $achievement_types[$achievement->post_type]['single_name'] : '';
	$default_title     = ( !empty( $title ) ? $title : "{$user->user_login} {$action} the \"{$achievement->post_title}\" {$achievement_type}" );
	$title             = apply_filters( 'badgeos_log_entry_title', $default_title, $post_id, $user_id, $action, $achievement, $achievement_types );

	$args = array(
		'post_title'  => $title,
		'post_status' => 'publish',
		'post_author' => absint( $user_id ),
		'post_type'   => 'badgeos-log-entry',
	);

	if ( $log_post_id = wp_insert_post( $args ) )
		add_post_meta( $log_post_id, '_badgeos_log_achievement_id', $post_id );

	do_action( 'badgeos_create_log_entry', $log_post_id, $post_id, $user_id, $action );

	return $log_post_id;
}
