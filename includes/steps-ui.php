<?php
/**
 * Custom Achievement Steps UI
 *
 * @package BadgeOS
 * @subpackage Achievements
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Add our Steps JS to the Badge post editor
 *
 * @since 1.0.0
 */
function badgeos_steps_ui_admin_scripts() {
    global $post_type;
    if ( in_array( $post_type, badgeos_get_achievement_types_slugs() ) ) {
    	wp_enqueue_script( 'badgeos-steps-ui', $GLOBALS['badgeos']->directory_url . 'js/steps-ui.js', array( 'jquery-ui-sortable' ), '2.0.0' );
    }
}
add_action( 'admin_print_scripts-post-new.php', 'badgeos_steps_ui_admin_scripts', 11 );
add_action( 'admin_print_scripts-post.php', 'badgeos_steps_ui_admin_scripts', 11 );

/**
 * Adds our Steps metabox to the Badge post editor
 *
 * @since  1.0.0
 */
function badgeos_add_steps_ui_meta_box() {
	$achievement_types = badgeos_get_achievement_types_slugs();
	foreach ( $achievement_types as $achievement_type ) {
		add_meta_box( 'badgeos_steps_ui', apply_filters( 'badgeos_steps_ui_title', __( 'Required Steps', 'badgeos' ) ), 'badgeos_steps_ui_meta_box', $achievement_type, 'advanced', 'high' );
	}
}
add_action( 'admin_menu', 'badgeos_add_steps_ui_meta_box', 99 );

/**
 * Renders the HTML for meta box, refreshes whenever a new step is added
 *
 * @since  1.0.0
 * @param  object $post The current post object
 */
function badgeos_steps_ui_meta_box( $post ) {

	// Grab our $wpdb global
	global $wpdb;

	// Grab our Badge's required steps
	$required_steps = get_posts( array(
		'post_type'           => 'step',
		'posts_per_page'      => -1,
		'suppress_filters'    => false,
		'connected_direction' => 'to',
		'connected_type'      => 'step-to-' . $post->post_type,
		'connected_items'     => $post->ID,
	));

	// Loop through each step and set the sort order
	foreach ( $required_steps as $required_step ) {
		$required_step->order = get_step_menu_order( $required_step->ID );
	}

	// Sort the steps by their order
	uasort( $required_steps, 'badgeos_compare_step_order' );

	echo '<p>' .__( 'Define the required "steps" for this achievement to be considered complete. Use the "Label" field to optionally customize the titles of each step.', 'badgeos' ). '</p>';

	// Concatenate our step output
	echo '<ul id="steps_list">';
	foreach ( $required_steps as $step ) {
		badgeos_steps_ui_html( $step->ID, $post->ID );
	}
	echo '</ul>';

	// Render our buttons
	echo '<input style="margin-right: 1em" class="button" type="button" onclick="badgeos_add_new_step(' . $post->ID . ');" value="' . apply_filters( 'badgeos_steps_ui_add_new', __( 'Add New Step', 'badgeos' ) ) . '">';
	echo '<input class="button-primary" type="button" onclick="badgeos_update_steps();" value="' . apply_filters( 'badgeos_steps_ui_save_all', __( 'Save All Steps', 'badgeos' ) ) . '">';
	echo '<img class="save-steps-spinner" src="' . admin_url( '/images/wpspin_light.gif' ) . '" style="margin-left: 10px; display: none;" />';

}

/**
 * Helper function for generating the HTML output for configuring a given step
 *
 * @since  1.0.0
 * @param  integer $step_id The given step's ID
 * @param  integer $post_id The given step's parent $post ID
 * @return string           The concatenated HTML input for the step
 */
function badgeos_steps_ui_html( $step_id, $post_id ) {

	// Grab our step's requirements and measurement
	$requirements      = badgeos_get_step_requirements( $step_id );
	$count             = !empty( $requirements['count'] ) ? $requirements['count'] : 1;
	$achievement_types = badgeos_get_achievement_types_slugs();
?>

	<li class="step-row step-<?php echo $step_id; ?>" data-step-id="<?php echo $step_id; ?>">
		<div class="step-handle"></div>
		<a class="delete-step" href="javascript: badgeos_delete_step( <?php echo $step_id; ?> );"><?php _e( 'Delete', 'badgeos' ); ?></a>
		<input type="hidden" name="post_id" value="<?php echo $post_id; ?>" />
		<input type="hidden" name="order" value="<?php echo get_step_menu_order( $step_id ); ?>" />

		<?php echo apply_filters( 'badgeos_steps_ui_html_require_text', __( 'Require', 'badgeos' ), $step_id, $post_id ); ?>

		<?php do_action( 'badgeos_steps_ui_html_after_require_text', $step_id, $post_id ); ?>

		<select class="select-trigger-type" data-step-id="<?php echo $step_id; ?>">
			<?php
				foreach ( badgeos_get_activity_triggers() as $value => $label ) {
					echo '<option value="' . esc_attr( $value ) . '" ' . selected( $requirements['trigger_type'], $value, false ) . '>' . esc_html( $label ) . '</option>';
				}
			?>
		</select>

		<?php do_action( 'badgeos_steps_ui_html_after_trigger_type', $step_id, $post_id ); ?>

		<select class="select-achievement-type select-achievement-type-<?php echo $step_id; ?>">
			<option value=""></option>
			<?php
				foreach ( $achievement_types as $achievement_type ) {
					echo '<option value="' . $achievement_type . '" ' . selected( $requirements['achievement_type'], $achievement_type, false ) . '>' . ucfirst( $achievement_type ) . '</option>';
				}
			?>
		</select>

		<?php do_action( 'badgeos_steps_ui_html_after_achievement_type', $step_id, $post_id ); ?>

		<select class="select-achievement-post select-achievement-post-<?php echo $step_id; ?>">
			<option value=""></option>
		</select>

		<?php do_action( 'badgeos_steps_ui_html_after_achievement_post', $step_id, $post_id ); ?>

		<input class="required-count" type="text" size="2" maxlength="2" value="<?php echo $count; ?>" placeholder="1">
		<?php echo apply_filters( 'badgeos_steps_ui_html_count_text', __( 'time(s).', 'badgeos' ), $step_id, $post_id ); ?>

		<?php do_action( 'badgeos_steps_ui_html_after_count_text', $step_id, $post_id ); ?>

		<div class="step-title"><label for="step-<?php echo $step_id; ?>-title">Label:</label> <input type="text" name="step-title" id="step-<?php echo $step_id; ?>-title" class="title" value="<?php echo get_the_title( $step_id ); ?>" /></div>
		<span class="spinner spinner-step-<?php echo $step_id;?>"></span>
	</li>
	<?php
}

/**
 * Get all the requirements of a given step
 *
 * @since  1.0.0
 * @param  integer $step_id The given step's post ID
 * @return array|bool       An array of all the step requirements if it has any, false if not
 */
function badgeos_get_step_requirements( $step_id ) {

	// Setup our default requirements array, assume we require nothing
	$requirements = array(
		'count'            => absint( get_post_meta( $step_id, '_badgeos_count', true ) ),
		'trigger_type'     => get_post_meta( $step_id, '_badgeos_trigger_type', true ),
		'achievement_type' => get_post_meta( $step_id, '_badgeos_achievement_type', true ),
		'achievement_post' => ''
	);

	// If the step requires a specific achievement
	if ( !empty( $requirements['achievement_type'] ) ) {
		$connected_activities = get_posts( array(
			'post_type'        => $requirements['achievement_type'],
			'posts_per_page'   => 1,
			'suppress_filters' => false,
			'connected_type'   => $requirements['achievement_type'] . '-to-step',
			'connected_to'     => $step_id
		));
		if ( ! empty( $connected_activities ) )
			$requirements['achievement_post'] = $connected_activities[0]->ID;
	}

	// Available filter for overriding elsewhere
	return apply_filters( 'badgeos_get_step_requirements', $requirements, $step_id );
}

/**
 * AJAX Handler for adding a new step
 *
 * @since 1.0.0
 */
function badgeos_add_step_ajax_handler() {

	// Create a new Step post and grab it's ID
	$step_id = wp_insert_post( array(
		'post_type'   => 'step',
		'post_status' => 'publish'
	) );

	// Output the edit step html to insert into the Steps metabox
	badgeos_steps_ui_html( $step_id, $_POST['achievement_id'] );

	// Grab the post object for our Badge
	$achievement = get_post( $_POST['achievement_id'] );

	// Create the P2P connection from the step to the badge
	$p2p_id = p2p_create_connection(
		'step-to-' . $achievement->post_type,
		array(
			'from' => $step_id,
			'to'   => $_POST['achievement_id'],
			'meta' => array(
				'date' => current_time( 'mysql' )
			)
		)
	);

	// Add relevant meta to our P2P connection
	p2p_add_meta( $p2p_id, 'order', '0' );

	// Die here, because it's AJAX
	die;
}
add_action( 'wp_ajax_add_step', 'badgeos_add_step_ajax_handler' );

/**
 * AJAX Handler for deleting a step
 *
 * @since 1.0.0
 */
function badgeos_delete_step_ajax_handler() {
	wp_delete_post( $_POST['step_id'] );
	die;
}
add_action( 'wp_ajax_delete_step', 'badgeos_delete_step_ajax_handler' );

/**
 * AJAX Handler for saving all steps
 *
 * @since 1.0.0
 */
function badgeos_update_steps_ajax_handler() {

	// Only continue if we have any steps
	if ( isset( $_POST['steps'] ) ) {

		// Grab our $wpdb global
		global $wpdb;

		// Setup an array for storing all our step titles
		// This lets us dynamically update the Label field when steps are saved
		$new_titles = array();

		// Loop through each of the created steps
		foreach ( $_POST['steps'] as $step ) {

			// Grab all of the relevant values of that step
			$step_id          = $step['step_id'];
			$order            = $step['order'];
			$required_count   = ( ! empty( $step['required_count'] ) ) ? $step['required_count'] : 1;
			$trigger_type     = $step['trigger_type'];
			$achievement_type = $step['achievement_type'];

			// Clear all relation data
			$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->p2p WHERE p2p_to=%d", $step_id ) );

			// Flip between our requirement types and make an appropriate connection
			switch ( $trigger_type ) {

				// Connect the step to ANY of the given achievement type
				case 'any-achievement' :
					$title = sprintf( __( 'any %s', 'badgeos' ), $achievement_type );
				break;

				// Connect the step to ALL of the given achievement type
				case 'all-achievements' :
					$title = sprintf( __( 'all %s', 'badgeos' ), $achievement_type );
				break;

				// Connect the step to a specific achievement
				case 'specific-achievement' :
					p2p_create_connection(
						$step['achievement_type'] . '-to-step',
						array(
							'from' => absint( $step['achievement_post'] ),
							'to'   => $step_id,
							'meta' => array(
								'date' => current_time('mysql')
							)
						)
					);
					$title = '"' . get_the_title( $step['achievement_post'] ) . '"';
				break;

				default :
					$triggers = badgeos_get_activity_triggers();
					$title = $triggers[$trigger_type];
				break;

			}

			// Update the step order
			p2p_update_meta( badgeos_get_p2p_id_from_child_id( $step_id ), 'order', $order );

			// Update our relevant meta
			update_post_meta( $step_id, '_badgeos_count', $required_count );
			update_post_meta( $step_id, '_badgeos_trigger_type', $trigger_type );
			update_post_meta( $step_id, '_badgeos_achievement_type', $achievement_type );

			// Available hook for custom Activity Triggers
			$custom_title = sprintf( __( 'Earn %s %s.', 'badgeos' ), $title, sprintf( _n( '%d time', '%d times', $required_count ), $required_count ) );
			$custom_title = apply_filters( 'badgeos_save_step', $custom_title, $step_id, $step );

			// Update our original post with the new title
			$post_title = !empty( $step['title'] ) ? $step['title'] : $custom_title;
			wp_update_post( array( 'ID' => $step_id, 'post_title' => $post_title ) );

			// Add the title to our AJAX return
			$new_titles[$step_id] = stripslashes( $post_title );

		}

		// Send back all our step titles
		echo json_encode($new_titles);

	}

	// Cave Johnson. We're done here.
	die;

}
add_action( 'wp_ajax_update_steps', 'badgeos_update_steps_ajax_handler' );

/**
 * AJAX helper for getting our posts and returning select options
 *
 * @since  1.0.0
 */
function badgeos_activity_trigger_post_select_ajax_handler() {

	// Grab our achievement type from the AJAX request
	$achievement_type = $_REQUEST['achievement_type'];
	$exclude_posts = (array) $_REQUEST['excluded_posts'];
	$requirements = badgeos_get_step_requirements( $_REQUEST['step_id'] );

	// If we don't have an achievement type, bail now
	if ( empty( $achievement_type ) )
		die();

	// Grab all our posts for this achievement type
	$achievements = get_posts( array(
		'post_type'      => $achievement_type,
		'post__not_in'   => $exclude_posts,
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	));

	// Setup our output
	$output = '<option></option>';
	foreach ( $achievements as $achievement ) {
		$output .= '<option value="' . $achievement->ID . '" ' . selected( $requirements['achievement_post'], $achievement->ID, false ) . '>' . $achievement->post_title . '</option>';
	}

	// Send back our results and die like a man
	echo $output;
	die();
}
add_action( 'wp_ajax_post_select_ajax', 'badgeos_activity_trigger_post_select_ajax_handler' );

/**
 * Get the the ID of a post connected to a given child post ID
 *
 * @since  1.0.0
 * @param  integer $child_id The given child's post ID
 * @return integer           The resulting connected post ID
 */
function badgeos_get_p2p_id_from_child_id( $child_id ) {
	global $wpdb;
	$p2p_id = $wpdb->get_var( $wpdb->prepare( "SELECT p2p_id FROM $wpdb->p2p WHERE p2p_from = %d ", $child_id ) );
	return $p2p_id;
}

/**
 * Get the sort order for a given step
 *
 * @since  1.0.0
 * @param  integer $step_id The given step's post ID
 * @return integer          The step's sort order
 */
function get_step_menu_order( $step_id ) {
	global $wpdb;
	$p2p_id = $wpdb->get_var( $wpdb->prepare( "SELECT p2p_id FROM $wpdb->p2p WHERE p2p_from = %d", $step_id ) );
	$menu_order = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $wpdb->p2pmeta WHERE p2p_id=%d AND meta_key='order'", $p2p_id ) );
	if ( ! $menu_order || $menu_order == 'NaN' ) $menu_order = '0';
	return $menu_order;
}

/**
 * Helper function for comparing our step sort order (used in uasort() in badgeos_create_steps_meta_box())
 *
 * @since  1.0.0
 * @param  integer $step1 The order number of our given step
 * @param  integer $step2 The order number of the step we're comparing against
 * @return integer        0 if the order matches, -1 if it's lower, 1 if it's higher
 */
function badgeos_compare_step_order( $step1, $step2 ) {
	if ( $step1->order == $step2->order ) return 0;
	return ( $step1->order < $step2->order ) ? -1 : 1;
}
