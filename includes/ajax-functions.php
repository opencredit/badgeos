<?php
/**
 * AJAX Support Functions
 *
 * @package BadgeOS
 * @subpackage AJAX
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

// Setup our Badgeos AJAX actions
$badgeos_ajax_actions = array(
	'get-achievements',
    'get-earned-achievements',
    'get-earned-ranks',
	'get-achievements-select2',
	'get-achievement-types',
    'get-users',
    'badgeos-get-users-list',
    'get-ranks-list',
    'get-achievements-award-list'
);

// Register core Ajax calls.
foreach ( $badgeos_ajax_actions as $action ) {
	add_action( 'wp_ajax_' . $action, 'badgeos_ajax_' . str_replace( '-', '_', $action ), 1 );
	add_action( 'wp_ajax_nopriv_' . $action, 'badgeos_ajax_' . str_replace( '-', '_', $action ), 1 );
}

/**
 * AJAX Helper for selecting users in Shortcode Embedder
 *
 * @since 1.4.0
 */
function badgeos_ajax_get_achievements_award_list() {

    // If no query was sent, die here
    if ( ! isset( $_REQUEST['q'] ) ) {
        die();
    }

    global $wpdb;
   
    $badgeos_settings   = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
    $achievement_id     = sanitize_text_field( $_REQUEST['achievement_id'] );
    $user_id            = sanitize_text_field( $_REQUEST['user_id'] );
    $q                  = sanitize_text_field( $_REQUEST['q'] );

    $sql = "SELECT entry_id as id, CONCAT(achievement_title, ' : ', entry_id) as label, entry_id as value FROM ".$wpdb->prefix."badgeos_achievements where post_type!='".$badgeos_settings['achievement_step_post_type']."'";

    // Build our query
    if ( !empty( $q ) ) {
        $sql .= " and achievement_title LIKE '%". $q."%'";
    }
    
    // Build our query
    if ( !empty( $achievement_id ) ) {
        $sql .= " and ID = '". $achievement_id."'";
    }
     
    // Build our query
    if ( ! empty( $user_id ) ) {
        $sql .= " and user_id = '". $user_id."'";
    }

    // Fetch our results (store as associative array)
    $results = $wpdb->get_results( $sql." limit 100 ", 'ARRAY_A' );

    // Return our results
    wp_send_json( $results );
}

/**
 * AJAX Helper for returning earned ranks
 *
 * @since 1.0.0
 * @return void
 */
function badgeos_ajax_get_ranks_list() {
    global $user_ID, $blog_id, $wpdb;

    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
    $earned_ranks_shortcode_default_view 	= ( ! empty ( $badgeos_settings['earned_ranks_shortcode_default_view'] ) ) ? $badgeos_settings['earned_ranks_shortcode_default_view'] : 'list';
    
    // Setup our AJAX query vars
    $type               = isset( $_REQUEST['types'] )      ? $_REQUEST['types']      : false;
    $limit              = isset( $_REQUEST['limit'] )      ? $_REQUEST['limit']      : false;
    $offset             = isset( $_REQUEST['offset'] )     ? $_REQUEST['offset']     : false;
    $count              = isset( $_REQUEST['count'] )      ? $_REQUEST['count']      : false;
    $search             = isset( $_REQUEST['search'] )     ? $_REQUEST['search']     : false;
    $user_id            = isset( $_REQUEST['user_id'] )    ? $_REQUEST['user_id']    : get_current_user_id();
    $orderby            = isset( $_REQUEST['orderby'] )    ? $_REQUEST['orderby']    : 'rank_id';
    $order              = isset( $_REQUEST['order'] )      ? $_REQUEST['order']      : 'ASC';
    $show_title         = isset( $_REQUEST['show_title'] ) ? $_REQUEST['show_title'] : 'true';
    $show_thumb         = isset( $_REQUEST['show_thumb'] ) ? $_REQUEST['show_thumb'] : 'true';
    $show_description   = isset( $_REQUEST['show_description'] ) ? $_REQUEST['show_description'] : 'true';
    
    $image_width = isset( $_REQUEST['image_width'] ) ? $_REQUEST['image_width'] : '';
    $image_height = isset( $_REQUEST['image_height'] ) ? $_REQUEST['image_height'] : '';

    // Convert $type to properly support multiple rank types
    $earned_ranks_shortcode_default_view = !empty( $_REQUEST['default_view'] ) ? $_REQUEST['default_view'] : $earned_ranks_shortcode_default_view;

    if ( 'all' == $type ) {
        $type = badgeos_get_rank_types_slugs();
        // Drop steps from our list of "all" achievements
        $step_key = array_search( trim( $badgeos_settings['ranks_step_post_type'] ), $type );
        if ( $step_key )
            unset( $type[$step_key] );
    } else {
        $type = explode( ',', $type );
    }

    // Get the current user if one wasn't specified
    if( ! $user_id )
        $user_id = get_current_user_id();

    // Initialize our output and counters
    if( $offset > 0 ) {
        $ranks = '';
    } else {
        $ranks = '<div class="badgeos-arrange-buttons"><button class="list buttons '.($earned_ranks_shortcode_default_view=='list'?'selected':'').'"><i class="fa fa-bars"></i> '.__( 'List', 'badgeos' ).'</button><button class="grid buttons '.($earned_ranks_shortcode_default_view=='grid'?'selected':'').'"><i class="fa fa-th-large"></i> '.__( 'Grid', 'badgeos' ).'</button></div><ul class="ls_grid_container '.$earned_ranks_shortcode_default_view.'">';
    }

    $ranks_count = 0;

    // If we're polling all sites, grab an array of site IDs
    $sites = array( $blog_id );

    // Loop through each site (default is current site only)
    $query_count = 0;
    foreach( $sites as $site_blog_id ) {
        
        // Query Achievements
		$args = array(
			'post_type'      =>	$type,
			'orderby'        =>	$orderby,
			'order'          =>	$order,
			'posts_per_page' =>	$limit,
			'offset'         => $offset,
			'post_status'    => 'publish'
		);

		// Search
		if ( $search ) {
			$args[ 's' ] = $search;
		}
        //$count $user_id $image_width $image_height
		// Loop ranks
        $rank_posts = new WP_Query( $args );
        //print_r($rank_posts);
		$query_count += $rank_posts->found_posts;
		while ( $rank_posts->have_posts() ) : $rank_posts->the_post();
            
            $user_ranks = badgeos_user_has_a_rank( get_the_ID(), $user_id );
            $earned_this = 'badgeos_not_earned_rank badgeos_not_earned_rank_'.get_the_ID();
            if( count( $user_ranks ) > 0 ) {
                $earned_this = 'badgeos_already_earned_rank badgeos_already_earned_rank_'.get_the_ID();
            }
            $output = '<li><div id="badgeos-list-item-' . get_the_ID() . '" class="badgeos-list-item badgeos-list-item-'.get_the_ID().' '.$earned_this.'">';


            // Achievement Image
            if( $show_thumb == 'true' ) {
                $output .= '<div class="badgeos-item-image">';
                $output .= '<a href="' . get_permalink( get_the_ID() ) . '">' . badgeos_get_rank_image( get_the_ID(), $image_width, $image_height ) . '</a>';
                $output .= '</div><!-- .badgeos-item-image -->';
            }

            // Achievement Content
            $output .= '<div class="badgeos-item-detail">';

            if( $show_title == 'true' ) {
                // Achievement Title
                $output .= '<h2 class="badgeos-item-title"><a href="'.get_permalink( get_the_ID() ).'">'.get_the_title().'</a></h2>';
            }

            // Achievement Short Description
            if( $show_description == 'true' ) {
                $post = get_post( get_the_ID() );
                if( $post ) {
                    $output .= '<div class="badgeos-item-excerpt">';
                    $excerpt = get_the_excerpt();
                    $excerpt = !empty( $excerpt ) ? $excerpt : get_the_content();
                    $output .= wpautop( apply_filters( 'get_the_excerpt', $excerpt ) );
                    $output .= '</div><!-- .badgeos-item-excerpt -->';
                }
            }

            $output .= '</div><!-- .badgeos-item-description -->';
            $output .= apply_filters( 'badgeos_after_ranks_list_item', '', $rank );
            $output .= '</div></li><!-- .badgeos-ranks-list-item -->';

            $ranks .= $output;
            $ranks_count++;    
        endwhile;

        $ranks .= '</ul>';

        // Display a message for no results
        if ( intval( $query_count ) == 0 ) {
            $current = current( $type );
            // If we have exactly one achievement type, get its plural name, otherwise use "ranks"
            $post_type_plural = ( 1 == count( $type ) && ! empty( $current ) ) ? get_post_type_object( $current )->labels->name : __( 'achievements' , 'badgeos' );

            // Setup our completion message
            $ranks .= '<div class="badgeos-no-results">';
            $ranks .= '<p>' . sprintf( __( 'No completed %s to display at this time.', 'badgeos' ), strtolower( $post_type_plural ) ) . '</p>';
            $ranks .= '</div><!-- .badgeos-no-results -->';
        }

        if ( $blog_id != $site_blog_id ) {
            // Come back to current blog
            restore_current_blog();
        }

    }

    // Send back our successful response
    wp_send_json_success( array(
        'message'     => $ranks,
        'offset'      => $offset + $limit,
        'query_count' => $query_count,
        'badge_count' => $ranks_count,
        'attr'        => $_REQUEST
    ) );

}

/**
 * AJAX Helper for returning earned ranks
 *
 * @since 1.0.0
 * @return void
 */
function badgeos_ajax_get_earned_ranks() {
    global $user_ID, $blog_id, $wpdb;

    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
    $earned_ranks_shortcode_default_view 	= ( ! empty ( $badgeos_settings['earned_ranks_shortcode_default_view'] ) ) ? $badgeos_settings['earned_ranks_shortcode_default_view'] : 'list';

    // Setup our AJAX query vars
    $type       = isset( $_REQUEST['rank_type'] )  ? $_REQUEST['rank_type']  : false;
    $limit      = isset( $_REQUEST['limit'] )      ? $_REQUEST['limit']      : false;
    $offset     = isset( $_REQUEST['offset'] )     ? $_REQUEST['offset']     : false;
    $count      = isset( $_REQUEST['count'] )      ? $_REQUEST['count']      : false;
    $search     = isset( $_REQUEST['search'] )     ? $_REQUEST['search']     : false;
    $user_id    = isset( $_REQUEST['user_id'] )    ? $_REQUEST['user_id']    : get_current_user_id();
    $orderby    = isset( $_REQUEST['orderby'] )    ? $_REQUEST['orderby']    : 'rank_id';
    $order      = isset( $_REQUEST['order'] )      ? $_REQUEST['order']      : 'ASC';
    $show_title = isset( $_REQUEST['show_title'] ) ? $_REQUEST['show_title'] : 'true';
    $show_thumb = isset( $_REQUEST['show_thumb'] ) ? $_REQUEST['show_thumb'] : 'true';
    $show_description = isset( $_REQUEST['show_description'] ) ? $_REQUEST['show_description'] : 'true';
    $image_width = isset( $_REQUEST['image_width'] ) ? $_REQUEST['image_width'] : '';
    $image_height = isset( $_REQUEST['image_height'] ) ? $_REQUEST['image_height'] : '';
    
    // Convert $type to properly support multiple rank types
    $earned_ranks_shortcode_default_view = !empty( $_REQUEST['default_view'] ) ? $_REQUEST['default_view'] : $earned_ranks_shortcode_default_view;

    if ( 'all' == $type ) {
        $type = badgeos_get_rank_types_slugs();
        // Drop steps from our list of "all" achievements
        $step_key = array_search( trim( $badgeos_settings['ranks_step_post_type'] ), $type );
        if ( $step_key )
            unset( $type[$step_key] );
    } else {
        $type = explode( ',', $type );
    }

    // Get the current user if one wasn't specified
    if( ! $user_id )
        $user_id = get_current_user_id();

    // Initialize our output and counters
    if( $offset > 0 ) {
        $ranks = '';
    } else {
        $ranks = '<div class="badgeos-arrange-buttons"><button class="list buttons '.($earned_ranks_shortcode_default_view=='list'?'selected':'').'"><i class="fa fa-bars"></i> '.__( 'List', 'badgeos' ).'</button><button class="grid buttons '.($earned_ranks_shortcode_default_view=='grid'?'selected':'').'"><i class="fa fa-th-large"></i> '.__( 'Grid', 'badgeos' ).'</button></div><ul class="ls_grid_container '.$earned_ranks_shortcode_default_view.'">';
    }

    $ranks_count = 0;

    // If we're polling all sites, grab an array of site IDs
    $sites = array( $blog_id );

    // Loop through each site (default is current site only)
    $query_count = 0;
    foreach( $sites as $site_blog_id ) {

        $qry = "SELECT * FROM ".$wpdb->prefix."badgeos_ranks WHERE user_id='".$user_id."'";
        $total_qry = "SELECT count(ID) as total FROM ".$wpdb->prefix."badgeos_ranks WHERE user_id='".$user_id."'";

        if( is_array( $type ) && count( $type ) > 0 ) {
            $qry .= " and rank_type in ('".implode( "', '", $type )."') ";
            $total_qry .= " and rank_type in ('".implode( "', '", $type )."') ";
        }

        if ( $search ) {
            $qry .= " and rank_title like '%".$search."%' ";
            $total_qry .= " and rank_title like '%".$search."%' ";
        }

        $query_count += intval( $wpdb->get_var( $total_qry ) );
        if( !empty( $orderby ) ) {
            if( !empty( $order ) ) {
                $qry .= " ORDER BY ".$orderby." ".$order;
            } else {
                $qry .= " ORDER BY ".$orderby." ASC";
            }
        }

        $qry .= " limit ".$offset.", ".$limit;
        $user_ranks = $wpdb->get_results( $qry );

        // Loop ranks
        foreach ( $user_ranks as $rank ) {

            $output = '<li><div id="badgeos-list-item-' . $rank->rank_id . '" class="badgeos-list-item badgeos-earned-rank-item badgeos-earned-rank-item-'.$rank->rank_id.'">';

            // Achievement Image
            if( $show_thumb == 'true' ) {
                $output .= '<div class="badgeos-item-image">';
                $output .= '<a href="' . get_permalink( $rank->rank_id ) . '">' . badgeos_get_rank_image( $rank->rank_id, $image_width, $image_height ) . '</a>';
                $output .= '</div><!-- .badgeos-item-image -->';
            }

            // Achievement Content
            $output .= '<div class="badgeos-item-detail">';

            if( $show_title == 'true' ) {
                $title = get_the_title($rank->rank_id);
	            $title = $rank->rank_title;
                // Achievement Title
                $output .= '<h2 class="badgeos-item-title"><a href="'.get_permalink( $rank->rank_id ).'">'.$title.'</a></h2>';
            }

            // Achievement Short Description
            if( $show_description == 'true' ) {
                $post = get_post( $rank->rank_id );
                if( $post ) {
                    $output .= '<div class="badgeos-item-excerpt">';
                    $excerpt = !empty( $post->post_excerpt ) ? $post->post_excerpt : $post->post_content;
                    $output .= wpautop( apply_filters( 'get_the_excerpt', $excerpt ) );
                    $output .= '</div><!-- .badgeos-item-excerpt -->';
                }
            }

            $output .= apply_filters( 'badgeos_after_earned_ranks', '', $rank );
            $output .= '</div></li><!-- .badgeos-list-item -->';

            $ranks .= $output;
            $ranks_count++;
        }

        $ranks .= '</ul>';

        // Display a message for no results
        if ( intval( $query_count ) == 0 ) {
            $current = current( $type );
            // If we have exactly one achievement type, get its plural name, otherwise use "ranks"
            $post_type_plural = ( 1 == count( $type ) && ! empty( $current ) ) ? get_post_type_object( $current )->labels->name : __( 'achievements' , 'badgeos' );

            // Setup our completion message
            $ranks .= '<div class="badgeos-no-results">';
            $ranks .= '<p>' . sprintf( __( 'No completed %s to display at this time.', 'badgeos' ), strtolower( $post_type_plural ) ) . '</p>';
            $ranks .= '</div><!-- .badgeos-no-results -->';
        }

        if ( $blog_id != $site_blog_id ) {
            // Come back to current blog
            restore_current_blog();
        }

    }

    // Send back our successful response
    wp_send_json_success( array(
        'message'     => $ranks,
        'offset'      => $offset + $limit,
        'query_count' => $query_count,
        'badge_count' => $ranks_count,
        'attr'        => $_REQUEST
    ) );

}

/**
 * AJAX Helper for returning earned achievements
 *
 * @since 1.0.0
 * @return void
 */
function badgeos_ajax_get_earned_achievements() {
    global $user_ID, $blog_id, $wpdb;

    // Convert $type to properly support multiple achievement types
    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
    $earned_achievements_shortcode_default_view 	= ( ! empty ( $badgeos_settings['earned_achievements_shortcode_default_view'] ) ) ? $badgeos_settings['earned_achievements_shortcode_default_view'] : 'list';

    // Setup our AJAX query vars
    $type       = isset( $_REQUEST['type'] )       ? $_REQUEST['type']       : false;
    $limit      = isset( $_REQUEST['limit'] )      ? $_REQUEST['limit']      : false;
    $offset     = isset( $_REQUEST['offset'] )     ? $_REQUEST['offset']     : false;
    $count      = isset( $_REQUEST['count'] )      ? $_REQUEST['count']      : false;
    $search     = isset( $_REQUEST['search'] )     ? $_REQUEST['search']     : false;
    $user_id    = isset( $_REQUEST['user_id'] )    ? $_REQUEST['user_id']    : get_current_user_id();
    $orderby    = isset( $_REQUEST['orderby'] )    ? $_REQUEST['orderby']    : 'ID';
    $order      = isset( $_REQUEST['order'] )      ? $_REQUEST['order']      : 'ASC';
    $wpms       = isset( $_REQUEST['wpms'] )       ? $_REQUEST['wpms']       : false;
    $include    = isset( $_REQUEST['include'] )    ? $_REQUEST['include']    : array();
    $exclude    = isset( $_REQUEST['exclude'] )    ? $_REQUEST['exclude']    : array();

    $show_title = isset( $_REQUEST['show_title'] ) ? $_REQUEST['show_title'] : 'true';
    $show_thumb = isset( $_REQUEST['show_thumb'] ) ? $_REQUEST['show_thumb'] : 'true';
    $show_description = isset( $_REQUEST['show_description'] ) ? $_REQUEST['show_description'] : 'true';
    $image_width = isset( $_REQUEST['image_width'] ) ? $_REQUEST['image_width'] : '';
    $image_height = isset( $_REQUEST['image_height'] ) ? $_REQUEST['image_height'] : '';
    $earned_achievements_shortcode_default_view = !empty( $_REQUEST['default_view'] ) ? $_REQUEST['default_view'] : $earned_achievements_shortcode_default_view;

    if ( 'all' == $type ) {
        $type = badgeos_get_achievement_types_slugs();
        // Drop steps from our list of "all" achievements
        $step_key = array_search( trim( $badgeos_settings['achievement_step_post_type'] ), $type );
        if ( $step_key )
            unset( $type[$step_key] );
    } else {
        $type = explode( ',', $type );
    }

    // Get the current user if one wasn't specified
    if( ! $user_id )
        $user_id = $user_ID;

    // Initialize our output and counters
    if( $offset > 0 ) {
        $achievements = '';
    } else {
        $achievements = '<div class="badgeos-arrange-buttons"><button class="list buttons '.($earned_achievements_shortcode_default_view=='list'?'selected':'').'"><i class="fa fa-bars"></i> '.__( 'List', 'badgeos' ).'</button><button class="grid buttons '.($earned_achievements_shortcode_default_view=='grid'?'selected':'').'"><i class="fa fa-th-large"></i> '.__( 'Grid', 'badgeos' ).'</button></div><ul class="ls_grid_container '.$earned_achievements_shortcode_default_view.'">';
    }

    $achievement_count = 0;

    // If we're polling all sites, grab an array of site IDs
    if( $wpms && $wpms != 'false' )
        $sites = badgeos_get_network_site_ids();
    else
        $sites = array( $blog_id );

    // Loop through each site (default is current site only)
    $query_count = 0;
    foreach( $sites as $site_blog_id ) {

        $qry = "SELECT * FROM ".$wpdb->prefix."badgeos_achievements WHERE site_id='".$site_blog_id."'";
        $total_qry = "SELECT count(ID) as total FROM ".$wpdb->prefix."badgeos_achievements WHERE site_id='".$site_blog_id."'";

        if( is_array( $type ) && count( $type ) > 0 ) {
            $qry .= " and post_type in ('".implode( "', '", $type )."') ";
            $total_qry .= " and post_type in ('".implode( "', '", $type )."') ";
        }

        $qry .= " and user_id = '".$user_id."' ";
        $total_qry .= " and user_id = '".$user_id."' ";

        if ( $search ) {
            $qry .= " and achievement_title like '%".$search."%' ";
            $total_qry .= " and achievement_title like '%".$search."%' ";
        }

        // Build $include array
        if ( !empty( $include ) ) {
            $qry .= " and ID in (".$include.") ";
            $total_qry .= " and ID in (".$include.") ";
        }

        // Build $exclude array
        if ( !empty( $exclude ) ) {
            $qry .= " and ID not in (".$exclude.") ";
            $total_qry .= " and ID not in (".$exclude.") ";
        }
        $query_count += intval( $wpdb->get_var( $total_qry ) );
        if( !empty( $orderby ) ) {
            if( !empty( $order ) ) {
                $qry .= " ORDER BY ".$orderby." ".$order;
            } else {
                $qry .= " ORDER BY ".$orderby." ASC";
            }
        }

        $qry .= " limit ".$offset.", ".$limit;
        $user_achievements = $wpdb->get_results( $qry );

        // Loop Achievements
        foreach ( $user_achievements as $achievement ) {

            $output = '<li><div id="badgeos-earned-list-item-'.$achievement->ID.'" class="badgeos-list-item badgeos-earned-list-item badgeos-earned-list-item-'.$achievement->ID.' badgeos-earned-list-item-'.$achievement->ID.'-'.$user_id.'">';

            $open_badge_enable_baking       	= badgeos_get_option_open_badge_enable_baking($achievement->ID);
            $permalink                          = get_permalink( $achievement->ID );
            if( $open_badge_enable_baking ) {
                $badgeos_evidence_page_id	= get_option( 'badgeos_evidence_url' );
                $badgeos_evidence_url 		= get_permalink( $badgeos_evidence_page_id );
                $badgeos_evidence_url 		= add_query_arg( 'bg', $achievement->ID, $badgeos_evidence_url );
                $badgeos_evidence_url  		= add_query_arg( 'eid', $achievement->entry_id, $badgeos_evidence_url );
                $badgeos_evidence_url  		= add_query_arg( 'uid', $achievement->user_id, $badgeos_evidence_url );
                $permalink = $badgeos_evidence_url;
            }

            // Achievement Image
            if( $show_thumb=='true' ) {
                $output .= '<div class="badgeos-item-image">';

                $image_size = 'boswp-badgeos-achievement';
                if( !empty( $image_width ) || !empty( $image_height) ) {
                    $image_size = array( $image_width,  $image_height );
                }

                $output .= '<a href="'.$permalink.'">' . badgeos_get_achievement_post_thumbnail( $achievement->ID, $image_size ) . '</a>';
                $output .= '</div><!-- .badgeos-item-image -->';
            }

            // Achievement Content
            $output .= '<div class="badgeos-item-detail">';

            // Achievement Title
            if( $show_title == 'true' ) {
                $title = get_the_title( $achievement->ID );
                if( empty( $title ) )
                    $title = $achievement->achievement_title;

                $output .= '<h2 class="badgeos-item-title"><a href="'.$permalink.'">'.$title.'</a></h2>';
            }

            // Achievement Short Description
            if( $show_description=='true' ) {
                $post = get_post( $achievement->ID );
                if( $post ) {
                    $output .= '<div class="badgeos-item-excerpt">';
                    $excerpt = !empty( $post->post_excerpt ) ? $post->post_excerpt : $post->post_content;
                    $output .= wpautop( apply_filters( 'get_the_excerpt', $excerpt ) );
                    $output .= '</div>';
                }
            }

            $output .= apply_filters( 'badgeos_after_earned_achievement', '', $achievement );
            $output .= '</div>';
            $output .= '</div></li>';

            $achievements .= $output;
            $achievement_count++;
        }

        if( $offset == 0 ) {
            $achievements .= '</ul>';
        }

        // Display a message for no results
        if ( intval( $query_count ) == 0 ) {
            $current = current( $type );
            // If we have exactly one achievement type, get its plural name, otherwise use "achievements"
            $post_type_plural = ( 1 == count( $type ) && ! empty( $current ) ) ? get_post_type_object( $current )->labels->name : __( 'achievements' , 'badgeos' );

            // Setup our completion message
            $achievements .= '<div class="badgeos-no-results">';
            $achievements .= '<p>' . sprintf( __( 'No %s to display at this time.', 'badgeos' ), strtolower( $post_type_plural ) ) . '</p>';
            $achievements .= '</div><!-- .badgeos-no-results -->';
        }

        if ( $blog_id != $site_blog_id ) {
            // Come back to current blog
            restore_current_blog();
        }

    }

    // Send back our successful response
    wp_send_json_success( array(
        'message'     => $achievements,
        'offset'      => $offset + $limit,
        'query_count' => $query_count,
        'badge_count' => $achievement_count,
        'attr'        => $_REQUEST
	
    ) );
}

/**
 * AJAX Helper for returning achievements
 *
 * @since 1.0.0
 * @return void
 */
function badgeos_ajax_get_achievements() {
	global $user_ID, $blog_id;

    // Convert $type to properly support multiple achievement types
    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
    $achievement_list_default_view 	= ( ! empty ( $badgeos_settings['achievement_list_shortcode_default_view'] ) ) ? $badgeos_settings['achievement_list_shortcode_default_view'] : 'list';

    $earned_ids = [];
    // Setup our AJAX query vars
	$type       = isset( $_REQUEST['type'] )       ? $_REQUEST['type']       : false;
	$limit      = isset( $_REQUEST['limit'] )      ? $_REQUEST['limit']      : false;
	$offset     = isset( $_REQUEST['offset'] )     ? $_REQUEST['offset']     : false;
	$count      = isset( $_REQUEST['count'] )      ? $_REQUEST['count']      : false;
	$filter     = isset( $_REQUEST['filter'] )     ? $_REQUEST['filter']     : false;
	$search     = isset( $_REQUEST['search'] )     ? $_REQUEST['search']     : false;
	$user_id    = isset( $_REQUEST['user_id'] )    ? $_REQUEST['user_id']    : false;
	$orderby    = isset( $_REQUEST['orderby'] )    ? $_REQUEST['orderby']    : false;
	$order      = isset( $_REQUEST['order'] )      ? $_REQUEST['order']      : false;
	$wpms       = isset( $_REQUEST['wpms'] )       ? $_REQUEST['wpms']       : false;
	$include    = isset( $_REQUEST['include'] )    ? $_REQUEST['include']    : array();
	$exclude    = isset( $_REQUEST['exclude'] )    ? $_REQUEST['exclude']    : array();
	$meta_key   = isset( $_REQUEST['meta_key'] )   ? $_REQUEST['meta_key']   : '';
    $meta_value = isset( $_REQUEST['meta_value'] ) ? $_REQUEST['meta_value'] : '';
    $show_title = isset( $_REQUEST['show_title'] ) ? $_REQUEST['show_title'] : 'true';
    $show_thumb = isset( $_REQUEST['show_thumb'] ) ? $_REQUEST['show_thumb'] : 'true';
    $show_description = isset( $_REQUEST['show_description'] ) ? $_REQUEST['show_description'] : 'true';
    $show_steps = isset( $_REQUEST['show_steps'] ) ? $_REQUEST['show_steps'] : 'true';

    $image_width = isset( $_REQUEST['image_width'] ) ? $_REQUEST['image_width'] : '';
    $image_height = isset( $_REQUEST['image_height'] ) ? $_REQUEST['image_height'] : '';

    $achievement_list_default_view = !empty( $_REQUEST['default_view'] ) ? $_REQUEST['default_view'] : $achievement_list_default_view;

    if ( 'all' == $type ) {
		$type = badgeos_get_achievement_types_slugs();
		// Drop steps from our list of "all" achievements
		$step_key = array_search( trim( $badgeos_settings['achievement_step_post_type'] ), $type );
		if ( $step_key )
			unset( $type[$step_key] );
	} else {
		$type = explode( ',', $type );
	}

	// Get the current user if one wasn't specified
	if( ! $user_id )
		$user_id = $user_ID;

	// Build $include array
    if ( !is_array( $include ) && !empty( $include ) ) {
		$include = explode( ',', $include );
	}

	// Build $exclude array
	if ( !is_array( $exclude ) && !empty($exclude) ) {
		$exclude = explode( ',', $exclude );
	}

    // Initialize our output and counters
    if( $offset > 0 ) {
        $achievements = '';
    } else {
        $achievements = '<div class="badgeos-arrange-buttons"><button class="list buttons '.($achievement_list_default_view=='list'?'selected':'').'"><i class="fa fa-bars"></i> '.__( 'List', 'badgeos' ).'</button><button class="grid buttons '.($achievement_list_default_view=='grid'?'selected':'').'""><i class="fa fa-th-large"></i> '.__( 'Grid', 'badgeos' ).'</button></div><ul class="ls_grid_container '.$achievement_list_default_view.'">';
    }

    $achievement_count = 0;
    $query_count = 0;

    // Grab our hidden badges (used to filter the query)
	$hidden = badgeos_get_hidden_achievement_ids( $type );

	// If we're polling all sites, grab an array of site IDs
	if( $wpms && $wpms != 'false' )
		$sites = badgeos_get_network_site_ids();
	// Otherwise, use only the current site
	else
		$sites = array( $blog_id );

	// Loop through each site (default is current site only)
	foreach( $sites as $site_blog_id ) {

		// If we're not polling the current site, switch to the site we're polling
		if ( $blog_id != $site_blog_id ) {
			switch_to_blog( $site_blog_id );
		}

		// Grab our earned badges (used to filter the query)
		$earned_ids = badgeos_get_user_earned_achievement_ids( $user_id, $type );

		// Query Achievements
		$args = array(
			'post_type'      =>	$type,
			'orderby'        =>	$orderby,
			'order'          =>	$order,
			'posts_per_page' =>	$limit,
			'offset'         => $offset,
			'post_status'    => 'publish',
			'post__not_in'   => $hidden
		);

        if( ! is_array( $args[ 'post__not_in' ] ) ) {
            $args[ 'post__not_in' ] = [];
        }

        // Filter - query completed or non completed achievements
		if ( $filter == 'completed' ) {
			$args[ 'post__in' ] = array_merge( array( 0 ), $earned_ids );
		}elseif( $filter == 'not-completed' ) {
			$args[ 'post__not_in' ] = array_merge( $hidden, $earned_ids );
		}

		if ( '' !== $meta_key && '' !== $meta_value ) {
			$args[ 'meta_key' ] = $meta_key;
			$args[ 'meta_value' ] = $meta_value;
		}

        // Include certain achievements
        if ( ! empty( $include ) ) {
            $args[ 'post__not_in' ] = array_diff( $args[ 'post__not_in' ], $include );
            if( ! is_array( $args[ 'post__in' ] ) ) {
                $args[ 'post__in' ] = [];
            }

            $args[ 'post__in' ] = array_merge( array( 0 ), array_diff( $include, $args[ 'post__in' ] ) );
        }

		// Exclude certain achievements
		if ( !empty( $exclude ) ) {
			$args[ 'post__not_in' ] = array_merge( $args[ 'post__not_in' ], $exclude );
		}

		// Search
		if ( $search ) {
			$args[ 's' ] = $search;
		}

		// Loop Achievements
		$achievement_posts = new WP_Query( $args );
		$query_count += $achievement_posts->found_posts;
		while ( $achievement_posts->have_posts() ) : $achievement_posts->the_post();
            $achievements .= '<li id="badgeos-achivements-list-item-'.get_the_ID().'" class="badgeos-achivements-list-item badgeos-achivements-list-item-'.get_the_ID().'">'.badgeos_render_achievement( get_the_ID(), $show_title, $show_thumb, $show_description, $show_steps, $image_width, $image_height ).'</li>';
			$achievement_count++;
		endwhile;

		// Sanity helper: if we're filtering for complete and we have no
		// earned achievements, $achievement_posts should definitely be false
		/*if ( 'completed' == $filter && empty( $earned_ids ) )
			$achievements = '';*/

		// Display a message for no results
        if ( $query_count == 0 ) {
			$current = current( $type );
			// If we have exactly one achievement type, get its plural name, otherwise use "achievements"
			$post_type_plural = ( 1 == count( $type ) && ! empty( $current ) ) ? get_post_type_object( $current )->labels->name : __( 'achievements' , 'badgeos' );

			// Setup our completion message
			$achievements .= '<div class="badgeos-no-results">';
			if ( 'completed' == $filter ) {
				$achievements .= '<p>' . sprintf( __( 'No completed %s to display at this time.', 'badgeos' ), strtolower( $post_type_plural ) ) . '</p>';
			}else{
				$achievements .= '<p>' . sprintf( __( 'No %s to display at this time.', 'badgeos' ), strtolower( $post_type_plural ) ) . '</p>';
			}
			$achievements .= '</div><!-- .badgeos-no-results -->';
		}

		if ( $blog_id != $site_blog_id ) {
			// Come back to current blog
			restore_current_blog();
		}
	}

    if( $offset == 0 ) {
        $achievements .= '</ul>';
    }

    // Send back our successful response
	wp_send_json_success( array(
		'message'     => $achievements,
		'offset'      => $offset + $limit,
		'query_count' => $query_count,
		'badge_count' => $achievement_count,
		'type'        => $earned_ids,
        'attr'        => $_REQUEST
	) );
}

/**
 * AJAX Helper for selecting users in Shortcode Embedder
 *
 * @since 1.4.0
 */
function badgeos_ajax_badgeos_get_users_list() {

    // If no query was sent, die here
    if ( ! isset( $_REQUEST['q'] ) ) {
        die();
    }

    global $wpdb;

    // Pull back the search string
    $search = esc_sql( $wpdb->esc_like( $_REQUEST['q'] ) );

    $sql = "SELECT ID as id, user_login as label, ID as value FROM {$wpdb->users}";

    // Build our query
    if ( !empty( $search ) ) {
        $sql .= " WHERE user_login LIKE '%".$_REQUEST['q']."%'";
    }

    // Fetch our results (store as associative array)
    $results = $wpdb->get_results( $sql." limit 100 ", 'ARRAY_A' );

    // Return our results
    wp_send_json( $results );
}

/**
 * AJAX Helper for selecting users in Shortcode Embedder
 *
 * @since 1.4.0
 */
function badgeos_ajax_get_users() {

	// If no query was sent, die here
	if ( ! isset( $_REQUEST['q'] ) ) {
		die();
	}

	global $wpdb;

	// Pull back the search string
    $search = esc_sql( $wpdb->esc_like( $_REQUEST['q'] ) );

    $sql = "SELECT ID as id, user_login as text FROM {$wpdb->users}";

	// Build our query
	if ( !empty( $search ) ) {
        $sql .= " WHERE user_login LIKE '%".$_REQUEST['q']."%'";
	}

	// Fetch our results (store as associative array)
    $results = $wpdb->get_results( $sql." limit 100 ", 'ARRAY_A' );

	// Return our results
    wp_send_json( array( "results"=> $results ) );
}

/**
 * AJAX Helper for selecting posts in Shortcode Embedder
 *
 * @since 1.4.0
 */
function badgeos_ajax_get_achievements_select2() {
	global $wpdb;

    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
	// Pull back the search string
    $search = isset( $_REQUEST['q'] ) ? $wpdb->esc_like( $_REQUEST['q'] ) : '';
	$achievement_types = isset( $_REQUEST['post_type'] ) && 'all' !== $_REQUEST['post_type']
		? array( esc_sql( $_REQUEST['post_type'] ) )
		: array_diff( badgeos_get_achievement_types_slugs(), array( trim( $badgeos_settings['achievement_step_post_type'] ) ) );
	$post_type = sprintf( 'AND p.post_type IN(\'%s\')', implode( "','", $achievement_types ) );

	$results = $wpdb->get_results( $wpdb->prepare(
		"
		SELECT p.ID, p.post_title
		FROM   $wpdb->posts AS p 
		JOIN $wpdb->postmeta AS pm
		ON p.ID = pm.post_id
		WHERE  p.post_title LIKE %s
		       {$post_type}
		       AND p.post_status = 'publish'
		       AND pm.meta_key LIKE %s
		       AND pm.meta_value LIKE %s
		",
		"%%{$search}%%",
		"%%_badgeos_hidden%%",
		"%%show%%"
	) );

	// Return our results
	wp_send_json_success( $results );
}

/**
 * AJAX Helper for selecting achievement types in Shortcode Embedder
 *
 * @since 1.4.0
 */
function badgeos_ajax_get_achievement_types() {

	// If no query was sent, die here
	if ( ! isset( $_REQUEST['q'] ) ) {
		die();
	}

    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
    $achievement_types = array_diff( badgeos_get_achievement_types_slugs(), array( trim( $badgeos_settings['achievement_step_post_type'] ) ) ); 
    $matches = preg_grep( "/{$_REQUEST['q']}/", $achievement_types );
	$found = array_map( 'get_post_type_object', $matches );

	// Include an "all" option as the first option
	array_unshift( $found, (object) array( 'name' => 'all', 'label' => __( 'All', 'badgeos') ) );

	// Return our results
	wp_send_json_success( $found );
}

function delete_badgeos_log_entries() {

    $action = ( isset( $_POST['action'] ) ? $_POST['action'] : '' );
    if( $action !== 'delete_badgeos_log_entries' ) {
        exit;
    }
    if ( ! wp_next_scheduled ( 'cron_delete_log_entries' ) ) {
        wp_schedule_single_event( time(), 'cron_delete_log_entries' );
    }
}
add_action( 'wp_ajax_delete_badgeos_log_entries', 'delete_badgeos_log_entries' );

function cron_delete_log_entries() {
    @set_time_limit( 3600 );

    global $wpdb;

    $badgeos_log_entry = $wpdb->get_results( "SELECT `ID` FROM $wpdb->posts WHERE post_type = 'badgeos-log-entry';" );
    if( is_array( $badgeos_log_entry ) && !empty( $badgeos_log_entry ) && !is_null( $badgeos_log_entry ) ) {
        foreach( $badgeos_log_entry as $log_entry ) {
            $wpdb->query( "DELETE FROM $wpdb->posts WHERE ID = '$log_entry->ID';" );
            $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE post_id = '$log_entry->ID';" );
        }
    }
}
add_action( 'cron_delete_log_entries', 'cron_delete_log_entries' );