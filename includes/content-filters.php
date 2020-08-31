<?php
/**
 * Content Filters
 *
 * @package BadgeOS
 * @subpackage Front-end
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */
/**
 * Add filters to remove stuff from our singular pages and add back in how we want it
 *
 * @since 1.0.0
 * @return null
 */
function badgeos_do_single_filters() {
	// check we're in the right place
	badgeos_is_main_loop();
	// enqueue our stylesheet
	wp_enqueue_style( 'badgeos-single' );
	// no worries.. we'll add back later
	remove_filter( 'the_content', 'wpautop' );
	// filter out the post title
	// add_filter( 'the_title', 'badgeos_remove_to_reformat_entries_title', 10, 2 );
	// and filter out the post image
	add_filter( 'post_thumbnail_html', 'badgeos_remove_to_reformat_entries_title', 10, 2 );
}
add_action( 'wp_enqueue_scripts', 'badgeos_do_single_filters' );

/**
 * Filter out the post title/post image and add back (later) how we want it
 *
 * @since 1.0.0
 * @param  string  $html The page content prior to filtering
 * @param  integer $id   The page id
 * @return string        The page content after being filtered
 */
function badgeos_remove_to_reformat_entries_title( $html = '', $id = 0 ) {

	// remove, but only on the main loop!
	if ( badgeos_is_main_loop( $id ) )
		return '';

	// nothing to see here... move along
	return $html;
}

/**
 * Filter badge content to add our removed content back
 *
 * @since  1.0.0
 * @param  string $content The page content
 * @return string          The page content after reformat
 */
function badgeos_reformat_entries( $content ) {

	wp_enqueue_style( 'badgeos-front' );

	$badge_id = get_the_ID();

	// filter, but only on the main loop!
	if ( !badgeos_is_main_loop( $badge_id ) )
		return wpautop( $content );

	// now that we're where we want to be, tell the filters to stop removing
	$GLOBALS['badgeos_reformat_content'] = true;

	// do badge title markup
	$title = '<h1 class="badge-title">'. get_the_title() .'</h1>';

	// check if user has earned this Achievement, and add an 'earned' class
	$class = badgeos_get_user_achievements( array( 'achievement_id' => absint( $badge_id ) ) ) ? ' earned' : '';

	// wrap our content, add the thumbnail and title and add wpautop back
	$newcontent = '<div class="achievement-wrap'. $class .'">';

	// Check if current user has earned this achievement
	$newcontent .= badgeos_render_earned_achievement_text( $badge_id, get_current_user_id() );

    $badge_image = badgeos_get_achievement_post_thumbnail( $badge_id, 'boswp-badgeos-achievement' );
	
	$achievements = badgeos_get_user_achievements( array( 'achievement_id' => absint( $badge_id ) ) );
	$class = count( $achievements ) > 0 ? ' earned' : '';
	
	if( trim( $class ) == 'earned' ) {
		$achievement = $achievements[ 0 ];
		$badge_image = apply_filters( 'badgeos_profile_achivement_image', $badge_image, $achievement  );
	}
					
	$newcontent .= '<div class="alignleft badgeos-item-image">'. $badge_image .'</div>';
	$newcontent .= $title;

	// Points for badge
	$newcontent .= badgeos_achievement_points_markup();
	$newcontent .= wpautop( $content );

	// Include output for our steps
	$newcontent .= badgeos_get_required_achievements_for_achievement_list( $badge_id );

	// Include achievement earners, if this achievement supports it
	if ( $show_earners = get_post_meta( $badge_id, '_badgeos_show_earners', true ) )
		$newcontent .= badgeos_get_achievement_earners_list( $badge_id );

	$newcontent .= '</div><!-- .achievement-wrap -->';

	// Ok, we're done reformating
	$GLOBALS['badgeos_reformat_content'] = false;

	return $newcontent;
}
add_filter( 'the_content', 'badgeos_reformat_entries', 9 );

/**
 * Helper function tests that we're in the main loop
 *
 * @since  1.0.0
 * @param  bool|integer $id The page id
 * @return boolean     A boolean determining if the function is in the main loop
 */
function badgeos_is_main_loop( $id = false ) {

	$slugs = badgeos_get_achievement_types_slugs();
	// only run our filters on the badgeos singular pages
	if ( is_admin() || empty( $slugs ) || !is_singular( $slugs ) )
		return false;
	// w/o id, we're only checking template context
	if ( !$id )
		return true;

	// Checks several variables to be sure we're in the main loop (and won't effect things like post pagination titles)
	return ( ( $GLOBALS['post']->ID == $id ) && in_the_loop() && empty( $GLOBALS['badgeos_reformat_content'] ) );
}

/**
 * Gets achievement's required steps and returns HTML markup for these steps
 *
 * @since  1.0.0
 * @param  integer $achievement_id The given achievement's post ID
 * @param  integer $user_id        A given user's ID
 * @return string                  The markup for our list
 */
function badgeos_get_required_achievements_for_achievement_list( $achievement_id = 0, $user_id = 0 ) {

	// Grab the current post ID if no achievement_id was specified
	if ( ! $achievement_id ) {
		global $post;
		$achievement_id = $post->ID;
	}

	// Grab the current user's ID if none was specifed
	if ( ! $user_id )
		$user_id = wp_get_current_user()->ID;

	// Grab our achievement's required steps
	$steps = badgeos_get_required_achievements_for_achievement( $achievement_id );

	// Return our markup output
	return badgeos_get_required_achievements_for_achievement_list_markup( $steps, $user_id );

}

/**
 * Generate HTML markup for an achievement's required steps
 *
 * This will generate an unorderd list (<ul>) if steps are non-sequential
 * and an ordered list (<ol>) if steps require sequentiality.
 *
 * @since  1.0.0
 * @param  array   $steps           An achievement's required steps
 * @param  integer $achievement_id  The given achievement's ID
 * @param  integer $user_id         The given user's ID
 * @return string                   The markup for our list
 */
function badgeos_get_required_achievements_for_achievement_list_markup( $steps = array(), $achievement_id = 0, $user_id = 0 ) {

	// If we don't have any steps, or our steps aren't an array, return nothing
	if ( ! $steps || ! is_array( $steps ) )
		return null;

	// Grab the current post ID if no achievement_id was specified
	if ( ! $achievement_id ) {
		global $post;
		$achievement_id = $post->ID;
	}

	$count = count( $steps );

	// If we have no steps, return nothing
	if ( ! $count )
		return null;

	// Grab the current user's ID if none was specifed
	if ( ! $user_id )
		$user_id = wp_get_current_user()->ID;

	// Setup our variables
	$output = $step_output = '';
	$container = badgeos_is_achievement_sequential() ? 'ol' : 'ul';

	// Concatenate our output
	foreach ( $steps as $step ) {

		// check if user has earned this Achievement, and add an 'earned' class
		$earned_status = badgeos_get_user_achievements( array(
			'user_id' => absint( $user_id ),
			'achievement_id' => absint( $step->ID ),
			'since' => absint( badgeos_achievement_last_user_activity( $achievement_id, $user_id ) )
		) ) ? 'user-has-earned' : 'user-has-not-earned';

		// get step title and if it doesn't have a title get the step trigger type post-meta
		$title = !empty( $step->post_title ) ? $step->post_title : get_post_meta( $step->ID, '_badgeos_trigger_type', true );
		$step_output .= '<li class="'. apply_filters( 'badgeos_step_class', $earned_status, $step ) .'">'. apply_filters( 'badgeos_step_title_display', $title, $step ) . '</li>';
	}
	$post_type_object = get_post_type_object( $step->post_type );

	$output .= '<h4>' . apply_filters( 'badgeos_steps_heading', sprintf( __( '%1$d Required %2$s', 'badgeos' ), $count, $post_type_object->labels->name ), $steps ) . '</h4>';
	$output .= '<' . $container .' class="badgeos-required-achievements">';
	$output .= $step_output;
	$output .= '</'. $container .'><!-- .badgeos-required-achievements -->';

	// Return our output
	return $output;

}

/**
 * Filter our step titles to link to achievements and achievement type archives
 *
 * @since  1.0.0
 * @param  string $title Our step title
 * @param  object $step  Our step's post object
 * @return string        Our potentially udated title
 */
function badgeos_step_link_title_to_achievement( $title = '', $step = null ) {

	// Grab our step requirements
	$step_requirements = badgeos_get_step_requirements( $step->ID );

	// Setup a URL to link to a specific achievement or an achievement type
	if ( ! empty( $step_requirements['achievement_post'] ) )
		$url = get_permalink( $step_requirements['achievement_post'] );
	// elseif ( ! empty( $step_requirements['achievement_type'] ) )
	//  $url = get_post_type_archive_link( $step_requirements['achievement_type'] );

	// If we have a URL, update the title to link to it
	if ( isset( $url ) && ! empty( $url ) )
		$title = '<a href="' . esc_url( $url ) . '">' . $title . '</a>';

	return $title;
}
add_filter( 'badgeos_step_title_display', 'badgeos_step_link_title_to_achievement', 10, 2 );

/**
 * Generate markup for an achievement's points output
 *
 * @since  1.0.0
 * @param  integer $achievement_id The given achievment's ID
 * @return string                  The HTML markup for our points
 */
function badgeos_achievement_points_markup( $achievement_id = 0 ) {

	// Grab the current post ID if no achievement_id was specified
	if ( ! $achievement_id ) {
		global $post;
		$achievement_id = $post->ID;
	}
	
	$points = get_post_meta( $achievement_id, '_badgeos_points', true );
	
	if( isset( $points ) &&  is_array( $points ) && count( $points ) > 0 ) {
		$point_value 	= $points['_badgeos_points'];
		$points_type 	= $points['_badgeos_points_type'];

		if( intval( $points_type ) > 0 ) {
			$points_type_lbl = badgeos_points_type_display_title( $points_type );
			return '<div class="badgeos-item-points badgeos-item-points-'.$points_type.' badgeos-item-points-'.$achievement_id.' badgeos-item-points-'.$points_type.'-'.$achievement_id.'">' . sprintf( __( '<span>%d</span> %s', 'badgeos' ), $point_value, $points_type_lbl) . '</div>';
		} else {
			return '<div class="badgeos-item-points badgeos-item-points-'.$points_type.' badgeos-item-points-'.$achievement_id.' badgeos-item-points-'.$points_type.'-'.$achievement_id.'"><span>0</span> '.__( 'Points', 'badgeos' ).'</div>';
		}
	} else {
        $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
        $default_point_type 	= ( ! empty ( $badgeos_settings['default_point_type'] ) ) ? $badgeos_settings['default_point_type'] : '';
        $point_type = badgeos_points_type_display_title( $default_point_type );
        if( !empty( $point_type ) ) {
			return '<div class="badgeos-item-points badgeos-item-points-'.$points_type.' badgeos-item-points-'.$achievement_id.' badgeos-item-points-'.$points_type.'-'.$achievement_id.'"><span>0</span> '.$point_type.'</div>';
        } else {
			return '<div class="badgeos-item-points badgeos-item-points-'.$points_type.' badgeos-item-points-'.$achievement_id.' badgeos-item-points-'.$points_type.'-'.$achievement_id.'"><span>0</span> '.__( 'Points', 'badgeos' ).'</div>';
        }
    }
}

/**
 * Adds "earned"/"not earned" post_class based on viewer's status
 *
 * @param  array $classes Post classes
 * @return array          Updated post classes
 */
function badgeos_add_earned_class_single( $classes = array() ) {
	global $user_ID;

	// check if current user has earned the achievement they're viewing
	$classes[] = badgeos_get_user_achievements( array( 'user_id' => $user_ID, 'achievement_id' => get_the_ID() ) ) ? 'user-has-earned' : 'user-has-not-earned';

	return $classes;
}
add_filter( 'post_class', 'badgeos_add_earned_class_single' );

/**
 * Returns a message if user has earned the achievement.
 *
 * @since  1.1.0
 *
 * @param  integer $achievement_id Achievement ID.
 * @param  integer $user_id        User ID.
 * @return string                  HTML Markup.
 */
function badgeos_render_earned_achievement_text( $achievement_id = 0, $user_id = 0 ) {

	$earned_message = '';

	if ( badgeos_has_user_earned_achievement( $achievement_id, $user_id ) ) {
		$earned_message .= '<div class="badgeos-achievement-earned"><p>' . __( 'You have earned this achievement!', 'badgeos' ) . '</p></div>';
		if ( $congrats_text = get_post_meta( $achievement_id, '_badgeos_congratulations_text', true ) ) {
			$earned_message .= '<div class="badgeos-achievement-congratulations">' . wpautop( $congrats_text ) . '</div>';
		}
	}

	return apply_filters( 'badgeos_earned_achievement_message', $earned_message, $achievement_id, $user_id );
}

/**
 * Check if user has earned a given achievement.
 *
 * @since  alpha
 *
 * @param  integer $achievement_id Achievement ID.
 * @param  integer $user_id        User ID.
 * @return bool                    True if user has earned the achievement, otherwise false.
 */
function badgeos_has_user_earned_achievement( $achievement_id = 0, $user_id = 0 ) {
	$earned_achievements = badgeos_get_user_achievements( array( 'user_id' => absint( $user_id ), 'achievement_id' => absint( $achievement_id ) ) );
	$earned_achievement = ! empty( $earned_achievements );
	return apply_filters( 'badgeos_has_user_earned_achievement', $earned_achievement, $achievement_id, $user_id );
}

/**
 * Render an achievement
 *
 * @since  1.0.0
 * @param  integer $achievement The achievement's post ID
 * @return string               Concatenated markup
 */
function badgeos_render_achievement( $achievement = 0, $show_title = 'true', $show_thumb = 'true', $show_description = 'true', $show_steps = 'true', $image_width = '', $image_height = '' ) {

    global $user_ID;

    // If we were given an ID, get the post
    if ( is_numeric( $achievement ) )
        $achievement = get_post( $achievement );

    // make sure our JS and CSS is enqueued
    wp_enqueue_script( 'badgeos-achievements' );
    wp_enqueue_style( 'badgeos-widget' );

    // check if user has earned this Achievement, and add an 'earned' class
    $earned_status = badgeos_get_user_achievements( array( 'user_id' => $user_ID, 'achievement_id' => absint( $achievement->ID ) ) ) ? 'user-has-earned' : 'user-has-not-earned';

    // Setup our credly classes
    $credly_class = '';
    $credly_ID = '';

    $giveable = false;
    if( badgeos_first_time_installed() ) {
        $giveable = credly_is_achievement_giveable( $achievement->ID, $user_ID );
    }

    // If the achievement is earned and givable, override our credly classes
    if ( 'user-has-earned' == $earned_status && $giveable ) {
        $credly_class = ' share-credly addCredly';
        $credly_ID = 'data-credlyid="'. absint( $achievement->ID ) .'"';
    }

    // Each Achievement
    $output = '';
    //exclude step CPT entries from displaying in the widget
    $is_hidden = get_post_meta( $achievement->ID, '_badgeos_hidden', true );
    if( $is_hidden != 'hidden' ) {

        $output .= '<div id="badgeos-list-item-' . $achievement->ID . '" class="badgeos-list-item '. $earned_status . $credly_class .'"'. $credly_ID .'>';

        // Achievement Image
        if( $show_thumb == 'true' ) {
            $output .= '<div class="badgeos-item-image">';
            $image_size = 'boswp-badgeos-achievement';
            if( !empty( $image_width ) || !empty( $image_height) ) {
                $image_size = array( $image_width,  $image_height );
            }
            $output .= '<a href="' . get_permalink( $achievement->ID ) . '">' . badgeos_get_achievement_post_thumbnail( $achievement->ID, $image_size ) . '</a>';
            $output .= '</div><!-- .badgeos-item-image -->';
        }

        // Achievement Content
        $output .= '<div class="badgeos-item-description">';

        // Achievement Title
        if( $show_title == 'true' ) {
            $output .= '<h2 class="badgeos-item-title"><a href="' . get_permalink( $achievement->ID ) . '">' . get_the_title( $achievement->ID ) .'</a></h2>';
        }

        // Achievement Short Description
        if( $show_description == 'true' ) {
            $output .= '<div class="badgeos-item-excerpt">';
            $output .= badgeos_achievement_points_markup( $achievement->ID );
            $excerpt = !empty( $achievement->post_excerpt ) ? $achievement->post_excerpt : $achievement->post_content;
            $output .= wpautop( apply_filters( 'get_the_excerpt', $excerpt ) );
            $output .= '</div><!-- .badgeos-item-excerpt -->';
        }

        // Render our Steps
        if( $show_steps == 'true' ) {
            if ( $steps = badgeos_get_required_achievements_for_achievement( $achievement->ID ) ) {
                $output.='<div class="badgeos-item-attached">';
                $output.='<div id="show-more-'.$achievement->ID.'" class="badgeos-open-close-switch"><a class="show-hide-open" data-badgeid="'. $achievement->ID .'" data-action="open" href="#">' . __( 'Show Details', 'badgeos' ) . '</a></div>';
                $output.='<div id="badgeos_toggle_more_window_'.$achievement->ID.'" class="badgeos-extras-window">'. badgeos_get_required_achievements_for_achievement_list_markup( $steps, $achievement->ID ) .'</div><!-- .badgeos-extras-window -->';
                $output.= '</div><!-- .badgeos-item-attached -->';
            }
        }
        $output .= '</div><!-- .badgeos-item-description -->';
        $output .= '</div><!-- .badgeos-list-item -->';
    }

    // Return our filterable markup
    return apply_filters( 'badgeos_render_achievement', $output, $achievement->ID );

}

/*
 * Get current page post id
 *
 * @param null
 * @return integer
 */
function badgeos_get_current_page_post_id() {

	global $posts;

	$current_post_id = null;

	foreach($posts as $post){
		if($post->post_type != 'page') {
			//Get current page achievement id
			$current_post_id = $post->ID;
		}
	}

	//Return current post id
    return $current_post_id;
}


/**
 * Hide the hidden achievement post link from next post link
 *
 * @param $link
 * @return string
 */
function badgeos_hide_next_hidden_achievement_link($link) {

    $slugs = badgeos_get_achievement_types_slugs();
    // only run our filters on the badgeos singular pages
    if ( is_admin() || empty( $slugs ) || !is_singular( $slugs ) ) {
        return $link;
    }

    if($link) {

		//Get current achievement id
		$achievement_id = badgeos_get_current_page_post_id();

		//Get post link , without hidden achievement
		$link = badgeos_get_post_link_without_hidden_achievement($achievement_id, 'next');

	}

	return $link;

}
add_filter('next_post_link', 'badgeos_hide_next_hidden_achievement_link');

/**
 * Hide the hidden achievement post link from previous post link
 *
 * @param $link
 * @return string
 */
function badgeos_hide_previous_hidden_achievement_link($link) {


    $slugs = badgeos_get_achievement_types_slugs();
    // only run our filters on the badgeos singular pages
    if ( is_admin() || empty( $slugs ) || !is_singular( $slugs ) ) {
        return $link;
    }

    if($link) {

		//Get current achievement id
		$achievement_id = badgeos_get_current_page_post_id();

		//Get post link , without hidden achievement
		$link = badgeos_get_post_link_without_hidden_achievement($achievement_id, 'prev');

	}

	return $link;

}

add_filter('previous_post_link', 'badgeos_hide_previous_hidden_achievement_link');


/*
 * Get post link without hidden achievement link
 *
 * @param $achievement_id
 * @param $rel
 * @return string
 */
function badgeos_get_post_link_without_hidden_achievement($achievement_id, $rel) {


	$link = null;

	$post = get_post($achievement_id);

	//Check the ahievement
	$achievement_id = ( badgeos_is_achievement($post) )? $post->ID : "";

	//Get next post id without hidden achievement id
	$next_post_id = badgeos_get_next_previous_achievement_id($achievement_id, $rel);

	if ($next_post_id)
		//Generate post link
		$link = badgeos_generate_post_link_by_post_id($next_post_id, $rel);


	return $link;

}

/**
 * Get next or previous post id , without hidden achievement id
 *
 * @param $current_achievement_id
 * @param $flag
 * @return integer
 */
function badgeos_get_next_previous_achievement_id($achievement_id , $rel ){


	$nested_post_id = null;

	$access = false;

	// Redirecting user page based on achievements
	$post = get_post( absint( $achievement_id ));

	//Get hidden achievements ids
	$hidden = badgeos_get_hidden_achievement_ids( $post->post_type );

	// Fetching achievement types
	$param = array(
		'posts_per_page'   => -1, // All achievements
		'offset'           => 0,  // Start from first achievement
		'post_type'=> $post->post_type, // set post type as achievement to filter only achievements
		'orderby' => 'ID',
		'order' => 'ASC',
	);

	$param['order'] = ($rel == 'next') ? 'ASC' : 'DESC';


	$achievement_types = get_posts($param);

	foreach ($achievement_types as $achievement){

		$check = false;

		//Compare next achievement
		if($achievement->ID > $achievement_id && $rel == 'next') {
			$check = true;
		}

		//Compare previous achievement
		if($achievement->ID < $achievement_id && $rel == 'prev') {
			$check = true;
		}

		if($check){
			//Checks achievement in hidden badges
			if (in_array($achievement->ID, $hidden)) {
				continue;
			} else {
				$access = true;
			}
		}

		if($access) {
			//Get next or previous achievement without hidden badges
			if (!in_array($achievement->ID, $hidden) && !$nested_post_id) {
				$nested_post_id = $achievement->ID;
			}
		}
	}

	//rerurn next or previous achievement without hidden badge id
	return $nested_post_id;

}

/**
 * Generate the post link based on custom post object
 *
 * @param $link
 * @return string
 */
function badgeos_generate_post_link_by_post_id( $post_id , $rel) {

	global $post;

	if(!empty($post_id))
		$post = get_post($post_id);

    //Title of the post
	$title = get_the_title( $post->ID );

	if ( empty( $post->post_title ) && $rel == 'next')
		$title = __( 'Next Post' );

	if ( empty( $post->post_title ) && $rel == 'prev')
		$title = __( 'Previous Post' );


	$rel =  ($rel == 'prev') ? 'prev' : 'next';

	$nav_prev = ($rel == 'prev') ? '<span class="meta-nav">←</span> ' : '';
	$nav_next = ($rel == 'next') ? ' <span class="meta-nav">→</span>' : '';

	//Build link
	$link = '<a href="' . get_permalink( $post ) . '" rel="'.$rel.'">' . $nav_prev . $title . $nav_next. '</a>';

	return $link;

}