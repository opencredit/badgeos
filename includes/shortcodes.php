<?php
/**
 * Custom Shortcodes
 *
 * @package BadgeOS
 * @subpackage Front-end
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

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
		return;

	global $user_ID;
	extract( shortcode_atts( array(
		'type'        => 'all',
		'limit'       => '10',
		'show_filter' => 'true',
		'show_search' => 'true',
		'group_id'    => '0',
		'user_id'     => '0',
		'wpms'        => 'false',
		'orderby'     => 'menu_order',
		'order'       => 'ASC'
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
		'order'       => $order
	);
	wp_localize_script( 'badgeos-achievements', 'badgeos', $data );

	// If we're dealing with multiple achievement types
	if ( 'all' == $type ) {
		$post_type_plural = 'achievements';
	} else {
		$types = explode( ',', $type );
		$post_type_plural = ( 1 == count( $types ) ) ? get_post_type_object( $type )->labels->name : 'achievements';
	}

	$badges = null;

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
add_shortcode( 'badgeos_achievements_list', 'badgeos_achievements_list_shortcode' );
/**
 * Add help content for [badgeos_achievements_list] to BadgeOs Help page
 *
 * @since  1.2.0
 */
function badgeos_achievements_list_shortcode_help() { ?>
	<hr/>
	<p><strong>[badgeos_achievements_list]</strong> - <?php _e( 'Output a list of achievements of any type on any post or page.', 'badgeos' ); ?></p>
	<div style="padding-left:15px;">
		<ul>
			<li><strong><?php _e( 'Parameters', 'badgeos' ); ?></strong></li>
			<li>
				<div style="padding-left:15px;">
				<ul>
					<li>type - <?php printf( __( 'Type of achievements to list. Accepts: %1$s Default: %2$s', 'badgeos' ), '<code>"all", comma-separated list of achievement types (e.g. "badge,level,trophy"), individual achievement type (e.g. "badge")</code>', '<code>all</code>' ); ?></li>
					<li>limit - <?php printf( __( 'Number of achievements to display per page. Default: %s', 'badgeos' ), '<code>10</code>' ); ?></li>
					<li>show_filter - <?php printf( __( 'Display the filter options. Accepts: %1$s Default: %2$s', 'badgeos' ), '<code>true, false</code>', '<code>true</code>' ); ?></li>
					<li>show_search - <?php printf( __( 'Display the search form. Accepts: %1$s Default: %2$s', 'badgeos' ), '<code>true, false</code>', '<code>true</code>' ); ?></li>
					<li>wpms - <?php printf( __( 'Displays achievements of the same type from across a multisite network if multisite is enabled and a super admin enables network achievements. Accepts: %1$s Default: %2$s', 'badgeos' ), '<code>true, false</code>', '<code>false</code>' ); ?></li>
					<li>orderby - <?php printf( __( 'Specify how to order achievements. Accepts: %1$s Default: %2$s', 'badgeos' ), '<a href="http://codex.wordpress.org/Class_Reference/WP_Query#Order_.26_Orderby_Parameters" target="_blank">See WP_Query orderby parameters</a>', '<code>menu_order</code>' ); ?></li>
					<li>order - <?php printf( __( 'Specify the direction to order achievements. Accepts: %1$s Default: %2$s', 'badgeos' ), '<code>ASC, DESC</code>', '<code>ASC</code>' ); ?></li>
				</ul>
				</div>
			</li>
			<li><strong><?php _e( 'Example', 'badgeos' ); ?>:</strong> <code>[badgeos_achievements_list type=badges limit=15]</code></li>
		</ul>
	</div>
<?php }
add_action( 'badgeos_help_support_page_shortcodes', 'badgeos_achievements_list_shortcode_help' );

/**
 * Render a single achievement
 *
 * Usage: [badgeos_achievement id=12]
 *
 * @since  1.1.0
 * @param  array  $atts Our attributes array
 * @return string       Concatenated markup
 */
function badgeos_achievement_shortcode( $atts = array() ) {
	global $post, $badgeos;

	// get the post id
	$atts = shortcode_atts( array(
	  'id' => $post->ID,
	), $atts );

	// return if post id not specified
	if ( empty($atts['id']) )
	  return;

	wp_enqueue_style( 'badgeos-front' );
	wp_enqueue_script( 'badgeos-achievements' );

	// get the post content and format the badge display
	$achievement = get_post($atts['id']);
	$output = '';

	// If we're dealing with an achievement post
	if ( in_array( $achievement->post_type, array_keys( $badgeos->achievement_types ) ) ) {
		$output .= '<div id=badgeos-achievements-container>';  // necessary for the jquery click handler to be called
		$output .= badgeos_render_achievement( $achievement );
		$output .= '</div>';
	}

	// Return our rendered achievement
	return $output;
}
add_shortcode( 'badgeos_achievement', 'badgeos_achievement_shortcode' );

/**
 * Add help content for [badgeos_achievement] to BadgeOs Help page
 *
 * @since  1.2.0
 */
function badgeos_achievement_shortcode_help() { ?>
	<hr/>
	<p><strong>[badgeos_achievement]</strong> - <?php _e( 'Display a single achievement on any post or page.', 'badgeos' ); ?></p>
	<div style="padding-left:15px;">
		<ul>
			<li><strong><?php _e( 'Parameters', 'badgeos' ); ?></strong></li>
			<li>
				<div style="padding-left:15px;">
				<ul>
					<li><?php _e( 'id', 'badgeos' ); ?> - <?php _e( 'The ID of the achievement to display.', 'badgeos' ); ?></li>
				</ul>
				</div>
			</li>
			<li><strong><?php _e( 'Example', 'badgeos' ); ?>:</strong> <code>[badgeos_achievement id=12]</code></li>
		</ul>
	</div>
<?php }
add_action( 'badgeos_help_support_page_shortcodes', 'badgeos_achievement_shortcode_help' );


/**
 * Display nomination form for awarding an achievemen
 * @since 1.0.0
 * @param  array  $atts The attributions for the meta box
 * @return string       The concatinated markup
 */
function badgeos_nomination_form( $atts = array() ) {
	global $user_ID, $post;

	// Parse our attributes
	$atts = shortcode_atts( array(
		'achievement_id' => $post->ID
	), $atts );

	$output = '';

	// Verify user is logged in to view any submission data
	if ( is_user_logged_in() ) {

		// If we've just saved nomination data
		if ( badgeos_save_nomination_data() )
			$output .= sprintf( '<p>%s</p>', __( 'Nomination saved successfully.', 'badgeos' ) );

		// Return the user's nominations
		if ( badgeos_check_if_user_has_nomination( $user_ID, $atts['achievement_id'] ) )
			$output .= badgeos_get_user_nominations( '', $atts['achievement_id'] );

		// Include the nomination form
		$output .= badgeos_get_nomination_form( array( 'user_id' => $user_ID, 'achievement_id' => $atts['achievement_id'] ) );

	} else {

		$output = '<p><i>' . __( 'You must be logged in to post a nomination.', 'badgeos' ) . '</i></p>';

	}

	return $output;

}
add_shortcode( 'badgeos_nomination', 'badgeos_nomination_form' );

/**
 * Add help content for [badgeos_nomination] to BadgeOs Help page
 *
 * @since  1.2.0
 */
function badgeos_nomination_form_shortcode_help() { ?>
	<hr/>
	<p><strong>[badgeos_nomination]</strong> - <?php _e( 'Display nominations or nomination form for a given achievement. <strong>Note:</strong> Achievements will automatically display this on their single page if <strong>Earned By</strong> is set to <strong>Nomination</strong>.', 'badgeos' ); ?></p>
	<div style="padding-left:15px;">
		<ul>
			<li><strong><?php _e( 'Parameters', 'badgeos' ); ?></strong></li>
			<li>
				<div style="padding-left:15px;">
				<ul>
					<li>achievement_id - <?php _e( 'The ID of the achievement to be awarded.  Default: current post ID', 'badgeos' ); ?></li>
				</ul>
				</div>
			</li>
			<li><strong><?php _e( 'Example', 'badgeos' ); ?>:</strong> <code>[badgeos_nomination achievement_id=35]</code></li>
		</ul>
	</div>
<?php }
add_action( 'badgeos_help_support_page_shortcodes', 'badgeos_nomination_form_shortcode_help' );


/**
 * Submission Form
 * @since 1.0.0
 * @param  array  $atts The attributes
 * @return string       The concatinated submission form markup
 */
function badgeos_submission_form( $atts = array() ) {
	global $user_ID, $post;

	// Parse our attributes
	$atts = shortcode_atts( array(
		'achievement_id' => $post->ID
	), $atts );

	// Verify user is logged in to view any submission data
	if ( is_user_logged_in() ) {

		if ( badgeos_save_submission_data() )
			printf( '<p>%s</p>', __( 'Submission saved successfully.', 'badgeos' ) );

		// Return either the user's submission or the submission form
		if ( badgeos_check_if_user_has_submission( $user_ID, $atts['achievement_id'] ) )
			return badgeos_get_user_submissions( '', $atts['achievement_id'] );
		else
			return badgeos_get_submission_form( array( 'user_id' => $user_ID, 'achievement_id' => $atts['achievement_id'] ) );

	} else {

		return '<p><i>' . __( 'You must be logged in to post a submission.', 'badgeos' ) . '</i></p>';

	}
}
add_shortcode( 'badgeos_submission', 'badgeos_submission_form' );

/**
 * Add help content for [badgeos_submission] to BadgeOs Help page
 *
 * @since  1.2.0
 */
function badgeos_submission_form_shortcode_help() { ?>
	<hr/>
	<p><strong>[badgeos_submission]</strong> - <?php _e( 'Display submissions or submission form for a given achievement. <strong>Note:</strong> Achievements will automatically display this on their single page if <strong>Earned By</strong> is set to <strong>Submission</strong>.', 'badgeos' ); ?></p>
	<div style="padding-left:15px;">
		<ul>
			<li><strong><?php _e( 'Parameters', 'badgeos' ); ?></strong></li>
			<li>
				<div style="padding-left:15px;">
				<ul>
					<li>achievement_id - <?php _e( 'The ID of the achievement to be awarded.  Default: current post ID', 'badgeos' ); ?></li>
				</ul>
				</div>
			</li>
			<li><strong><?php _e( 'Example', 'badgeos' ); ?>:</strong> <code>[badgeos_submission achievement_id=35]</code></li>
		</ul>
	</div>
<?php }
add_action( 'badgeos_help_support_page_shortcodes', 'badgeos_submission_form_shortcode_help' );


/**
 * Shortcode to display a filterable list of Submissions
 *
 * @since  1.1.0
 * @param  array  $atts Attributes passed via shortcode
 * @return string       Concatenated output
 */
function badgeos_display_submissions( $atts = array() ) {
	global $user_ID;

	// Parse our attributes
	$atts = shortcode_atts( array(
		'type'             => 'submission',
		'limit'            => '10',
		'status'           => 'all',
		'show_filter'      => true,
		'show_search'      => true,
		'show_attachments' => true,
		'show_comments'    => true
	), $atts );

	$feedback = badgeos_render_feedback( $atts );

	// Enqueue and localize our JS
	$atts['ajax_url'] = esc_url( admin_url( 'admin-ajax.php', 'relative' ) );
	$atts['user_id']  = $user_ID;
	wp_enqueue_script( 'badgeos-achievements' );
	wp_localize_script( 'badgeos-achievements', 'badgeos_feedback', $atts );

	// Return our rendered content
	return $feedback;

}
add_shortcode( 'badgeos_submissions', 'badgeos_display_submissions' );

/**
 * Add help content for [badgeos_submissions] to BadgeOs Help page
 *
 * @since  1.2.0
 */
function badgeos_submissions_shortcode_help() { ?>
	<hr/>
	<p><strong>[badgeos_submissions]</strong> - <?php _e( 'Generate a list of submissions on any post or page.', 'badgeos' ); ?></p>
	<div style="padding-left:15px;">
		<ul>
			<li><strong><?php _e( 'Parameters', 'badgeos' ); ?></strong></li>
			<li>
				<div style="padding-left:15px;">
				<ul>
					<li>limit - <?php printf( __( 'Number of submissions to display per page. Default: %1$s', 'badgeos' ), '<code>10</code>' ); ?></li>
					<li>status - <?php printf( __( 'Which Approval Status type to show on initial page load. Accepts: %1$s  Default: %2$s', 'badgeos' ), '<code>all, pending, auto-approved, approved, denied</code>', '<code>all</code>' ); ?></li>
					<li>show_filter - <?php printf( __( 'Display the filter select input. Accepts: %1$s  Default: %2$s', 'badgeos' ), '<code>true, false</code>', '<code>true</code>' ); ?></li>
					<li>show_search - <?php printf( __( 'Display the search form. Accepts: %1$s  Default: %2$s', 'badgeos' ), '<code>true, false</code>', '<code>true</code>' ); ?></li>
					<li>show_attachments - <?php printf( __( 'Display attachments connected to the submission. Accepts: %1$s  Default: %2$s', 'badgeos' ), '<code>true, false</code>', '<code>true</code>' ); ?></li>
					<li>show_comments - <?php printf( __( 'Display comments associated with the submission. Accepts: %1$s  Default: %2$s', 'badgeos' ), '<code>true, false</code>', '<code>true</code>' ); ?></li>
				</ul>
				</div>
			</li>
			<li><strong><?php _e( 'Example', 'badgeos' ); ?>:</strong> <?php printf( __( 'To show 15 pending submissions, %s', 'badgeos' ), '<code>[badgeos_submissions status=pending limit=15]</code>' ); ?></li>
		</ul>
	</div>
<?php }
add_action( 'badgeos_help_support_page_shortcodes', 'badgeos_submissions_shortcode_help' );


/**
 * Shortcode to display a filterable list of Nominations
 *
 * @since  1.1.0
 * @param  array  $atts Attributes passed via shortcode
 * @return string       Concatenated output
 */
function badgeos_display_nominations( $atts = array() ) {
	global $user_ID;

	// Parse our attributes
	$atts = shortcode_atts( array(
		'type'             => 'nomination',
		'limit'            => '10',
		'status'           => 'all',
		'show_filter'      => true,
		'show_search'      => true,
		'show_attachments' => 'false',
		'show_comments'    => 'false'
	), $atts );

	$feedback = badgeos_render_feedback( $atts );

	// Enqueue and localize our JS
	$atts['ajax_url'] = esc_url( admin_url( 'admin-ajax.php', 'relative' ) );
	$atts['user_id']  = $user_ID;
	wp_enqueue_script( 'badgeos-achievements' );
	wp_localize_script( 'badgeos-achievements', 'badgeos_feedback', $atts );

	// Return our rendered content
	return $feedback;

}
add_shortcode( 'badgeos_nominations', 'badgeos_display_nominations' );

/**
 * Add help content for [badgeos_nominations] to BadgeOs Help page
 *
 * @since  1.2.0
 */
function badgeos_nominations_shortcode_help() { ?>
	<hr/>
	<p><strong>[badgeos_nominations]</strong> - <?php _e( 'Generate a list of nominations on any post or page.', 'badgeos' ); ?></p>
	<div style="padding-left:15px;">
		<ul>
			<li><strong><?php _e( 'Parameters', 'badgeos' ); ?></strong></li>
			<li>
				<div style="padding-left:15px;">
				<ul>
					<li>limit - <?php printf( __( 'Number of nominations to display per page. Default: %1$s', 'badgeos' ), '<code>10</code>' ); ?></li>
					<li>status - <?php printf( __( 'Which Approval Status type to show on initial page load. Accepts: %1$s  Default: %2$s', 'badgeos' ), '<code>all, pending, approved, denied</code>', '<code>all</code>' ); ?></li>
					<li>show_filter - <?php printf( __( 'Display the filter select input. Accepts: %1$s  Default: %2$s', 'badgeos' ), '<code>true, false</code>', '<code>true</code>' ); ?></li>
					<li>show_search - <?php printf( __( 'Display the search form. Accepts: %1$s  Default: %2$s', 'badgeos' ), '<code>true, false</code>', '<code>true</code>' ); ?></li>
				</ul>
				</div>
			</li>
			<li><strong><?php _e( 'Example', 'badgeos' ); ?>:</strong> <?php printf( __( 'To display 20 nominations and no search form, %s', 'badgeos' ), '<code>[badgeos_nominations show_search=false limit=20]</code>' ); ?></li>
		</ul>
	</div>
<?php }
add_action( 'badgeos_help_support_page_shortcodes', 'badgeos_nominations_shortcode_help' );
