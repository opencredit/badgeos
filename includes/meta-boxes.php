<?php
/**
 * Admin Meta Boxes
 *
 * @package BadgeOS
 * @subpackage Admin
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Register custom meta boxes for BadgeOS achievement type
 *
 * @since  1.0.0
 * @param  none
 * @return none
 */
function badgeos_achievment_type_metaboxes() {

	// Start with an underscore to hide fields from custom fields list
	$prefix = '_badgeos_';

	// Grab our achievement types as an array
	$achievement_types = badgeos_get_achievement_types_slugs();

	// Setup our $post_id, if available
	$post_id = isset( $_GET['post'] ) ? $_GET['post'] : 0;

    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();

	// New Achievement Types
    $cmb_obj = new_cmb2_box( array(
        'id'            => 'achievement_type_data',
        'title'         => esc_html__( 'Achievement Type Data', 'badgeos' ),
        'object_types'  => array( $badgeos_settings['achievement_main_post_type'] ),
        'context'    => 'normal',
        'priority'   => 'high',
        'show_names' => true, // Show field names on the left
    ) );
    $cmb_obj->add_field(array(
        'name' => __( 'Plural Name', 'badgeos' ),
        'desc' => __( 'The plural name for this achievement (Title is singular name).', 'badgeos' ),
        'id'   => $prefix . 'plural_name',
        'type' => 'text_medium',
    ));
    $cmb_obj->add_field(array(
        'name'    => __( 'Show in Menu?', 'badgeos' ),
        'desc' 	 => ' '.__( 'Yes, show this achievement in the BadgeOS menu.', 'badgeos' ),
        'id'      => $prefix . 'show_in_menu',
        'type'	 => 'checkbox',
    ));
    
    if( badgeos_first_time_installed() ) {
        $cmb_obj->add_field(array(
            'name' => __( 'Default Badge Image', 'badgeos' ),
            'desc' => esc_html__(  sprintf(
                __( 'To set a default image, use the <strong>Default Achievement Image</strong> metabox to the right. For best results, use a square .png file with a transparent background, at least 200x200 pixels. Or, design a badge using the %1$s.', 'badgeos' ),
                badgeos_get_badge_builder_link( array( 'link_text' => __( 'Credly Badge Builder', 'badgeos' ) ) )
            ), 'cmb2' ),
            'id'   => $prefix . 'upload_badge_image_achievement',
            'type' => 'text_only',
        ));
    }
}
add_action( 'cmb2_admin_init', 'badgeos_achievment_type_metaboxes' );

/**
 * Add Custom field type
 *
 * @since  1.0.0
 * @param  $field_object
 * @param  $escaped_value
 * @param  $object_id
 * @param  $object_type
 * @param  $field_type_object
 * @return none
 */
function sch_cmb_render_text_only( $field_object, $escaped_value, $object_id, $object_type, $field_type_object ) {
    echo htmlspecialchars_decode($field_object->args["desc"]);
}
add_action( 'cmb2_render_text_only', 'sch_cmb_render_text_only', 10, 5 );


/**
 * Register custom meta boxes for Badgeos achievements.
 *
 * @since  1.0.0
 * @param  none
 * @return none
 */
function badgeos_achievment_metaboxes( ) {

    // Start with an underscore to hide fields from custom fields list
    $prefix = '_badgeos_';

    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();

    // Grab our achievement types as an array
    $achievement_types_temp = badgeos_get_achievement_types_slugs();
    $achievement_types = array();
    if( $achievement_types_temp ) {
        foreach( $achievement_types_temp as $key=>$ach ) {
            if( ! empty( $ach ) && $ach != trim( $badgeos_settings['achievement_step_post_type'] ) ) {
                if (!empty($ach) && $ach != 'step') {
                    $achievement_types[] = $ach;
                }
            }
        }
    }

    // Setup our $post_id, if available
    $post_id = isset( $_GET['post'] ) ? $_GET['post'] : 0;

    // New Achievement Types
    $cmb_obj = new_cmb2_box( array(
        'id'            => 'achievement_data',
        'title'         => __( 'Achievement Data', 'badgeos' ),
        'object_types'  => $achievement_types, // Post type
        'context'    => 'advanced',
        'priority'   => 'high',
        'show_names' => true, // Show field names on the left
    ) );
    
    if( badgeos_first_time_installed() ) {
        $cmb_obj->add_field(array(
            'name' => __( 'Upload Badge Image', 'badgeos' ),
            'desc' => sprintf(
                __( '<p>To set an image use the <strong>Achievement Image</strong> metabox to the right. For best results, use a square .png file with a transparent background, at least 200x200 pixels. Or, design a badge using the %1$s.</p><p>If no image is specified, this achievement will default to the %2$s featured image.</p>', 'badgeos' ),
                badgeos_get_badge_builder_link( array( 'link_text' => __( 'Credly Badge Builder', 'badgeos' ) ) ),
                '<a href="' . admin_url('edit.php?post_type=' . $badgeos_settings[ 'achievement_main_post_type' ] ) . '">' . __( 'Achievement Type\'s', 'badgeos' ) . '</a>'

            ),
            'id'   => $prefix . 'upload_badge_image_achievement',
            'type' => 'text_only',
        ));
    }

    $cmb_obj->add_field(array(
        'name' => __( 'Points Awarded', 'badgeos' ),
        'desc' => ' '.__( 'Points awarded for earning this achievement (optional). Leave empty if no points are awarded.', 'badgeos' ),
        'id'   => $prefix . 'points',
        'type' => 'credit_field',
    ));

    $cmb_obj->add_field(array(
        'name'    => __( 'Earned By:', 'badgeos' ),
        'desc'    => __( 'How this achievement can be earned.', 'badgeos' ),
        'id'      => $prefix . 'earned_by',
        'type'    => 'select',
        'options' => apply_filters( 'badgeos_achievement_earned_by', array(
            'triggers' => __( 'Completing Steps', 'badgeos' ),
            'points' => __( 'Minimum Number of Points'),
            'admin' => __( 'Admin-awarded Only', 'badgeos' )
        ))
    ));

    $cmb_obj->add_field(array(
        'name' => __( 'Minimum Points Requried', 'badgeos' ),
        'desc' => ' '.__( 'Fewest number of points required for earning this achievement.', 'badgeos' ),
        'id'   => $prefix . 'points_required',
        'type' => 'credit_field',
    ));

    $cmb_obj->add_field(array(
        'name' => __( 'Sequential Steps', 'badgeos' ),
        'desc' => ' '.__( 'Yes, steps must be completed in order.', 'badgeos' ),
        'id'   => $prefix . 'sequential',
        'type' => 'checkbox',
    ));
    $cmb_obj->add_field(array(
        'name' => __( 'Show Earners', 'badgeos' ),
        'desc' => ' '.__( 'Yes, display a list of users who have earned this achievement.', 'badgeos' ),
        'id'   => $prefix . 'show_earners',
        'type' => 'checkbox',
    ));
    $cmb_obj->add_field(array(
        'name' => __( 'Congratulations Text', 'badgeos' ),
        'desc' => __( 'Displayed after achievement is earned. If sending to Credly, a great place for a testimonial for those who complete this achievement.', 'badgeos' ),
        'id'   => $prefix . 'congratulations_text',
        'type' => 'textarea',
    ));
    $cmb_obj->add_field(array(
        'name' => __( 'Maximum Earnings', 'badgeos' ),
        'desc' => ' '.__( 'Number of times a user can earn this badge (set to -1 for no maximum).', 'badgeos' ),
        'id'   => $prefix . 'maximum_earnings',
        'type' => 'text_small',
        'std' => '1',
    ));
    $cmb_obj->add_field(array(
        'name'    => __( 'Hidden?', 'badgeos' ),
        'desc'    => '',
        'id'      => $prefix . 'hidden',
        'type'    => 'select',
        'options' => array(
            'show' => __( 'Show to User', 'badgeos' ),
            'hidden' => __( 'Hidden to User', 'badgeos' )
        ),
    ));

}
add_action( 'cmb2_admin_init', 'badgeos_achievment_metaboxes' );
