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

	// @TODO should we be using do_settings_fields here to be more efficient?
	// @RESPONSE yes, oh please God, yes
	?>
	<div class="wrap" >
		<div id="icon-options-general" class="icon32"></div>
		<h2><?php _e( 'BadgeOS Settings', 'badgeos' ); ?></h2>

		<form method="post" action="options.php">
			<?php settings_fields( 'badgeos_settings_group' ); ?>
			<?php $badgeos_settings = get_option( 'badgeos_settings' ); ?>
			<table class="form-table">
				<tr valign="top"><th scope="row"><label for="minimum_role"><?php _e( 'Minimum Role to Administer BadgeOS plugin: ', 'badgeos' ); ?></label></th>
					<td>
                        <select id="minimum_role" name="badgeos_settings[minimum_role]">
                            <option value="manage_options" <?php selected( $badgeos_settings['minimum_role'], 'manage_options' ); ?>><?php _e( 'Administrator', 'badgeos' ); ?></option>
                            <option value="delete_others_posts" <?php selected( $badgeos_settings['minimum_role'], 'delete_others_posts' ); ?>><?php _e( 'Editor', 'badgeos' ); ?></option>
                            <option value="publish_posts" <?php selected( $badgeos_settings['minimum_role'], 'publish_posts' ); ?>><?php _e( 'Author', 'badgeos' ); ?></option>
                            <option value="edit_posts" <?php selected( $badgeos_settings['minimum_role'], 'edit_posts' ); ?>><?php _e( 'Contributor', 'badgeos' ); ?></option>
                            <option value="read" <?php selected( $badgeos_settings['minimum_role'], 'read' ); ?>><?php _e( 'Subscriber', 'badgeos' ); ?></option>
                        </select>
					</td>
				</tr>
				<tr valign="top"><th scope="row"><label for="submission_email"><?php _e( 'Send email when submissions/nominations are received:', 'badgeos' ); ?></label></th>
					<td>
                        <select id="debug_mode" name="badgeos_settings[submission_email]">
                            <option value="enabled" <?php selected( $badgeos_settings['submission_email'], 'enabled' ); ?>><?php _e( 'Enabled', 'badgeos' ) ?></option>
                            <option value="disabled" <?php selected( $badgeos_settings['submission_email'], 'disabled' ); ?>><?php _e( 'Disabled', 'badgeos' ) ?></option>
                        </select>
					</td>
				</tr>
				<tr valign="top"><th scope="row"><label for="debug_mode"><?php _e( 'Debug Mode:', 'badgeos' ); ?></label></th>
					<td>
                        <select id="debug_mode" name="badgeos_settings[debug_mode]">
                            <option value="disabled" <?php selected( $badgeos_settings['debug_mode'], 'disabled' ); ?>><?php _e( 'Disabled', 'badgeos' ) ?></option>
                            <option value="enabled" <?php selected( $badgeos_settings['debug_mode'], 'enabled' ); ?>><?php _e( 'Enabled', 'badgeos' ) ?></option>
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
function badgeos_add_ons_page() { ?>
	<div class="wrap" >
		<div id="icon-options-general" class="icon32"></div>
		<h2><?php _e( 'BadgeOS Add-Ons', 'badgeos' ); ?></h2>
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
		<h3><?php _e( 'About BadgeOS', 'badgeos' ); ?>:</h3>
		<p><?php printf( __( 'BadgeOS&trade; is plugin to WordPress that allows your site\'s users to complete tasks, demonstrate achievement, and earn badges. You define the Achievement types, organize your requirements any way you like, and choose from a range of options to determine whether each task or requirement has been achieved. Badges earned in BadgeOS are Mozilla OBI compatible through out-of-the-box integration of the "Open Credit" API by <a href="%s" target="_blank">Credly</a>, the free web service for issuing, earning and sharing badges.', 'badgeos' ), 'https://credly.com/' ); ?></p>
		<p><?php _e( 'BadgeOS is extremely extensible. Check out examples of what we’ve built with it, and stay connected to the project site for updates, add-ins and news. Share your ideas and code improvements on our github site so we can keep making BadgeOS better for everyone.', 'badgeos' ); ?></p>

		<h3><?php _e( 'Help / Support', 'badgeos' ); ?>:</h3>
		<p><?php _e( 'For support on using BadgeOS or to suggest feature enhancements, visit the <a href="http://badgeos.org" target="_blank">BadgeOS site</a>.  The BadgeOS team does perform custom development that extends the BadgeOS platform in some incredibly powerful ways. <a href="http://badgeos.org/" target="_blank">Contact us</a> with inquiries. See examples of enhanced BadgeOS projects.', 'badgeos' ); ?></p>
		<p><?php _e( 'Please submit bugs or issues to our Github site for the BadgeOS Project.', 'badgeos' ); ?></p>

		<h3><?php _e( 'Shortcodes', 'badgeos' ); ?>:</h3>
		<p><?php _e( 'With BadgeOS activated, the following shortcodes can be placed on any page or post within WordPress to expose a variety of BadgeOS functions.', 'badgeos' ); ?></p>

		<p><strong>[badgeos_achievements_list]</strong> - <?php _e( 'Use this shortcode to generate a list of achievements of any type on any post or page.', 'badgeos' ); ?>
		<div style="padding-left:15px;">
			<ul>
				<li><strong><?php _e( 'Parameters', 'badgeos' ); ?></strong></li>
				<li>
					<div style="padding-left:15px;">
					<ul>
						<li><?php _e( 'type', 'badgeos' ); ?> - <?php _e( 'Custom post type of achievements to list.  Default: all', 'badgeos' ); ?></li>
						<li><?php _e( 'limit', 'badgeos' ); ?> - <?php _e( 'Number of achievements to display per page.  Default: 10', 'badgeos' ); ?></li>
						<li><?php _e( 'show_filter', 'badgeos' ); ?> - <?php _e( 'Display the filter options.  Default: true', 'badgeos' ); ?></li>
						<li><?php _e( 'show_search', 'badgeos' ); ?> - <?php _e( 'Display the search form.  Default: true', 'badgeos' ); ?></li>
					</ul>
					</div>
				</li>
				<li><strong><?php _e( 'Example', 'badgeos' ); ?>:</strong> [badgeos_achievements_list type=badge limit=15]</li>
			</ul>
		</div>
		</p>
		<p><strong>[badgeos_submission]</strong> - <?php _e( 'Use this shortcode to add a submission box to any achievement set to require Submission by the user.  If <strong>Earned By</strong> is set to <strong>Submission</strong> this shortcode is automatically added on the single page.', 'badgeos' ); ?></p>
		<p><strong>[badgeos_nomination]</strong> - <?php _e( 'Use this shortcode to add a nomination box to any achievement set to require Nomination by the user.  If <strong>Earned By</strong> is set to <strong>Nomination</strong> this shortcode is automatically added on the single page.', 'badgeos' ); ?></p>
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
            <p><?php printf( __( '<a href="%s" target="_blank">Credly</a> is a universal way for people to earn and showcase their achievements and badges. With Credly Integration enabled here, badges or achievements you create on this BadgeOS site can automatically be created on your Credly account. As select badges are earned using BadgeOS, the badge will automatically be issued via Credly to the earner so they can easily share it on Facebook, LinkedIn, Twitter, Mozilla Backpack, their web site, blog, Credly profile or other location. Credly makes badge issuing and sharing fun and easy! <a href="%s" target="_blank">Learn more</a>.  <br /><br />If you do not yet have a Credly account, <a href="%s" target="_blank">create one now</a>. It\'s free.', 'badgeos' ), 'https://credly.com', 'https://credly.com', 'https://credly.com' ); ?></p>
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
