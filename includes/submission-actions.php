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
 */
function badgeos_save_nomination_data() {
	global $current_user, $post;

	// If the form hasn't been submitted, bail.
	if ( ! isset( $_POST['badgeos_nomination_submit'] ) )
		return false;

	//nonce check for security
	check_admin_referer( 'badgeos_nomination_form', 'submit_nomination' );

	get_currentuserinfo();
	$nomination_content = $_POST['badgeos_nomination_content'];
	$nomination_user_id = $_POST['badgeos_nomination_user_id'];

	$nomination_type = get_post_type( absint( $post->ID ) );

	return badgeos_create_nomination(
		$post->ID,
		$nomination_type . ':' . get_the_title( absint( $post->ID ) ),
		sanitize_text_field( $nomination_content ),
		absint( $nomination_user_id ),
		absint( $current_user->ID )
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
function badgeos_create_nomination( $achievement_id, $title, $content, $user_nominated, $user_nominating ) {

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
 * @since 1.0.0
 */
function badgeos_hide_quick_edit( $actions ) {
	global $post;

	if ( 'submission' == get_post_type( $post ) || 'nomination' == get_post_type( $post ) ) {
		//hide action links
		unset( $actions['inline hide-if-no-js'] );
		unset( $actions['trash'] );
		unset( $actions['view'] );
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
function badgeos_add_submission_columns( $columns ) {

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
function badgeos_add_nomination_columns( $columns ) {

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
 */
function badgeos_submission_column_action( $column ) {
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

			$status = get_post_meta( $post->ID, '_badgeos_submission_status', true );
			$status = ( $status ) ? $status : __( 'pending', 'badgeos' );
			echo $status;
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
 */
function badgeos_add_submission_dropdown_filters() {
    global $typenow, $wpdb;

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
 */
function badgeos_submission_status_filter( $query ) {
	global $pagenow;

	if ( $query->is_admin && ( 'edit.php' == $pagenow ) ) {
		$metavalue = ( isset($_GET['badgeos_submission_status']) && $_GET['badgeos_submission_status'] != '' ) ? $_GET['badgeos_submission_status'] : '';

		if ( '' != $metavalue ) {
			$query->set( 'orderby' , 'meta_value' );
			$query->set( 'meta_key' , '_badgeos_submission_status' );
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
 */
function badgeos_process_submission_review( $post_id ) {

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
 */
function badgeos_save_submission_data() {
	global $current_user, $post;

	//if form items don't exist, bail.
	if ( ! isset( $_POST['badgeos_submission_submit'] ) || ! isset( $_POST['badgeos_submission_content'] ) )
		return;

	//nonce check for security
	check_admin_referer( 'badgeos_submission_form', 'submit_submission' );

	get_currentuserinfo();

	$submission_content = $_POST['badgeos_submission_content'];
	$submission_type = get_post_type( absint( $post->ID ) );

	return badgeos_create_submission(
		$post->ID,
		$submission_type . ':' . get_the_title( absint( $post->ID ) ),
		sanitize_text_field( $submission_content ),
		absint( $current_user->ID )
	);
}

function badgeos_create_submission( $achievement_id, $title, $content, $user_id ) {

	$submission_data = array(
		'post_title'	=>	$title,
		'post_content'	=>	$content,
		'post_status'	=>	'publish',
		'post_author'	=>	$user_id,
		'post_type'		=>	'submission',
	);

	//insert the post into the database
	if ( $new_post_id = wp_insert_post( $submission_data ) ) {

		// Check if submission is auto approved or not
		$submission_status = ( get_post_meta( $achievement_id, '_badgeos_earned_by', true ) == 'submission_auto' ) ? 'approved' : 'pending';

		// Set the submission approval status
		add_post_meta( $new_post_id, '_badgeos_submission_status', sanitize_text_field( $submission_status ) );

		// save the achievement ID related to the submission
		add_post_meta( $new_post_id, '_badgeos_submission_achievement_id', $achievement_id );

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
					'post_parent'       => $new_post_id
				);

				$attach_id  = wp_insert_attachment( $attachment, $upload['file'] );

			}
		}

		do_action( 'save_submission', $new_post_id );

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
'.html_entity_decode( esc_url_raw( get_edit_post_link( absint( $new_post_id ) ) ) ).'

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
 *
 *
 */
function badgeos_get_comment_form( $post_id = 0 ) {

	// user must be logged in to see the submission form
	if ( !is_user_logged_in() )
		return '';

	$defaults = array(
		'heading' => sprintf( '<h4>%s</h4>', __( 'Comment on Submission', 'badgeos' ) ),
		'attachment' => __( 'Attachment:', 'badgeos' ),
		'submit' => __( 'Submit Comment', 'badgeos' )
	);
	// filter our text
	$new_defaults = apply_filters( 'badgeos_comment_form_language', $defaults );
	// fill in missing data
	$language = wp_parse_args( $new_defaults, $defaults );

	$sub_form = '<form class="badgeos-comment-form" method="post" enctype="multipart/form-data">';
		$sub_form .= wp_nonce_field( 'badgeos_comment_form', 'submit_comment' );
		// comment form heading
		$sub_form .= '<legend>'. $language['heading'] .'</legend>';
		// submission file upload
		$sub_form .= '<fieldset class="badgeos-file-submission">';
		$sub_form .= '<p><label>'. $language['attachment'] .' <input type="file" name="document_file" id="document_file" /></label></p>';
		$sub_form .= '</fieldset>';
		// submission comment
		$sub_form .= '<fieldset class="badgeos-submission-comment">';
		$sub_form .= '<p><textarea name="badgeos_comment"></textarea></p>';
		$sub_form .= '</fieldset>';

		// submit button
		$sub_form .= '<p class="badgeos-submission-submit"><input type="submit" name="badgeos_comment_submit" value="'. $language['submit'] .'" /></p>';
	$sub_form .= '</form>';

	return apply_filters( 'badgeos_get_comment_form', $sub_form );

}

/**
 * Save submission comment data
 *
 */
function badgeos_save_comment_data( $post_id = 0 ) {
	global $current_user;

	if ( ! isset( $_POST['badgeos_comment_submit'] ) || ! isset( $_POST['badgeos_comment'] ) )
		return;

	// process comment data

	// nonce check for security
	check_admin_referer( 'badgeos_comment_form', 'submit_comment' );

	get_currentuserinfo();

	$comment_data = array(
		'comment_post_ID' => absint( $post_id ),
		'comment_content' => sanitize_text_field( $_POST['badgeos_comment'] ),
		'user_id' => $current_user->ID,
	);

	if ( $comment_id = wp_insert_comment( $comment_data ) ) {

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
					'post_mime_type' => $filetype['type'],
					'post_title'	  => addslashes( $title ),
					'post_content'	  => '',
					'post_status'	  => 'inherit',
					'post_parent'	  => absint( $post_id ),
					'post_author'	  => $current_user->ID
				);

				$attach_id  = wp_insert_attachment( $attachment, $upload['file'] );

			}
		}

		echo 'Comment saved!';

	}

}

/**
 * Returns all comments for a Submission entry
 *
 *
 */
function badgeos_get_comments_for_submission( $post_id = 0 ) {

	$comments = get_comments( array(
		'post_id' => absint( $post_id ),
		'orderby' => 'date',
		'order' => 'ASC',
	) );

	$comment_data = '';

	if ( !$comments )
		return $comment_data;

	$comment_data .= '<ul class="badgeos-submission-comments-list">';

	// init our odd_even class
	$odd_even = 'odd';
	foreach( $comments as $comment ) :

		// get comment author data
		$user_data = get_userdata( $comment->user_id );

		//display comment data
		$comment_data .= '<li class="badgeos-submission-comment '. $odd_even .'">';
		$comment_data .= '<div class="badgeos-comment-text">';
		$comment_data .= wpautop( $comment->comment_content );
		$comment_data .= '</div>';

		$comment_data .= '<p class="badgeos-comment-date-by alignright">';
		$comment_data .= sprintf( __( '%s by %s', 'badgeos' ), '<span class="badgeos-comment-date">'. mysql2date( 'F j, Y g:i a', $comment->comment_date ) .'<span>', '<cite class="badgeos-comment-author">'. $user_data->display_name ) .'</cite>';
		$comment_data .= '</p>';

		$comment_data .= '</li><!-- badgeos-submission-comment -->';
		// toggle our odd_even class
		$odd_even = $odd_even == 'odd' ? 'even' : 'odd';

	endforeach;

	$comment_data .= '</ul><!-- .badgeos-submission-comments-list -->';

	if ( $comment_data )
		$comment_data =  sprintf( '<h4>%s</h4>', __( 'Submission Comments', 'badgeos' ) ) . $comment_data;

	return apply_filters( 'badgeos_get_comments_for_submission', $comment_data );

}


/**
 * Check if a user has an existing submission for an achievement
 *
 *
 */
function badgeos_check_if_user_has_submission( $user_id, $activity_id ) {

	$args = array(
		'post_type'		=>	'submission',
		'author'		=>	absint( $user_id ),
		'post_status'	=>	'publish',
		'meta_key'		=>	'_badgeos_submission_achievement_id',
		'meta_value'	=>	absint( $activity_id ),
	);

	$submissions = get_posts( $args );

	if ( !empty( $submissions ) ) {
		//user has an active submission for this achievement
		return true;
	}

	//user has no active submission for this achievement
	return false;

}

/**
 * Check if a user has an existing nomination for an achievement
 *
 *
 */
function badgeos_check_if_user_has_nomination( $user_id, $activity_id ) {

	$args = array(
		'post_type'		=>	'nomination',
		'author'		=>	absint( $user_id ),
		'post_status'	=>	'publish',
		'meta_key'		=>	'_badgeos_nomination_achievement_id',
		'meta_value'	=>	absint( $activity_id ),
	);

	$nomination = get_posts( $args );

	if ( !empty( $nomination ) ) {

		//user has an active nomination for this achievement
		return true;

	}

	//user has no active nomination for this achievement
	return false;

}
