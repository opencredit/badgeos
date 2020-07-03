<?php 
    $badgeos_settings = get_option( 'badgeos_settings' );  
    $badgeos_admin_side_tab 	= ( ! empty ( $badgeos_settings['side_tab'] ) ) ? $badgeos_settings['side_tab'] : '#badgeos_settings_db_upgrades_settings'; 
?>
<div id="badgeos-setting-tabs">
    <div class="tab-title"><?php _e( 'Data Upgrades', 'badgeos' ); ?></div>
    <form method="POST" name="badgeos_frm_general_tab" id="badgeos_frm_general_tab" action="options.php">
        <input type="hidden" id="tab_action" name="badgeos_settings[tab_action]" value="dbupgrades" />
        <input type="hidden" id="badgeos_admin_side_tab" name="badgeos_settings[side_tab]" value="<?php echo $badgeos_admin_side_tab;?>" />
        <ul class="badgeos_sidebar_tab_links">
            <li>
                <a href="#badgeos_settings_db_upgrades_settings">
                    &nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-database" aria-hidden="true"></i>&nbsp;&nbsp;
                    <?php _e( 'DB Upgrade', 'badgeos' ); ?>
                </a>
            </li>
            <li>
                <a href="#badgeos_settings_upgrade_achievements_settings">
                    &nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-cloud-download" aria-hidden="true"></i>&nbsp;&nbsp;
                    <?php _e( 'Upgrade Achievement', 'badgeos' ); ?>
                </a>
            </li>
            <?php do_action( 'badgeos_dupgrades_settings_tab_header', $badgeos_settings ); ?>
        </ul>
        <div id="badgeos_settings_db_upgrades_settings">
            <table class="form-table badgeos-migration-form-table">
                <tr valign="top">
                    <td>
                        <h3><?php _e( 'BadgeOS DB Upgrade', 'badgeos' ); ?></h3>
                    </td>
                </tr>
                <tr valign="top">
                    <td>
                        <p><?php _e( 'Please click on "Upgrade 3.0 DB" button below to update the users\' existing achievements and points in the BadgeOS table.', 'badgeos' ); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <td>
                        <?php
                        $is_badgeos_all_achievement_db_updated = get_option( 'badgeos_all_achievement_db_updated', 'No' );
                        if( $is_badgeos_all_achievement_db_updated!='Yes' ) {
                            ?>
                            <input type="button" id="badgeos_migrate_meta_to_db" class="button-primary" value="<?php _e( 'Upgrade 3.0 DB', 'badgeos' ); ?>" />
                        <?php } else { ?>
                            <input type="button" id="badgeos_migrate_meta_to_db" class="button-primary" value="<?php _e( 'Upgrade 3.0 DB', 'badgeos' ); ?>" />
                        <?php } ?>

                        <div class="badgeos_migrate_meta_to_db_message"></div>
                    </td>
                </tr>
            </table>
        </div>
        <div id="badgeos_settings_upgrade_achievements_settings">
            <table class="form-table badgeos-migration-form-table">
                <tr valign="top">
                    <td>
                        <h3><?php _e( 'BadgeOS Upgrade Achievement', 'badgeos' ); ?></h3>
                    </td>
                </tr>
                <tr valign="top">
                    <td>
                        <p><?php _e( 'Please click on "Upgrade Achievements" button below to update the existing achievement points with the point types.', 'badgeos' ); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <td>
                        <input type="button" id="badgeos_migrate_fields_single_to_multi" class="button-primary" value="<?php _e( 'Upgrade Achievements', 'badgeos' ); ?>" />
                        <div class="badgeos_migrate_fields_single_to_multi_message"></div>
                    </td>
                </tr>
            </table>
        </div>
        <?php do_action( 'badgeos_dupgrades_settings_tab_content', $badgeos_settings ); ?>
    </form>
</div>