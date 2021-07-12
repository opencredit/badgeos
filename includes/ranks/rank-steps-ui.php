<?php
/**
 * Ranks Steps UI
 *
 * @package Badgeos
 * @subpackage Ranks
 * @author LearningTimes
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com/
 */

/**
 * Add our Steps JS to the badgeos post editor
 *
 * @return void      rank_requirement
 */
function badgeos_rank_req_steps_ui_admin_scripts() {
	global $post_type;

    if ( in_array( trim( $post_type ) ,badgeos_get_rank_types_slugs( ) ) ) {
    	wp_enqueue_script( 'badgeos-rank-req-steps-ui', $GLOBALS['badgeos']->directory_url . 'js/rank-req-steps-ui.js', array( 'jquery-ui-sortable' ), Badgeos::$version );
    }
}
add_action( 'admin_print_scripts-post-new.php', 'badgeos_rank_req_steps_ui_admin_scripts', 11 );
add_action( 'admin_print_scripts-post.php', 'badgeos_rank_req_steps_ui_admin_scripts', 11 );

/**
 * Adds our Steps metabox to the Achievement post editor
 *
 * @return void
 */
function badgeos_add_ranks_award_steps_ui_meta_box() {
	$rank_types = badgeos_get_rank_types_slugs();
	if( count( $rank_types ) ) {
		add_meta_box( 'badgeos_rank_req_steps_ui', apply_filters( 'badgeos_rank_req_steps_ui_title', __( 'Rank Requirement', 'badgeos' ) ), 'badgeos_rank_req_steps_ui_meta_box', $rank_types, 'advanced', 'high' );
	}
}
add_action( 'add_meta_boxes', 'badgeos_add_ranks_award_steps_ui_meta_box' );

/**
 * Renders the HTML for meta box, refreshes whenever a new step is added
 *
 * @param  object $post The current post object
 * @return void 
 */
function badgeos_rank_req_steps_ui_meta_box( $post  = null) {

    global $wpdb;
	$settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();

	/**
     * If is lowest priority rank then show a notice and prevent to show requirements UI
     */
	if( $post->post_type == trim( $settings['ranks_step_post_type'] ) || in_array( $post->post_type, badgeos_get_rank_types_slugs() ) ) {
		if( ( badgeos_get_rank_priority( $post->ID ) < 1 )  ) {
			 
			echo '<p>' .
				 __( 'The rank with the lowest priority is set as default rank for all users so this rank should be created without requirements.', 'badgeos' )
				 . '<br>'
				 . __( 'You will be able to set requirements on next ranks. After save this rank, if it is not configured as the lowest priority you will be able to edit the requirements again.', 'badgeos' )
				 . '</p>';
			return;
		}
	}

	$required_steps = $wpdb->get_results( $wpdb->prepare( "SELECT p.*, pp.* FROM $wpdb->p2p as pp inner join $wpdb->posts as p on(pp.p2p_from = p.ID) WHERE pp.p2p_to = %d and p.post_type = %s", $post->ID, trim( $settings['ranks_step_post_type'] )  ) );

	/**
     * Loop through each step and set the sort order
     */
	foreach ( $required_steps as $required_step ) {
		$required_step->order = get_ranks_step_menu_order( $required_step->ID );
	}

	/**
     * Sort the steps by their order
     */
	uasort( $required_steps, 'badgeos_compare_rank_step_order');

	echo '<p>' .__( 'Define the required "steps" for this rank to be considered complete. Use the "Label" field to optionally customize the titles of each step.', 'badgeos' ). '</p>';

	/**
     * Concatenate our step output
     */
	echo '<ul id="ranks_steps_list">';
	foreach ( $required_steps as $step ) {
		badgeos_rank_req_steps_ui_html( $step->ID, $post->ID );
	}
	echo '</ul>';

	/**
     * Render our buttons
     */
	echo '<input style="margin-right: 1em" class="button" type="button" onclick="badgeos_add_new_rank_req_step(' . $post->ID . ');" value="' . apply_filters( 'badgeos_rank_req_steps_ui_add_new', __( 'Add New Step', 'badgeos' ) ) . '">';
	echo '<input class="button-primary" type="button" onclick="badgeos_update_rank_steps();" value="' . apply_filters( 'badgeos_rank_req_steps_ui_save_all', __( 'Save All Steps', 'badgeos' ) ) . '">';
	echo '<img class="save-steps-spinner save-ranks-steps-spinner" src="' . admin_url( '/images/wpspin_light.gif' ) . '" style="margin-left: 10px; display: none;" />';

}

/**
 * Helper function for generating the HTML output for configuring a given step
 *
 * @param  integer $step_id The given step's ID
 * @param  integer $post_id The given step's parent $post ID
 * @return string  The concatenated HTML input for the step
 */
function badgeos_rank_req_steps_ui_html($step_id = 0, $post_id = 0 ) {

	/**
     * Grab our step's requirements and measurement
     */
	$requirements      = badgeos_get_rank_req_step_requirements( $step_id );
	$total_count             = !empty( $requirements['count'] ) ? $requirements['count'] : 1;
	$achievement_types = badgeos_get_rank_types_slugs();
	
    $dynamic_triggers = array();
    $badgeos_subtrigger_value 	= $requirements['badgeos_subtrigger_value'];
    $badgeos_subtrigger_id 		= $requirements['badgeos_subtrigger_id'];
    $badgeos_fields_data 		= $requirements['badgeos_fields_data'];

    ?> 

	<li class="step-row step-<?php echo $step_id; ?>" data-step-id="<?php echo $step_id; ?>">
		<div class="step-handle"></div>
		<a class="delete-step" href="javascript: badgeos_delete_rank_req_step( <?php echo $step_id; ?> );"><?php _e( 'Delete', 'badgeos' ); ?></a>
		<input type="hidden" name="post_id" value="<?php echo $post_id; ?>" />
		<input type="hidden" name="order" value="<?php echo get_ranks_step_menu_order( $step_id ); ?>" />

		<?php echo apply_filters( 'badgeos_rank_req_steps_ui_html_require_text', __( 'Require', 'badgeos' ), $step_id, $post_id ); ?>
		
		<?php do_action( 'badgeos_rank_req_steps_ui_html_after_require_text', $step_id, $post_id ); ?>

		<select class="select-trigger-type" data-step-id="<?php echo $step_id; ?>">
			<?php
				foreach (get_badgeos_ranks_req_activity_triggers() as $value => $label ) {
                    if( is_array( $label ) ) {
                        echo '<option value="' . esc_attr( $value ) . '" ' . selected( $requirements['trigger_type'], $value, false ) . '>' . esc_html( $label['label'] ) . '</option>';
                        $dynamic_triggers[ $value ] = $label;
                    } else {
                        echo '<option value="' . esc_attr( $value ) . '" ' . selected( $requirements['trigger_type'], $value, false ) . '>' . esc_html( $label ) . '</option>';
                    }
                }
			?>
		</select>
		
		<?php do_action( 'badgeos_rank_req_steps_ui_html_after_trigger_type', $step_id, $post_id ); ?>

        <?php if( count( $dynamic_triggers ) > 0 ) {

        foreach ( $dynamic_triggers as $key => $data ) {
        $fields_group = array();
        ?>
        <div id="badgeos_achievements_step_dynamic_section_<?php echo $key;?>" style="display:inline-block">
            <select id="badgeos_achievements_step_ddl_dynamic_<?php echo $key;?>" data-trigger="<?php echo $key;?>" class="badgeos_achievements_step_fields badgeos_achievements_step_ddl_dynamic" name="badgeos_achievements_step_ddl_dynamic_<?php echo $key;?>">
                <?php
                foreach ( $data['sub_triggers'] as $key2 => $data2 ) {
                    $sub_fields = array();
                    echo '<option value="'.$data2[ 'trigger' ].'" '.selected( $data2[ 'trigger' ], $badgeos_subtrigger_value, false ) . ' >'.$data2[ 'label' ].'</option>';
                    foreach ( $data2['fields'] as $fieldkey => $field ) {
                        $sub_fields[] = $field;
                    }

                    $fields_group[$data2['trigger']] = $sub_fields;
                }

                echo '</select>';

                foreach ( $fields_group as $fields_groupkey => $fields ) {
                    foreach ( $fields as $fieldkey => $field ) {
                        $sel_val = '';
                        if( isset( $badgeos_fields_data[$field['id']] ) ) {
                            $sel_val = $badgeos_fields_data[$field['id']];
                        }

                        switch( trim( $field['type'] ) ) {
                            case "select":
                                echo '<select id="'.$field['id'].'" class="badgeos_achievements_step_fields badgeos_achievements_step_subddl_dynamic badgeos_achievements_step_fields_'.$fields_groupkey.' badgeos_achievements_step_subddl_'.$fields_groupkey.'" name="'.$field['id'].'">';
                                foreach ( $field['options'] as $ddlkey => $ddlval ) {
                                    echo '<option value="'.$ddlkey.'" '.($ddlkey == $sel_val?'selected':'').'>'.$ddlval.'</option>';
                                }
                                echo '</select>';
                                break;
                            case "text":
                                echo '<input value="'.$sel_val.'" type="'.$field['type'].'" size="4" id="'.$field['id'].'"  class="badgeos_achievements_step_fields badgeos_achievements_step_subtxt_dynamic badgeos_achievements_step_fields_'.$fields_groupkey.' badgeos_achievements_step_subtxt_'.$fields_groupkey.'" name="'.$field['id'].'" />';
                                break;
                            case "number":
                                echo '<input value="'.$sel_val.'" type="'.$field['type'].'" size="4" step="1" min="0" id="'.$field['id'].'" class="badgeos_achievements_step_fields badgeos_achievements_step_subtxt_dynamic badgeos_achievements_step_fields_'.$fields_groupkey.' badgeos_achievements_step_subtxt_'.$fields_groupkey.'" name="'.$field['id'].'" />';
                                break;
                        }
                    }
                }
                echo '</div>';
                }
            } ?>
			<?php do_action( 'badgeos_rank_req_steps_ui_html_after_dynamic_trigger_type', $step_id, $post_id ); ?>

			<input type="text" size="5" placeholder="<?php _e( 'Post ID', 'badgeos' ); ?>" value="<?php esc_attr_e( $requirements['achievement_post'] ); ?>" class="select-achievement-post select-achievement-post-<?php echo $step_id; ?>">

		<?php do_action( 'badgeos_rank_req_steps_ui_html_after_achievement_post', $step_id, $post_id ); ?>
		
		<?php do_action( 'badgeos_steps_ui_html_before_ranks_visit_post', $step_id, $post_id ); ?>
		<select class="badgeos-select-visit-post badgeos-select-visit-post-<?php echo $step_id; ?>">
			<?php
				$defaults = array(
					'post_type'         => 'post',
					'numberposts'       => -1,
					'orderby'           => 'menu_order'
				);
				$posts = get_posts( $defaults );
				echo '<option value="" selected>'.__( 'Any Post', 'badgeos' ).'</option>';
				foreach ( $posts as $post ) {
					echo '<option value="' . $post->ID . '" ' . selected( $post->ID, $requirements['visit_post'], false ) . '>' . ucfirst( $post->post_title ).'</option>';
				}
			?>
		</select>
		<?php do_action( 'badgeos_steps_ui_html_after_ranks_visit_post', $step_id, $post_id ); ?>
		<select class="badgeos-select-visit-page badgeos-select-visit-page-<?php echo $step_id; ?>">
			<?php
				$pages = get_pages(); 
				echo '<option value="" selected>'.__( 'Any Page', 'badgeos' ).'</option>';
				foreach ( $pages as $page ) {
					echo '<option value="' . $page->ID . '" ' . selected( $page->ID, trim( $requirements['visit_page'] ), false ) . '>' . ucfirst( $page->post_title ).'</option>';
				}
			?>
		</select>
		<?php do_action( 'badgeos_steps_ui_html_after_ranks_visit_page', $step_id, $post_id ); ?>
		
		<input type="number" size="5" min="0" placeholder="<?php _e( 'Years', 'badgeos' ); ?>" value="<?php esc_attr_e( intval( $requirements['num_of_years'] ) > 0 ? intval( $requirements['num_of_years'] ): "1" ); ?>" class="badgeos-num-of-years badgeos-num-of-years-<?php echo $step_id; ?>">
		<?php do_action( 'badgeos_rank_steps_ui_html_after_num_of_years', $step_id, $post_id ); ?>

		<input class="required-count" type="text" size="3" maxlength="3" value="<?php echo $total_count; ?>" placeholder="1">
		<?php echo apply_filters( 'badgeos_rank_req_steps_ui_html_count_text', __( 'time(s).', 'badgeos' ), $step_id, $post_id ); ?>

		<?php do_action( 'badgeos_rank_req_steps_ui_html_after_count_text', $step_id, $post_id ); ?>

		<div class="step-title"><label for="step-<?php echo $step_id; ?>-title"><?php _e( 'Label', 'badgeos' ); ?>:</label> <input type="text" name="step-title" id="step-<?php echo $step_id; ?>-title" class="title" value="<?php echo get_the_title( $step_id ); ?>" /></div>
		<span class="spinner spinner-step-<?php echo $step_id;?>"></span>
	</li>
	<?php
}

/**
 * Get all the requirements of a given step
 *
 * @param  integer $step_id The given step's post ID
 * @return array|bool       An array of all the step requirements if it has any, false if not
 */
function badgeos_get_rank_req_step_requirements($step_id = 0 ) {

	/**
     * Setup our default requirements array, assume we require nothing
     */
	$requirements = array(
        'count'            			=> absint( badgeos_utilities::get_post_meta( $step_id, '_badgeos_count', true ) ),
        '_credit_value'    			=> absint( badgeos_utilities::get_post_meta( $step_id, '_credit_value', true ) ),
        'trigger_type'     			=> badgeos_utilities::get_post_meta( $step_id, '_rank_trigger_type', true ),
        'achievement_post' 			=> badgeos_utilities::get_post_meta( $step_id, 'achievement_post', true ),
        'badgeos_subtrigger_id' 	=> badgeos_utilities::get_post_meta( $step_id, '_badgeos_subtrigger_id', true ),
        'badgeos_subtrigger_value' 	=> badgeos_utilities::get_post_meta( $step_id, '_badgeos_rank_subtrigger_value', true ),
		'badgeos_fields_data' 		=> badgeos_utilities::get_post_meta( $step_id, '_badgeos_fields_data', true ),
		'visit_post' 				=> badgeos_utilities::get_post_meta( $step_id, '_badgeos_visit_post', true ),
		'visit_page' 				=> badgeos_utilities::get_post_meta( $step_id, '_badgeos_visit_page', true ),
		'num_of_years' 				=> badgeos_utilities::get_post_meta( $step_id, '_badgeos_num_of_years', true ),
    );

    if( !empty( $requirements['badgeos_fields_data'] ) ) {

        $requirements['badgeos_fields_data'] = badgeos_extract_array_from_query_params( $requirements['badgeos_fields_data'] );
    }

    /**
     * If the step requires a specific achievement
     */
	if ( ! empty( $requirements['achievement_type'] ) ) {
		$connected_activities = @get_posts( array(
			'post_type'        => $requirements['achievement_type'],
			'posts_per_page'   => 1,
			'suppress_filters' => false,
			'connected_type'   => $requirements['achievement_type'] . '-to-ranks',
			'connected_to'     => $step_id
		));
		if ( ! empty( $connected_activities ) )
			$requirements['achievement_post'] = $connected_activities[0]->ID;
	} elseif ( 'badgeos_specific_new_comment' === $requirements['trigger_type'] ) {
		$achievement_post = absint( badgeos_utilities::get_post_meta( $step_id, '_badgeos_achievement_post', true ) );
		if ( 0 < $achievement_post ) {
			$requirements[ 'achievement_post' ] = $achievement_post;
		}
	}
	
	/**
     * Available filter for overriding elsewhere
     */
	return apply_filters( 'badgeos_get_rank_req_step_requirements', $requirements, $step_id );
}

/**
 * AJAX Handler for adding a new step
 *
 * @return void
 */
function badgeos_add_rank_req_step_ajax_handler() {

	/**
     * Create a new Step post and grab it's ID
     */
	$settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
	$step_id = wp_insert_post( array(
		'post_type'   => trim( $settings['ranks_step_post_type'] ),
		'post_status' => 'publish'
	) );

	/**
     * Output the edit step html to insert into the Steps metabox
     */
	badgeos_rank_req_steps_ui_html( $step_id, $_POST['achievement_id'] );

	/**
     * Grab the post object for our Achievement
     */
	$achievement = badgeos_utilities::badgeos_get_post( $_POST['achievement_id'] );

	/**
     * Create the P2P connection from the step to the achievement
     */
	$p2p_id = p2p_create_connection(
		'rank_requirement-to-' . $achievement->post_type,
		array(
			'from' => $step_id,
			'to'   => $_POST['achievement_id'],
			'meta' => array(
				'date' => current_time( 'mysql' )
			)
		)
	);

	/**
     * Add relevant meta to our P2P connection
     */
	p2p_add_meta( $p2p_id, 'order', '0' );

	/**
     * Die here, because it's AJAX
     */
	die;
}
add_action( 'wp_ajax_add_rank_req_step', 'badgeos_add_rank_req_step_ajax_handler' );

/**
 * AJAX Handler for deleting a step
 *
 * @return void
 */
function badgeos_delete_rank_req_step_ajax_handler() {
	wp_delete_post( $_POST['step_id'] );
	die;
}
add_action( 'wp_ajax_delete_rank_req_step', 'badgeos_delete_rank_req_step_ajax_handler' );

/**
 * AJAX Handler for saving all steps
 *
 * @return void
 */
function badgeos_update_ranks_req_steps_ajax_handler() {
	
	/**
     * Only continue if we have any steps
     */
	if ( isset( $_POST['steps'] ) ) {

		/**
         * Grab our $wpdb global
         */
		global $wpdb;

		/**
         * Setup an array for storing all our step titles
         * This lets us dynamically update the Label field when steps are saved
         */
		$new_titles = array();

		/**
         * Loop through each of the created steps
         */
		foreach ( $_POST['steps'] as $key => $step ) {

			/**
             * Grab all of the relevant values of that step
             */
			$step_id          	= $step['step_id'];
			$required_count   	= ( ! empty( $step['required_count'] ) ) ? $step['required_count'] : 1;
			//$credit_value   	= ( ! empty( $step['credit_value'] ) ) ? $step['credit_value'] : 0;
			$trigger_type     	= $step['trigger_type'];
			$visit_post 		= $step['visit_post'];
			$visit_page 		= $step['visit_page'];
			$num_of_years		= $step['num_of_years'];
            $achievement_post = '';
            if( array_key_exists( 'achievement_post', $step ) )
                $achievement_post = $step['achievement_post'];

            $badgeos_subtrigger_id = '';
            $badgeos_subtrigger_value = '';
            $badgeos_fields_data = '';
            if( isset( $step['badgeos_subtrigger_id'] ) )
                $badgeos_subtrigger_id 		= $step['badgeos_subtrigger_id'];
            if( isset( $step['badgeos_subtrigger_value'] ) )
                $badgeos_subtrigger_value 	= $step['badgeos_subtrigger_value'];
            if( isset( $step['badgeos_fields_data'] ) )
                $badgeos_fields_data 		= $step['badgeos_fields_data'];

            /**
             * Clear all relation data
             */
			$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->p2p WHERE p2p_to=%d", $step_id ) );
			badgeos_utilities::del_post_meta( $step_id, '_badgeos_achievement_post' );
			badgeos_utilities::del_post_meta( $step_id, '_badgeos_num_of_years' );
			/**
             * Flip between our requirement types and make an appropriate connection
             */
			switch ( $trigger_type ) {

				case 'badgeos_specific_new_comment' :
					badgeos_utilities::update_post_meta( $step_id, '_badgeos_achievement_post', absint( $step['achievement_post'] ) );
					$title = sprintf( __( 'comment on post %d', 'badgeos' ),  $step['achievement_post'] );
					break;
				case 'badgeos_visit_a_post':
					badgeos_utilities::update_post_meta( $step_id, '_badgeos_visit_post', absint( $visit_post ) );
					if( ! empty( $visit_post ) )
						$title = sprintf( __( 'Visit a Post#%d', 'badgeos' ),  $visit_post );
					else 
						$title = __( 'Visit a Post', 'badgeos' );
					break;	
				case 'badgeos_visit_a_page':
					badgeos_utilities::update_post_meta( $step_id, '_badgeos_visit_page', absint( $visit_page ) );
					if( ! empty( $visit_page ) )	
						$title = sprintf( __( 'Visit a Page#%d', 'badgeos' ),  $visit_page );
					else 
						$title = __( 'Visit a Page', 'badgeos' );
					break;	
				case 'badgeos_award_author_on_visit_post':
					badgeos_utilities::update_post_meta( $step_id, '_badgeos_visit_post', absint( $visit_post ) );
					if( ! empty( $visit_post ) )
						$title = sprintf( __( 'Author on Visit a Post#%d', 'badgeos' ),  $visit_post );
					else 
						$title = __( 'Author on Visit a Post', 'badgeos' );
					break;	
				case 'badgeos_award_author_on_visit_page':
					badgeos_utilities::update_post_meta( $step_id, '_badgeos_visit_page', absint( $visit_page ) );
					if( !empty( $visit_page ) )	
						$title = sprintf( __( 'Author on Visit a Page#%d', 'badgeos' ),  $visit_page );
					else 
						$title = __( 'Author on Visit a Page', 'badgeos' );
					break;	
				case 'badgeos_on_completing_num_of_year':
					badgeos_utilities::update_post_meta( $step_id, '_badgeos_num_of_years', absint( $num_of_years ) );
					if( ! empty( $num_of_years ) )
						$title = sprintf( __( 'on completing %d year(s)', 'badgeos' ),  $num_of_years );
					else 
						$title = __( 'on completing number of year(s)', 'badgeos' );
					break;
				default :
					$triggers = get_badgeos_ranks_req_activity_triggers();
					$title = $triggers[$trigger_type];
                    if( is_array( $title ) ) {
                        if( !empty( $badgeos_subtrigger_value ) )
                            $title = $badgeos_subtrigger_value;
                        else
                            $title = $title['label'];
                    }
                    break;

			}

			/**
             * Update the step order
             */
			p2p_update_meta( badgeos_get_p2p_id_from_child_id( $step_id ), 'order', $key );

			/**
             * Update our relevant meta
             */
            badgeos_utilities::update_post_meta( $step_id, '_badgeos_count', 				 $required_count );
            badgeos_utilities::update_post_meta( $step_id, '_rank_trigger_type', 			 $trigger_type );
            badgeos_utilities::update_post_meta( $step_id, 'achievement_post', 			 $achievement_post );
            badgeos_utilities::update_post_meta( $step_id, '_badgeos_subtrigger_id', 		 $badgeos_subtrigger_id );
            badgeos_utilities::update_post_meta( $step_id, '_badgeos_rank_subtrigger_value',$badgeos_subtrigger_value );
            badgeos_utilities::update_post_meta( $step_id, '_badgeos_fields_data', 		 $badgeos_fields_data );

            /**
             * Available hook for custom Activity Triggers
             */
			$custom_title = sprintf( __( 'Earn %1$s %2$s.', 'badgeos' ), $title, sprintf( _n( '%d time', '%d times', $required_count ), $required_count ) );
			$custom_title = apply_filters( 'badgeos_save_step', $custom_title, $step_id, $step );

			/**
             * Update our original post with the new title
             */
			$post_title = !empty( $step['title'] ) ? $step['title'] : $custom_title;
			wp_update_post( array( 'ID' => $step_id, 'post_title' => $post_title ) );

			/**
             * Add the title to our AJAX return
             */
			$new_titles[$step_id] = stripslashes( $post_title );

		}

		/**
         * Send back all our step titles
         */
		echo json_encode($new_titles);

	}

	die;
}
add_action( 'wp_ajax_update_ranks_req_steps', 'badgeos_update_ranks_req_steps_ajax_handler' );

/**
 * AJAX helper for getting our posts and returning select options
 *
 * @return void
 */
function badgeos_rank_activity_trigger_post_award_select_ajax_handler() {

	/**
     * Grab our achievement type from the AJAX request
     */
	$achievement_type = $_REQUEST['achievement_type'];
	$exclude_posts = (array) $_REQUEST['excluded_posts'];
	$requirements = badgeos_get_rank_req_step_requirements( $_REQUEST['step_id'] );

	/**
     * If we don't have an achievement type, bail now
     */
	if ( empty( $achievement_type ) ) {
		die();
	}

	/**
     * Grab all our posts for this achievement type
     */
	$achievements = get_posts( array(
		'post_type'      => $achievement_type,
		'post__not_in'   => $exclude_posts,
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	));

	/**
     * Setup our output
     */
	$output = '<option></option>';
	foreach ( $achievements as $achievement ) {
		$output .= '<option value="' . $achievement->ID . '" ' . selected( $requirements['achievement_post'], $achievement->ID, false ) . '>' . $achievement->post_title . '</option>';
	}

	/**
     * Send back our results and die like a man
     */
	echo $output;
	die();
}
add_action( 'wp_ajax_post_award_select_ajax', 'badgeos_rank_activity_trigger_post_award_select_ajax_handler' );

/**
 * Get the sort order for a given step
 *
 * @param  integer $step_id The given step's post ID
 * @return integer          The step's sort order
 */
function get_ranks_step_menu_order( $step_id = 0 ) {

    global $wpdb;
	$p2p_id = $wpdb->get_var( $wpdb->prepare( "SELECT p2p_id FROM $wpdb->p2p WHERE p2p_from = %d", $step_id ) );
	$menu_order = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $wpdb->p2pmeta WHERE p2p_id=%d AND meta_key='order'", $p2p_id ) );
	if ( ! $menu_order || $menu_order == 'NaN' ) $menu_order = '0';
	return $menu_order;
}

/**
 * Helper function for comparing our step sort order (used in uasort() in badgeos_create_steps_meta_box())
 *
 * @param  integer $step1 The order number of our given step
 * @param  integer $step2 The order number of the step we're comparing against
 * @return integer        0 if the order matches, -1 if it's lower, 1 if it's higher
 */
function badgeos_compare_rank_step_order($step1 = 0, $step2 = 0 ) {

    if ( $step1->order == $step2->order ) return 0;
	return ( $step1->order < $step2->order ) ? -1 : 1;
}
