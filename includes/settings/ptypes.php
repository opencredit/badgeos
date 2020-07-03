<?php 
    $badgeos_settings = get_option( 'badgeos_settings' );

    //load settings
    $achievement_step_post_type 	= ( ! empty ( $badgeos_settings['achievement_step_post_type'] ) ) ? $badgeos_settings['achievement_step_post_type'] : 'step';
    $achievement_main_post_type 	= ( ! empty ( $badgeos_settings['achievement_main_post_type'] ) ) ? $badgeos_settings['achievement_main_post_type'] : 'achievement-type';
    $ranks_main_post_type 			= ( ! empty ( $badgeos_settings['ranks_main_post_type'] ) ) ? $badgeos_settings['ranks_main_post_type'] : 'rank_types';
    $ranks_step_post_type 			= ( ! empty ( $badgeos_settings['ranks_step_post_type'] ) ) ? $badgeos_settings['ranks_step_post_type'] : 'rank_requirement';
    $points_main_post_type 			= ( ! empty ( $badgeos_settings['points_main_post_type'] ) ) ? $badgeos_settings['points_main_post_type'] : 'point_type';
    $points_award_post_type 		= ( ! empty ( $badgeos_settings['points_award_post_type'] ) ) ? $badgeos_settings['points_award_post_type'] : 'point_award';
    $points_deduct_post_type 		= ( ! empty ( $badgeos_settings['points_deduct_post_type'] ) ) ? $badgeos_settings['points_deduct_post_type'] : 'point_deduct';
    $default_point_type 			= ( ! empty ( $badgeos_settings['default_point_type'] ) ) ? $badgeos_settings['default_point_type'] : '';
    $badgeos_admin_side_tab 	= ( ! empty ( $badgeos_settings['side_tab'] ) ) ? $badgeos_settings['side_tab'] : '#badgeos_settings_achievement_ptype_settings';  
?>
<div id="badgeos-setting-tabs">
    <div class="tab-title"><?php _e( 'Post Types', 'badgeos' ); ?></div>
    <?php settings_errors(); ?>
    <form method="POST" name="badgeos_frm_general_tab" id="badgeos_frm_general_tab" action="options.php">
        <input type="hidden" id="tab_action" name="badgeos_settings[tab_action]" value="ptypes" />
        <input type="hidden" id="badgeos_admin_side_tab" name="badgeos_settings[side_tab]" value="<?php echo $badgeos_admin_side_tab;?>" />
        <ul class="badgeos_sidebar_tab_links">
            <li>
                <a href="#badgeos_settings_achievement_ptype_settings">
                    &nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-trophy" aria-hidden="true"></i>&nbsp;&nbsp;
                    <?php _e( 'Achievement Types', 'badgeos' ); ?>
                </a>
            </li>
            <li>
                <a href="#badgeos_settings_rank_ptype_settings">
                    &nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-star-o" aria-hidden="true"></i>&nbsp;&nbsp;
                    <?php _e( 'Rank Types', 'badgeos' ); ?>
                </a>
            </li>
            <li>
                <a href="#badgeos_settings_points_ptype_settings">
                    &nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-bank" aria-hidden="true"></i>&nbsp;&nbsp;
                    <?php _e( 'Point Types', 'badgeos' ); ?>
                </a>
            </li>
            <?php do_action( 'badgeos_ptype_settings_tab_header', $badgeos_settings ); ?>
        </ul>
        <div id="badgeos_settings_achievement_ptype_settings">
            <?php 
                settings_fields( 'badgeos_settings_group' ); 
                wp_nonce_field( 'badgeos_settings_nonce', 'badgeos_settings_nonce' );
            ?>
            <table cellspacing="0" width="100%">
                <tbody>
                    <tr valign="top">
                        <th scope="row"><label for="achievement_main_post_type"><?php _e( 'Achievement Post Type:', 'badgeos' ); ?></label></th>
                        <td>
                            <input id="achievement_main_post_type" name="badgeos_settings[achievement_main_post_type]" type="text" value="<?php echo esc_attr( $achievement_main_post_type ); ?>" class="regular-text" />
                            <p class="description"><?php _e( 'Achievement Type Slug.', 'badgeos' ); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="achievement_step_post_type"><?php _e( 'Achievement Step Post Type:', 'badgeos' ); ?></label></th>
                        <td>
                            <input id="achievement_step_post_type" name="badgeos_settings[achievement_step_post_type]" type="text" value="<?php echo esc_attr( $achievement_step_post_type ); ?>" class="regular-text" />
                            <p class="description"><?php _e( 'Achievement Step Type Slug.', 'badgeos' ); ?></p>
                        </td>
                    </tr>
                    <?php do_action( 'badgeos_settings_achievement_ptype_fields', $badgeos_settings ); ?>
                    <tr valign="top">
                        <td colspan="2">
                            <input type="submit" name="badgeos_settings_update_btn" class="button button-primary" value="<?php _e( 'Save Settings', 'badgeos' ); ?>">                
                        </td>
                    </tr>
                </tbody>
            </table>
            <p><b><?php echo __( 'Note' . ': ', 'badgeos' ); ?></b><?php echo __( 'We strongly recommend you to take back up of database first when updating the post_types slugs. Also make sure that slugs should not exceeded than 18 characters.', 'badgeos'  ); ?></p>
        </div>
        <div id="badgeos_settings_rank_ptype_settings">
            <table cellspacing="0" width="100%">
                <tbody>
                    <tr valign="top">
                        <th scope="row"><label for="ranks_main_post_type"><?php _e( 'Rank Post Type:', 'badgeos' ); ?></label></th>
                        <td>
                            <input id="ranks_main_post_type" name="badgeos_settings[ranks_main_post_type]" type="text" value="<?php echo esc_attr( $ranks_main_post_type ); ?>" class="regular-text" />
                            <p class="description"><?php _e( 'Ranks Type Slug.', 'badgeos' ); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="ranks_step_post_type"><?php _e( 'Rank Step Post Type:', 'badgeos' ); ?></label></th>
                        <td>
                            <input id="ranks_step_post_type" name="badgeos_settings[ranks_step_post_type]" type="text" value="<?php echo esc_attr( $ranks_step_post_type ); ?>" class="regular-text" />
                            <p class="description"><?php _e( 'Ranks Step Type Slug.', 'badgeos' ); ?></p>
                        </td>
                    </tr>
                    <?php do_action( 'badgeos_settings_rank_ptype_fields', $badgeos_settings ); ?>
                    <tr valign="top">
                        <td colspan="2">
                            <input type="submit" name="badgeos_settings_update_btn" class="button button-primary" value="<?php _e( 'Save Settings', 'badgeos' ); ?>">                
                        </td>
                    </tr>
                </tbody>
            </table>
            <p><b><?php echo __( 'Note' . ': ', 'badgeos' ); ?></b><?php echo __( 'We strongly recommend you to take back up of database first when updating the post_types slugs. Also make sure that slugs should not exceeded than 18 characters.', 'badgeos'  ); ?></p>
        </div>
        <div id="badgeos_settings_points_ptype_settings">
            <table cellspacing="0" width="100%">
                <tbody>
                    <tr valign="top">
					    <th scope="row" width="30%"><label for="points_main_post_type"><?php _e( 'Point Type:', 'badgeos' ); ?></label></th>
                        <td width="70%">
                            <input id="points_main_post_type" name="badgeos_settings[points_main_post_type]" type="text" value="<?php echo esc_attr( $points_main_post_type ); ?>" class="regular-text" />
                            <p class="description"><?php _e( 'Point Type Slug.', 'badgeos' ); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="points_award_post_type"><?php _e( 'Point Award:', 'badgeos' ); ?></label></th>
                        <td>
                            <input id="points_award_post_type" name="badgeos_settings[points_award_post_type]" type="text" value="<?php echo esc_attr( $points_award_post_type ); ?>" class="regular-text" />
                            <p class="description"><?php _e( 'Point Award Type Slug.', 'badgeos' ); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="points_deduct_post_type"><?php _e( 'Point Deduct:', 'badgeos' ); ?></label></th>
                        <td>
                            <input id="points_deduct_post_type" name="badgeos_settings[points_deduct_post_type]" type="text" value="<?php echo esc_attr( $points_deduct_post_type ); ?>" class="regular-text" />
                            <p class="description"><?php _e( 'Point Deduct Type Slug.', 'badgeos' ); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="points_default_point_type"><?php _e( 'Default Point Type:', 'badgeos' ); ?></label></th>
                        <td>
                            <?php
                                $points = badgeos_get_point_types();
                            ?>
                            <select id="points_default_point_type" name="badgeos_settings[default_point_type]">
                                <?php foreach( $points as $point ) {?>
                                    <option value="<?php echo $point->ID;?>" <?php selected( $default_point_type, $point->ID ); ?>><?php echo $point->post_title;?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <?php do_action( 'badgeos_settings_points_ptype_fields', $badgeos_settings ); ?>
                    <tr valign="top">
                        <td colspan="2">
                            <input type="submit" name="badgeos_settings_update_btn" class="button button-primary" value="<?php _e( 'Save Settings', 'badgeos' ); ?>">                
                        </td>
                    </tr>
                </tbody>
            </table>
            <p><b><?php echo __( 'Note' . ': ', 'badgeos' ); ?></b><?php echo __( 'We strongly recommend you to take back up of database first when updating the post_types slugs. Also make sure that slugs should not exceeded than 18 characters.', 'badgeos'  ); ?></p>
        </div>
        <?php do_action( 'badgeos_ptype_settings_tab_content', $badgeos_settings ); ?>
    </form>
</div>