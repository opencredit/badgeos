<?php
/**
 * System Tools
 *
 * @package badgeos
 * @subpackage Tools
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://badgeos.org
 */


global $wpdb;

/**
 * Get WordPress theme info
 */
$theme_data   = wp_get_theme();
$theme        = $theme_data->Name . ' (' . $theme_data->Version . ')';
$parent_theme = $theme_data->Template;

if ( ! empty( $parent_theme ) ) {
    $parent_theme_data = wp_get_theme( $parent_theme );
    $parent_theme      = $parent_theme_data->Name . ' (' . $parent_theme_data->Version . ')';
}

/**
 * Retrieve current plugin information
 */
if( ! function_exists( 'get_plugins' ) ) {
    include ABSPATH . '/wp-admin/includes/plugin.php';
}

$plugins = get_plugins();
$active_plugins = get_option( 'active_plugins', array() );
$active_plugins_output = '';

foreach ( $plugins as $plugin_path => $plugin ) {

    /**
     * If the plugin isn't active, don't show it.
     */
    if ( ! in_array( $plugin_path, $active_plugins ) )
        continue;

    $active_plugins_output .= $plugin['Name'] . ' (' . $plugin['Version'] . ')' . '<br>';
}

$points_types = badgeos_get_point_types();
$points_types_output = '';

if( !empty( $points_types ) ) {
    foreach( $points_types as $key => $item ) {
        $points_types_output .= $item->post_title. ' - ' . get_post_meta($item->ID,'_credits_plural_name', true ) . ' - ' . $item->post_name . ' (#' . $item->ID  . ')' . '<br>';
    }
}

if( isset( $GLOBALS['badgeos']->achievement_types ) )
    $achievement_types = $GLOBALS['badgeos']->achievement_types;
else
    $achievement_types = array();

$achievement_types_output = '';

if(!empty($achievement_types)) {
    foreach ( $achievement_types as $achievement_type_slug => $achievement_type ) {

        $achievement_types_output .= $achievement_type['single_name'] . ' - ' . $achievement_type['plural_name'] . ' - ' . $achievement_type_slug . '<br>';
    }
}

/**
 * Get all rank types
 */
if( isset( $GLOBALS['badgeos']->ranks_types ) )
    $rank_types = $GLOBALS['badgeos']->ranks_types;
else
    $rank_types = array();
$rank_types_output = '';

if( !empty( $rank_types ) ) {
    foreach ( $rank_types as $rank_type_slug => $rank_type ) {

        $rank_types_output .= $rank_type['single_name'] . ' - ' . $rank_type['plural_name'] . ' - ' . $rank_type_slug . '<br>';
    }
}


function show_symbol_on_tool_page($type) {
    if($type=='success') {
        echo '<div class="badgeos_success_symbol">&nbsp;</div>';
    } else if($type=='failure') {
        echo '<div class="badgeos_failure_symbol">&nbsp;</div>';
    }
}

/**
 * Return the hosting provider this site is using if possible
 *
 * Taken from Easy Digital Downloads
 *
 * @since 1.1.5
 *
 * @return mixed string $host if detected, false otherwise
 */
function badgeos_get_hosting_provider() {
    $host = false;

    if( defined( 'WPE_APIKEY' ) ) {
        $host = 'WP Engine';
    } elseif( defined( 'PAGELYBIN' ) ) {
        $host = 'Pagely';
    } elseif( DB_HOST == 'localhost:/tmp/mysql5.sock' ) {
        $host = 'ICDSoft';
    } elseif( DB_HOST == 'mysqlv5' ) {
        $host = 'NetworkSolutions';
    } elseif( strpos( DB_HOST, 'ipagemysql.com' ) !== false ) {
        $host = 'iPage';
    } elseif( strpos( DB_HOST, 'ipowermysql.com' ) !== false ) {
        $host = 'IPower';
    } elseif( strpos( DB_HOST, '.gridserver.com' ) !== false ) {
        $host = 'MediaTemple Grid';
    } elseif( strpos( DB_HOST, '.pair.com' ) !== false ) {
        $host = 'pair Networks';
    } elseif( strpos( DB_HOST, '.stabletransit.com' ) !== false ) {
        $host = 'Rackspace Cloud';
    } elseif( strpos( DB_HOST, '.sysfix.eu' ) !== false ) {
        $host = 'SysFix.eu Power Hosting';
    } elseif( strpos( $_SERVER['SERVER_NAME'], 'Flywheel' ) !== false ) {
        $host = 'Flywheel';
    } else {
        // Adding a general fallback for data gathering
        $host = 'DBH: ' . DB_HOST . ', SRV: ' . $_SERVER['SERVER_NAME'];
    }

    return $host;
}

$badgeos_settings = get_option( 'badgeos_settings' );

$logs = wp_count_posts('badgeos-log-entry');
$logs_exists = ( intval( $logs->publish ) > 0 ) ? true : false;

$minimum_role = ( isset( $badgeos_settings['minimum_role'] ) ) ? $badgeos_settings['minimum_role'] : 'manage_options';
$debug_mode = ( isset( $badgeos_settings['debug_mode'] ) && 'disabled' !=  $badgeos_settings['debug_mode'] ) ? $badgeos_settings['debug_mode'] : 'disabled';
$log_entries = ( isset( $badgeos_settings['log_entries'] ) && 'disabled' !=  $badgeos_settings['log_entries'] ) ? $badgeos_settings['log_entries'] : 'disabled';
$ms_show_all_achievements = ( isset( $badgeos_settings['ms_show_all_achievements'] ) ) ? $badgeos_settings['ms_show_all_achievements'] : 'disabled';
$remove_data_on_uninstall = ( isset( $badgeos_settings['remove_data_on_uninstall'] ) && $badgeos_settings['remove_data_on_uninstall'] == 'on' ) ? $badgeos_settings['remove_data_on_uninstall'] : '';

?>
<div id="system-tabs">
    <div class="tab-title"><?php _e( 'System Tools', 'badgeos' ); ?></div>
    <ul>
        <li>
            <a href="#server_info">
                &nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-server" aria-hidden="true"></i>&nbsp;&nbsp;
                <?php _e( 'Server Info', 'badgeos' ); ?>
            </a>
        </li>
        <li>
            <a href="#php_config">
                &nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-cog" aria-hidden="true"></i>&nbsp;&nbsp;
                <?php _e( 'PHP Configuration', 'badgeos' ); ?>
            </a>
        </li>
        <li>
            <a href="#wordpress_info">
                &nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-wordpress" aria-hidden="true"></i>&nbsp;&nbsp;
                <?php _e( 'WordPress Info', 'badgeos' ); ?>
            </a>
        </li>
        <li>
            <a href="#badgeos_info">
                &nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-circle-o-notch" aria-hidden="true"></i>&nbsp;&nbsp;
                <?php _e( 'BadgeOS Info', 'badgeos' ); ?>
            </a>
        </li>
    </ul>
    <div id="server_info">
        <div class="tab-title"><?php _e( 'Server Info', 'badgeos' ); ?></div>
        <table cellspacing="0">
            <tbody>
            <tr>
                <th scope="row">
                    <label for="hosting_provider">
                        <?php _e( 'Hosting Provider:', 'badgeos' ); ?>
                    </label>
                </th>
                <td><?php echo badgeos_get_hosting_provider(); ?></td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="php_version">
                        <?php _e( 'PHP Version:', 'badgeos' ); ?>
                    </label>
                </th>
                <td><?php echo phpversion(); ?></td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="msql_version">
                        <?php _e( 'MySQL Version:', 'badgeos' ); ?>
                    </label>
                </th>
                <td><?php echo $wpdb->db_version(); ?></td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="web_server_info">
                        <?php _e( 'Webserver Info:', 'badgeos' ); ?>
                    </label>
                </th>
                <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
            </tr>
            </tbody>
        </table>
    </div>
    <div id="php_config">
        <div class="tab-title"><?php _e( 'PHP Configuration', 'badgeos' ); ?></div>
        <table cellspacing="0">
            <tbody>
            <tr>
                <th scope="row">
                    <label for="memory_limit">
                        <?php _e( 'Memory Limit:', 'badgeos' ); ?>
                    </label>
                </th>
                <td><?php echo ini_get( 'memory_limit' ); ?></td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="time_limit">
                        <?php _e( 'Time Limit:', 'badgeos' ); ?>
                    </label>
                </th>
                <td><?php echo ini_get( 'max_execution_time' ); ?></td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="upload_max_size">
                        <?php _e( 'Upload Max Size:', 'badgeos' ); ?>
                    </label>
                </th>
                <td><?php echo ini_get( 'upload_max_filesize' ); ?></td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="post_max_size">
                        <?php _e( 'Post Max Size:', 'badgeos' ); ?>
                    </label>
                </th>
                <td><?php echo ini_get( 'post_max_size' ); ?></td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="max_input_vars">
                        <?php _e( 'Max Input Vars:', 'badgeos' ); ?>
                    </label>
                </th>
                <td><?php echo ini_get( 'max_input_vars' ); ?></td>
            </tr>
            <tr>
                <th scope="row"><label for="display_errors">
                        <?php _e( 'Display Errors:', 'badgeos' ); ?>
                    </label>
                </th>
                <td><?php echo ( ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A' ); ?></td>
            </tr>
            </tbody>
        </table>
    </div>
    <div id="wordpress_info">
        <div class="tab-title"><?php _e( 'WordPress Info', 'badgeos' ); ?></div>
        <table cellspacing="0">
            <tbody>
            <tr>
                <th scope="row"><label for="site_url">
                        <?php _e( 'Site URL:', 'badgeos' ); ?>
                    </label>
                </th>
                <td><?php echo site_url(); ?></td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="home_url">
                        <?php _e( 'Home URL:', 'badgeos' ); ?>
                    </label>
                </th>
                <td><?php echo home_url(); ?></td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="multisite">
                        <?php _e( 'Multisite', 'badgeos' ); ?>
                    </label>
                </th>
                <td><?php echo ( is_multisite() ? 'Yes' : 'No' ); ?></td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="version">
                        <?php _e( 'Version', 'badgeos' ); ?>
                    </label>
                </th>
                <td><?php echo get_bloginfo( 'version' ); ?></td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="language">
                        <?php _e( 'Language:', 'badgeos' ); ?>
                    </label>
                </th>
                <td><?php echo get_locale(); ?></td>
            </tr>
            <tr>
                <th scope="row"><label for="permalink_structure">
                        <?php _e( 'Permalink Structure:', 'badgeos' ); ?>
                    </label>
                </th>
                <td><?php echo ( get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default' ); ?></td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="absolute_path">
                        <?php _e( 'Absolute Path:', 'badgeos' ); ?>
                    </label>
                </th>
                <td><?php echo ABSPATH; ?></td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="debug">
                        <?php _e( 'Debug:', 'badgeos' ); ?>
                    </label>
                </th>
                <td><?php echo ( defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' : 'Disabled' : 'Not set' ); ?></td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="memory_limit">
                        <?php _e( 'Memory Limit:', 'badgeos' ); ?>
                    </label>
                </th>
                <td><?php echo WP_MEMORY_LIMIT; ?></td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="table_prefix">
                        <?php _e( 'Table Prefix:', 'badgeos' ); ?>
                    </label>
                </th>
                <td><?php echo $wpdb->prefix; ?></td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="active_theme">
                        <?php _e( 'Active Theme:', 'badgeos' ); ?>
                    </label>
                </th>
                <td><?php echo $theme; ?></td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="parent_theme">
                        <?php _e( 'Parent Theme:', 'badgeos' ); ?>
                    </label>
                </th>
                <td><?php echo $parent_theme; ?></td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="active_plugins">
                        <?php _e( 'Active Plugins:', 'badgeos' ); ?>
                    </label>
                </th>
                <td><?php echo $active_plugins_output; ?></td>
            </tr>
            </tbody>
        </table>
    </div>
    <div id="badgeos_info">
        <div class="tab-title"><?php _e( 'BadgeOS Info', 'badgeos' ); ?></div>
        <table cellspacing="0">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="point_types">
                            <?php _e( 'Points Types:', 'badgeos' ); ?>
                        </label>
                    </th>
                    <td><?php echo $points_types_output; ?></td>
                </tr>
                <tr>
                    <th scope="row"><label for="achievement_types">
                            <?php _e( 'Achievement Types:', 'badgeos' ); ?>
                        </label>
                    </th>
                    <td><?php echo $achievement_types_output; ?></td>
                </tr>
                <tr>
                    <th scope="row"><label for="rank_types">
                            <?php _e( 'Rank Types:', 'badgeos' ); ?>
                        </label>
                    </th>
                    <td><?php echo $rank_types_output; ?></td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="min_role_to-administrate">
                            <?php _e( 'Min Role to Administer Plugin?', 'badgeos' ); ?>
                        </label>
                    </th>
                    <td><?php echo $minimum_role; ?></td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="log_enabled">
                            <?php _e( 'Log Enabled: ', 'badgeos' ); ?>
                        </label>
                    </th>
                    <td><?php echo ( $log_entries != 'disabled' )? show_symbol_on_tool_page('success') : show_symbol_on_tool_page('failure'); ?></td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="log_exists">
                            <?php _e( 'Log Exists: ', 'badgeos' ); ?>
                        </label>
                    </th>
                    <td><?php echo ( $logs_exists ? show_symbol_on_tool_page('success') : show_symbol_on_tool_page('failure') ); ?></td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="debug_mode">
                            <?php _e( 'Debug Mode?', 'badgeos' ); ?>
                        </label>
                    </th>
                    <td><?php echo ( 'disabled' != $debug_mode ) ? show_symbol_on_tool_page('success') : show_symbol_on_tool_page('failure') ; ?></td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="delete_data_on_uninstall">
                            <?php _e( 'Delete Data on Uninstall? ', 'badgeos' ); ?>
                        </label>
                    </th>
                    <td><?php echo ( $remove_data_on_uninstall == 'on' ) ? show_symbol_on_tool_page('success') : show_symbol_on_tool_page('failure') ; ?></td>
                </tr>
                <?php do_action( 'badgeos_tools_badgeos_information', $badgeos_settings );?>
            </tbody>
        </table>
    </div>
</div>