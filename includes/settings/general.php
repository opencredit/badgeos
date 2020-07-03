<?php 
    $badgeos_settings = get_option( 'badgeos_settings' );

    //load settings
    $minimum_role = ( isset( $badgeos_settings['minimum_role'] ) ) ? $badgeos_settings['minimum_role'] : 'manage_options';
    $submission_manager_role = ( isset( $badgeos_settings['submission_manager_role'] ) ) ? $badgeos_settings['submission_manager_role'] : 'manage_options';
    $submission_email = ( isset( $badgeos_settings['submission_email'] ) ) ? $badgeos_settings['submission_email'] : '';
    $submission_email_addresses = ( isset( $badgeos_settings['submission_email_addresses'] ) ) ? $badgeos_settings['submission_email_addresses'] : '';
    $debug_mode = ( isset( $badgeos_settings['debug_mode'] ) ) ? $badgeos_settings['debug_mode'] : 'disabled';
    $log_entries = ( isset( $badgeos_settings['log_entries'] ) ) ? $badgeos_settings['log_entries'] : 'disabled';
    $ms_show_all_achievements = ( isset( $badgeos_settings['ms_show_all_achievements'] ) ) ? $badgeos_settings['ms_show_all_achievements'] : 'disabled';
    $remove_data_on_uninstall = ( isset( $badgeos_settings['remove_data_on_uninstall'] ) ) ? $badgeos_settings['remove_data_on_uninstall'] : '';
    $badgeos_achievement_global_image_width 	= ( ! empty ( $badgeos_settings['badgeos_achievement_global_image_width'] ) ) ? $badgeos_settings['badgeos_achievement_global_image_width'] : '50';
    $badgeos_achievement_global_image_height = ( ! empty ( $badgeos_settings['badgeos_achievement_global_image_height'] ) ) ? $badgeos_settings['badgeos_achievement_global_image_height'] : '50';
    $badgeos_rank_global_image_width 		= ( ! empty ( $badgeos_settings['badgeos_rank_global_image_width'] ) ) ? $badgeos_settings['badgeos_rank_global_image_width'] : '50';
    $badgeos_rank_global_image_height 		= ( ! empty ( $badgeos_settings['badgeos_rank_global_image_height'] ) ) ? $badgeos_settings['badgeos_rank_global_image_height'] : '50';

    $achievement_list_default_view 	= ( ! empty ( $badgeos_settings['achievement_list_shortcode_default_view'] ) ) ? $badgeos_settings['achievement_list_shortcode_default_view'] : 'list';
    $earned_achievements_shortcode_default_view 	= ( ! empty ( $badgeos_settings['earned_achievements_shortcode_default_view'] ) ) ? $badgeos_settings['earned_achievements_shortcode_default_view'] : 'list';
    $earned_ranks_shortcode_default_view 	= ( ! empty ( $badgeos_settings['earned_ranks_shortcode_default_view'] ) ) ? $badgeos_settings['earned_ranks_shortcode_default_view'] : 'list';
    $badgeos_admin_side_tab 	= ( ! empty ( $badgeos_settings['side_tab'] ) ) ? $badgeos_settings['side_tab'] : '#badgeos_settings_general_settings';

    ob_start();
    do_action( 'badgeos_settings', $badgeos_settings );
    $addon_contents = ob_get_clean();
?>
<div id="badgeos-setting-tabs">
    <div class="tab-title"><?php _e( 'General', 'badgeos' ); ?></div>
    <?php settings_errors(); ?>
    <form method="POST" name="badgeos_frm_general_tab" id="badgeos_frm_general_tab" action="options.php">
        <input type="hidden" id="tab_action" name="badgeos_settings[tab_action]" value="general" />
        <input type="hidden" id="badgeos_admin_side_tab" name="badgeos_settings[side_tab]" value="<?php echo $badgeos_admin_side_tab;?>" />
        <ul class="badgeos_sidebar_tab_links">
            <li>
                <a href="#badgeos_settings_general_settings">
                    &nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-table" aria-hidden="true"></i>&nbsp;&nbsp;
                    <?php _e( 'General Settings', 'badgeos' ); ?>
                </a>
            </li>
            <li>
                <a href="#badgeos_settings_default_views">
                    &nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-street-view" aria-hidden="true"></i>&nbsp;&nbsp;
                    <?php _e( 'Default Views', 'badgeos' ); ?>
                </a>
            </li>
            <li>
                <a href="#badgeos_settings_thumb_sizes">
                    &nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-futbol-o" aria-hidden="true"></i>&nbsp;&nbsp;
                    <?php _e( 'Thumbnail Sizes', 'badgeos' ); ?>
                </a>
            </li>
            <?php if( ! empty( $addon_contents ) ) { ?>
                <li>
                    <a href="#badgeos_settings_addon_settings">
                        &nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-empire" aria-hidden="true"></i>&nbsp;&nbsp;
                        <?php _e( "Addon's Settings", 'badgeos' ); ?>
                    </a>
                </li>
            <?php } ?>
            <?php do_action( 'badgeos_general_settings_tab_header', $badgeos_settings ); ?>
        </ul>
        <div id="badgeos_settings_general_settings">
            <?php 
                settings_fields( 'badgeos_settings_group' ); 
                wp_nonce_field( 'badgeos_settings_nonce', 'badgeos_settings_nonce' );
            ?>
            <table cellspacing="0" width="100%">
                <tbody>
                    <?php if ( current_user_can( 'manage_options' ) ) { ?>
                        <tr valign="top"><th scope="row" width="50%"><label for="minimum_role"><?php _e( 'Minimum Role to Administer BadgeOS plugin: ', 'badgeos' ); ?></label></th>
                            <td width="50%">
                                <select id="minimum_role" name="badgeos_settings[minimum_role]">
                                    <option value="manage_options" <?php selected( $minimum_role, 'manage_options' ); ?>><?php _e( 'Administrator', 'badgeos' ); ?></option>
                                    <option value="delete_others_posts" <?php selected( $minimum_role, 'delete_others_posts' ); ?>><?php _e( 'Editor', 'badgeos' ); ?></option>
                                    <option value="publish_posts" <?php selected( $minimum_role, 'publish_posts' ); ?>><?php _e( 'Author', 'badgeos' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr valign="top"><th scope="row"><label for="submission_manager_role"><?php _e( 'Minimum Role to Administer Submissions/Nominations: ', 'badgeos' ); ?></label></th>
                            <td>
                                <select id="submission_manager_role" name="badgeos_settings[submission_manager_role]">
                                    <option value="manage_options" <?php selected( $submission_manager_role, 'manage_options' ); ?>><?php _e( 'Administrator', 'badgeos' ); ?></option>
                                    <option value="delete_others_posts" <?php selected( $submission_manager_role, 'delete_others_posts' ); ?>><?php _e( 'Editor', 'badgeos' ); ?></option>
                                    <option value="publish_posts" <?php selected( $submission_manager_role, 'publish_posts' ); ?>><?php _e( 'Author', 'badgeos' ); ?></option>
                                </select>
                            </td>
                        </tr>
                    <?php } ?>
                    <tr valign="top"><th scope="row"><label for="submission_email"><?php _e( 'Send email when submissions/nominations are received:', 'badgeos' ); ?></label></th>
                        <td>
                            <select id="submission_email" name="badgeos_settings[submission_email]">
                                <option value="enabled" <?php selected( $submission_email, 'enabled' ); ?>><?php _e( 'Enabled', 'badgeos' ) ?></option>
                                <option value="disabled" <?php selected( $submission_email, 'disabled' ); ?>><?php _e( 'Disabled', 'badgeos' ) ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top"><th scope="row"><label for="submission_email_addresses"><?php _e( 'Notification email addresses:', 'badgeos' ); ?></label></th>
                        <td>
                            <input id="submission_email_addresses" name="badgeos_settings[submission_email_addresses]" type="text" value="<?php echo esc_attr( $submission_email_addresses ); ?>" class="regular-text" />
                            <p class="description"><?php _e( 'Comma-separated list of email addresses to send submission/nomination notifications, in addition to the Site Admin email.', 'badgeos' ); ?></p>
                        </td>
                    </tr>
                    <tr valign="top"><th scope="row"><label for="remove_data_on_uninstall"><?php _e( 'Delete Data on Uninstall:', 'badgeos' ); ?></label></th>
                        <td>
                            <input id="remove_data_on_uninstall" name="badgeos_settings[remove_data_on_uninstall]" type="checkbox" <?php echo ( $remove_data_on_uninstall == "on" ) ? "checked" : ""; ?> class="regular-text" />
                            <p class="description"><?php _e( 'It will delete all BadgeOS DB entries on uninstall including posts, setting options, usermeta', 'badgeos' ); ?></p>
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
                    <tr valign="top"><th scope="row"><label for="log_entries"><?php _e( 'Log Entries:', 'badgeos' ); ?></label></th>
                        <td>
                            <select id="log_entries" name="badgeos_settings[log_entries]">
                                <option value="disabled" <?php selected( $log_entries, 'disabled' ); ?>><?php _e( 'Disabled', 'badgeos' ) ?></option>
                                <option value="enabled" <?php selected( $log_entries, 'enabled' ); ?>><?php _e( 'Enabled', 'badgeos' ) ?></option>
                            </select>
                        </td>
                    </tr>
                    <?php
                    if ( is_super_admin() ){
                        if ( is_multisite() ) {
                        ?>
                            <tr valign="top"><th scope="row"><label for="debug_mode"><?php _e( 'Show achievements earned across all sites on the network:', 'badgeos' ); ?></label></th>
                                <td>
                                    <select id="debug_mode" name="badgeos_settings[ms_show_all_achievements]">
                                        <option value="disabled" <?php selected( $ms_show_all_achievements, 'disabled' ); ?>><?php _e( 'Disabled', 'badgeos' ) ?></option>
                                        <option value="enabled" <?php selected( $ms_show_all_achievements, 'enabled' ); ?>><?php _e( 'Enabled', 'badgeos' ) ?></option>
                                    </select>
                                </td>
                            </tr>
                        <?php
                        }
                    }
                    do_action( 'badgeos_general_settings_fields', $badgeos_settings ); ?>
                </tbody>
            </table>
            <input type="submit" name="badgeos_settings_update_btn" class="button button-primary" value="<?php _e( 'Save Settings', 'badgeos' ); ?>">
        </div>
        <div id="badgeos_settings_default_views">
            <table cellspacing="0">
                <tbody>
                    <tr valign="top">
                        <th scope="row"><label for="achievement_list_shortcode_default_view"><?php _e( 'Achievement List Shortcode Default View:', 'badgeos' ); ?></label></th>
                        <td>
                            <select id="achievement_list_shortcode_default_view" name="badgeos_settings[achievement_list_shortcode_default_view]">
                                <option value="list" <?php selected( $achievement_list_default_view, "list" ); ?>><?php _e( 'List', 'badgeos' ); ?></option>
                                <option value="grid" <?php selected( $achievement_list_default_view, "grid" ); ?>><?php _e( 'Grid', 'badgeos' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="earned_achievements_shortcode_default_view"><?php _e( 'Earned Achievements Shortcode Default View:', 'badgeos' ); ?></label></th>
                        <td>
                            <select id="earned_achievements_shortcode_default_view" name="badgeos_settings[earned_achievements_shortcode_default_view]">
                                <option value="list" <?php selected( $earned_achievements_shortcode_default_view, "list" ); ?>><?php _e( 'List', 'badgeos' ); ?></option>
                                <option value="grid" <?php selected( $earned_achievements_shortcode_default_view, "grid" ); ?>><?php _e( 'Grid', 'badgeos' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="earned_ranks_shortcode_default_view"><?php _e( 'Earned Ranks List Shortcode Default View:', 'badgeos' ); ?></label></th>
                        <td>
                            <select id="earned_ranks_shortcode_default_view" name="badgeos_settings[earned_ranks_shortcode_default_view]">
                                <option value="list" <?php selected( $earned_ranks_shortcode_default_view, "list" ); ?>><?php _e( 'List', 'badgeos' ); ?></option>
                                <option value="grid" <?php selected( $earned_ranks_shortcode_default_view, "grid" ); ?>><?php _e( 'Grid', 'badgeos' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <?php do_action( 'badgeos_general_settings_default_view_fields', $badgeos_settings ); ?>
                </tbody>
            </table>
            <input type="submit" name="badgeos_settings_update_btn" class="button button-primary" value="<?php _e( 'Save Settings', 'badgeos' ); ?>">
        </div>
        <div id="badgeos_settings_thumb_sizes">
            <table cellspacing="0">
                <tbody>
                    <tr valign="top">
                        <th scope="row"><label for="badgeos_achievement_global_image_width"><?php _e( 'Achievement Image Size:', 'badgeos' ); ?></label></th>
                        <td>
                            <label>
                                <?php _e( 'Width:', 'badgeos' ); ?>
                                <input type="number" id="badgeos_achievement_global_image_width" value="<?php echo $badgeos_achievement_global_image_width;?>" name="badgeos_settings[badgeos_achievement_global_image_width]" />
                            </label>
                            <label>
                                <?php _e( 'Height:', 'badgeos' ); ?>
                                <input type="number" id="badgeos_achievement_global_image_height" value="<?php echo $badgeos_achievement_global_image_height;?>" name="badgeos_settings[badgeos_achievement_global_image_height]" />
                            </label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="badgeos_rank_global_image_width"><?php _e( 'Rank Image Size:', 'badgeos' ); ?></label></th>
                        <td>
                            <label>
                                <?php _e( 'Width:', 'badgeos' ); ?>
                                <input type="number" id="badgeos_rank_global_image_width" value="<?php echo $badgeos_rank_global_image_width;?>" name="badgeos_settings[badgeos_rank_global_image_width]" />
                            </label>
                            <label>
                                <?php _e( 'Height:', 'badgeos' ); ?>
                                <input type="number" id="badgeos_rank_global_image_height" value="<?php echo $badgeos_rank_global_image_height;?>" name="badgeos_settings[badgeos_rank_global_image_height]" />
                            </label>
                        </td>
                    </tr>
                    <?php do_action( 'badgeos_general_settings_thumb_size_fields', $badgeos_settings ); ?>
                </tbody>
            </table>
            <input type="submit" name="badgeos_settings_update_btn" class="button button-primary" value="<?php _e( 'Save Settings', 'badgeos' ); ?>">
        </div>
        <?php if( ! empty( $addon_contents ) ) { ?>
            <div id="badgeos_settings_addon_settings">
                <table cellspacing="0">
                    <tbody>
                        <?php do_action( 'badgeos_settings', $badgeos_settings ); ?>
                    </tbody>
                </table>
                <input type="submit" name="badgeos_settings_update_btn" class="button button-primary" value="<?php _e( 'Save Settings', 'badgeos' ); ?>">
            </div>
        <?php } ?>
        <?php do_action( 'badgeos_general_settings_tab_content', $badgeos_settings ); ?>
    </form>
</div>