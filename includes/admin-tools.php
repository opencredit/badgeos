<?php
/**
 * Admin Tools Page
 *
 * @package BadgeOS WP
 * @subpackage Admin
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Class Badgeos_Tools
 */
class Badgeos_Tools {

    public $page_tab;

    /**
     * Badgeos_Tools constructor.
     * @param array $_args
     */
    public function __construct() {

        $this->page_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'achievement_tools';
        add_action( 'admin_menu', [ $this, 'add_tool_page' ] );
        add_action( 'admin_init', [ $this, 'badgeos_award_reward_achievements' ] );
        add_action( 'admin_init', [ $this, 'badgeos_award_reward_credits' ] );
        add_action( 'admin_init', [ $this, 'badgeos_award_reward_ranks' ] );
        add_action( 'admin_init', [ $this, 'badgeos_tools_email_tab' ] );
    }

    /**
     * Add BadgeOS Tools Page
     */
    public function add_tool_page() {
        add_submenu_page(
            'badgeos_badgeos',
            __( 'Tools', 'badgeos' ),
            __( 'Tools', 'badgeos' ),
            badgeos_get_manager_capability(),
            'badgeos_tools',
            [ $this, 'register_badgeos_tool' ]
        );
    }

    /**
     * Register BadgeOS Tools
     */
    public function register_badgeos_tool() { 
        wp_enqueue_script( 'badgeos-jquery-ui-js' );
        wp_enqueue_style( 'badgeos-admin-styles' );
        ?>

        <div class="wrap badgeos-tools-page">
            <div id="icon-options-general" class="icon32"></div>
            <h2><?php _e( 'Tools', 'badgeos' ); ?></h2>

            <div class="nav-tab-wrapper">
                <?php
                    $badgeos_tools_sections = $this->badgeos_get_tools_sections();
                    foreach( $badgeos_tools_sections as $key => $badgeos_tools_section ) {
                        ?>
                        <a href="?page=badgeos_tools&tab=<?php echo $key; ?>" class="nav-tab <?php echo $this->page_tab == $key ? 'nav-tab-active' : ''; ?>">
                            <i class="fa <?php echo $badgeos_tools_section['icon']; ?>" aria-hidden="true"></i>
                            <?php _e( $badgeos_tools_section['title'], 'badgeos' ); ?>
                        </a>
                        <?php
                    }
                ?>
            </div>

            <?php
                foreach( $badgeos_tools_sections as $key => $badgeos_tools_section ) {
                    if( $this->page_tab == $key ) {
                        $key = str_replace( '_', '-', $key );
                        include( 'tools/' . $key . '.php' );
                    }
                }
            ?>
        </div>
        <?php
    }

    /**
     * BadgeOS Tools Section
     *
     * @return mixed|void
     */
    public function badgeos_tools_email_tab() {
        global $wpdb;

        if( ! $_POST || $_SERVER['REQUEST_METHOD'] != 'POST' ) {
            return false;
        }
        
        $badgeos_admin_tools = ( $exists = get_option( 'badgeos_admin_tools' ) ) ? $exists : array();
        
        if( ( isset( $_POST['action'] ) && $_POST['action']=='badgeos_tools_email_general' ) ) {
            
            if( isset( $_POST['badgeos_tools_email_general'] ) ) {
                $tools_data = $_POST['badgeos_tools'];
                
                if( isset( $_FILES['badgeos_tools_email_general_logo'] ) ) {
                    if ( isset( $_FILES['badgeos_tools_email_general_logo']['name'] ) && !empty( $_FILES['badgeos_tools_email_general_logo']['name'] ) ) {
                        $file_dir = wp_upload_bits( $_FILES['badgeos_tools_email_general_logo']['name'], null, @file_get_contents( $_FILES['badgeos_tools_email_general_logo']['tmp_name'] ) );
                        $badgeos_admin_tools['badgeos_tools_email_logo_url'] = $file_dir[ 'url' ];
                        $badgeos_admin_tools['badgeos_tools_email_logo_dir'] = $file_dir[ 'file' ];
                    }
                }

                $badgeos_admin_tools['email_general_footer_text']   =  sanitize_text_field( $tools_data[ 'email_general_footer_text' ] );
                $badgeos_admin_tools['email_general_from_name']     =  sanitize_text_field( $tools_data[ 'email_general_from_name' ] );
                $badgeos_admin_tools['email_general_from_email']    =  sanitize_text_field( $tools_data[ 'email_general_from_email' ] );
                update_option( 'badgeos_admin_tools',                  $badgeos_admin_tools );
            }
        }

        if( ( isset( $_POST['action'] ) && $_POST['action']=='badgeos_tools_email_achievement' ) ) {
            
            if( isset( $_POST['badgeos_tools_email_achievement'] ) ) {
                $tools_data = $_POST['badgeos_tools'];
                $email_disable_earned_achievement_email = 'no';
                if( isset( $tools_data['email_disable_earned_achievement_email'] ) ) {
                    $email_disable_earned_achievement_email = 'yes';
                }
                
                $badgeos_admin_tools['email_achievement_subject']               = sanitize_text_field( $tools_data[ 'email_achievement_subject' ] );
                $badgeos_admin_tools['email_achievement_content']               = htmlentities( $tools_data[ 'email_achievement_content' ] );
                $badgeos_admin_tools['email_disable_earned_achievement_email']  = $email_disable_earned_achievement_email;

                update_option( 'badgeos_admin_tools', $badgeos_admin_tools );
            }
        }

        if( ( isset( $_POST['action'] ) && $_POST['action']=='badgeos_tools_email_achievement_steps' ) ) {
            
            if( isset( $_POST['badgeos_tools_email_achievement_steps'] ) ) {
                $tools_data = $_POST['badgeos_tools'];
                $email_disable_achievement_steps_email = 'no';
                if( isset( $tools_data['email_disable_achievement_steps_email'] ) ) {
                    $email_disable_achievement_steps_email = 'yes';
                }
                
                $badgeos_admin_tools['email_steps_achievement_subject']        = sanitize_text_field( $tools_data[ 'email_steps_achievement_subject' ] );
                $badgeos_admin_tools['email_steps_achievement_content']        = htmlentities( $tools_data[ 'email_steps_achievement_content' ] );
                $badgeos_admin_tools['email_disable_achievement_steps_email']  = $email_disable_achievement_steps_email;

                update_option( 'badgeos_admin_tools', $badgeos_admin_tools );
            }
        }
       
        if( ( isset( $_POST['action'] ) && $_POST['action']=='badgeos_tools_email_ranks' ) ) {
            
            if( isset( $_POST['badgeos_tools_email_ranks'] ) ) {
                $tools_data = $_POST['badgeos_tools'];
                $email_disable_ranks_email = 'no';
                if( isset( $tools_data['email_disable_ranks_email'] ) ) {
                    $email_disable_ranks_email = 'yes';
                }

                $badgeos_admin_tools['email_ranks_subject']        = sanitize_text_field( $tools_data[ 'email_ranks_subject' ] );
                $badgeos_admin_tools['email_ranks_content']        = htmlentities( $tools_data[ 'email_ranks_content' ] );
                $badgeos_admin_tools['email_disable_ranks_email']  = $email_disable_ranks_email;
                
                update_option( 'badgeos_admin_tools', $badgeos_admin_tools );
            }
        }

        if( ( isset( $_POST['action'] ) && $_POST['action']=='badgeos_tools_email_rank_steps' ) ) {
            
            if( isset( $_POST['badgeos_tools_email_rank_steps'] ) ) {
                $tools_data = $_POST['badgeos_tools'];
                $email_disable_rank_steps_email = 'no';
                if( isset( $tools_data['email_disable_rank_steps_email'] ) ) {
                    $email_disable_rank_steps_email = 'yes';
                }

                $badgeos_admin_tools['email_steps_rank_subject']        = sanitize_text_field( $tools_data[ 'email_steps_rank_subject' ] );
                $badgeos_admin_tools['email_steps_rank_content']        = htmlentities( $tools_data[ 'email_steps_rank_content' ] );
                $badgeos_admin_tools['email_disable_rank_steps_email']  = $email_disable_rank_steps_email;
                
                update_option( 'badgeos_admin_tools', $badgeos_admin_tools );
            }
        }
       
        if( ( isset( $_POST['action'] ) && $_POST['action']=='badgeos_tools_email_point_awards' ) ) {
            
            if( isset( $_POST['badgeos_tools_email_point_awards'] ) ) {
                $tools_data = $_POST['badgeos_tools'];
                $email_disable_point_awards_email = 'no';
                if( isset( $tools_data['email_disable_point_awards_email'] ) ) {
                    $email_disable_point_awards_email = 'yes';
                }

                $badgeos_admin_tools['email_point_awards_subject']          = sanitize_text_field( $tools_data[ 'email_point_awards_subject' ] );
                $badgeos_admin_tools['email_point_awards_content']          = htmlentities( $tools_data[ 'email_point_awards_content' ] );
                $badgeos_admin_tools['email_disable_point_awards_email']    = $email_disable_point_awards_email;
                
                update_option( 'badgeos_admin_tools', $badgeos_admin_tools );
            }
        }

        if( ( isset( $_POST['action'] ) && $_POST['action']=='badgeos_tools_email_point_deducts' ) ) {
            
            if( isset( $_POST['badgeos_tools_email_point_deducts'] ) ) {
                $tools_data = $_POST['badgeos_tools'];
                $email_disable_point_deducts_email = 'no';
                if( isset( $tools_data['email_disable_point_deducts_email'] ) ) {
                    $email_disable_point_deducts_email = 'yes';
                }

                $badgeos_admin_tools['email_point_deducts_subject']          = sanitize_text_field( $tools_data[ 'email_point_deducts_subject' ] );
                $badgeos_admin_tools['email_point_deducts_content']          = htmlentities( $tools_data[ 'email_point_deducts_content' ] );
                $badgeos_admin_tools['email_disable_point_deducts_email']    = $email_disable_point_deducts_email;
                
                update_option( 'badgeos_admin_tools', $badgeos_admin_tools );
            }
        }
     }
    /**
     * BadgeOS Tools Section
     *
     * @return mixed|void
     */
    public function badgeos_get_tools_sections() {

        $badgeos_tools_sections = array(
            'achievement_tools' => array(
                'title' => __( 'Achievements', 'badgeos' ),
                'icon' => 'fa-shield',
            ),
            'credit_tools' => array(
                'title' => __( 'Credits', 'badgeos' ),
                'icon' => 'fa-hashtag',
            ),
            'rank_tools' => array(
                'title' => __( 'Ranks', 'badgeos' ),
                'icon' => 'fa-arrow-up',
            ),
            'email_tools' => array(   
                'title' => __( 'Emails', 'badgeos' ),
                'icon' => 'fa-envelope',
            ),
            'system_tools' => array(
                'title' => __( 'System', 'badgeos' ),
                'icon' => 'fa-info',
            ),
        );

        return apply_filters( 'badgeos_tools_sections', $badgeos_tools_sections );
    }

    /**
     * Award | Revoke Achievements
     *
     * @return bool
     */
    public function badgeos_award_reward_achievements(){

        global $wpdb;

        if( ! $_POST || $_SERVER['REQUEST_METHOD'] != 'POST' ) {
            return false;
        }

        $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();

        if( ( isset( $_POST['achievement_bulk_award'] ) && wp_verify_nonce( $_POST['achievement_bulk_award'], 'achievement_bulk_award' ) ) ||
            ( isset( $_POST['achievement_bulk_revoke'] ) && wp_verify_nonce( $_POST['achievement_bulk_revoke'], 'achievement_bulk_revoke' ) )
        ) {
            /**
             * Award Achievements
             */
            if( isset( $_POST['action'] ) && $_POST['action'] == 'award_bulk_achievement' ) {

                $achievement_ids_to_award = ( isset( $_POST['badgeos_tools']['award_achievement_types'] ) ? $_POST['badgeos_tools']['award_achievement_types'] : '' );

                if( ! empty( $achievement_ids_to_award ) ){
                    $award_achievements_to_all_users = ( ( isset( $_POST['badgeos_tools']['award_all_users'] ) &&
                        $_POST['badgeos_tools']['award_all_users'] == 'on' ) ? $_POST['badgeos_tools']['award_all_users'] : '' );
                    foreach( $achievement_ids_to_award as $achievement_id ) {
                        $achievement_object = badgeos_build_achievement_object( $achievement_id );
                        $users_to_award = array();
                        if( 'on' == $award_achievements_to_all_users ) {
                            $users = get_users();
                            foreach( $users as $user ) {
                                $users_to_award[] = $user->ID;
                            }
                        } else {
                            $users_to_award = ( isset( $_POST['badgeos_tools']['award_users'] ) ? $_POST['badgeos_tools']['award_users'] : '' );
                        }

                        foreach( $users_to_award as $user_ids ) {
                            badgeos_update_user_achievements( array( 'user_id' => $user_ids, 'new_achievements' => array( $achievement_object ) ) );
                        }
                    }
                }
            }

            
            /**
             * Revoke Achievements
             */
            if( isset( $_POST['action'] ) && $_POST['action'] == 'revoke_bulk_achievement' ) {

                $achievement_ids_to_revoke = ( isset( $_POST['badgeos_tools']['revoke_achievement_types'] ) ? $_POST['badgeos_tools']['revoke_achievement_types'] : '' );

                if( ! empty( $achievement_ids_to_revoke ) ){
                    $revoke_achievements_to_all_users = ( ( isset( $_POST['badgeos_tools']['revoke_all_users'] ) &&
                        $_POST['badgeos_tools']['revoke_all_users'] == 'on' ) ? $_POST['badgeos_tools']['revoke_all_users'] : '' );
                    foreach( $achievement_ids_to_revoke as $achievement_id ) {
                        $users_to_revoke = array();
                        if( 'on' == $revoke_achievements_to_all_users ) {
                            $users = get_users();
                            foreach( $users as $user ) {
                                $users_to_revoke[] = $user->ID;
                            }
                        } else {
                            $users_to_revoke = ( isset( $_POST['badgeos_tools']['revoke_users'] ) ? $_POST['badgeos_tools']['revoke_users'] : '' );
                        }

                        foreach( $users_to_revoke as $user_id ) {

                            if( intval( $achievement_id ) > 0 ) {

                                $achievements = array();
                                $entries = array();
                                $indexes = array();

                                $i=0;
                                $my_achievements = badgeos_get_user_achievements( array( 'user_id' => $user_id, 'achievement_id'=>$achievement_id ) );
                                foreach( $my_achievements as $rec ) {
                                    $achievements[] = $rec->ID;
                                    $indexes[] = $i++;
                                    $entries[] = $rec->entry_id;
                                }

                                $index = 0;
                                $new_achievements = array();
                                $delete_achievement = array();
                                foreach( $my_achievements as $my_achs ) {
                                    if( $my_achs->post_type != trim( $badgeos_settings['achievement_step_post_type'] ) ) {
                                        if( in_array( $index, $indexes ) && in_array( $my_achs->ID, $achievements ) ) {
                                            $delete_achievement[] = $my_achs->ID;
                                        } else {
                                            $new_achievements[] = $my_achs;
                                        }
                                        $index += 1;
                                    } else {
                                        $new_achievements[] = $my_achs;
                                    }
                                }

                                foreach( $delete_achievement as $del_ach_id ) {
                                    $children = badgeos_get_achievements( array( 'children_of' => $del_ach_id) );
                                    foreach( $children as $child ) {
                                        foreach( $new_achievements as $index => $item ) {

                                            if( $child->ID == $item->ID ) {
                                                unset( $new_achievements[ $index ] );
                                                $new_achievements = array_values( $new_achievements );
                                                $table_name = $wpdb->prefix . "badgeos_achievements";
                                                if($wpdb->get_var("show tables like '$table_name'") == $table_name) {
                                                    $where = " where user_id='".intval($user_id)."' and entry_id = '".intval($item->entry_id)."'";
                                                    $wpdb->get_results('delete from '.$wpdb->prefix.'badgeos_achievements '.$where.' limit 1' );
                                                }
                                                badgeos_decrement_user_trigger_count( $user_id, $child->ID, $del_ach_id );
                                                break;
                                            }
                                        }
                                    }
                                }
                                $new_achievements = array_values( $new_achievements );

                                // Update user's earned achievements
                                badgeos_update_user_achievements( array( 'user_id' => $user_id, 'all_achievements' => $new_achievements ) );

                                foreach( $entries as $key => $entry ) {
                                    $where = array( 'user_id' => $user_id );

                                    if( $entry != 0 ) {
                                        $where['entry_id'] = $entry;
                                    }
                                    do_action( 'badgeos_before_revoke_achievement', $user_id, intval( $achievements[$key] ), $entry );

                                    $table_name = $wpdb->prefix . "badgeos_achievements";
                                    if($wpdb->get_var("show tables like '$table_name'") == $table_name) {
                                        $wpdb->delete( $table_name, $where );
                                    }
                                    do_action( 'badgeos_after_revoke_achievement', $user_id, intval( $achievements[$key] ), $entry );
                                }

                                // Available hook for taking further action when an achievement is revoked
                                do_action( 'badgeos_revoke_bulk_achievement', $user_id, $achievements, $entries );
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Award | Revoke Credits
     *
     * @return bool
     */
    public function badgeos_award_reward_credits(){

        if( ! $_POST || $_SERVER['REQUEST_METHOD'] != 'POST' ) {
            return false;
        }

        if( ( isset( $_POST['credit_bulk_award'] ) &&
                wp_verify_nonce( $_POST['credit_bulk_award'], 'credit_bulk_award' ) ) ||
            ( isset( $_POST['credit_bulk_revoke'] ) &&
                wp_verify_nonce( $_POST['credit_bulk_revoke'], 'credit_bulk_revoke' ) ) ) {
            /**
             * Award Credits
             */
            if( isset( $_POST['action'] ) && $_POST['action'] == 'award_credits_in_bulk' ) {

                $credit_type_to_award = ( isset( $_POST['badgeos_tools']['award_credit_type'] ) ? $_POST['badgeos_tools']['award_credit_type'] : '' );
                $credit_amount = ( isset( $_POST['badgeos_tools']['credit_amount'] ) ? $_POST['badgeos_tools']['credit_amount'] : 0 );

                if( ! empty( $credit_type_to_award ) && $credit_amount > 0 ) {
                    $award_credits_to_all_users = ( ( isset( $_POST['badgeos_tools']['award_all_users'] ) && $_POST['badgeos_tools']['award_all_users'] == 'on' ) ? $_POST['badgeos_tools']['award_all_users'] : '' );
                    $users_to_award = array();
                    if( 'on' == $award_credits_to_all_users ) {
                        $users = get_users();
                        foreach( $users as $user ) {
                            $users_to_award[] = $user->ID;
                        }
                    } else {
                        $users_to_award = ( isset( $_POST['badgeos_tools']['award_users'] ) ? $_POST['badgeos_tools']['award_users'] : '' );
                    }
                    foreach( $users_to_award as $user_id ) {
                        badgeos_award_credit( $credit_type_to_award, $user_id, 'Award', $credit_amount, 'admin_bulk_award', get_current_user_id(), '', '' );
                    }
                }
            }

            /**
             * Revoke Credits
             */
            if( isset( $_POST['action'] ) && $_POST['action'] == 'revoke_credits_in_bulk' ) {
                $credit_type_to_revoke = ( isset( $_POST['badgeos_tools']['revoke_credit_type'] ) ? $_POST['badgeos_tools']['revoke_credit_type'] : '' );
                $credit_amount = ( isset( $_POST['badgeos_tools']['credit_amount'] ) ? $_POST['badgeos_tools']['credit_amount'] : 0 );

                if( ! empty( $credit_type_to_revoke ) && ( int ) $credit_amount ) {
                    $revoke_credits_to_all_users = ( ( isset( $_POST['badgeos_tools']['revoke_all_users'] ) &&
                        $_POST['badgeos_tools']['revoke_all_users'] == 'on' ) ? $_POST['badgeos_tools']['revoke_all_users'] : '' );
                    $users_to_revoke = array();
                    if( 'on' == $revoke_credits_to_all_users ) {
                        $users = get_users();
                        foreach( $users as $user ) {
                            $users_to_revoke[] = $user->ID;
                        }
                    } else {
                        $users_to_revoke = ( isset( $_POST['badgeos_tools']['revoke_users'] ) ? $_POST['badgeos_tools']['revoke_users'] : '' );
                    }

                    foreach( $users_to_revoke as $user_id ) {
                        $earned_credits = badgeos_get_points_by_type( $credit_type_to_revoke, $user_id );
                        if( ( $earned_credits - $credit_amount ) >= 0 ) {
                            badgeos_revoke_credit( $credit_type_to_revoke, $user_id, 'Deduct', $credit_amount, 'admin_bulk_revoke', get_current_user_id(), '', '' );
                        }
                    }
                }
            }
        }
    }

    /**
     * Award | Revoke Ranks
     *
     * @return bool
     */
    public function badgeos_award_reward_ranks(){

        if( ! $_POST || $_SERVER['REQUEST_METHOD'] != 'POST' ) {
            return false;
        }

        if( ( isset( $_POST['rank_bulk_award'] ) &&
                wp_verify_nonce( $_POST['rank_bulk_award'], 'rank_bulk_award' ) ) ||
            ( isset( $_POST['rank_bulk_revoke'] ) &&
                wp_verify_nonce( $_POST['rank_bulk_revoke'], 'rank_bulk_revoke' ) ) ) {

            /**
             * Award Ranks
             */
            if( isset( $_POST['action'] ) && $_POST['action'] == 'award_bulk_ranks' ) {

                $rank_ids_to_award = ( isset( $_POST['badgeos_tools']['award_rank_types'] ) ? $_POST['badgeos_tools']['award_rank_types'] : '' );

                if( ! empty( $rank_ids_to_award ) ){
                    $award_ranks_to_all_users = ( ( isset( $_POST['badgeos_tools']['award_all_users'] ) &&
                        $_POST['badgeos_tools']['award_all_users'] == 'on' ) ? $_POST['badgeos_tools']['award_all_users'] : '' );

                    foreach( $rank_ids_to_award as $rank_id ) {
                        $users_to_award = array();
                        if( 'on' == $award_ranks_to_all_users ) {
                            $users = get_users();
                            foreach( $users as $user ) {
                                $users_to_award[] = $user->ID;
                            }
                        } else {
                            $users_to_award = ( isset( $_POST['badgeos_tools']['award_users'] ) ? $_POST['badgeos_tools']['award_users'] : '' );
                        }

                        foreach( $users_to_award as $user_id ) {
                            badgeos_update_user_rank( array(
                                'user_id'           => $user_id,
                                'site_id'           => get_current_blog_id(),
                                'rank_id'           => (int) $rank_id,
                                'this_trigger'      => 'admin_awarded',
                                'credit_id'         => 0,
                                'credit_amount'     => 0,
                                'admin_id'          => ( current_user_can( 'administrator' ) ? get_current_user_id() : 0 ),
                            )  );
                        }
                    }
                }
            }

            /**
             * Revoke Ranks
             */
            if( isset( $_POST['action'] ) && $_POST['action'] == 'revoke_bulk_ranks' ) {

                $rank_ids_to_revoke = ( isset( $_POST['badgeos_tools']['revoke_rank_types'] ) ? $_POST['badgeos_tools']['revoke_rank_types'] : '' );

                if( ! empty( $rank_ids_to_revoke ) ){
                    $revoke_rank_to_all_users = ( ( isset( $_POST['badgeos_tools']['revoke_all_users'] ) &&
                        $_POST['badgeos_tools']['revoke_all_users'] == 'on' ) ? $_POST['badgeos_tools']['revoke_all_users'] : '' );

                    foreach( $rank_ids_to_revoke as $rank_id ) {
                        $users_to_revoke = array();
                        if( 'on' == $revoke_rank_to_all_users ) {
                            $users = get_users();
                            foreach( $users as $user ) {
                                $users_to_revoke[] = $user->ID;
                            }
                        } else {
                            $users_to_revoke = ( isset( $_POST['badgeos_tools']['revoke_users'] ) ? $_POST['badgeos_tools']['revoke_users'] : '' );
                        }

                        foreach( $users_to_revoke as $user_id ) {
                            badgeos_revoke_rank_from_user_account( absint( $user_id ), absint( $rank_id ) );
                        }
                    }
                }

            }
        }


    }
}

new Badgeos_Tools();