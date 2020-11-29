<?php
/**
 * Admin Settings Pages
 *
 * @package BadgeOS
 * @subpackage Admin
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Register BadgeOS Settings with Settings API.
 * @return void
 */
function badgeos_register_settings() {
	register_setting( 'badgeos_settings_group', 'badgeos_settings', 'badgeos_settings_validate' );
	register_setting( 'credly_settings_group', 'credly_settings', 'badgeos_credly_settings_validate' );
}
add_action( 'admin_init', 'badgeos_register_settings' );

/**
 * Grant BadgeOS manager role ability to edit BadgeOS settings.
 *
 * @since  1.4.0
 *
 * @param  string $capability Required capability.
 * @return string             Required capability.
 */
function badgeos_edit_settings_capability( $capability ) {
	return badgeos_get_manager_capability();
}
add_filter( 'option_page_capability_badgeos_settings_group', 'badgeos_edit_settings_capability' );
add_filter( 'option_page_capability_credly_settings_group', 'badgeos_edit_settings_capability' );

/**
 * BadgeOS Settings validation
 *
 * @param  string $input The input we want to validate
 * @return string        Our sanitized input
 */
function badgeos_settings_validate( $input = '' ) {
	
    global $wpdb;
	// Fetch existing settings
	$original_settings = badgeos_utilities::get_option( 'badgeos_settings' );
	// Allow add-on settings to be sanitized
	do_action( 'badgeos_settings_validate', $input );
	
	$original_settings = apply_filters( "badgeos_before_validate_external_settings", $original_settings, $input );
	foreach( $input as $key=>$value ) {
		$original_settings[ $key ] = $value;
	}
	$original_settings = apply_filters( "badgeos_after_validate_external_settings", $original_settings, $input );

	switch( $input['tab_action'] ) {
		case "general":
			$original_settings['minimum_role'] = isset( $input['minimum_role'] ) ? sanitize_text_field( $input['minimum_role'] ) : $original_settings['minimum_role'];
			$original_settings['debug_mode'] = isset( $input['debug_mode'] ) ? sanitize_text_field( $input['debug_mode'] ) : $original_settings['debug_mode'];
			$original_settings['log_entries'] = isset( $input['log_entries'] ) ? sanitize_text_field( $input['log_entries'] ) : $original_settings['log_entries'];
			$original_settings['ms_show_all_achievements'] = isset( $input['ms_show_all_achievements'] ) ? sanitize_text_field( $input['ms_show_all_achievements'] ) : $original_settings['ms_show_all_achievements'];
			$original_settings['remove_data_on_uninstall'] = ( isset( $input['remove_data_on_uninstall'] ) && "on" == $input['remove_data_on_uninstall'] ) ? "on" : null;
			$original_settings['achievement_list_shortcode_default_view'] = isset( $input['achievement_list_shortcode_default_view'] ) ? sanitize_text_field( $input['achievement_list_shortcode_default_view'] ) : $original_settings['achievement_list_shortcode_default_view'];
			$original_settings['earned_achievements_shortcode_default_view'] = isset( $input['earned_achievements_shortcode_default_view'] ) ? sanitize_text_field( $input['earned_achievements_shortcode_default_view'] ) : $original_settings['earned_achievements_shortcode_default_view'];
			$original_settings['earned_ranks_shortcode_default_view'] = isset( $input['earned_ranks_shortcode_default_view'] ) ? sanitize_text_field( $input['earned_ranks_shortcode_default_view'] ) : $original_settings['earned_ranks_shortcode_default_view'];
			$original_settings['badgeos_achievement_global_image_width'] = isset( $input['badgeos_achievement_global_image_width'] ) ? sanitize_text_field( $input['badgeos_achievement_global_image_width'] ) : $original_settings['badgeos_achievement_global_image_width'];
			$original_settings['badgeos_achievement_global_image_height'] = isset( $input['badgeos_achievement_global_image_height'] ) ? sanitize_text_field( $input['badgeos_achievement_global_image_height'] ) : $original_settings['badgeos_achievement_global_image_height'];
			$original_settings['badgeos_rank_global_image_width'] = isset( $input['badgeos_rank_global_image_width'] ) ? sanitize_text_field( $input['badgeos_rank_global_image_width'] ) : $original_settings['badgeos_rank_global_image_width'];
			$original_settings['badgeos_rank_global_image_height'] = isset( $input['badgeos_rank_global_image_height'] ) ? sanitize_text_field( $input['badgeos_rank_global_image_height'] ) : $original_settings['badgeos_rank_global_image_height'];
			$original_settings['badgeos_point_global_image_width'] = isset( $input['badgeos_point_global_image_width'] ) ? sanitize_text_field( $input['badgeos_point_global_image_width'] ) : $original_settings['badgeos_point_global_image_width'];
			$original_settings['badgeos_point_global_image_height'] = isset( $input['badgeos_point_global_image_height'] ) ? sanitize_text_field( $input['badgeos_point_global_image_height'] ) : $original_settings['badgeos_point_global_image_height'];
			break;
		case "ptypes":
			
			if( ! empty( $input['achievement_step_post_type'] ) && trim( $original_settings['achievement_step_post_type'] ) != trim( $input['achievement_step_post_type'] )  ) {
				$achievement_step_post_type = sanitize_text_field( str_replace( ' ', '_', trim( $input[ 'achievement_step_post_type' ] ) ) );
				$strQuery = "update ".$wpdb->prefix . "badgeos_achievements set post_type='".trim( $achievement_step_post_type )."' where post_type='".trim( $original_settings['achievement_step_post_type'] )."'";
				$wpdb->query( $strQuery );
		
				$strQuery = "update ".$wpdb->prefix . "posts set post_type='".trim( $achievement_step_post_type )."' where post_type='".trim( $original_settings['achievement_step_post_type'] )."'";
				$wpdb->query( $strQuery );
		
				badgeos_settings_update_p2p( $achievement_step_post_type, trim( $original_settings['achievement_step_post_type'] ), 'step' );
				$original_settings['achievement_step_post_type'] = !empty( $input[ 'achievement_step_post_type' ] ) ? $achievement_step_post_type : 	( isset( $original_settings['achievement_step_post_type'] )?$original_settings['achievement_step_post_type']:'step' );
			}
		
			if( ! empty( $input['achievement_main_post_type'] ) && trim( $original_settings['achievement_main_post_type'] ) != trim( $input['achievement_main_post_type'] )  ) {
				$achievement_main_post_type = sanitize_text_field( str_replace( ' ', '_', trim( $input[ 'achievement_main_post_type' ] ) ) );
				$strQuery = "update ".$wpdb->prefix . "badgeos_achievements set post_type='".trim( $achievement_main_post_type )."' where post_type='".trim( $original_settings['achievement_main_post_type'] )."'";
				$wpdb->query( $strQuery );
		
				$strQuery = "update ".$wpdb->prefix . "posts set post_type='".trim( $achievement_main_post_type )."' where post_type='".trim( $original_settings['achievement_main_post_type'] )."'";
				$wpdb->query( $strQuery );
				badgeos_settings_update_p2p( $input['achievement_main_post_type'], trim( $original_settings['achievement_main_post_type'] ), 'main' );
				$original_settings['achievement_main_post_type'] = !empty( $achievement_main_post_type ) 		? $achievement_main_post_type : 	( isset( $original_settings['achievement_main_post_type'] )?$original_settings['achievement_main_post_type']:'achievement-type' );
			}
		
			if( ! empty( $input['points_main_post_type'] ) && trim( $original_settings['points_main_post_type'] ) != trim( $input['points_main_post_type'] )  ) {
				$points_main_post_type = sanitize_text_field( str_replace( ' ', '_', trim( $input[ 'points_main_post_type' ] ) ) );
				$strQuery = "update ".$wpdb->prefix . "posts set post_type='".trim( $points_main_post_type )."' where post_type='".trim( $original_settings['points_main_post_type'] )."'";
				$wpdb->query( $strQuery );
		
				badgeos_settings_update_p2p( $points_main_post_type, trim( $original_settings['points_main_post_type'] ), 'main' );
				$original_settings['points_main_post_type'] = !empty( $points_main_post_type ) 	? $points_main_post_type : 	( isset( $original_settings['points_main_post_type'] )?$original_settings['points_main_post_type']:'point_type' );
			}

			if( ! empty( $input['points_award_post_type'] ) && trim( $original_settings['points_award_post_type'] ) != trim( $input['points_award_post_type'] )  ) {
				$points_award_post_type = sanitize_text_field( str_replace( ' ', '_', trim( $input[ 'points_award_post_type' ] ) ) );
				$strQuery = "update ".$wpdb->prefix . "posts set post_type='".trim( $points_award_post_type )."' where post_type='".trim( $original_settings['points_award_post_type'] )."'";
				$wpdb->query( $strQuery );
		
				badgeos_settings_update_p2p( $points_award_post_type, trim( $original_settings['points_award_post_type'] ), 'step' );
				$original_settings['points_award_post_type'] = !empty( $points_award_post_type )	? $points_award_post_type :	( isset( $original_settings['points_award_post_type'] )?$original_settings['points_award_post_type']:'point_award' );
			}

			if( ! empty( $input['points_deduct_post_type'] ) && trim( $original_settings['points_deduct_post_type'] ) != trim( $input['points_deduct_post_type'] )  ) {
				$points_deduct_post_type = sanitize_text_field( str_replace( ' ', '_', trim( $input[ 'points_deduct_post_type' ] ) ) );
				$strQuery = "update ".$wpdb->prefix . "posts set post_type='".trim( $points_deduct_post_type )."' where post_type='".trim( $original_settings['points_deduct_post_type'] )."'";
				$wpdb->query( $strQuery );
		
				badgeos_settings_update_p2p( $points_deduct_post_type, trim( $original_settings['points_deduct_post_type'] ), 'step' );
				$original_settings['points_deduct_post_type'] = !empty( $points_deduct_post_type ) ? $points_deduct_post_type : ( isset( $original_settings['points_deduct_post_type'] )?$original_settings['points_deduct_post_type']:'point_deduct' );
			}
		
			if( ! empty( $input['ranks_main_post_type'] ) && trim( $original_settings['ranks_main_post_type'] ) != trim( $input['ranks_main_post_type'] )  ) {
				
				$ranks_main_post_type = sanitize_text_field( str_replace( ' ', '_', trim( $input[ 'ranks_main_post_type' ] ) ) );
				
				$strQuery = "update ".$wpdb->prefix . "badgeos_ranks set rank_type='".trim( $ranks_main_post_type )."' where rank_type='".trim( $original_settings['ranks_main_post_type'] )."'";
				$wpdb->query( $strQuery );
		
				$strQuery = "update ".$wpdb->prefix . "posts set post_type='".trim( $ranks_main_post_type )."' where post_type='".trim( $original_settings['ranks_main_post_type'] )."'";
				$wpdb->query( $strQuery );
		
				badgeos_settings_update_p2p( $ranks_main_post_type, trim( $original_settings['ranks_main_post_type'] ), 'main' );
				$original_settings['ranks_main_post_type'] 	= !empty( $ranks_main_post_type ) 		? $ranks_main_post_type : 	( isset( $original_settings['ranks_main_post_type'] )?$original_settings['ranks_main_post_type']:'ranks' );
			}
		
			if( ! empty( $input['ranks_step_post_type'] ) && trim( $original_settings['ranks_step_post_type'] ) != trim( $input['ranks_step_post_type'] )  ) {
				$ranks_step_post_type = sanitize_text_field( str_replace( ' ', '_', trim( $input[ 'ranks_step_post_type' ] ) ) );
				$strQuery = "update ".$wpdb->prefix . "badgeos_ranks set rank_type='".trim( $ranks_step_post_type )."' where rank_type='".trim( $original_settings['ranks_step_post_type'] )."'";
				$wpdb->query( $strQuery );
		
				$strQuery = "update ".$wpdb->prefix . "posts set post_type='".trim( $ranks_step_post_type )."' where post_type='".trim( $original_settings['ranks_step_post_type'] )."'";
				$wpdb->query( $strQuery );
		
				badgeos_settings_update_p2p( $ranks_step_post_type, trim( $original_settings['ranks_step_post_type'] ), 'step' );
				$original_settings['ranks_step_post_type'] = !empty( $ranks_step_post_type ) 		? $ranks_step_post_type : 	( isset( $original_settings['ranks_step_post_type'] )?$original_settings['ranks_step_post_type']:'rank_requirement' );
			
			} 
			break;
	}

	
	$original_settings = apply_filters( "badgeos_validate_setting_page_data", $original_settings, $input );
	// Sanitize the settings data submitted
	return $original_settings;
}

/**
 * Credly API Settings validation
 * @since  1.0.0
 * @param  array $options Form inputs data
 * @return array          Sanitized form input data
 */
function badgeos_settings_update_p2p( $new_post_type, $old_post_type, $type ) {
    global $wpdb;

    if( $type == 'main' ) {
        $strQuery = "select * from ".$wpdb->prefix . "p2p where p2p_type like '%-to-".trim( $old_post_type )."'";
    } elseif( $type == 'step' ) {
        $strQuery = "select * from ".$wpdb->prefix . "p2p where p2p_type like '".trim( $old_post_type )."-to-%'";
    }

    $records = $wpdb->get_results( $strQuery );
    foreach( $records as $rec ) {
        $new_value = '';
        if( $type == 'main' ) {
            $new_value = str_replace( "-to-".trim( $old_post_type ), "-to-".trim( $new_post_type ), $rec->p2p_type );
        } elseif( $type == 'step' ) {
            $new_value = str_replace( trim( $old_post_type )."-to-", trim( $new_post_type )."-to-", $rec->p2p_type );
        }
        if( !empty( $new_value ) ) {
            $strQuery = "update ".$wpdb->prefix . "p2p set p2p_type='".trim( $new_value )."' where p2p_id='".trim( $rec->p2p_id )."'";

            $wpdb->query( $strQuery );
        }
    }
}

/**
 * Credly API Settings validation
 * @since  1.0.0
 * @param  array $options Form inputs data
 * @return array          Sanitized form input data
 */
function badgeos_credly_settings_validate( $options = array() ) {

	// If attempting to retrieve an api key from credly
	if (
		empty( $options['api_key'] )
		&& isset( $_POST['badgeos_credly_api_key_nonce'] )
		&& wp_verify_nonce( $_POST['badgeos_credly_api_key_nonce'], 'badgeos_credly_api_key_nonce' )
		&& 'false' !== $options['credly_enable'] // Only continue if credly is enabled
	) {
		// sanitize
		$username = ( !empty( $options['credly_user'] ) ? sanitize_text_field( $options['credly_user'] ) : '' );
		$password = ( !empty( $options['credly_password'] ) ? sanitize_text_field( $options['credly_password'] ) : '' );

		if ( ! is_email( $username ) || empty( $password ) ) {

			$error = '';

			if ( ! is_email( $username ) )
				$error .= '<p>'. __( 'Please enter a valid email address in the username field.', 'badgeos' ). '</p>';

			if ( empty( $password ) )
				$error .= '<p>'. __( 'Please enter a password.', 'badgeos' ). '</p>';

			// Save our error message.
			badgeos_credly_get_api_key_error( $error );

			// Keep the user/pass
			$clean_options['credly_user'] = $username;
			$clean_options['credly_password'] = $password;
		} else {
			unset( $options['api_key'] );
			$clean_options['api_key'] = badgeos_credly_get_api_key( $username, $password );
			// No key?
			if ( !$clean_options['api_key'] ) {
				// Keep the user/pass
				$clean_options['credly_user'] = $username;
				$clean_options['credly_password'] = $password;
			}
		}

	}

	// we're not saving these values
	unset( $options['credly_user'] );
	unset( $options['credly_password'] );
	// sanitize all our options
	foreach ( $options as $key => $opt ) {
		$clean_options[$key] = sanitize_text_field( $opt );
	}

	return $clean_options;

}

/**
 * Retrieves credly api key via username/password
 * @since  1.0.0
 * @param  string $username Credly user email
 * @param  string $password Credly user passowrd
 * @return string           API key on success, false otherwise
 */
function badgeos_credly_get_api_key( $username = '', $password = '' ) {

	$url = BADGEOS_CREDLY_API_URL . 'authenticate/';

	$response = wp_remote_post( $url, array(
		'headers' => array(
			'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password )
		),
		'sslverify' => false
	) );

	// If the response is a WP error
	if ( is_wp_error( $response ) ) {
		$error = '<p>'. sprintf( __( 'There was an error getting a Credly API Key: %s', 'badgeos' ), $response->get_error_message() ) . '</p>';
		return badgeos_credly_get_api_key_error( $error );
	}

	// If the response resulted from potentially bad credentials
	if ( '401' == wp_remote_retrieve_response_code( $response ) ) {
		$error = '<p>'. __( 'There was an error getting a Credly API Key: Please check your username and password.', 'badgeos' ) . '</p>';
		// Save our error message.
		return badgeos_credly_get_api_key_error( $error );
	}

	$api_key = json_decode( $response['body'] );
	$api_key = $api_key->data->token;

	return $api_key;

}

/**
 * Saves an error string for display on Credly settings page
 * @since  1.0.0
 * @param  string $error Error message
 * @return bool          False after updating option
 */
function badgeos_credly_get_api_key_error( $error = '' ) {

	// Temporarily store our error message.
	badgeos_utilities::update_option( 'credly_api_key_error', $error );
	return false;
}

add_action( 'all_admin_notices', 'badgeos_credly_api_key_errors' );
/**
 * Displays error messages from Credly API key retrieval
 * @since  1.0.0
 * @return void
 */
function badgeos_credly_api_key_errors() {

	if ( get_current_screen()->id != 'badgeos_page_badgeos_sub_credly_integration' || !( $has_notice = badgeos_utilities::get_option( 'credly_api_key_error' ) ) )
		return;

	// If we have an error message, we'll display it
	echo '<div id="message" class="error">'. $has_notice .'</div>';
	// and then delete it
	badgeos_utilities::delete_option( 'credly_api_key_error' );
}

/**
 * BadgeOS main settings page output
 * @since  1.0.0
 * @return void
 */
function badgeos_settings_page() {
	wp_enqueue_style( 'badgeos-jquery-ui-styles' );
	wp_enqueue_style( 'badgeos-admin-styles' );
	wp_enqueue_script( 'badgeos-jquery-ui-js' );
	wp_enqueue_script( 'badgeos-admin-tools-js' );
	$licensed_addons = apply_filters( 'badgeos_licensed_addons', array() );
	$setting_page_tab = isset( $_GET['bos_s_tab'] ) ? $_GET['bos_s_tab'] : 'general';
	if( !isset( $setting_page_tab ) || empty( $setting_page_tab ) ) {
		$setting_page_tab = 'general';
	}
	$base_tabs = [ 'general', 'ptypes', 'dupgrades', 'licenses' ];
	?>
	<div class="wrap badgeos-tools-page">
		<div id="icon-options-general" class="icon32"></div>
		<h2><?php _e( 'BadgeOS Settings', 'badgeos' ); ?></h2>

		<div class="nav-tab-wrapper">
			<a href="admin.php?page=badgeos_settings&bos_s_tab=general" class="nav-tab <?php echo ( $setting_page_tab == 'general' || empty( $setting_page_tab ) ) ? 'nav-tab-active' : ''; ?>">
				<i class="fa fa-shield" aria-hidden="true"></i>
				<?php _e( 'General', 'badgeos' ); ?>
			</a>
			<a href="admin.php?page=badgeos_settings&bos_s_tab=ptypes" class="nav-tab <?php echo $setting_page_tab == 'ptypes'? 'nav-tab-active' : ''; ?>">
				<i class="fa fa-shield" aria-hidden="true"></i>
				<?php _e( 'Post Types', 'badgeos' ); ?>
			</a>
			<a href="admin.php?page=badgeos_settings&bos_s_tab=dupgrades" class="nav-tab <?php echo $setting_page_tab == 'dupgrades'? 'nav-tab-active' : ''; ?>">
				<i class="fa fa-shield" aria-hidden="true"></i>
				<?php _e( 'Data Upgrade', 'badgeos' ); ?>
			</a>
			<?php do_action( 'badgeos_settings_main_tab_header', $setting_page_tab ); ?>
			<?php if( count( $licensed_addons ) ) { ?>
				<a href="admin.php?page=badgeos_settings&bos_s_tab=licenses" class="nav-tab <?php echo $setting_page_tab == 'licenses'? 'nav-tab-active' : ''; ?>">
					<i class="fa fa-shield" aria-hidden="true"></i>
					<?php _e( 'Licenses', 'badgeos' ); ?>
				</a>
			<?php } ?>
		</div>
		<?php if( in_array( $setting_page_tab, $base_tabs ) ) {
			include( 'settings/' . $setting_page_tab . '.php' );
		} else { ?> 
			<?php do_action( 'badgeos_settings_main_tab_content', $setting_page_tab ); ?>
		<?php } ?>
	</div>
	<?php
}


/**
 * Adds additional options to the BadgeOS Settings page
 *
 * @since 1.0.0
 */
function badgeos_license_settings() {

	// Get our licensed add-ons
	$licensed_addons = apply_filters( 'badgeos_licensed_addons', array() );

	// If we have any licensed add-ons
	if ( ! empty( $licensed_addons ) ) {

		// Output the header for licenses
		echo '<tr><td colspan="2"><hr/><h2>' . __( 'BadgeOS Add-on Licenses', 'badgeos' ) . '</h2></td></tr>';

		// Sort our licenses alphabetially
		ksort( $licensed_addons );

		// Output each individual licensed product
		foreach ( $licensed_addons as $slug => $addon ) {
			$status = ! empty( $addon['license_status'] ) ? $addon['license_status'] : 'inactive';
			echo '<tr valign="top">';
			echo '<th scope="row">';
			echo '<label for="badgeos_settings[licenses][' . $slug . ']">' . urldecode( $addon['item_name'] ) . ': </label></th>';
			echo '<td>';
			echo '<input type="text" size="30" name="badgeos_settings[licenses][' . $slug . ']" id="badgeos_settings[licenses][' . $slug . ']" value="' . $addon['license'] . '" />';
			echo ' <span class="badgeos-license-status ' . $status . '">' . sprintf( __( 'License Status: %s' ), '<strong>' . ucfirst( $status ) . '</strong>' ) . '</span>';
			echo '</td>';
			echo '</tr>';
		}
	}

}
add_action( 'badgeos_license_settings', 'badgeos_license_settings', 0 );

/**
 * Add-ons settings page
 *
 * @since  1.0.0
 */
function badgeos_add_ons_page() {
	$image_url = $GLOBALS['badgeos']->directory_url .'images/';
	?>
	<div class="wrap badgeos-addons">
		<div id="icon-options-general" class="icon32"></div>
		<h2><?php printf( __( 'BadgeOS Add-Ons &nbsp;&mdash;&nbsp; %s', 'badgeos' ), '<a href="http://badgeos.org/add-ons/?ref=badgeos" class="button-primary" target="_blank">' . __( 'Browse All Add-Ons', 'badgeos' ) . '</a>' ); ?></h2>
		<p><?php _e( 'These add-ons extend the functionality of BadgeOS.', 'badgeos' ); ?></p>
		<?php echo badgeos_add_ons_get_feed(); ?>
	</div>
	<?php
}

/**
 * Get all add-ons from the BadgeOS catalog feed.
 *
 * @since  1.2.0
 * @return string Concatenated markup from feed, or error message
*/
function badgeos_add_ons_get_feed() {

	// Attempt to pull back our cached feed
	$feed = get_transient( 'badgeos_add_ons_feed' );

	// If we don't have a cached feed, pull back fresh data
	if ( empty( $feed ) ) {

		// Retrieve and parse our feed
		$feed = wp_remote_get( 'http://badgeos.org/?feed=addons', array( 'sslverify' => false ) );
		if ( ! is_wp_error( $feed ) ) {
			if ( isset( $feed['body'] ) && strlen( $feed['body'] ) > 0 ) {
				$feed = wp_remote_retrieve_body( $feed );
				$feed = str_replace( '<html><body>', '', $feed );
				$feed = str_replace( '</body></html>', '', $feed );
				// Cache our feed for 1 hour
				set_transient( 'badgeos_add_ons_feed', $feed, HOUR_IN_SECONDS );
			}
		} else {
			$feed = '<div class="error"><p>' . __( 'There was an error retrieving the add-ons list from the server. Please try again later.', 'badgeos' ) . '</div>';
		}
	}

	// Return our feed, or error message
	return $feed;
}

/**
 * Help and Support settings page
 * @since  1.0.0
 * @return void
 */
function badgeos_help_support_page() { ?>
	<div class="wrap" >
		<div id="icon-options-general" class="icon32"></div>
		<h2><?php _e( 'BadgeOS Help and Support', 'badgeos' ); ?></h2>
		<h2><?php _e( 'About BadgeOS', 'badgeos' ); ?>:</h2>
		<p><?php printf(
			__( 'BadgeOS&trade; is plugin to WordPress that allows your site\'s users to complete tasks, demonstrate achievements, and earn badges. You define the achievement types, organize your requirements any way you like, and choose from a range of options to determine whether each task or requirement has been achieved. Badges earned in BadgeOS are Mozilla OBI compatible through out-of-the-box integration of the "Open Credit" API by %s, the free web service for issuing, earning and sharing badges.', 'badgeos' ),
			'<a href="https://credly.com/" target="_blank">Credly</a>'
		); ?></p>
		<p><?php printf(
			__( "BadgeOS is extremely extensible. Check out examples of what we've built with it, and stay connected to the project site for updates, add-ins and news. Share your ideas and code improvements on %s so we can keep making BadgeOS better for everyone.", 'badgeos' ),
			'<a href="https://github.com/opencredit/BadgeOS" target="_blank">GitHub</a>'
		); ?></p>
		<?php do_action( 'badgeos_help_support_page_about' ); ?>

		<h2><?php _e( 'Help / Support', 'badgeos' ); ?>:</h2>
		<p><?php printf(
			__( 'For support on using BadgeOS or to suggest feature enhancements, visit the %1$s. The BadgeOS team does perform custom development that extends the BadgeOS platform in some incredibly powerful ways. %2$s with inquiries. See examples of %3$s.', 'badgeos' ),
			sprintf(
				'<a href="http://badgeos.org" target="_blank">%s</a>',
				__( 'BadgeOS site', 'badgeos' )
			),
			sprintf(
				'<a href="http://badgeos.org/contact/" target="_blank">%s</a>',
				__( 'Contact us', 'badgeos' )
			),
			sprintf(
				'<a href="http://badgeos.org/about/sample-sites/">%s</a>',
				__( 'enhanced BadgeOS projects', 'badgeos' )
			)
		); ?></p>
		<p><?php printf( __( 'Please submit bugs or issues to %s for the BadgeOS Project.', 'badgeos' ), '<a href="https://github.com/opencredit/BadgeOS" target="_blank">Github</a>' ); ?></p>
		<?php do_action( 'badgeos_help_support_page_help' ); ?>

		<h2><?php _e( 'Shortcodes', 'badgeos' ); ?>:</h2>
		<p><?php printf(
			__( 'With BadgeOS activated, the following shortcodes can be placed on any page or post within WordPress to expose a variety of BadgeOS functions. Visit %s for additional information on shortcodes.', 'badgeos' ),
			'<a href="http://badgeos.org/support/shortcodes/" target="_blank">BadgeOS.org</a>'
		); ?></p>
		<?php do_action( 'badgeos_help_support_page_shortcodes' ); ?>
	</div>
	<?php
}

/**
 * BadgeOS Credly Integration settings page.
 * @since  1.0.0
 * @return void
 */
function badgeos_credly_options_page() {

	/**
	 * @var $badgeos_credly BadgeOS_Credly
	 */
	global $badgeos_credly;

	$credly_settings = $badgeos_credly->credly_settings;
?>
	<div class="wrap" >
		<div id="icon-options-general" class="icon32"></div>
		<h2><?php _e( 'Credly Integration Settings', 'badgeos' ); ?></h2>
		<?php settings_errors(); ?>

		<form method="post" action="options.php">
			<?php
				settings_fields( 'credly_settings_group' );
			?>
            <div class="box-credly-alert">
                <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
                <?php _e( "It's time! Migrate from Classic Credly now and take advantage of the all new stand alone BadgeOS plugin - <a href='https://badgeos.org/good-news-what-credly-sunsetting-means-for-badgeos-users/'>Read more!</a> ", "badgeos" ); ?>
            </div>
            <p><?php printf( __( '<a href="%1$s" target="_blank">Credly</a> is a universal way for people to earn and showcase their achievements and badges. With Credly Integration enabled here, badges or achievements you create on this BadgeOS site can automatically be created on your Credly account. As select badges are earned using BadgeOS, the badge will automatically be issued via Credly to the earner so they can easily share it on Facebook, LinkedIn, Twitter, Mozilla Backpack, their web site, blog, Credly profile or other location. Credly makes badge issuing and sharing fun and easy! <a href="%1$s" target="_blank">Learn more</a>.  <br /><br />If you do not yet have a Credly account, <a href="%1$s" target="_blank">create one now</a>. It\'s free.', 'badgeos' ), 'https://credly.com/#!/create-account' ); ?></p>

			<table class="form-table">
				<tr valign="top">
					<th scope="row">
						<label for="credly_enable"><?php _e( 'Enable Badge Sharing via Credly: ', 'badgeos' ); ?></label>
					</th>
					<td>
						<select id="credly_enable" name="credly_settings[credly_enable]">
							<option value="false" <?php selected( $credly_settings['credly_enable'], 'false' ); ?>><?php _e( 'No', 'badgeos' ) ?></option>
							<option value="true" <?php selected( $credly_settings['credly_enable'], 'true' ); ?>><?php _e( 'Yes', 'badgeos' ) ?></option>
						</select>
					</td>
				</tr>
			</table>

			<?php
				// We need to get our api key
				if ( empty( $credly_settings['api_key'] ) ) {
					badgeos_credly_options_no_api( $credly_settings );
				}
				// We already have our api key
				else {
					badgeos_credly_options_yes_api( $credly_settings );
				}

				submit_button( __( 'Save Settings', 'badgeos' ) );
			?>
		</form>
	</div>
<?php

}

/**
 * BadgeOS Credly API key retrieval form.
 * @since  1.0.0
 * @param  array $credly_settings saved settings
 * @return void
 */
function badgeos_credly_options_no_api( $credly_settings = array() ) {

	wp_nonce_field( 'badgeos_credly_api_key_nonce', 'badgeos_credly_api_key_nonce' );

	if ( is_array( $credly_settings ) ) {
		foreach ( $credly_settings as $key => $opt ) {
			if ( in_array( $key, array( 'credly_user', 'credly_password', 'api_key', 'credly_enable' ) ) ) {
				continue;
			}

			// Save our hidden form values
			echo '<input type="hidden" name="credly_settings['. esc_attr( $key ) .']" value="'. esc_attr( $opt ) .'" />';
		}
	}
?>
	<div id="credly-settings">
		<h3><?php _e( 'Get Credly API Key', 'badgeos' ); ?></h3>

		<p class="toggle"><?php _e( 'Enter your Credly account username and password to access your API key.', 'badgeos' ); ?></p>

		<p class="toggle hidden"><?php printf( __( 'Enter your %s to access your API key.', 'badgeos' ), '<a href="#show-api-key">'. __( 'Credly account username and password', 'badgeos' ) .'</a>' ); ?></p>

		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<label for="credly_user"><?php _e( 'Username: ', 'badgeos' ); ?></label>
				</th>
				<td>
					<input id="credly_user" type="text" name="credly_settings[credly_user]" class="widefat" value="<?php echo esc_attr( $credly_settings[ 'credly_user' ] ); ?>" style="max-width: 400px;" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="credly_password"><?php _e( 'Password: ', 'badgeos' ); ?></label>
				</th>
				<td>
					<input id="credly_password" type="password" name="credly_settings[credly_password]" class="widefat" value="<?php echo esc_attr( $credly_settings[ 'credly_password' ] ); ?>" style="max-width: 400px;" />
				</td>
			</tr>
			<tr valign="top" class="hidden">
				<th scope="row">
					<label for="api_key"><?php _e( 'API Key: ', 'badgeos' ); ?></label>
				</th>
				<td>
					<input id="api_key" type="text" name="credly_settings[api_key]" class="widefat" value="<?php echo esc_attr( $credly_settings[ 'api_key' ] ); ?>" style="max-width: 1000px;" />
				</td>
			</tr>
		</table>

		<p class="toggle"><?php printf( __( 'Already have your API key? %s', 'badgeos' ), '<a href="#show-api-key">'. __( 'Click here', 'badgeos' ) .'</a>' ); ?></p>
	</div>
<?php

}

/**
 * BadgeOS Credly Settings form (when API key has been saved).
 * @since  1.0.0
 * @param  array $credly_settings saved settings
 * @return void
 */
function badgeos_credly_options_yes_api( $credly_settings = array() ) {

?>
	<div id="credly-settings">
		<h3><?php _e( 'Credly API Key', 'badgeos' ); ?></h3>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="api_key"><?php _e( 'API Key: ', 'badgeos' ); ?></label></th>
				<td>
					<input id="api_key" type="text" name="credly_settings[api_key]" class="widefat" value="<?php echo esc_attr( $credly_settings[ 'api_key' ] ); ?>" />
				</td>
			</tr>
		</table>

		<h3><?php _e( 'Credly Field Mapping', 'badgeos' ); ?></h3>

		<p><?php _e( 'Customize which Credly fields for badge creation and issuing (listed on the left) match which WordPress and BadgeOS fields (listed to the right).', 'badgeos' ); ?>
			<br /><?php _e( 'When badges are created and issued, the info sent to Credly will rely on the global mapping found here. (Note: Visit the edit screen for each achievement you create in BadgeOS to further configure the sharing and Credly settings for that achievement.)', 'badgeos' ); ?>
		</p>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="credly_badge_title"><?php _e( 'Badge Title: ', 'badgeos' ); ?></label></th>
				<td>
					<select id="credly_badge_title" name="credly_settings[credly_badge_title]">
						<?php echo credly_fieldmap_list_options( $credly_settings[ 'credly_badge_title' ] ); ?>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="credly_badge_short_description"><?php _e( 'Short Description: ', 'badgeos' ); ?></label>
				</th>
				<td>
					<select id="credly_badge_short_description" name="credly_settings[credly_badge_short_description]">
						<?php echo credly_fieldmap_list_options( $credly_settings[ 'credly_badge_short_description' ] ); ?>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="credly_badge_description"><?php _e( 'Description: ', 'badgeos' ); ?></label>
				</th>
				<td><select id="credly_badge_description" name="credly_settings[credly_badge_description]">
						<?php echo credly_fieldmap_list_options( $credly_settings[ 'credly_badge_description' ] ); ?>
					</select></td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="credly_badge_criteria"><?php _e( 'Criteria: ', 'badgeos' ); ?></label>
				</th>
				<td>
					<select id="credly_badge_criteria" name="credly_settings[credly_badge_criteria]">
						<?php echo credly_fieldmap_list_options( $credly_settings[ 'credly_badge_criteria' ] ); ?>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="credly_badge_image"><?php _e( 'Image: ', 'badgeos' ); ?></label>
				</th>
				<td>
					<select id="credly_badge_image" name="credly_settings[credly_badge_image]">
						<?php echo credly_fieldmap_list_options( $credly_settings[ 'credly_badge_image' ] ); ?>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="credly_badge_testimonial"><?php _e( 'Testimonial: ', 'badgeos' ); ?></label>
				</th>
				<td><select id="credly_badge_testimonial" name="credly_settings[credly_badge_testimonial]">
						<?php echo credly_fieldmap_list_options( $credly_settings[ 'credly_badge_testimonial' ] ); ?>
					</select></td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="credly_badge_evidence"><?php _e( 'Evidence: ', 'badgeos' ); ?></label>
				</th>
				<td>
					<select id="credly_badge_evidence" name="credly_settings[credly_badge_evidence]">
						<?php echo credly_fieldmap_list_options( $credly_settings[ 'credly_badge_evidence' ] ); ?>
					</select>
				</td>
			</tr>
		</table>

		<h3><?php _e( 'Credly Notification Settings', 'badgeos' ); ?></h3>
		<p><?php _e( 'Send custom notifications to users when they earn a Credly-enabled achievement.', 'badgeos' ); ?></p>

		<table class="form-table credly-notifications">
			<tr valign="top" class="credly-notifications-enable-message">
				<th scope="row">
					<label for="credly_badge_sendemail_add_message"><?php _e( 'Add a global custom message to each notification: ', 'badgeos' ); ?></label>
				</th>
				<td>
					<select id="credly_badge_sendemail_add_message" name="credly_settings[credly_badge_sendemail_add_message]">
						<option value="false"<?php selected( $credly_settings[ 'credly_badge_sendemail_add_message' ], 'false' ); ?>><?php _e( 'No', 'badgeos' ) ?></option>
						<option value="true"<?php selected( $credly_settings[ 'credly_badge_sendemail_add_message' ], 'true' ); ?>><?php _e( 'Yes', 'badgeos' ) ?></option>
					</select>
				</td>
			</tr>

			<tr valign="top" class="credly-notifications-message">
				<th scope="row">
					<label for="credly_badge_sendemail"><?php _e( 'Custom notification message: ', 'badgeos' ); ?></label>
				</th>
				<td>
					<textarea id="credly_badge_sendemail_message" name="credly_settings[credly_badge_sendemail_message]" cols="80" rows="10"><?php echo esc_textarea( $credly_settings[ 'credly_badge_sendemail_message' ] ); ?></textarea>
				</td>
			</tr>
		</table>
		<?php do_action( 'credly_settings', $credly_settings ); ?>
	</div>
<?php

}

/**
 * Globally replace "Featured Image" text with "Achievement Image".
 *
 * @since  1.3.0
 *
 * @param  string $string Original output string.
 * @return string         Potentially modified output string.
 */
function badgeos_featured_image_metabox_title( $string = '' ) {

	// If this is a new achievement type post
	// OR this is an existing achievement type post
	// AND the text is "Featured Image"
	// ...replace the string
    $badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
    if (
        (
            ( isset( $_GET['post_type'] ) && in_array( $_GET['post_type'], badgeos_get_achievement_types_slugs() ) )
            || ( isset( $_GET['post'] ) && badgeos_is_achievement( $_GET['post'] ) )
        ) && 'Featured Image' == $string

    )
        $string = __( 'Achievement Image', 'badgeos' );
    elseif (
        (
            ( isset( $_GET['post_type'] ) && $badgeos_settings['achievement_main_post_type'] == $_GET['post_type'] )
            || ( isset( $_GET['post'] ) && $badgeos_settings['achievement_main_post_type'] == badgeos_utilities::get_post_type( $_GET['post'] ) )
        ) && 'Featured Image' == $string
    )
        $string = __( 'Default Achievement Image', 'badgeos' );

    return $string;
}

add_filter( 'gettext', 'badgeos_featured_image_metabox_title' );

/**
 * Change "Featured Image" to "Achievement Image" in post editor metabox.
 *
 * @since  1.3.0
 *
 * @param  string  $content HTML output.
 * @param  integer $ID      Post ID.
 * @return string           Potentially modified output.
 */
function badgeos_featured_image_metabox_text( $content = '', $ID = 0 ) {
    $badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
    if ( badgeos_is_achievement( $ID ) )
		$content = str_replace( 'featured image', __( 'achievement image', 'badgeos' ), $content );
    elseif ( $badgeos_settings['achievement_main_post_type'] == badgeos_utilities::get_post_type( $ID ) )
		$content = str_replace( 'featured image', __( 'default achievement image', 'badgeos' ), $content );

	return $content;
}
add_filter( 'admin_post_thumbnail_html', 'badgeos_featured_image_metabox_text', 10, 2 );

/**
 * Change "Featured Image" to "Achievement Image" throughout media modal.
 *
 * @since  1.3.0
 *
 * @param  array  $strings All strings passed to media modal.
 * @param  object $post    Post object.
 * @return array           Potentially modified strings.
 */
function badgeos_media_modal_featured_image_text( $strings = array(), $post = null ) {

	if ( is_object( $post ) ) {
        $badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
		if ( badgeos_is_achievement( $post->ID ) ) {
			$strings['setFeaturedImageTitle'] = __( 'Set Achievement Image', 'badgeos' );
			$strings['setFeaturedImage'] = __( 'Set achievement image', 'badgeos' );
		} elseif ( $badgeos_settings['achievement_main_post_type'] == $post->post_type ) {
			$strings['setFeaturedImageTitle'] = __( 'Set Default Achievement Image', 'badgeos' );
			$strings['setFeaturedImage'] = __( 'Set default achievement image', 'badgeos' );
		}
	}

	return $strings;
}
add_filter( 'media_view_strings', 'badgeos_media_modal_featured_image_text', 10, 2 );

/**
 * Get capability required for BadgeOS administration.
 *
 * @since  1.4.0
 *
 * @return string User capability.
 */
function badgeos_get_manager_capability() {
	$badgeos_settings = badgeos_utilities::get_option( 'badgeos_settings' );
	return isset( $badgeos_settings[ 'minimum_role' ] ) ? $badgeos_settings[ 'minimum_role' ] : 'manage_options';
}
