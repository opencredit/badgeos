<?php
/**
 * User-related Functions
 *
 * @package BadgeOS
 * @subpackage Admin
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Get a user's badgeos achievements
 *
 * @since  1.0.0
 * @param  array $args An array of all our relevant arguments
 * @return array       An array of all the achievement objects that matched our parameters, or empty if none
 */
function badgeos_get_user_achievements( $args = array() ) {

	global $wpdb;
	
	// Setup our default args
	$defaults = array(
		'user_id'          => 0,     // The given user's ID
		'site_id'          => get_current_blog_id(), // The given site's ID
		'achievement_id'   => false, // A specific achievement's post ID
		'achievement_type' => false, // A specific achievement type
		'start_date' => false, // A specific achievement type
		'end_date' => false, // A specific achievement type
		'no_step' => false, // A specific achievement type
		'since'            => 0,     // A specific timestamp to use in place of $limit_in_days
		'pagination'	=> false,// if true the pagination will be applied
		'limit'	=> 10,
		'page'	=> 1,
		'orderby' => 'entry_id',
		'order' => 'ASC',
		'total_only' => false
	);
	$args = wp_parse_args( $args, $defaults );

	// Return our $achievements array_values (so our array keys start back at 0), or an empty array
	// return ( is_array( $achievements ) ? array_values( $achievements ) : array());

	if( $args['user_id'] == 0 ) {
        $args['user_id'] = get_current_user_id();
    }
 
	$table_name = $wpdb->prefix . 'badgeos_achievements';
	if($wpdb->get_var("show tables like '$table_name'") == $table_name) {
		$where = 'user_id = ' . $args['user_id'];
	
		if( isset( $args['entry_id'] ) && $args['entry_id'] != false ) {
			$where .= ' AND entry_id = ' . $args['entry_id'];
		}

		if( $args['achievement_id'] != false ) {
			$where .= ' AND ID = ' . $args['achievement_id'];
		}
	
		if( $args['achievement_type'] != false ) {
			if( is_array( $args['achievement_type'] ) ) {
				
				$wherepttype = '';

				foreach( $args['achievement_type'] as $achievement_type ) {

					if( ! empty( $wherepttype ) ) {
						$wherepttype .= ' OR ' ;
					}
					$wherepttype .= " post_type = '".$achievement_type . "'";
				}

				$where .= ' and ( '. $wherepttype .')';
			} else {

				$where .= " AND post_type = '".$args['achievement_type']."'";
			}
		}

        $badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
		if( $args['no_step'] ) {
            $where .= " AND post_type != '".trim( $badgeos_settings['achievement_step_post_type'] )."'";
		}
	
		if( $args['since'] > 1 ) {
			$where .= " AND date_earned > '". date("Y-m-d H:i:s", $args['since'] ). "'";
		}
		
		if( $args['start_date'] ) {
			$where .= " AND date_earned >= '". $args['start_date']. "'";
		}

		if( $args['end_date'] ) {
			$where .= " AND date_earned <= '". $args['end_date']. "'";
		}
		
		$user_achievements = [];
		if( $args['total_only'] == true ) {
			$user_achievements = $wpdb->get_var( "SELECT count(entry_id) as entry_id FROM $table_name WHERE $where" );
		} else {
			
			$paginate_str = '';
			if( $args['pagination'] == true ||  $args['pagination'] == 'true' ) { 
				$offset = (intval( $args['page'] )-1) * intval( $args['limit'] );
				if( $offset < 0 ) {
					$offset = 0;
				}
				$paginate_str = ' limit '.$offset.', '.$args['limit'];
			}
			
			$order_str = '';
			if( !empty( $args['orderby'] ) &&  !empty( $args['order'] ) ) {
				$order_str = " ORDER BY ".$args['orderby']." ".$args['order'];
			}
			$user_achievements = $wpdb->get_results( "SELECT * FROM $table_name WHERE $where ".$order_str.' '.$paginate_str );

		}

		return $user_achievements;
	} else {
		// Grab the user's current achievements
		$achievements = ( $earned_items = badgeos_utilities::get_user_meta( absint( $args['user_id'] ), '_badgeos_achievements', true ) ) ? (array) $earned_items : array();

		// If we want all sites (or no specific site), return the full array
		if ( empty( $achievements ) || empty( $args['site_id']) || 'all' == $args['site_id'] )
			return $achievements;

		if ( !array_key_exists( $args['site_id'], $achievements ) ) { return; }
		
		// Otherwise, we only want the specific site's achievements
		$achievements = $achievements[$args['site_id']];

		if ( is_array( $achievements) && ! empty( $achievements ) ) {
			foreach ( $achievements as $key => $achievement ) {

				// Drop any achievements earned before our since timestamp
				if ( absint($args['since']) > $achievement->date_earned )
					unset($achievements[$key]);

				// Drop any achievements that don't match our achievement ID
				if ( ! empty( $args['achievement_id'] ) && absint( $args['achievement_id'] ) != $achievement->ID )
					unset($achievements[$key]);

				// Drop any achievements that don't match our achievement type
				if ( ! empty( $args['achievement_type'] ) && ( $args['achievement_type'] != $achievement->post_type && ( !is_array( $args['achievement_type'] ) || !in_array( $achievement->post_type, $args['achievement_type'] ) ) ) )
					unset($achievements[$key]);

				//unset hidden achievements
				$hidden = badgeos_get_hidden_achievement_by_id( $achievement->ID );
				if( !empty( $hidden ) && isset($args['display']))
					unset($achievements[$key]);

			}
		}

		// Return our $achievements array_values (so our array keys start back at 0), or an empty array
		return ( is_array( $achievements ) ? array_values( $achievements ) : array());

	}
}

/**
 * Updates the user's earned achievements
 *
 * We can either replace the achievement's array, or append new achievements to it.
 *
 * @since  1.0.0
 * @param  array        $args An array containing all our relevant arguments
 * @return integer|bool       The updated umeta ID on success, false on failure
 */
function badgeos_update_user_achievements( $args = array() ) {
	global $wpdb;
	// Setup our default args
	$defaults = array(
		'user_id'          => 0,     // The given user's ID
		'site_id'          => get_current_blog_id(), // The given site's ID
		'all_achievements' => false, // An array of ALL achievements earned by the user
		'new_achievements' => false, // An array of NEW achievements earned by the user
	);
	$args = wp_parse_args( $args, $defaults );

	// Use current user's ID if none specified
	if ( ! $args['user_id'] )
		$args['user_id'] = wp_get_current_user()->ID;

	// Grab our user's achievements
	$achievements = badgeos_get_user_achievements( array( 'user_id' => absint( $args['user_id'] ), 'site_id' => 'all' ) );

	// If we don't already have an array stored for this site, create a fresh one
	if ( !isset( $achievements[$args['site_id']] ) )
		$achievements[$args['site_id']] = array();

	// Determine if we should be replacing or appending to our achievements array
	if ( is_array( $args['all_achievements'] ) )
		$achievements[$args['site_id']] = $args['all_achievements'];
	elseif ( is_array( $args['new_achievements'] ) && ! empty( $args['new_achievements'] ) ) {
		if( ! is_array( $achievements[$args['site_id']] ) ) {
			$achievements[$args['site_id']] = array();
		}
		$achievements[$args['site_id']] = array_merge( $achievements[$args['site_id']], $args['new_achievements'] );
	}

	$new_achievements = $args['new_achievements'];
	if( $new_achievements !== false ) {
		$new_achievement = $new_achievements[0];
		$rec_type = $new_achievement->rec_type;
		$rec_type = apply_filters( 'badgeos_achievements_record_type', $rec_type, $new_achievement->ID, absint( $args['user_id'] ) );
		
		$table_name = $wpdb->prefix . 'badgeos_achievements';
		if($wpdb->get_var("show tables like '$table_name'") == $table_name) {
			$wpdb->insert( $table_name, array(
				'ID'        			=> $new_achievement->ID,
				'post_type'      		=> $new_achievement->post_type,
				'achievement_title'     => get_the_title( $new_achievement->ID ),
				'points'             	=> $new_achievement->points,
				'point_type'			=> $new_achievement->point_type,
				'this_trigger'         	=> $new_achievement->trigger,
				'user_id'               => absint( $args['user_id'] ),
				'site_id'               => $args['site_id'],
				'image'          		=> $new_achievement->image,
				'rec_type'          	=> $rec_type,
				'actual_date_earned'    => badgeos_default_datetime( 'mysql_achievements' ),
                'date_earned'           => current_time( 'mysql' )
			));
			
			do_action( 'badgeos_achievements_new_added', $rec_type, $new_achievement->ID, absint( $args['user_id'] ), $wpdb->insert_id );
			
			return $wpdb->insert_id;
		}	else {
			badgeos_utilities::update_user_meta( absint( $args['user_id'] ), '_badgeos_achievements', $achievements );
			
			do_action( 'badgeos_achievements_new_added', $rec_type, $new_achievement->ID, absint( $args['user_id'] ), 0 );

			return 0;
		}
	} else {
		
		// Finally, update our user meta
		$is_saved = badgeos_utilities::update_user_meta( absint( $args['user_id'] ), '_badgeos_achievements', $achievements);
        do_action( 'badgeos_achievements_new_added', '', 0, absint( $args['user_id'] ), 0 );
		return $is_saved;
	}
}

/**
 * Display achievements for a user on their profile screen
 *
 * @param null $user
 */
function badgeos_user_points_section( $user = null ) {
    $can_manage = current_user_can( badgeos_get_manager_capability() );
	wp_enqueue_style( 'badgeos-admin-styles' );
	/**
     * BadgeOS User Points
     */
    ?>
    <hr />
    <h2><?php _e( 'Earned Points', 'badgeos' ); ?></h2>
    <div class="earned-user-credits-wrapper">
        <?php
        $credit_types = badgeos_get_point_types();
        if ( is_array( $credit_types ) && ! empty( $credit_types ) ) {
            foreach ( $credit_types as $credit_type ) {
                $earned_credits = badgeos_get_points_by_type( $credit_type->ID, $user->ID );
				$post_type_plural = badgeos_points_type_display_title( $credit_type->ID );
				$badge_image = badgeos_get_point_image( $credit_type->ID, 50, 50 );
				$badge_image = apply_filters( 'badgeos_profile_points_image', $badge_image, 'backend-profile' , $credit_type  );
                ?>
                <div class="badgeos-credits"> 
					<table>
						<tr>
							<td valign="top" width="15%"><?php echo $badge_image; ?></td>
							<td valign="top" width="85%">
								<h3><?php echo $post_type_plural; ?></h3>
								<?php if( $can_manage ) {
									?>
										<span class="badgeos-credit-edit"><?php echo _e( 'Edit', 'badgeos' ); ?></span>
									<?php
								}
								?>

								<div class="badgeos-earned-credit"><?php echo __( 'Total: ' ) .'<span id="badgeos-'.$credit_type->ID.'-credit-profile-label">'.$earned_credits; ?></span></div>

								<?php if( $can_manage ) {
									?>
										<div class="badgeos-edit-credit-wrapper" style="display:none">
											<?php echo $post_type_plural; ?>
											<input type="number" class="badgeos-edit-credit" id="badgeos-<?php echo $credit_type->ID; ?>-credit" name="badgeos-<?php echo $credit_type->ID; ?>-credit" value="<?php echo (int) $earned_credits; ?>" />
											<input type="button" data-user_id="<?php echo $user->ID?>" data-field_id="badgeos-<?php echo $credit_type->ID; ?>-credit" data-admin_ajax="<?php echo admin_url( 'admin-ajax.php' ); ?>" data-points_id="<?php echo $credit_type->ID; ?>" class="badgeos-profile-points-update-button button button-primary" value="<?php echo _e( 'Update', 'badgeos' ); ?>" />
											<input type="button" data-field_id="badgeos-<?php echo $credit_type->ID; ?>-credit" class="badgeos-profile-points-cancel-button button button-primary" value="<?php echo _e( 'Cancel', 'badgeos' ); ?>" />
										</div>
									<?php
								} ?>
							</td>
						</tr>
					</table>
            	</div>
                <?php
            }
        }
        ?>
	</div>
	<div style="clear:both">&nbsp;</div>
    <?php
}
add_action( 'show_user_profile', 'badgeos_user_points_section' );
add_action( 'edit_user_profile', 'badgeos_user_points_section' );

/**
 * Save profile points
 *
 * @return none
 */
function badgeos_user_profile_update_points_callback() {

    $points_id = sanitize_text_field($_POST['points_id'] );
    $points = sanitize_text_field($_POST['points'] );
    $user_id = sanitize_text_field($_POST['user_id'] );

    $array_output = [];
    $array_output[ 'new_points' ] 	= $points;
    $array_output[ 'points_id' ] 	= $points_id;
    $array_output[ 'user_id' ] 		= $user_id;

    if ( ! current_user_can( 'edit_user', get_current_user_id() ) ) {
        $array_output[ 'message' ] 		= __( 'Permission denied!', 'badgeos' );
        wp_send_json_error( $array_output );
    }

    $old_points = badgeos_get_points_by_type( $points_id, $user_id );
    $array_output[ 'old_points' ] 	= $old_points;

    $updated_credit = $points;
    if( (int) $points != $old_points ) {
        if( (int) $points >= 0 ) {
            if( (int) $points == 0 ) {
                badgeos_revoke_credit( $points_id, $user_id, 'Deduct', $old_points, 'user_profile_credit_update', $user_id, '', '' );
                $array_output[ 'action' ] = 'Deduct';
            } elseif( (int) $points > $old_points ) {
                $add_credit_amount = (int) $points - $old_points;
                badgeos_award_credit( $points_id, $user_id, 'Award', $add_credit_amount, 'user_profile_credit_update', $user_id, '', '' );
                $array_output[ 'action' ] = 'Award';
            } else {
                $deduct_credit_amount = $old_points - (int) $points;
                badgeos_revoke_credit( $points_id, $user_id, 'Deduct', $deduct_credit_amount, 'user_profile_credit_update', $user_id, '', '' );
                $array_output[ 'action' ] = 'Deduct';
            }
        } else {
            $array_output[ 'action' ] = 'None';
            $array_output[ 'message' ] = __( 'Updated points should be greater than zero!', 'badgeos' );
            wp_send_json_error( $array_output );
        }
    } else {
        $array_output[ 'action' ] = 'None';
        $array_output[ 'message' ] = __( 'You can not update the same value!', 'badgeos' );
        wp_send_json_error( $array_output );
    }

    wp_send_json_success( $array_output );
}
add_action( 'wp_ajax_badgeos_user_profile_update_points', 'badgeos_user_profile_update_points_callback' );

/**
 * Display achievements for a user on their profile screen
 *
 * @since  1.0.0
 * @param  object $user The current user's $user object
 * @return void
 */
function badgeos_user_profile_data( $user = null ) {

	$achievement_ids = array();

    $badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
    echo '<h2>' . __( 'BadgeOS Email Notifications', 'badgeos' ) . '</h2>';
    echo '<table class="form-table">';
    echo '<tr>';
        echo '<th scope="row">' . __( 'Email Preference', 'badgeos' ) . '</th>';
        echo '<td>';
            echo '<label for="_badgeos_can_notify_user"><input type="checkbox" name="_badgeos_can_notify_user" id="_badgeos_can_notify_user" value="1" ' . checked( badgeos_can_notify_user( $user->ID ), true, false ) . '/>' . __( 'Enable BadgeOS Email Notifications', 'badgeos' ) . '</label>';
        echo '</td>';
    echo '</tr>';
    echo '</table>';

    echo '<input id="badgeos_user_id" name="badgeos_user_id" value="'.$user->ID.'" type="hidden" />';

    $achievements = badgeos_get_user_achievements( array( 'user_id' => absint( $user->ID ) ) );

    // List all of a user's earned achievements
    echo '<h2>' . __( 'Earned Achievements', 'badgeos' ) . '</h2>';

    echo '<table class="form-table">';

    echo '<tr><td colspan="2">';

    echo '<table class="widefat badgeos-table">';
    echo '<thead><tr>';
    if ( current_user_can( badgeos_get_manager_capability() ) ) {
        echo '<th width="5%"><input type="checkbox" id="badgeos_ach_check_all" name="badgeos_ach_check_all" /></th>';
    }
    echo '<th>'. __( 'Image', 'badgeos' ) .'</th>';
    echo '<th>'. __( 'Name', 'badgeos' ) .'</th>';
    echo '<th>'. __( 'Points', 'badgeos' ) .'</th>';
    do_action( 'badgeos_profile_achivement_add_column_heading' );
    echo '</tr></thead>';

    $ach_index = 0;
    $achievement_exists = false;

    foreach ( $achievements as $achievement ) {

        if( $achievement->post_type != trim( $badgeos_settings['achievement_step_post_type'] ) ) {
            $achievement_exists = true;

            echo '<tr>';
            if ( current_user_can( badgeos_get_manager_capability() ) ) {
                $ent_id = 0;
                if( isset( $achievement->entry_id ) && intval( $achievement->entry_id ) ) {
                    $ent_id = $achievement->entry_id;
                }
                echo '<td width="5%" style="text-align:center;"><input type="checkbox" id="badgeos_ach_check_indi_'.$achievement->ID.'" value="'.$achievement->ID.'_'.$ach_index.'_'.$ent_id.'" name="badgeos_ach_check_indis[]" /></td>';
            }
            $badge_image = badgeos_get_achievement_post_thumbnail( $achievement->ID, array( 50, 50 ) );
            $badge_image = apply_filters( 'badgeos_profile_achivement_image', $badge_image, $achievement, array( 50, 50 )  );

            echo '<td width="20%">'.$badge_image.'</td>';
            echo '<td width="55%">';
            $achievement_title = get_the_title( $achievement->ID );
            if( empty( $achievement_title ) ) {
                if ( current_user_can( badgeos_get_manager_capability() ) ) {
                    echo '<a class="post-edit-link" href="'.get_permalink($achievement->ID).'">'. esc_html( $achievement->achievement_title ) .'</a>';
                } else {
                    echo '<a class="post-edit-link" href="'.get_permalink($achievement->ID).'">'. esc_html( $achievement->achievement_title ) .'</a>';
                }
            } else {
                if ( current_user_can( badgeos_get_manager_capability() ) ) {
                    $achievement_title = edit_post_link( $achievement_title, '', '', $achievement->ID );
                } else {
                    echo '<a class="post-edit-link" href="'.get_permalink($achievement->ID).'">'. esc_html( $achievement->achievement_title ) .'</a>';
                }
            }
            echo '</td>';
            $point_type = '';

            $post_id = 0;
            if( property_exists ( $achievement, 'point_type' ) ) {
                $post_id = intval( $achievement->point_type );
            } else if( property_exists ( $achievement, 'points_type' ) ) {
                $post_id = intval( $achievement->points_type );
			}
			
			$point_type = badgeos_points_type_display_title( $post_id ); 
            $default_point_type 	= ( ! empty ( $badgeos_settings['default_point_type'] ) ) ? $badgeos_settings['default_point_type'] : '';
            if( intval( $post_id ) == 0 ) {
				$point_type = badgeos_points_type_display_title( $default_point_type );
			}
			
			if( empty( $point_type ) ) {
				$point_type = __( 'Points', 'badgeos' );
			}

            echo '<td width="20%">'.intval( $achievement->points ).' '.$point_type.'</td>';

            do_action( 'badgeos_profile_achivement_add_column_data', $achievement );
            echo '</tr>';
            $ach_index += 1;
            $achievement_ids[] = $achievement->ID;
        }
    }

    if( $achievement_exists ) {
        if ( current_user_can( badgeos_get_manager_capability() ) ) {
            echo '<tr>';
            echo '<td colspan="5" class="bulk-delete-detail">'.__( 'Select achievement(s) before clicking on revoke selected button', 'badgeos' ).'</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td colspan="5" class="bulk-delete-detail"><button type="button" id="badgeos_btn_revoke_bulk_achievements" class="button button-primary">'.__( 'Revoke Selected', 'badgeos' ).'</button><img id="revoke-badges-loader" src="'. admin_url( '/images/spinner-2x.gif' ) .'" /></td>';
            echo '</tr>';
        }
    } else {
        echo '<tr>';
        echo '<td>'. __( 'No Achievement Found.', 'badgeos' ) .'</td>';
        echo '</tr>';
    }

    echo '</table>';

    echo '</td></tr>';
    echo '</table>';

    // If debug mode is on, output our achievements array
    if ( badgeos_is_debug_mode() ) {

        echo __( 'DEBUG MODE ENABLED', 'badgeos' ) . '<br />';
        echo __( 'Metadata value for:', 'badgeos' ) . ' _badgeos_achievements<br />';

        var_dump ( $achievements );

    }

    echo '<br/>';

    // Output markup for awarding achievement for user
    if ( current_user_can( badgeos_get_manager_capability() ) ) {
        badgeos_profile_award_achievement( $user, $achievement_ids );
    }

}
add_action( 'show_user_profile', 'badgeos_user_profile_data' );
add_action( 'edit_user_profile', 'badgeos_user_profile_data' );

/**
 * revokes achievements
 *
 * @since  1.0.0
 * @param  int  $user_id      User ID being saved
 * @return none
 */
function delete_badgeos_bulk_achievements_records( ){

	global $wpdb;

    $badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
    $user_recs = $_POST['achievements'];
    $user_id = $_POST['user_id'];
    if( is_array( $user_recs ) && count( $user_recs ) > 0 ) {
		$achievements = array();
		$entries = array();
        $indexes = array();
        foreach( $user_recs as $rec ) {
            $params = explode("_", $rec);
            $achievements[] = $params[0];
			$indexes[] = $params[1];
			$entries[] = $params[2];
		}
		
        $my_achievements = badgeos_get_user_achievements( array( 'user_id' => $user_id ) );

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
        echo 'success';
    } else {
        echo __( 'Please, select some achievement(s) to delete', 'badgeos' );
    }
    exit;
}
add_action( 'wp_ajax_delete_badgeos_bulk_achievements', 'delete_badgeos_bulk_achievements_records' );

/**
 * Save extra user meta fields to the Edit Profile screen
 *
 * @since  1.0.0
 * @param  int  $user_id      User ID being saved
 * @return mixed			  false if current user can not edit users, void if can
 */
function badgeos_save_user_profile_fields( $user_id = 0 ) {

	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return false;
	}

	/**
     * Update Rank Type Filter Field
     */
    $rank_type_filter = ( isset( $_POST['badgeos_ranks_filter'] ) ? $_POST['badgeos_ranks_filter'] : 'all' );
    badgeos_utilities::update_user_meta( $user_id, '_badgeos_ranks_filter', $rank_type_filter );

    /**
     * Update Achievement Type Filter Field
     */
    $rank_type_filter = ( isset( $_POST['badgeos_achievement_filter'] ) ? $_POST['badgeos_achievement_filter'] : 'all' );
	badgeos_utilities::update_user_meta( $user_id, '_badgeos_achievement_filter', $rank_type_filter );

}
add_action( 'personal_options_update', 'badgeos_save_user_profile_fields' );
add_action( 'edit_user_profile_update', 'badgeos_save_user_profile_fields' );

/**
 * Generate markup to show user rank
 *
 * @param null $user
 */
function badgeos_profile_user_ranks( $user = null ) {

	$rank_types = badgeos_get_rank_types_slugs_detailed();
	$can_manage = current_user_can( badgeos_get_manager_capability() );
	$selected = badgeos_utilities::get_user_meta( $user->ID, '_badgeos_ranks_filter', true );
	if( empty( $selected ) ) {
        $selected = 'all';
    }
	?>
	<hr />
	<div class="badgeos-ranks-wrapper">
		<h2><?php _e( 'BadgeOS Ranks', 'badgeos' ); ?></h2>
		<div class="filter-wrapper">
			<label for="badgeos_ranks_filter"><?php _e( 'Filter By Rank Type: ', 'badgeos' ); ?></label>
			<select name="badgeos_ranks_filter" id="badgeos_ranks_filter">
				<?php
				if ( is_array( $rank_types ) && ! empty( $rank_types ) ) {
					?>
					<option value="all"><?php _e( 'All', 'badgeos' ); ?></option>
					<?php
					foreach ( $rank_types as $key => $rank_type ) {
						$rank_type = ucwords( str_replace( '-', ' ', $key ) );
						?>
						<option value="<?php echo $key; ?>" <?php selected( $selected, $key ); ?>><?php echo $rank_type; ?></option>
						<?php
					}
				}
				?>
			</select>
		</div>
	</div>
	<table class="form-table badgeos-rank-table badgeos-rank-revoke-table">
		<thead>
			<tr>
				<th><?php _e( 'Image', 'badgeos' ); ?></th>
				<th><?php _e( 'Name', 'badgeos' ); ?></th>
				<th><?php _e( 'Rank Type', 'badgeos' ); ?></th>
				<th><?php _e( 'Points', 'badgeos' ); ?></th>
				<th align="center" style="text-align:center !important;"><?php _e( 'Last Awarded', 'badgeos' ); ?></th>
				<?php
				if( $can_manage ) {
					?>
					<th><?php _e( 'Action', 'badgeos' ); ?></th>
					<?php
				}
				?>
				<?php do_action( 'badgeos_profile_rank_add_column_heading' ); ?>
			</tr>
		</thead>
		<tbody>
			<?php
			if( empty( $rank_types ) && $can_manage ) {
				?>
				<tr>
					<td colspan="6">
						<span class="description">
							<?php echo sprintf( __( 'No rank types configured, visit %s to configure some rank types.', 'badgeos' ), '<a href="' . admin_url( 'edit.php?post_type=rank-type' ) . '">' . __( 'this page', 'badgeos' ) . '</a>' ); ?>
						</span>
					</td>
				</tr>
				<?php
			} else {
				$user_ranks = badgeos_get_user_ranks( array(
					'user_id' => absint( $user->ID ),
					'rank_type' => $selected,
				) );

				if( $user_ranks ) {
					foreach( $user_ranks as $rank ) {
						
						$point_type = badgeos_points_type_display_title( $rank->credit_id ); 
						$default_point_type 	= ( ! empty ( $badgeos_settings['default_point_type'] ) ) ? $badgeos_settings['default_point_type'] : '';
						if( intval( $rank->rank_id ) == 0 ) {
							$point_type = badgeos_points_type_display_title( $default_point_type );
						}
						
						if( empty( $point_type ) ) {
							$point_type = __( 'Points', 'badgeos' );
						}

						?>
						<tr class="<?php echo $rank->rank_type; ?> ">
							<?php
							$ranks_image = badgeos_get_rank_image( $rank->rank_id, 50, 50 );
							?>
							<td><?php echo $ranks_image; ?></td>
							<td><?php echo $rank->rank_title; ?></td>
							<td><?php echo $rank->rank_type; ?></td>
							<td><?php echo $rank->credit_amount.' '.$point_type; ?></td>
							<?php
							$last_awarded = badgeos_utilities::get_user_meta( $user->ID, '_badgeos_'.$rank->rank_type.'_rank', true );
							?>
							<td class="last-awarded" align="center">
								<?php
								if( !empty( $last_awarded ) && $last_awarded == $rank->id ) {
									?><span class="profile_ranks_last_award_field">&#10003;</span><?php
								}
								?>
							</td>
							<?php
							if ( $can_manage ) {
								?>
								<td>
									<?php
									$rank_post = badgeos_utilities::badgeos_get_post( $rank->rank_id );
									if( $rank->priority > 0 ) {
										$prev_rank_id = ( !is_null( badgeos_get_prev_rank_id( absint( $rank->rank_id ) ) ) ? badgeos_get_prev_rank_id( absint( $rank->rank_id ) ) : '' );
										?>
										<span data-rank_id="<?php echo $rank->rank_id; ?>"
											data-user_id="<?php echo $user->ID; ?>"
											data-previous_rank_id="<?php echo $prev_rank_id; ?>"
											data-admin_ajax="<?php echo admin_url( 'admin-ajax.php' ); ?>"
											class="revoke-rank">
														<?php _e( 'Revoke Rank', 'badgeos' ); ?>
													</span>
													<span class="spinner-loader" >
														<img class="revoke-rank-spinner" src="<?php echo admin_url( '/images/wpspin_light.gif' ); ?>" style="margin-left: 10px; display: none;" />
													</span>

										<?php
									} else {
										?>
										<span class="default-rank"><?php _e( 'Default Rank', 'badgeos' ); ?></span>
										<?php
									}
									?>
								</td>
								<?php
							}
							?>
							<?php do_action( 'badgeos_profile_rank_add_column_data', $rank ); ?>
						</tr>
						<?php
					}
				} else {
					$colpan = ( $can_manage ) ? 7 : 6;
					?>
					<tr class="no-awarded-rank">
						<td colspan="<?php echo $colpan; ?>">
						<span class="description">
							<?php _e( 'No Awarded Ranks', 'badgeos' ); ?>
						</span>
						</td>
					</tr>
					<?php
				}
			}
			?>
		</tbody>
		<tfoot>
		<tr>
			<th><?php _e( 'Image', 'badgeos' ); ?></th>
			<th><?php _e( 'Name', 'badgeos' ); ?></th>
			<th><?php _e( 'Rank Type', 'badgeos' ); ?></th>
			<th><?php _e( 'Points', 'badgeos' ); ?></th>
			<th align="center" style="text-align:center !important;"><?php _e( 'Last Awarded', 'badgeos' ); ?></th>
			<?php if( $can_manage ) { ?>
				<th><?php _e( 'Action', 'badgeos' ); ?></th>
			<?php } ?>
			<?php do_action( 'badgeos_profile_rank_add_column_heading' ); ?>
		</tr>
		</tfoot>
	</table>

	<?php
	if( $can_manage ) {
		$selected_type = '';
		if( 'all' != $selected ) {
			$selected_type = ucwords( str_replace( '-', ' ', $selected ) );
		}
		?>
		<div class="user-profile-award-ranks">
			<h2>
				<?php _e('Award Ranks:', 'badgeos'); ?> &nbsp;&nbsp;&nbsp;&nbsp;
				<span data-rank-filter="<?php echo $selected; ?>" data-user-id="<?php echo $user->ID; ?>" data-admin_ajax="<?php echo admin_url( 'admin-ajax.php' ); ?>"
						class="display-ranks-to-award button button-primary">
					<?php _e( 'Display '. $selected_type .' Ranks to Award', 'badgeos' ); ?>
				</span>
			</h2>

			<img class="revoke-rank-spinner" src="<?php echo admin_url( '/images/wpspin_light.gif' ); ?>" style="margin-left: 10px; display: none;" />
		</div>
		<?php
	}
}
add_action( 'show_user_profile', 'badgeos_profile_user_ranks' );
add_action( 'edit_user_profile', 'badgeos_profile_user_ranks' );


/**
 * Generate markup for awarding an achievement to a user
 *
 * @since  1.0.0
 * @param  object $user         The current user's $user object
 * @param  array  $achievements array of user-earned achievement IDs
 * @return string               concatenated markup
 */
function badgeos_profile_award_achievement( $user = null, $achievement_ids = array() ) {

	// Grab our achivement types
	$achievement_types = badgeos_get_achievement_types();
    $badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
	?>

	<h2><?php _e( 'Award an Achievement', 'badgeos' ); ?></h2>

	<table class="form-table">
		<tr>
			<th><label for="thechoices"><?php _e( 'Select an Achievement Type to Award:', 'badgeos' ); ?></label></th>
			<td>
				<select id="thechoices">
				<option></option>
				<?php
				foreach ( $achievement_types as $achievement_slug => $achievement_type ) {
                    if( $achievement_slug != trim( $badgeos_settings['achievement_step_post_type'] ) ) {
                        echo '<option value="'. $achievement_slug .'">' . ucwords( $achievement_type['single_name'] ) .'</option>';
                    }
                }
				?>
				</select>
			</td>
		</tr>
		<tr><td id="boxes" colspan="2">
			<?php foreach ( $achievement_types as $achievement_slug => $achievement_type ) : ?>
                <?php if( $achievement_slug != trim( $badgeos_settings['achievement_step_post_type'] ) ) { ?>
				<table id="<?php echo esc_attr( $achievement_slug ); ?>" class="widefat badgeos-table">
                    <thead>
                    <tr>
                        <th><?php _e( 'Image', 'badgeos' ); ?></th>
                        <th><?php echo ucwords( $achievement_type['single_name'] ); ?></th>
                        <th><?php _e( 'Action', 'badgeos' ); ?></th>
                    </tr>
                    </thead>

                    <tbody>
					<?php
					// Load achievement type entries
					$the_query = new WP_Query( array(
						'post_type'      => $achievement_slug,
						'posts_per_page' => '999',
						'post_status'    => 'publish'
					) );

					if ( $the_query->have_posts() ) : ?>

						<?php while ( $the_query->have_posts() ) : $the_query->the_post();

							// Setup our award URL
							$award_url = add_query_arg( array(
								'action'         => 'award',
								'achievement_id' => absint( get_the_ID() ),
								'user_id'        => absint( $user->ID )
							) );
							?>
							<tr>
								<td><?php the_post_thumbnail( array( 50, 50 ) ); ?></td>
								<td>
									<?php echo edit_post_link( get_the_title() ); ?>
								</td>
								<td>
									<a href="<?php echo esc_url( wp_nonce_url( $award_url, 'badgeos_award_achievement' ) ); ?>"><?php printf( __( 'Award %s', 'badgeos' ), ucwords( $achievement_type['single_name'] ) ); ?></a>
								</td>
							</tr>
						<?php endwhile; ?>

					<?php else : ?>
						<tr>
							<th><?php printf( __( 'No %s found.', 'badgeos' ), $achievement_type['plural_name'] ); ?></th>
						</tr>
					<?php endif; wp_reset_postdata(); ?>

					</tbody>
					</table><!-- #<?php echo esc_attr( $achievement_slug ); ?> -->
                <?php } ?>
			<?php endforeach; ?>
		</td><!-- #boxes --></tr>
	</table>

	<script type="text/javascript">
		jQuery(document).ready(function($){
			<?php foreach ( $achievement_types as $achievement_slug => $achievement_type ) { ?>
				$('#<?php echo $achievement_slug; ?>').hide();
			<?php } ?>
			$("#thechoices").change(function(){
				if ( 'all' == this.value )
					$("#boxes").children().show();
				else
					$("#" + this.value).show().siblings().hide();
			}).change();
		});
	</script>
	<?php
}

/**
 * Process the adding/revoking of achievements on the user profile page
 *
 * @since  1.0.0
 * @return void
 */
function badgeos_process_user_data() {

	//verify uesr meets minimum role to view earned badges
	if ( current_user_can( badgeos_get_manager_capability() ) ) {

		// Process awarding achievement to user
		if ( isset( $_GET['action'] ) && 'award' == $_GET['action'] &&  isset( $_GET['user_id'] ) && isset( $_GET['achievement_id'] ) ) {

			// Verify our nonce
			check_admin_referer( 'badgeos_award_achievement' );

			// Award the achievement
			badgeos_award_achievement_to_user( absint( $_GET['achievement_id'] ), absint( $_GET['user_id'] ) );

			// Redirect back to the user editor
			wp_redirect( add_query_arg( 'user_id', absint( $_GET['user_id'] ), admin_url( 'user-edit.php' ) ) );
			exit();
		}

		// Process revoking achievement from a user
		if ( isset( $_GET['action'] ) && 'revoke' == $_GET['action'] && isset( $_GET['user_id'] ) && isset( $_GET['achievement_id'] ) ) {

			// Verify our nonce
			check_admin_referer( 'badgeos_revoke_achievement' );

			// Revoke the achievement
			badgeos_revoke_achievement_from_user( absint( $_GET['achievement_id'] ), absint( $_GET['user_id'] ) );

			// Redirect back to the user editor
			wp_redirect( add_query_arg( 'user_id', absint( $_GET['user_id'] ), admin_url( 'user-edit.php' ) ) );
			exit();

		}

	}

}
add_action( 'init', 'badgeos_process_user_data' );

/**
 * Returns array of achievement types a user has earned across a multisite network
 *
 * @since  1.2.0
 * @param  integer $user_id  The user's ID
 * @return array             An array of post types
 */
function badgeos_get_network_achievement_types_for_user( $user_id ) {
	global $blog_id;

	// Store a copy of the original ID for later
	$cached_id = $blog_id;

	// Assume we have no achievement types
	$all_achievement_types = array();

	// Loop through all active sites
	$sites = badgeos_get_network_site_ids();
	foreach( $sites as $site_blog_id ) {

		// Merge earned achievements to our achievement type array
		$achievement_types = badgeos_get_user_earned_achievement_types( $user_id );
		if ( is_array($achievement_types) ) {
			$all_achievement_types = array_merge($achievement_types,$all_achievement_types);
		}
	}

	// Pare down achievement type list so we return no duplicates
	$achievement_types = array_unique( $all_achievement_types );

	// Return all found achievements
	return $achievement_types;
}

/**
 * Check if a user has disabled email notifications.
 *
 * @since  1.4.0
 *
 * @param  int   $user_id User ID.
 * @return bool           True if user can be emailed, otherwise false.
 */
function badgeos_can_notify_user( $user_id = 0 ) {
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

    return badgeos_utilities::get_user_meta( $user_id, '_badgeos_can_notify_user', true )? true:false;
}

/**
 * Update the meta information.
 *
 * @since  3.5
 *
 * @param  int
 * @return none
 */
function badgeos_update_can_notify_user_field( $user_id ){

    badgeos_utilities::update_user_meta( $user_id, '_badgeos_can_notify_user', 1 );
}
add_action( 'user_register', 'badgeos_update_can_notify_user_field', 10, 1 );

/**
 * Update the meta information.
 *
 * @since  3.5
 *
 * @param  int
 * @return none
 */
function badgeos_save_email_notify_field( $user_id ) {

    if ( !current_user_can( 'edit_user', $user_id ) ) {
        return false;
    }

	badgeos_utilities::update_user_meta( $user_id, '_badgeos_can_notify_user', sanitize_text_field($_POST['_badgeos_can_notify_user'] ) );
	badgeos_utilities::update_user_meta( $user_id, '_badgeos_date_of_birth', sanitize_text_field($_POST['badgeos_date_of_birth'] ) );
}
add_action( 'personal_options_update', 'badgeos_save_email_notify_field' );
add_action( 'edit_user_profile_update', 'badgeos_save_email_notify_field' );
add_action( 'user_register', 'badgeos_register_save_email_notify_field', 10, 1 );

function badgeos_register_save_email_notify_field( $user_id ) {
	if (isset($_POST['badgeos_date_of_birth']) && !empty( $_POST['badgeos_date_of_birth'] ) ) {
		badgeos_utilities::update_user_meta( $user_id, '_badgeos_date_of_birth', sanitize_text_field($_POST['badgeos_date_of_birth'] ) );
	}
}

/**
 * Show custom user profile fields
 * 
 * @param  object $profileuser A WP_User object
 * @return void
 */
function badgeos_dob_profile_fields( $profileuser ) {
	
	if(is_string( $profileuser ) === true){
        $profileuser = new stdClass();//create a new
        $profileuser->ID = -9999;
    }
	
	$badgeos_settings = get_option( 'badgeos_settings' );
    $date_of_birth_from 	= ( ! empty ( $badgeos_settings['date_of_birth_from'] ) ) ? $badgeos_settings['date_of_birth_from'] : 'profile';
    
	if( ! class_exists( 'BuddyPress' ) || $date_of_birth_from == 'profile' ) {
		?>
			<table class="form-table">
				<tr>
					<th>
						<label for="user_location"><?php _e( 'Date Of Birth', 'badgeos' ); ?></label>
					</th>
					<td>
						<input type="text" readonly="readonly" name="badgeos_date_of_birth" id="badgeos_date_of_birth" value="<?php echo esc_attr( get_the_author_meta( '_badgeos_date_of_birth', $profileuser->ID ) ); ?>" class="regular-text" />
					</td>
				</tr>
			</table>
		<?php
	}

}
add_action( 'show_user_profile', 'badgeos_dob_profile_fields', 0, 1 );
add_action( 'edit_user_profile', 'badgeos_dob_profile_fields', 0, 1 );
add_action( 'user_new_form', 'badgeos_dob_profile_fields' );
add_action( 'network_user_new_form', 'badgeos_dob_profile_fields' );