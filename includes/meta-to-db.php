<?php
    global $wpdb;

    $is_badgeos_all_achievement_db_updated = get_option( 'badgeos_all_achievement_db_updated', 'No' );
    if( $is_badgeos_all_achievement_db_updated!='Yes' ) {
        add_action( 'admin_notices', 'badgeos_update_achievement_from_meta_to_db' );
    }

    function badgeos_update_achievement_from_meta_to_db() {
        if( ! is_admin() ) {
            return;
        }
    
        $class = 'notice is-dismissible error';
        $message = __( 'Please <a href="admin-post.php?action=badgeos_update_from_meta_to_db" >click</a> here to update Badgeos achivements from meta to data.', 'badgeos' );
        printf ( '<div id="message" class="%s"> <p>%s</p></div>', $class, $message );
    }
    
    add_action( 'admin_post_badgeos_update_from_meta_to_db', 'badgeos_update_from_meta_to_db_callback' );
    function badgeos_update_from_meta_to_db_callback() {
        
        if( ! is_admin() ) {
            return;
        }

        $site_id = get_current_blog_id();
        $users = get_users( 'fields=ids' );
        $updated_count = 0;

        foreach( $users as $user_id ) {
            
            global $wpdb;

            $meta_to_db = get_user_meta( $user_id, 'updated_achivements_meta_to_db', true );
            if( $meta_to_db != 'Yes' ) {
                $achievements = get_user_meta( $user_id, '_badgeos_achievements', true );
                
                if( is_array( $achievements ) && count( $achievements ) > 0 ) {
                    foreach( $achievements[$site_id] as $achievement ) {
                        
                        $badgeos_settings = get_option( 'badgeos_settings' );
                        $point_id = 0;
                        $point_type = 0;
                        if( isset( $achievement->points ) ) {
                            $point_rec = $achievement->points;
                        
                            if( is_array( $point_rec ) && count( $point_rec ) ) {
                                $point_id = intval( $point_rec['_badgeos_points'] );
                                $point_type = intval( $point_rec['_badgeos_points_type'] );
                            } else {
                                $point_id = intval( $point_rec );
                                $point_type = $badgeos_settings['default_point_type'];
                            }
                        }
                        
                        $wpdb->insert($wpdb->prefix.'badgeos_achievements', array(
                            'ID'        			=> $achievement->ID,
                            'post_type'      		=> $achievement->post_type,
                            'achievement_title'     => $achievement->title,
                            'points'             	=> $point_id,
                            'point_type'			=> $point_type,
                            'this_trigger'         	=> $achievement->trigger,
                            'user_id'               => absint( $user_id ),
                            'site_id'               => $site_id,
                            'image'          		=> '',
                            'rec_type'          	=> 'normal_old',
                            'date_earned'           => date("Y-m-d H:i:s", $achievement->date_earned)
                        ));
                    }
                }
                
                update_user_meta( $user_id, 'updated_achivements_meta_to_db', 'Yes' );
            }

            $updated_count += 1;
        }

        if( count( $users ) == $updated_count && $updated_count > 0 ) {
            update_option( 'badgeos_all_achievement_db_updated', 'Yes' );
        }

        wp_redirect( 'admin.php?page=badgeos_settings' );
        exit;
    }
?>