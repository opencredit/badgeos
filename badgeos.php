<?php
/**
* Plugin Name: BadgeOS
* Plugin URI: http://www.badgeos.org/
* Description: BadgeOS lets your site’s users complete tasks and earn badges that recognize their achievement.  Define achievements and choose from a range of options that determine when they're complete.  Badges are Mozilla Open Badges (OBI) compatible through integration with the “Open Credit” API by Credly, the free web service for issuing, earning and sharing badges for lifelong achievement.
* Author: LearningTimes
* Version: 3.3
* Author URI: https://credly.com/
* License: GNU AGPL
* Text Domain: badgeos
*/

/*
Copyright © 2012-2014 LearningTimes, LLC

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

	/**
	 * BadgeOS Version
	 *
	 * @var string
	 */
	public static $version = '3.3';

	function __construct() {
		// Define plugin constants
		$this->basename       = plugin_basename( __FILE__ );
		$this->directory_path = plugin_dir_path( __FILE__ );
		$this->directory_url  = plugin_dir_url( __FILE__ );

        $this->start_time	  = strtotime(" -1 second ");

        $this->award_ids  = array();

		// Load translations
		load_plugin_textdomain( 'badgeos', false, 'badgeos/languages' );

		// Setup our activation and deactivation hooks
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		// Hook in all our important pieces
		add_action( 'plugins_loaded', array( $this, 'includes' ) );
		add_action( 'init', array( $this, 'register_scripts_and_styles' ) );
		add_action( 'init', array( $this, 'include_cmb' ), 999 );
		add_action( 'init', array( $this, 'register_achievement_relationships' ) );
		add_action( 'init', array( $this, 'register_image_sizes' ) );
		add_action( 'admin_menu', array( $this, 'plugin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
		add_action( 'init', array( $this, 'credly_init' ) );
		
		$this->db_upgrade();

        //add action for adding ckeditor script
        add_action('wp_footer', array( $this, 'frontend_scripts' ));

	}

/**
	 * This function will made the necessary changes on existing database to accomodate the points work
	 */
	function db_upgrade() {
		
		global $wpdb;

		$point_type = 'point_type';
		
		$table_name = $wpdb->prefix . "badgeos_achievements";
		if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
			$sql = "CREATE TABLE " . $table_name . " (
				`entry_id` int(10) NOT NULL AUTO_INCREMENT,
				`ID` int(10) DEFAULT '0',
				`post_type` varchar(100) DEFAULT NULL,
				`achievement_title` TEXT DEFAULT NULL,
				`rec_type` varchar(10) DEFAULT '',
				`points` int(10) DEFAULT '0',
				`point_type` varchar(50) DEFAULT '',
				`user_id` int(10) DEFAULT '0',
				`this_trigger` varchar(100) DEFAULT NULL,
				`image` varchar(50) DEFAULT NULL,
				`site_id` int(10) DEFAULT '0',
				`date_earned` timestamp NULL DEFAULT NULL DEFAULT CURRENT_TIMESTAMP,						
				PRIMARY KEY (`entry_id`)
			);";
			$wpdb->query( $sql );
		}

		/**
         * Badgeos Points Table
         */
		$table_name = $wpdb->prefix . 'badgeos_points';
		if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
			$sql = "CREATE TABLE " . $table_name . " (
				`id` int(10) NOT NULL AUTO_INCREMENT,
				`achievement_id` int(10) DEFAULT '0',
				`credit_id` int(10) DEFAULT '0',
				`step_id` int(10) DEFAULT '0',
				`user_id` int(10) DEFAULT NULL,
				`admin_id` int(10) DEFAULT '0',
				`type` enum('Award','Deduct','Utilized') DEFAULT NULL,
				`this_trigger` varchar(100) DEFAULT NULL,
				`credit` int(10) DEFAULT NULL,
				`dateadded` timestamp NULL DEFAULT NULL,							
				PRIMARY KEY (`id`)
			);";
			$wpdb->query( $sql );
		}

        $table_name = $wpdb->prefix . 'badgeos_ranks';
		if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
            $sql = "CREATE TABLE " . $table_name . " (
				`id` int(10) NOT NULL AUTO_INCREMENT,
				`rank_id` int(10) DEFAULT '0',
				`rank_type` varchar(100) DEFAULT NULL,
				`rank_title` TEXT DEFAULT NULL,
				`credit_id` int(10) DEFAULT '0',
				`credit_amount` int(10) DEFAULT '0',
				`user_id` int(10) DEFAULT '0',
				`admin_id` int(10) DEFAULT '0',
				`this_trigger` varchar(100) DEFAULT NULL,
				`priority` int(10) NOT NULL,
				`dateadded` timestamp NULL DEFAULT NULL,							
				PRIMARY KEY (`id`)
			);";
			$wpdb->query( $sql );
		}

        // Setup default BadgeOS options
		$badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();

        $badgeos_rec_title_updated = ( $exists = get_option( 'badgeos_rec_title_updated' ) ) ? $exists : 'No';
        if( $badgeos_rec_title_updated == 'No' ) {

            $strQuery = 'ALTER TABLE '.$wpdb->prefix . 'badgeos_achievements MODIFY achievement_title TEXT DEFAULT NULL;';
            $wpdb->query( $strQuery );

            $strQuery = 'ALTER TABLE '.$wpdb->prefix . 'badgeos_ranks MODIFY rank_title TEXT DEFAULT NULL;';
            $wpdb->query( $strQuery );

            update_option( 'badgeos_rec_title_updated', 'Yes' );
        }

        if ( $badgeos_settings ) {

            if( ! isset( $badgeos_settings['ranks_main_post_type'] ) && empty( $badgeos_settings['ranks_main_post_type'] )  )
                $badgeos_settings['ranks_main_post_type']       = 'rank_types';

            if(  !isset( $badgeos_settings['ranks_step_post_type'] ) && empty( $badgeos_settings['ranks_step_post_type'] ) )
                $badgeos_settings['ranks_step_post_type']       = 'rank_requirement';

            if(  !isset( $badgeos_settings['points_main_post_type'] ) && empty( $badgeos_settings['points_main_post_type'] ) )
				$badgeos_settings['points_main_post_type']     = $point_type;

			if( !isset( $badgeos_settings['points_award_post_type'] ) && empty( $badgeos_settings['points_award_post_type'] ) )
				$badgeos_settings['points_award_post_type']    = 'point_award';

			if( !isset( $badgeos_settings['points_deduct_post_type'] ) && empty( $badgeos_settings['points_deduct_post_type'] ) )
				$badgeos_settings['points_deduct_post_type']   = 'point_deduct';

			update_option( 'badgeos_settings', $badgeos_settings );
		}
	}

	/**
	 * Include all our important files.
	 */
	function includes() {
		require_once( $this->directory_path . 'includes/p2p/load.php' );
		require_once( $this->directory_path . 'includes/class.BadgeOS_Editor_Shortcodes.php' );
		require_once( $this->directory_path . 'includes/class.BadgeOS_Plugin_Updater.php' );
		require_once( $this->directory_path . 'includes/class.BadgeOS_Shortcode.php' );
		require_once( $this->directory_path . 'includes/class.Credly_Badge_Builder.php' );
		require_once( $this->directory_path . 'includes/post-types.php' );
		require_once( $this->directory_path . 'includes/admin-settings.php' );
        require_once( $this->directory_path . 'includes/admin-tools.php' );
		require_once( $this->directory_path . 'includes/achievement-functions.php' );
		require_once( $this->directory_path . 'includes/activity-functions.php' );
		require_once( $this->directory_path . 'includes/ajax-functions.php' );
		require_once( $this->directory_path . 'includes/logging-functions.php' );
		require_once( $this->directory_path . 'includes/meta-boxes.php' );
		
		/**
		 * Move achivements from meta to db
		 */
		require_once( $this->directory_path . 'includes/meta-to-db.php' );
        require_once( $this->directory_path . 'includes/achievement-upgrade.php' );

		/**
		 * Point files
		 */
		require_once( $this->directory_path . 'includes/points/post-types.php' );
		require_once( $this->directory_path . 'includes/points/meta-boxes.php' );
		require_once( $this->directory_path . 'includes/points/award-steps-ui.php' );
		require_once( $this->directory_path . 'includes/points/deduct-steps-ui.php' ); 
		require_once( $this->directory_path . 'includes/points/point-rules-engine.php' );
		require_once( $this->directory_path . 'includes/points/triggers.php' );
		require_once( $this->directory_path . 'includes/points/point-functions.php' );

        /**
         * Ranks files heredd
         */
		require_once( $this->directory_path . 'includes/ranks/rank-functions.php' );
		require_once( $this->directory_path . 'includes/ranks/post-types.php' );
		require_once( $this->directory_path . 'includes/ranks/meta-boxes.php' );
		require_once( $this->directory_path . 'includes/ranks/rank-steps-ui.php' );
		require_once( $this->directory_path . 'includes/ranks/triggers.php' );
		require_once( $this->directory_path . 'includes/ranks/ranks-rules-engine.php' );
		
		require_once( $this->directory_path . 'includes/triggers.php' );
		require_once( $this->directory_path . 'includes/steps-ui.php' );
		require_once( $this->directory_path . 'includes/shortcodes.php' );
		require_once( $this->directory_path . 'includes/content-filters.php' );
		require_once( $this->directory_path . 'includes/submission-actions.php' );
		require_once( $this->directory_path . 'includes/rules-engine.php' );
		require_once( $this->directory_path . 'includes/user.php' );
		require_once( $this->directory_path . 'includes/credly.php' );
		require_once( $this->directory_path . 'includes/credly-badge-builder.php' );
		require_once( $this->directory_path . 'includes/widgets.php' );
        require_once( $this->directory_path . 'includes/posts-functions.php' );
	}

	/**
	 * Register all core scripts and styles
	 *
	 * @since  1.3.0
	 */
	function register_scripts_and_styles() {
		// Register scripts
        wp_register_script( 'badgeos-admin-tools-js', $this->directory_url . 'js/tools.js', array( 'jquery', 'jquery-ui-tabs' ), $this::$version, true );
        wp_register_script( 'badgeos-admin-js', $this->directory_url . 'js/admin.js', array( 'jquery', 'jquery-ui-tabs' ), $this::$version, true );
        wp_register_script( 'badgeos-credly', $this->directory_url . 'js/credly.js' );
		wp_register_script( 'badgeos-achievements', $this->directory_url . 'js/badgeos-achievements.js', array( 'jquery' ), $this::$version, true );
		wp_register_script( 'credly-badge-builder', $this->directory_url . 'js/credly-badge-builder.js', array( 'jquery' ), $this::$version, true );

        $admin_js_translation_array = array(
            'ajax_url' 					=> admin_url( 'admin-ajax.php' ),
			'loading_img' 				=> admin_url( 'images/spinner.gif' ),
			'no_awarded_rank'           => __( 'No Awarded Ranks', 'badgeos' ),
            'no_awarded_achievement'    => __( 'No Awarded Achievements', 'badgeos' ),
            'revoke_rank'               => __( 'Revoke Rank', 'badgeos' ),
            'revoke_achievements'       => __( 'Revoke Achievements', 'badgeos' ),
            'no_available_achievements' => __( 'No Available Achievements', 'badgeos' ),
            'default_rank'              => __( 'Default Rank', 'badgeos' ),

        );
        wp_localize_script( 'badgeos-admin-js', 'admin_js', $admin_js_translation_array );

		// Register styles
        wp_register_style( 'jquery-ui-styles', $this->directory_url . 'css/jquery-ui.css' );
		wp_register_style( 'badgeos-admin-styles', $this->directory_url . 'css/admin.css' );

		$badgeos_front = file_exists( get_stylesheet_directory() .'/badgeos.css' )
			? get_stylesheet_directory_uri() .'/badgeos.css'
			: $this->directory_url . 'css/badgeos-front.css';
		wp_register_style( 'badgeos-front', $badgeos_front, null, $this::$version );

		$badgeos_single = file_exists( get_stylesheet_directory() .'/badgeos-single.css' )
			? get_stylesheet_directory_uri() .'/badgeos-single.css'
			: $this->directory_url . 'css/badgeos-single.css';
		wp_register_style( 'badgeos-single', $badgeos_single, null, $this::$version );

		$badgeos_widget = file_exists( get_stylesheet_directory() .'/badgeos-widgets.css' )
			? get_stylesheet_directory_uri() .'/badgeos-widgets.css'
			: $this->directory_url . 'css/badgeos-widgets.css';
		wp_register_style( 'badgeos-widget', $badgeos_widget, null, $this::$version );
	}

	/**
	 * Initialize CMB.
	 */
	function include_cmb() {

		// Don't load on frontend.
		if ( !is_admin() ) { return; }
        require_once( $this->directory_path . 'includes/cmb2/init.php' );
        require_once( $this->directory_path . 'includes/cmb2-custom-multiple-selectbox.php' );
		require_once( $this->directory_path . 'includes/CMB2-Date-Range-Field/wds-cmb2-date-range-field.php' );
		require_once( $this->directory_path . 'includes/points/cmb2-custom-credit-field.php' );
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
							'title'   => __( 'Order', 'badgeos' ),
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
							'title'   => __( 'Order', 'badgeos' ),
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
        $point_type = 'point_type';

		// Create Badges achievement type
		if ( !get_page_by_title( 'Badges', 'OBJECT', 'achievement-type' ) ) {
			$badge_post_id = wp_insert_post( array(
				'post_title'   => __( 'Badges', 'badgeos'),
				'post_content' => __( 'Badges badge type', 'badgeos' ),
				'post_status'  => 'publish',
				'post_author'  => 1,
				'post_type'    => 'achievement-type',
			) );
			update_post_meta( $badge_post_id, '_badgeos_singular_name', __( 'Badge', 'badgeos' ) );
			update_post_meta( $badge_post_id, '_badgeos_show_in_menu', true );
		}

		// Setup default BadgeOS options
		$badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
		if ( empty( $badgeos_settings ) ) {
			$badgeos_settings['minimum_role']     = 'manage_options';
			$badgeos_settings['submission_manager_role'] = 'manage_options';
			$badgeos_settings['submission_email'] = 'enabled';
			$badgeos_settings['debug_mode']       			= 'disabled';
            $badgeos_settings['ranks_main_post_type']       = 'rank_types';
            $badgeos_settings['ranks_step_post_type']       = 'rank_requirement';

            $badgeos_settings['points_main_post_type']     = $point_type;
            $badgeos_settings['points_award_post_type']    = 'point_award';
            $badgeos_settings['points_deduct_post_type']   = 'point_deduct';

            $badgeos_settings['remove_data_on_uninstall']   = null;
			update_option( 'badgeos_settings', $badgeos_settings );
		}

		// Setup default Credly options
		$credly_settings = (array) get_option( 'credly_settings', array() );

		if ( empty( $credly_settings ) || !isset( $credly_settings[ 'credly_enable' ] ) ) {
			$credly_settings['credly_enable']                      = 'true';
			$credly_settings['credly_badge_title']                 = 'post_title';
			$credly_settings['credly_badge_description']           = 'post_body';
			$credly_settings['credly_badge_short_description']     = 'post_excerpt';
			$credly_settings['credly_badge_criteria']              = '';
			$credly_settings['credly_badge_image']                 = 'featured_image';
			$credly_settings['credly_badge_testimonial']           = 'congratulations_text';
			$credly_settings['credly_badge_evidence']              = 'permalink';
			$credly_settings['credly_badge_sendemail_add_message'] = 'false';
			update_option( 'credly_settings', $credly_settings );
		}
		
		$this->db_upgrade();
		// Register our post types and flush rewrite rules
		badgeos_flush_rewrite_rules();
	}

	/**
	 * Create BadgeOS Settings menus
	 */
	function plugin_menu() {

		// Set minimum role setting for menus
        $caps = array( 'manage_options', 'delete_others_posts', 'publish_posts' );
        $minimum_role 				= badgeos_get_manager_capability();
        $minimum_submission_role 	= badgeos_get_submission_manager_capability();

        $admin_role_num = array_search ( $minimum_role, $caps );
        $submission_role_num = array_search ( $minimum_submission_role, $caps );
        $main_item_role = $minimum_role;
        if( $submission_role_num > $admin_role_num ) {
            $main_item_role = $minimum_submission_role;
        }
        $main_item_role = trim( $main_item_role );


        // Create main menu
        add_menu_page( 'BadgeOS', 'BadgeOS', $main_item_role, 'badgeos_badgeos', 'badgeos_settings', $this->directory_url . 'images/badgeos_icon.png', 110 );

		// Create submenu items
		add_submenu_page( 'badgeos_badgeos', __( 'BadgeOS Settings', 'badgeos' ), __( 'Settings', 'badgeos' ), $minimum_role, 'badgeos_settings', 'badgeos_settings_page' );
		add_submenu_page( 'badgeos_badgeos', __( 'Credly Integration', 'badgeos' ), __( 'Credly Integration', 'badgeos' ), $minimum_role, 'badgeos_sub_credly_integration', 'badgeos_credly_options_page' );
		add_submenu_page( 'badgeos_badgeos', __( 'Add-Ons', 'badgeos' ), __( 'Add-Ons', 'badgeos' ), $minimum_role, 'badgeos_sub_add_ons', 'badgeos_add_ons_page' );
		add_submenu_page( 'badgeos_badgeos', __( 'Help / Support', 'badgeos' ), __( 'Help / Support', 'badgeos' ), $minimum_role, 'badgeos_sub_help_support', 'badgeos_help_support_page' );

        $settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
        if ( ! empty( $settings ) ) {
            $query = wp_count_posts( trim( $settings['ranks_main_post_type'] ) );
            $rank_types = get_posts( array(
                'post_type'         => trim( $settings['ranks_main_post_type'] ),
                'post_status'       => 'publish',
                'posts_per_page'    => -1,
            ) );

            $rank_menu = false;
            foreach( $rank_types as $rank_type ) {
                $show_in_menu = get_post_meta( $rank_type->ID, '_badgeos_show_in_menu', true );
                if( 'on' == $show_in_menu ) {
                    $rank_menu = true;
                }
            }

            if( absint( $query->publish ) > 0 && $rank_menu ) {
                add_menu_page(__( 'Ranks', 'badgeos' ),__( 'Ranks', 'badgeos' ), $minimum_role, 'badgeos_ranks',  [ $this, 'badgeos_wp_dashboard' ]);
            }
        }
    }

	/**
	 * Admin scripts and styles
	 */
	function admin_scripts() {

        // Load scripts
        $screen = get_current_screen();
        if( $screen->id == 'badgeos_page_badgeos_tools' ) {
            wp_enqueue_script( 'badgeos-admin-tools-js' );
            $min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
            wp_enqueue_script( 'badgeos-select2', $this->directory_url . "js/select2/select2$min.js", array( 'jquery' ), '', true );
            wp_enqueue_style( 'badgeos-select2-css', $this->directory_url . 'js/select2/select2.css' );
        }

		wp_enqueue_script( 'badgeos-admin-js' );
		wp_enqueue_script( 'badgeos-credly' );

		// Load styles
        wp_enqueue_style( 'jquery-ui-styles' );
		wp_enqueue_style( 'badgeos-admin-styles' );

	}

	/**
	 * Frontend scripts and styles
	 */
	function frontend_scripts() {

		$data = array(
			'ajax_url'        => esc_url( admin_url( 'admin-ajax.php', 'relative' ) ),
			'message'         => __( 'Would you like to display this badge on social networks and add it to your lifelong badge collection?', 'badgeos' ),
			'confirm'         => __( 'Yes, send to Credly', 'badgeos' ),
			'cancel'          => __( 'Cancel', 'badgeos' ),
			'share'           => __( 'Share on Credly!', 'badgeos' ),
			'localized_error' => __( 'Error:', 'badgeos' ),
			'errormessage'    => __( 'Error: Timed out', 'badgeos' )
		);
		wp_localize_script( 'badgeos-achievements', 'BadgeosCredlyData', $data );

        wp_enqueue_script(
            'ck_editor_cdn',
            ('https://cdn.ckeditor.com/4.5.3/standard/ckeditor.js'), array( 'jquery' ), 'v3', true
        );

        wp_enqueue_script(
           'custom_script',
            plugins_url( '/js/ckeditor.js' , __FILE__ ),
            array( 'jquery' ),$this::$version,true
        );
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
 * Get our current start time
 *
 * @return timestamp
 */
function badgeos_get_start_time() {
    return $GLOBALS['badgeos']->start_time;
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

if ( ! function_exists('badgeos_write_log')) {
    function badgeos_write_log ( $log )  {
        if ( is_array( $log ) || is_object( $log ) ) {
            error_log( print_r( $log, true ) );
        } else {
            error_log( $log );
        }
    }
}
