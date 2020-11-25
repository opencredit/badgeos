<?php
/**
 * Email Tools
 *
 * @package BadgeOS
 * @subpackage Tools
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */
$badgeos_admin_tools        = ( $exists = badgeos_utilities::get_option( 'badgeos_admin_tools' ) ) ? $exists : array();
$email_achievement_content  = isset( $badgeos_admin_tools['email_achievement_content'] ) ? $badgeos_admin_tools['email_achievement_content'] : '';
$email_achievement_content  = stripslashes( html_entity_decode( $email_achievement_content ) );

$email_steps_achievement_content = isset( $badgeos_admin_tools['email_steps_achievement_content'] ) ? $badgeos_admin_tools['email_steps_achievement_content'] : '';
$email_steps_achievement_content = stripslashes( html_entity_decode( $email_steps_achievement_content ) );

$email_disable_ranks_email = isset( $badgeos_admin_tools['email_disable_ranks_email'] ) ? $badgeos_admin_tools['email_disable_ranks_email'] : '';
$email_disable_ranks_email = stripslashes( html_entity_decode( $email_disable_ranks_email ) );

$email_ranks_content = isset( $badgeos_admin_tools['email_ranks_content'] ) ? $badgeos_admin_tools['email_ranks_content'] : '';
$email_ranks_content = stripslashes( html_entity_decode( $email_ranks_content ) );

$email_steps_rank_content = isset( $badgeos_admin_tools['email_steps_rank_content'] ) ? $badgeos_admin_tools['email_steps_rank_content'] : '';
$email_steps_rank_content = stripslashes( html_entity_decode( $email_steps_rank_content ) );

$email_point_deducts_content = isset( $badgeos_admin_tools['email_point_deducts_content'] ) ? $badgeos_admin_tools['email_point_deducts_content'] : '';
$email_point_deducts_content = stripslashes( html_entity_decode( $email_point_deducts_content ) );

$email_point_awards_content = isset( $badgeos_admin_tools['email_point_awards_content'] ) ? $badgeos_admin_tools['email_point_awards_content'] : '';
$email_point_awards_content = stripslashes( html_entity_decode( $email_point_awards_content ) );
wp_enqueue_script( 'badgeos-jquery-mini-colorpicker-js' );
wp_enqueue_style( 'badgeos-minicolorpicker_css' );
?>
<div id="email-tabs">
    <div class="tab-title"><?php _e( 'Email Tools', 'badgeos' ); ?></div>
    <ul>
        <li>
            <a id="badgeos_tools_email_general_link" href="#badgeos_tools_email_general">
                &nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-trophy" aria-hidden="true"></i>&nbsp;&nbsp;
                <?php _e( 'General Settings', 'badgeos' ); ?>
            </a>
        </li>
        <li>
            <a id="badgeos_tools_email_achievements_link" href="#badgeos_tools_email_achievements">
                &nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-star-o" aria-hidden="true"></i>&nbsp;&nbsp;
                <?php _e( 'Achievements', 'badgeos' ); ?>
            </a>
        </li>
        <li>
            <a id="badgeos_tools_email_achievement_steps_link" href="#badgeos_tools_email_achievement_steps">
                &nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-star-o" aria-hidden="true"></i>&nbsp;&nbsp;
                <?php _e( 'Achievement Steps', 'badgeos' ); ?>
            </a>
        </li>
        <li>
            <a id="badgeos_tools_email_ranks_link" href="#badgeos_tools_email_ranks">
                &nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-star-o" aria-hidden="true"></i>&nbsp;&nbsp;
                <?php _e( 'Ranks', 'badgeos' ); ?>
            </a>
        </li>
        <li>
            <a id="badgeos_tools_email_rank_steps_link" href="#badgeos_tools_email_rank_steps">
                &nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-star-o" aria-hidden="true"></i>&nbsp;&nbsp;
                <?php _e( 'Ranks Steps', 'badgeos' ); ?>
            </a>
        </li>
        <li>
            <a id="badgeos_tools_email_point_awards_link" href="#badgeos_tools_email_point_awards">
                &nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-star-o" aria-hidden="true"></i>&nbsp;&nbsp;
                <?php _e( 'Point Award', 'badgeos' ); ?>
            </a>
        </li>
        <li>
            <a id="badgeos_tools_email_point_deducts_link" href="#badgeos_tools_email_point_deducts">
                &nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-star-o" aria-hidden="true"></i>&nbsp;&nbsp;
                <?php _e( 'Point Deduct', 'badgeos' ); ?>
            </a>
        </li>
        <?php do_action( 'badgeos_email_tools_settings_tab_header', $badgeos_admin_tools ); ?>
    </ul> 
    <div id="badgeos_tools_email_general">
        <form method="POST" class="badgeos_tools_email_general" action="" enctype="multipart/form-data">
            <table cellspacing="5">
                <tbody>
                    <tr>
                        <th scope="row" valign="top"><label for="credit_types"><?php _e( 'Email Logo', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="file" name="badgeos_tools_email_general_logo" id="badgeos_tools_email_general_logo" />
                            <span class="tool-hint"><?php _e( 'Choose an email logo.', 'badgeos' ); ?></span>
                            <img src="<?php echo $badgeos_admin_tools['badgeos_tools_email_logo_url'];?>" width="100px" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label for="badgeos_tools_email_general_from_name"><?php _e( 'From Name', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="text" name="badgeos_tools[email_general_from_name]" value="<?php echo isset( $badgeos_admin_tools['email_general_from_name'] )?$badgeos_admin_tools['email_general_from_name']:''; ?>" id="badgeos_tools_email_general_from_name">
                            <span class="tool-hint"><?php _e( 'From name of email', 'badgeos' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label><?php _e( 'From Email', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="text" name="badgeos_tools[email_general_from_email]" value="<?php echo isset( $badgeos_admin_tools['email_general_from_email'] )?$badgeos_admin_tools['email_general_from_email']:''; ?>" id="badgeos_tools_email_general_from_email">
                            <span class="tool-hint"><?php _e( 'From email', 'badgeos' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label><?php _e( 'CC', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="text" name="badgeos_tools[email_general_cc_list]" value="<?php echo isset( $badgeos_admin_tools['email_general_cc_list'] )?$badgeos_admin_tools['email_general_cc_list']:''; ?>" id="badgeos_tools_email_general_cc_list">
                            <span class="tool-hint"><?php _e( 'Comma separated list of emails', 'badgeos' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label><?php _e( 'BCC', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="text" name="badgeos_tools[email_general_bcc_list]" value="<?php echo isset( $badgeos_admin_tools['email_general_bcc_list'] )?$badgeos_admin_tools['email_general_bcc_list']:''; ?>" id="badgeos_tools_email_general_bcc_list">
                            <span class="tool-hint"><?php _e( 'Comma separated list of emails', 'badgeos' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label><?php _e( 'Footer Text', 'badgeos' ); ?></label></th>
                        <td>
                            <textarea name="badgeos_tools[email_general_footer_text]" rows="6" cols="60" id="badgeos_tools_email_general_footer_text"><?php echo isset( $badgeos_admin_tools['email_general_footer_text'] )?$badgeos_admin_tools['email_general_footer_text']:''; ?></textarea>`  
                            <span class="tool-hint"><?php _e( 'Text will be added on the footer of BadgeOS emails.', 'badgeos' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label for="badgeos_tools_email_allow_unsubscribe_email"><?php _e( 'Allow Unsubscribe', 'badgeos' ); ?></label></th>
                        <td>
                            <select id="badgeos_tools_email_allow_unsubscribe_email" name="badgeos_tools[allow_unsubscribe_email]">
                                <option value="No" selected><?php _e( 'No', 'badgeos' ); ?></option>
                                <option value="Yes" <?php echo isset( $badgeos_admin_tools['allow_unsubscribe_email'] ) && $badgeos_admin_tools['allow_unsubscribe_email']=='Yes'?'selected':''; ?>><?php _e( 'Yes', 'badgeos' ); ?></option>
                            </select>
                            <span class="tool-hint"><?php _e( 'This option will add an "unsubscribe" link in the emails footer.', 'badgeos' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label class="badgeos_tools_email_unsubscribe_page_fields" for="badgeos_tools_email_unsubscribe_page"><?php _e( 'Unsubscribe Redirect', 'badgeos' ); ?></label></th>
                        <td>
                            <div class="badgeos_tools_email_unsubscribe_page_fields">
                                <?php wp_dropdown_pages( array( 'show_option_none' => __( 'Select Unsubscribe Page', 'badgeos' ), 'selected'=>$badgeos_admin_tools['unsubscribe_email_page'], 'name'=> 'badgeos_tools[unsubscribe_email_page]', 'id'=>'unsubscribe_email_page' ) ); ?>
                                <span class="tool-hint"><?php _e( 'Users will be redirected to this selected page after unsubscription.', 'badgeos' ); ?></span>
                            </div>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row" valign="top"><label for="users"><?php _e( 'Background Color', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="text" name="badgeos_tools[email_general_background_color]" id="badgeos_tools_email_general_background_color" class="form-control badgeos_mini_color_picker_ctrl" data-control="impcolor" value="<?php echo isset( $badgeos_admin_tools['email_general_background_color'] )?$badgeos_admin_tools['email_general_background_color']:'#ffffff'; ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label for="users"><?php _e( 'Text Color', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="text" name="badgeos_tools[email_general_body_text_color]" id="badgeos_tools_email_general_body_text_color" class="form-control badgeos_mini_color_picker_ctrl" data-control="impcolor" value="<?php echo isset( $badgeos_admin_tools['email_general_body_text_color'] )?$badgeos_admin_tools['email_general_body_text_color']:'#000000'; ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label for="users"><?php _e( 'Body Background Color', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="text" name="badgeos_tools[email_general_body_background_color]" id="badgeos_tools_email_general_body_background_color" class="form-control badgeos_mini_color_picker_ctrl" data-control="impcolor" value="<?php echo isset( $badgeos_admin_tools['email_general_body_background_color'] )?$badgeos_admin_tools['email_general_body_background_color']:'#f6f6f6'; ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label for="users"><?php _e( 'Footer Background Color', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="text" name="badgeos_tools[email_general_footer_background_color]" id="badgeos_tools_email_general_footer_background_color" class="form-control badgeos_mini_color_picker_ctrl" data-control="impcolor" value="<?php echo isset( $badgeos_admin_tools['email_general_footer_background_color'] )?$badgeos_admin_tools['email_general_footer_background_color']:'#ffffff'; ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label for="users"><?php _e( 'Footer Text Color', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="text" name="badgeos_tools[email_general_footer_text_color]" id="badgeos_tools_email_general_footer_text_color" class="form-control badgeos_mini_color_picker_ctrl" data-control="impcolor" value="<?php echo isset( $badgeos_admin_tools['email_general_footer_text_color'] )?$badgeos_admin_tools['email_general_footer_text_color']:'#000000'; ?>">
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php wp_nonce_field( 'badgeos_tools_email', 'badgeos_tools_email_general' ); ?>
            <input type="hidden" name="action" value="badgeos_tools_email_general">
            <input type="hidden" name="badgeos_tools_email_tab" value="badgeos_tools_email_general">
            <input type="submit" name="badgeos_tools_email_general_save" class="button button-primary" value="<?php _e( 'Save', 'badgeos' ); ?>">
        </form>
    </div>
    <div id="badgeos_tools_email_achievements">
        <form method="POST" class="badgeos_tools_email_achievements" action="">
            <table cellspacing="0">
                <tbody>
                    <tr>
                        <td colspan="2">
                            <div class="form-switcher form-switcher-lg">
                                <input type="checkbox" class="badgeos_tools_disable_email_checkboxes" name="badgeos_tools[email_disable_earned_achievement_email]" <?php echo (isset( $badgeos_admin_tools['email_disable_earned_achievement_email'] ) && $badgeos_admin_tools['email_disable_earned_achievement_email']=="yes" ) ?'checked':''; ?> id="badgeos_tools_disable_earned_achievement_email" data-com.bitwarden.browser.user-edited="yes">
                                <?php _e( 'Disable Earned Achievement Email', 'badgeos' ); ?>
                            </div>
                            <span class="tool-hint"><?php _e( 'Select the checkbox to stop sending the emails.', 'badgeos' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label for="badgeos_tools_email_achievement_subject"><?php _e( 'Subject', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="text" class="badgeos_tools_email_achievement_field" name="badgeos_tools[email_achievement_subject]" value="<?php echo isset( $badgeos_admin_tools['email_achievement_subject'] )?$badgeos_admin_tools['email_achievement_subject']:''; ?>" size="50" id="badgeos_tools_email_achievement_subject" />
                            <p><b><?php _e( 'Shortcodes', 'badgeos' ); ?>:</b> [achievement_type], [date_earned], [achievement_title], [points], [user_email], [user_name]</p>
                            <span class="tool-hint"><?php _e( 'Email Subject', 'badgeos' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label><?php _e( 'CC', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="text" name="badgeos_tools[email_achievement_cc_list]" value="<?php echo isset( $badgeos_admin_tools['email_achievement_cc_list'] )?$badgeos_admin_tools['email_achievement_cc_list']:''; ?>" id="badgeos_tools_email_achievement_cc_list">
                            <span class="badgeos_tools_email_achievement_field tool-hint"><?php _e( 'Comma separated list of emails', 'badgeos' ); ?></span>
                        </td> 
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label><?php _e( 'BCC', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="text" name="badgeos_tools[email_achievement_bcc_list]" value="<?php echo isset( $badgeos_admin_tools['email_achievement_bcc_list'] )?$badgeos_admin_tools['email_achievement_bcc_list']:''; ?>" id="badgeos_tools_email_achievement_bcc_list">
                            <span class="badgeos_tools_email_achievement_field tool-hint"><?php _e( 'Comma separated list of emails', 'badgeos' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label for="badgeos_tools_email_achievement_content"><?php _e( 'Content', 'badgeos' ); ?></label></th>
                        <td>
                            <?php wp_editor( $email_achievement_content, 'badgeos_tools_email_achievement_content', array('media_buttons' => true, 'editor_height' => 500, 'textarea_rows' => 20, 'textarea_name' => 'badgeos_tools[email_achievement_content]' ) ); ?>
                            <span class="badgeos_tools_email_achievement_field tool-hint"><?php _e( 'Content', 'badgeos' ); ?></span>
                            <p><b><?php _e( 'Shortcodes', 'badgeos' ); ?>:</b> [achievement_type], [date_earned], [achievement_title], [achievement_link], [achievement_image], [points], [user_email], [user_name], [user_profile_link], [evidence]</p>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php wp_nonce_field( 'badgeos_tools_email', 'badgeos_tools_email_achievement' ); ?>
            <input type="hidden" name="action" value="badgeos_tools_email_achievement">
            <input type="hidden" name="badgeos_tools_email_tab" value="badgeos_tools_email_achievements" />
            <input type="submit" name="badgeos_tools_email_achievement_save" class="button button-primary" value="<?php _e( 'Save', 'badgeos' ); ?>">
        </form>
    </div>
    <div id="badgeos_tools_email_achievement_steps">
        <form method="POST" class="badgeos_tools_email_achievement_steps" action="">
            <table cellspacing="0">
                <tbody>
                    <tr>
                        <td colspan="2">
                            <div class="form-switcher form-switcher-lg">
                                <input type="checkbox" class="badgeos_tools_disable_email_checkboxes" name="badgeos_tools[email_disable_achievement_steps_email]" <?php echo (isset( $badgeos_admin_tools['email_disable_achievement_steps_email'] ) && $badgeos_admin_tools['email_disable_achievement_steps_email']=="yes" ) ?'checked':''; ?> id="badgeos_tools_email_disable_achievement_steps_email" data-com.bitwarden.browser.user-edited="yes">
                                <?php _e( 'Disable Achievement Steps Email', 'badgeos' ); ?>
                            </div>
                            <span class="tool-hint"><?php _e( 'Select the checkbox to stop sending the emails.', 'badgeos' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label for="badgeos_tools_email_steps_achievement_subject"><?php _e( 'Subject', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="text" class="badgeos_tools_email_achievement_field" name="badgeos_tools[email_steps_achievement_subject]" value="<?php echo isset( $badgeos_admin_tools['email_steps_achievement_subject'] )?$badgeos_admin_tools['email_steps_achievement_subject']:''; ?>" size="50" id="badgeos_tools_email_steps_achievement_subject" />
                            <p><b><?php _e( 'Shortcodes', 'badgeos' ); ?>:</b> [step_title], [date_earned], [user_email], [user_name]</p>
                            <span class="tool-hint"><?php _e( 'Email Subject', 'badgeos' ); ?></span>
                        </td>
                    </tr> 
                    <tr>
                        <th scope="row" valign="top"><label><?php _e( 'CC', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="text" name="badgeos_tools[email_achievement_steps_cc_list]" value="<?php echo isset( $badgeos_admin_tools['email_achievement_steps_cc_list'] )?$badgeos_admin_tools['email_achievement_steps_cc_list']:''; ?>" id="badgeos_tools_email_achievement_steps_cc_list">
                            <span class="badgeos_tools_email_achievement_field tool-hint"><?php _e( 'Comma separated list of emails', 'badgeos' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label><?php _e( 'BCC', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="text" name="badgeos_tools[email_achievement_steps_bcc_list]" value="<?php echo isset( $badgeos_admin_tools['email_achievement_steps_bcc_list'] )?$badgeos_admin_tools['email_achievement_steps_bcc_list']:''; ?>" id="badgeos_tools_email_achievement_steps_bcc_list">
                            <span class="badgeos_tools_email_achievement_field tool-hint"><?php _e( 'Comma separated list of emails', 'badgeos' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label for="badgeos_tools_email_steps_achievement_content"><?php _e( 'Content', 'badgeos' ); ?></label></th>
                        <td>
                            <?php wp_editor( $email_steps_achievement_content, 'badgeos_tools_email_steps_achievement_content', array('media_buttons' => true, 'editor_height' => 500, 'textarea_rows' => 20, 'textarea_name' => 'badgeos_tools[email_steps_achievement_content]' ) ); ?>
                            <span class="badgeos_tools_email_achievement_field tool-hint"><?php _e( 'Content', 'badgeos' ); ?></span>
                            <p><b><?php _e( 'Shortcodes', 'badgeos' ); ?>:</b> [step_title], [date_earned], [user_email], [user_name], [user_profile_link]</p>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php wp_nonce_field( 'badgeos_tools_email', 'badgeos_tools_email_achievement_steps' ); ?>
            <input type="hidden" name="action" value="badgeos_tools_email_achievement_steps">
            <input type="hidden" name="badgeos_tools_email_tab" value="badgeos_tools_email_achievement_steps" />
            <input type="submit" name="badgeos_tools_email_achievement_steps_save" class="button button-primary" value="<?php _e( 'Save', 'badgeos' ); ?>">
        </form>
    </div>
    <div id="badgeos_tools_email_ranks">
        <form method="POST" class="badgeos_tools_email_ranks" action="">
            <table cellspacing="0">
                <tbody>
                    <tr>
                        <td colspan="2">
                            <div class="form-switcher form-switcher-lg">
                                <input type="checkbox" class="badgeos_tools_disable_email_checkboxes" name="badgeos_tools[email_disable_ranks_email]" <?php echo (isset( $badgeos_admin_tools['email_disable_ranks_email'] ) && $badgeos_admin_tools['email_disable_ranks_email']=="yes" ) ?'checked':''; ?> id="badgeos_tools_email_disable_ranks_email" data-com.bitwarden.browser.user-edited="yes">
                                <?php _e( 'Disable Ranks Email', 'badgeos' ); ?>
                            </div>
                            <span class="tool-hint"><?php _e( 'Select the checkbox to stop sending the emails.', 'badgeos' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label for="badgeos_tools_email_ranks_subject"><?php _e( 'Subject', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="text" class="badgeos_tools_email_achievement_field" name="badgeos_tools[email_ranks_subject]" value="<?php echo isset( $badgeos_admin_tools['email_ranks_subject'] )?$badgeos_admin_tools['email_ranks_subject']:''; ?>" size="50" id="badgeos_tools_email_ranks_subject" />
                            <p><b><?php _e( 'Shortcodes', 'badgeos' ); ?>:</b>  [rank_type], [date_earned], [rank_title], [user_email], [user_name]</p>
                            <span class="tool-hint"><?php _e( 'Email Subject', 'badgeos' ); ?></span>
                        </td>
                    </tr> 
                    <tr>
                        <th scope="row" valign="top"><label><?php _e( 'CC', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="text" name="badgeos_tools[email_ranks_cc_list]" value="<?php echo isset( $badgeos_admin_tools['email_ranks_cc_list'] )?$badgeos_admin_tools['email_ranks_cc_list']:''; ?>" id="badgeos_tools_email_ranks_cc_list">
                            <span class="badgeos_tools_email_achievement_field tool-hint"><?php _e( 'Comma separated list of emails', 'badgeos' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label><?php _e( 'BCC', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="text" name="badgeos_tools[email_ranks_bcc_list]" value="<?php echo isset( $badgeos_admin_tools['email_ranks_bcc_list'] )?$badgeos_admin_tools['email_ranks_bcc_list']:''; ?>" id="badgeos_tools_email_ranks_bcc_list">
                            <span class="badgeos_tools_email_achievement_field tool-hint"><?php _e( 'Comma separated list of emails', 'badgeos' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label for="badgeos_tools_email_ranks_content"><?php _e( 'Content', 'badgeos' ); ?></label></th>
                        <td>
                            <?php wp_editor( $email_ranks_content, 'badgeos_tools_email_ranks_content', array('media_buttons' => true, 'editor_height' => 500, 'textarea_rows' => 20, 'textarea_name' => 'badgeos_tools[email_ranks_content]' ) ); ?>
                            <span class="badgeos_tools_email_achievement_field tool-hint"><?php _e( 'Content', 'badgeos' ); ?></span>
                            <p><b><?php _e( 'Shortcodes', 'badgeos' ); ?>:</b> [rank_type], [date_earned], [rank_title], [rank_link], [rank_image], [user_email], [user_name], [user_profile_link]</p>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php wp_nonce_field( 'badgeos_tools_email', 'badgeos_tools_email_ranks' ); ?>
            <input type="hidden" name="action" value="badgeos_tools_email_ranks">
            <input type="hidden" name="badgeos_tools_email_tab" value="badgeos_tools_email_ranks" />
            <input type="submit" name="badgeos_tools_email_ranks_save" class="button button-primary" value="<?php _e( 'Save', 'badgeos' ); ?>">
        </form>
    </div>
    <div id="badgeos_tools_email_rank_steps">
        <form method="POST" class="badgeos_tools_email_rank_steps" action="">
            <table cellspacing="0">
                <tbody>
                    <tr>
                        <td colspan="2">
                            <div class="form-switcher form-switcher-lg">
                                <input type="checkbox" class="badgeos_tools_disable_email_checkboxes" name="badgeos_tools[email_disable_rank_steps_email]" <?php echo (isset( $badgeos_admin_tools['email_disable_rank_steps_email'] ) && $badgeos_admin_tools['email_disable_rank_steps_email']=="yes" ) ?'checked':''; ?> id="badgeos_tools_email_disable_rank_steps_email" data-com.bitwarden.browser.user-edited="yes">
                                <?php _e( 'Disable Rank Steps Email', 'badgeos' ); ?>
                            </div>
                            <span class="tool-hint"><?php _e( 'Select the checkbox to stop sending the emails.', 'badgeos' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label for="badgeos_tools_email_steps_rank_subject"><?php _e( 'Subject', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="text" class="badgeos_tools_email_achievement_field" name="badgeos_tools[email_steps_rank_subject]" value="<?php echo isset( $badgeos_admin_tools['email_steps_rank_subject'] )?$badgeos_admin_tools['email_steps_rank_subject']:''; ?>" size="50" id="badgeos_tools_email_steps_rank_subject" />
                            <p><b><?php _e( 'Shortcodes', 'badgeos' ); ?>:</b> [rank_step_title], [date_earned], [user_email], [user_name]</p>
                            <span class="tool-hint"><?php _e( 'Email Subject', 'badgeos' ); ?></span>
                        </td>
                    </tr> 
                    <tr>
                        <th scope="row" valign="top"><label><?php _e( 'CC', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="text" name="badgeos_tools[email_ranks_steps_cc_list]" value="<?php echo isset( $badgeos_admin_tools['email_ranks_steps_cc_list'] )?$badgeos_admin_tools['email_ranks_steps_cc_list']:''; ?>" id="badgeos_tools_email_ranks_steps_cc_list">
                            <span class="badgeos_tools_email_achievement_field tool-hint"><?php _e( 'Comma separated list of emails', 'badgeos' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label><?php _e( 'BCC', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="text" name="badgeos_tools[email_ranks_steps_bcc_list]" value="<?php echo isset( $badgeos_admin_tools['email_ranks_steps_bcc_list'] )?$badgeos_admin_tools['email_ranks_steps_bcc_list']:''; ?>" id="badgeos_tools_email_ranks_steps_bcc_list">
                            <span class="badgeos_tools_email_achievement_field tool-hint"><?php _e( 'Comma separated list of emails', 'badgeos' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label for="badgeos_tools_email_steps_rank_content"><?php _e( 'Content', 'badgeos' ); ?></label></th>
                        <td>
                            <?php wp_editor( $email_steps_rank_content, 'badgeos_tools_email_steps_rank_content', array('media_buttons' => true, 'editor_height' => 500, 'textarea_rows' => 20, 'textarea_name' => 'badgeos_tools[email_steps_rank_content]' ) ); ?>
                            <span class="badgeos_tools_email_achievement_field tool-hint"><?php _e( 'Content', 'badgeos' ); ?></span>
                            <p><b><?php _e( 'Shortcodes', 'badgeos' ); ?>:</b> [rank_step_title], [date_earned], [user_email], [user_name], [user_profile_link]</p>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php wp_nonce_field( 'badgeos_tools_email', 'badgeos_tools_email_rank_steps' ); ?>
            <input type="hidden" name="action" value="badgeos_tools_email_rank_steps">
            <input type="hidden" name="badgeos_tools_email_tab" value="badgeos_tools_email_rank_steps" />
            <input type="submit" name="badgeos_tools_email_rank_steps_save" class="button button-primary" value="<?php _e( 'Save', 'badgeos' ); ?>">
        </form>
    </div>
    <div id="badgeos_tools_email_point_awards">
        <form method="POST" class="badgeos_tools_email_point_awards" action="">
            <table cellspacing="0">
                <tbody>
                    <tr>
                        <td colspan="2">
                            <div class="form-switcher form-switcher-lg">
                                <input type="checkbox" class="badgeos_tools_disable_email_checkboxes" name="badgeos_tools[email_disable_point_awards_email]" <?php echo (isset( $badgeos_admin_tools['email_disable_point_awards_email'] ) && $badgeos_admin_tools['email_disable_point_awards_email']=="yes" ) ?'checked':''; ?> id="badgeos_tools_email_disable_point_awards_email" data-com.bitwarden.browser.user-edited="yes">
                                <?php _e( 'Disable Point Award Email', 'badgeos' ); ?>
                            </div>
                            <span class="tool-hint"><?php _e( 'Select the checkbox to stop sending the emails.', 'badgeos' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label for="badgeos_tools_email_point_awards_subject"><?php _e( 'Subject', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="text" class="badgeos_tools_email_achievement_field" name="badgeos_tools[email_point_awards_subject]" value="<?php echo isset( $badgeos_admin_tools['email_point_awards_subject'] )?$badgeos_admin_tools['email_point_awards_subject']:''; ?>" size="50" id="badgeos_tools_email_point_awards_subject" />
                            <p><b><?php _e( 'Shortcodes', 'badgeos' ); ?>:</b> [point_title], [date_earned], [credit], [user_email], [user_name]</p>
                            <span class="tool-hint"><?php _e( 'Email Subject', 'badgeos' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label><?php _e( 'CC', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="text" name="badgeos_tools[email_point_awards_cc_list]" value="<?php echo isset( $badgeos_admin_tools['email_point_awards_cc_list'] )?$badgeos_admin_tools['email_point_awards_cc_list']:''; ?>" id="badgeos_tools_email_point_awards_cc_list">
                            <span class="badgeos_tools_email_achievement_field tool-hint"><?php _e( 'Comma separated list of emails', 'badgeos' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label><?php _e( 'BCC', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="text" name="badgeos_tools[email_point_awards_bcc_list]" value="<?php echo isset( $badgeos_admin_tools['email_point_awards_bcc_list'] )?$badgeos_admin_tools['email_point_awards_bcc_list']:''; ?>" id="badgeos_tools_email_point_awards_bcc_list">
                            <span class="badgeos_tools_email_achievement_field tool-hint"><?php _e( 'Comma separated list of emails', 'badgeos' ); ?></span>
                        </td>
                    </tr> 
                    <tr>
                        <th scope="row" valign="top"><label for="badgeos_tools_email_point_awards_content"><?php _e( 'Content', 'badgeos' ); ?></label></th>
                        <td>
                            <?php wp_editor( $email_point_awards_content, 'badgeos_tools_email_point_awards_content', array('media_buttons' => true, 'editor_height' => 500, 'textarea_rows' => 20, 'textarea_name' => 'badgeos_tools[email_point_awards_content]' ) ); ?>
                            <span class="badgeos_tools_email_achievement_field tool-hint"><?php _e( 'Content', 'badgeos' ); ?></span>
                            <p><b><?php _e( 'Shortcodes', 'badgeos' ); ?>:</b> [point_title], [date_earned], [credit], [user_email], [user_name], [user_profile_link]</p>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php wp_nonce_field( 'badgeos_tools_email', 'badgeos_tools_email_point_awards' ); ?>
            <input type="hidden" name="action" value="badgeos_tools_email_point_awards">
            <input type="hidden" name="badgeos_tools_email_tab" value="badgeos_tools_email_point_awards" />
            <input type="submit" name="badgeos_tools_email_point_awards_save" class="button button-primary" value="<?php _e( 'Save', 'badgeos' ); ?>">
        </form>
    </div>
    <div id="badgeos_tools_email_point_deducts">
        <form method="POST" class="badgeos_tools_email_point_deducts" action="">
            <table cellspacing="0">
                <tbody>
                    <tr>
                        <td colspan="2">
                            <div class="form-switcher form-switcher-lg">
                                <input type="checkbox" class="badgeos_tools_disable_email_checkboxes" name="badgeos_tools[email_disable_point_deducts_email]" <?php echo (isset( $badgeos_admin_tools['email_disable_point_deducts_email'] ) && $badgeos_admin_tools['email_disable_point_deducts_email']=="yes" ) ?'checked':''; ?> id="badgeos_tools_email_disable_point_deducts_email" data-com.bitwarden.browser.user-edited="yes">
                                <?php _e( 'Disable Point Deduct Email', 'badgeos' ); ?>
                            </div>
                            <span class="tool-hint"><?php _e( 'Select the checkbox to stop sending the emails.', 'badgeos' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label for="badgeos_tools_email_point_deducts_subject"><?php _e( 'Subject', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="text" class="badgeos_tools_email_achievement_field" name="badgeos_tools[email_point_deducts_subject]" value="<?php echo isset( $badgeos_admin_tools['email_point_deducts_subject'] )?$badgeos_admin_tools['email_point_deducts_subject']:''; ?>" size="50" id="badgeos_tools_email_point_deducts_subject" />
                            <p><b><?php _e( 'Shortcodes', 'badgeos' ); ?>:</b> [point_title], [date_earned], [credit], [user_email], [user_name]</p>
                            <span class="tool-hint"><?php _e( 'Email Subject', 'badgeos' ); ?></span>
                        </td>
                    </tr> 
                    <tr>
                        <th scope="row" valign="top"><label><?php _e( 'CC', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="text" name="badgeos_tools[email_point_deducts_cc_list]" value="<?php echo isset( $badgeos_admin_tools['email_point_deducts_cc_list'] )?$badgeos_admin_tools['email_point_deducts_cc_list']:''; ?>" id="badgeos_tools_email_point_deducts_cc_list">
                            <span class="badgeos_tools_email_achievement_field tool-hint"><?php _e( 'Comma separated list of emails', 'badgeos' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label><?php _e( 'BCC', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="text" name="badgeos_tools[email_point_deducts_bcc_list]" value="<?php echo isset( $badgeos_admin_tools['email_point_deducts_bcc_list'] )?$badgeos_admin_tools['email_point_deducts_bcc_list']:''; ?>" id="badgeos_tools_email_point_deducts_bcc_list">
                            <span class="badgeos_tools_email_achievement_field tool-hint"><?php _e( 'Comma separated list of emails', 'badgeos' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" valign="top"><label for="badgeos_tools_email_point_deducts_content"><?php _e( 'Content', 'badgeos' ); ?></label></th>
                        <td>
                            <?php wp_editor( $email_point_deducts_content, 'badgeos_tools_email_point_deducts_content', array('media_buttons' => true, 'editor_height' => 500, 'textarea_rows' => 20, 'textarea_name' => 'badgeos_tools[email_point_deducts_content]' ) ); ?>
                            <span class="badgeos_tools_email_achievement_field tool-hint"><?php _e( 'Content', 'badgeos' ); ?></span>
                            <p><b><?php _e( 'Shortcodes', 'badgeos' ); ?>:</b> [point_title], [date_earned], [credit], [user_email], [user_name], [user_profile_link]</p>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php wp_nonce_field( 'badgeos_tools_email', 'badgeos_tools_email_point_deducts' ); ?>
            <input type="hidden" name="action" value="badgeos_tools_email_point_deducts">
            <input type="hidden" name="badgeos_tools_email_tab" value="badgeos_tools_email_point_deducts" />
            <input type="submit" name="badgeos_tools_email_point_deducts_save" class="button button-primary" value="<?php _e( 'Save', 'badgeos' ); ?>">
        </form>
    </div>
    <?php do_action( 'badgeos_email_tools_settings_tab_content', $badgeos_admin_tools ); ?>
</div>