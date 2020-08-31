<?php

/**
 * Register [badgeos_user_earned_achievements] shortcode.
 *
 * @since 1.4.0
 */
function badgeos_user_earned_achievements_shortcode() {
    global $wpdb;
    // Setup a custom array of achievement types
    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
    $achievement_types = get_posts( array(
        'post_type'      =>	$badgeos_settings['achievement_main_post_type'],
        'posts_per_page' =>	-1,
        'post_status'=> 'publish'
    ) );

    $post_list = array();

    $types = array( 'all' => __( 'All', 'badgeos' ) );
    foreach( $achievement_types as $type ) {
        $types[ $type->post_name ] = $type->post_title;
        $posts = get_posts( array( 'post_type' => $type->post_name ) );
        foreach( $posts as $post ) {
            $post_list[ $post->ID ] = $post->post_title;
        }
    }

    badgeos_register_shortcode( array(
        'name'            => __( 'User Earned Achievements', 'badgeos' ),
        'description'     => __( 'Output a list of achievements.', 'badgeos' ),
        'slug'            => 'badgeos_user_earned_achievements',
        'output_callback' => 'badgeos_earned_achievements_shortcode',
        'attributes'      => array(
            'type' => array(
                'name'        => __( 'Achievement Type(s)', 'badgeos' ),
                'description' => __( 'Single, or comma-separated list of, achievement type(s) to display.', 'badgeos' ),
                'type'        => 'select',
                'values'      => $types,
                'default'     => '',
            ),
            'limit' => array(
                'name'        => __( 'Limit', 'badgeos' ),
                'description' => __( 'Number of achievements to display.', 'badgeos' ),
                'type'        => 'text',
                'default'     => 10,
            ),
            'show_search' => array(
                'name'        => __( 'Show Search', 'badgeos' ),
                'description' => __( 'Display a search input.', 'badgeos' ),
                'type'        => 'select',
                'values'      => array(
                    'true'  => __( 'True', 'badgeos' ),
                    'false' => __( 'False', 'badgeos' )
                ),
                'default'     => 'true',
            ),
            'orderby' => array(
                'name'        => __( 'Order By', 'badgeos' ),
                'description' => __( 'Parameter to use for sorting.', 'badgeos' ),
                'type'        => 'select',
                'values'      => array(
                    'ID'         		=> __( 'Achievement ID', 'badgeos' ),
                    'achievement_title' => __( 'Achievement Title', 'badgeos' ),
                    'date_earned'  		=> __( 'Award Date', 'badgeos' ),
                    'rand()'       		=> __( 'Random', 'badgeos' ),
                ),
                'default'     => 'ID',
            ),
            'order' => array(
                'name'        => __( 'Order', 'badgeos' ),
                'description' => __( 'Sort order.', 'badgeos' ),
                'type'        => 'select',
                'values'      => array( 'ASC' => __( 'Ascending', 'badgeos' ), 'DESC' => __( 'Descending', 'badgeos' ) ),
                'default'     => 'ASC',
            ),
            'include' => array(
                'name'          => __( 'Include', 'badgeos' ),
                'description'   => __( 'Comma-separated list of specific achievement IDs to include.', 'badgeos' ),
                'type'          => 'select',
                'values'        => $post_list
            ),
            'exclude' => array(
                'name'          => __( 'Exclude', 'badgeos' ),
                'description'   => __( 'Comma-separated list of specific achievement IDs to exclude.', 'badgeos' ),
                'type'          => 'select',
                'values'        => $post_list
            ),
            'user_id1' => array(
                'name'          => __( 'Select User (Type 3 chars)', 'badgeos' ),
                'description'   => __( 'Show only achievements earned by a specific user.', 'badgeos' ),
                'type'          => 'text',
                'autocomplete_name' => 'user_id',
            ),
            'wpms' => array(
                'name'        => __( 'Include Multisite Achievements', 'badgeos' ),
                'description' => __( 'Show achievements from all network sites.', 'badgeos' ),
                'type'        => 'select',
                'values'      => array(
                    'true'  => __( 'True', 'badgeos' ),
                    'false' => __( 'False', 'badgeos' )
                ),
                'default'     => 'false',
            ),
            'show_title' => array(
                'name'        => __( 'Show Title', 'badgeos' ),
                'description' => __( 'Display Achievement Title.', 'badgeos' ),

                'type'        => 'select',
                'values'      => array(
                    'true'  => __( 'True', 'badgeos' ),
                    'false' => __( 'False', 'badgeos' )
                ),
                'default'     => 'true',
            ),
            'show_thumb' => array(
                'name'        => __( 'Show Thumbnail', 'badgeos' ),
                'description' => __( 'Display Thumbnail Image.', 'badgeos' ),
                'type'        => 'select',
                'values'      => array(
                    'true'  => __( 'True', 'badgeos' ),
                    'false' => __( 'False', 'badgeos' )
                ),
                'default'     => 'true',
            ),
            'show_description' => array(
                'name'        => __( 'Show Description', 'badgeos' ),
                'description' => __( 'Display Short Description.', 'badgeos' ),
                'type'        => 'select',
                'values'      => array(
                    'true'  => __( 'True', 'badgeos' ),
                    'false' => __( 'False', 'badgeos' )
                ),
                'default'     => 'true',
            ),
            'default_view' => array (
                'name'        => __( 'Default View', 'badgeos' ),
                'description' => __( 'Default Listing i.e. List or Grid.', 'badgeos' ),
                'type'        => 'select',
                'values'      => array(
                    ''  => '',
                    'list'  => __( 'List', 'badgeos' ),
                    'grid' => __( 'Grid', 'badgeos' )
                ),
                'default'     => '',
            ),
            'image_width' => array (
                'name'        => __( 'Thumnail Width', 'badgeos' ),
                'description' => __( "Achievement's image width.", 'badgeos' ),
                'type'        => 'text',
                'default'     => '',
            ),
            'image_height' => array (
                'name'        => __( 'Thumnail Height', 'badgeos' ),
                'description' => __( "Achievement's image height.", 'badgeos' ),
                'type'        => 'text',
                'default'     => '',
            ),
        ),
    ) );
}
add_action( 'init', 'badgeos_user_earned_achievements_shortcode' );

/**
 * Earned Achievement List Shortcode.
 *
 * @since  1.0.0
 *
 * @param  array $atts Shortcode attributes.
 * @return string 	   HTML markup.
 */
function badgeos_earned_achievements_shortcode( $atts = array () ){

    if( ! is_user_logged_in() && ( ! isset( $atts['user_id'] ) || empty( $atts['user_id'] ) ) ) {
        return '<div id="badgeos-achievements-filters-wrap">'.__( 'Please login to the site to view the earned achievements.', 'badgeos' ).'</div>';
    }

    $key = 'badgeos_user_earned_achievements';
    if( is_array( $atts ) && count( $atts ) > 0 ) {
        foreach( $atts as $index => $value ) {
            $key .= "_".strval( $value ) ;
        }
    }

    /**
     * check if shortcode has already been run
     */
    if ( isset( $GLOBALS[$key] ) ) {
        return '';
    }

    global $user_ID;

    $passed_user_id = get_current_user_id();
    if( isset( $atts['user_id'] ) && ! empty( $atts['user_id'] ) ) {
        $passed_user_id = $atts['user_id'];
    }

    extract( shortcode_atts( array(
        'type'        => 'all',
        'limit'       => '10',
        'show_search' => true,
        'user_id'     => $passed_user_id,
        'wpms'        => false,
        'orderby'     => 'ID',
        'order'       => 'ASC',
        'include'     => array(),
        'exclude'     => array(),
        'show_title'  => 'true',
        'show_thumb'  => 'true',
        'show_description'  => 'true',
        'default_view'  => '',
        'image_width'  => '',
        'image_height'  => '',

    ), $atts, 'badgeos_user_earned_achievements' ) );

    wp_enqueue_script( 'thickbox' );
    wp_enqueue_style( 'thickbox' );
    wp_enqueue_style( 'badgeos-front' );
    wp_enqueue_script( 'badgeos-achievements' );

    $data = array(
        'ajax_url'    => esc_url( admin_url( 'admin-ajax.php', 'relative' ) ),
        'type'        => $type,
        'limit'       => $limit,
        'show_search' => $show_search,
        'user_id'     => $user_id,
        'wpms'        => $wpms,
        'orderby'     => $orderby,
        'order'       => $order,
        'include'     => $include,
        'exclude'     => $exclude,
        'show_title'  => $show_title,
        'show_thumb'  => $show_thumb,
        'default_view'  => $default_view,
        'show_description'  => $show_description,
        'image_width'  => $image_width,
        'image_height'  => $image_height,
    );
    // wp_localize_script( 'badgeos-achievements', 'badgeos', $data );

    // If we're dealing with multiple achievement types
    if ( 'all' == $type ) {
        $post_type_plural = __( 'User Earned Achievements', 'badgeos' );
    } else {
        $types = explode( ',', $type );
        $badge_post_type = get_post_type_object( $types[0] );
        $type_name = '';
        if( $badge_post_type ) {
            if( isset( $badge_post_type->labels ) ) {
                if( isset( $badge_post_type->labels->name ) ) {
                    $type_name = $badge_post_type->labels->name;
                }
            }
        }
        $post_type_plural = ( 1 == count( $types ) && !empty( $types[0] ) ) ? $type_name : __( 'User Earned Achievements', 'badgeos' );
    }

    $badges = '';

    $badges .= '<div id="badgeos-achievements-filters-wrap">';
    // Search
    if ( $show_search != 'false' ) {

        $search = isset( $_POST['achievements_list_search'] ) ? $_POST['achievements_list_search'] : '';
        $badges .= '<div id="badgeos-achievements-search">';
        $badges .= '<form id="earned_achievements_list_search_go_form" class="earned_achievements_list_search_go_form" action="'. get_permalink( get_the_ID() ) .'" method="post">';
        $badges .= sprintf( __( 'Search: %s', 'badgeos' ), '<input type="text" id="earned_achievements_list_search" name="earned_achievements_list_search" class="earned_achievements_list_search" value="'. $search .'">' );
        $badges .= '<input type="button" id="earned_achievements_list_search_go" name="earned_achievements_list_search_go" class="earned_achievements_list_search_go" value="' . esc_attr__( 'Go', 'badgeos' ) . '">';
        $badges .= '</form>';
        $badges .= '</div>';
    }

    $badges .= '</div><!-- #badgeos-achievements-filters-wrap -->';

    // Content Container
    $badges .= '<div id="badgeos-earned-achievements-container"></div>';

    // Hidden fields and Load More button
    $badges .= '<input type="hidden" class="badgeos_earned_achievements_offset" id="badgeos_achievements_offset" value="0">';
    $badges .= '<input type="hidden" id="badgeos_achievements_count" value="0">';
    $badges .= '<input type="button" class="earned_achievements_list_load_more" value="' . esc_attr__( 'Load More', 'badgeos' ) . '" style="display:none;">';
    $badges .= '<div class="badgeos-earned-spinner"></div>';

    if( is_array( $include ) ){
        $include = implode(',', $include);
    }
    if( is_array( $exclude ) ){
        $exclude = implode(',', $exclude);
    }

    $maindiv = '<div class="badgeos_earned_achievement_main_container" data-url="'.esc_url( admin_url( 'admin-ajax.php', 'relative' ) ).'" data-type="'.$type.'" data-limit="'.$limit.'" data-show_search="'.$show_search.'" data-user_id="'.$user_id.'" data-wpms="'.$wpms.'" data-orderby="'.$orderby.'" data-order="'.$order.'" data-include="'.$include.'" data-exclude="'.$exclude.'" data-show_title="'.$show_title.'" data-show_thumb="'.$show_thumb.'" data-show_description="'.$show_description.'" data-default_view="'.$default_view.'" data-image_width="'.$image_width.'" data-image_height="'.$image_height.'">';
    $maindiv .= $badges;
    $maindiv .= '</div><div id="modal" class="badgeos_verification_modal_popup">
    <header class="badgeos_verification_popup_header">
        <h2>'.__( 'Verification', 'badgeos' ).'</h2>
        <span class="controls">
            <a href="#" class="badgeos_verification_close"></a>
        </span>
    </header>
    <div class="badgeos_verification_modal_panel">
        
    </div>
</div>';


    // Reset Post Data
    wp_reset_postdata();

    // Save a global to prohibit multiple shortcodes
    $GLOBALS[$key] = true;
    return $maindiv;
}