<?php

/**
 * Register [badgeos_user_earned_points] shortcode.
 *
 * @since 1.4.0
 */
function badgeos_user_earned_points_shortcode() {
    global $wpdb;
    // Setup a custom array of achievement types
    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
    $point_types = get_posts( array(
        'post_type'      =>	$badgeos_settings['points_main_post_type'],
        'posts_per_page' =>	-1,
    ) );

    $types = array(  );
    foreach( $point_types as $type ) {
        $types[ $type->post_name ] = $type->post_title;
    }

    badgeos_register_shortcode( array(
        'name'            => __( 'User Earned Points', 'badgeos' ),
        'description'     => __( 'Output a list of Points.', 'badgeos' ),
        'slug'            => 'badgeos_user_earned_points',
        'output_callback' => 'badgeos_earned_points_shortcode',
        'attributes'      => array(
            'point_type' => array(
                'name'        => __( 'Point Type', 'badgeos' ),
                'description' => __( 'Single point type to display.', 'badgeos' ),
                'type'        => 'select',
                'values'      => $types,
                'default'     => '',
            ),
            'user_id1' => array(
                'name'          => __( 'Select User (Type 3 chars)', 'badgeos' ),
                'description'   => __( 'Show only achievements earned by a specific user.', 'badgeos' ),
                'type'          => 'text',
                'autocomplete_name' => 'user_id',
            ),
            'show_title' => array(
                'name'        => __( 'Show Point Title', 'badgeos' ),
                'description' => __( 'Display Point Title.', 'badgeos' ),
                'type'        => 'select',
                'values'      => array(
                    'true'  => __( 'True', 'badgeos' ),
                    'false' => __( 'False', 'badgeos' )
                ),
                'default'     => 'true',
            ),
        ),
    ) );
}
add_action( 'init', 'badgeos_user_earned_points_shortcode' );

function get_post_by_name($post_name, $output = OBJECT) {
    global $wpdb;

    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();

    $post = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type=%s", $post_name, $badgeos_settings['points_main_post_type'] ));
    if ( $post )
        return get_post($post, $output);

    return null;
}
/**
 * Earned Achievement List Shortcode.
 *
 * @since  1.0.0
 *
 * @param  array $atts Shortcode attributes.
 * @return string 	   HTML markup.
 */
function badgeos_earned_points_shortcode( $atts = array () ){

    if( ! is_user_logged_in() && ( ! isset( $atts['user_id'] ) || empty( $atts['user_id'] ) ) ) {
        return '<div class="badgeos_earned_point_main">'.__( 'Please login to the site to view the earned points.', 'badgeos' ).'</div>';
    }

    $key = 'badgeos_user_earned_points';
    if( is_array( $atts ) && count( $atts ) > 0 ) {
        foreach( $atts as $index => $value ) {
            $key .= "_".strval( $value ) ;
        }
    }

    global $user_ID;
    extract( shortcode_atts( array(
        'point_type'  => '',
        'show_title'  => 'true',
        'user_id'  => get_current_user_id()

    ), $atts, 'badgeos_user_earned_points' ) );

    wp_enqueue_style( 'badgeos-front' );
    wp_enqueue_script( 'badgeos-achievements' );

    // If we're dealing with multiple achievement types
    $credit_id = 0;
    $point_unit = 0;
    if ( '' == $point_type ) {
        $post_type_plural = __( 'User Earned Points', 'badgeos' );
        $point_unit = 'Points';
    } else {
        $point_obj = get_post_by_name( $point_type );
        $post_type_plural = __( 'User Earned Points', 'badgeos' );
        if( $point_obj ) {
            $credit_id = $point_obj->ID;
            if( isset( $point_obj->ID ) ) {

                $plural_name = get_post_meta( $point_obj->ID, '_point_plural_name', true );

                if( !empty( $plural_name ) ) {
                    $post_type_plural = $plural_name;
                } else {
                    $post_type_plural = $point_obj->post_title;
                }

                $point_unit = $post_type_plural;
            }
        }
    }
    $point = badgeos_get_points_by_type( $credit_id, $user_id );
    if( $show_title != 'false' ) {
        $maindiv = '<div class="badgeos_earned_point_main" data-point_type="'.$point_type.'">';
        $maindiv .= '<div class="badgeos_earned_point_title">'.$post_type_plural.'</div>';
        $maindiv .= '<div class="badgeos_earned_point_detail">';
        $maindiv .= '<span class="point_value">'.$point.'</span> <span class="point_unit">'.$point_unit.'</span>';
        $maindiv .= '</div>';
        $maindiv .= apply_filters( 'badgeos_after_earned_point', '', $credit_id, $user_id );
        $maindiv .= '</div>';
    } else {
        $maindiv = '<span class="point_value point_value_'.$credit_id.'">'.$point.'</span> <span class="point_unit">'.$point_unit.'</span>';
    }


    // Reset Post Data
    wp_reset_postdata();

    return $maindiv;
}