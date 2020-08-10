<?php
/**
 * Register [badgeos_user_earned_ranks] shortcode.
 *
 * @since 1.4.0
 */
function badgeos_user_earned_ranks_shortcode() {
    global $wpdb;
    // Setup a custom array of rank types
    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
    $rank_types = get_posts( array(
        'post_type'      =>	$badgeos_settings['ranks_main_post_type'],
        'posts_per_page' =>	-1,
    ) );

    $types = array(  );
    foreach( $rank_types as $type ) {
        $types[ $type->post_name ] = $type->post_title;
    }

    $posts = get_posts();
    $post_list = array();
    foreach( $posts as $post ) {
        $post_list[ $post->ID ] = $post->post_title;
    }

    badgeos_register_shortcode( array(
        'name'            => __( 'User Earned Ranks', 'badgeos' ),
        'description'     => __( 'Output a list of Ranks.', 'badgeos' ),
        'slug'            => 'badgeos_user_earned_ranks',
        'output_callback' => 'badgeos_earned_ranks_shortcode',
        'attributes'      => array(
            'rank_type' => array(
                'name'        => __( 'Rank Type(s)', 'badgeos' ),
                'description' => __( 'Single, or comma-separated list of, Rank type(s) to display.', 'badgeos' ),
                'type'        => 'select',
                'values'      => $types,
                'default'     => '',
            ),
            'limit' => array(
                'name'        => __( 'Limit', 'badgeos' ),
                'description' => __( 'Number of Ranks to display.', 'badgeos' ),
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
                    'rank_id'         => __( 'Rank ID', 'badgeos' ),
                    'rank_title'      => __( 'Rank Title', 'badgeos' ),
                    'dateadded'       => __( 'Award Date', 'badgeos' ),
                    'rand()'       => __( 'Random', 'badgeos' ),
                ),
                'default'     => 'rank_id',
            ),
            'order' => array(
                'name'        => __( 'Order', 'badgeos' ),
                'description' => __( 'Sort order.', 'badgeos' ),
                'type'        => 'select',
                'values'      => array( 'ASC' => __( 'Ascending', 'badgeos' ), 'DESC' => __( 'Descending', 'badgeos' ) ),
                'default'     => 'ASC',
            ),
            'user_id1' => array(
                'name'          => __( 'Select User (Type 3 chars)', 'badgeos' ),
                'description'   => __( 'Show only ranks earned by a specific user.', 'badgeos' ),
                'type'          => 'text',
                'autocomplete_name' => 'user_id',
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
            'show_title' => array(
                'name'        => __( 'Show Rank Title', 'badgeos' ),
                'description' => __( 'Display Rank Title.', 'badgeos' ),
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
add_action( 'init', 'badgeos_user_earned_ranks_shortcode' );

/**
 * Earned rank List Shortcode.
 *
 * @since  1.0.0
 *
 * @param  array $atts Shortcode attributes.
 * @return string 	   HTML markup.
 */
function badgeos_earned_ranks_shortcode( $atts = array () ){

    if( ! is_user_logged_in() && ( ! isset( $atts['user_id'] ) || empty( $atts['user_id'] ) ) ) {
        return '<div id="badgeos-achievements-filters-wrap">'.__( 'Please login to the site to view the earned ranks.', 'badgeos' ).'</div>';
    }

    $key = 'badgeos_user_earned_ranks';
    if( is_array( $atts ) && count( $atts ) > 0 ) {
        foreach( $atts as $index => $value ) {
            $key .= "_".strval( $value ) ;
        }
    }

    global $user_ID;
    extract( shortcode_atts( array(
        'rank_type'   => 'all',
        'limit'       => '10',
        'show_search' => true,
        'user_id'     => get_current_user_id(),
        'orderby'     => 'ID',
        'order'       => 'ASC',
        'show_title'  => 'true',
        'show_thumb'  => 'true',
        'show_description'  => 'true',
        'default_view'  => '',
        'image_width'  => '',
        'image_height'  => '',

    ), $atts, 'badgeos_user_earned_ranks' ) );

    wp_enqueue_style( 'badgeos-front' );
    wp_enqueue_script( 'badgeos-achievements' );

    if ( 'all' == $rank_type ) {
        $post_type_plural = __( 'User Earned Ranks', 'badgeos' );
    } else {
        $types = explode( ',', $rank_type );
        $badge_post_type = get_post_type_object( $types[0] );
        $type_name = '';
        if( $badge_post_type ) {
            if( isset( $badge_post_type->labels ) ) {
                if( isset( $badge_post_type->labels->name ) ) {
                    $type_name = $badge_post_type->labels->name;
                }
            }
        }
        $post_type_plural = ( 1 == count( $types ) && !empty( $types[0] ) ) ? $type_name : __( 'User Earned ranks', 'badgeos' );
    }

    $ranks_html = '';

    $ranks_html .= '<div id="badgeos-ranks-filters-wrap">';
    // Search
    if ( $show_search != 'false' ) {

        $search = isset( $_POST['earned_ranks_list_search'] ) ? $_POST['earned_ranks_list_search'] : '';
        $ranks_html .= '<div id="badgeos-ranks-search">';
        $ranks_html .= '<form id="earned_ranks_list_search_go_form" class="earned_ranks_list_search_go_form" action="'. get_permalink( get_the_ID() ) .'" method="post">';
        $ranks_html .= sprintf( __( 'Search: %s', 'badgeos' ), '<input type="text" id="earned_ranks_list_search" name="earned_ranks_list_search" class="earned_ranks_list_search" value="'. $search .'">' );
        $ranks_html .= '<input type="button" id="earned_ranks_list_search_go" name="earned_ranks_list_search_go" class="earned_ranks_list_search_go" value="' . esc_attr__( 'Go', 'badgeos' ) . '">';
        $ranks_html .= '</form>';
        $ranks_html .= '</div>';
    }

    $ranks_html .= '</div><!-- #badgeos-ranks-filters-wrap -->';

    // Content Container
    $ranks_html .= '<div id="badgeos-earned-ranks-container"></div>';

    // Hidden fields and Load More button
    $ranks_html .= '<input type="hidden" class="badgeos_earned_ranks_offset" id="badgeos_earned_ranks_offset" value="0">';
    $ranks_html .= '<input type="hidden" class="badgeos_ranks_count"  id="badgeos_ranks_count" value="0">';
    $ranks_html .= '<input type="button" class="earned_ranks_list_load_more" value="' . esc_attr__( 'Load More', 'badgeos' ) . '" style="display:none;">';
    $ranks_html .= '<div class="badgeos-earned-ranks-spinner"></div>';

    $maindiv = '<div class="badgeos_earned_rank_main_container" data-url="'.esc_url( admin_url( 'admin-ajax.php', 'relative' ) ).'" data-rank_type="'.$rank_type.'" data-limit="'.$limit.'" data-show_search="'.$show_search.'" data-user_id="'.$user_id.'" data-orderby="'.$orderby.'" data-order="'.$order.'" data-show_title="'.$show_title.'" data-show_thumb="'.$show_thumb.'" data-show_description="'.$show_description.'" data-default_view="'.$default_view.'" data-image_width="'.$image_width.'" data-image_height="'.$image_height.'">';
    $maindiv .= $ranks_html;
    $maindiv .= '</div>';


    // Reset Post Data
    wp_reset_postdata();

    // Save a global to prohibit multiple shortcodes
    return $maindiv;
}