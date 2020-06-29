<?php

/**
 * Get all non open standard badges
 *
 * @return array
 */
function badgeos_ob_get_all_non_achievements() {

	global $wpdb;

	$table_name = $wpdb->prefix . 'badgeos_achievements';
	$badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array(); 
	$step_type = trim( $badgeos_settings['achievement_step_post_type'] );
	$query = "SELECT * 
				FROM        $table_name 
				WHERE       `$table_name`.post_type != '$step_type'
				AND `$table_name`.rec_type != 'open_badge'
				";

	$results = $wpdb->get_results( $query );
	$final_records = [];

	foreach($results as $result) {

		$achievement_id = $result->ID;
		//$open_badge_enable_baking = ( get_post_meta( $achievement_id, '_open_badge_enable_baking', true ) ? get_post_meta( $achievement_id, '_open_badge_enable_baking', true ) : 'false' );
		$open_badge_enable_baking = badgeos_get_option_open_badge_enable_baking($achievement_id);

		if($open_badge_enable_baking) {
			$final_records[] = $result;
		}
	}

	return $final_records;
}

function badgeos_get_option_open_badge_enable_baking($post_id) {

	//Check existing post meta
	$open_badge_enable_baking = get_post_meta( $post_id, '_open_badge_enable_baking', true );

	$open_badge_enable_baking = sanitize_text_field($open_badge_enable_baking);

	return $open_badge_enable_baking == 'true';
}

/**
 * @param $entry_id
 * @param $achievement_id
 * @param $user_id
 * @return bool
 */
function get_badgeos_ob_achievements($entry_id,$achievement_id,$user_id) {

	global $wpdb;

	$table_name = $wpdb->prefix . 'badgeos_achievements';
	$badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array(); 
	$step_type = trim( $badgeos_settings['achievement_step_post_type'] );
	$results = $wpdb->get_col( $wpdb->prepare(
		"
					SELECT      COUNT(*) AS count
					FROM        $table_name BOS_A
					WHERE       BOS_A.entry_id = %d 
								AND BOS_A.ID = %d
								AND BOS_A.user_id = %d
								AND BOS_A.post_type != %s
								AND BOS_A.image IS NOT NULL
			",
		$entry_id,
		$achievement_id,
		$user_id,
		$step_type
	) );

	return $results[0] > 0;
}

/**
 * Bakes the badge image when achivement is awarded...
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
function badgeos_obadge_issue_badge( $user_id, $achievement_id, $this_trigger, $site_id, $args, $entry_id ) {
	
	$GLOBALS['badgeos_open_badge']->bake_user_badge( $entry_id, $user_id, $achievement_id );
}

add_action( 'badgeos_award_achievement', 'badgeos_obadge_issue_badge', 10, 6 );

/**
 * Override the default thumbnail image... 
 * 
 * @param $file
 * 
 * @return $file
 */ 
function badgeos_ob_default_achievement_thumb( $file ) {
	
	$asset_url = badgeos_get_directory_url();
	
	if( file_exists( $asset_url.'images/default_badge.png' ) ) {
		$file = $asset_url.'images/default_badge.png';
	}

	return $file;
}
add_filter( 'badgeos_default_achievement_post_thumbnail', 'badgeos_ob_default_achievement_thumb', 10, 1 );

/**
 * Override the default thumbnail image... 
 * 
 * @param $file
 * 
 * @return $file
 */ 
function badgeos_ob_profile_achivement_image( $badge_image, $achievement ) {
	
	$dirs = wp_upload_dir();
	$baseurl = trailingslashit( $dirs[ 'baseurl' ] );
	$basedir = trailingslashit( $dirs[ 'basedir' ] );
	$badge_directory = trailingslashit( $basedir.'user_badges/'.$achievement->user_id );
	$badge_url = trailingslashit( $baseurl.'user_badges/'.$achievement->user_id );

	if( ! empty( $achievement->image ) && file_exists( $badge_directory.$achievement->image ) ) {
		return '<img src="'.$badge_url.$achievement->image.'" height="50" with="50" />';
	} 

	return $badge_image;
	
}
add_filter( 'badgeos_profile_achivement_image', 'badgeos_ob_profile_achivement_image', 10, 2 );

function badgeos_ob_profile_add_column( $achievement ) {
	
	$dirs = wp_upload_dir();
	$baseurl = trailingslashit( $dirs[ 'baseurl' ] );
	$basedir = trailingslashit( $dirs[ 'basedir' ] );
	$badge_directory = trailingslashit( $basedir.'user_badges/'.$achievement->user_id );
	$badge_url = trailingslashit( $baseurl.'user_badges/'.$achievement->user_id );
	
	$badgeos_evidence_page_id		= get_option( 'badgeos_evidence_url' );

	echo '<td>';
	$open_badge_enable_baking       	= badgeos_get_option_open_badge_enable_baking($achievement->ID);
	$is_pipe_sign = false;
	if( $open_badge_enable_baking ) {
		$badgeos_evidence_page_id	= get_option( 'badgeos_evidence_url' );
		$badgeos_evidence_url 		= get_permalink( $badgeos_evidence_page_id );
		$badgeos_evidence_url 		= add_query_arg( 'bg', $achievement->ID, $badgeos_evidence_url );
		$badgeos_evidence_url  		= add_query_arg( 'eid', $achievement->entry_id, $badgeos_evidence_url );
		$badgeos_evidence_url  		= add_query_arg( 'uid', $achievement->user_id, $badgeos_evidence_url );

		if( ! empty( $badgeos_evidence_url ) ) {
			echo '<span class="evidence"><a class="evidence_lnk" href="'.esc_url( $badgeos_evidence_url ).'">' . __( 'Evidence', 'badgeos' ) . "</a></span>";
			$is_pipe_sign = true;
		}	
	}
	$download_url= add_query_arg( array( 
		'action'         	=> 'download',
		'user_id'        	=> absint( $achievement->user_id ),
		'achievement_id' 	=> absint( $achievement->ID ),
		'entry_id' 			=> absint( $achievement->entry_id ),
	) );
	
	if( ! empty( $achievement->image ) && file_exists( $badge_directory.$achievement->image ) ) {
		if( $is_pipe_sign ) {
			echo '&nbsp;|&nbsp;';
		}
		echo '<span class="download"><a class="download_lnk" href="'.esc_url( wp_nonce_url( $download_url, 'badgeos_download_achievement' ) ).'">' . __( 'Download', 'badgeos' ) . '</a></span>';
	}
	echo '</td>';
}
add_action( 'badgeos_profile_achivement_add_column_data', 'badgeos_ob_profile_add_column', 10, 1 );
 
function badgeos_ob_profile_add_column_heading( ) {
	wp_enqueue_script('thickbox');
	wp_enqueue_style('thickbox');
	echo '<th>'.__( 'Options', 'badgeos' ).'</th>';
}
add_action( 'badgeos_profile_achivement_add_column_heading', 'badgeos_ob_profile_add_column_heading', 10, 0 );

/**
 * Process the adding/revoking of achievements on the user profile page
 *
 * @since  1.0.0
 * @return void
 */
function badgeos_ob_process_user_data() {
	global $wpdb;
	
	// Process revoking achievement from a user
	if ( isset( $_GET['action'] ) && 'download' == $_GET['action'] && isset( $_GET['entry_id'] ) && isset( $_GET['user_id'] ) && isset( $_GET['achievement_id'] ) ) {

		// Verify our nonce
		check_admin_referer( 'badgeos_download_achievement' );

		$entry_id 		= sanitize_text_field( $_REQUEST['entry_id'] );
		$user_id 		= sanitize_text_field( $_REQUEST['user_id'] );
		$achievement_id = sanitize_text_field( $_REQUEST['achievement_id'] );

		$where = " entry_id = '".$entry_id."' and ID = '".$achievement_id."' and user_id='".$user_id."'";

		$table_name = $wpdb->prefix . 'badgeos_achievements';
		$achievements = $wpdb->get_results( "SELECT * FROM $table_name WHERE $where order by date_earned" );
		if( count( $achievements ) > 0 ) {
			$achievement = $achievements[0];

			$dirs = wp_upload_dir();
			$baseurl = trailingslashit( $dirs[ 'baseurl' ] );
			$basedir = trailingslashit( $dirs[ 'basedir' ] );
			$badge_directory = trailingslashit( $basedir.'user_badges/'.$user_id );
			$badge_url = trailingslashit( $baseurl.'user_badges/'.$user_id );
			if( ! empty( $achievement->image ) && file_exists( $badge_directory.$achievement->image ) ) {

				$file_name = $badge_directory.$achievement->image;
				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename="'.basename($achievement->image).'"');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . filesize($file_name));
				flush(); // Flush system output buffer
				readfile($file_name);
				exit;
			}
		}
	}
}

add_action( 'init', 'badgeos_ob_process_user_data' );

/**
 * change the record type if achivement is open badge based.
 * 
 * @param $rec_type
 * @param $achievement_id
 * @param $user_id
 * 
 * @return $rec_type
 */ 
function badgeos_ob_achievements_record_type( $rec_type, $achievement_id, $user_id ) {

	//$enable_baking = get_post_meta( $achievement_id, '_open_badge_enable_baking', true );
	$enable_baking = badgeos_get_option_open_badge_enable_baking($achievement_id);

	if( $enable_baking  ) {
		return 'open_badge';
	}

	return $rec_type;
}
add_filter( 'badgeos_achievements_record_type', 'badgeos_ob_achievements_record_type', 10, 3 );

/**
 * Globally replace "Featured Image" text with "Achievement Image".
 *
 * @since  1.3.0
 *
 * @param  string $string Original output string.
 * @return string         Potentially modified output string.
 */
function badgeos_ob_png_only_note( $html ) {
	
	$pt = get_current_screen()->post_type;
	
	//if ( $pt != 'post') return;
	$badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array(); 		
	$achievement_types = get_posts( array(
		'post_type'      =>	trim( $badgeos_settings['achievement_main_post_type'] ),
		'posts_per_page' =>	-1,
	) );
	// Loop through each achievement type post and register it as a CPT
	foreach ( $achievement_types as $achievement_type ) {
		if ( $pt == $achievement_type->post_name ) {
			return $html .= "<b>".__( 'Note', 'badgeos' ).'</b>: '.__( "If 'enabled badge baking' option is 'yes' then upload png images only here.", 'badgeos' );
		}
	}
	return $html;
}
add_filter( 'admin_post_thumbnail_html', 'badgeos_ob_png_only_note');

function badgeos_ob_theme_setup() {

	add_theme_support( 'custom-logo', array(
		'height'      => 100,
		'width'       => 400,
		'flex-width' => true,
	) );
}
add_action( 'after_setup_theme', 'badgeos_ob_theme_setup' );

function badgeos_ob_get_achievement_entry($entry_id) {
	global $wpdb;

	$table_name = $wpdb->prefix . 'badgeos_achievements';
	$result = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} A WHERE 1=1 AND A.entry_id=%d", $entry_id), ARRAY_A);
	return $result;
}

function badgeos_convertable_credly_achievements_list_count() {
	
	global $wpdb;
	
	$result = $wpdb->get_results("SELECT p.ID FROM `".$wpdb->prefix."posts` as p inner JOIN ".$wpdb->prefix."postmeta as pm on( p.ID=pm.post_id AND pm.meta_key='_badgeos_send_to_credly' ) where pm.meta_value='true' ", ARRAY_A);
	$recs = array();
	if( count( $result ) > 0 ) {
		foreach( $result as $res ) {

			$enable_baking = get_post_meta( $res['ID'], '_open_badge_enable_baking', true );
			if( $enable_baking != 'true' )
				$recs[] = $res['ID'];
		}
	}
	
	wp_send_json( array( 'total_recs'=>count($recs), 'recs' => $recs ) );
}
add_action( 'wp_ajax_badgeos_get_convertable_credly_achievements_list_count', 'badgeos_convertable_credly_achievements_list_count' );

function badgeos_convert_credly_achievements_2_open_badge_callback() {
	
	global $wpdb;
	$post_id 		= sanitize_text_field( $_REQUEST[ 'ID' ] );
	$disable_credly = sanitize_text_field( $_REQUEST[ 'disable_credly' ] );
	$achievement = get_post( $post_id );
	if( $achievement ) {
		
		update_post_meta( $post_id, '_open_badge_enable_baking', 'true' );
		update_post_meta( $post_id, '_open_badge_criteria', get_permalink( $post_id ) );

		update_post_meta( $post_id, '_open_badge_include_evidence', ( get_post_meta( $post_id, '_badgeos_credly_include_evidence', true ) ? get_post_meta( $post_id, '_badgeos_credly_include_evidence', true ): 'false' ) );
        update_post_meta( $post_id, '_open_badge_expiration', ( get_post_meta( $post_id, '_badgeos_credly_expiration', true ) ? get_post_meta( $post_id, '_badgeos_credly_expiration', true ) : '0' ) );
		update_post_meta( $post_id, '_open_badge_expiration_type', 'Day' );
		
		update_post_meta( $post_id, '_open_badge_updated_from_credly', 'true' );
		update_post_meta( $post_id, '_open_badge_updated_from_credly_at', current_time( 'mysql' ) );

		if( $disable_credly == 'true' ) {
			update_post_meta( $post_id, '_badgeos_send_to_credly', 'false' );
		}

		wp_send_json( array( "type" => "success" ) );
	} else {
		wp_send_json( array( "type" => "error", 'message'=> __( 'No such an achievement found.', 'badgeos' ) ) );
	}
}
add_action( 'wp_ajax_badgeos_convert_credly_achievements_2_open_badge', 'badgeos_convert_credly_achievements_2_open_badge_callback' );

function badgeos_open_badge_metabox_save( $post_id = 0 ) {
	
	// Verify nonce
	if ( isset( $_POST['badgeos_ob_quick_bulk_edit_action'] ) && $_POST['badgeos_ob_quick_bulk_edit_action'] == 'yes' ) {

		update_post_meta( $post_id, '_open_badge_enable_baking', $_POST['open_badge_enable_baking'] == '1'?'true':'false' );
        update_post_meta( $post_id, '_open_badge_criteria', get_permalink( $post_id ) );
        update_post_meta( $post_id, '_open_badge_include_evidence', $_POST['open_badge_include_evidence'] == '1'?'true':'false' );
        update_post_meta( $post_id, '_open_badge_expiration', sanitize_text_field( $_POST['open_badge_expiration'] ) );
        update_post_meta( $post_id, '_open_badge_expiration_type', sanitize_text_field( $_POST['open_badge_expiration_type'] ) );
	}
}
add_action( 'save_post', 'badgeos_open_badge_metabox_save' );

function badgeos_ob_bulk_action_handler(   ) {
	
	if( ! current_user_can('manage_options') ) {
		return;
	}

	if( isset( $_REQUEST[ 'post' ] ) && is_array( $_REQUEST[ 'post' ] ) && count( $_REQUEST[ 'post' ] ) > 0 ) {
		$post_ids = $_REQUEST[ 'post' ];
		if( is_array( $post_ids ) &&  count( $post_ids ) > 0 ) {
			foreach( $post_ids as $post_id ) {
				
				if( isset( $_REQUEST['open_badge_enable_baking']  ) ) {
					update_post_meta( $post_id, '_open_badge_enable_baking', $_REQUEST['open_badge_enable_baking'] == '1'?'true':'false' );
				}

				update_post_meta( $post_id, '_open_badge_criteria', get_permalink( $post_id ) );
				
				if( isset( $_REQUEST['open_badge_include_evidence']  ) ) {
					update_post_meta( $post_id, '_open_badge_include_evidence', $_REQUEST['open_badge_include_evidence'] == '1'?'true':'false' );
				}

				if( isset( $_REQUEST['open_badge_expiration']  ) && !empty( $_REQUEST['open_badge_expiration']  ) ) {
					update_post_meta( $post_id, '_open_badge_expiration', sanitize_text_field( $_REQUEST['open_badge_expiration'] ) );
				}

				if( isset( $_REQUEST['open_badge_expiration_type']  ) && !empty( $_REQUEST['open_badge_expiration_type']  ) ) {
					update_post_meta( $post_id, '_open_badge_expiration_type', sanitize_text_field( $_REQUEST['open_badge_expiration_type'] ) );
				}
			}
		}
		
	}
}

if( isset( $_REQUEST['badgeos_ob_quick_bulk_edit_action'] ) && $_REQUEST[ 'badgeos_ob_quick_bulk_edit_action' ] == 'yes' ) {
	add_action( 'admin_init', 'badgeos_ob_bulk_action_handler' );
}

// Add our text to the quick & bulk edit box
add_action('quick_edit_custom_box', 'badgeos_ob_quick_edit_custom_box', 10, 2);
add_action('bulk_edit_custom_box', 'badgeos_ob_quick_edit_custom_box', 10, 2);
function badgeos_ob_quick_edit_custom_box($column_name, $post_type ) {
	global $post;
	$screen = get_current_screen();

	if( '_open_badge_enable_baking' == $column_name ) {
		
		$achievement_types 				= badgeos_get_achievement_types_slugs();
		$open_badge_enable_baking 		= get_post_meta( $post->ID, '_open_badge_enable_baking', true ); 
		$open_badge_include_evidence    = ( get_post_meta( $post->ID, '_open_badge_include_evidence', true ) ? get_post_meta( $post->ID, '_open_badge_include_evidence', true ) : 'false' );
		$open_badge_expiration          = ( get_post_meta( $post->ID, '_open_badge_expiration', true ) ? get_post_meta( $post->ID, '_open_badge_expiration', true ) : '0' );
		$open_badge_expiration_type     = ( get_post_meta( $post->ID, '_open_badge_expiration_type', true ) ? get_post_meta( $post->ID, '_open_badge_expiration_type', true ) : '0' );
	
		if ( in_array( $post_type, $achievement_types ) ) {
			?>	
				<input type="hidden" name="badgeos_ob_quick_bulk_edit_action" value="yes" />
				<fieldset class="inline-edit-col-right" style="margin-top: 0;">
					<div class="inline-edit-col">
						<div class="inline-edit-group">
							<label class="alignleft">
								<span class="title" for="open_badge_enable_baking"><?php _e( 'Badge Baking?', 'badgeos' ); ?></span>
								<select id="open_badge_enable_baking" class="open_badge_enable_baking_blk_field" name="open_badge_enable_baking">
									<option value="1" <?php selected( $open_badge_enable_baking, 'true' ); ?>><?php _e( 'Yes', 'badgeos' ) ?></option>
									<option value="0" <?php selected( $open_badge_enable_baking, 'false' ); ?>><?php _e( 'No', 'badgeos' ) ?></option>
								</select>
							</label>
						</div>
					</div>
					<div class="inline-edit-col sub_ob_badgeos_blk_fields">
						<div class="inline-edit-group">
							<label class="alignleft">
								<span class="title" for="open_badge_include_evidence"><?php _e( 'Evidence', 'badgeos' ); ?></span>
								<select id="open_badge_include_evidence" name="open_badge_include_evidence">
									<option value="1" <?php selected( $open_badge_include_evidence, 'true' ); ?>><?php _e( 'Yes', 'badgeos' ) ?></option>
									<option value="0" <?php selected( $open_badge_include_evidence, 'false' ); ?>><?php _e( 'No', 'badgeos' ) ?></option>
								</select>
							</label>
						</div>
		
					</div>
					<div class="inline-edit-col sub_ob_badgeos_blk_fields">
						<div class="inline-edit-group">
							<label class="alignleft">
								<span class="title" for="open_badge_expiration"><?php _e( 'Expiration', 'badgeos' ); ?></span>
								<input type="number" id="open_badge_expiration" size="3" min="0" max="365" name="open_badge_expiration" value="<?php echo $open_badge_expiration; ?>" class="date_picker_class" />
								<select id="open_badge_expiration_type" name="open_badge_expiration_type">
									<option value="Day" <?php selected( $open_badge_expiration_type, 'Day' ); ?>><?php _e( 'Day(s)', 'badgeos' ) ?></option>
									<option value="Month" <?php selected( $open_badge_expiration_type, 'Month' ); ?>><?php _e( 'Month(s)', 'badgeos' ) ?></option>
									<option value="Year" <?php selected( $open_badge_expiration_type, 'Year' ); ?>><?php _e( 'Year(s)', 'badgeos' ) ?></option>
								</select>
							</label>
						</div>
					</div>
				</fieldset>
			<?php
		}
	}
}

/*
 * New columns
 */
//add_filter('manage_post_posts_columns', 'badgeos_ob_admin_post_columns');

// the above hook will add columns only for default 'post' post type, for CPT:
// manage_{POST TYPE NAME}_posts_columns
function badgeos_ob_admin_post_columns( $column_array ) {
 
	$column_array['_open_badge_enable_baking'] = 'Open Badge?';
	$column_array['_badgeos_send_to_credly'] = 'Credly?';
	return $column_array;
}

add_action('admin_init', 'badgeos_ob_admin_edit_achievements_filter' );
function  badgeos_ob_admin_edit_achievements_filter(  ) {
	$achievement_types 				= badgeos_get_achievement_types_slugs();
	foreach( $achievement_types as $type ) {
		add_filter('manage_edit-'.$type.'_columns', 'badgeos_ob_admin_post_columns');
		add_action( 'manage_'.$type.'_posts_custom_column' , 'badgeos_ob_admin_post_columns_data', 10, 2 );
	}
}

/*
 * Populate our new columns with data
 */
add_action('manage_posts_custom_column', 'badgeos_ob_admin_post_columns_data', 10, 2);
function badgeos_ob_admin_post_columns_data( $column_name, $id ) {
	// if you have to populate more that one columns, use switch()
	switch( $column_name ) :
		case '_open_badge_enable_baking': {
			$open_badge_enable_baking = get_post_meta( $id, '_open_badge_enable_baking', true );
			echo $open_badge_enable_baking=='true'?__( 'Yes', 'badgeos' ):__( 'No', 'badgeos' );
			break;
		}
		case '_badgeos_send_to_credly': {
			$send_to_credly = get_post_meta( $id, '_badgeos_send_to_credly', true );
			echo $send_to_credly=='true'?__( 'Yes', 'badgeos' ):__( 'No', 'badgeos' );
			break;
		}
	endswitch;
 
}