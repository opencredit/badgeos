<?php
/**
 * Submission and Nomination Functions
 *
 * @package BadgeOS
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Check if nomination form was submitted
 *
 * @since 1.0.0
 * @return array The content from the submission
 */
function badgeos_save_nomination_data() {

	// If the form hasn't been submitted, bail.
	if ( ! isset( $_POST['badgeos_nomination_submit'] ) )
		return false;

	// Nonce check for security
	check_admin_referer( 'badgeos_nomination_form', 'submit_nomination' );

	// Publish the nomination
	return badgeos_create_nomination(
		absint( $_POST['achievement_id'] ),
		sprintf( '%1$s: %2$s', get_post_type( absint( $_POST['achievement_id'] ) ), get_the_title( absint( $_POST['achievement_id'] ) ) ),
		sanitize_text_field( $_POST['badgeos_nomination_content'] ),
		absint( $_POST['badgeos_nomination_user_id'] ),
		absint( absint( $_POST['user_id'] ) )
	);
}

/**
 * Create a new nomination entry
 *
 * @since  1.0.0
 * @param  integer $achievement_id  The associated achievement's post ID
 * @param  string  $title           The title of the new nomination
 * @param  string  $content         The content for the new nomination
 * @param  integer $user_nominated  The user ID of the person nominated
 * @param  integer $user_nominating The user ID of the person who did the nominating
 * @return bool                     True on succesful post creation, false otherwise
 */
function badgeos_create_nomination( $achievement_id  = 0, $title = '', $content = '', $user_nominated  = 0, $user_nominating = 0 ) {

	if ( ! badgeos_check_if_user_has_nomination( absint( $user_nominated ), absint( $achievement_id ) ) ) {

		$submission_data = array(
			'post_title'	=>	sanitize_text_field( $title ),
			'post_content'	=>	sanitize_text_field( $content ),
			'post_status'	=>	'publish',
			'post_author'	=>	absint( $user_nominated ),
			'post_type'		=>	'nomination',
		);

		//insert the post into the database
		if ( $new_post_id = wp_insert_post( $submission_data ) ) {
			//save the submission status metadata
			add_post_meta( $new_post_id, '_badgeos_nomination_status', 'pending' );

			//save the achievement id metadata
			add_post_meta( $new_post_id, '_badgeos_nomination_achievement_id', absint( $achievement_id ) );

			//save the user being nominated
			add_post_meta( $new_post_id, '_badgeos_nomination_user_id', absint( $user_nominated ) );

			//save the user doing the nomination
			add_post_meta( $new_post_id, '_badgeos_nominating_user_id', absint( $user_nominating ) );

			do_action( 'badgeos_save_nomination', $new_post_id );

			//load BadgeOS settings
			$badgeos_settings = get_option( 'badgeos_settings' );

			//check if nomination emails are enabled
			if ( $badgeos_settings['submission_email'] != 'disabled' ) {

				$nominee_data = get_userdata( absint( $user_nominated ) );
				$nominating_data = get_userdata( absint( $user_nominating ) );

				//set the admin email address
				$admin_email = apply_filters( 'badgeos_nomination_notify_email', get_bloginfo( 'admin_email' ) );

				//set the email subject
				$subject = 'Nomination: '.get_the_title( absint( $achievement_id ) ). ' from ' .$nominating_data->display_name;
				$subject = apply_filters( 'badgeos_nomination_notify_subject', $subject );

				//set the email message
				$message = 'A new nomination has been received:

	In response to: ' .get_the_title( absint( $achievement_id ) ).'
	Nominee: '.$nominee_data->display_name.'
	Nominated by: '.$nominating_data->display_name.'

	Review the complete submission and approve or deny it at:
	'.html_entity_decode( esc_url_raw( get_edit_post_link( absint( $new_post_id ) ) ) ).'

	To view all submissions, visit:
	'.admin_url( 'edit.php?post_type=nomination' );

				$message = apply_filters( 'badgeos_nomination_notify_message', $message );

				//send notification email to admin
				wp_mail( $admin_email, $subject, $message );

			}

			return true;

		} else {

			return false;

		}

	}
}

/**
 * Hide action links on the submissions edit listing screen
 *
 * @since  1.0.0
 * @param  array @actions The array of action links
 * @return array 		  The updated array of action links
 */
function badgeos_hide_quick_edit( $actions = array() ) {
	global $post;

	if ( 'submission' == get_post_type( $post ) || 'nomination' == get_post_type( $post ) ) {
		// Hide unnecessary actions
		unset( $actions['inline hide-if-no-js'] );
		unset( $actions['trash'] );
		unset( $actions['view'] );

		// Rewrtie edit text
		$actions['edit'] = str_replace( 'Edit', __( 'Review', 'badgeos' ), $actions['edit'] );
	}

	return $actions;

}
add_filter( 'post_row_actions', 'badgeos_hide_quick_edit' );

/**
 * Add columns to the Submissions and Nominations edit screen
 *
 * @since  1.0.0
 * @param  array $columns The array of columns on the edit screen
 * @return array          Our updated array of columns
 */
function badgeos_add_submission_columns( $columns = array() ) {

	$column_content = array( 'content' => __( 'Content', 'badgeos' ) );
 	//$column_action = array( 'action' => __( 'Action', 'badgeos' ) );
	$column_status = array( 'status' => __( 'Status', 'badgeos' ) );

	$columns = array_slice( $columns, 0, 2, true ) + $column_content + array_slice( $columns, 2, NULL, true );
	//$columns = array_slice( $columns, 0, 3, true ) + $column_action + array_slice( $columns, 2, NULL, true );
	$columns = array_slice( $columns, 0, 3, true ) + $column_status + array_slice( $columns, 2, NULL, true );

	unset( $columns['comments'] );

	return $columns;

}
add_filter( 'manage_edit-submission_columns', 'badgeos_add_submission_columns', 10, 1 );
add_filter( 'manage_edit-nomination_columns', 'badgeos_add_nomination_columns', 10, 1 );

/**
 * Add columns to the Nominations edit screan
 *
 * @since  1.0.0
 * @param  array $columns The array of columns on the edit screen
 * @return array          Our updated array of columns
 */
function badgeos_add_nomination_columns( $columns = array() ) {

	$column_content = array( 'content' => __( 'Content', 'badgeos' ) );
	$column_userid = array( 'user' => __( 'User', 'badgeos' ) );
 	//$column_action = array( 'action' => __( 'Action', 'badgeos' ) );
	$column_status = array( 'status' => __( 'Status', 'badgeos' ) );

	$columns = array_slice( $columns, 0, 2, true ) + $column_content + array_slice( $columns, 2, NULL, true );
	$columns = array_slice( $columns, 0, 3, true ) + $column_userid + array_slice( $columns, 2, NULL, true );
	//$columns = array_slice( $columns, 0, 4, true ) + $column_action + array_slice( $columns, 2, NULL, true );
	$columns = array_slice( $columns, 0, 4, true ) + $column_status + array_slice( $columns, 2, NULL, true );

	unset( $columns['comments'] );

	return $columns;

}

/**
 * Content for the custom Submission columns
 *
 * @since  1.0.0
 * @param  string $column The column name
 * @return void
 */
function badgeos_submission_column_action( $column = '' ) {
	global $post, $badgeos;

	switch ( $column ) {
		case 'action':

			//if submission use the post Author ID, if nomination use the user ID meta value
			$user_id = ( isset( $_GET['post_type'] ) && 'submission' == $_GET['post_type'] ) ? $post->post_author : get_post_meta( $post->ID, '_badgeos_submission_user_id', true );

			echo '<a class="button-secondary" href="'.wp_nonce_url( add_query_arg( array( 'badgeos_status' => 'approve', 'post_id' => absint( $post->ID ), 'user_id' => absint( $user_id ) ) ), 'badgeos_status_action' ).'">'.__( 'Approve', 'badgeos' ).'</a>&nbsp;&nbsp;';
			echo '<a class="button-secondary" href="'.wp_nonce_url( add_query_arg( array( 'badgeos_status' => 'deny', 'post_id' => absint( $post->ID ), 'user_id' => absint( $user_id ) ) ), 'badgeos_status_action' ).'">'.__( 'Deny', 'badgeos' ).'</a>';
			break;

		case 'content':

			echo substr( $post->post_content, 0, 250 ) .'...';
			break;

		case 'status':

			$status = ( get_post_type( $post ) == 'submission' ) ? get_post_meta( $post->ID, '_badgeos_submission_status', true ) : get_post_meta( $post->ID, '_badgeos_nomination_status', true );
			$status = ( $status ) ? $status : __( 'pending', 'badgeos' );
			echo esc_html( $status );
			break;

		case 'user':

			$user_id = ( get_post_type( $post ) == 'submission' ) ? get_post_meta( $post->ID, '_badgeos_submission_user_id', true ) : get_post_meta( $post->ID, '_badgeos_nomination_user_id', true );

			if ( is_numeric( $user_id ) ) {
				$user_info = get_userdata( absint( $user_id ) );
				echo $user_info->display_name;
				break;
			}
	}
}
add_action( 'manage_posts_custom_column', 'badgeos_submission_column_action', 10, 1 );

/**
 * Add filter select to Submissions edit screen
 *
 * @since 1.0.0
 * @return void
 */
function badgeos_add_submission_dropdown_filters() {
    global $typenow;

	if ( $typenow == 'submission' ) {
        //array of current status values available
        $submission_statuses = array( __( 'Approve', 'badgeos' ), __( 'Deny', 'badgeos' ), __( 'Pending', 'badgeos' ) );

		$current_status = ( isset( $_GET['badgeos_submission_status'] ) ) ? $_GET['badgeos_submission_status'] : '';

		//output html for status dropdown filter
		echo "<select name='badgeos_submission_status' id='badgeos_submission_status' class='postform'>";
		echo "<option value=''>" .__( 'Show All Statuses', 'badgeos' ).'</option>';
		foreach ( $submission_statuses as $status ) {
			echo '<option value="'.strtolower( $status ).'"  '.selected( $current_status, strtolower( $status ) ).'>' .$status .'</option>';
		}
		echo '</select>';
	}
}
add_action( 'restrict_manage_posts', 'badgeos_add_submission_dropdown_filters' );

/**
 * Filter the query to show submission statuses
 *
 * @since 1.0.0
 * @param object $query The Query to be filtered
 * @param object 	   The Query after filtering
 */
function badgeos_submission_status_filter( $query = null ) {
	global $pagenow;

	if ( $query->is_admin && ( 'edit.php' == $pagenow ) ) {
		$metavalue = ( isset($_GET['badgeos_submission_status']) && $_GET['badgeos_submission_status'] != '' ) ? $_GET['badgeos_submission_status'] : '';

		if ( '' != $metavalue ) {
			$query->set( 'orderby', 'meta_value' );
			$query->set( 'meta_key', '_badgeos_submission_status' );
			$query->set( 'meta_value', esc_html( $metavalue ) );
		}
	}

	return $query;
}
add_filter( 'pre_get_posts', 'badgeos_submission_status_filter' );


/**
 * Process admin submission/nomination approvals
 *
 * @since 1.0.0
 * @param integer $post_id The given post's ID
 * @return void
 */
function badgeos_process_submission_review( $post_id = 0 ) {

	// Confirm we're deailing with either a submission or nomination post type,
	// and our nonce is valid
	// and the user is allowed to edit the post
	// and we've gained an approved status
	if (
		!empty( $_POST )
		&& isset( $_POST['post_type'] )
		&& ( 'submission' == $_POST['post_type'] || 'nomination' == $_POST['post_type'] ) //verify post type is submission or nomination
		&& isset( $_POST['wp_meta_box_nonce'] )
		&& wp_verify_nonce( $_POST['wp_meta_box_nonce'], 'init.php' ) //verify nonce for security
		&& current_user_can( 'edit_post', $post_id ) //check if current user has permission to edit this submission/nomination
		&& ( 'approved' == $_POST['_badgeos_submission_status'] || 'approved' == $_POST['_badgeos_nomination_status']  ) //verify user is approving a submission or nomination
		&& get_post_meta( $post_id, '_badgeos_nomination_status', true ) != 'approved' //if nomination is already approved, skip it
		&& get_post_meta( $post_id, '_badgeos_submission_status', true ) != 'approved' //if submission is already approved, skip it
	) {

		// Get the achievement and user attached to this Submission
		$achievement_id = ( get_post_type( $post_id ) == 'submission' ) ? get_post_meta( $post_id, '_badgeos_submission_achievement_id', true ) : get_post_meta( $post_id, '_badgeos_nomination_achievement_id', true );
		$user_id = isset( $_POST['_badgeos_nominated_user_id'] ) ? $_POST['_badgeos_nominated_user_id'] : $_POST['post_author'];

		// Give the achievement to the user
		if ( $achievement_id && $user_id ) {

			badgeos_award_achievement_to_user( absint( $achievement_id ), absint( $user_id ) );

		}

	}

}
add_action( 'save_post', 'badgeos_process_submission_review' );

/**
 * Check if nomination form has been submitted and save data
 * @since 1.0.0
 * @return string  The submitted form or the submission box.
 */
function badgeos_save_submission_data() {

	// If form items don't exist, bail.
	if ( ! isset( $_POST['badgeos_submission_submit'] ) || ! isset( $_POST['badgeos_submission_content'] ) )
		return;

	// Nonce check for security
	check_admin_referer( 'badgeos_submission_form', 'submit_submission' );

	// Publish the submission
	return badgeos_create_submission(
		absint( $_POST['achievement_id'] ),
		sprintf( '%1$s: %2$s', get_post_type( absint( $_POST['achievement_id'] ) ), get_the_title( absint( $_POST['achievement_id'] ) ) ),
		sanitize_text_field( $_POST['badgeos_submission_content'] ),
		absint( $_POST['user_id'] )
	);
}
/**
 * Create Submission form
 * @since  1.0.0
 * @param  integer $achievement_id The achievement ID intended for submission
 * @param  string  $title          The title of the post
 * @param  string  $content        The post content
 * @param  integer $user_id        The user ID
 * @return boolean                 Returns true if able to create form
 */
function badgeos_create_submission( $achievement_id  = 0, $title = '', $content = '', $user_id = 0  ) {

	$submission_data = array(
		'post_title'	=>	$title,
		'post_content'	=>	$content,
		'post_status'	=>	'publish',
		'post_author'	=>	$user_id,
		'post_type'		=>	'submission',
	);

	//insert the post into the database
	if ( $submission_id = wp_insert_post( $submission_data ) ) {

		// save the achievement ID related to the submission
		add_post_meta( $submission_id, '_badgeos_submission_achievement_id', $achievement_id );

		// Check if submission is auto approved or not
		$submission_status = badgeos_is_submission_auto_approved( $submission_id ) ? 'approved' : 'pending';

		// Set the submission approval status
		add_post_meta( $submission_id, '_badgeos_submission_status', sanitize_text_field( $submission_status ) );

		//if submission is set to auto-approve, award the achievement to the user
		if ( get_post_meta( $achievement_id, '_badgeos_earned_by', true ) == 'submission_auto' )
			badgeos_award_achievement_to_user( absint( $achievement_id ), absint( $user_id ) );

		//process attachment upload if a file was submitted
		if( ! empty($_FILES['document_file'] ) ) {

			if ( ! function_exists( 'wp_handle_upload' ) ) require_once( ABSPATH . 'wp-admin/includes/file.php' );

			$file   = $_FILES['document_file'];
			$upload = wp_handle_upload( $file, array( 'test_form' => false ) );

			if( ! isset( $upload['error'] ) && isset($upload['file'] ) ) {

				$filetype   = wp_check_filetype( basename( $upload['file'] ), null );
				$title      = $file['name'];
				$ext        = strrchr( $title, '.' );
				$title      = ( $ext !== false ) ? substr( $title, 0, -strlen( $ext ) ) : $title;

				$attachment = array(
					'post_mime_type'    => $filetype['type'],
					'post_title'        => addslashes( $title ),
					'post_content'      => '',
					'post_status'       => 'inherit',
					'post_parent'       => $submission_id
				);

				$attach_id  = wp_insert_attachment( $attachment, $upload['file'] );

			}
		}

		// Available action for other processes
		do_action( 'badgeos_save_submission', $submission_id );

		//load BadgeOS settings
		$badgeos_settings = get_option( 'badgeos_settings' );

		//check if submission emails are enabled
		if ( $badgeos_settings['submission_email'] != 'disabled' ) {

			$user_data = get_userdata( absint( $user_id ) );

			//set the admin email address
			$admin_email = apply_filters( 'badgeos_submission_notify_email', get_bloginfo( 'admin_email' ) );

			//set the email subject
			$subject = 'Submission: '.get_the_title( absint( $achievement_id ) ). ' from ' .$user_data->display_name;
			$subject = apply_filters( 'badgeos_submission_notify_subject', $subject );

			//set the email message
			$message = 'A new submission has been received:

In response to: ' .get_the_title( absint( $achievement_id ) ).'
Submitted by: '.$user_data->display_name.'

Review the complete submission and approve or deny it at:
'.html_entity_decode( esc_url_raw( get_edit_post_link( absint( $submission_id ) ) ) ).'

To view all submissions, visit:
'.admin_url( 'edit.php?post_type=submission' );

			$message = apply_filters( 'badgeos_submission_notify_message', $message );

			//send notification email to admin
			wp_mail( $admin_email, $subject, $message );

		}

		return true;

	} else {

		return false;

	}
}

/**
 * Returns the comment form for Submissions
 * @since 1.0.0
 * @param integer $post_id The post_id for the current page
 * @return string 		   The submission markup
 */
function badgeos_get_comment_form( $post_id = 0 ) {
	global $current_user;

	// user must be logged in to see the submission form
	if ( !is_user_logged_in() )
		return '';

	$defaults = array(
		'heading'    => '<h4>' . sprintf( __( 'Comment on Submission (#%1$d):', 'badgeos' ), $post_id ) . '</h4>',
		'attachment' => __( 'Attachment:', 'badgeos' ),
		'submit'     => __( 'Submit Comment', 'badgeos' )
	);
	// filter our text
	$new_defaults = apply_filters( 'badgeos_comment_form_language', $defaults );
	// fill in missing data
	$language = wp_parse_args( $new_defaults, $defaults );

	$sub_form = '<form class="badgeos-comment-form" method="post" enctype="multipart/form-data">';

		// comment form heading
		$sub_form .= '<legend>'. $language['heading'] .'</legend>';

		// submission comment
		$sub_form .= '<fieldset class="badgeos-submission-comment-entry">';
		$sub_form .= '<p><textarea name="badgeos_comment"></textarea></p>';
		$sub_form .= '</fieldset>';

		// submission file upload
		$sub_form .= '<fieldset class="badgeos-submission-file">';
		$sub_form .= '<p><label>'. $language['attachment'] .' <input type="file" name="document_file" id="document_file" /></label></p>';
		$sub_form .= '</fieldset>';

		// submit button
		$sub_form .= '<p class="badgeos-submission-submit"><input type="submit" name="badgeos_comment_submit" value="'. $language['submit'] .'" /></p>';

		// Hidden Fields
		$sub_form .= wp_nonce_field( 'submit_comment', 'badgeos_comment_nonce', true, false );
		$sub_form .= '<input type="hidden" name="user_id" value="' . $current_user->ID . '">';
		$sub_form .= '<input type="hidden" name="submission_id" value="' . $post_id . '">';

	$sub_form .= '</form>';

	return apply_filters( 'badgeos_get_comment_form', $sub_form, $post_id );

}

/**
 * Listener for saving submission comments
 *
 * @since 1.0.0
 * @return void
 */
function badgeos_save_comment_data() {

	// If our nonce data is empty, bail
	if ( ! isset( $_POST['badgeos_comment_nonce'] ) )
		return;

	// If our nonce doesn't vaildate, bail
	if ( ! wp_verify_nonce( $_POST['badgeos_comment_nonce'], 'submit_comment' ) )
		return;

	// Process comment data
	$comment_data = array(
		'user_id'         => absint( $_POST['user_id'] ),
		'comment_post_ID' => absint( $_POST['submission_id'] ),
		'comment_content' => sanitize_text_field( $_POST['badgeos_comment'] ),
	);

	if ( $comment_id = wp_insert_comment( $comment_data ) ) {

		// Process attachment upload if a file was submitted
		if( ! empty($_FILES['document_file'] ) ) {

			if ( ! function_exists( 'wp_handle_upload' ) ) require_once( ABSPATH . 'wp-admin/includes/file.php' );

			$file   = $_FILES['document_file'];
			$upload = wp_handle_upload( $file, array( 'test_form' => false ) );

			if( ! isset( $upload['error'] ) && isset( $upload['file'] ) ) {

				$filetype = wp_check_filetype( basename( $upload['file'] ), null );
				$title    = $file['name'];
				$ext      = strrchr( $title, '.' );
				$title    = ( $ext !== false ) ? substr( $title, 0, -strlen( $ext ) ) : $title;

				$attachment = array(
					'post_mime_type' => $filetype['type'],
					'post_title'     => addslashes( $title ),
					'post_content'   => '',
					'post_status'    => 'inherit',
					'post_parent'    => absint( $_REQUEST['submission_id'] ),
					'post_author'    => absint( $_REQUEST['user_id'] )
				);
				wp_insert_attachment( $attachment, $upload['file'] );
			}
		}
	}
}
add_action( 'init', 'badgeos_save_comment_data' );

/**
 * Returns all comments for a Submission entry
 *
 * @since  1.0.0
 * @param  integer $submission_id The submission's post ID
 * @return string                 Concatenated markup for comments
 */
function badgeos_get_comments_for_submission( $submission_id = 0 ) {

	// Get our comments
	$comments = get_comments( array(
		'post_id' => absint( $submission_id ),
		'orderby' => 'date',
		'order'   => 'ASC',
	) );

	// If we have no comments, bail
	if ( empty( $comments ) )
		return;

	// Concatenate our output
	$output = '<h4>' . sprintf( __( 'Comments:', 'badgeos' ), $submission_id ) . '</h4>';
	$output .= '<ul class="badgeos-submission-comments-list">';
	foreach( $comments as $comment ) {
		// Setup an alternating odd/even class
		$odd_even = ( isset( $odd_even ) && 'odd' == $odd_even ) ? 'even' : 'odd';

		// Render the comment
		$output .= badgeos_render_submission_comment( $comment, $odd_even );
	}
	$output .= '</ul><!-- .badgeos-submission-comments-list -->';

	return apply_filters( 'badgeos_get_comments_for_submission', $output, $submission_id, $comments );

}

/**
 * Conditional to determine if a submission's achievement is set to auto-approve
 *
 * @since  1.1.0
 * @param  integer $submission_id The submission's post ID
 * @return bool                   True if connected achievement is set to auto-approve, false otherwise
 */
function badgeos_is_submission_auto_approved( $submission_id = 0 ) {

	// Get the submission's connected achievement
	$achievement_id = get_post_meta( $submission_id, '_badgeos_submission_achievement_id', true );

	// If the achievement is set to auto-approve, return true
	if ( 'submission_auto' == get_post_meta( $achievement_id, '_badgeos_earned_by', true ) )
		return true;
	else
		return false;
}

/**
 * Check if a user has left feedback for an achievement
 *
 * @since  1.1.0
 * @param  integer $user_id        The user's ID
 * @param  integer $achievement_id The achievement's post ID
 * @param  string  $feedback_type  The type of feedback to check for (e.g. "submission")
 * @return bool                    True if the user has sent a submission, false otherwise
 */
function badgeos_check_if_user_has_feedback( $user_id = 0, $achievement_id = 0, $feedback_type = '' ) {

	// Setup our search args
	$args = array(
		'post_type'   => $feedback_type,
		'author'      => absint( $user_id ),
		'post_status' => 'publish',
		'meta_query'  => array(
			array(
				'key'   => "_badgeos_{$feedback_type}_achievement_id",
				'value' => absint( $achievement_id ),
			)
		)
	);

	// If nomination, modify our query to look for nominations by this user
	if ( 'nomination' == $feedback_type ) {
		unset( $args['author'] );
		$args['meta_query'][] = array(
			'key'   => '_badgeos_nominating_user_id',
			'value' => absint( $user_id )
		);
	}

	// Get feedback
	$feedback = get_posts( $args );

	// User DOES have a submission for this achievement
	if ( ! empty( $feedback ) )
		return true;

	// User does NOT have a submission
	else
		return false;
}

/**
 * Check if a user has an existing submission for an achievement
 *
 * @since  1.0.0
 * @param  integer $user_id        The user's ID
 * @param  integer $achievement_id The achievement's post ID
 * @return bool                    True if the user has sent a submission, false otherwise
 */
function badgeos_check_if_user_has_submission( $user_id = 0, $achievement_id = 0 ) {
	return badgeos_check_if_user_has_feedback( $user_id, $achievement_id, 'submission' );
}

/**
 * Check if a user has an existing nomination for an achievement
 *
 * @since  1.0.0
 * @param  integer $user_id        The user's ID
 * @param  integer $achievement_id The achievement's post ID
 * @return bool                    True if the user has sent a submission, false otherwise
 */
function badgeos_check_if_user_has_nomination( $user_id = 0, $achievement_id = 0 ) {
	return badgeos_check_if_user_has_feedback( $user_id, $achievement_id, 'nomination' );
}

/**
 * Check if a user has access to the submission form for an achievement.
 *
 * @since  1.3.2
 *
 * @param  integer $user_id        User ID.
 * @param  integer $achievement_id Achievement post ID.
 * @return bool                    True if user has access, otherwise false.
 */
function badgeos_user_has_access_to_submission_form( $user_id = 0, $achievement_id = 0 ) {

	// Assume the user has access
	$has_access = true;

	// If user is not logged in, they have no access
	if ( ! absint( $user_id ) ) {
		$has_access = false;
	}

	// If user cannot access achievement, they cannot submit anything
	if ( $has_access && ! badgeos_user_has_access_to_achievement( $user_id, $achievement_id ) ) {
		$has_access = false;
	}

	// If the user has access, look for pending submissions
	if ( $has_access ) {
		$pending_submissions = get_posts( array(
			'post_type'   => 'submission',
			'author'      => absint( $user_id ),
			'post_status' => 'publish',
			'meta_query'  => array(
				'relation' => 'AND',
				array(
					'key'   => '_badgeos_submission_achievement_id',
					'value' => absint( $achievement_id ),
					),
				array(
					'key'   => '_badgeos_submission_status',
					'value' => 'pending',
					),
				),
		) );

		// If user has any pending submissions, they do not have access
		if ( ! empty( $pending_submissions ) ) {
			$has_access = false;
		}
	}

	return apply_filters( 'badgeos_user_has_access_to_submission_form', $has_access, $user_id, $achievement_id );
}

/**
 * Get the nomination form
 * @param  array  $args The meta box arguemnts
 * @return void
 */
function badgeos_get_nomination_form( $args = array() ) {
	global $post, $user_ID;

	// Setup our defaults
	$defaults = array(
		'heading' => sprintf( '<h4>%s</h4>', __( 'Nomination Form', 'badgeos' ) ),
		'submit'  => __( 'Submit', 'badgeos' )
	);

	// Available filter for changing the language
	$defaults = apply_filters( 'badgeos_submission_form_language', $defaults );

	// Patch in our achievement and user IDs
	$defaults['achievement_id'] = $post->ID;
	$defaults['user_id']        = $user_ID;

	// Merge our defaults with the passed args
	$args = wp_parse_args( $args, $defaults );

	$sub_form = '<form class="badgeos-nomination-form" method="post" enctype="multipart/form-data">';
		// nomination form heading
		$sub_form .= '<legend>'. $args['heading'] .'</legend>';
		// nomination user
		$sub_form .= '<label>'.__( 'User to nominate', 'badgeos' ).'</label>';
		$sub_form .= '<p>' .wp_dropdown_users( array( 'name' => 'badgeos_nomination_user_id', 'echo' => '0' ) ). '</p>';
		// nomination content
		$sub_form .= '<label>'.__( 'Reason for nomination', 'badgeos' ).'</label>';
		$sub_form .= '<fieldset class="badgeos-nomination-content">';
		$sub_form .= '<p><textarea name="badgeos_nomination_content"></textarea></p>';
		$sub_form .= '</fieldset>';
		// submit button
		$sub_form .= '<p class="badgeos-nomination-submit"><input type="submit" name="badgeos_nomination_submit" value="'. esc_attr( $args['submit'] ) .'" /></p>';
		// hidden fields
		$sub_form .= wp_nonce_field( 'badgeos_nomination_form', 'submit_nomination', true, false );
		$sub_form .= '<input type="hidden" name="achievement_id" value="' . absint( $args['achievement_id'] ) . '">';
		$sub_form .= '<input type="hidden" name="user_id" value="' . absint( $args['user_id'] ) . '">';
	$sub_form .= '</form>';

	return apply_filters( 'badgeos_get_nomination_form', $sub_form );
}

/**
 * Get the submission form
 * @param  array  $args The meta box arguemnts
 * @return void
 */
function badgeos_get_submission_form( $args = array() ) {
	global $post, $user_ID;

	// Setup our defaults
	$defaults = array(
		'heading'    => sprintf( '<h4>%s</h4>', __( 'Submission Form', 'badgeos' ) ),
		'attachment' => __( 'Attachment:', 'badgeos' ),
		'submit'     => __( 'Submit', 'badgeos' )
	);

	// Available filter for changing the language
	$defaults = apply_filters( 'badgeos_submission_form_language', $defaults );

	// Patch in our achievement and user IDs
	$defaults['achievement_id'] = $post->ID;
	$defaults['user_id']        = $user_ID;

	// Merge our defaults with the passed args
	$args = wp_parse_args( $args, $defaults );

	$sub_form = '<form class="badgeos-submission-form" method="post" enctype="multipart/form-data">';
		// submission form heading
		$sub_form .= '<legend>'. $args['heading'] .'</legend>';
		// submission file upload
		$sub_form .= '<fieldset class="badgeos-file-submission">';
		$sub_form .= '<p><label>'. $args['attachment'] .' <input type="file" name="document_file" id="document_file" /></label></p>';
		$sub_form .= '</fieldset>';
		// submission comment
		$sub_form .= '<fieldset class="badgeos-submission-comment">';
		$sub_form .= '<p><textarea name="badgeos_submission_content"></textarea></p>';
		$sub_form .= '</fieldset>';
		// submit button
		$sub_form .= '<p class="badgeos-submission-submit"><input type="submit" name="badgeos_submission_submit" value="'. $args['submit'] .'" /></p>';
		// hidden fields
		$sub_form .= wp_nonce_field( 'badgeos_submission_form', 'submit_submission', true, false );
		$sub_form .= '<input type="hidden" name="achievement_id" value="' . absint( $args['achievement_id'] ) . '">';
		$sub_form .= '<input type="hidden" name="user_id" value="' . absint( $args['user_id'] ) . '">';
	$sub_form .= '</form>';

	return apply_filters( 'badgeos_get_submission_form', $sub_form );
}

/**
 * Get achievement-based feedback
 *
 * @since  1.1.0
 * @param  array  $args An array of arguments to limit or alter output
 * @return string       Conatenated output for feedback
 */
function badgeos_get_feedback( $args = array() ) {
	global $user_ID;

	// If no one is logged in, bail now
	if ( ! is_user_logged_in() )
		return '<p class="error must-be-logged-in">' . __( 'You must be logged in to see results.', 'badgeos' ) . '</p>';

	// Setup our default args
	$defaults = array(
		'post_status' => 'publish',
		'post_type'   => 'submission'
	);
	$args = wp_parse_args( $args, $defaults );

	// Eliminate need for case-sensitivity on status
	if ( ! empty( $args['status'] ) )
		$args['status'] = strtolower( $args['status'] );
	else
		$args['status'] = '';

	// If we're looking for auto-approved only
	$show_auto_approved = true;
	if ( 'auto-approved' == $args['status'] ) {
		$args['status'] = 'approved';
	} elseif ( 'all' !== $args['status'] ) {
		$show_auto_approved = false;
	}

	// If we're looking for a specific approval status
	if ( ! empty( $args['status'] ) && 'all' !== $args['status'] ) {
		$args['meta_query'][] = array(
			'key'   => "_badgeos_{$args['post_type']}_status",
			'value' => $args['status']
		);
	}

	// If we want feedback connected to a specific achievement
	if ( isset( $args['achievement_id'] ) ) {
		$args['meta_query'][] = array(
			'key'   => "_badgeos_{$args['post_type']}_achievement_id",
			'value' => absint( $args['achievement_id'] ),
		);
	}

	// Setup our author limit
	if ( empty( $args['author'] ) && 'nomination' != $args['post_type'] ) {
		// If we're not an admin, limit results to the current user
		$badgeos_settings = get_option( 'badgeos_settings' );
		if ( ! current_user_can( $badgeos_settings['minimum_role'] ) ) {
			$args['author'] = $user_ID;
		}
	}

	// Get our feedback
	$feedback = get_posts( $args );
	$output = '';

	if ( ! empty( $feedback ) ) {

		$output .= '<div class="badgeos-submissions">';

		foreach( $feedback as $submission ) {

			// Bail if we do NOT want to show auto approved, and it is
			if ( ! $show_auto_approved && badgeos_is_submission_auto_approved( $submission->ID ) )
				continue;

			// Setup our output
			if ( 'nomination' == $args['post_type'] )
				$output .= badgeos_render_nomination( $submission, $args );
			else
				$output .= badgeos_render_submission( $submission, $args );

		} // End: foreach( $feedback )

		$output .= '</div><!-- badgeos-submissions -->';

	} // End: if ( $feedback )

	// Return our filterable output
	return apply_filters( 'badgeos_get_submissions', $output, $args, $feedback );
}

/**
 * Get achievement-based submission posts
 *
 * @since  1.1.0
 * @param  array  $args An array of arguments to limit or alter output
 * @return string       Conatenated output for submission, attachments and comments
 */
function badgeos_get_submissions( $args = array() ) {

	// Setup our default args
	$defaults = array(
		'post_type'        => 'submission',
		'show_attachments' => true,
		'show_comments'    => true
	);
	$args = wp_parse_args( $args, $defaults );

	// Grab our submissions
	$submissions = badgeos_get_feedback( $args );

	// Return our filterable output
	return apply_filters( 'badgeos_get_submissions', $submissions, $args );
}

/**
 * Get submissions attached to a specific achievement by a specific user
 *
 * @since  1.0.0
 * @param  integer $achievement_id The achievement's post ID
 * @param  integer $user_id        The user's ID
 * @return string                  Conatenated output for submission, attachments and comments
 */
function badgeos_get_user_submissions( $user_id = 0, $achievement_id = 0 ) {
	global $user_ID, $post;

	// Setup our empty args array
	$args = array();

	// Setup our author limit
	if ( ! empty( $user_id ) ) {
		// Use the provided user ID
		$args['author'] = absint( $user_id );
	} else {
		// If we're not an admin, limit results to the current user
		$badgeos_settings = get_option( 'badgeos_settings' );
		if ( ! current_user_can( $badgeos_settings['minimum_role'] ) ) {
			$args['author'] = $user_ID;
		}
	}

	// If we were not given an achievement ID,
	// use the current post's ID
	$args['achievement_id'] = ( absint( $achievement_id ) )
		? absint( $achievement_id )
		: $post->ID;

	// Grab our submissions for the current user
	$submissions = badgeos_get_submissions( $args );

	// Return filterable output
	return apply_filters( 'badgeos_get_user_submissions', $submissions, $achievement_id, $user_id );
}

/**
 * Get nominations attached to a specific achievement by a specific user
 *
 * @since  1.0.0
 * @param  integer $achievement_id The achievement's post ID
 * @param  integer $user_id        The user's ID
 * @return string                  Conatenated output for submission, attachments and comments
 */
function badgeos_get_user_nominations( $user_id = 0, $achievement_id = 0 ) {
	global $user_ID, $post;

	// Setup our empty args array
	$args = array(
		'post_type'        => 'nomination',
		'show_attachments' => 'false',
		'show_comments'    => 'false'
	);

	// Setup our author limit
	if ( ! empty( $user_id ) ) {
		// Use the provided user ID
		$args['meta_query'][] = array(
			'key'   => '_badgeos_nominating_user_id',
			'value' => absint( $user_id )
		);
	} else {
		// If we're not an admin, limit results to the current user
		$badgeos_settings = get_option( 'badgeos_settings' );
		if ( ! current_user_can( $badgeos_settings['minimum_role'] ) ) {
			$args['meta_query'][] = array(
				'key'   => '_badgeos_nominating_user_id',
				'value' => absint( $user_ID )
			);
		}
	}

	// If we were not given an achievement ID,
	// use the current post's ID
	$args['achievement_id'] = ( absint( $achievement_id ) )
		? absint( $achievement_id )
		: $post->ID;

	// Grab our submissions for the current user
	$submissions = badgeos_get_feedback( $args );

	// Return filterable output
	return apply_filters( 'badgeos_get_user_submissions', $submissions, $achievement_id, $user_id );
}



/**
 * Get attachments connected to a specific achievement
 *
 * @since  1.1.0
 * @param  integer $submission_id The submission's post ID
 * @return string                 The concatenated attachment output
 */
function badgeos_get_submission_attachments( $submission_id = 0 ) {

	// Get attachments
	$attachments = get_posts( array(
		'post_type'      => 'attachment',
		'posts_per_page' => -1,
		'post_parent'    => $submission_id,
		'orderby'        => 'date',
		'order'          => 'ASC',
	) );

	// If we have attachments
	$output = '';
	if ( ! empty( $attachments ) ) {
		$output .= '<h4>' . sprintf( __( 'Submitted Attachments:', 'badgeos' ), $submission_id ) . '</h4>';
		$output .= '<ul class="badgeos-attachments-list">';
		foreach ( $attachments as $attachment ) {
			$output .= badgeos_render_submission_attachment( $attachment );
		}
		$output .= '</ul><!-- .badgeos-attachments-list -->';
	}

	// Return out filterable output
	return apply_filters( 'badgeos_get_submission_attachments', $output, $submission_id, $attachments );
}
