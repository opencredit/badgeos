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

	// New Achievement Types
    $cmb_obj = new_cmb2_box( array(
        'id'            => 'achievement_type_data',
        'title'         => esc_html__( 'Achievement Type Data', 'badgeos' ),
        'object_types'  => array( 'achievement-type' ), // Post type
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

    // Grab our achievement types as an array
    $achievement_types = badgeos_get_achievement_types_slugs();

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
    $cmb_obj->add_field(array(
        'name' => __( 'Upload Badge Image', 'badgeos' ),
        'desc' => sprintf(
            __( '<p>To set an image use the <strong>Achievement Image</strong> metabox to the right. For best results, use a square .png file with a transparent background, at least 200x200 pixels. Or, design a badge using the %1$s.</p><p>If no image is specified, this achievement will default to the %2$s featured image.</p>', 'badgeos' ),
            badgeos_get_badge_builder_link( array( 'link_text' => __( 'Credly Badge Builder', 'badgeos' ) ) ),
            '<a href="' . admin_url('edit.php?post_type=achievement-type') . '">' . __( 'Achievement Type\'s', 'badgeos' ) . '</a>'

        ),
        'id'   => $prefix . 'upload_badge_image_achievement',
        'type' => 'text_only',
    ));
    $cmb_obj->add_field(array(
        'name' => __( 'Points Awarded', 'badgeos' ),
        'desc' => ' '.__( 'Points awarded for earning this achievement (optional). Leave empty if no points are awarded.', 'badgeos' ),
        'id'   => $prefix . 'points',
        'type' => 'text_small',
    ));

    $cmb_obj->add_field(array(
        'name'    => __( 'Earned By:', 'badgeos' ),
        'desc'    => __( 'How this achievement can be earned.', 'badgeos' ),
        'id'      => $prefix . 'earned_by',
        'type'    => 'select',
        'options' => apply_filters( 'badgeos_achievement_earned_by', array(
            'triggers' => __( 'Completing Steps', 'badgeos' ),
            'points' => __( 'Minimum Number of Points'),
            'submission' => __( 'Submission (Reviewed)', 'badgeos' ),
            'submission_auto' => __( 'Submission (Auto-accepted)', 'badgeos' ),
            'nomination' => __( 'Nomination', 'badgeos' ),
            'admin' => __( 'Admin-awarded Only', 'badgeos' )
        ))
    ));

    $cmb_obj->add_field(array(
        'name' => __( 'Minimum Points Requried', 'badgeos' ),
        'desc' => ' '.__( 'Fewest number of points required for earning this achievement.', 'badgeos' ),
        'id'   => $prefix . 'points_required',
        'type' => 'text_small',
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
    $cmb_obj->add_field(array(
        'name' => __( 'Allow Attachment for Submission', 'badgeos' ),
        'desc' => ' '.__( 'Yes, allow to display submission attachment.', 'badgeos' ),
        'id'   => $prefix . 'all_attachment_submission',
        'type' => 'checkbox',
    ));
    $cmb_obj->add_field(array(
        'name' => __( 'Allow Attachment for Submission Comment', 'badgeos' ),
        'desc' => ' '.__( 'Yes, allow to display submission comment attachment.', 'badgeos' ),
        'id'   => $prefix . 'all_attachment_submission_comment',
        'type' => 'checkbox',
    ));

}
add_action( 'cmb2_admin_init', 'badgeos_achievment_metaboxes' );

/**
 * Register custom meta box for BadgeOS submission.
 *
 * @since  1.0.0
 * @param  array $field The field data array
 * @param  string $meta The stored meta for this field (which will always be blank)
 * @return string       HTML markup for our field
 * @param  none
 * @return none
 */
function badgeos_submission_metaboxes( ) {

    // Start with an underscore to hide fields from custom fields list
    $prefix = '_badgeos_';

    // Grab our achievement types as an array
    $achievement_types = badgeos_get_achievement_types_slugs();

    // Setup our $post_id, if available
    $post_id = isset( $_GET['post'] ) ? $_GET['post'] : 0;

    // New Achievement Types

    $cmb_obj = new_cmb2_box( array(
        'id'            => 'submission_data',
        'title'         => __( 'Submission Status', 'badgeos' ),
        'object_types'  => array( 'submission' ), // Post type
        'context'    => 'side',
        'priority'   => 'default',
        'show_names' => true, // Show field names on the left
    ) );
    $cmb_obj->add_field(array(
        'name' => __( 'Current Status', 'badgeos' ),
        'desc' => ucfirst( get_post_meta( $post_id, $prefix . 'submission_status', true ) ),
        'id'   => $prefix . 'submission_current',
        'type' => 'text_only',
    ));
    $cmb_obj->add_field(array(
        'name' => __( 'Change Status', 'badgeos' ),
        'desc' => badgeos_render_feedback_buttons( $post_id ),
        'id'   => $prefix . 'submission_update',
        'type' => 'text_only',
    ));
    $cmb_obj->add_field(array(
        'name' => __( 'Achievement ID to Award', 'badgeos' ),
        'desc' => '<a href="' . esc_url( admin_url('post.php?post=' . get_post_meta( $post_id, '_badgeos_submission_achievement_id', true ) . '&action=edit') ) . '">' . __( 'View Achievement', 'badgeos' ) . '</a>',
        'id'   => $prefix . 'submission_achievement_id',
        'type'	 => 'text_small',
    ));
}
add_action( 'cmb2_admin_init', 'badgeos_submission_metaboxes' );

/**
 * Register custom meta box for BadgeOS Nominations.
 *
 * @since  1.0.0
 * @param  none
 * @return none
 */
function badgeos_nominations_metaboxes( ) {

    // Start with an underscore to hide fields from custom fields list
    $prefix = '_badgeos_';

    // Grab our achievement types as an array
    $achievement_types = badgeos_get_achievement_types_slugs();

    // Setup our $post_id, if available
    $post_id = isset( $_GET['post'] ) ? $_GET['post'] : 0;

    // New Achievement Types

    $cmb_obj = new_cmb2_box( array(
        'id'            => 'nomination_data',
        'title'         => __( 'Nomination Data', 'badgeos' ),
        'object_types'  => array( 'nomination' ), // Post type
        'context'    => 'side',
        'priority'   => 'default',
        'show_names' => true, // Show field names on the left
    ) );
    $cmb_obj->add_field(array(
        'name' => __( 'Current Status', 'badgeos' ),
        'desc' => ucfirst( get_post_meta( $post_id, $prefix . 'nomination_status', true ) ),
        'id'   => $prefix . 'nomination_current',
        'type' => 'text_only',
    ));
    $cmb_obj->add_field(array(
        'name' => __( 'Change Status', 'badgeos' ),
        'desc' => badgeos_render_feedback_buttons( $post_id ),
        'id'   => $prefix . 'nomination_update',
        'type' => 'text_only',
    ));

    $user_data = get_userdata( absint( get_post_meta( $post_id, '_badgeos_nomination_user_id', true ) ) );
    $display_name = '';
    if( $user_data ) {
        $display_name = $user_data->display_name;
    }

    $cmb_obj->add_field(array(
        'name' => __( 'Nominee', 'badgeos' ),
        'id'   => $prefix . 'nomination_user_id',
        'desc' => ( $post_id && get_post_type( $post_id ) == 'nomination' ) ? $display_name : '',
        'type' => 'text_medium',
    ));

    $user_data2 = get_userdata( absint( get_post_meta( $post_id, '_badgeos_nominating_user_id', true ) ) );
    $display_name2 = '';
    if( $user_data2 ) {
        $display_name2 = $user_data2->display_name;
    }
    $cmb_obj->add_field(array(
        'name' => __( 'Nominated By', 'badgeos' ),
        'id'   => $prefix . 'nominating_user_id',
        'desc' => ( $post_id && get_post_type( $post_id ) == 'nomination' ) ? $display_name2 : '',
        'type' => 'text_medium',
    ));
    $cmb_obj->add_field(array(
        'name' => __( 'Achievement ID to Award', 'badgeos' ),
        'desc' => '<a href="' . esc_url( admin_url('post.php?post=' . get_post_meta( $post_id, '_badgeos_nomination_achievement_id', true ) . '&action=edit') ) . '">' . __( 'View Achievement', 'badgeos' ) . '</a>',
        'id'   => $prefix . 'nomination_achievement_id',
        'type'	 => 'text_small',
    ));
}
add_action( 'cmb2_admin_init', 'badgeos_nominations_metaboxes' );

/**
 * Register the Submission attachments meta box
 *
 * @since  1.1.0
 * @return void
 */
function badgeos_submission_attachments_meta_box() {

	//register the submission attachments meta box
	add_meta_box( 'badgeos_submission_attachments_id', __( 'Submission Attachments', 'badgeos' ), 'badgeos_submission_attachments', 'submission' );

}
add_action( 'add_meta_boxes', 'badgeos_submission_attachments_meta_box' );

/**
 * Display all Submission attachments in a meta box
 *
 * @since  1.1.0
 * @param  object $post The post content
 * @return object 		The modified post content
 */
function badgeos_submission_attachments( $post = null) {

	//return all submission attachments
	if ( $submission_attachments = badgeos_get_submission_attachments( absint( $post->ID ) ) ) {
		echo $submission_attachments;
	}else{
		_e( 'No attachments on this submission', 'badgeos' );
	}

}