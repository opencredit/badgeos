<?php
/**
 * Rank Tools
 *
 * @package badgeos
 * @subpackage Tools
 * @author earningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://badgeos.org
 */

$rank_types = badgeos_get_ranks();
$args = array(
    'role'         => '',
    'orderby'      => 'nicename',
    'order'        => 'ASC',
    'count_total'  => false,
    'fields'       => array('ID','user_nicename'),
);
$wp_users = get_users( $args );
?>
<div id="rank-tabs">
    <div class="tab-title"><?php _e( 'Rank Tools', 'badgeos' ); ?></div>
    <ul>
        <li>
            <a href="#rank_bulk_award">
                &nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-trophy" aria-hidden="true"></i>&nbsp;&nbsp;
                <?php _e( 'Award Ranks In Bulk', 'badgeos' ); ?>
            </a>
        </li>
        <li>
            <a href="#rank_bulk_revoke">
                &nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-star-o" aria-hidden="true"></i>&nbsp;&nbsp;
                <?php _e( 'Revoke Ranks In Bulk', 'badgeos' ); ?>
            </a>
        </li>
    </ul>
    <div id="rank_bulk_award">
        <form method="POST" class="rank-bulk-award" action="">
            <table cellspacing="0">
                <tbody>
                <tr>
                    <th scope="row"><label for="rank_types"><?php _e( 'Ranks to Award', 'badgeos' ); ?></label></th>
                    <td>
                        <select id="rank_types_to_award" data-placeholder="Select Rank to Award" name="badgeos_tools[award_rank_types][]" multiple="multiple" class="badgeos-select">
                            <?php
                            if ( is_array( $rank_types ) && ! empty( $rank_types ) ) {
                                foreach ( $rank_types as $rank_type ) {
                                    echo '<option value="' . $rank_type->ID . '">' . $rank_type->post_title . '</option>';
                                }
                            }
                            ?>
                        </select>
                        <span class="tool-hint"><?php _e( 'Choose the ranks to award', 'badgeos' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="all_users"><?php _e( 'Award to All Users', 'badgeos' ); ?></label></th>
                    <td>
                        <div class="form-switcher form-switcher-lg form-switcher-sm-phone">
                            <input type="checkbox" name="badgeos_tools[award_all_users]" id="award-ranks" data-com.bitwarden.browser.user-edited="yes">
                            <label class="switcher" for="award-ranks"></label>
                        </div>
                        <span class="tool-hint"><?php _e( 'Check this point to award ranks to all users', 'badgeos' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="users"><?php _e( 'Users to Award', 'badgeos' ); ?></label></th>
                    <td>
                        <select id="badgeos-award-users" name="badgeos_tools[award_users][]" data-placeholder="Select a user" multiple="multiple" class="badgeos-select">
                            <?php
                            $args = array(
                                'role'         => '',
                                'orderby'      => 'nicename',
                                'order'        => 'ASC',
                                'count_total'  => false,
                                'fields'       => array('ID','user_nicename'),
                            );
                            $wp_users = get_users( $args );
                            foreach( $wp_users as $user ) :
                                ?>
                                <option value="<?php echo $user->ID; ?>" <?php selected( $user->ID, 'disabled' ); ?>>
                                    <?php _e( $user->user_nicename, 'badgeos' ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="tool-hint"><?php _e( 'Choose users to award', 'badgeos' ); ?></span>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php wp_nonce_field( 'rank_bulk_award', 'rank_bulk_award' ); ?>
            <input type="hidden" name="action" value="award_bulk_ranks">
            <input type="submit" name="award_bulk_ranks" class="button button-primary" value="<?php _e( 'Award Ranks', 'badgeos' ); ?>">
        </form>
    </div>
    <div id="rank_bulk_revoke">
        <form method="POST" class="rank_bulk_revoke" action="">
            <table cellspacing="0">
                <tbody>
                <tr>
                    <th scope="row"><label for="rank_types"><?php _e( 'Ranks to Revoke', 'badgeos' ); ?></label></th>
                    <td>
                        <select id="rank_types_to_revoke" data-placeholder="Select Ranks to Revoke" name="badgeos_tools[revoke_rank_types][]" multiple="multiple" class="badgeos-select">
                            <?php
                            if( $wp_users ) {
                                $plucked = array();
                                foreach ( $wp_users as $wp_user ) {
                                    $user_ranks = badgeos_get_user_ranks( array(
                                        'user_id' => absint( $wp_user->ID )
                                    ) );
                                    if( $user_ranks ) {
                                        foreach( $user_ranks as $user_rank ) {
                                            if( in_array( $user_rank->rank_id, $plucked ) ) {
                                                continue;
                                            }

                                            $plucked[] = $user_rank->rank_id;
                                            echo '<option value="' . $user_rank->rank_id . '">' . $user_rank->rank_title . '</option>';
                                        }
                                    }
                                }
                            }
                            ?>
                        </select>
                        <span class="tool-hint"><?php _e( 'Check this point to revoke ranks to all users', 'badgeos' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="all_users"><?php _e( 'Revoke to All Users', 'badgeos' ); ?></label></th>
                    <td>
                        <div class="form-switcher form-switcher-lg">
                            <input type="checkbox" name="badgeos_tools[revoke_all_users]" id="revoke-ranks" data-com.bitwarden.browser.user-edited="yes">
                            <label class="switcher" for="revoke-ranks"></label>
                        </div>
                        <span class="tool-hint"><?php _e( 'Check this point to revoke ranks to all users', 'badgeos' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="users"><?php _e( 'Users to Revoke', 'badgeos' ); ?></label></th>
                    <td>
                        <select id="badgeos-revoke-users" name="badgeos_tools[revoke_users][]" data-placeholder="Select a user" multiple="multiple" class="badgeos-select">
                            <?php
                            $args = array(
                                'role'         => '',
                                'orderby'      => 'nicename',
                                'order'        => 'ASC',
                                'count_total'  => false,
                                'fields'       => array('ID','user_nicename'),
                            );
                            $wp_users = get_users( $args );
                            foreach( $wp_users as $user ) :
                                ?>
                                <option value="<?php echo $user->ID; ?>">
                                    <?php _e( $user->user_nicename, 'badgeos' ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="tool-hint"><?php _e( 'Choose users to revoke', 'badgeos' ); ?></span>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php wp_nonce_field( 'rank_bulk_revoke', 'rank_bulk_revoke' ); ?>
            <input type="hidden" name="action" value="revoke_bulk_ranks">
            <input type="submit" name="revoke_bulk_ranks" class="button button-primary" value="<?php _e( 'Revoke Ranks', 'badgeos' ); ?>">
        </form>
    </div>
</div>