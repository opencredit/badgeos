<?php
/**
 * Achievement Tools
 *
 * @package BadgeOS
 * @subpackage Tools
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://badgeos.org
 */

$badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
$achievement_types = badgeos_get_achievement_types_slugs();
$args = array(
    'role'         => '',
    'orderby'      => 'nicename',
    'order'        => 'ASC',
    'count_total'  => false,
    'fields'       => array('ID','user_nicename'),
);
$wp_users = get_users( $args );
?>
<div id="achievement-tabs">
    <div class="tab-title"><?php _e( 'Achievement Tools', 'badgeos' ); ?></div>
    <ul>
        <li>
            <a href="#achievement_bulk_award">
                &nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-trophy" aria-hidden="true"></i>&nbsp;&nbsp;
                <?php _e( 'Award Achievement In Bulk', 'badgeos' ); ?>
            </a>
        </li>
        <li>
            <a href="#achievement_bulk_revoke">
                &nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-star-o" aria-hidden="true"></i>&nbsp;&nbsp;
                <?php _e( 'Revoke Achievement In Bulk', 'badgeos' ); ?>
            </a>
        </li>
    </ul>
    <div id="achievement_bulk_award">
        <form method="POST" class="achievement-bulk-award" action="">
            <table cellspacing="0">
                <tbody>
                <tr>
                    <th scope="row"><label for="achievement_types"><?php _e( 'Achievements to Award', 'badgeos' ); ?></label></th>
                    <td>
                        <select id="achievement_types_to_award" data-placeholder="Select an achievement" name="badgeos_tools[award_achievement_types][]" multiple="multiple" class="badgeos-select">
                            <?php
                            if ( is_array( $achievement_types ) && ! empty( $achievement_types ) ) {
                                foreach ( $achievement_types as $achievement_type ) {
                                    if( $achievement_type == trim( $badgeos_settings['achievement_step_post_type'] ) ) {
                                        continue;
                                    }
                                    $achievements = get_posts( array(
                                        'post_type'         =>	$achievement_type,
                                        'posts_per_page'    =>	-1,
                                        'suppress_filters'  => false,
                                        'post_status'       => 'publish',
                                    ) );
                                    foreach ( $achievements as $achievement ) {

                                        echo '<option value="' . $achievement->ID . '">' . $achievement->post_title . '</option>';
                                    }
                                }
                            }
                            ?>
                        </select>
                        <span class="tool-hint"><?php _e( 'Choose the achievements to award', 'badgeos' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="all_users"><?php _e( 'Award to All Users', 'badgeos' ); ?></label></th>
                    <td>
                        <div class="form-switcher form-switcher-lg form-switcher-sm-phone">
                            <input type="checkbox" name="badgeos_tools[award_all_users]" id="award-achievement" data-com.bitwarden.browser.user-edited="yes">
                            <label class="switcher" for="award-achievement"></label>
                        </div>
                        <span class="tool-hint"><?php _e( 'Check this point to award achievements to all users', 'badgeos' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="users"><?php _e( 'Users to Award', 'badgeos' ); ?></label></th>
                    <td>
                        <select id="badgeos-award-users" name="badgeos_tools[award_users][]" data-placeholder="Select a user" multiple="multiple" class="badgeos-select">
                            <?php
                            if( $wp_users ) {
                                foreach( $wp_users as $user ) {
                                    ?>
                                <option value="<?php echo $user->ID; ?>" <?php selected( $user->ID, 'disabled' ); ?>>
                                    <?php _e( $user->user_nicename, 'badgeos' ) ?>
                                    </option><?php
                                }
                            } ?>
                        </select>
                        <span class="tool-hint"><?php _e( 'Choose users to award', 'badgeos' ); ?></span>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php wp_nonce_field( 'achievement_bulk_award', 'achievement_bulk_award' ); ?>
            <input type="hidden" name="action" value="award_bulk_achievement">
            <input type="submit" name="award_bulk_achievement" class="button button-primary" value="<?php _e( 'Award Achievements', 'badgeos' ); ?>">
        </form>
    </div>
    <div id="achievement_bulk_revoke">
        <form method="POST" class="achievement_bulk_revoke" action="">
            <table cellspacing="0">
                <tbody>
                <tr>
                    <th scope="row"><label for="achievement_types"><?php _e( 'Achievements to Revoke', 'badgeos' ); ?></label></th>
                    <td>
                        <select id="achievement_types_to_revoke" data-placeholder="Select an achievement" name="badgeos_tools[revoke_achievement_types][]" multiple="multiple" class="badgeos-select">
                            <?php
                            if( $wp_users ) {
                                $plucked = array();
                                foreach ( $wp_users as $wp_user ) {
                                    $user_achievements = badgeos_get_user_achievements( array( 'user_id' => $wp_user->ID ) );
                                    if( $user_achievements ) {
                                        foreach( $user_achievements as $user_achievement ) {
                                            if( in_array( $user_achievement->ID, $plucked ) ) {
                                                continue;
                                            }

                                            $plucked[] = $user_achievement->ID;
                                            echo '<option value="' . $user_achievement->ID . '">' . $user_achievement->achievement_title . '</option>';
                                        }
                                    }
                                }
                            }
                            ?>
                        </select>
                        <span class="tool-hint"><?php _e( 'Check this point to revoke achievements to all users', 'badgeos' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="all_users"><?php _e( 'Revoke to All Users', 'badgeos' ); ?></label></th>
                    <td>
                        <div class="form-switcher form-switcher-lg">
                            <input type="checkbox" name="badgeos_tools[revoke_all_users]" id="revoke-achievement" data-com.bitwarden.browser.user-edited="yes">
                            <label class="switcher" for="revoke-achievement"></label>
                        </div>
                        <span class="tool-hint"><?php _e( 'Check this point to revoke achievements to all users', 'badgeos' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="users"><?php _e( 'Users to Revoke', 'badgeos' ); ?></label></th>
                    <td>
                        <select id="badgeos-revoke-users" name="badgeos_tools[revoke_users][]" data-placeholder="Select a user" multiple="multiple" class="badgeos-select">
                            <?php
                            if( $wp_users ) {
                                foreach( $wp_users as $user ) {
                                    ?>
                                    <option value="<?php echo $user->ID; ?>" <?php selected( $user->ID, 'disabled' ); ?>>
                                        <?php _e( $user->user_nicename, 'badgeos' ) ?>
                                    </option>
                                <?php }
                            } ?>
                        </select>
                        <span class="tool-hint"><?php _e( 'Choose users to revoke', 'badgeos' ); ?></span>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php wp_nonce_field( 'achievement_bulk_revoke', 'achievement_bulk_revoke' ); ?>
            <input type="hidden" name="action" value="revoke_bulk_achievement">
            <input type="submit" name="revoke_bulk_achievement" class="button button-primary" value="<?php _e( 'Revoke Achievements', 'badgeos' ); ?>">
        </form>
    </div>
</div>