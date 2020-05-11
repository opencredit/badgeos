<?php
/**
 * Credit Tools
 *
 * @package badgeos
 * @subpackage Tools
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://badgeos.org
 */

$credit_types = badgeos_get_point_types();
?>
<div id="credit-tabs">
    <div class="tab-title"><?php _e( 'Credit Tools', 'badgeos' ); ?></div>
    <ul>
        <li>
            <a href="#credit_bulk_award">
                &nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-trophy" aria-hidden="true"></i>&nbsp;&nbsp;
                <?php _e( 'Award Credits In Bulk', 'badgeos' ); ?>
            </a>
        </li>
        <li>
            <a href="#credit_bulk_revoke">
                &nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-star-o" aria-hidden="true"></i>&nbsp;&nbsp;
                <?php _e( 'Revoke Credits In Bulk', 'badgeos' ); ?>
            </a>
        </li>
    </ul>
    <div id="credit_bulk_award">
        <form method="POST" class="credit-bulk-award" action="">
            <table cellspacing="0">
                <tbody>
                <tr>
                    <th scope="row"><label for="credit_types"><?php _e( 'Select Credit Type', 'badgeos' ); ?></label></th>
                    <td>
                        <select id="credit_types_to_award" data-placeholder="Select Credit Type" name="badgeos_tools[award_credit_type]" class="badgeos-select">
                            <option value=""><?php _e( 'Select Credit Type' ); ?></option>
                            <?php
                            if ( is_array( $credit_types ) && ! empty( $credit_types ) ) {
                                foreach ( $credit_types as $credit_type ) {
                                    echo '<option value="' . $credit_type->ID . '">' . $credit_type->post_title . '</option>';
                                }
                            }
                            ?>
                        </select>
                        <span class="tool-hint"><?php _e( 'Choose the credit type to award', 'badgeos' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="credit_amount"><?php _e( 'Credit Amount', 'badgeos' ); ?></label></th>
                    <td>
                        <input type="number" class="credit-amount" placeholder="Credit Amount" name="badgeos_tools[credit_amount]">
                        <span class="tool-hint"><?php _e( 'Amount of credit to award', 'badgeos' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="all_users"><?php _e( 'Award to All Users', 'badgeos' ); ?></label></th>
                    <td>
                        <div class="form-switcher form-switcher-lg form-switcher-sm-phone">
                            <input type="checkbox" name="badgeos_tools[award_all_users]" id="award-credits" data-com.bitwarden.browser.user-edited="yes">
                            <label class="switcher" for="award-credits"></label>
                        </div>
                        <span class="tool-hint"><?php _e( 'Check this point to award achievements to all users', 'badgeos' ); ?></span>
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
            <?php wp_nonce_field( 'credit_bulk_award', 'credit_bulk_award' ); ?>
            <input type="hidden" name="action" value="award_credits_in_bulk">
            <input type="submit" name="award_credits_in_bulk" class="button button-primary" value="<?php _e( 'Award Credits', 'badgeos' ); ?>">
        </form>
    </div>
    <div id="credit_bulk_revoke">
        <form method="POST" class="credit_bulk_revoke" action="">
            <table cellspacing="0">
                <tbody>
                <tr>
                    <th scope="row"><label for="credit_types"><?php _e( 'Select Credit Type', 'badgeos' ); ?></label></th>
                    <td>
                        <select id="credit_types_to_revoke" data-placeholder="Select Credit Type" name="badgeos_tools[revoke_credit_type]" class="badgeos-select">
                            <option value=""><?php _e( 'Select Credit Type' ); ?></option>
                            <?php
                            if ( is_array( $credit_types ) && ! empty( $credit_types ) ) {
                                foreach ( $credit_types as $credit_type ) {
                                    echo '<option value="' . $credit_type->ID . '">' . $credit_type->post_title . '</option>';
                                }
                            }
                            ?>
                        </select>
                        <span class="tool-hint"><?php _e( 'Choose the credit type to revoke', 'badgeos' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="credit_amount"><?php _e( 'Credit Amount', 'badgeos' ); ?></label></th>
                    <td>
                        <input type="number" class="credit-amount" placeholder="Credit Amount" name="badgeos_tools[credit_amount]">
                        <span class="tool-hint"><?php _e( 'Amount of credit to revoke', 'badgeos' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="all_users"><?php _e( 'Revoke to All Users', 'badgeos' ); ?></label></th>
                    <td>
                        <div class="form-switcher form-switcher-lg">
                            <input type="checkbox" name="badgeos_tools[revoke_all_users]" id="revoke-credits" data-com.bitwarden.browser.user-edited="yes">
                            <label class="switcher" for="revoke-credits"></label>
                        </div>
                        <span class="tool-hint"><?php _e( 'Check this point to revoke achievements to all users', 'badgeos' ); ?></span>
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
                                <option value="<?php echo $user->ID; ?>" <?php selected( $user->ID, 'disabled' ); ?>>
                                    <?php _e( $user->user_nicename, 'badgeos' ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="tool-hint"><?php _e( 'Choose users to revoke', 'badgeos' ); ?></span>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php wp_nonce_field( 'credit_bulk_revoke', 'credit_bulk_revoke' ); ?>
            <input type="hidden" name="action" value="revoke_credits_in_bulk">
            <input type="submit" name="revoke_credits_in_bulk" class="button button-primary" value="<?php _e( 'Revoke Credits', 'badgeos' ); ?>">
        </form>
    </div>
</div>