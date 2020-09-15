<?php

/**
 * BadgeOS Open Badge Options
 *
 * @package BadgeOS
 * @subpackage Open Badge
 * @author Learning Times
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class open_badge_metabox {

    /**
     * Instantiate the Opne Badge.
     */
    public function __construct() {

        // Badge Metabox
        add_action( 'add_meta_boxes', array( $this, 'open_badge_metabox_add' ) );
        add_action( 'save_post', array( $this, 'open_badge_metabox_save' ) );

        add_action ( 'wp_ajax_badgeos_validate_open_badge', array( $this, 'badgeos_validate_open_badge' ) );
        add_action ( 'wp_ajax_nopriv_badgeos_validate_open_badge', array( $this, 'badgeos_validate_open_badge' ) );

        add_action ( 'wp_ajax_bos_ob_convert_to_open_badge', array( $this, 'convert_to_open_badge' ) );
        add_action ( 'wp_ajax_nopriv_bos_ob_convert_to_open_badge', array( $this, 'convert_to_open_badge' ) );

        add_filter('badgeos_after_earned_achievement', [$this, 'badgeos_after_earned_achievement_cb'], 10, 2);
    }

    public function badgeos_after_earned_achievement_cb($return, $achievement) {
         
        $achievement_id = $achievement->ID;
        $entry_id = $achievement->entry_id;
        $rec_type = $achievement->rec_type;
        ob_start();
        $open_badge_enable_baking = badgeos_get_option_open_badge_enable_baking($achievement_id);
        ?>
        <div class="bos_ob_achievement_details">
            <div class="cob-ss-wrap">
                <?php if( badgeos_get_option_open_badge_enable_baking($achievement->ID) && $rec_type != 'open_badge' ) { ?>
                    <div class="bos_ob_convert_to_ob">
                        <button type="submit" class="bos_ob_convert_to_ob_btn" value="<?php echo $entry_id; ?>">
                            <?php echo apply_filters('bos_ob_convert_to_ob_label', __('Convert to OpenBadge', 'badgeos')); ?>
                        </button>
                    </div>
                <?php } elseif( $open_badge_enable_baking ) { ?>
                    <div class="badgeos_ob_verifcation_wrapper_div">
                        <input class="badgeos-ob-verification-buttons" id="open-badgeos-verification" href="javascript:;" data-bg="<?php echo $achievement_id;?>" data-eid="<?php echo $entry_id;?>" data-uid="<?php echo $achievement->user_id;?>" class="verify-open-badge" value="<?php echo _e( 'Verify', 'badgeos' );?>" type="button" />
                    </div>
                <?php } ?>

            </div>
        </div>
        <?php
        return $return.ob_get_clean();
    }

    /**
     * Convert to existing normal Badge to Open Badge Standard
     */
    public function convert_to_open_badge() {
        ini_set( 'display_errors', 1 );error_reporting(E_ALL);
        if( !wp_doing_ajax() ) return;

        if( !isset($_POST['entry_id']) ) return;

        $entry_id = absint($_POST['entry_id']);

        if( empty($entry_id) ) return;

        $response = array(
            'status' => 'error',
            'message' => __('An unknown error occurred while processing your request', 'badgeos')
        );

        $user_id = get_current_user_id();
        $result = badgeos_ob_get_achievement_entry($entry_id);

        if( !empty($result) ) {
            $object_id = $result['ID'];

            if ( badgeos_get_option_open_badge_enable_baking($object_id) ) {
                $GLOBALS['badgeos_open_badge']->bake_user_badge($entry_id, $user_id, $object_id);
                $response = array(
                    'status' => 'success',
                    'message' => __('Success', 'badgeos')
                );
            }
        }

        if($response['status'] == 'error') {
            wp_send_json($response, 400);
        }

        wp_send_json($response);
    }

    public function is_open_badge($entry_id, $user_id) {

        $response = false;

        if( isset($entry_id) ) {

            $entry_id = absint($entry_id);

            if( !empty($entry_id) ) {

                $result = badgeos_ob_get_achievement_entry($entry_id);

                if(!empty($result)) {

                    $achievement_id = $result['ID'];

                    if( badgeos_get_option_open_badge_enable_baking($achievement_id) ) {
                        $response = $result['rec_type'] == 'open_badge';
                    }
                }
            }
        }

        return $response;
    }

    /**
     * Validate badgeOS data
     */
    public function badgeos_validate_open_badge() {
       
        global $wpdb;

        $achievement_id = 0;
        if( ! empty( $_REQUEST['bg'] ) ) {
            $achievement_id 	= sanitize_text_field( $_REQUEST['bg'] );
        }

        $entry_id = 0;
        if( ! empty( $_REQUEST['eid'] ) ) {
            $entry_id  	        = sanitize_text_field( $_REQUEST['eid'] );
        }

        $user_id = 0;
        if( ! empty( $_REQUEST['uid'] ) ) {
            $user_id  	        = sanitize_text_field( $_REQUEST['uid'] );
        }

        $type = 'issued_on';
        if( ! empty( $_REQUEST['type'] ) ) {
            $type = sanitize_text_field( $_REQUEST['type'] );
        }
        $date_format = badgeos_utilities::get_option( 'date_format', true );
        $time_format = badgeos_utilities::get_option( 'time_format', true );
        
        $msg = array( 'type' => 'error', 'result' => 0,'message' => __( 'We are unable to serve ur request. Please, try back later.', 'badgeos' ) );
        
        switch( $type ) {


            case "issued_on":
                $recs = $wpdb->get_results( "select * from ".$wpdb->prefix."badgeos_achievements where entry_id='".$entry_id."'" );
                if( count( $recs ) > 0 ) {
                    $msg = array( 'type' => 'success', 'result' => 1, 'message' => __( 'Issued On ', 'badgeos' ).date( $date_format.' '.$time_format, strtotime($recs[0]->date_earned) ) );
                }  else {
                    $msg = array( 'type' => 'failed', 'result' => 0,'message' => __( 'No Issued Date Found.', 'badgeos' ) );
                }
                break;
            case "issued_by":
                $recs = $wpdb->get_results( "select * from ".$wpdb->prefix."badgeos_achievements where entry_id='".$entry_id."'" );
                if( count( $recs ) > 0 ) {
                    $site_title = get_bloginfo( 'name' );
                    $msg = array( 'type' => 'success', 'result' => 1, 'message' => __( 'Issued by', 'badgeos' ).' '.$site_title );
                }  else {
                    $msg = array( 'type' => 'failed', 'result' => 0,'message' => __( 'Issued by some other website.', 'badgeos' ) );
                }
                break;
            case "issued_using":
                $recs = $wpdb->get_results( "select * from ".$wpdb->prefix."badgeos_achievements where entry_id='".$entry_id."'" );
                if( count( $recs ) > 0 ) {
                    $site_title = get_bloginfo( 'name' );
                    $msg = array( 'type' => 'success', 'result' => 1, 'message' => __( 'Issued using BadgeOS', 'badgeos' ) );
                }  else {
                    $msg = array( 'type' => 'failed', 'result' => 0,'message' => __( 'Issued using some other plateform.', 'badgeos' ) );
                }
                break;
            case "issued_to":
                $recs = $wpdb->get_results( "select * from ".$wpdb->prefix."badgeos_achievements where entry_id='".$entry_id."'" );
                if( count( $recs ) > 0 ) {
                    $user_id = $recs[0]->user_id;

                    $user_info = get_userdata($user_id);
                    if( $user_info ) {
                        $display_name = $user_info->display_name;
                        $msg = array( 'type' => 'success', 'result' => 1, 'message' => __( 'Issued to ', 'badgeos' ).' '.$display_name );
                    } else {
                        $msg = array( 'type' => 'failed', 'result' => 0,'message' => __( 'Not issued to this user.', 'badgeos' ) );
                    }
                }  else {
                    $msg = array( 'type' => 'failed', 'result' => 0,'message' => __( 'Not issued to this user.', 'badgeos' ) );
                }
                break;
            case "expiry_date":
                $open_badge_expiration       = ( badgeos_utilities::get_post_meta( $achievement_id, '_open_badge_expiration', true ) ? badgeos_utilities::get_post_meta( $achievement_id, '_open_badge_expiration', true ) : '0' );
                $open_badge_expiration_type  = ( badgeos_utilities::get_post_meta( $achievement_id, '_open_badge_expiration_type', true ) ? badgeos_utilities::get_post_meta( $achievement_id, '_open_badge_expiration_type', true ) : '0' );

                if( intval( $open_badge_expiration ) > 0 ) {
                    $recs = $wpdb->get_results( "select * from ".$wpdb->prefix."badgeos_achievements where entry_id='".$entry_id."'" );
                    if( count( $recs ) > 0 ) {
                        $badge_date = strtotime( $recs[ 0 ]->date_earned );
                        if( !empty( $badge_date ) ) {

                            $badge_expiry = strtotime( '+'.$open_badge_expiration.' '.$open_badge_expiration_type, $badge_date );
                            if( $badge_expiry >= time() ) {
                                $msg = array( 'type' => 'success', 'result' => 1, 'message' => __( 'Expiry date is', 'badgeos' ).' '.date( $date_format.' '.$time_format, $badge_expiry ) );
                            } else {
                                $msg = array( 'type' => 'success', 'result' => 0, 'message' => __( 'Badge is expired on', 'badgeos' ).' '.date( $date_format.' '.$time_format, $badge_expiry ) );
                            }
                        } else {
                            $msg = array( 'type' => 'failed', 'result' => 0, 'message' => __( 'Badge is expired', 'badgeos' ) );
                        }
                    } else {
                        $msg = array( 'type' => 'notfound', 'result' => 0, 'message' => __( 'Badge is not found', 'badgeos' ) );
                    }
                } else {
                    $recs = $wpdb->get_results( "select * from ".$wpdb->prefix."badgeos_achievements where entry_id='".$entry_id."'" );
                    if( count( $recs ) > 0 ) {
                        $msg = array( 'type' => 'success', 'result' => 1, 'message' => __( 'No Expiry Date', 'badgeos' ) );
                    }  else {
                        $msg = array( 'type' => 'failed', 'result' => 0, 'message' => __( 'Badge is not found.', 'badgeos' ) );
                    }
                 }
        }
        $msg['verified_label'] = __( 'Verified', 'badgeos' );
        $msg['notverified_label'] = __( 'Not Verified', 'badgeos' );
        wp_send_json( $msg );
    }

    /**
     * Add a Open Badge Settings metabox on the badge CPT
     *
     * @return void
     */
    public function open_badge_metabox_add() {

        foreach ( badgeos_get_achievement_types_slugs() as $achievement_type ) {

            add_meta_box( 'badgeos_social_share_meta_box', __( 'Open Badge Options', 'badgeos' ), array( $this, 'open_badge_metabox_show' ), $achievement_type, 'advanced', 'default' );

        }
    }

    /**
     * Output a Open Badge Settings metabox on the badge CPT
     *
     * @return void
     */
    public function open_badge_metabox_show() {

        global $post;

        $open_badge_enable_baking       = badgeos_get_option_open_badge_enable_baking($post->ID);
        $open_badge_criteria            = ( badgeos_utilities::get_post_meta( $post->ID, '_open_badge_criteria', true ) ? badgeos_utilities::get_post_meta( $post->ID, '_open_badge_criteria', true ): '' );
        $open_badge_include_evidence    = ( badgeos_utilities::get_post_meta( $post->ID, '_open_badge_include_evidence', true ) ? badgeos_utilities::get_post_meta( $post->ID, '_open_badge_include_evidence', true ) : 'false' );
        $open_badge_expiration          = ( badgeos_utilities::get_post_meta( $post->ID, '_open_badge_expiration', true ) ? badgeos_utilities::get_post_meta( $post->ID, '_open_badge_expiration', true ) : '0' );
        $open_badge_expiration_type     = ( badgeos_utilities::get_post_meta( $post->ID, '_open_badge_expiration_type', true ) ? badgeos_utilities::get_post_meta( $post->ID, '_open_badge_expiration_type', true ) : '0' );
        ?>
            <input type="hidden" name="open_badge_nonce" value="<?php echo wp_create_nonce( 'open_badge' ); ?>" />
            <table class="form-table">
                <tr valign="top">
                    <td colspan="2"><?php _e( "This setting makes the earned badge for this achievement sharable on social networks, such as Facebook, Twitter, LinkedIn, Mozilla Backpack, or the badge earner's own blog or site.", 'badgeos' ); ?></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="open_badge_enable_baking"><?php _e( 'Enable Badge Baking', 'badgeos' ); ?></label></th>
                    <td>
                        <select id="open_badge_enable_baking" name="open_badge_enable_baking">
                            <option value="1" <?php selected( $open_badge_enable_baking, true ); ?>><?php _e( 'Yes', 'badgeos' ) ?></option>
                            <option value="0" <?php selected( $open_badge_enable_baking, false ); ?>><?php _e( 'No', 'badgeos' ) ?></option>
                        </select>
                    </td>
                </tr>
            </table>
            <div id="open-badge-setting-section">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><label for="open_badge_criteria"><?php _e( 'Criteria', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="text" id="open_badge_criteria" readonly="readonly" name="open_badge_criteria" value="<?php echo $open_badge_criteria; ?>" class="widefat" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="open_badge_include_evidence"><?php _e( 'Include Evidence', 'badgeos' ); ?></label></th>
                        <td>
                            <select id="open_badge_include_evidence" name="open_badge_include_evidence">
                                <option value="1" <?php selected( $open_badge_include_evidence, 'true' ); ?>><?php _e( 'Yes', 'badgeos' ) ?></option>
                                <option value="0" <?php selected( $open_badge_include_evidence, 'false' ); ?>><?php _e( 'No', 'badgeos' ) ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="open_badge_expiration"><?php _e( 'Expiration', 'badgeos' ); ?></label></th>
                        <td>
                            <input type="number" id="open_badge_expiration" name="open_badge_expiration" value="<?php echo $open_badge_expiration; ?>" class="date_picker_class" />
                            <select id="open_badge_expiration_type" name="open_badge_expiration_type">
                                <option value="Day" <?php selected( $open_badge_expiration_type, 'Day' ); ?>><?php _e( 'Day(s)', 'badgeos' ) ?></option>
                                <option value="Month" <?php selected( $open_badge_expiration_type, 'Month' ); ?>><?php _e( 'Month(s)', 'badgeos' ) ?></option>
                                <option value="Year" <?php selected( $open_badge_expiration_type, 'Year' ); ?>><?php _e( 'Year(s)', 'badgeos' ) ?></option>
                            </select>
                            <p><?php _e( 'Enter zero or leave empty for no expiry limit.', 'badgeos' ); ?></p>
                        </td>
                    </tr>
                </table>
                <p><strong><?php _e( 'Note', 'badgeos' ); ?></strong>: <?php _e( "If Badge Baking is enabled, upload only PNG image on featured image option.", 'badgeos' ); ?></p>
            </div>
        <?php
    }

    /**
     * Save our open Badge Settings metabox
     *
     * @param  int     $post_id The ID of the given post
     *
     * @return int     Return the post ID of the post we're running on
     */
    public function open_badge_metabox_save( $post_id = 0 ) {

        // Verify nonce
        if ( ! isset( $_POST['open_badge_nonce'] ) || ! wp_verify_nonce( $_POST['open_badge_nonce'], 'open_badge' ) )
            return $post_id;

        // Make sure we're not doing an autosave
        if ( defined('DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            return $post_id;

        // Make sure this isn't a post revision
        if ( wp_is_post_revision( $post_id ) )
            return $post_id;

        // Check user permissions
        if ( !current_user_can( 'edit_post', $post_id ) )
            return $post_id;

        // Sanitize our fields
        $fields = $this->open_badge_metabox_sanitize_fields();

        // Save our meta
        $meta = $this->open_badge_metabox_save_meta( $post_id, $fields );

        return $post_id;
    }

    /**
     * Save the meta fields from our metabox
     *
     * @param  int  $post_id   Post ID
     * @param  array  $fields  An array of fields in the metabox
     *
     * @return bool            Return true
     */
    private function open_badge_metabox_save_meta( $post_id = 0, $fields = array() ) {

        badgeos_utilities::update_post_meta( $post_id, '_open_badge_enable_baking', $fields['open_badge_enable_baking'] );
        badgeos_utilities::update_post_meta( $post_id, '_open_badge_criteria', get_permalink( $post_id ) ); //$fields['open_badge_criteria']
        badgeos_utilities::update_post_meta( $post_id, '_open_badge_include_evidence', $fields['open_badge_include_evidence'] );
        badgeos_utilities::update_post_meta( $post_id, '_open_badge_expiration', $fields['open_badge_expiration'] );
        badgeos_utilities::update_post_meta( $post_id, '_open_badge_expiration_type', $fields['open_badge_expiration_type'] );

        return true;
    }

    /**
     * Sanitize our metabox fields
     *
     * @return array  An array of sanitized fields from our metabox
     */
    private function open_badge_metabox_sanitize_fields() {

        $fields = array();

        // Sanitize our input fields
        $fields['open_badge_enable_baking']         = ( $_POST['open_badge_enable_baking'] ? 'true' : 'false' );
        $fields['open_badge_criteria']              = sanitize_text_field( $_POST['open_badge_criteria'] );
        $fields['open_badge_include_evidence']      = ( $_POST['open_badge_include_evidence'] ? 'true' : 'false' );
        $fields['open_badge_expiration']            = sanitize_text_field( $_POST['open_badge_expiration'] );
        $fields['open_badge_expiration_type']       = sanitize_text_field( $_POST['open_badge_expiration_type'] );

        return $fields;
    }
}

new open_badge_metabox();