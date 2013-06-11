<?php
/**
 * AJAX Support Functions
 *
 * @package BadgeOS
 * @subpackage AJAX
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

// Setup our MVP AJAX actions
$badgeos_ajax_actions = array(
	'get-achievements',
);

// Register core Ajax calls.
foreach ( $badgeos_ajax_actions as $action ) {
	add_action( 'wp_ajax_' . $action, 'badgeos_ajax_' . str_replace( '-', '_', $action ), 1 );
	add_action( 'wp_ajax_nopriv_' . $action, 'badgeos_ajax_' . str_replace( '-', '_', $action ), 1 );
}


/**
 * Master Achievement List AJAX
 *
 * @since 1.0.0
 */
function badgeos_ajax_get_achievements(){
	global $user_ID;

	// Setup our AJAX query vars
	$type    = isset( $_REQUEST['type'] )    ? $_REQUEST['type']    : false;
	$limit   = isset( $_REQUEST['limit'] )   ? $_REQUEST['limit']   : false;
	$offset  = isset( $_REQUEST['offset'] )  ? $_REQUEST['offset']  : false;
	$count   = isset( $_REQUEST['count'] )   ? $_REQUEST['count']   : false;
	$filter  = isset( $_REQUEST['filter'] )  ? $_REQUEST['filter']  : false;
	$search  = isset( $_REQUEST['search'] )  ? $_REQUEST['search']  : false;
	$user_id = isset( $_REQUEST['user_id'] ) ? $_REQUEST['user_id'] : false;
	if( !$user_id )
		$user_id = $user_ID;

	$badges = null;

	// Grab our hidden and earned badges (used to filter the query)
	$hidden = badgeos_get_hidden_achievement_ids( $type );
	$earned_ids = badgeos_get_user_earned_achievement_ids( $user_id, $type );

	// Query Achievements
	$args = array(
		'post_type'      =>	$type,
		'orderby'        =>	'menu_order',
		'order'          =>	'ASC',
		'posts_per_page' =>	$limit,
		'offset'         => $offset,
		'post_status'    => 'publish',
		'post__not_in'   => array_diff( $hidden, $earned_ids )
	);

	// Filter - query completed or non completed achievements
	if ( $filter == 'completed' ) {
		$args = array_merge( $args, array( 'post__in' => array_merge( array(0), $earned_ids ) ) );
	}elseif( $filter == 'not-completed' ) {
		$args = array_merge( $args, array( 'post__not_in' => array_merge( $hidden, $earned_ids ) ) );
	}

	// Search
	if ( $search ) {
		$args = array_merge( $args, array( 's' => $search ) );
	}

	$the_badges = new WP_Query( $args );

	// Loop Achievements
	while ( $the_badges->have_posts() ) : $the_badges->the_post();

		$badge_id = get_the_ID();

		// check if user has earned this Achievement, and add an 'earned' class
		$earned_status = badgeos_get_user_achievements( array( 'user_id' => $user_id, 'achievement_id' => absint( $badge_id ) ) ) ? 'user-has-earned' : 'user-has-not-earned';

		// Only include the achievement if it HAS been earned -or- it is NOT hidden
		// if ( 'user-has-earned' == $earned_status || 'show' == get_post_meta( $badge_id, '_badgeos_hidden', true ) ) {

			$credly_class = '';
			$credly_ID = '';

			// check if current user has completed this achievement and check if Credly giveable
			if ( 'user-has-earned' == $earned_status ) {
				// Credly Share
				$giveable = credly_is_achievement_giveable( $badge_id );
				if ( $giveable ) {
					// make sure our JS and CSS is enqueued
					wp_enqueue_script( 'badgeos-achievements' );
					wp_enqueue_style( 'badgeos-widget' );
					$credly_class = ' share-credly addCredly';
					$credly_ID = 'data-credlyid="'. absint( $badge_id ) .'"';
				}
			}

			// Each Achievement
			$badges .= '<div id="badgeos-achievements-list-item-' . $badge_id . '" class="badgeos-achievements-list-item '. $earned_status . $credly_class .'"'. $credly_ID .'>';

				// Achievement Image
				$badges .= '<div class="badgeos-item-image">';
					$badges .= '<a href="'.get_permalink().'">' . badgeos_get_achievement_post_thumbnail( $badge_id ) . '</a>';
				$badges .= '</div><!-- .badgeos-item-image -->';

				$badges .= '<div class="badgeos-item-description">';

					// Achievement Title
					$badges .= '<h2 class="badgeos-item-title"><a href="'.get_permalink().'">' .get_the_title() .'</a></h2>';

					// Achievement Short Description
					$badges .= '<div class="badgeos-item-excerpt">';
						$badges .= badgeos_achievement_points_markup();
						$badges .= wpautop( get_the_excerpt() );
					$badges .= '</div><!-- .badgeos-item-excerpt -->';


					if ( $steps = badgeos_get_required_achievements_for_achievement( $badge_id ) ) {
						$badges.='<div class="badgeos-item-attached">';
							$badges.='<div id="show-more-'.$badge_id.'" class="badgeos-open-close-switch"><a class="show-hide-open" data-badgeid="'. $badge_id .'" data-action="open" href="#">Show Details</a></div>';
							$badges.='<div id="badgeos_toggle_more_window_'.$badge_id.'" class="badgeos-extras-window">'. badgeos_get_required_achievements_for_achievement_list_markup( $steps ) .'</div><!-- .badgeos-extras-window -->';
						$badges.= '</div><!-- .badgeos-item-attached -->';
					}

				$badges .= '</div><!-- .badgeos-item-description -->';

			$badges .= '</div><!-- .badgeos-achievements-list-item -->';

			$count +=1;

		// }

	endwhile;

	// Sanity helper: if we're filtering for complete and we have no
	// earned achievements, $badge_id should definitely be false
	if ( 'completed' == $filter && empty( $earned_ids ) )
		$badge_id = false;

	//display a message for no results
	if ( !$badge_id ) {
		$post_type_plural = get_post_type_object( $type )->labels->name;
		$badges .= '<div class="badgeos-no-results">';
		if ( 'completed' == $filter ) {
			$badges .= sprintf( __( 'No completed %s yet.', 'badgeos' ), strtolower( $post_type_plural ) );
		}else{
			$badges .= sprintf( __( 'There are no %s at this time.', 'badgeos' ), strtolower( $post_type_plural ) );
		}
		$badges .= '</div><!-- .badgeos-no-results -->';
	}

	$response['message']     = $badges;
	$response['offset']      = $offset + $limit;
	$response['query_count'] = $the_badges->found_posts;
	$response['badge_count'] = $count;

	echo json_encode( $response );
	die();
}
