<?php
/**
* Plugin Name: BadgeOS
* Plugin URI: http://www.badgeos.org/
* Description: BadgeOS lets your site’s users complete tasks and earn badges that recognize their achievement.  Define achievements and choose from a range of options that determine when they're complete.  Badges are Mozilla Open Badges (OBI) compatible through integration with the “Open Credit” API by Credly, the free web service for issuing, earning and sharing badges for lifelong achievement.
* Author: LearningTimes
* Version: 3.6.6
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
	public static $version = '3.6.6';

	/**
	 * BadgeOS Achievement Date
	 *
	 * @var string
	 */
	public $achievement_date;

	/**
	 * BadgeOS Rank Date
	 *
	 * @var string
	 */
	public $rank_date;

	/**
	 * BadgeOS Point Award Date
	 *
	 * @var string
	 */
	public $mysql_award_points;

	/**
	 * BadgeOS Point Deduct Date
	 *
	 * @var string
	 */
	public $mysql_deduct_points;
	
	function __construct() {
		// Define plugin constants
		$this->basename       = plugin_basename( __FILE__ );
		$this->directory_path = plugin_dir_path( __FILE__ );
		$this->directory_url  = plugin_dir_url( __FILE__ );

        $this->start_time	  = current_time( 'timestamp' ) - 5 ;

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
		
		if( badgeos_first_time_installed() ) {
			add_action( 'init', array( $this, 'credly_init' ) );
		}
		
		$this->db_upgrade();

        //add action for adding ckeditor script
        add_action('wp_footer', array( $this, 'frontend_scripts' ));
		
		if( ! is_network_admin() ) {
			//Template redirect hook
			add_action( 'activated_plugin', array( $this, 'activation_redirect' ), 10, 2 );
		}
	}

	/**
	 * This function will redirect to templates on activation
	 */
	function activation_redirect( $plugin, $network_wide ) {
		if( trim( $this->basename ) == trim( $plugin ) ) {
			exit( wp_redirect( 'admin.php?page=badgeos-welcome'  ) );
		}
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
				`sub_nom_id` int(10) DEFAULT '0',
				`post_type` varchar(100) DEFAULT NULL,
				`achievement_title` TEXT DEFAULT NULL,
				`rec_type` varchar(10) DEFAULT '',
				`points` int(10) DEFAULT '0',
				`point_type` varchar(50) DEFAULT '',
				`user_id` int(10) DEFAULT '0',
				`this_trigger` varchar(100) DEFAULT NULL,
				`image` varchar(50) DEFAULT NULL,
				`site_id` int(10) DEFAULT '0',
				`actual_date_earned` timestamp NULL DEFAULT NULL DEFAULT CURRENT_TIMESTAMP,						
				`date_earned` timestamp NULL DEFAULT NULL DEFAULT CURRENT_TIMESTAMP,						
				PRIMARY KEY (`entry_id`)
			);";
			$wpdb->query( $sql );
		}

		/**
         * BadgeOS Points Table
         */
		$table_name = $wpdb->prefix . 'badgeos_points';
		if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
			$sql = "CREATE TABLE ".$table_name." (
				`id` int(10) NOT NULL AUTO_INCREMENT,
				`achievement_id` int(10) DEFAULT '0',
				`credit_id` int(10) DEFAULT '0',
				`step_id` int(10) DEFAULT '0',
				`user_id` int(10) DEFAULT NULL,
				`admin_id` int(10) DEFAULT '0',
				`type` enum('Award','Deduct','Utilized') DEFAULT NULL,
				`this_trigger` varchar(100) DEFAULT NULL,
				`credit` int(10) DEFAULT NULL,
				`actual_date_earned` timestamp NULL DEFAULT NULL DEFAULT CURRENT_TIMESTAMP,
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
				`actual_date_earned` timestamp NULL DEFAULT NULL DEFAULT CURRENT_TIMESTAMP,
				`dateadded` timestamp NULL DEFAULT NULL,							
				PRIMARY KEY (`id`)
			);";
			$wpdb->query( $sql );
		}
		
		require_once( $this->directory_path . 'includes/utilities.php' );
		$table_name = $wpdb->prefix . 'p2p';
		if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
			badgeos_utilities::update_option( 'p2p_storage', '' );
		}
		// Setup default BadgeOS options
		$badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
        $badgeos_admin_tools = ( $exists = badgeos_utilities::get_option( 'badgeos_admin_tools' ) ) ? $exists : array();
        
        $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".$wpdb->prefix."badgeos_achievements' AND column_name = 'sub_nom_id'"  );
        if(empty($row)){
            $wpdb->query("ALTER TABLE ".$wpdb->prefix . "badgeos_achievements ADD sub_nom_id int(10) DEFAULT '0'");
		}
		
		//actuall date earned field on all of the tables
		$row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".$wpdb->prefix."badgeos_achievements' AND column_name = 'actual_date_earned'"  );
        if(empty($row)){
            $wpdb->query("ALTER TABLE ".$wpdb->prefix . "badgeos_achievements ADD actual_date_earned timestamp NULL DEFAULT NULL DEFAULT CURRENT_TIMESTAMP");
		}
		
		$row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".$wpdb->prefix."badgeos_points' AND column_name = 'actual_date_earned'"  );
        if(empty($row)){
            $wpdb->query("ALTER TABLE ".$wpdb->prefix . "badgeos_points ADD actual_date_earned timestamp NULL DEFAULT NULL DEFAULT CURRENT_TIMESTAMP");
		}
		
		$row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".$wpdb->prefix."badgeos_ranks' AND column_name = 'actual_date_earned'"  );
        if(empty($row)){
            $wpdb->query("ALTER TABLE ".$wpdb->prefix . "badgeos_ranks ADD actual_date_earned timestamp NULL DEFAULT NULL DEFAULT CURRENT_TIMESTAMP");
        }
		////////////////////////////////////////////////

        $badgeos_rec_title_updated = ( $exists = badgeos_utilities::get_option( 'badgeos_rec_title_updated' ) ) ? $exists : 'No';
        if( $badgeos_rec_title_updated == 'No' ) {

            $strQuery = 'ALTER TABLE '.$wpdb->prefix . 'badgeos_achievements MODIFY achievement_title TEXT DEFAULT NULL;';
            $wpdb->query( $strQuery );

            $strQuery = 'ALTER TABLE '.$wpdb->prefix . 'badgeos_ranks MODIFY rank_title TEXT DEFAULT NULL;';
            $wpdb->query( $strQuery );

            badgeos_utilities::update_option( 'badgeos_rec_title_updated', 'Yes' );
        }

		// Setup default BadgeOS options
		
		if ( empty( $badgeos_settings ) ) {
			$badgeos_settings['minimum_role']     				= 'manage_options';
            $badgeos_settings['submission_manager_role'] 		= 'manage_options';
            $badgeos_settings['submission_email'] 				= 'enabled';
            $badgeos_settings['debug_mode']       				= 'disabled';
            $badgeos_settings['achievement_main_post_type']     = 'achievement-type';
            $badgeos_settings['achievement_step_post_type']     = 'step';
			$badgeos_settings['minimum_role']     				= 'manage_options';
            $badgeos_settings['submission_manager_role'] 		= 'manage_options';
            $badgeos_settings['submission_email'] 				= 'enabled';
            $badgeos_settings['debug_mode']       				= 'disabled';
            $badgeos_settings['ranks_main_post_type']       	= 'rank_types';
            $badgeos_settings['ranks_step_post_type']       	= 'rank_requirement';

            $badgeos_settings['points_main_post_type']     		= $point_type;
            $badgeos_settings['points_award_post_type']    		= 'point_award';
            $badgeos_settings['points_deduct_post_type']   		= 'point_deduct';
			$badgeos_settings['date_of_birth_from']   			= 'profile';
            $badgeos_settings['remove_data_on_uninstall']   	= null;

            $badgeos_settings['badgeos_achievement_global_image_width']    	= '50';
            $badgeos_settings['badgeos_achievement_global_image_height']    = '50';
            $badgeos_settings['badgeos_rank_global_image_width']    		= '50';
            $badgeos_settings['badgeos_rank_global_image_height']    		= '50';
			$badgeos_settings['badgeos_point_global_image_width']    		= '32';
            $badgeos_settings['badgeos_point_global_image_height']    		= '32';
			badgeos_utilities::update_option( 'badgeos_settings', $badgeos_settings );
		}

        if ( empty( $badgeos_admin_tools ) ) {
            $badgeos_admin_tools['badgeos_tools_email_logo_url']   = '';
            $badgeos_admin_tools['badgeos_tools_email_logo_dir']   = '';
			$badgeos_admin_tools['email_general_footer_text']   = '';
			$badgeos_admin_tools['allow_unsubscribe_email']   = 'No';
			$badgeos_admin_tools['unsubscribe_email_page']   = '0';
            $badgeos_admin_tools['email_general_from_name']   = get_bloginfo( 'name' );
			$badgeos_admin_tools['email_general_from_email']   = get_bloginfo( 'admin_email' );
			$badgeos_admin_tools['email_general_cc_list']   = '';
			$badgeos_admin_tools['email_general_bcc_list']   = '';

			$badgeos_admin_tools['email_general_background_color']   		= '#ffffff';
			$badgeos_admin_tools['email_general_body_background_color']   	= '#f6f6f6';
			$badgeos_admin_tools['email_general_body_text_color']   		= '#000000';
			$badgeos_admin_tools['email_general_footer_background_color']   = '#ffffff';
			$badgeos_admin_tools['email_general_footer_text_color']   		= '#000000';

            $badgeos_admin_tools['email_disable_earned_achievement_email']   = 'yes';
            $badgeos_admin_tools['email_achievement_subject']   = __( 'Congratulation for earning an achievement', 'badgeos' );
			$badgeos_admin_tools['email_achievement_cc_list']   = '';
			$badgeos_admin_tools['email_achievement_bcc_list']   = '';
            $email_achievement_content =  '<p>'.__( 'Dear', 'badgeos' ).' [user_name]</p>';
            $email_achievement_content .= '<p>'.__( 'You have earned a new achievement i.e. "[achievement_title]". [points] points are also added in your point balance.', 'badgeos' ).'</p>';
            $email_achievement_content .= '<p>[achievement_image]</p>';
            $badgeos_admin_tools['email_achievement_content']   = $email_achievement_content;

            $badgeos_admin_tools['email_disable_achievement_steps_email']   = 'yes';
            $badgeos_admin_tools['email_steps_achievement_subject']   = __( 'Congratulation for earning an achievement step', 'badgeos' );
			$badgeos_admin_tools['email_achievement_steps_cc_list']   = '';
			$badgeos_admin_tools['email_achievement_steps_bcc_list']   = '';
            $email_steps_achievement_content =  '<p>'.__( 'Dear', 'badgeos' ).' [user_name]</p>';
            $email_steps_achievement_content .= '<p>'.__( 'You have earned a new achievement step i.e. "[step_title]".', 'badgeos' ).'</p>';
            $email_steps_achievement_content .= '<p>'.__( 'Thanks.', 'badgeos' ).'</p>';
            $badgeos_admin_tools['email_steps_achievement_content']   = $email_steps_achievement_content;

            $badgeos_admin_tools['email_disable_ranks_email']   = 'yes';
            $badgeos_admin_tools['email_ranks_subject']   = __( 'Congratulation for earning a rank', 'badgeos' );
			$badgeos_admin_tools['email_ranks_cc_list']   = '';
			$badgeos_admin_tools['email_ranks_bcc_list']   = '';
            $email_ranks_content =  '<p>'.__( 'Dear', 'badgeos' ).' [user_name]</p>';
            $email_ranks_content .= '<p>'.__( 'You have earned a new rank i.e. "[rank_title]".', 'badgeos' ).'</p>';
            $email_ranks_content .= '<p>'.__( 'Thanks.', 'badgeos' ).'</p>';
            $badgeos_admin_tools['email_ranks_content']   = $email_ranks_content;

            $badgeos_admin_tools['email_disable_rank_steps_email']   = 'yes';
            $badgeos_admin_tools['email_steps_rank_subject']   = __( 'Congratulation for earning a rank step', 'badgeos' );
			$badgeos_admin_tools['email_ranks_steps_cc_list']   = '';
			$badgeos_admin_tools['email_ranks_steps_bcc_list']   = '';
            $email_steps_rank_content =  '<p>'.__( 'Dear', 'badgeos' ).' [user_name]</p>';
            $email_steps_rank_content .= '<p>'.__( 'You have earned a new rank step i.e. "[rank_step_title]".', 'badgeos' ).'</p>';
            $email_steps_rank_content .= '<p>'.__( 'Thanks.', 'badgeos' ).'</p>';
            $badgeos_admin_tools['email_steps_rank_content']   = $email_steps_rank_content;
            $badgeos_admin_tools['email_disable_point_awards_email']   = 'yes';
            $badgeos_admin_tools['email_point_awards_subject']   = __( 'Congratulation for earning new points', 'badgeos' );

            $email_point_awards_content =  '<p>'.__( 'Dear', 'badgeos' ).' [user_name]</p>';
            $email_point_awards_content .= '<p>'.__( 'You have earned [credit] [point_title] at [date_earned].', 'badgeos' ).'</p>';
            $email_point_awards_content .= '<p>'.__( 'Thanks.', 'badgeos' ).'</p>';
            $badgeos_admin_tools['email_point_awards_content']   = $email_point_awards_content;
			$badgeos_admin_tools['email_point_awards_cc_list']   = '';
			$badgeos_admin_tools['email_point_awards_bcc_list']   = '';

            $badgeos_admin_tools['email_disable_point_deducts_email']   = 'yes';
            $badgeos_admin_tools['email_point_deducts_subject']   = '[credit] [point_title] '.__( 'are deducted', 'badgeos' );
            $email_point_deducts_content =  '<p>'.__( 'Dear', 'badgeos' ).' [user_name]</p>';
            $email_point_deducts_content .= '<p>[credit] [point_title] '.__( 'are deducted from your balace at', 'badgeos' ).' [date_earned].</p>';
            $email_point_deducts_content .= '<p>'.__( 'Thanks.', 'badgeos' ).'</p>';
            $badgeos_admin_tools['email_point_deducts_content']   = $email_point_deducts_content;
			$badgeos_admin_tools['email_point_deducts_cc_list']          = '';
            $badgeos_admin_tools['email_point_deducts_bcc_list']         = '';

			badgeos_utilities::update_option( 'badgeos_admin_tools', $badgeos_admin_tools );
        }
    }

	/**
	 * Include all our important files.
	 */
	function includes() {

        global $wp_version;

		require_once( $this->directory_path . 'includes/p2p/load.php' );
		require_once( $this->directory_path . 'includes/class.BadgeOS_Editor_Shortcodes.php' );
		require_once( $this->directory_path . 'includes/class.BadgeOS_Plugin_Updater.php' );
		require_once( $this->directory_path . 'includes/class.BadgeOS_Shortcode.php' );
		require_once( $this->directory_path . 'includes/utilities.php' );

        /**
         * WP blocks (page builder)
         */
        if (version_compare($wp_version, '5.0.0') >= 0) {
            require_once( $this->directory_path . 'includes/blocks/block-routes.php' );
            require_once( $this->directory_path . 'includes/blocks/blocks.php' );
            require_once( $this->directory_path . 'includes/blocks/src/init.php' );
        }

        if( badgeos_first_time_installed() ) {
            require_once( $this->directory_path . 'includes/class.Credly_Badge_Builder.php' );
        }
		
		require_once( $this->directory_path . 'includes/post-types.php' );
		require_once( $this->directory_path . 'includes/admin-settings.php' );
        require_once( $this->directory_path . 'includes/admin-tools.php' );
		require_once( $this->directory_path . 'includes/achievement-functions.php' );
		require_once( $this->directory_path . 'includes/activity-functions.php' );
		require_once( $this->directory_path . 'includes/ajax-functions.php' );
		require_once( $this->directory_path . 'includes/logging-functions.php' );
		require_once( $this->directory_path . 'includes/meta-boxes.php' );
		
		/**
		 * Move achievements from meta to db
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

        require_once( $this->directory_path . 'includes/open_badge/install-default-pages.php' );
        require_once( $this->directory_path . 'includes/open_badge/functions.php' );
        require_once( $this->directory_path . 'includes/open_badge/class-open-badge.php' );
        require_once( $this->directory_path . 'includes/open_badge/ob-metabox.php' );

        require_once( $this->directory_path . 'includes/ranks/rank-steps-ui.php' );
		require_once( $this->directory_path . 'includes/ranks/triggers.php' );
		require_once( $this->directory_path . 'includes/ranks/ranks-rules-engine.php' );
		
		require_once( $this->directory_path . 'includes/triggers.php' );
        require_once( $this->directory_path . 'includes/steps-ui.php' );
		require_once( $this->directory_path . 'includes/shortcodes.php' );
		require_once( $this->directory_path . 'includes/content-filters.php' );
		
		require_once( $this->directory_path . 'includes/rules-engine.php' );
		require_once( $this->directory_path . 'includes/user.php' );
        if( badgeos_first_time_installed() ) {
            require_once( $this->directory_path . 'includes/credly.php' );
            require_once( $this->directory_path . 'includes/credly-badge-builder.php' );
        }
        require_once( $this->directory_path . 'includes/open_badge/ob-integrations.php' );
		require_once( $this->directory_path . 'includes/widgets.php' );
        require_once( $this->directory_path . 'includes/posts-functions.php' );
		require_once( $this->directory_path . 'includes/badgeos-emails.php' );
		require_once( $this->directory_path . 'includes/assets.php' );
		require_once( $this->directory_path . 'includes/welcome.php' );
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
        wp_enqueue_style('badgeos-font-awesome', '//stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
        if( badgeos_first_time_installed() ) {
            wp_register_script( 'badgeos-credly', $this->directory_url . 'js/credly.js' );
        }

        wp_register_script( 'badgeos-achievements', $this->directory_url . 'js/badgeos-achievements.js', array( 'jquery' ), $this::$version, true );

        if( badgeos_first_time_installed() ) {
            wp_register_script( 'credly-badge-builder', $this->directory_url . 'js/credly-badge-builder.js', array( 'jquery' ), $this::$version, true );
		}
		
        wp_register_script( 'badgeos-ob-integrations', $this->directory_url . 'js/ob-integrations.js', array( 'jquery' ), $this::$version, true );
		if( badgeos_first_time_installed() ) {
			wp_register_script( 'badgeos-convert-credly-achievements', $this->directory_url . 'js/convert-credly-achievements.js', array( 'jquery' ), $this::$version, true );
		}
        $badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();

        $badgeos_tools_email_tab = '';
        if( isset( $_REQUEST['badgeos_tools_email_tab'] ) && !empty( $_REQUEST['badgeos_tools_email_tab'] ) ) {
            $badgeos_tools_email_tab = $_REQUEST['badgeos_tools_email_tab'];
        }

        $admin_js_translation_array = array(
            'ajax_url' 					=> admin_url( 'admin-ajax.php' ),
			'loading_img' 				=> admin_url( 'images/spinner.gif' ),
			'no_awarded_rank'           => __( 'No Awarded Ranks', 'badgeos' ),
            'no_awarded_achievement'    => __( 'No Awarded Achievements', 'badgeos' ),
            'revoke_rank'               => __( 'Revoke Rank', 'badgeos' ),
            'revoke_achievements'       => __( 'Revoke Achievements', 'badgeos' ),
            'no_available_achievements' => __( 'No Available Achievements', 'badgeos' ),
            'default_rank'              => __( 'Default Rank', 'badgeos' ),
            'achievement_post_type'     => $badgeos_settings['achievement_main_post_type'],
            'achievement_step_type'     => $badgeos_settings['achievement_step_post_type'],
            'badgeos_tools_email_tab'   => $badgeos_tools_email_tab

        );
        wp_localize_script( 'badgeos-admin-js', 'admin_js', $admin_js_translation_array );

		// Register styles
        wp_register_style( 'badgeos-jquery-ui-styles', $this->directory_url . 'css/jquery-ui.css' );
		wp_register_script('badgeos-jquery-ui-js', ('https://code.jquery.com/ui/1.12.1/jquery-ui.js'),"jquery", self::$version, true);
		
		wp_register_style( 'badgeos-jquery-slick-styles', $this->directory_url . 'js/slick/slick.css' );
		wp_register_style( 'badgeos-jquery-slick-theme-styles', $this->directory_url . 'js/slick/slick-theme.css' );
		wp_register_script('badgeos-jquery-slick-js', $this->directory_url . 'js/slick/slick.min.js',"jquery", self::$version, true);
		wp_register_script('badgeos-jquery-welcome-js', $this->directory_url . 'js/welcome.js',"jquery", self::$version, true);

		wp_register_script('badgeos-jquery-mini-colorpicker-js', $this->directory_url . 'js/jquery.minicolors.js',"jquery", self::$version, true);
		wp_register_style( 'badgeos-minicolorpicker_css', $this->directory_url.'css/jquery.minicolors.css', null, '' );
        wp_register_style( 'badgeos-admin-styles', $this->directory_url . 'css/admin.css', null, '' );

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
        $badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();

		if ( is_array( $achievement_types ) && ! empty( $achievement_types ) ) {
			foreach ( $achievement_types as $achievement_type ) {

				// Connect steps to each achievement type
				// Used to get an achievement's required steps (e.g. This badge requires these 3 steps)
				p2p_register_connection_type( array(
					'name'      => $badgeos_settings['achievement_step_post_type'].'-to-' . $achievement_type,
					'from'      => $badgeos_settings['achievement_step_post_type'],
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
					'name'      => $achievement_type . '-to-'.$badgeos_settings['achievement_step_post_type'],
					'from'      => $achievement_type,
					'to'        => $badgeos_settings['achievement_step_post_type'],
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
        $badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();

        $achievement_width = '50';
        if( isset( $badgeos_settings['badgeos_achievement_global_image_width'] ) && intval( $badgeos_settings['badgeos_achievement_global_image_width'] ) > 0 ) {
            $achievement_width = intval( $badgeos_settings['badgeos_achievement_global_image_width'] );
        }

        $achievement_height = '50';
        if( isset( $badgeos_settings['badgeos_achievement_global_image_height'] ) && intval( $badgeos_settings['badgeos_achievement_global_image_height'] ) > 0 ) {
            $achievement_height = intval( $badgeos_settings['badgeos_achievement_global_image_height'] );
        }
		
        add_image_size( 'boswp-badgeos-achievement', $achievement_width, $achievement_height );
    }

	/**
	 * Activation hook for the plugin.
	 */
	function activate() {
 
		// Include our important bits
		$this->includes();
        $point_type = 'point_type';

        $achievement_type = 'achievement-type';

		// Create Badges achievement type
		if ( !get_page_by_title( 'Badges', 'OBJECT', $achievement_type ) ) {
			$badge_post_id = wp_insert_post( array(
				'post_title'   => __( 'Badges', 'badgeos'),
				'post_content' => __( 'Badges badge type', 'badgeos' ),
				'post_status'  => 'publish',
				'post_author'  => 1,
				'post_type'    => $achievement_type,
			) );
			badgeos_utilities::update_post_meta( $badge_post_id, '_badgeos_singular_name', __( 'Badge', 'badgeos' ) );
			badgeos_utilities::update_post_meta( $badge_post_id, '_badgeos_show_in_menu', true );
		}

		// Setup default BadgeOS options
		$badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
		if ( empty( $badgeos_settings ) ) {
			$badgeos_settings['minimum_role']     				= 'manage_options';
            $badgeos_settings['submission_manager_role'] 		= 'manage_options';
            $badgeos_settings['submission_email'] 				= 'enabled';
            $badgeos_settings['debug_mode']       				= 'disabled';
            $badgeos_settings['achievement_main_post_type']     = $achievement_type;
            $badgeos_settings['achievement_step_post_type']     = 'step';
            $badgeos_settings['ranks_main_post_type']       	= 'rank_types';
            $badgeos_settings['ranks_step_post_type']       	= 'rank_requirement';

            $badgeos_settings['points_main_post_type']     		= $point_type;
            $badgeos_settings['points_award_post_type']    		= 'point_award';
            $badgeos_settings['points_deduct_post_type']   		= 'point_deduct';

            $badgeos_settings['remove_data_on_uninstall']   	= null;
			$badgeos_settings['date_of_birth_from']   			= 'profile';
            $badgeos_settings['badgeos_achievement_global_image_width']    	= '50';
            $badgeos_settings['badgeos_achievement_global_image_height']    = '50';
            $badgeos_settings['badgeos_rank_global_image_width']    		= '50';
            $badgeos_settings['badgeos_rank_global_image_height']    		= '50';

            badgeos_utilities::update_option( 'badgeos_settings', $badgeos_settings );
		}

		// Setup default Credly options
		$credly_settings = (array) badgeos_utilities::get_option( 'credly_settings', array() );

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
			//update_option( 'credly_settings', $credly_settings );
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
		$main_item_role = trim( $minimum_role );
			
		if( class_exists( 'BOS_Nomination_Submission' ) ) {
			$minimum_submission_role 	= badgeos_get_submission_manager_capability();
			$admin_role_num = array_search ( $minimum_role, $caps );
			$submission_role_num = array_search ( $minimum_submission_role, $caps );
			if( $submission_role_num > $admin_role_num ) {
				$main_item_role = $minimum_submission_role;
			}
			$main_item_role = trim( $main_item_role );
		}
		
		// Create main menu
        add_menu_page( 'BadgeOS', 'BadgeOS', $main_item_role, 'badgeos_badgeos', 'badgeos_settings', $this->directory_url . 'images/badgeos_icon.png', 110 );
		add_submenu_page( 'badgeos_badgeos', __( 'Welcome', 'badgeos' ), __( 'Welcome', 'badgeos' ), $minimum_role, 'badgeos-welcome', 'badgeos_welcome_page', 0 );
		
		// Create submenu items
		add_submenu_page( 'badgeos_badgeos', __( 'BadgeOS Settings', 'badgeos' ), __( 'Settings', 'badgeos' ), $minimum_role, 'badgeos_settings', 'badgeos_settings_page' );
		
		if( badgeos_first_time_installed() ) {
			add_submenu_page( 'badgeos_badgeos', __( 'Credly Integration', 'badgeos' ), __( 'Credly Integration', 'badgeos' ), $minimum_role, 'badgeos_sub_credly_integration', 'badgeos_credly_options_page' );
		}
		
		add_submenu_page( 'badgeos_badgeos', __( 'Assets', 'badgeos' ), __( 'Assets', 'badgeos' ), $minimum_role, 'badgeos-assets', 'badgeos_assets_page' );
		add_submenu_page( 'badgeos_badgeos', __( 'OB Integration', 'badgeos' ), __( 'OB Integration', 'badgeos' ), $minimum_role, 'badgeos-ob-integration', 'badgeos_ob_integration_page' );
		add_submenu_page( 'badgeos_badgeos', __( 'Add-Ons', 'badgeos' ), __( 'Add-Ons', 'badgeos' ), $minimum_role, 'badgeos_sub_add_ons', 'badgeos_add_ons_page' );
		add_submenu_page( 'badgeos_badgeos', __( 'Help / Support', 'badgeos' ), __( 'Help / Support', 'badgeos' ), $minimum_role, 'badgeos_sub_help_support', 'badgeos_help_support_page' );

        $settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
        if ( ! empty( $settings ) ) {
            $query = wp_count_posts( trim( $settings['ranks_main_post_type'] ) );
            $rank_types = get_posts( array(
                'post_type'         => trim( $settings['ranks_main_post_type'] ),
                'post_status'       => 'publish',
                'posts_per_page'    => -1,
            ) );

            $rank_menu = false;
            foreach( $rank_types as $rank_type ) {
                $show_in_menu = badgeos_utilities::get_post_meta( $rank_type->ID, '_badgeos_show_in_menu', true );
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
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-datepicker'); 

		if( $screen->id == 'badgeos_page_badgeos_tools' ) {
            wp_enqueue_script( 'badgeos-admin-tools-js' );
            $min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
            wp_enqueue_script( 'badgeos-select2', $this->directory_url . "js/select2/select2$min.js", array( 'jquery' ), '', true );
            wp_enqueue_style( 'badgeos-select2-css', $this->directory_url . 'js/select2/select2.css' );
        }

        wp_enqueue_style( 'badgeos-font-awesome' );
		wp_enqueue_script( 'badgeos-admin-js' );

		if( badgeos_first_time_installed() ) {
			wp_enqueue_script( 'badgeos-credly' );
		}

        wp_enqueue_script( 'badgeos-ob-integrations' );

		// Load styles
        wp_enqueue_style( 'badgeos-jquery-ui-styles' );
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

        wp_enqueue_style( 'badgeos-font-awesome' );
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
	$badgeos_settings = badgeos_utilities::get_option( 'badgeos_settings' );
	$debug_mode = ( !empty( $badgeos_settings['debug_mode'] ) ) ? $badgeos_settings['debug_mode'] : 'disabled';

	if ( $debug_mode == 'enabled' ) {
		return true;
	}

	return false;

}

/**
 * Helper function for writing wordpress log entries
 *
 *@return none
 */
if ( ! function_exists('badgeos_write_log')) {
    function badgeos_write_log ( $log )  {
        if ( is_array( $log ) || is_object( $log ) ) {
            error_log( print_r( $log, true ) );
        } else {
            error_log( $log );
        }
    }
}

/**
 * Check if credly is enabled
 *
 * @return bool
 */
function badgeos_first_time_installed() {
	require_once( plugin_dir_path( __FILE__ ) . 'includes/utilities.php' );
	$credly_settings = badgeos_utilities::get_option( 'credly_settings' );
	if ( isset( $credly_settings ) && is_array( $credly_settings ) && count( $credly_settings ) > 0 ) {
		return true;
	}

	return false;
}

/**
 * Check if credly is enabled
 *
 * @return bool
 */
function badgeos_get_meta_data( $type, $object_id, $meta_key = '', $single = false) {

	return badgeos_utilities::get_post_meta( $object_id, $meta_key, $single );
}
