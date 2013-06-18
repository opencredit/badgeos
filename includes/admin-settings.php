<?php
/**
 * Admin Settings Pages
 *
 * @package BadgeOS
 * @subpackage Admin
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Register BadgeOS Settings with Settings API.
 * @return null
 */
function badgeos_register_settings() {
	register_setting( 'badgeos_settings_group', 'badgeos_settings', 'badgeos_settings_validate' );
	register_setting( 'credly_settings_group', 'credly_settings', 'badgeos_credly_settings_validate' );
}
add_action( 'admin_init', 'badgeos_register_settings' );

/**
 * BadgeOS Settings validation
 *
 * @param  string $input The input we want to validate
 * @return string        Our sanitized input
 */
function badgeos_settings_validate( $input ) {

	//sanitize the settings data submitted
	$input['minimum_role'] = sanitize_text_field( $input['minimum_role'] );
	$input['debug_mode']   = sanitize_text_field( $input['debug_mode'] );

	return $input;

}

/**
 * Credly API Settings validation
 * @since  1.0.0
 * @param  array $options Form inputs data
 * @return array          Sanitized form input data
 */
function badgeos_credly_settings_validate( $options ) {

	// If attempting to retrieve an api key from credly
	if (
		empty( $options['api_key'] )
		&& isset( $_POST['badgeos_credly_api_key_nonce'] )
		&& wp_verify_nonce( $_POST['badgeos_credly_api_key_nonce'], 'badgeos_credly_api_key_nonce' )
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
			'headers' => array( 'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password ) )
	) ) ;

	if ( '401' == wp_remote_retrieve_response_code( $response ) ) {
		$error = '<p>'. __( 'There was an error getting a Credly API Key. Please check your username and password.', 'badgeos' ). '</p>';
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
 */
function badgeos_credly_get_api_key_error( $error ) {

	// Temporarily store our error message.
	update_option( 'credly_api_key_error', $error );
	return false;
}

add_action( 'all_admin_notices', 'badgeos_credly_api_key_errors' );
/**
 * Displays error messages from Credly API key retrieval
 * @since  1.0.0
 * @return null
 */
function badgeos_credly_api_key_errors() {

	if ( get_current_screen()->id != 'badgeos_page_badgeos_sub_credly_integration' || !( $has_notice = get_option( 'credly_api_key_error' ) ) )
		return;

	// If we have an error message, we'll display it
	echo '<div id="message" class="error">'. $has_notice .'</div>';
	// and then delete it
	delete_option( 'credly_api_key_error' );
}

/**
 * BadgeOS main settings page output
 * @since  1.0.0
 * @return null
 */
function badgeos_settings_page() {
	flush_rewrite_rules();
	if ( badgeos_is_debug_mode() )
		echo 'debug mode is on';

	?>
	<div class="wrap" >
		<div id="icon-options-general" class="icon32"></div>
		<h2><?php _e( 'BadgeOS Settings', 'badgeos' ); ?></h2>

		<form method="post" action="options.php">
			<?php settings_fields( 'badgeos_settings_group' ); ?>
			<?php $badgeos_settings = get_option( 'badgeos_settings' ); ?>
			<?php
			//load settings
			$minimum_role = ( isset( $badgeos_settings['minimum_role'] ) ) ? $badgeos_settings['minimum_role'] : '';
			$submission_email = ( isset( $badgeos_settings['submission_email'] ) ) ? $badgeos_settings['submission_email'] : '';
			$debug_mode = ( isset( $badgeos_settings['debug_mode'] ) ) ? $badgeos_settings['debug_mode'] : 'disabled';
			?>
			<table class="form-table">
				<tr valign="top"><th scope="row"><label for="minimum_role"><?php _e( 'Minimum Role to Administer BadgeOS plugin: ', 'badgeos' ); ?></label></th>
					<td>
                        <select id="minimum_role" name="badgeos_settings[minimum_role]">
                            <option value="manage_options" <?php selected( $minimum_role, 'manage_options' ); ?>><?php _e( 'Administrator', 'badgeos' ); ?></option>
                            <option value="delete_others_posts" <?php selected( $minimum_role, 'delete_others_posts' ); ?>><?php _e( 'Editor', 'badgeos' ); ?></option>
                            <option value="publish_posts" <?php selected( $minimum_role, 'publish_posts' ); ?>><?php _e( 'Author', 'badgeos' ); ?></option>
                            <option value="edit_posts" <?php selected( $minimum_role, 'edit_posts' ); ?>><?php _e( 'Contributor', 'badgeos' ); ?></option>
                            <option value="read" <?php selected( $minimum_role, 'read' ); ?>><?php _e( 'Subscriber', 'badgeos' ); ?></option>
                        </select>
					</td>
				</tr>
				<tr valign="top"><th scope="row"><label for="submission_email"><?php _e( 'Send email when submissions/nominations are received:', 'badgeos' ); ?></label></th>
					<td>
                        <select id="debug_mode" name="badgeos_settings[submission_email]">
                            <option value="enabled" <?php selected( $submission_email, 'enabled' ); ?>><?php _e( 'Enabled', 'badgeos' ) ?></option>
                            <option value="disabled" <?php selected( $submission_email, 'disabled' ); ?>><?php _e( 'Disabled', 'badgeos' ) ?></option>
                        </select>
					</td>
				</tr>
				<tr valign="top"><th scope="row"><label for="debug_mode"><?php _e( 'Debug Mode:', 'badgeos' ); ?></label></th>
					<td>
						<select id="debug_mode" name="badgeos_settings[debug_mode]">
                            <option value="disabled" <?php selected( $debug_mode, 'disabled' ); ?>><?php _e( 'Disabled', 'badgeos' ) ?></option>
                            <option value="enabled" <?php selected( $debug_mode, 'enabled' ); ?>><?php _e( 'Enabled', 'badgeos' ) ?></option>
                        </select>
					</td>
				</tr>
				<?php do_action( 'badgeos_settings', $badgeos_settings ); ?>
			</table>
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e( 'Save Settings', 'badgeos' ); ?>" />
			</p>
			<!-- TODO: Add settings to select WP page for archives of each achievement type.
				See BuddyPress' implementation of this idea.  -->
		</form>
	</div>
	<?php
}

/**
 * Add-ons settings page
 * @since  1.0.0
 * @return null
 */
function badgeos_add_ons_page() {
	$image_url = $GLOBALS['badgeos']->directory_url .'images/';
	?>
	<div class="wrap" >
		<div id="icon-options-general" class="icon32"></div>
		<h2><?php _e( 'BadgeOS Add-Ons', 'badgeos' ); ?></h2>

		<table>
			<tr>
				<td valign="top">
					<a target="_blank" href="http://wordpress.org/extend/plugins/badgeos-community-add-on/">
						<img width="150" height="150" src="<?php echo $image_url;?>add-on-community.png">
					</a>
				</td>
				<td valign="top">
					<h3>Community Add-on</h3>
					<p><a target="_blank" href="http://wordpress.org/extend/plugins/badgeos-community-add-on/">http://wordpress.org/extend/plugins/badgeos-community-add-on/</a></p>
					The "BadgeOS Community Add-on" integrates BadgeOS features into BuddyPress and bbPress. Site members complete achievements and earn badges based on a range of community activity and triggers. This add-on to BadgeOS also includes the ability to display badges and achievements on user profiles and activity feeds.
				</td>
			</tr>
			<tr>
				<td valign="top">
					<a target="_blank" href="http://wordpress.org/extend/plugins/badgeos-badgestack-add-on/">
						<img width="150" height="150" src="<?php echo $image_url;?>add-on-badgestack.png">
					</a>
				</td>
				<td valign="top">
					<h3>BadgeStack Add-on</h3>
					<p><a target="_blank" href="http://wordpress.org/extend/plugins/badgeos-badgestack-add-on/">http://wordpress.org/extend/plugins/badgeos-badgestack-add-on/</a></p>
					The BadgeStack add-on to BadgeOS automatically creates all the achievement types and pages needed to quickly set up your very own badging system. Levels, Quest Badges, Quests and Community Badges are all ready upon activating the plugin, as are pages with shortcodes for each achievement type. BadgeStack also includes a set of sample achievements and badges. A great way to bring some instant organization to your site and to get started with badging. This add-on is made possible in part due to the generous support of HASTAC, through the DML Badging Competition.
				</td>
			</tr>
		</table>

	</div>
	<?php
}

/**
 * Help and Support settings page
 * @since  1.0.0
 * @return null
 */
function badgeos_help_support_page() { ?>
	<div class="wrap" >
		<div id="icon-options-general" class="icon32"></div>
		<h2><?php _e( 'BadgeOS Help and Support', 'badgeos' ); ?></h2>
		<h2><?php _e( 'About BadgeOS', 'badgeos' ); ?>:</h2>
		<p><?php printf( __( 'BadgeOS&trade; is plugin to WordPress that allows your site\'s users to complete tasks, demonstrate achievement, and earn badges. You define the Achievement types, organize your requirements any way you like, and choose from a range of options to determine whether each task or requirement has been achieved. Badges earned in BadgeOS are Mozilla OBI compatible through out-of-the-box integration of the "Open Credit" API by <a href="%s" target="_blank">Credly</a>, the free web service for issuing, earning and sharing badges.', 'badgeos' ), 'https://credly.com/' ); ?></p>
		<p><?php _e( "BadgeOS is extremely extensible. Check out examples of what we've built with it, and stay connected to the project site for updates, add-ins and news. Share your ideas and code improvements on our github site so we can keep making BadgeOS better for everyone.", 'badgeos' ); ?></p>

		<h2><?php _e( 'Help / Support', 'badgeos' ); ?>:</h2>
		<p><?php _e( 'For support on using BadgeOS or to suggest feature enhancements, visit the <a href="http://badgeos.org" target="_blank">BadgeOS site</a>.  The BadgeOS team does perform custom development that extends the BadgeOS platform in some incredibly powerful ways. <a href="http://badgeos.org/" target="_blank">Contact us</a> with inquiries. See examples of enhanced BadgeOS projects.', 'badgeos' ); ?></p>
		<p><?php _e( 'Please submit bugs or issues to our Github site for the BadgeOS Project.', 'badgeos' ); ?></p>

		<h2><?php _e( 'Shortcodes', 'badgeos' ); ?>:</h2>
		<p><?php _e( 'With BadgeOS activated, the following shortcodes can be placed on any page or post within WordPress to expose a variety of BadgeOS functions.  Visit <a href="http://badgeos.org/support/shortcodes/" target="_blank">BadgeOS.org</a> for additional information on shortcodes.', 'badgeos' ); ?></p>
		<hr/>
		<p><strong>[badgeos_achievement]</strong> - <?php _e( 'Display a single achievement on any post or page.', 'badgeos' ); ?>
		<div style="padding-left:15px;">
			<ul>
				<li><strong><?php _e( 'Parameters', 'badgeos' ); ?></strong></li>
				<li>
					<div style="padding-left:15px;">
					<ul>
						<li><?php _e( 'id', 'badgeos' ); ?> - <?php _e( 'The ID of the achievement to display.', 'badgeos' ); ?></li>
					</ul>
					</div>
				</li>
				<li><strong><?php _e( 'Example', 'badgeos' ); ?>:</strong> <code>[badgeos_achievement id=12]</code></li>
			</ul>
		</div>
		</p>
		<hr/>
		<p><strong>[badgeos_achievements_list]</strong> - <?php _e( 'Output a list of achievements of any type on any post or page.', 'badgeos' ); ?>
		<div style="padding-left:15px;">
			<ul>
				<li><strong><?php _e( 'Parameters', 'badgeos' ); ?></strong></li>
				<li>
					<div style="padding-left:15px;">
					<ul>
						<li><?php _e( 'type', 'badgeos' ); ?> - <?php printf( __( 'Type of achievements to list. Default: %s', 'badgeos' ), '<code>all</code>' ); ?></li>
						<li><?php _e( 'limit', 'badgeos' ); ?> - <?php printf( __( 'Number of achievements to display per page. Default: %s', 'badgeos' ), '<code>10</code>' ); ?></li>
						<li><?php _e( 'show_filter', 'badgeos' ); ?> - <?php printf( __( 'Display the filter options. Accepts: %1$s Default: %2$s', 'badgeos' ), '<code>true, false</code>', '<code>true</code>' ); ?></li>
						<li><?php _e( 'show_search', 'badgeos' ); ?> - <?php printf( __( 'Display the search form. Accepts: %1$s Default: %2$s', 'badgeos' ), '<code>true, false</code>', '<code>true</code>' ); ?></li>
					</ul>
					</div>
				</li>
				<li><strong><?php _e( 'Example', 'badgeos' ); ?>:</strong> <code>[badgeos_achievements_list type=badge limit=15]</code></li>
			</ul>
		</div>
		</p>
		<hr/>
		<p><strong>[badgeos_submission]</strong> - <?php _e( 'Display submissions or submission form for a given achievement. <strong>Note:</strong> Achievements will automatically display this on their single page if <strong>Earned By</strong> is set to <strong>Submission</strong>.', 'badgeos' ); ?></p>
		<div style="padding-left:15px;">
			<ul>
				<li><strong><?php _e( 'Parameters', 'badgeos' ); ?></strong></li>
				<li>
					<div style="padding-left:15px;">
					<ul>
						<li>achievement_id - <?php _e( 'The ID of the achievement to be awarded.  Default: current post ID', 'badgeos' ); ?></li>
					</ul>
					</div>
				</li>
				<li><strong><?php _e( 'Example', 'badgeos' ); ?>:</strong> <code>[badgeos_submission achievement_id=35]</code></li>
			</ul>
		</div>
		<hr/>
		<p><strong>[badgeos_nomination]</strong> - <?php _e( 'Display nominations or nomination form for a given achievement. <strong>Note:</strong> Achievements will automatically display this on their single page if <strong>Earned By</strong> is set to <strong>Nomination</strong>.', 'badgeos' ); ?></p>
		<div style="padding-left:15px;">
			<ul>
				<li><strong><?php _e( 'Parameters', 'badgeos' ); ?></strong></li>
				<li>
					<div style="padding-left:15px;">
					<ul>
						<li>achievement_id - <?php _e( 'The ID of the achievement to be awarded.  Default: current post ID', 'badgeos' ); ?></li>
					</ul>
					</div>
				</li>
				<li><strong><?php _e( 'Example', 'badgeos' ); ?>:</strong> <code>[badgeos_nomination achievement_id=35]</code></li>
			</ul>
		</div>
		<hr/>
		<p><strong>[badgeos_submissions]</strong> - <?php _e( 'Generate a list of submissions on any post or page.', 'badgeos' ); ?>
		<div style="padding-left:15px;">
			<ul>
				<li><strong><?php _e( 'Parameters', 'badgeos' ); ?></strong></li>
				<li>
					<div style="padding-left:15px;">
					<ul>
						<li>limit - <?php printf( __( 'Number of submissions to display per page. Default: %1$s', 'badgeos' ), '<code>10</code>' ); ?></li>
						<li>status - <?php printf( __( 'Which Approval Status type to show on initial page load. Accepts: %1$s  Default: %2$s', 'badgeos' ), '<code>all, pending, auto-approved, approved, denied</code>', '<code>all</code>' ); ?></li>
						<li>show_filter - <?php printf( __( 'Display the filter select input. Accepts: %1$s  Default: %2$s', 'badgeos' ), '<code>true, false</code>', '<code>true</code>' ); ?></li>
						<li>show_search - <?php printf( __( 'Display the search form. Accepts: %1$s  Default: %2$s', 'badgeos' ), '<code>true, false</code>', '<code>true</code>' ); ?></li>
						<li>show_attachments - <?php printf( __( 'Display attachments connected to the submission. Accepts: %1$s  Default: %2$s', 'badgeos' ), '<code>true, false</code>', '<code>true</code>' ); ?></li>
						<li>show_comments - <?php printf( __( 'Display comments associated with the submission. Accepts: %1$s  Default: %2$s', 'badgeos' ), '<code>true, false</code>', '<code>true</code>' ); ?></li>
					</ul>
					</div>
				</li>
				<li><strong><?php _e( 'Example', 'badgeos' ); ?>:</strong> <?php printf( __( 'To show 15 pending submissions, %s', 'badgeos' ), '<code>[badgeos_submissions status=pending limit=15]</code>' ); ?></li>
			</ul>
		</div>
		</p>
		<hr/>
		<p><strong>[badgeos_nominations]</strong> - <?php _e( 'Generate a list of nominations on any post or page.', 'badgeos' ); ?>
		<div style="padding-left:15px;">
			<ul>
				<li><strong><?php _e( 'Parameters', 'badgeos' ); ?></strong></li>
				<li>
					<div style="padding-left:15px;">
					<ul>
						<li>limit - <?php printf( __( 'Number of nominations to display per page. Default: %1$s', 'badgeos' ), '<code>10</code>' ); ?></li>
						<li>status - <?php printf( __( 'Which Approval Status type to show on initial page load. Accepts: %1$s  Default: %2$s', 'badgeos' ), '<code>all, pending, approved, denied</code>', '<code>all</code>' ); ?></li>
						<li>show_filter - <?php printf( __( 'Display the filter select input. Accepts: %1$s  Default: %2$s', 'badgeos' ), '<code>true, false</code>', '<code>true</code>' ); ?></li>
						<li>show_search - <?php printf( __( 'Display the search form. Accepts: %1$s  Default: %2$s', 'badgeos' ), '<code>true, false</code>', '<code>true</code>' ); ?></li>
					</ul>
					</div>
				</li>
				<li><strong><?php _e( 'Example', 'badgeos' ); ?>:</strong> <?php printf( __( 'To display 20 nominations and no search form, %s', 'badgeos' ), '<code>[badgeos_nominations show_search=false limit=20]</code>' ); ?></li>
			</ul>
		</div>
		</p>
	</div>
	<?php
}

/**
 * BadgeOS Credly Integration settings page.
 * @since  1.0.0
 * @return null
 */
function badgeos_credly_options_page() {
	?>
	<div class="wrap" >
		<div id="icon-options-general" class="icon32"></div>
		<h2><?php _e( 'Credly Integration Settings', 'badgeos' ); ?></h2>
		<?php settings_errors(); ?>

		<form method="post" action="options.php">
			<?php
			$credly_settings = get_option( 'credly_settings' );
			settings_fields( 'credly_settings_group' );
			?>
			<p><?php printf( __( '<a href="%1$s" target="_blank">Credly</a> is a universal way for people to earn and showcase their achievements and badges. With Credly Integration enabled here, badges or achievements you create on this BadgeOS site can automatically be created on your Credly account. As select badges are earned using BadgeOS, the badge will automatically be issued via Credly to the earner so they can easily share it on Facebook, LinkedIn, Twitter, Mozilla Backpack, their web site, blog, Credly profile or other location. Credly makes badge issuing and sharing fun and easy! <a href="%1$s" target="_blank">Learn more</a>.  <br /><br />If you do not yet have a Credly account, <a href="%1$s" target="_blank">create one now</a>. It\'s free.', 'badgeos' ), 'https://credly.com' ); ?></p>
			<table class="form-table">
				<tr valign="top"><th scope="row"><label for="credly_enable"><?php _e( 'Enable Badge Sharing via Credly: ', 'badgeos' ); ?></label></th>
					<td><select id="credly_enable" name="credly_settings[credly_enable]">
						<option value="false" <?php selected( isset( $credly_settings['credly_enable'] ) && $credly_settings['credly_enable'] == 'false' ); ?>><?php _e( 'No', 'badgeos' ) ?></option>
						<option value="true" <?php selected( isset( $credly_settings['credly_enable'] ) && $credly_settings['credly_enable'] == 'true' ); ?>><?php _e( 'Yes', 'badgeos' ) ?></option>
					</select></td>
				</tr>
			</table>
			<?php

			// We need to get our api key
			if ( !isset( $credly_settings['api_key'] ) || empty( $credly_settings['api_key'] ) )
				badgeos_credly_options_no_api( $credly_settings );

			else // We already have our api key
				badgeos_credly_options_yes_api( $credly_settings );

			?>
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e( 'Save Settings', 'badgeos' ); ?>" />
			</p>
		</form>
	</div>
	<?php
}

/**
 * BadgeOS Credly API key retrieval form.
 * @since  1.0.0
 * @param  array $credly_settings saved settings
 * @return null
 */
function badgeos_credly_options_no_api( $credly_settings ) {
	wp_nonce_field( 'badgeos_credly_api_key_nonce', 'badgeos_credly_api_key_nonce' );

	if ( is_array( $credly_settings ) ) {
		foreach ( $credly_settings as $key => $opt ) {
			if (
				$key == 'credly_user'
				|| $key == 'credly_password'
				|| $key == 'api_key'
				|| $key == 'credly_enable'
			)
				continue;

			// Save our hidden form values
			echo '<input type="hidden" name="credly_settings['. $key .']" value="'. $opt .'" />';
		}
	}
	?>
	<div id="credly-settings">
		<h3><?php _e( 'Get Credly API Key', 'badgeos' ); ?></h3>
		<p class="toggle"><?php _e( 'Enter your Credly account username and password to access your API key.', 'badgeos' ); ?></p>
		<p class="toggle hidden"><?php printf( __( 'Enter your %s to access your API key.', 'badgeos' ), '<a href="#show-api-key">'. __( 'Credly account username and password', 'badgeos' ) .'</a>' ); ?></p>
		<table class="form-table">
			<tr valign="top"><th scope="row"><label for="credly_user"><?php _e( 'Username: ', 'badgeos' ); ?></label></th>
				<td><input id="credly_user" type="text" name="credly_settings[credly_user]" class="widefat" value="<?php echo isset( $credly_settings['credly_user'] ) ? esc_attr( $credly_settings['credly_user'] ) : ''; ?>" style="max-width: 400px;" /></td>
			</tr>
			<tr valign="top"><th scope="row"><label for="credly_password"><?php _e( 'Password: ', 'badgeos' ); ?></label></th>
				<td><input id="credly_password" type="password" name="credly_settings[credly_password]" class="widefat" value="<?php echo isset( $credly_settings['credly_password'] ) ? esc_attr( $credly_settings['credly_password'] ) : ''; ?>" style="max-width: 400px;" /></td>
			</tr>
			<tr valign="top" class="hidden"><th scope="row"><label for="api_key"><?php _e( 'API Key: ', 'badgeos' ); ?></label></th>
				<td><input id="api_key" type="text" name="credly_settings[api_key]" class="widefat" value="<?php echo isset( $credly_settings['api_key'] ) ? esc_attr( $credly_settings['api_key'] ) : ''; ?>" style="max-width: 1000px;" /></td>
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
 * @return null
 */
function badgeos_credly_options_yes_api( $credly_settings ) {
	?>
	<div id="credly-settings">
		<h3><?php _e( 'Credly API Key', 'badgeos' ); ?></h3>
		<table class="form-table">
			<tr valign="top"><th scope="row"><label for="api_key"><?php _e( 'API Key: ', 'badgeos' ); ?></label></th>
				<td><input id="api_key" type="text" name="credly_settings[api_key]" class="widefat" value="<?php echo isset( $credly_settings['api_key'] ) ? esc_attr( $credly_settings['api_key'] ) : ''; ?>" /></td>
			</tr>
		</table>

		<h3><?php _e( 'Credly Field Mapping', 'badgeos' ); ?></h3>
		<p><?php _e( 'Customize which Credly fields for badge creation and issuing (listed on the left) match which WordPress and BadgeOS fields (listed to the right).', 'badgeos' ); ?><br/><?php _e( 'When badges are created and issued, the info sent to Credly will rely on the global mapping found here. (Note: Visit the edit screen for each achievement you create in BadgeOS to further configure the sharing and Credly settings for that achievement.)', 'badgeos' ); ?></p>
		<table class="form-table">
			<tr valign="top"><th scope="row"><label for="credly_badge_title"><?php _e( 'Badge Title: ', 'badgeos' ); ?></label></th>
				<td><select id="credly_badge_title" name="credly_settings[credly_badge_title]">
					<?php echo credly_fieldmap_list_options( $credly_settings['credly_badge_title'] ); ?>
				</select></td>
			</tr>
			<tr valign="top"><th scope="row"><label for="credly_badge_short_description"><?php _e( 'Short Description: ', 'badgeos' ); ?></label></th>
				<td><select id="credly_badge_short_description" name="credly_settings[credly_badge_short_description]">
					<?php echo credly_fieldmap_list_options( $credly_settings['credly_badge_short_description'] ); ?>
				</select></td>
			</tr>
			<tr valign="top"><th scope="row"><label for="credly_badge_description"><?php _e( 'Description: ', 'badgeos' ); ?></label></th>
				<td><select id="credly_badge_description" name="credly_settings[credly_badge_description]">
					<?php echo credly_fieldmap_list_options( $credly_settings['credly_badge_description'] ); ?>
				</select></td>
			</tr>
			<tr valign="top"><th scope="row"><label for="credly_badge_criteria"><?php _e( 'Criteria: ', 'badgeos' ); ?></label></th>
				<td><select id="credly_badge_criteria" name="credly_settings[credly_badge_criteria]">
					<?php echo credly_fieldmap_list_options( $credly_settings['credly_badge_criteria'] ); ?>
				</select></td>
			</tr>
			<tr valign="top"><th scope="row"><label for="credly_badge_image"><?php _e( 'Image: ', 'badgeos' ); ?></label></th>
				<td><select id="credly_badge_image" name="credly_settings[credly_badge_image]">
					<?php echo credly_fieldmap_list_options( $credly_settings['credly_badge_image'] ); ?>
				</select></td>
			</tr>
			<tr valign="top"><th scope="row"><label for="credly_badge_testimonial"><?php _e( 'Testimonial: ', 'badgeos' ); ?></label></th>
				<td><select id="credly_badge_testimonial" name="credly_settings[credly_badge_testimonial]">
					<?php echo credly_fieldmap_list_options( $credly_settings['credly_badge_testimonial'] ); ?>
				</select></td>
			</tr>
			<tr valign="top"><th scope="row"><label for="credly_badge_evidence"><?php _e( 'Evidence: ', 'badgeos' ); ?></label></th>
				<td><select id="credly_badge_evidence" name="credly_settings[credly_badge_evidence]">
					<?php echo credly_fieldmap_list_options( $credly_settings['credly_badge_evidence'] ); ?>
				</select></td>
			</tr>
			<tr valign="top"><th scope="row"><label for="credly_badge_sendemail"><?php _e( 'Notify users of earned badge: ', 'badgeos' ); ?></label></th>
				<td><select id="credly_badge_sendemail" name="credly_settings[credly_badge_sendemail]">
					<option></option>
					<option value="true" <?php selected( $credly_settings['credly_badge_sendemail'], 'true' ); ?>><?php _e( 'Yes', 'badgeos' ) ?></option>
					<option value="false" <?php selected( $credly_settings['credly_badge_sendemail'], 'false' ); ?>><?php _e( 'No', 'badgeos' ) ?></option>
				</select></td>
			</tr>
		</table>
		<?php do_action( 'credly_settings', $credly_settings ); ?>
	</div>
	<?php
}
