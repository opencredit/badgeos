<?php

function badgeos_register_achievements_list_shortcode() {

	$badgeos_achievements_list_shortcode = badgeos_register_shortcode( array(
	'name' => __( 'BadgeOS Achievements List', 'badgeos' ),
	'slug' => 'badgeos_achievements_list',
	'description' => __( 'Output a list of achievements of any type on any post or page.', 'badgeos' ),
	'attributes' => array(
		'type' => array(
			'name' => __( 'Type', 'badgeos' ),
			'type' => 'select',
			'description' => __( 'The achievement type to display. Default: all', 'badgeos' ),
			'values' => badgeos_get_awardable_achievements()
			),
		'limit' => array(
			'name' => __( 'Limit', 'badgeos' ),
			'type' => 'text',
			'description' => __( 'How many achievements to display at a time. Default: 10', 'badgeos' )
			),
		'show_filter' => array(
			'name' => __( 'Show Filter', 'badgeos' ),
			'type' => 'select_bool',
			'description' => __( 'Whether or not to show filter controls. Default: true', 'badgeos' ),
			'values' => array( 'true', 'false' ),
			'default' => 'true'
			),
		'show_search' => array(
			'name' => __( 'Show Search', 'badgeos' ),
			'type' => 'select_bool',
			'description' => __( 'Whether or not to show search inputs. Default: true', 'badgeos' ),
			'values' => array( 'true', 'false' ),
			'default' => 'true'
			),
		'group_id' => array(
			'name' => __( 'Group ID', 'badgeos' ),
			'type' => 'text',
			'description' => '0'
			),
		'user_id' => array(
			'name' => __( 'User ID', 'badgeos' ),
			'type' => 'text',
			'description' => __( 'The user ID to display achievements for. Default: 0', 'badgeos' )
			),
		'wpms' => array(
			'name' => __( 'Multisite', 'badgeos' ),
			'type' => 'select_bool',
			'description' => __( 'Whether to display achievements from across a multisite network. Default: false', 'badgeos' ),
			'values' => array( 'true', 'false' ),
			'default' => 'false'
			),
		'orderby' => array(
			'name' => __( 'Order By', 'badgeos' ),
			'type' => 'select',
			'description' => __( 'What content to order the achievements by. Default: menu_order', 'badgeos' ),
			'values' => array( 'none', 'ID', 'author', 'title', 'name', 'date', 'modified', 'parent', 'rand', 'comment_count', 'menu_order', 'meta_value', 'meta_value_num', 'post__in' )
			),
		'order' => array(
			'name' => __( 'Order', 'badgeos' ),
			'type' => 'select',
			'description' => __( 'Whether to display in ascending or descending order. Default: ASC', 'badgeos' ),
			'values' => array( 'ASC', 'DESC' ),
			),
		'include' => array(
			'name' => __( 'Include', 'badgeos' ),
			'type' => 'text',
			'description' => __( 'Comma-separated list of IDs to include. Default: none', 'badgeos' )
			),
		'exclude' => array(
			'name' => __( 'Exclude', 'badgeos' ),
			'type' => 'text',
			'description' => __( 'Comma-separated list of IDs to exclude. none', 'badgeos' )
			),
		'meta_key' => array(
			'name' => __( 'Meta Key', 'badgeos' ),
			'type' => 'text',
			'description' => __( 'Meta key to use in the query. Default: none', 'badgeos' )
			),
		'meta_value' => array(
			'name' => __( 'Meta Value', 'badgeos' ),
			'type' => 'text',
			'description' => __( 'Meta value to use in the query. Default: none', 'badgeos' )
			)
	),
	'output_callback' => 'badgeos_achievements_list_shortcode'
	) );
}
add_action( 'init', 'badgeos_register_achievements_list_shortcode' );

/**
 * Master Achievement List Short Code
 *
 * @since  1.0.0
 * @param  array $atts Shortcode attributes
 * @return string 	   The concatinated markup
 */
function badgeos_achievements_list_shortcode( $atts = array () ){

	// check if shortcode has already been run
	if ( isset( $GLOBALS['badgeos_achievements_list'] ) )
		return '';

	global $user_ID;
	extract( shortcode_atts( array(
		'type'        => 'all',
		'limit'       => '10',
		'show_filter' => true,
		'show_search' => true,
		'group_id'    => '0',
		'user_id'     => '0',
		'wpms'        => false,
		'orderby'     => 'menu_order',
		'order'       => 'ASC',
		'include'     => array(),
		'exclude'     => array(),
		'meta_key'    => '',
		'meta_value'  => ''
	), $atts ) );

	wp_enqueue_style( 'badgeos-front' );
	wp_enqueue_script( 'badgeos-achievements' );

	$data = array(
		'ajax_url'    => esc_url( admin_url( 'admin-ajax.php', 'relative' ) ),
		'type'        => $type,
		'limit'       => $limit,
		'show_filter' => $show_filter,
		'show_search' => $show_search,
		'group_id'    => $group_id,
		'user_id'     => $user_id,
		'wpms'        => $wpms,
		'orderby'     => $orderby,
		'order'       => $order,
		'include'     => $include,
		'exclude'     => $exclude,
		'meta_key'    => $meta_key,
		'meta_value'  => $meta_value
	);
	wp_localize_script( 'badgeos-achievements', 'badgeos', $data );

	// If we're dealing with multiple achievement types
	if ( 'all' == $type ) {
		$post_type_plural = 'achievements';
	} else {
		$types = explode( ',', $type );
		$post_type_plural = ( 1 == count( $types ) ) ? get_post_type_object( $type )->labels->name : 'achievements';
	}

	$badges = '';

	$badges .= '<div id="badgeos-achievements-filters-wrap">';
		// Filter
		if ( $show_filter == 'false' ) {

			$filter_value = 'all';
			if( $user_id ){
				$filter_value = 'completed';
				$badges .= '<input type="hidden" name="user_id" id="user_id" value="'.$user_id.'">';
			}
			$badges .= '<input type="hidden" name="achievements_list_filter" id="achievements_list_filter" value="'.$filter_value.'">';

		}else{

			$badges .= '<div id="badgeos-achievements-filter">';

				$badges .= 'Filter: <select name="achievements_list_filter" id="achievements_list_filter">';

					$badges .= '<option value="all">All '.$post_type_plural;
					// If logged in
					if ( $user_ID >0 ) {
						$badges .= '<option value="completed">Completed '.$post_type_plural;
						$badges .= '<option value="not-completed">Not Completed '.$post_type_plural;
					}
					// TODO: if show_points is true "Badges by Points"
					// TODO: if dev adds a custom taxonomy to this post type then load all of the terms to filter by

				$badges .= '</select>';

			$badges .= '</div>';

		}

		// Search
		if ( $show_search != 'false' ) {

			$search = isset( $_POST['achievements_list_search'] ) ? $_POST['achievements_list_search'] : '';
			$badges .= '<div id="badgeos-achievements-search">';
				$badges .= '<form id="achievements_list_search_go_form" action="'. get_permalink( get_the_ID() ) .'" method="post">';
				$badges .= 'Search: <input type="text" id="achievements_list_search" name="achievements_list_search" value="'. $search .'">';
				$badges .= '<input type="submit" id="achievements_list_search_go" name="achievements_list_search_go" value="Go">';
				$badges .= '</form>';
			$badges .= '</div>';

		}

	$badges .= '</div><!-- #badgeos-achievements-filters-wrap -->';

	// Content Container
	$badges .= '<div id="badgeos-achievements-container"></div>';

	// Hidden fields and Load More button
	$badges .= '<input type="hidden" id="badgeos_achievements_offset" value="0">';
	$badges .= '<input type="hidden" id="badgeos_achievements_count" value="0">';
	$badges .= '<input type="button" id="achievements_list_load_more" value="Load More" style="display:none;">';
	$badges .= '<div class="badgeos-spinner"></div>';

	// Reset Post Data
	wp_reset_postdata();

	// Save a global to prohibit multiple shortcodes
	$GLOBALS['badgeos_achievements_list'] = true;
	return $badges;

}
