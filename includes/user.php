<?php
/**
 * User-related Functions
 *
 * @package BadgeOS
 * @subpackage Admin
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Get a user's badgeos achievements
 *
 * @since  1.0.0
 * @param  array $args An array of all our relevant arguments
 * @return array       An array of all the achievement objects that matched our parameters, or empty if none
 */
function badgeos_get_user_achievements( $args = array() ) {

	// Setup our default args
	$defaults = array(
		'user_id'          => 0,     // The given user's ID
		'site_id'          => 1,     // The given site's ID
		'achievement_id'   => false, // A specific achievement's post ID
		'achievement_type' => false, // A specific achievement type
		'since'            => 0,     // A specific timestamp to use in place of $limit_in_days
	);
	$args = wp_parse_args( $args, $defaults );

	// Use current user's ID if none specified
	if ( ! $args['user_id'] )
		$args['user_id'] = wp_get_current_user()->ID;

	// Grab the user's current achievements
	$achievements = ( $earned_items = get_user_meta( absint( $args['user_id'] ), '_badgeos_achievements', true ) ) ? $earned_items : array( $args['site_id'] => array() );

	// If we want all sites (or no specific site), return the full array
	if ( empty( $args['site_id']) || 'all' == $args['site_id'] )
		return $achievements;

	// Otherwise, we only want the specific site's achievements
	$achievements = $achievements[$args['site_id']];

	if ( is_array( $achievements) && ! empty( $achievements ) ) {
		foreach ( $achievements as $key => $achievement ) {

			// Drop any achievements earned before our since timestamp
			if ( absint($args['since']) > $achievement->date_earned )
				unset($achievements[$key]);

			// Drop any achievements that don't match our achievement ID
			if ( ! empty( $args['achievement_id'] ) && absint( $args['achievement_id'] ) != $achievement->ID )
				unset($achievements[$key]);

			// Drop any achievements that don't match our achievement type
			if ( ! empty( $args['achievement_type'] ) && $args['achievement_type'] != $achievement->post_type )
				unset($achievements[$key]);

		}
	}

	// Return our $achievements array_values (so our array keys start back at 0), or an empty array
	return array_values( $achievements );

}

/**
 * Updates the user's earned achievements
 *
 * We can either replace the achievement's array, or append new achievements to it.
 *
 * @since  1.0.0
 * @param  array        $earned_achievements An array containing all our relevant arguments
 * @return integer|bool                      The updated umeta ID on success, false on failure
 */
function badgeos_update_user_achievements( $args = array() ) {

	// Setup our default args
	$defaults = array(
		'user_id'          => 0,     // The given user's ID
		'site_id'          => 1,     // The given site's ID
		'all_achievements' => false, // An array of ALL achievements earned by the user
		'new_achievements' => false, // An array of NEW achievements earned by the user
	);
	$args = wp_parse_args( $args, $defaults );

	// Use current user's ID if none specified
	if ( ! $args['user_id'] )
		$args['user_id'] = wp_get_current_user()->ID;

	// Grab our user's achievements
	$achievements = badgeos_get_user_achievements( array( 'user_id' => absint( $args['user_id'] ), 'site_id' => 'all' ) );

	// If we don't already have an array stored for this site, create a fresh one
	if ( !isset( $achievements[$args['site_id']] ) )
		$achievements[$args['site_id']] = array();

	// Determine if we should be replacing or appending to our achievements array
	if ( is_array( $args['all_achievements'] ) )
		$achievements[$args['site_id']] = $args['all_achievements'];
	elseif ( is_array( $args['new_achievements'] ) && ! empty( $args['new_achievements'] ) )
		$achievements[$args['site_id']] = array_merge( $achievements[$args['site_id']], $args['new_achievements'] );

	// Finally, update our user meta
	return update_user_meta( absint( $args['user_id'] ), '_badgeos_achievements', $achievements);

}

/**
 * Return a user's points
 *
 * @since  1.0
 * @param  string   $user_id      The given user's ID
 * @return integer  $user_points  The user's current points
 */
function badgeos_get_users_points( $user_id = 0 ) {

	// Use current user's ID if none specified
	if ( ! $user_id )
		$user_id = wp_get_current_user()->ID;

	// Return our user's points as an integer (sanely falls back to 0 if empty)
	return absint( get_user_meta( $user_id, '_badgeos_points', true ) );
}

/**
 * Posts a log entry when a user earns points
 *
 * @since  1.0
 * @param  integer $user_id    The given user's ID
 * @param  integer $new_points The new points the user is being awarded
 * @param  integer $admin_id   If being awarded by an admin, the admin's user ID
 * @return int                 The user's updated point total
 */
function badgeos_update_users_points( $user_id = 0, $new_points = 0, $admin_id = 0 ) {

	// Use current user's ID if none specified
	if ( ! $user_id )
		$user_id = wp_get_current_user()->ID;

	// Setup our user objects
	$user  = get_userdata( $user_id );
	$admin = get_userdata( $admin_id );

	// Grab the user's current points
	$current_points = badgeos_get_users_points( $user_id );

	// If we're getting an admin ID, $new_points is actually the final total, so subtract the current points
	if ( $admin_id ) $new_points = $new_points - $current_points;

	// Update our user's total
	$updated_points_total = absint( $current_points + $new_points );
	update_user_meta( $user_id, '_badgeos_points', $updated_points_total );

	// Alter our log message if this was an admin action
	if ( $admin_id )
		$log_message = sprintf( __( '%s awarded %s %s points for a new total of %s points', 'dma' ), $admin->user_login, $user->user_login, number_format( $new_points ), number_format( $updated_points_total ) );
	else
		$log_message = sprintf( __( '%s earned %s points for a new total of %s points', 'dma' ), $user->user_login, number_format( $new_points ), number_format( $updated_points_total ) );

	// Create a log entry
	badgeos_post_log_entry( null, $user_id, 'points', $log_message );

	// Maybe award some points-based badges
	foreach ( badgeos_get_points_based_achievements() as $achievement )
		badgeos_maybe_award_achievement_to_user( $achievement->ID );

	return $updated_points_total;
}

/**
 * Award new points to a user based on logged activites and earned badges
 *
 * @since  1.0
 * @param  integer $user_id        The given user's ID
 * @param  integer $achievement_id The given achievement's post ID
 * @return integer                 The user's updated points total
 */
function badgeos_award_user_points( $user_id, $achievement_id ) {

	// Grab our points from the provided post
	$points = absint( get_post_meta( $achievement_id, '_badgeos_points', true ) );

	if ( ! empty( $points ) )
		return badgeos_update_users_points( $user_id, $points );
}
add_action( 'badgeos_award_achievement', 'badgeos_award_user_points', 999, 2 );

/**
 * Display achievements for a user on their profile screen
 *
 * @since 1.0.0
 * @param object $user The current user's $user object
 */
function badgeos_user_profile_data( $user ) {


	// Get minimum role setting for menus
	$badgeos_settings = get_option( 'badgeos_settings' );
	$minimum_role = ( ! empty( $badgeos_settings['minimum_role'] ) ) ? $badgeos_settings['minimum_role'] : 'administrator';

	//verify uesr meets minimum role to view earned badges
	if ( current_user_can( $minimum_role ) ) {

		$achievements = badgeos_get_user_achievements( array( 'user_id' => absint( $user->ID ) ) );

		echo '<h2>' . __( 'Earned Achievements', 'badgeos' ) . '</h2>';

		echo '<table class="form-table">';
		echo '<tr>';
			echo '<th><label for="user_points">' . __( 'Earned Points', 'badgeos' ) . '</label></th>';
			echo '<td>';
				echo '<input type="text" name="user_points" id="user_points" value="' . badgeos_get_users_points( $user->ID ) . '" class="regular-text" /><br />';
				echo '<span class="description">' . __( "The user's points total. Entering a new total will automatically log the change and difference between totals.", 'badgeos' ) . '</span>';
			echo '</td>';
		echo '</tr>';

		echo '<tr><td colspan="2">';
		// List all of a user's earned achievements
		if ( $achievements ) {
			echo '<table class="widefat badgeos-table">';
			echo '<thead><tr>';
				echo '<th>'. __( 'Image', 'badgeos' ) .'</th>';
				echo '<th>'. __( 'Name', 'badgeos' ) .'</th>';
				echo '<th>'. __( 'Action', 'badgeos' ) .'</th>';
			echo '</tr></thead>';

			$achievement_ids = array();

			foreach ( $achievements as $achievement ) {

				// Setup our revoke URL
				$revoke_url = add_query_arg( array(
					'action'         => 'revoke',
					'user_id'        => absint( $user->ID ),
					'achievement_id' => absint( $achievement->ID ),
				) );

				echo '<tr>';
					echo '<td>'. get_the_post_thumbnail( $achievement->ID, array( 50, 50 ) ) .'</td>';
					echo '<td>', edit_post_link( get_the_title( $achievement->ID ), '', '', $achievement->ID ), ' </td>';
					echo '<td> <span class="delete"><a class="error" href="'.esc_url( wp_nonce_url( $revoke_url, 'badgeos_revoke_achievement' ) ).'">' . __( 'Revoke Award', 'badgeos' ) . '</a></span></td>';
				echo '</tr>';

				$achievement_ids[] = $achievement->ID;

			}
			echo '</table>';
		}

		echo '</td></tr>';
		echo '</table>';

		// If debug mode is on, output our achievements array
		if ( badgeos_is_debug_mode() ) {

			echo 'DEBUG MODE ENABLED<br />';
			echo 'Metadata value for: _badgeos_achievements<br />';

			var_dump ( $achievements );

		}

		echo '<br/>';

		// Output markup for awarding achievement for user
		badgeos_profile_award_achievement( $user, $achievement_ids );

	}

}
add_action( 'show_user_profile', 'badgeos_user_profile_data' );
add_action( 'edit_user_profile', 'badgeos_user_profile_data' );


/**
 * Save extra user meta fields to the Edit Profile screen
 *
 * @since  1.0
 * @param  string  $user_id      User ID being saved
 * @return nothing
 */
function badgeos_save_user_profile_fields( $user_id ) {

	if ( !current_user_can( 'edit_user', $user_id ) )
		return FALSE;

	// Update our user's points total, but only if edited
	if ( $_POST['user_points'] != badgeos_get_users_points( $user_id ) )
		badgeos_update_users_points( $user_id, absint( $_POST['user_points'] ), get_current_user_id() );

}
add_action( 'personal_options_update', 'badgeos_save_user_profile_fields' );
add_action( 'edit_user_profile_update', 'badgeos_save_user_profile_fields' );

/**
 * Generate markup for awarding an achievement to a user
 *
 * @since 1.0.0
 * @param object $user         The current user's $user object
 * @param array  $achievements array of user-earned achievement IDs
 */
function badgeos_profile_award_achievement( $user, $achievement_ids = array() ) {

	// Grab our achivement types
	$achievement_types = badgeos_get_achievement_types();
	?>

	<h2><?php _e( 'Award an Achievement', 'badgeos' ); ?></h2>

	<table class="form-table">
		<tr>
			<th><label for="thechoices"><?php _e( 'Select an Achievement Type to Award:', 'badgeos' ); ?></label></th>
			<td>
				<select id="thechoices">
				<option></option>
				<?php
				foreach ( $achievement_types as $achievement_slug => $achievement_type ) {
					echo '<option value="'. $achievement_slug .'">' . ucwords( $achievement_type['single_name'] ) .'</option>';
				}
				?>
				</select>
			</td>
		</tr>
		<tr><td id="boxes" colspan="2">
			<?php foreach ( $achievement_types as $achievement_slug => $achievement_type ) : ?>
				<table id="<?php echo esc_attr( $achievement_slug ); ?>" class="widefat badgeos-table">
					<thead><tr>
						<th><?php _e( 'Image', 'badgeos' ); ?></th>
						<th><?php echo ucwords( $achievement_type['single_name'] ); ?></th>
						<th><?php _e( 'Action', 'badgeos' ); ?></th>
						<th><?php _e( 'Awarded', 'badgeos' ); ?></th>
					</tr></thead>
					<tbody>
					<?php
					// Load achievement type entries
					$the_query = new WP_Query( array(
						'post_type'      => $achievement_slug,
						'posts_per_page' => '999',
						'post_status'    => 'publish'
					) );

					if ( $the_query->have_posts() ) : ?>

						<?php while ( $the_query->have_posts() ) : $the_query->the_post();

							// Setup our award URL
							$award_url = add_query_arg( array(
								'action'         => 'award',
								'achievement_id' => absint( get_the_ID() ),
								'user_id'        => absint( $user->ID )
							) );
							?>
							<tr>
								<td><?php the_post_thumbnail( array( 50, 50 ) ); ?></td>
								<td>
									<?php echo edit_post_link( get_the_title() ); ?>
								</td>
								<td>
									<?php if ( in_array( get_the_ID(), $achievement_ids ) ) :
										// Setup our revoke URL
										$revoke_url = add_query_arg( array(
											'action'         => 'revoke',
											'user_id'        => absint( $user->ID ),
											'achievement_id' => absint( get_the_ID() ),
										) );
										?>
										<span class="delete"><a class="error" href="<?php echo esc_url( wp_nonce_url( $revoke_url, 'badgeos_revoke_achievement' ) ); ?>"><?php _e( 'Revoke Award', 'badgeos' ); ?></a></span>
									<?php else : ?>
										<a href="<?php echo esc_url( wp_nonce_url( $award_url, 'badgeos_award_achievement' ) ); ?>"><?php printf( __( 'Award %s', 'badgeos' ), ucwords( $achievement_type['single_name'] ) ); ?></a>
									<?php endif; ?>

								</td>
							</tr>
						<?php endwhile; ?>

					<?php else : ?>
						<tr>
							<th><?php printf( __( 'No %s found.', 'badgeos' ), $achievement_type['plural_name'] ); ?></th>
						</tr>
					<?php endif; wp_reset_postdata(); ?>

					</tbody>
					</table><!-- #<?php echo esc_attr( $achievement_slug ); ?> -->
			<?php endforeach; ?>
		</td><!-- #boxes --></tr>
	</table>

	<script type="text/javascript">
		jQuery(document).ready(function($){
			<?php foreach ( $achievement_types as $achievement_slug => $achievement_type ) { ?>
				$('#<?php echo $achievement_slug; ?>').hide();
			<?php } ?>
			$("#thechoices").change(function(){
				if ( 'all' == this.value )
					$("#boxes").children().show();
				else
					$("#" + this.value).show().siblings().hide();
			}).change();
		});
	</script>
	<?php
}

/**
 * Process the adding/revoking of achievements on the user profile page
 *
 * @since 1.0.0
 */
function badgeos_process_user_data() {

	// Get minimum role setting for menus
	$badgeos_settings = get_option( 'badgeos_settings' );
	$minimum_role = ( ! empty( $badgeos_settings['minimum_role'] ) ) ? $badgeos_settings['minimum_role'] : 'administrator';

	//verify uesr meets minimum role to view earned badges
	if ( current_user_can( $minimum_role ) ) {

		// Process awarding achievement to user
		if ( isset( $_GET['action'] ) && 'award' == $_GET['action'] &&  isset( $_GET['user_id'] ) && isset( $_GET['achievement_id'] ) ) {

			// Verify our nonce
			check_admin_referer( 'badgeos_award_achievement' );

			// Award the achievement
			badgeos_award_achievement_to_user( absint( $_GET['achievement_id'] ), absint( $_GET['user_id'] ) );

			// Redirect back to the user editor
			wp_redirect( add_query_arg( 'user_id', absint( $_GET['user_id'] ), admin_url( 'user-edit.php' ) ) );
			exit();
		}

		// Process revoking achievement from a user
		if ( isset( $_GET['action'] ) && 'revoke' == $_GET['action'] && isset( $_GET['user_id'] ) && isset( $_GET['achievement_id'] ) ) {

			// Verify our nonce
			check_admin_referer( 'badgeos_revoke_achievement' );

			// Revoke the achievement
			badgeos_revoke_achievement_from_user( absint( $_GET['achievement_id'] ), absint( $_GET['user_id'] ) );

			// Redirect back to the user editor
			wp_redirect( add_query_arg( 'user_id', absint( $_GET['user_id'] ), admin_url( 'user-edit.php' ) ) );
			exit();

		}

	}

}
add_action( 'init', 'badgeos_process_user_data' );




/**
 * Returns array of user log ids (log/journey cpt) from post ids array and a user id.
 *
 *
 *
 */
function badgeos_get_userlog_ids( $post_ids, $user_id ){
	global $wpdb;
	if ( is_array( $post_ids ) ) {
		$post_ids = implode( ',', $post_ids );
		$sql = "SELECT a.ID FROM $wpdb->posts a, $wpdb->postmeta b WHERE a.ID = b.post_id AND a.post_author = ".$user_id." AND a.post_status = 'publish' AND b.meta_key = '_badgeos_log_achievement_id' and b.meta_value in ( ".$post_ids." )";
		$rs = $wpdb->get_results( $sql );
		foreach ( $rs as $post ) {
			$log_ids[] = $post->ID;
		}
		return $log_ids;
	}
}
