<?php
/**
 * BadgeOS Emails
 *
 * @package BadgeOS
 * @subpackage Admin
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 */

 function badgeos_bcc_cc_emails( $badgeos_admin_tools, $field_id, $type ) {
    
    $email_list = '';
    if( isset($badgeos_admin_tools) &&  is_array($badgeos_admin_tools) && count( $badgeos_admin_tools ) > 0 ) {
        if( array_key_exists( $field_id, $badgeos_admin_tools ) &&  !empty( $badgeos_admin_tools[ $field_id ] ) ) {
            $email_list = $badgeos_admin_tools[ $field_id ];
        } elseif( $type == 'cc' ) {
            $email_list = isset( $badgeos_admin_tools[ 'email_general_cc_list' ] ) ? $badgeos_admin_tools[ 'email_general_cc_list' ] : '';
        } elseif( $type == 'bcc' ) {
            $email_list = isset( $badgeos_admin_tools[ 'email_general_bcc_list' ] ) ? $badgeos_admin_tools[ 'email_general_bcc_list' ] : '';
        }
    
        if( ! empty( $email_list) ) {
            $email_list = explode( ',', $email_list ); 
        }
    }
    

    return $email_list;
 }

/**
 * Sends email when achievement is awarded...
 * 
 * @param $user_id
 * @param $achievement_id
 * @param $this_trigger
 * @param $site_id
 * @param $args
 * @param $entry_id
 * 
 * @return none
 */ 
function badgeos_send_achievements_email( $user_id, $achievement_id, $this_trigger, $site_id, $args, $entry_id ) {
    
    global $wpdb;
    
    if( badgeos_can_notify_user( $user_id ) ) {
        $badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array(); 
        $achievement_post_type = badgeos_utilities::get_post_type( $achievement_id );
        $achievement_type = $badgeos_settings['achievement_main_post_type'];
        $post_obj = get_page_by_path( $achievement_post_type, OBJECT, $achievement_type );
        
        $parent_post_type = '';
        if( $post_obj ) {
            $parent_post_type = $post_obj->post_type;
        }
        if ( trim( $achievement_type ) == trim( $parent_post_type ) ) {
            
            $badgeos_admin_tools = ( $exists = badgeos_utilities::get_option( 'badgeos_admin_tools' ) ) ? $exists : array();
            
            $email_cc_list = badgeos_bcc_cc_emails( $badgeos_admin_tools, 'email_achievement_cc_list', 'cc' );
            $email_bcc_list = badgeos_bcc_cc_emails( $badgeos_admin_tools, 'email_achievement_bcc_list', 'bcc' );

            if( ! isset( $badgeos_admin_tools['email_disable_earned_achievement_email'] ) || $badgeos_admin_tools['email_disable_earned_achievement_email'] == 'no' ) {
                
                $results = $wpdb->get_results( "select * from ".$wpdb->prefix."badgeos_achievements where entry_id='".$entry_id."'", 'ARRAY_A' );
                if( count( $results ) > 0 ) {
                    $record = $results[ 0 ];
                    $achievement_type   = $record[ 'post_type' ];
                    $type_title  = $post_obj->post_title;
                    $step_type = trim( $badgeos_settings['achievement_step_post_type'] );
                    $issue_date = $record[ 'date_earned' ];
                    $rec_type = $record[ 'rec_type' ];
                    $rec_date_earned = $record['date_earned'];                    
                    $date_format = badgeos_utilities::get_option( 'date_format', true );
                    $time_format = badgeos_utilities::get_option( 'time_format', true );
                    if( get_post_meta( $achievement_id, '_open_badge_enable_baking', true ) ) {
                        $evidence_page_id           = get_option( 'badgeos_evidence_url' );
                        $badgeos_evidence_url       = get_permalink( $evidence_page_id );
                        $badgeos_evidence_url       = add_query_arg( 'bg',  $record[ 'ID' ],        $badgeos_evidence_url );
                        $badgeos_evidence_url       = add_query_arg( 'eid', $record[ 'entry_id' ],  $badgeos_evidence_url );
                        $badgeos_evidence_url       = add_query_arg( 'uid', $record[ 'user_id' ],   $badgeos_evidence_url );    
                    }
                       
                    if( ! empty( $achievement_type ) && trim( $achievement_type ) != $step_type ) {
                        $email_subject = $badgeos_admin_tools['email_achievement_subject'];
                        if( empty( $email_subject ) ) {
                            $email_subject      = __( 'Congratulation for earning an achievement', 'badgeos' );
                        }
                        
                        $email_content  = $badgeos_admin_tools['email_achievement_content'];
                        
                        $from_title = get_bloginfo( 'name' );
                        $from_email = get_bloginfo( 'admin_email' );
                        
                        if( !empty( $badgeos_admin_tools['email_general_from_name'] ) ) {
                            $from_title = $badgeos_admin_tools['email_general_from_name'];
                        }
    
                        if( !empty( $badgeos_admin_tools['email_general_from_email'] ) ) {
                            $from_title = $badgeos_admin_tools['email_general_from_email'];
                        }
    
                        $achievement_title  = $record[ 'achievement_title' ];
                        $points             = $record[ 'points' ];
                        $achievement_image = badgeos_get_achievement_post_thumbnail( $achievement_id, 'full' );
                    
                        $user_to_title = '';
                        $user_email = '';
                        $to_user_id = $record[ 'user_id'];
                        $user_to = get_user_by( 'ID', $record[ 'user_id'] );
                        if( $user_to ) {
                            $user_to_title = $user_to->display_name;
                            $user_email = $user_to->user_email;
                        }
            
                        $headers[] = 'From: '.$from_title.' <'.$from_email.'>';
                        $headers[] = 'Content-Type: text/html; charset=UTF-8';
                        if( is_array( $email_cc_list ) && count( $email_cc_list ) > 0 ) {
                            foreach( $email_cc_list as $cc_id ) {
                                if( !empty( $cc_id ) ) {
                                    $headers[] = 'Cc: '.$cc_id;
                                }
                            }
                        }
                        
                        if( is_array( $email_bcc_list ) &&  count( $email_bcc_list ) > 0 ) {
                            foreach( $email_bcc_list as $bcc_id ) {
                                if( !empty( $bcc_id ) ) {
                                    $headers[] = 'Bcc: '.$bcc_id;
                                }
                            }
                        }
                    
                        $email_subject = str_replace('[achievement_type]', $type_title, $email_subject ); 
                        $email_subject = str_replace('[achievement_title]', $achievement_title, $email_subject ); 
                        $email_subject = str_replace('[points]', $points, $email_subject );
                        $email_subject = str_replace('[user_email]', $user_email, $email_subject );
                        $email_subject = str_replace('[user_name]', $user_to_title, $email_subject );
    
                        ob_start();
                        
                        $email_content  = stripslashes( html_entity_decode( $email_content ) );
                        $email_content = str_replace("\'","'", $email_content);
                        $email_content = str_replace('\"','"', $email_content);
            
                        $email_content = str_replace('[achievement_type]', $type_title, $email_content ); 
                        $email_content = str_replace('[achievement_title]', $achievement_title, $email_content );
                        $email_content = str_replace('[date_earned]', date( $date_format.' '.$time_format, strtotime($record['date_earned']) ), $email_content );  
                        $email_content = str_replace('[achievement_link]', get_permalink($achievement_id), $email_content ); 
                        $email_content = str_replace('[points]', $points, $email_content ); 
                        $email_content = str_replace('[user_email]', $user_email, $email_content ); 
                        $email_content = str_replace('[user_name]', $user_to_title, $email_content ); 
                        $email_content = str_replace('[achievement_image]', $achievement_image, $email_content ); 
                        $email_content = str_replace('[user_profile_link]', get_edit_profile_url( $to_user_id ), $email_content ); 
                        $email_content = str_replace('[evidence]', badgeos_include_evidence_in_email( $achievement_id, $issue_date, $rec_type, $badgeos_evidence_url, $rec_date_earned ), $email_content);
                        include( 'email_headers/header.php' );
                        ?>
                            <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;">
                                <tr>
                                    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">
                                        <?php echo $email_content; ?>
                                    </td>
                                </tr>
                            </table>
                        <?php
                        include( 'email_headers/footer.php' );
            
                        $message = ob_get_contents();
                        ob_end_clean();
                        if( ! empty( $user_email ) ) {
                            wp_mail( $user_email, strip_tags( $email_subject ), $message, $headers );
                            add_post_meta( 11,'test', 1 );
                        }
                    }
                }
            }
        }
    }
}
add_action( 'badgeos_award_achievement', 'badgeos_send_achievements_email', 10, 6 );

/**
 * return evidence for earned achievement email notifications...
 * 
 * @return none
 */
function badgeos_include_evidence_in_email( $achievement_id, $issue_date, $rec_type, $url, $rec_date_earned ) {
    $badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array(); 
    $content = '';
    if ( get_post_meta( $achievement_id, '_open_badge_enable_baking', true ) == true && $rec_type == 'open_badge' ) { 
        $expiration = ( badgeos_utilities::get_post_meta( $achievement_id, '_open_badge_expiration', true ) ? badgeos_utilities::get_post_meta( $achievement_id, '_open_badge_expiration', true ) : '0' );
    
        $expiration_type = ( badgeos_utilities::get_post_meta( $achievement_id, '_open_badge_expiration_type', true ) ? badgeos_utilities::get_post_meta( $achievement_id, '_open_badge_expiration_type', true ) : 'Day' );

        $content .= '<p style="font:normal 12px/120% Arial,sans-serif;color:#737373;margin:20px 0 0 0;letter-spacing:0.1em">Issuer:'. get_bloginfo( 'name' ).'</p>';
        $content .= '<p style="font:normal 12px/120% Arial,sans-serif;color:#737373;margin:10px 0 0 0;letter-spacing:0.1em">Issue Date:'.date( 'Y-m-d', strtotime( $issue_date ) ).'</p>';    
        
        if( intval( $expiration ) > 0 ) {
            $expiry_date = strtotime( '+'.$expiration.' '.$expiration_type, strtotime( $rec_date_earned ) );
            $content .= '<p style="font:normal 12px/120% Arial,sans-serif;color:#737373;margin:10px 0 0 0;letter-spacing:0.1em">Expiry Date:'.date( 'Y-m-d', $expiry_date ).'</p>';
        }
        
        $content .= '<p style="font:normal 12px/120% Arial,sans-serif;color:#737373;margin:10px 0 0 0;letter-spacing:0.1em"><a href="'. $url.'">View Evidence</a></p>';
      
        return $content;
    }
}

/**
 * Unsubscribe email notifications...
 * 
 * @return none
 */
function badgeos_unsubscribe_emails_callback() {
    
    // Process revoking achievement from a user
    if ( isset( $_GET['action'] ) && 'badgeos_unsubscribe_email' == trim( $_GET['action'] ) ) {
        $user_id = sanitize_text_field( $_GET['user_id'] );
        if ( !current_user_can( 'edit_user', $user_id ) ) {
            return false;
        }
        
        badgeos_utilities::update_user_meta( sanitize_text_field( $_GET['user_id'] ), '_badgeos_can_notify_user', false );

        $badgeos_admin_tools        = ( $exists = badgeos_utilities::get_option( 'badgeos_admin_tools' ) ) ? $exists : array();
        $unsubscribe_email_page     = !empty( $badgeos_admin_tools['unsubscribe_email_page'] )? get_permalink( $badgeos_admin_tools['unsubscribe_email_page']) : site_url();
        
        //wp_redirect( $unsubscribe_email_page );
    }
}
add_action( 'init', 'badgeos_unsubscribe_emails_callback' );

/**
 * Sends email when achievement step is awarded...
 * 
 * @param $user_id
 * @param $achievement_id
 * @param $this_trigger
 * @param $site_id
 * @param $args
 * @param $entry_id
 * 
 * @return none
 */ 
function badgeos_send_achievements_step_email( $user_id, $achievement_id, $this_trigger, $site_id, $args, $entry_id ) {
    
    global $wpdb;
    if( badgeos_can_notify_user( $user_id ) ) {
        $achievement_post_type = badgeos_utilities::get_post_type( $achievement_id );
        $badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array(); 
        if ( trim( $badgeos_settings['achievement_step_post_type'] ) == $achievement_post_type ) {
            $badgeos_admin_tools = ( $exists = badgeos_utilities::get_option( 'badgeos_admin_tools' ) ) ? $exists : array();

            $email_cc_list = badgeos_bcc_cc_emails( $badgeos_admin_tools, 'email_achievement_steps_cc_list', 'cc' );
            $email_bcc_list = badgeos_bcc_cc_emails( $badgeos_admin_tools, 'email_achievement_steps_bcc_list', 'bcc' );

            if( ! isset( $badgeos_admin_tools['email_disable_achievement_steps_email'] ) || $badgeos_admin_tools['email_disable_achievement_steps_email'] == 'no' ) {
                $results = $wpdb->get_results( "select * from ".$wpdb->prefix."badgeos_achievements where entry_id='".$entry_id."'", 'ARRAY_A' );
                if( count( $results ) > 0 ) {
                    $record = $results[ 0 ];
                    $achievement_type = $record[ 'post_type' ];
                    
                    $step_type = trim( $badgeos_settings['achievement_step_post_type'] );
                    if( ! empty( $achievement_type ) && trim( $achievement_type ) == $step_type ) {
            
                        $email_subject = $badgeos_admin_tools['email_steps_achievement_subject'];
                        if( empty( $email_subject ) ) {
                            $email_subject      = __( 'Congratulation for earning an achievement', 'badgeos' );
                        }
                        
                        $email_content  = $badgeos_admin_tools['email_steps_achievement_content'];
                        
                        $from_title = get_bloginfo( 'name' );
                        $from_email = get_bloginfo( 'admin_email' );
                        
                        if( !empty( $badgeos_admin_tools['email_general_from_name'] ) ) {
                            $from_title = $badgeos_admin_tools['email_general_from_name'];
                        }

                        if( !empty( $badgeos_admin_tools['email_general_from_email'] ) ) {
                            $from_title = $badgeos_admin_tools['email_general_from_email'];
                        }

                        $step_title = get_the_title( $achievement_id );
                        if( empty( $step_title ) )
                            $step_title     = $record[ 'achievement_title' ];
                        
                        $user_to_title = '';
                        $user_email = '';
                        $to_user_id = $record[ 'user_id'];
                        $user_to = get_user_by( 'ID', $record[ 'user_id'] );
                        if( $user_to ) {
                            $user_to_title = $user_to->display_name;
                            $user_email = $user_to->user_email;
                        }
            
                        $headers[] = 'From: '.$from_title.' <'.$from_email.'>';
                        $headers[] = 'Content-Type: text/html; charset=UTF-8';

                        if( is_array( $email_cc_list ) && count( $email_cc_list ) > 0 ) {
                            foreach( $email_cc_list as $cc_id ) {
                                if( !empty( $cc_id ) ) {
                                    $headers[] = 'Cc: '.$cc_id;
                                }
                            }
                        }
                        
                        if( is_array( $email_bcc_list ) &&  count( $email_bcc_list ) > 0 ) {
                            foreach( $email_bcc_list as $bcc_id ) {
                                if( !empty( $bcc_id ) ) {
                                    $headers[] = 'Bcc: '.$bcc_id;
                                }
                            }
                        }

                        $date_format = badgeos_utilities::get_option( 'date_format', true );
                        $time_format = badgeos_utilities::get_option( 'time_format', true );

                        $email_subject = str_replace('[step_title]', $step_title, $email_subject ); 
                        $email_subject = str_replace('[date_earned]', date( $date_format.' '.$time_format, strtotime($record['date_earned']) ), $email_subject );
                        $email_subject = str_replace('[user_email]', $user_email, $email_subject );
                        $email_subject = str_replace('[user_name]', $user_to_title, $email_subject );
                        ob_start();
                        
                        $email_content  = stripslashes( html_entity_decode( $email_content ) );
                        $email_content = str_replace("\'","'", $email_content);
                        $email_content = str_replace('\"','"', $email_content);
            

                        $email_content = str_replace('[step_title]', $step_title, $email_content ); 
                        $email_content = str_replace('[date_earned]', date( $date_format.' '.$time_format, strtotime($record['date_earned']) ), $email_content ); 
                        $email_content = str_replace('[user_email]', $user_email, $email_content ); 
                        $email_content = str_replace('[user_name]', $user_to_title, $email_content ); 
                        $email_content = str_replace('[user_profile_link]', get_edit_profile_url( $to_user_id ), $email_content ); 

                        include( 'email_headers/header.php' );
                        ?>
                            <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;">
                                <tr>
                                    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">
                                        <?php echo $email_content; ?>
                                    </td>
                                </tr>
                            </table>
                        <?php
                        include( 'email_headers/footer.php' );
            
                        $message = ob_get_contents();
                        ob_end_clean();
                        if( ! empty( $user_email ) ) {
                            wp_mail( $user_email, strip_tags( $email_subject ), $message, $headers );
                        }
                    }
                }
            }
        }
    }
}
add_action( 'badgeos_award_achievement', 'badgeos_send_achievements_step_email', 10, 6 );


/**
 * Sends email when rank step is awarded...
 * 
 * @param $user_id
 * @param $achievement_id
 * @param $this_trigger
 * @param $site_id
 * @param $args
 * @param $entry_id
 * 
 * @return none
 */ 
function badgeos_send_rank_step_email( $user_id, $rank_id, $rank_type, $credit_id, $credit_amount, $admin_id, $this_trigger, $rank_entry_id=0 ) {

    global $wpdb;
    if( badgeos_can_notify_user( $user_id ) ) {
        $achievement_post_type = badgeos_utilities::get_post_type( $rank_id );
        $badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array(); 
        if ( trim( $badgeos_settings['ranks_step_post_type'] ) == $achievement_post_type ) {
            $badgeos_admin_tools = ( $exists = badgeos_utilities::get_option( 'badgeos_admin_tools' ) ) ? $exists : array();
                        
            $email_cc_list = badgeos_bcc_cc_emails( $badgeos_admin_tools, 'email_ranks_steps_cc_list', 'cc' );
            $email_bcc_list = badgeos_bcc_cc_emails( $badgeos_admin_tools, 'email_ranks_steps_bcc_list', 'bcc' );

            if( ! isset( $badgeos_admin_tools['email_disable_rank_steps_email'] ) || $badgeos_admin_tools['email_disable_rank_steps_email'] == 'no' ) {
                $results = $wpdb->get_results( "select * from ".$wpdb->prefix."badgeos_ranks where id='".$rank_entry_id."'", 'ARRAY_A' );
                if( count( $results ) > 0 ) {
                    
                    $record = $results[ 0 ];
                    $rank_type = $record[ 'rank_type' ];
                    
                    $step_type = trim( $badgeos_settings['ranks_step_post_type'] );
                    if( ! empty( $rank_type ) && trim( $rank_type ) == $step_type ) {
            
                        $email_subject = $badgeos_admin_tools['email_steps_rank_subject'];
                        if( empty( $email_subject ) ) {
                            $email_subject      = __( 'Congratulation for earning an achievement', 'badgeos' );
                        }
                        
                        $email_content  = $badgeos_admin_tools['email_steps_rank_content'];
                        
                        $from_title = get_bloginfo( 'name' );
                        $from_email = get_bloginfo( 'admin_email' );
                        
                        if( !empty( $badgeos_admin_tools['email_general_from_name'] ) ) {
                            $from_title = $badgeos_admin_tools['email_general_from_name'];
                        }

                        if( !empty( $badgeos_admin_tools['email_general_from_email'] ) ) {
                            $from_title = $badgeos_admin_tools['email_general_from_email'];
                        }

                        $rank_title = get_the_title( $rank_id );
                        if( empty( $rank_title ) )
                            $rank_title     = $record[ 'rank_title' ];
                        
                        $user_to_title = '';
                        $user_email = '';
                        $to_user_id = $record[ 'user_id'];
                        $user_to = get_user_by( 'ID', $record[ 'user_id'] );
                        if( $user_to ) {
                            $user_to_title = $user_to->display_name;
                            $user_email = $user_to->user_email;
                        }
            
                        $headers[] = 'From: '.$from_title.' <'.$from_email.'>';
                        $headers[] = 'Content-Type: text/html; charset=UTF-8';

                        if( is_array( $email_cc_list ) && count( $email_cc_list ) > 0 ) {
                            foreach( $email_cc_list as $cc_id ) {
                                if( !empty( $cc_id ) ) {
                                    $headers[] = 'Cc: '.$cc_id;
                                }
                            }
                        }
                        
                        if( is_array( $email_bcc_list ) &&  count( $email_bcc_list ) > 0 ) {
                            foreach( $email_bcc_list as $bcc_id ) {
                                if( !empty( $bcc_id ) ) {
                                    $headers[] = 'Bcc: '.$bcc_id;
                                }
                            }
                        }

                        $date_format = badgeos_utilities::get_option( 'date_format', true );
                        $time_format = badgeos_utilities::get_option( 'time_format', true );

                        $email_subject = str_replace('[rank_step_title]', $rank_title, $email_subject ); 
                        $email_subject = str_replace('[date_earned]', date( $date_format.' '.$time_format, strtotime($record['dateadded']) ), $email_subject );
                        $email_subject = str_replace('[user_email]', $user_email, $email_subject );
                        $email_subject = str_replace('[user_name]', $user_to_title, $email_subject );
                        ob_start();
                        
                        $email_content  = stripslashes( html_entity_decode( $email_content ) );
                        $email_content = str_replace("\'","'", $email_content);
                        $email_content = str_replace('\"','"', $email_content);
            
                        $email_content = str_replace('[rank_step_title]', $rank_title, $email_content ); 
                        $email_content = str_replace('[date_earned]', date( $date_format.' '.$time_format, strtotime($record['dateadded']) ), $email_content ); 
                        $email_content = str_replace('[user_email]', $user_email, $email_content ); 
                        $email_content = str_replace('[user_name]', $user_to_title, $email_content ); 
                        $email_content = str_replace('[user_profile_link]', get_edit_profile_url( $to_user_id ), $email_content ); 

                        include( 'email_headers/header.php' );
                        ?>
                            <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;">
                                <tr>
                                    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">
                                        <?php echo $email_content; ?>
                                    </td>
                                </tr>
                            </table>
                        <?php
                        include( 'email_headers/footer.php' );
            
                        $message = ob_get_contents();
                        ob_end_clean();
                        
                        if( ! empty( $user_email ) ) {
                            wp_mail( $user_email, strip_tags( $email_subject ), $message, $headers );
                        }
                    }
                }
            }
        }
    }
}
add_action( 'badgeos_after_award_rank', 'badgeos_send_rank_step_email', 10, 8 );

/**
 * Sends email when rank is awarded...
 * 
 * @param $user_id
 * @param $achievement_id
 * @param $this_trigger
 * @param $site_id
 * @param $args
 * @param $entry_id
 * 
 * @return none
 */ 
function badgeos_send_rank_email( $user_id, $rank_id, $rank_type, $credit_id, $credit_amount, $admin_id, $this_trigger, $rank_entry_id=0 ) {
    
    global $wpdb;
    if( badgeos_can_notify_user( $user_id ) ) {
        $badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array(); 
        $rank_main_type = $badgeos_settings['ranks_main_post_type'];
        $post_main_obj = get_page_by_path( $rank_type, OBJECT, $rank_main_type );
        
        if( $post_main_obj ) {
            $rank_post_type = $post_main_obj->post_type;
            if ( trim( $rank_main_type ) == trim( $rank_post_type ) ) {
                $badgeos_admin_tools = ( $exists = badgeos_utilities::get_option( 'badgeos_admin_tools' ) ) ? $exists : array();

                $email_cc_list = badgeos_bcc_cc_emails( $badgeos_admin_tools, 'email_ranks_cc_list', 'cc' );
                $email_bcc_list = badgeos_bcc_cc_emails( $badgeos_admin_tools, 'email_ranks_bcc_list', 'bcc' );

                if( ! isset( $badgeos_admin_tools['email_disable_ranks_email'] ) || $badgeos_admin_tools['email_disable_ranks_email'] == 'no' ) {
        
                    $results = $wpdb->get_results( "select * from ".$wpdb->prefix."badgeos_ranks where id='".$rank_entry_id."'", 'ARRAY_A' );
                    if( count( $results ) > 0 ) {
                        
                        $record = $results[ 0 ];
                        $rank_type = $record[ 'rank_type' ];
                        
                        $email_subject = $badgeos_admin_tools['email_ranks_subject'];
                        if( empty( $email_subject ) ) {
                            $email_subject      = __( 'Congratulation for earning a rank', 'badgeos' );
                        }
                        
                        $email_content  = $badgeos_admin_tools['email_ranks_content'];
                        
                        $from_title = get_bloginfo( 'name' );
                        $from_email = get_bloginfo( 'admin_email' );
                        
                        if( !empty( $badgeos_admin_tools['email_general_from_name'] ) ) {
                            $from_title = $badgeos_admin_tools['email_general_from_name'];
                        }

                        if( !empty( $badgeos_admin_tools['email_general_from_email'] ) ) {
                            $from_title = $badgeos_admin_tools['email_general_from_email'];
                        }

                        $rank_title = get_the_title( $rank_id );
                        if( empty( $rank_title ) )
                            $rank_title     = $record[ 'rank_title' ];
                        
                        $user_to_title = '';
                        $user_email = '';
                        $to_user_id = $record[ 'user_id'];
                        $user_to = get_user_by( 'ID', $record[ 'user_id'] );
                        if( $user_to ) {
                            $user_to_title = $user_to->display_name;
                            $user_email = $user_to->user_email;
                        }
            
                        $headers[] = 'From: '.$from_title.' <'.$from_email.'>';
                        $headers[] = 'Content-Type: text/html; charset=UTF-8';

                        if( is_array( $email_cc_list ) && count( $email_cc_list ) > 0 ) {
                            foreach( $email_cc_list as $cc_id ) {
                                if( !empty( $cc_id ) ) {
                                    $headers[] = 'Cc: '.$cc_id;
                                }
                            }
                        }
                        
                        if( is_array( $email_bcc_list ) &&  count( $email_bcc_list ) > 0 ) {
                            foreach( $email_bcc_list as $bcc_id ) {
                                if( !empty( $bcc_id ) ) {
                                    $headers[] = 'Bcc: '.$bcc_id;
                                }
                            }
                        }

                        $date_format = badgeos_utilities::get_option( 'date_format', true );
                        $time_format = badgeos_utilities::get_option( 'time_format', true );

                        $email_subject = str_replace('[rank_type]', $rank_type, $email_subject ); 
                        $email_subject = str_replace('[rank_title]', $rank_title, $email_subject ); 
                        $email_subject = str_replace('[date_earned]', date( $date_format.' '.$time_format, strtotime($record['dateadded']) ), $email_subject );
                        $email_subject = str_replace('[user_email]', $user_email, $email_subject );
                        $email_subject = str_replace('[user_name]', $user_to_title, $email_subject );
                        ob_start();
                        
                        $email_content  = stripslashes( html_entity_decode( $email_content ) );
                        $email_content = str_replace("\'","'", $email_content);
                        $email_content = str_replace('\"','"', $email_content);

                        $email_content = str_replace('[rank_type]', $rank_type, $email_content ); 
                        $email_content = str_replace('[rank_title]', $rank_title, $email_content ); 
                        $email_content = str_replace('[date_earned]', date( $date_format.' '.$time_format, strtotime($record['dateadded']) ), $email_content ); 
                        $email_content = str_replace('[rank_link]', get_permalink( $rank_id ), $email_content );
                        $email_content = str_replace('[rank_image]', badgeos_get_rank_image( $rank_id ), $email_content );
                        $email_content = str_replace('[user_email]', $user_email, $email_content ); 
                        $email_content = str_replace('[user_name]', $user_to_title, $email_content ); 
                        $email_content = str_replace('[user_profile_link]', get_edit_profile_url( $to_user_id ), $email_content ); 

                        include( 'email_headers/header.php' );
                        ?>
                            <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;">
                                <tr>
                                    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">
                                        <?php echo $email_content; ?>
                                    </td>
                                </tr>
                            </table>
                        <?php
                        include( 'email_headers/footer.php' );
            
                        $message = ob_get_contents();
                        ob_end_clean();
                        
                        if( ! empty( $user_email ) ) {
                            wp_mail( $user_email, strip_tags( $email_subject ), $message, $headers );
                        }
                    }
                    
                }
            }
        }
    }
}
add_action( 'badgeos_after_award_rank', 'badgeos_send_rank_email', 10, 8 );

/**
 * Sends email when points are awarded...
 * 
 * @param $user_id
 * @param $credit_id
 * @param $achievement_id
 * @param $type
 * @param $new_points
 * @param $this_trigger
 * @param $step_id
 * @param $point_rec_id
 * 
 * @return none
 */
function badgeos_send_points_award_email( $user_id, $credit_id, $achievement_id, $type, $new_points, $this_trigger, $step_id, $point_rec_id ) {
    
    global $wpdb;
    if( badgeos_can_notify_user( $user_id ) ) {
        $badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array(); 
        $rank_main_type = $badgeos_settings['ranks_main_post_type'];
        $post_main_obj = badgeos_utilities::badgeos_get_post( $credit_id );
        
        if( $post_main_obj && $type == 'Award' ) {
            
            $point_post_type = $post_main_obj->post_type;
            $badgeos_admin_tools = ( $exists = badgeos_utilities::get_option( 'badgeos_admin_tools' ) ) ? $exists : array();

            $email_cc_list = badgeos_bcc_cc_emails( $badgeos_admin_tools, 'email_point_awards_cc_list', 'cc' );
            $email_bcc_list = badgeos_bcc_cc_emails( $badgeos_admin_tools, 'email_point_awards_bcc_list', 'bcc' );

            if( ! isset( $badgeos_admin_tools['email_disable_point_awards_email'] ) || $badgeos_admin_tools['email_disable_point_awards_email'] == 'no' ) {

                $results = $wpdb->get_results( "select * from ".$wpdb->prefix."badgeos_points where id='".$point_rec_id."'", 'ARRAY_A' );
                if( count( $results ) > 0 ) {
                    
                    $record = $results[ 0 ];
                    
                    $email_subject = $badgeos_admin_tools['email_point_awards_subject'];
                    if( empty( $email_subject ) ) {
                        $email_subject      = __( 'Congratulation for earning points', 'badgeos' );
                    }
                    
                    $email_content  = $badgeos_admin_tools['email_point_awards_content'];
                    
                    $from_title = get_bloginfo( 'name' );
                    $from_email = get_bloginfo( 'admin_email' );
                    
                    if( !empty( $badgeos_admin_tools['email_general_from_name'] ) ) {
                        $from_title = $badgeos_admin_tools['email_general_from_name'];
                    }

                    if( !empty( $badgeos_admin_tools['email_general_from_email'] ) ) {
                        $from_title = $badgeos_admin_tools['email_general_from_email'];
                    }

                    $point_title = get_the_title( $credit_id );
                    
                    $user_to_title = '';
                    $user_email = '';
                    $to_user_id = $record[ 'user_id'];
                    $user_to = get_user_by( 'ID', $record[ 'user_id'] );
                    if( $user_to ) {
                        $user_to_title = $user_to->display_name;
                        $user_email = $user_to->user_email;
                    }
        
                    $headers[] = 'From: '.$from_title.' <'.$from_email.'>';
                    $headers[] = 'Content-Type: text/html; charset=UTF-8';

                    if( is_array( $email_cc_list ) && count( $email_cc_list ) > 0 ) {
                        foreach( $email_cc_list as $cc_id ) {
                            if( !empty( $cc_id ) ) {
                                $headers[] = 'Cc: '.$cc_id;
                            }
                        }
                    }
                    
                    if( is_array( $email_bcc_list ) &&  count( $email_bcc_list ) > 0 ) {
                        foreach( $email_bcc_list as $bcc_id ) {
                            if( !empty( $bcc_id ) ) {
                                $headers[] = 'Bcc: '.$bcc_id;
                            }
                        }
                    }

                    $date_format = badgeos_utilities::get_option( 'date_format', true );
                    $time_format = badgeos_utilities::get_option( 'time_format', true );
                    
                    $email_subject = str_replace('[credit]', $record[ 'credit' ], $email_subject ); 
                    $email_subject = str_replace('[point_title]', $point_title, $email_subject ); 
                    $email_subject = str_replace('[date_earned]', date( $date_format.' '.$time_format, strtotime($record['dateadded']) ), $email_subject );
                    $email_subject = str_replace('[user_email]', $user_email, $email_subject );
                    $email_subject = str_replace('[user_name]', $user_to_title, $email_subject );
                    ob_start();
                    
                    $email_content  = stripslashes( html_entity_decode( $email_content ) );
                    $email_content = str_replace("\'","'", $email_content);
                    $email_content = str_replace('\"','"', $email_content);

                    $email_content = str_replace('[credit]', $record[ 'credit' ], $email_content ); 
                    $email_content = str_replace('[point_title]', $point_title, $email_content ); 
                    $email_content = str_replace('[date_earned]', date( $date_format.' '.$time_format, strtotime($record['dateadded']) ), $email_content ); 
                    $email_content = str_replace('[user_email]', $user_email, $email_content ); 
                    $email_content = str_replace('[user_name]', $user_to_title, $email_content ); 
                    $email_content = str_replace('[user_profile_link]', get_edit_profile_url( $to_user_id ), $email_content ); 

                    include( 'email_headers/header.php' );
                    ?>
                        <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;">
                            <tr>
                                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">
                                    <?php echo $email_content; ?>
                                </td>
                            </tr>
                        </table>
                    <?php
                    include( 'email_headers/footer.php' );
        
                    $message = ob_get_contents();
                    ob_end_clean();
                    
                    if( ! empty( $user_email ) ) {
                        wp_mail( $user_email, strip_tags( $email_subject ), $message, $headers );
                    }
                }
            }
        }
    }
}
add_action( 'badgeos_after_award_points', 'badgeos_send_points_award_email', 10, 8 );

/**
 * Sends email when points are deducted...
 * 
 * @param $user_id
 * @param $credit_id
 * @param $achievement_id
 * @param $type
 * @param $new_points
 * @param $this_trigger
 * @param $step_id
 * @param $point_rec_id
 * 
 * @return none
 */
function badgeos_send_points_deduct_email( $user_id, $credit_id, $achievement_id, $type, $new_points, $this_trigger, $step_id, $point_rec_id ) {
    
    global $wpdb;
    if( badgeos_can_notify_user( $user_id ) ) {
        $badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array(); 
        $rank_main_type = $badgeos_settings['ranks_main_post_type'];
        $post_main_obj = badgeos_utilities::badgeos_get_post( $credit_id );
        
        if( $post_main_obj && $type == 'Deduct' ) {
            
            $point_post_type = $post_main_obj->post_type;
            $badgeos_admin_tools = ( $exists = badgeos_utilities::get_option( 'badgeos_admin_tools' ) ) ? $exists : array();

            $email_cc_list = badgeos_bcc_cc_emails( $badgeos_admin_tools, 'email_point_deducts_cc_list', 'cc' );
            $email_bcc_list = badgeos_bcc_cc_emails( $badgeos_admin_tools, 'email_point_deducts_bcc_list', 'bcc' );

            if( ! isset( $badgeos_admin_tools['email_disable_point_deducts_email'] ) || $badgeos_admin_tools['email_disable_point_deducts_email'] == 'no' ) {

                $results = $wpdb->get_results( "select * from ".$wpdb->prefix."badgeos_points where id='".$point_rec_id."'", 'ARRAY_A' );
                if( count( $results ) > 0 ) {
                    
                    $record = $results[ 0 ];
                    
                    $email_subject = $badgeos_admin_tools['email_point_deducts_subject'];
                    if( empty( $email_subject ) ) {
                        $email_subject      = __( 'Point are deducted from your balance.', 'badgeos' );
                    }
                    
                    $email_content  = $badgeos_admin_tools['email_point_deducts_content'];
                    
                    $from_title = get_bloginfo( 'name' );
                    $from_email = get_bloginfo( 'admin_email' );
                    
                    if( !empty( $badgeos_admin_tools['email_general_from_name'] ) ) {
                        $from_title = $badgeos_admin_tools['email_general_from_name'];
                    }

                    if( !empty( $badgeos_admin_tools['email_general_from_email'] ) ) {
                        $from_title = $badgeos_admin_tools['email_general_from_email'];
                    }

                    $point_title = get_the_title( $credit_id );
                    
                    $user_to_title = '';
                    $user_email = '';
                    $to_user_id = $record[ 'user_id'];
                    $user_to = get_user_by( 'ID', $record[ 'user_id'] );
                    if( $user_to ) {
                        $user_to_title = $user_to->display_name;
                        $user_email = $user_to->user_email;
                    }
        
                    $headers[] = 'From: '.$from_title.' <'.$from_email.'>';
                    $headers[] = 'Content-Type: text/html; charset=UTF-8';
                    if( is_array( $email_cc_list ) && count( $email_cc_list ) > 0 ) {
                        foreach( $email_cc_list as $cc_id ) {
                            if( !empty( $cc_id ) ) {
                                $headers[] = 'Cc: '.$cc_id;
                            }
                        }
                    }
                    
                    if( is_array( $email_bcc_list ) &&  count( $email_bcc_list ) > 0 ) {
                        foreach( $email_bcc_list as $bcc_id ) {
                            if( !empty( $bcc_id ) ) {
                                $headers[] = 'Bcc: '.$bcc_id;
                            }
                        }
                    }

                    $date_format = badgeos_utilities::get_option( 'date_format', true );
                    $time_format = badgeos_utilities::get_option( 'time_format', true );
                    
                    $email_subject = str_replace('[credit]', $record[ 'credit' ], $email_subject ); 
                    $email_subject = str_replace('[point_title]', $point_title, $email_subject ); 
                    $email_subject = str_replace('[date_earned]', date( $date_format.' '.$time_format, strtotime($record['dateadded']) ), $email_subject );
                    $email_subject = str_replace('[user_email]', $user_email, $email_subject );
                    $email_subject = str_replace('[user_name]', $user_to_title, $email_subject );
                    ob_start();
                    
                    $email_content  = stripslashes( html_entity_decode( $email_content ) );
                    $email_content = str_replace("\'","'", $email_content);
                    $email_content = str_replace('\"','"', $email_content);

                    $email_content = str_replace('[credit]', $record[ 'credit' ], $email_content ); 
                    $email_content = str_replace('[point_title]', $point_title, $email_content ); 
                    $email_content = str_replace('[date_earned]', date( $date_format.' '.$time_format, strtotime($record['dateadded']) ), $email_content ); 
                    $email_content = str_replace('[user_email]', $user_email, $email_content ); 
                    $email_content = str_replace('[user_name]', $user_to_title, $email_content ); 
                    $email_content = str_replace('[user_profile_link]', get_edit_profile_url( $to_user_id ), $email_content ); 

                    include( 'email_headers/header.php' );
                    ?>
                        <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;">
                            <tr>
                                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">
                                    <?php echo $email_content; ?>
                                </td>
                            </tr>
                        </table>
                    <?php
                    include( 'email_headers/footer.php' );
        
                    $message = ob_get_contents();
                    ob_end_clean();
                    if( ! empty( $user_email ) ) {
                        wp_mail( $user_email, strip_tags( $email_subject ), $message, $headers );
                    }
                }
            }
        }
    }
}
add_action( 'badgeos_after_award_points', 'badgeos_send_points_deduct_email', 10, 8 );
add_action( 'badgeos_after_revoke_points', 'badgeos_send_points_deduct_email', 10, 8 );


/**
 * Sends bulk email when achievement are awarded in bulk from tools page [ achievement page ]...
 * 
 * @param $rec_type
 * @param $achievement_id
 * @param $user_id
 * @param $entry_id
 * 
 * @return none
 */ 
function badgeos_send_bulk_achievements_email( $rec_type, $achievement_id, $user_id, $entry_id ) {
    
    global $wpdb;
    
    if( badgeos_can_notify_user( $user_id ) && $_GET['page'] == 'badgeos_tools' ) {
        $badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array(); 
        $achievement_post_type = badgeos_utilities::get_post_type( $achievement_id );
        $achievement_type = $badgeos_settings['achievement_main_post_type'];
        $post_obj = get_page_by_path( $achievement_post_type, OBJECT, $achievement_type );
        
        $parent_post_type = '';
        if( $post_obj ) {
            $parent_post_type = $post_obj->post_type;
        }
        if ( trim( $achievement_type ) == trim( $parent_post_type ) ) {
            
            $badgeos_admin_tools = ( $exists = badgeos_utilities::get_option( 'badgeos_admin_tools' ) ) ? $exists : array();
            
            $email_cc_list = badgeos_bcc_cc_emails( $badgeos_admin_tools, 'email_achievement_cc_list', 'cc' );
            $email_bcc_list = badgeos_bcc_cc_emails( $badgeos_admin_tools, 'email_achievement_bcc_list', 'bcc' );

            if( ! isset( $badgeos_admin_tools['email_disable_earned_achievement_email'] ) || $badgeos_admin_tools['email_disable_earned_achievement_email'] == 'no' ) {
                
                $results = $wpdb->get_results( "select * from ".$wpdb->prefix."badgeos_achievements where entry_id='".$entry_id."'", 'ARRAY_A' );
                if( count( $results ) > 0 ) {
                    $record = $results[ 0 ];
                    $achievement_type   = $record[ 'post_type' ];
                    $type_title  = $post_obj->post_title;
                    $step_type = trim( $badgeos_settings['achievement_step_post_type'] );
                    $issue_date = $record[ 'date_earned' ];
                    $rec_type = $record[ 'rec_type' ];
                    $rec_date_earned = $record['date_earned'];                    
                    $date_format = badgeos_utilities::get_option( 'date_format', true );
                    $time_format = badgeos_utilities::get_option( 'time_format', true );
                    if( get_post_meta( $achievement_id, '_open_badge_enable_baking', true ) ) {
                        $evidence_page_id           = get_option( 'badgeos_evidence_url' );
                        $badgeos_evidence_url       = get_permalink( $evidence_page_id );
                        $badgeos_evidence_url       = add_query_arg( 'bg',  $record[ 'ID' ],        $badgeos_evidence_url );
                        $badgeos_evidence_url       = add_query_arg( 'eid', $record[ 'entry_id' ],  $badgeos_evidence_url );
                        $badgeos_evidence_url       = add_query_arg( 'uid', $record[ 'user_id' ],   $badgeos_evidence_url );    
                    }
                       
                    if( ! empty( $achievement_type ) && trim( $achievement_type ) != $step_type ) {
                        $email_subject = $badgeos_admin_tools['email_achievement_subject'];
                        if( empty( $email_subject ) ) {
                            $email_subject      = __( 'Congratulation for earning an achievement', 'badgeos' );
                        }
                        
                        $email_content  = $badgeos_admin_tools['email_achievement_content'];
                        
                        $from_title = get_bloginfo( 'name' );
                        $from_email = get_bloginfo( 'admin_email' );
                        
                        if( !empty( $badgeos_admin_tools['email_general_from_name'] ) ) {
                            $from_title = $badgeos_admin_tools['email_general_from_name'];
                        }
    
                        if( !empty( $badgeos_admin_tools['email_general_from_email'] ) ) {
                            $from_title = $badgeos_admin_tools['email_general_from_email'];
                        }
    
                        $achievement_title  = $record[ 'achievement_title' ];
                        $points             = $record[ 'points' ];
                        $achievement_image = badgeos_get_achievement_post_thumbnail( $achievement_id, 'full' );
                    
                        $user_to_title = '';
                        $user_email = '';
                        $to_user_id = $record[ 'user_id'];
                        $user_to = get_user_by( 'ID', $record[ 'user_id'] );
                        if( $user_to ) {
                            $user_to_title = $user_to->display_name;
                            $user_email = $user_to->user_email;
                        }
            
                        $headers[] = 'From: '.$from_title.' <'.$from_email.'>';
                        $headers[] = 'Content-Type: text/html; charset=UTF-8';
                        if( is_array( $email_cc_list ) && count( $email_cc_list ) > 0 ) {
                            foreach( $email_cc_list as $cc_id ) {
                                if( !empty( $cc_id ) ) {
                                    $headers[] = 'Cc: '.$cc_id;
                                }
                            }
                        }
                        
                        if( is_array( $email_bcc_list ) &&  count( $email_bcc_list ) > 0 ) {
                            foreach( $email_bcc_list as $bcc_id ) {
                                if( !empty( $bcc_id ) ) {
                                    $headers[] = 'Bcc: '.$bcc_id;
                                }
                            }
                        }
                    
                        $email_subject = str_replace('[achievement_type]', $type_title, $email_subject ); 
                        $email_subject = str_replace('[achievement_title]', $achievement_title, $email_subject ); 
                        $email_subject = str_replace('[points]', $points, $email_subject );
                        $email_subject = str_replace('[user_email]', $user_email, $email_subject );
                        $email_subject = str_replace('[user_name]', $user_to_title, $email_subject );
    
                        ob_start();
                        
                        $email_content  = stripslashes( html_entity_decode( $email_content ) );
                        $email_content = str_replace("\'","'", $email_content);
                        $email_content = str_replace('\"','"', $email_content);
            
                        $email_content = str_replace('[achievement_type]', $type_title, $email_content ); 
                        $email_content = str_replace('[achievement_title]', $achievement_title, $email_content );
                        $email_content = str_replace('[date_earned]', date( $date_format.' '.$time_format, strtotime($record['date_earned']) ), $email_content );  
                        $email_content = str_replace('[achievement_link]', get_permalink($achievement_id), $email_content ); 
                        $email_content = str_replace('[points]', $points, $email_content ); 
                        $email_content = str_replace('[user_email]', $user_email, $email_content ); 
                        $email_content = str_replace('[user_name]', $user_to_title, $email_content ); 
                        $email_content = str_replace('[achievement_image]', $achievement_image, $email_content ); 
                        $email_content = str_replace('[user_profile_link]', get_edit_profile_url( $to_user_id ), $email_content ); 
                        $email_content = str_replace('[evidence]', badgeos_include_evidence_in_email( $achievement_id, $issue_date, $rec_type, $badgeos_evidence_url, $rec_date_earned ), $email_content);
                        include( 'email_headers/header.php' );
                        ?>
                            <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;">
                                <tr>
                                    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">
                                        <?php echo $email_content; ?>
                                    </td>
                                </tr>
                            </table>
                        <?php
                        include( 'email_headers/footer.php' );
                        $message = ob_get_contents();
                        ob_end_clean();
                        if( ! empty( $user_email ) ) {
                            wp_mail( $user_email, strip_tags( $email_subject ), $message, $headers );
                        }
                    }
                }
            }
        }
    }
}
add_action( 'badgeos_achievements_new_added', 'badgeos_send_bulk_achievements_email', 10, 4 );