<?php
/**
 * Register the [badgeos_achievement] shortcode.
 */
function badgeos_register_ob_evidence_shortcode() {
    
    // Setup a custom array of achievement types
    $badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
    $achievement_types = get_posts( array(
        'post_type'      =>	$badgeos_settings['achievement_main_post_type'],
        'posts_per_page' =>	-1,
        'post_status'=> 'publish'
    ) );

    $post_list = array();
    foreach( $achievement_types as $type ) {
        $posts = get_posts( array( 'post_type' => $type->post_name, 'posts_per_page' =>	-1 ) );
        foreach( $posts as $post ) {
            if( badgeos_get_option_open_badge_enable_baking($post->ID) ) {
                $post_list[ $post->ID ] = $post->post_title;
            }
        }
    }
    
    badgeos_register_shortcode( array(
		'name'            => __( 'Achievement Evidence', 'badgeos' ),
		'slug'            => 'badgeos_evidence',
		'output_callback' => 'badgeos_openbadge_evidence_shortcode',
		'description'     => __( "Render a single achievement's evidence.", 'badgeos' ),
		'attributes'      => array(
			'achievement' => array(
				'name'          => __( 'Achievement', 'badgeos' ),
				'description'   => __( 'Achievement ID to show.', 'badgeos' ),
                'type'          => 'select',
                'values'        => $post_list
            ),
            'user_id1' => array(
                'name'              => __( 'Select User (Type 3 chars)', 'badgeos' ),
				'description'       => __( 'Achievement Earned by.', 'badgeos' ),
                'type'              => 'text',
                'autocomplete_name' => 'user_id',
            ),
            'award_id1' => array(
                'name'                  => __( 'Award Id', 'badgeos' ),
				'description'           => __( 'User awarded achievement record.', 'badgeos' ),
                'type'                  => 'text',
                'autocomplete_name'     => 'award_id',
            ),
		),
	) );
}
add_action( 'init', 'badgeos_register_ob_evidence_shortcode' );

/**
 * Single Achievement Shortcode.
 *
 * @param  array $atts Shortcode attributes.
 * @return string 	   HTML markup.
 */
function badgeos_openbadge_evidence_shortcode( $atts = array() ) {

    global $wpdb;
    
    wp_enqueue_script('thickbox');
    wp_enqueue_style('thickbox');

    /**
     * get the post id
     */
	$atts = shortcode_atts( array(
      'show_sharing_opt' => 'Yes',
      'achievement' => 0,
      'user_id' => 0,
      'award_id' => 0
	), $atts, 'badgeos_evidence' ); 
    
    $achievement_id = 0;
    if( ! empty( $_REQUEST['bg'] ) ) {
        $achievement_id 	= sanitize_text_field( $_REQUEST['bg'] );
    } else if( intval( $atts['achievement'] ) > 0 ) {
        $achievement 	= sanitize_text_field( $atts['achievement'] );
    }
    
    $entry_id = 0;
    if( ! empty( $_REQUEST['eid'] ) ) {
        $entry_id  	= sanitize_text_field( $_REQUEST['eid'] );
    } else if( intval( $atts['award_id'] ) > 0 ) {
        $entry_id 	= sanitize_text_field( $atts['award_id'] );
    }
    
    $user_id = 0;
    if( ! empty( $_REQUEST['uid'] ) ) {
        $user_id  	        = sanitize_text_field( $_REQUEST['uid'] );
    } else if( intval( $atts['user_id'] ) > 0 ) {
        $user_id 	= sanitize_text_field( $atts['user_id'] );
    }
    
    $where = '';

    /**
     * return if entry_id not specified
     */
	if ( empty( $entry_id )  || intval( $entry_id ) < 1  ) {
        return;
    } else {
        $where .= " and entry_id='" . $entry_id . "'";  
    }
      
    
    /**
     * return if user_id not specified
     */
    if ( ! empty( $user_id ) && intval( $user_id ) > 1 ) {
        $where .= " and user_id='".$user_id."'";  
    }
      
    /**
     * return if user_id not specified
     */
    if ( ! empty( $achievement_id ) && intval( $achievement_id ) > 1 ) {
        $where .= " and ID='".$achievement_id."'";  
    }
    
    $output = '';
    
    $recs = $wpdb->get_results( "select * from ".$wpdb->prefix."badgeos_achievements where 1=1 ".$where );
    if( count( $recs ) > 0 ) {

        $rec = $recs[0];

        $expiration          = ( badgeos_utilities::get_post_meta( $achievement_id, '_open_badge_expiration', true ) ? badgeos_utilities::get_post_meta( $achievement_id, '_open_badge_expiration', true ) : '0' );
        $expiration_type     = ( badgeos_utilities::get_post_meta( $achievement_id, '_open_badge_expiration_type', true ) ? badgeos_utilities::get_post_meta( $achievement_id, '_open_badge_expiration_type', true ) : 'Day' );

        $user = get_user_by( 'ID', $rec->user_id );
        $achievement = badgeos_utilities::badgeos_get_post( $rec->ID );
        wp_enqueue_style( 'badgeos-front' );
        wp_enqueue_script( 'badgeos-achievements' ); 
        
        $dirs = wp_upload_dir();
        $baseurl = trailingslashit( $dirs[ 'baseurl' ] );
        $basedir = trailingslashit( $dirs[ 'basedir' ] );
        $badge_directory = trailingslashit( $basedir.'user_badges/'.$user_id );
        $badge_url = trailingslashit( $baseurl.'user_badges/'.$user_id );
        
        ob_start();wp_enqueue_style( 'badgeos-font-awesome' );wp_enqueue_style( 'badgeos-front' );
        ?>
            <div class="evidence_main">
                <div class="left_col">
                    <?php if( ! empty( $rec->image ) && file_exists( $badge_directory.$rec->image ) ) { ?>
                        <img src="<?php echo $badge_url.$rec->image;?>" with="100%" />
                    <?php } else { ?>
                        <?php echo badgeos_get_achievement_post_thumbnail( $achievement_id, 'full' ); ?>
                    <?php  } ?>
                    
                    <div class="verification"> 
                        <input id="open-badgeos-verification" href="javascript:;" data-bg="<?php echo $achievement_id;?>" data-eid="<?php echo $entry_id;?>" data-uid="<?php echo $user_id;?>" class="verify-open-badge" value="<?php echo _e( 'Verify', 'badgeos' );?>" type="button" />
                        
                    </div>
                    <?php echo apply_filters( 'badgeos_evidence_after_left_column', '', $achievement );?>
                </div>
                <div class="right_col">
                    <h3 class="title"><?php echo $rec->achievement_title;?></h3>        
                    <?php if($achievement) {  ?>
                        <p>
                            <?php echo $achievement->post_content;?>
                        </p>
                    <?php } ?>
                    <div class="badgeos_user_name"><strong><?php echo _e( 'Receiver', 'badgeos' );?>:</strong> <?php echo $user->display_name;?></div>
                    <div class="badgeos_issuer_name"><strong><?php echo _e( 'Issuer', 'badgeos' );?>:</strong> <?php bloginfo( 'name' ); ?></div>
                    <div class="badgeos_issue_date"><strong><?php echo _e( 'Issue Date', 'badgeos' );?>:</strong> <?php echo date( badgeos_utilities::get_option('date_format'), strtotime( $rec->date_earned ) );?></div>
                    <?php if( intval( $expiration ) > 0 ) { ?>
                        <div class="badgeos_expiry_date"><strong><?php echo _e( 'Expiry Date', 'badgeos' );?>:</strong> <?php echo date( badgeos_utilities::get_option('date_format'), strtotime( '+'.$expiration.' '.$expiration_type, strtotime( $rec->date_earned ) ) );?></div>
                    <?php } else { ?>
                        <div class="badgeos_expiry_date"><strong><?php echo _e( 'Expiry Date', 'badgeos' );?>:</strong> <?php echo _e( 'None', 'badgeos' );?></div>
                    <?php } ?>
                    <?php echo apply_filters( 'badgeos_evidence_after_right_column', '', $achievement );?>
                </div>
            </div>

            <div id="modal" class="badgeos_verification_modal_popup">
                <header class="badgeos_verification_popup_header">
                    <h2><?php echo _e( 'Verification', 'badgeos' );?></h2>
                    <span class="controls">
                        <a href="#" class="badgeos_verification_close"></a>
                    </span>
                </header>
                <div class="badgeos_verification_modal_panel">
                    
                </div>
            </div>
        <?php

        $output = ob_get_clean();

        return $output;
    }
    
    /**
     * Return our rendered achievement
     */
	return $output;
}