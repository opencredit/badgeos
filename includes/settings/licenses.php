<?php 
    $badgeos_settings = get_option( 'badgeos_settings' );
    $badgeos_admin_side_tab 	= ( ! empty ( $badgeos_settings['side_tab'] ) ) ? $badgeos_settings['side_tab'] : '#badgeos_settings_license_tab';
    $licensed_addons = apply_filters( 'badgeos_licensed_addons', array() );
    if( count( $licensed_addons ) > 0 ) {
?>
    <div id="badgeos-setting-tabs">
        <div class="tab-title"><?php _e( 'Licenses', 'badgeos' ); ?></div>
        <?php settings_errors(); ?>
        <form method="POST" name="badgeos_frm_general_tab" id="badgeos_frm_general_tab" action="options.php">
            <input type="hidden" id="tab_action" name="badgeos_settings[tab_action]" value="licenses" />
            <input type="hidden" id="badgeos_admin_side_tab" name="badgeos_settings[side_tab]" value="<?php echo $badgeos_admin_side_tab;?>" />
            <?php 
                settings_fields( 'badgeos_settings_group' ); 
                wp_nonce_field( 'badgeos_settings_nonce', 'badgeos_settings_nonce' );
            ?>
            <ul class="badgeos_sidebar_tab_links">
                <li>
                    <a id="badgeos_settings_license_tab_link" href="#badgeos_settings_license_tab">
                        &nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-drivers-license-o" aria-hidden="true"></i>&nbsp;&nbsp;
                        <?php _e( 'Licenses', 'badgeos' ); ?>
                    </a>
                </li>
                <?php do_action( 'badgeos_license_tab_header' ); ?>
            </ul>
            <div id="badgeos_settings_license_tab" class="badgeos_settings_license_tab" >
                <table cellspacing="0" width="90%">
                    <?php do_action( 'badgeos_license_settings', $badgeos_settings ); ?>
                </table>
                <input type="submit" name="badgeos_settings_update_btn" class="button button-primary" value="<?php _e( 'Save Settings', 'badgeos' ); ?>">                
            </div>
            <?php do_action( 'badgeos_license_tab_content' ); ?>
        </form>
    </div>
<?php } ?>