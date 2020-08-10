<?php

/**
 * Register the [badgeos_rank] shortcode.
 *
 * @since 1.4.0
 */
function badgeos_register_rank_shortcode() {

    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
    $rank_types = get_posts( array(
        'post_type'      =>	$badgeos_settings['ranks_main_post_type'],
        'posts_per_page' =>	-1,
    ) );

    $ranks = array();
    foreach( $rank_types as $rtype ) {
        $records = get_posts( array(
            'post_type'      =>	$rtype->post_name,
            'posts_per_page' =>	-1,
        ) );

        foreach( $records as $record ) {
            $ranks[ $record->ID ] = $record->post_title;
        }
    }
    
	badgeos_register_shortcode( array(
		'name'            => __( 'Single Rank', 'badgeos' ),
		'slug'            => 'badgeos_rank',
		'output_callback' => 'badgeos_rank_shortcode',
		'description'     => __( 'Render a single rank.', 'badgeos' ),
		'attributes'      => array(
			'id' => array(
				'name'        => __( 'Rank ID', 'badgeos' ),
				'description' => __( 'The ID of the rank to render.', 'badgeos' ),
                'type'        => 'select',
                'values'      => $ranks,
                'default'     => '',
            ),
            'show_title' => array (
                'name'        => __( 'Show Title', 'badgeos' ),
                'description' => __( 'Display Rank Title.', 'badgeos' ),
                'type'        => 'select',
                'values'      => array (
                    'true'  => __( 'True', 'badgeos' ),
                    'false' => __( 'False', 'badgeos' )
                ),
                'default'     => 'true',
            ),
            'show_thumb' => array (
                'name'        => __( 'Show Thumbnail', 'badgeos' ),
                'description' => __( 'Display Thumbnail Image.', 'badgeos' ),
                'type'        => 'select',
                'values'      => array(
                    'true'  => __( 'True', 'badgeos' ),
                    'false' => __( 'False', 'badgeos' )
                ),
                'default'     => 'true',
            ),
            'show_description' => array (
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
                'description' => __( "Rank's image width.", 'badgeos' ),
                'type'        => 'text',
                'default'     => '',
            ),
            'image_height' => array (
                'name'        => __( 'Thumnail Height', 'badgeos' ),
                'description' => __( "Rank's image height.", 'badgeos' ),
                'type'        => 'text',
                'default'     => '',
            ),
        ),
	) );
}
add_action( 'init', 'badgeos_register_rank_shortcode' );

/**
 * Single rank Shortcode.
 *
 * @since  1.0.0
 *
 * @param  array $atts Shortcode attributes.
 * @return string 	   HTML markup.
 */
function badgeos_rank_shortcode( $atts = array() ) {

	// get the post id
	$atts = shortcode_atts( array(
        'id' => get_the_ID(),
        'show_title'  => 'true',
        'show_thumb'  => 'true',
        'show_description'  => 'true',
        'image_width' => '',
        'image_height' => '',
    ), $atts, 'badgeos_rank' );

	// return if post id not specified
	if ( empty($atts['id']) )
	  return;

	wp_enqueue_style( 'badgeos-front' );
	wp_enqueue_script( 'badgeos-achievements' );

	// get the post content and format the badge display
	$rank = get_post( $atts['id'] );
	$output = '';

	// If we're dealing with an rank post
	if ( badgeos_is_rank( $rank ) ) {
        
        $output .= '<div id="badgeos-rank-item-' . $rank->ID . '" class="badgeos-list-item badgeos-rank-item badgeos-rank-item-' . $rank->ID . '">';
        
        // Achievement Image
        if( $atts['show_thumb'] == 'true' ) {
            $output .= '<div class="badgeos-item-image">';
            $output .= '<a href="' . get_permalink( $rank->ID ) . '">' . badgeos_get_rank_image( $rank->ID, $atts['image_width'], $atts['image_height'] ) . '</a>';
            $output .= '</div><!-- .badgeos-item-image -->';
        }

        // Achievement Content
        $output .= '<div class="badgeos-item-detail">';

        if( $atts['show_title'] == 'true' ) {
            
            $title = get_the_title($rank->ID);
            
            // Achievement Title
            $output .= '<h2 class="badgeos-item-title"><a href="'.get_permalink( $rank->ID ).'">'.$title.'</a></h2>';
        }

        // Achievement Short Description
        if( $atts['show_description'] == 'true' ) {
            $post = get_post( $rank->ID );
            if( $post ) {
                $output .= '<div class="badgeos-item-excerpt">';
                $excerpt = !empty( $post->post_excerpt ) ? $post->post_excerpt : $post->post_content;
                $output .= wpautop( apply_filters( 'get_the_excerpt', $excerpt ) );
                $output .= '</div><!-- .badgeos-item-excerpt -->';
            }
        }

        $output .= '</div><!-- .badgeos-item-description -->';
        $output .= apply_filters( 'badgeos_after_single_rank', '', $rank );
        $output .= '</div>';
	}

	// Return our rendered rank
	return $output;
}
