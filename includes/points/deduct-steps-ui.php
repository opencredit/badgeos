<?php
/**
 * Custom Points Steps UI
 *
 * @package Badgeos
 * @subpackage Points
 * @author LearningTimes
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com/
 */

/**
 * Add our Steps JS to the Achievement post editor 
 *
 * @return void
 */
function badgeos_deduct_steps_ui_admin_scripts() {

    global $post_type;
	$settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
	
	if ( trim($post_type) == $settings['points_main_post_type'] ) {
    	wp_enqueue_script( 'badgeos-deduct-steps-ui', $GLOBALS['badgeos']->directory_url . 'js/deduct-steps-ui.js', array( 'jquery-ui-sortable' ), BadgeOS::$version );
    }
}
add_action( 'admin_print_scripts-post-new.php', 'badgeos_deduct_steps_ui_admin_scripts', 11 );
add_action( 'admin_print_scripts-post.php', 'badgeos_deduct_steps_ui_admin_scripts', 11 );

/**
 * Adds our Steps metabox to the Badge post editor
 *
 * @return void
 */
function badgeos_add_deduct_steps_ui_meta_box() {
	
	$settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
	add_meta_box( 'badgeos_deduct_steps_ui', apply_filters( 'badgeos_deduct_steps_ui_title', __( 'Points Deducts', 'badgeos' ) ), 'badgeos_deduct_steps_ui_meta_box', trim( $settings['points_main_post_type'] ), 'advanced', 'high' );
}
add_action( 'add_meta_boxes', 'badgeos_add_deduct_steps_ui_meta_box' );

/**
 * Renders the HTML for meta box, refreshes whenever a new step is added
 *
 * @param  object $post The current post object
 * @return void
 */
function badgeos_deduct_steps_ui_meta_box($post  = null) {
	
	global $wpdb;
	$settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
	$required_steps = $wpdb->get_results( $wpdb->prepare( "SELECT p.*, pp.* FROM $wpdb->p2p as pp inner join $wpdb->posts as p on(pp.p2p_from = p.ID) WHERE pp.p2p_to = %d and p.post_type = %s", $post->ID,trim( $settings['points_deduct_post_type'] )  ) );

	/**
     * Loop through each step and set the sort order
     */
	foreach ( $required_steps as $required_step ) {
		$required_step->order = get_deduct_step_menu_order( $required_step->ID );
	}

	/**
     * Sort the steps by their order
     */
	uasort( $required_steps, 'badgeos_compare_step_order');

	echo '<p>' .__( 'Defined points will be deducted when any of the following "steps" will be considered as complete. Use the "Label" field to optionally customize the titles of each step.', 'badgeos' ). '</p>';

	/**
     * Concatenate our step output
     */
	echo '<ul id="deduct_steps_list">';
	foreach ( $required_steps as $step ) {
		badgeos_deduct_steps_ui_html( $step->ID, $post->ID );
	}
	echo '</ul>';

	/**
     * Render our buttons
     */
	echo '<input style="margin-right: 1em" class="button" type="button" onclick="badgeos_add_new_deduct_step(' . $post->ID . ');" value="' . apply_filters( 'badgeos_deduct_steps_ui_add_new', __( 'Add New Step', 'badgeos' ) ) . '">';
	echo '<input class="button-primary" type="button" onclick="badgeos_update_deduct_steps();" value="' . apply_filters( 'badgeos_deduct_steps_ui_save_all', __( 'Save All Steps', 'badgeos' ) ) . '">';
	echo '<img class="save-steps-spinner save-deduct-steps-spinner" src="' . admin_url( '/images/wpspin_light.gif' ) . '" style="margin-left: 10px; display: none;" />';
}

/**
 * Helper function for generating the HTML output for configuring a given step
 *
 * @param  integer $step_id The given step's ID
 * @param  integer $post_id The given step's parent $post ID
 * @return string           The concatenated HTML input for the step
 */
function badgeos_deduct_steps_ui_html($step_id = 0, $post_id = 0 ) {

	/**
     * Grab our step's requirements and measurement
     */
	$requirements      = badgeos_get_deduct_step_requirements( $step_id );
	$count             = !empty( $requirements['count'] ) ? $requirements['count'] : 1;
	$achievement_types = badgeos_get_achievement_types_slugs();
	$settings          = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
    ?>

	<li class="step-row step-<?php echo $step_id; ?>" data-step-id="<?php echo $step_id; ?>">
		<div class="step-handle"></div>
		<a class="delete-step" href="javascript: badgeos_delete_deduct_step( <?php echo $step_id; ?> );"><?php _e( 'Delete', 'badgeos' ); ?></a>
		<input type="hidden" name="post_id" value="<?php echo $post_id; ?>" />
		<input type="hidden" name="order" value="<?php echo get_deduct_step_menu_order( $step_id ); ?>" />

		<?php echo apply_filters( 'badgeos_deduct_steps_ui_html_require_text', __( 'Require', 'badgeos' ), $step_id, $post_id ); ?>

		<?php do_action( 'badgeos_deduct_steps_ui_html_after_require_text', $step_id, $post_id ); ?>

		<select class="select-trigger-type" data-step-id="<?php echo $step_id; ?>">
			<?php
				foreach (get_badgeos_points_deduct_activity_triggers() as $value => $label ) {
					echo '<option value="' . esc_attr( $value ) . '" ' . selected( $requirements['trigger_type'], $value, false ) . '>' . esc_html( $label ) . '</option>';
				}
			?>
		</select>

		<?php do_action( 'badgeos_deduct_steps_ui_html_after_trigger_type', $step_id, $post_id ); ?>

		<select class="select-achievement-type select-achievement-type-<?php echo $step_id; ?>">
			<?php
				foreach ( $achievement_types as $achievement_type ) {
					if ( $settings['points_deduct_post_type'] == $achievement_type ){
						continue;
					}
					echo '<option value="' . $achievement_type . '" ' . selected( $requirements['achievement_type'], $achievement_type, false ) . '>' . ucfirst( $achievement_type ) . '</option>';
				}
			?>
		</select>

		<?php do_action( 'badgeos_deduct_steps_ui_html_after_achievement_type', $step_id, $post_id ); ?>

		<select class="select-achievement-post select-achievement-post-<?php echo $step_id; ?>">
			<option value=""></option>
		</select>

		<input type="text" size="5" placeholder="<?php _e( 'Post ID', 'badgeos' ); ?>" value="<?php esc_attr_e( $requirements['achievement_post'] ); ?>" class="select-achievement-post select-achievement-post-<?php echo $step_id; ?>">

		<?php do_action( 'badgeos_deduct_steps_ui_html_after_achievement_post', $step_id, $post_id ); ?>
		<input class="point-value" type="number" size="3" maxlength="3" value="<?php echo $requirements['_point_value']; ?>" placeholder="<?php _e( 'Points', 'badgeos' ); ?>">		
		<input class="required-count" type="text" size="3" maxlength="3" value="<?php echo $count; ?>" placeholder="1">
		<?php echo apply_filters( 'badgeos_deduct_steps_ui_html_count_text', __( 'time(s).', 'badgeos' ), $step_id, $post_id ); ?>

		<?php do_action( 'badgeos_deduct_steps_ui_html_after_count_text', $step_id, $post_id ); ?>

		<div class="step-title"><label for="step-<?php echo $step_id; ?>-title"><?php _e( 'Label', 'badgeos' ); ?>:</label> <input type="text" name="step-title" id="step-<?php echo $step_id; ?>-title" class="title" value="<?php echo get_the_title( $step_id ); ?>" /></div>
		<span class="spinner spinner-step-<?php echo $step_id;?>"></span>
	</li>
	<?php
}

/** 
 * Get all the requirements of a given step
 *
 * @param  integer $step_id The given step's post ID
 * @return array|bool       An array of all the step requirements if it has any, false if not
 */
function badgeos_get_deduct_step_requirements($step_id = 0 ) {

	/**
     * Setup our default requirements array, assume we require nothing
     */
	$requirements = array(
		'count'            => absint( get_post_meta( $step_id, '_badgeos_count', true ) ),
		'_point_value'    => absint( get_post_meta( $step_id, '_point_value', true ) ),
		'trigger_type'     => get_post_meta( $step_id, '_deduct_trigger_type', true ),
		'achievement_type' => get_post_meta( $step_id, '_badgeos_achievement_type', true ),
		'achievement_post' => absint( get_post_meta( $step_id, '_badgeos_achievement_post', true ) ),
	);

	/**
     * If the step requires a specific achievement
     */
	if ( ! empty( $requirements['achievement_type'] ) ) {
		$connected_activities = @get_posts( array(
			'post_type'        => $requirements['achievement_type'],
			'posts_per_page'   => 1,
			'suppress_filters' => false,
			'connected_type'   => $requirements['achievement_type'] . '-to-step',
			'connected_to'     => $step_id
		));
		if ( ! empty( $connected_activities ) )
			$requirements['achievement_post'] = $connected_activities[0]->ID;
	} elseif ( 'badgeos_specific_new_comment' === $requirements['trigger_type'] ) {
		$achievement_post = absint( get_post_meta( $step_id, '_badgeos_achievement_post', true ) );
		if ( 0 < $achievement_post ) {
			$requirements[ 'achievement_post' ] = $achievement_post;
		}
	}

	/**
     * Available filter for overriding elsewhere
     */
	return apply_filters( 'badgeos_get_deduct_step_requirements', $requirements, $step_id );
}

/**
 * AJAX Handler for adding a new step
 *
 * @return void
 */
function badgeos_add_deduct_step_ajax_handler() {

	/**
     * Create a new Step post and grab it's ID
     */
	$settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
	$step_id = wp_insert_post( array(
		'post_type'   => $settings['points_deduct_post_type'],
		'post_status' => 'publish'
	) );

	/**
     * Output the edit step html to insert into the Steps metabox
     */
	badgeos_deduct_steps_ui_html( $step_id, $_POST['achievement_id'] );

	/**
     * Grab the post object for our Achievement
     */
	$achievement = get_post( $_POST['achievement_id'] );

	/**
     * Create the P2P connection from the step to the Achievement
     */
	$p2p_id = p2p_create_connection(
		'point_deduct-to-' . $achievement->post_type,
		array(
			'from' => $step_id,
			'to'   => $_POST['achievement_id'],
			'meta' => array(
				'date' => current_time( 'mysql' )
			)
		)
	);

	/**
     * Add relevant meta to our P2P connection
     */
	p2p_add_meta( $p2p_id, 'order', '0' );

	/**
     * Die here, because it's AJAX
     */
	die;
}
add_action( 'wp_ajax_add_deduct_step', 'badgeos_add_deduct_step_ajax_handler' );

/**
 * AJAX Handler for deleting a step
 *
 * @return void
 */
function badgeos_delete_deduct_step_ajax_handler() {
	wp_delete_post( $_POST['step_id'] );
	die;
}
add_action( 'wp_ajax_delete_deduct_step', 'badgeos_delete_deduct_step_ajax_handler' );

/**
 * AJAX Handler for saving all steps
 *
 * @return void
 */
function badgeos_update_deduct_steps_ajax_handler() {

	/**
     * Only continue if we have any steps
     */
	if ( isset( $_POST['steps'] ) ) {

		/**
         * Grab our $wpdb global
         */
		global $wpdb;

		/**
         * Setup an array for storing all our step titles
         * This lets us dynamically update the Label field when steps are saved
         */
		$new_titles = array();

		/**
         * Loop through each of the created steps
         */
		foreach ( $_POST['steps'] as $key => $step ) {

			/**
             * Grab all of the relevant values of that step
             */
			$step_id          = $step['step_id'];
			$required_count   = ( ! empty( $step['required_count'] ) ) ? $step['required_count'] : 1;
			$point_value   = ( ! empty( $step['point_value'] ) ) ? $step['point_value'] : 0;

			$trigger_type     = $step['trigger_type'];
			$achievement_type = $step['achievement_type'];

			/**
             * Clear all relation data
             */
			$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->p2p WHERE p2p_to=%d", $step_id ) );
			delete_post_meta( $step_id, '_badgeos_achievement_post' );

			/**
             * Flip between our requirement types and make an appropriate connection
             */
			switch ( $trigger_type ) {

				/**
                 * Connect the step to ANY of the given achievement type
                 */
				case 'any-achievement' :
					$title = sprintf( __( 'any %s', 'badgeos' ), $achievement_type );
					break;
				case 'all-achievements' :
					$title = sprintf( __( 'all %s', 'badgeos' ), $achievement_type );
					break;
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
				case 'badgeos_specific_new_comment' :
					update_post_meta( $step_id, '_badgeos_achievement_post', absint( $step['achievement_post'] ) );
					$title = sprintf( __( 'comment on post %d', 'badgeos' ),  $step['achievement_post'] );
					break;
				default :
					$triggers = badgeos_get_activity_triggers();
					$title = $triggers[$trigger_type];
				break;

			}

			/**
             * Update the step order
             */
			p2p_update_meta( badgeos_get_p2p_id_from_child_id( $step_id ), 'order', $key );

			/**
             * Update our relevant meta
             */
			update_post_meta( $step_id, '_badgeos_count', $required_count );
			update_post_meta( $step_id, '_point_value', $point_value );
			update_post_meta( $step_id, '_deduct_trigger_type', $trigger_type );
			update_post_meta( $step_id, '_badgeos_achievement_type', $achievement_type );

			/**
             * Available hook for custom Activity Triggers
             */
			$custom_title = sprintf( __( 'Earn %1$s %2$s.', 'badgeos' ), $title, sprintf( _n( '%d time', '%d times', $required_count ), $required_count ) );
			$custom_title = apply_filters( 'badgeos_save_step', $custom_title, $step_id, $step );

			/**
             * Update our original post with the new title
             */
			$post_title = !empty( $step['title'] ) ? $step['title'] : $custom_title;
			wp_update_post( array( 'ID' => $step_id, 'post_title' => $post_title ) );

			/**
             * Add the title to our AJAX return
             */
			$new_titles[$step_id] = stripslashes( $post_title );

		}

		/**
         * Send back all our step titles
         */
		echo json_encode($new_titles);

	}

	die;
}
add_action( 'wp_ajax_update_deduct_steps', 'badgeos_update_deduct_steps_ajax_handler' );

/**
 * AJAX helper for getting our posts and returning select options
 *
 * @return void
 */
function badgeos_activity_trigger_post_deduct_select_ajax_handler() {

	/**
     * Grab our achievement type from the AJAX request
     */
	$achievement_type = $_REQUEST['achievement_type'];
	$exclude_posts = (array) $_REQUEST['excluded_posts'];
	$requirements = badgeos_get_deduct_step_requirements( $_REQUEST['step_id'] );

	/**
     * If we don't have an achievement type, bail now
     */
	if ( empty( $achievement_type ) ) {
		die();
	}

	/**
     * Grab all our posts for this achievement type
     */
	$achievements = get_posts( array(
		'post_type'      => $achievement_type,
		'post__not_in'   => $exclude_posts,
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	));

	/**
     * Setup our output
     */
	$output = '<option></option>';
	foreach ( $achievements as $achievement ) {
		$output .= '<option value="' . $achievement->ID . '" ' . selected( $requirements['achievement_post'], $achievement->ID, false ) . '>' . $achievement->post_title . '</option>';
	}

	/**
     * Send back our results and die like a man
     */
	echo $output;
	die();
}
add_action( 'wp_ajax_post_deduct_select_ajax', 'badgeos_activity_trigger_post_deduct_select_ajax_handler' );

/**
 * Get the the ID of a post connected to a given child post ID
 *
 * @param  integer $child_id The given child's post ID
 * @return integer           The resulting connected post ID
 */
function badgeos_get_p2p_deduct_id_from_child_id( $child_id = 0 ) {

	global $wpdb;
	$p2p_id = $wpdb->get_var( $wpdb->prepare( "SELECT p2p_id FROM $wpdb->p2p WHERE p2p_from = %d ", $child_id ) );
	return $p2p_id;
}

/**
 * Get the sort order for a given step
 *
 * @param  integer $step_id The given step's post ID
 * @return integer          The step's sort order
 */
function get_deduct_step_menu_order( $step_id = 0 ) {

	global $wpdb;
	$p2p_id = $wpdb->get_var( $wpdb->prepare( "SELECT p2p_id FROM $wpdb->p2p WHERE p2p_from = %d", $step_id ) );
	$menu_order = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $wpdb->p2pmeta WHERE p2p_id=%d AND meta_key='order'", $p2p_id ) );
	if ( ! $menu_order || $menu_order == 'NaN' ) $menu_order = '0';
	return $menu_order;
}

/**
 * Helper function for comparing our step sort order (used in uasort() in badgeos_create_steps_meta_box())
 *
 * @param  integer $step1 The order number of our given step
 * @param  integer $step2 The order number of the step we're comparing against
 * @return integer        0 if the order matches, -1 if it's lower, 1 if it's higher
 */
function badgeos_compare_deduct_step_order($step1 = 0, $step2 = 0 ) {
	if ( $step1->order == $step2->order ) return 0;
	return ( $step1->order < $step2->order ) ? -1 : 1;
}
