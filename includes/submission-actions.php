<?php
/**
 * Submission and Nomination Functions
 *
 * @package BadgeOS
 * @author LearningTimes, LLC
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
		esc_textarea( $_POST['badgeos_nomination_content'] ),
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
			'post_title'	=>	esc_html( $title ),
			'post_content'	=>	esc_textarea( $content ),
			'post_status'	=>	'publish',
			'post_author'	=>	absint( $user_nominated ),
			'post_type'		=>	'nomination',
		);

		//insert the post into the database
		if ( $new_post_id = wp_insert_post( $submission_data ) ) {
			//save the achievement id metadata
			add_post_meta( $new_post_id, '_badgeos_nomination_achievement_id', absint( $achievement_id ) );

			//save the user being nominated
			add_post_meta( $new_post_id, '_badgeos_nomination_user_id', absint( $user_nominated ) );

			//save the user doing the nomination
			add_post_meta( $new_post_id, '_badgeos_nominating_user_id', absint( $user_nominating ) );

			do_action( 'badgeos_save_nomination', $new_post_id );

			// Submission status workflow
			$status_args = array(
				'achievement_id' => $achievement_id,
				'user_id' => $user_nominated,
				'from_user_id' => $user_nominating,
				'submission_type' => 'nomination'
			);

			badgeos_set_submission_status( $new_post_id, 'pending', $status_args );

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
 * @return string         The concatenated markup
 */
function badgeos_submission_column_action( $column = '' ) {
	global $post;

	// Do not pollute other post types
	if ( ! in_array( $post->post_type, array( 'submission', 'nomination' ) ) ) {
		return;
	}

	switch ( $column ) {
		case 'action':

			//if submission use the post Author ID, if nomination use the user ID meta value
			$user_id = ( isset( $_GET['post_type'] ) && 'submission' == $_GET['post_type'] ) ? $post->post_author : get_post_meta( $post->ID, '_badgeos_submission_user_id', true );

			echo '<a class="button-secondary" href="'.wp_nonce_url( add_query_arg( array( 'badgeos_status' => 'approve', 'post_id' => absint( $post->ID ), 'user_id' => absint( $user_id ) ) ), 'badgeos_status_action' ).'">'.__( 'Approve', 'badgeos' ).'</a>&nbsp;&nbsp;';
			echo '<a class="button-secondary" href="'.wp_nonce_url( add_query_arg( array( 'badgeos_status' => 'denied', 'post_id' => absint( $post->ID ), 'user_id' => absint( $user_id ) ) ), 'badgeos_status_action' ).'">'.__( 'Deny', 'badgeos' ).'</a>';
			break;

		case 'content':

			$content = substr( $post->post_content, 0, 250 ) .'...';
            echo html_entity_decode( wpautop( $content ), ENT_QUOTES, 'UTF-8' );
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
		$submission_statuses = array(
			'approved' => __( 'Approve', 'badgeos' ),
			'denied'  => __( 'Deny', 'badgeos' ),
			'pending' => __( 'Pending', 'badgeos' ),
		);

		$current_status = ( isset( $_GET['badgeos_submission_status'] ) ) ? $_GET['badgeos_submission_status'] : '';

		//output html for status dropdown filter
		echo "<select name='badgeos_submission_status' id='badgeos_submission_status' class='postform'>";
		echo "<option value=''>" .__( 'Show All Statuses', 'badgeos' ).'</option>';
		foreach ( $submission_statuses as $status_key => $status ) {
			echo '<option value="'.strtolower( $status_key ).'"  '.selected( $current_status, $status_key ).'>' .$status .'</option>';
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
 * @return object 	    The Query after filtering
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

	if (
		!empty( $_POST )
		&& isset( $_POST[ 'post_type' ] ) // verify post_type is set
		&& in_array( $_POST[ 'post_type' ], array( 'submission', 'nomination' ) ) // verify post type is submission/nomination
		&& isset( $_POST[ 'wp_meta_box_nonce' ] ) // verify nonce is set
		&& wp_verify_nonce( $_POST[ 'wp_meta_box_nonce' ], 'init.php' ) // verify nonce for security
		&& current_user_can( 'edit_post', $post_id ) // check if current user has permission to edit this submission/nomination
		&& isset( $_POST[ '_badgeos_' . $_POST[ 'post_type' ] . '_status' ] ) // verify status is set
		&& $_POST[ '_badgeos_' . $_POST[ 'post_type' ] . '_status' ] != get_post_meta( $post_id, '_badgeos_' . $_POST[ 'post_type' ] . '_status', true ) // if submission/nomination hasn't changed, skip it
	) {

		// Get the achievement and user attached to this submission/nomination
		$achievement_id = get_post_meta( $post_id, '_badgeos_' . $_POST[ 'post_type' ] . '_achievement_id', true );
		$user_id = isset( $_POST[ '_badgeos_nominated_user_id' ] ) ? $_POST[ '_badgeos_nominated_user_id' ] : $_POST[ 'post_author' ];

		// Submission/nomination status workflow
		$status_args = array(
			'achievement_id' => $achievement_id,
			'user_id' => $user_id
		);

		$status = $_POST[ '_badgeos_' . $_POST[ 'post_type' ] . '_status' ];

		badgeos_set_submission_status( $post_id, $status, $status_args );

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
	if ( ( ! isset( $_POST['badgeos_submission_submit'] ) && ! isset($_POST['badgeos_submission_draft']) ) || ! isset( $_POST['badgeos_submission_content'] ) )
		return;

	// Nonce check for security
	check_admin_referer( 'badgeos_submission_form', 'submit_submission' );

    $action =  isset( $_POST['badgeos_submission_submit'] ) ? $_POST['badgeos_submission_submit'] : $_POST['badgeos_submission_draft'];

	// Publish the submission
	return badgeos_create_submission(
		absint( $_POST['achievement_id'] ),
		sprintf( '%1$s: %2$s', get_post_type( absint( $_POST['achievement_id'] ) ), get_the_title( absint( $_POST['achievement_id'] ) ) ),
		esc_textarea( $_POST['badgeos_submission_content'] ),
		absint( $_POST['user_id'] ),
        $action
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
function badgeos_create_submission( $achievement_id  = 0, $title = '', $content = '', $user_id = 0 , $action = '' ) {

    global $wpdb;

    $post_status = 'publish';

    if(!empty($action) && $action == 'Save Draft'){
        $post_status = 'draft';
    }

	$submission_data = array(
		'post_title'	=>	$title,
		'post_content'	=>	$content,
		'post_status'	=>	$post_status,
		'post_author'	=>	$user_id,
		'post_type'		=>	'submission',
	);

    $submission_data_draft = '';

    //Check exists submission data
    if(isset($_POST['post_id']) && ($_POST['post_id'] != 0)){

        $submission_data_draft = get_post(absint( $_POST['post_id'] ));
    }

    if(!empty($submission_data_draft)){

        $update_submission_data = array(
            'ID'           => absint( $_POST['post_id'] ),
            'post_content' => $content,
            'post_status'  => $post_status
        );

        if($title){
            $update_submission_data['post_title'] = $title;
        }

        // Update the submission data into the database
        wp_update_post( $update_submission_data );

        if($post_status == 'publish'){
            // save the achievement ID related to the submission
            add_post_meta( absint( $_POST['post_id'] ), '_badgeos_submission_achievement_id', $achievement_id );

            // Available action for other processes
            do_action( 'badgeos_save_submission', absint( $_POST['post_id'] )  );

            // Submission status workflow
            $status_args = array(
                'achievement_id' => $achievement_id,
                'user_id' => $user_id
            );

            $status = 'pending';

            // Check if submission is auto approved or not
            if ( badgeos_is_submission_auto_approved( absint( $_POST['post_id'] ) ) ) {
                $status = 'approved';
                $status_args[ 'auto' ] = true;
            }

            badgeos_set_submission_status( absint( $_POST['post_id'] ) , $status, $status_args );

		}

    }else if ( $submission_id = wp_insert_post( $submission_data ) ) {

        if($post_status == 'publish'){
            // save the achievement ID related to the submission
            add_post_meta( $submission_id, '_badgeos_submission_achievement_id', $achievement_id );
        }

		// Available action for other processes
		do_action( 'badgeos_save_submission', $submission_id );

		// Submission status workflow
		$status_args = array(
			'achievement_id' => $achievement_id,
			'user_id' => $user_id
		);

		$status = 'pending';

		// Check if submission is auto approved or not
		if ( badgeos_is_submission_auto_approved( $submission_id ) ) {
			$status = 'approved';

			$status_args[ 'auto' ] = true;
		}

		badgeos_set_submission_status( $submission_id, $status, $status_args );

    }
    //Check this submission already has file attachment
    $args = array(
        'post_type'        => 'attachment',
        'post_parent'      => absint( $_POST['post_id'] ),
        'post_status'      => 'draft',
        'post_author'      => $user_id,
        'suppress_filters' => true
    );

    $draft_file = get_posts( $args );

    //process attachment upload if a file was submitted
    if( ! empty($_FILES['document_file'] ) && !empty($_FILES['document_file']['name'])) {

        if ( ! function_exists( 'wp_handle_upload' ) ) require_once( ABSPATH . 'wp-admin/includes/file.php' );

        $file   = $_FILES['document_file'];
        $upload = wp_handle_upload( $file, array( 'test_form' => false ) );

        if( ! isset( $upload['error'] ) && isset($upload['file'] ) ) {

            $filetype   = wp_check_filetype( basename( $upload['file'] ), null );
            $title      = $file['name'];
            $ext        = strrchr( $title, '.' );
            $title      = ( $ext !== false ) ? substr( $title, 0, -strlen( $ext ) ) : $title;

            $post_status = ($post_status == 'draft')?$post_status:'inherit';

            $attachment = array(
                'post_mime_type'    => $filetype['type'],
                'post_title'        => addslashes( $title ),
                'post_content'      => '',
                'post_status'       => $post_status,
                'post_parent'       => absint( $_POST['post_id'] )
            );

            if($draft_file){

                $attachment_file = get_post_meta( $draft_file[0]->ID, '_wp_attached_file', true );

                //Delete file from directory for new file attachment with same submission
                $upload_dir = wp_upload_dir();
                $path = $upload_dir['basedir'].'/'.$attachment_file;
                unlink($path);

                //Update post meta to exists value
                update_attached_file(absint(  $draft_file[0]->ID ), $upload['file']);

                //Update post attachment
                $attachment['ID'] = absint(  $draft_file[0]->ID );
                wp_update_post($attachment);


                $data_array = array('post_status' => $post_status);
                $where = array('ID' => absint(  $draft_file[0]->ID ));
                $wpdb->update( $wpdb->posts , $data_array, $where );

            }else{

                $attachment['post_parent'] = absint($submission_id);
                //Insert file attachment to draft submission
                $attach_id = wp_insert_attachment( $attachment, $upload['file'] );

                $data_array = array('post_status' => $post_status);
                $where = array('ID' => absint(  $attach_id ));
                $wpdb->update( $wpdb->posts , $data_array, $where );
	}
}
    }else{

        if($draft_file){

            $post_status = ($post_status == 'draft')?$post_status:'inherit';

            $data_array = array('post_status' => $post_status);
            $where = array('ID' => absint(  $draft_file[0]->ID ));
            $wpdb->update( $wpdb->posts , $data_array, $where );
        }

    }

    if(isset($_POST['post_id']) && ($_POST['post_id'] != 0)){
         $new_submission_id = $_POST['post_id'];
    }else{
        $new_submission_id = $submission_id;
    }

    $_SESSION['new_added_submission_id'] = $new_submission_id;

    return true;
}

/**
 * Set submission / nominiation status
 * @since 1.4.0
 * @param int $submission_id Object ID
 * @param string $status Status (pending/approved/denied)
 * @param array $args Additional args for config
 */
function badgeos_set_submission_status( $submission_id, $status, $args = array() ) {

	$args = array_merge(
		array(
			'submission_id' => $submission_id,
			'status' => $status,
			'achievement_id' => 0,
			'user_id' => 0,
			'from_user_id' => 0,
			'submission_type' => 'submission',
			'notification_type' => null,
			'auto' => false,
			'user_data' => false,
			'from_user_data' => false
		),
		$args
	);

	$args = apply_filters( 'badgeos_' . $args[ 'submission_type' ] . '_args', $args );

	$args[ 'badgeos_settings' ] = get_option( 'badgeos_settings' );

	$submission_id = $args[ 'submission_id' ] = absint( $args[ 'submission_id' ] );
	$args[ 'achievement_id' ] = absint( $args[ 'achievement_id' ] );

	$status = $args[ 'status' ];
	$submission_type = $args[ 'submission_type' ];

	// Get old status
	$args[ 'old_status' ] = get_post_meta( $submission_id, '_badgeos_' . $submission_type . '_status', true );

	if ( empty( $args[ 'old_status' ] ) ) {
		$args[ 'old_status' ] = 'new';
	}

	$args[ 'user_id' ] = absint( $args[ 'user_id' ] );
	$args[ 'from_user_id' ] = absint( $args[ 'from_user_id' ] );
	$args[ 'from_user_id' ] = ( !empty( $args[ 'from_user_id' ] ) ) ? absint( $args[ 'from_user_id' ] ) : get_post_meta( $submission_id, '_badgeos_nominating_user_id', true );

	if ( $args[ 'user_id' ] && !$args[ 'user_data' ] ) {
		$args[ 'user_data' ] = get_userdata( $args[ 'user_id' ] );
	}

	if ( $args[ 'from_user_id' ] && !$args[ 'from_user_data' ]  ) {
		$args[ 'from_user_data' ] = get_userdata( $args[ 'from_user_id' ] );
	}

	if ( !$args[ 'achievement_id' ] || !$args[ 'user_data' ] ) {
		return;
	}

	// set the admin email address
	$email = get_bloginfo( 'admin_email' );

	// add email addresses set for site
	if ( isset( $args[ 'badgeos_settings' ][ 'submission_email_addresses' ] ) && !empty( $args[ 'badgeos_settings' ][ 'submission_email_addresses' ] ) ) {
		if ( !is_array( $email ) ) {
			$email = explode( ',', $email );
		}

		$email = array_merge( $email, explode( ',', $args[ 'badgeos_settings' ][ 'submission_email_addresses' ] ) );
		$email = array_unique( array_filter( $email ) );
		$email = implode( ',', $email );
	}

	$args[ 'submission_email_addresses' ] = $email;

	update_post_meta( $submission_id, '_badgeos_' . $submission_type . '_status', $status );

	$email_messages = array();
	$email_messages = apply_filters( 'badgeos_notifications_' . $submission_type . '_' . $args[ 'old_status' ] . '_to_' . $status . '_messages', $email_messages, $args );
	$email_messages = apply_filters( 'badgeos_notifications_' . $submission_type . '_' . $status . '_messages', $email_messages, $args );
	$email_messages = apply_filters( 'badgeos_notifications_' . $submission_type . '_messages', $email_messages, $args );

	$default_message = array(
		'email' => '',
		'subject' => '',
		'message' => '',
		'headers' => '',
		'attachments' => array()
	);

	foreach ( $email_messages as $email_message ) {
		$email_message = wp_parse_args( $email_message, $default_message );

		$emails = $email_message[ 'email' ];

		if ( ! is_array( $emails ) ) {
			$emails = str_replace( array( ';', "\t", "\n", ' ' ), ',', $emails );
			$emails = explode( ',', $emails );
		}

		if ( ! empty( $emails ) && ! empty( $email_message[ 'subject' ] ) && ! empty( $email_message[ 'message' ] ) && badgeos_can_notify_user( $args[ 'user_id' ] ) ) {
			foreach ( $emails as $email ) {
				$email_message[ 'email' ] = $email;
				call_user_func_array( 'wp_mail', array_values( $email_message ) );
			}
		}
	}

}

/**
 * Filter submission messages and send one for Submission Approval
 *
 * @param array $messages Messages to send
 * @param array $args Submission Args
 */
function badgeos_set_submission_status_submission_approved( $messages, $args ) {

	// Award achievement
	badgeos_award_achievement_to_user( $args[ 'achievement_id' ], $args[ 'user_id' ] );

	// Check if user can be notified
	if ( !badgeos_can_notify_user( $args[ 'user_data' ]->ID ) ) {
		return $messages;
	}

	$email = $args[ 'user_data' ]->user_email;

	$message_id = 'badgeos_submission_approved';

	if ( $args[ 'auto' ] ) {
		$message_id = 'badgeos_submission_auto_approved';

		$email = $args[ 'submission_email_addresses' ];

		$subject = sprintf( __( 'Approved Submission: %1$s from %2$s', 'badgeos' ), get_the_title( $args[ 'achievement_id' ] ), $args[ 'user_data' ]->display_name );

		// set the email message
		$message = sprintf( __( 'A new submission has been received and auto-approved:

			In response to: %1$s
			Submitted by: %2$s

			To view all submissions, including this one, visit: %3$s', 'badgeos' ),
			get_the_title( $args[ 'achievement_id' ] ),
			$args[ 'user_data' ]->display_name,
			admin_url( 'edit.php?post_type=submission' )
		);
	}
	else {
		$subject = sprintf( __( 'Submission Approved: %s', 'badgeos' ), get_the_title( $args[ 'achievement_id' ] ) );

		// set the email message
		$message = sprintf( __( 'Your submission has been approved:

			In response to: %1$s
			Submitted by: %2$s
			%3$s', 'badgeos' ),
			get_the_title( $args[ 'achievement_id' ] ),
			$args[ 'user_data' ]->display_name,
			get_permalink( $args[ 'achievement_id' ] )
		);
	}

	$messages[ $message_id ] = array(
		'email' => $email,
		'subject' => $subject,
		'message' => $message
	);

	return $messages;

}
add_filter( 'badgeos_notifications_submission_approved_messages', 'badgeos_set_submission_status_submission_approved', 10, 2 );


/**
 * Filter submission messages and send one for Nomination Approval
 *
 * @param array $messages Messages to send
 * @param array $args Submission Args
 */
function badgeos_set_submission_status_nomination_approved( $messages, $args ) {

	// Award achievement
	badgeos_maybe_award_achievement_to_user( $args[ 'achievement_id' ], $args[ 'user_id' ] );

	// Check if user can be notified
	if ( !badgeos_can_notify_user( $args[ 'user_data' ]->ID ) ) {
		return $messages;
	}

	$email = $args[ 'user_data' ]->user_email;

	$message_id = 'badgeos_nomination_approved';

	if ( $args[ 'auto' ] ) {
		$message_id = 'badgeos_nomination_auto_approved';

		$email = $args[ 'submission_email_addresses' ];

		$subject = sprintf( __( 'Approved Nomination: %1$s from %2$s', 'badgeos' ), get_the_title( $args[ 'achievement_id' ] ), $args[ 'user_data' ]->display_name );

		// set the email message
		$message = sprintf( __( 'A new nomination has been received and auto-approved:

			In response to: %1$s
			Nominee: %2$s
			Nominated by: %3$s

			To view all nominations, including this one, visit: %4$s', 'badgeos' ),
			get_the_title( $args[ 'achievement_id' ] ),
			$args[ 'user_data' ]->display_name,
			$args[ 'from_user_data' ]->display_name,
			admin_url( 'edit.php?post_type=nomination' )
		);

		// @todo set $email based on nominee and nominated by
	}
	else {
		$subject = sprintf( __( 'Nomination Approved: %s', 'badgeos' ), get_the_title( $args[ 'achievement_id' ] ) );

		// set the email message
		$message = sprintf( __( 'Your nomination has been approved:

			In response to: %1$s
			Nominee: %2$s
			Nominated by: %3$s
			%4$s', 'badgeos' ),
			get_the_title( $args[ 'achievement_id' ] ),
			$args[ 'user_data' ]->display_name,
			$args[ 'from_user_data' ]->display_name,
			get_permalink( $args[ 'achievement_id' ] )
		);

		// @todo set $email based on nominee and nominated by
	}

	$messages[ $message_id ] = array(
		'email' => $email,
		'subject' => $subject,
		'message' => $message
	);

	return $messages;

}
add_filter( 'badgeos_notifications_nomination_approved_messages', 'badgeos_set_submission_status_nomination_approved', 10, 2 );

/**
 * Filter submission messages and send one for Submission Denial
 *
 * @param array $messages Messages to send
 * @param array $args Submission Args
 */
function badgeos_set_submission_status_submission_denied( $messages, $args ) {

	// Check if user can be notified
	if ( !badgeos_can_notify_user( $args[ 'user_data' ]->ID ) ) {
		return $messages;
	}

	$email = $args[ 'user_data' ]->user_email;

	$args[ 'notification_type' ] = 'notify_denied';

	$subject = sprintf( __( 'Submission Not Approved: %s', 'badgeos' ), get_the_title( $args[ 'achievement_id' ] ) );

	// set the email message
	$message = sprintf( __( 'Your submission has not been approved:

		In response to: %1$s
		Submitted by: %2$s
		%3$s', 'badgeos' ),
		get_the_title( $args[ 'achievement_id' ] ),
		$args[ 'user_data' ]->display_name,
		get_permalink( $args[ 'achievement_id' ] )
	);

	$messages[ 'badgeos_submission_denied' ] = array(
		'email' => $email,
		'subject' => $subject,
		'message' => $message
	);

	return $messages;

}
add_filter( 'badgeos_notifications_submission_denied_messages', 'badgeos_set_submission_status_submission_denied', 10, 2 );

/**
 * Filter submission messages and send one for Nomination Denial
 *
 * @param array $messages Messages to send
 * @param array $args Submission Args
 */
function badgeos_set_submission_status_nomination_denied( $messages, $args ) {

	// Check if user can be notified
	if ( !badgeos_can_notify_user( $args[ 'user_data' ]->ID ) ) {
		return $messages;
	}

	$email = $args[ 'user_data' ]->user_email;

	$args[ 'notification_type' ] = 'notify_denied';

	$subject = sprintf( __( 'Nomination Not Approved: %s', 'badgeos' ), get_the_title( $args[ 'achievement_id' ] ) );

	// set the email message
	$message = sprintf( __( 'Your submission has not been approved:

		In response to: %1$s
		Nominee: %2$s
		Nominated by: %3$s
		%4$s', 'badgeos' ),
		get_the_title( $args[ 'achievement_id' ] ),
		$args[ 'user_data' ]->display_name,
		$args[ 'from_user_data' ]->display_name,
		get_permalink( $args[ 'achievement_id' ] )
	);

	// @todo set $email based on nominee and nominated by

	$messages[ 'badgeos_nomination_denied' ] = array(
		'email' => $email,
		'subject' => $subject,
		'message' => $message
	);

	return $messages;

}
add_filter( 'badgeos_notifications_nomination_denied_messages', 'badgeos_set_submission_status_nomination_denied', 10, 2 );

/**
 * Filter submission messages and send one for Submission Pending
 *
 * @param array $messages Messages to send
 * @param array $args Submission Args
 */
function badgeos_set_submission_status_submission_pending( $messages, $args ) {

	if ( $args[ 'badgeos_settings' ] && 'disabled' != $args[ 'badgeos_settings' ][ 'submission_email' ] ) {
		$email = $args[ 'submission_email_addresses' ];

		$subject = sprintf( __( 'Submission: %1$s from %2$s', 'badgeos' ), get_the_title( $args[ 'achievement_id' ] ), $args[ 'user_data' ]->display_name );

		$message = sprintf(
			__( 'A new submission has been received:

			In response to: %1$s
			Submitted by: %2$s

			Review the complete submission and approve or deny it at:
			%3$s

			To view all submissions, visit: %4$s', 'badgeos' ),
			get_the_title( $args[ 'achievement_id' ] ),
			$args[ 'user_data' ]->display_name,
			admin_url( sprintf( 'post.php?post=%d&action=edit', $args[ 'submission_id' ] ) ),
			admin_url( 'edit.php?post_type=submission' )
		);

		$messages[ 'badgeos_submission_pending' ] = array(
			'email' => $email,
			'subject' => $subject,
			'message' => $message
		);
	}

	return $messages;

}
add_filter( 'badgeos_notifications_submission_pending_messages', 'badgeos_set_submission_status_submission_pending', 10, 2 );

/**
 * Filter submission messages and send one for Nomination Pending
 *
 * @param array $messages Messages to send
 * @param array $args Submission Args
 */
function badgeos_set_submission_status_nomination_pending( $messages, $args ) {

	if ( $args[ 'badgeos_settings' ] && 'disabled' != $args[ 'badgeos_settings' ][ 'submission_email' ] ) {
		$email = $args[ 'submission_email_addresses' ];

		$subject = sprintf( __( 'Nomination: %1$s from %2$s', 'badgeos' ), get_the_title( $args[ 'achievement_id' ] ), $args[ 'user_data' ]->display_name );

		$message = sprintf(
			__( 'A new nomination has been received:

			In response to: %1$s
			Nominee: %2$s
			Nominated by: %3$s

			Review the complete submission and approve or deny it at:
			%4$s

			To view all nominations, visit: %5$s', 'badgeos' ),
			get_the_title( $args[ 'achievement_id' ] ),
			$args[ 'user_data' ]->display_name,
			$args[ 'from_user_data' ]->display_name,
			admin_url( sprintf( 'post.php?post=%d&action=edit', $args[ 'submission_id' ] ) ),
			admin_url( 'edit.php?post_type=nomination' )
		);

		$messages[ 'badgeos_nomination_pending' ] = array(
			'email' => $email,
			'subject' => $subject,
			'message' => $message
		);
	}

	return $messages;

}
add_filter( 'badgeos_notifications_submission_pending_messages', 'badgeos_set_submission_status_submission_pending', 10, 2 );

/**
 * Returns the comment form for Submissions
 * @since 1.0.0
 * @param integer $post_id The post_id for the current page
 * @return string 		   The submission markup
 */
function badgeos_get_comment_form( $post_id = 0 ) {

    global $post;

	if ( ! is_user_logged_in() )
		return '';

	$defaults = array(
		'heading'    => '<h4>' . sprintf( __( 'Comment on Submission:', 'badgeos' )) . '</h4>',
		'attachment' => __( 'Attachment:', 'badgeos' ),
		'submit'     => __( 'Submit Comment', 'badgeos' ),
		'toggle'     => __( 'Show/Add Comments', 'badgeos' ),
	);
	$language = wp_parse_args( apply_filters( 'badgeos_comment_form_language', $defaults ), $defaults );


    //check draft comment
    $comment_filter = array(
        'type' => 'draft',
        'user_id' => get_current_user_id(),
        'post_id' => absint( $post_id ),
        'orderby' => 'comment_ID',
        'order' => 'DESC',
        'offset' => 1
    );

    $comments = get_comments($comment_filter);

    $comment_data = null;

    if($comments){

        foreach($comments as $comment){
            $comment_data = $comment;
        }
    }

    //Check enable/disable attachment for submission comment
    $id = $post->ID;

    if($post->post_type == 'page'){

        $post_data = get_post($post_id);

        $pos = strpos($post_data->post_title,":");
        $post_type = trim(substr($post_data->post_title,0,$pos));
        $post_title = trim(substr(strstr($post_data->post_title,':'),1));

        global $wpdb;

        $table = $wpdb->posts;

        $query = "SELECT ID FROM ".$table." WHERE post_title LIKE '$post_title%' AND post_type LIKE '$post_type%' ORDER BY ID LIMIT 1";

        $id = $wpdb->get_var($query);

    }

    //check attachment in draft comment submission data
    $attachment_data = get_attachment_from_draft_submission($comment_data->comment_post_ID, get_current_user_id());

    $attachment = null;

    if(!empty($attachment_data)){
        $attachment = render_attachment_from_draft_submission($attachment_data);
    }

	$sub_form = '<form class="badgeos-comment-form" method="post" enctype="multipart/form-data">';

		// comment form heading
		$sub_form .= '<legend>'. wp_kses_post( $language['heading'] ) .'</legend>';

		// submission comment
		$sub_form .= '<fieldset class="badgeos-submission-comment-entry">';
		$sub_form .= '<p><textarea name="badgeos_comment" id="badgeos_comment' . absint( $post_id ) . '" class="badgeos_comment">'.$comment_data->comment_content.'</textarea></p>';
		$sub_form .= '</fieldset>';

        if(get_post_meta($id, '_badgeos_all_attachment_submission_comment', true)){
		// submission file upload
		$sub_form .= '<fieldset class="badgeos-submission-file">';
		$sub_form .= '<p><label>'. esc_html( $language['attachment'] ) . ' <input type="file" name="document_file" id="document_file" /></label></p>';
		if($attachment){
            $sub_form .= $attachment;
        }
		$sub_form .= '</fieldset>';

        }
		// submit button
		$sub_form .= '<p class="badgeos-submission-submit" style="margin-top:10px;"><input type="submit" name="badgeos_comment_submit" value="'. $language['submit'] .'" />
		<input type="submit" name="badgeos_comment_draft" value="Save Draft" />
		</p>';

		// Hidden Fields
		$sub_form .= wp_nonce_field( 'submit_comment', 'badgeos_comment_nonce', true, false );
		$sub_form .= '<input type="hidden" name="user_id" value="' . get_current_user_id() . '">';
		$sub_form .= '<input type="hidden" name="submission_id" value="' . absint( $post_id ) . '">';

        if($comments){
        $sub_form .= '<input type="hidden" name="comment_id" value="' . absint( $comment_data->comment_ID ) . '">';
        }

	$sub_form .= '</form>';

	// Toggle button for showing comment form
    $sub_form .= '<a href="" class="button submission-comment-toggle comment-toggle-' . absint( $post_id ) . '">' . esc_html( $language['toggle'] ) . '</a>';

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

    global $wpdb;

    //Check comment type
    $comment_type = '';
    $comment_approved = 1; //Approve

    if(isset($_POST['badgeos_comment_draft'])){

        $comment_type = 'draft';
        $comment_approved = 0; //Unapprove

    }

	// Process comment data
	$comment_data = array(
		'user_id'         => absint( $_POST['user_id'] ),
		'comment_post_ID' => absint( $_POST['submission_id'] ),
		'comment_content' => esc_textarea( $_POST['badgeos_comment'] ),
        'comment_type' => $comment_type,
        'comment_approved' => $comment_approved
	);

    $comment_count = '';

    //Check exists comment data
    if(isset($_POST['comment_id']) && ($_POST['comment_id'] != 0)){

        $comment_filter = array(
            'ID' => absint( $_POST['comment_id'] )
        );

        $comment_count = get_comments($comment_filter);
    }

    if(!empty($comment_count)){

        //update comment data
        $where = array( 'comment_ID' => absint( $_POST['comment_id'] ) );

        $wpdb->update( $wpdb->comments, $comment_data, $where );

    }else if( $comment_id = wp_insert_comment( $comment_data ) ){
    }

    //Check this submission already has file attachment
    $args = array(
        'post_type'        => 'attachment',
        'post_parent'      => absint( $_POST['submission_id'] ),
        'post_status'      => 'draft',
        'post_author'      => absint( $_POST['user_id'] ),
        'suppress_filters' => true
    );

    $draft_file = get_posts( $args );

    //process attachment upload if a file was submitted
    if( ! empty($_FILES['document_file'] ) && !empty($_FILES['document_file']['name'])) {

			if ( ! function_exists( 'wp_handle_upload' ) ) require_once( ABSPATH . 'wp-admin/includes/file.php' );

			$file   = $_FILES['document_file'];
			$upload = wp_handle_upload( $file, array( 'test_form' => false ) );

        if( ! isset( $upload['error'] ) && isset($upload['file'] ) ) {

            $filetype   = wp_check_filetype( basename( $upload['file'] ), null );
            $title      = $file['name'];
            $ext        = strrchr( $title, '.' );
            $title      = ( $ext !== false ) ? substr( $title, 0, -strlen( $ext ) ) : $title;

            $comment_type = ($comment_type == 'draft')?$comment_type:'inherit';

				$attachment = array(
					'post_mime_type' => $filetype['type'],
					'post_title'     => addslashes( $title ),
					'post_content'   => '',
                'post_status'    =>  'draft',
                /*'post_status'    => ! isset ($comment_type) ? 'inherit' : $comment_type ,*/
					'post_parent'    => absint( $_REQUEST['submission_id'] ),
					'post_author'    => absint( $_REQUEST['user_id'] )
				);

            if(!empty($draft_file) && !empty($comment_count)){

                $attachment_file = get_post_meta( $draft_file[0]->ID, '_wp_attached_file', true );

                //Delete file from directory for new file attachment with same submission
                $upload_dir = wp_upload_dir();
                $path = $upload_dir['basedir'].'/'.$attachment_file;
                unlink($path);

                //Update post meta to exists value
                update_attached_file(absint(  $draft_file[0]->ID ), $upload['file']);


                //Update post attachment
                $attachment['ID'] = absint(  $draft_file[0]->ID );
                wp_update_post($attachment);

                $data_array = array('post_status' => $comment_type);
                $where = array('ID' => absint(  $draft_file[0]->ID ));
                $wpdb->update( $wpdb->posts , $data_array, $where );

            }else{
                //Insert file attachment to draft submission
                $attachment_id = wp_insert_attachment( $attachment, $upload['file'] );

                $data_array = array('post_status' => $comment_type);
                $where = array('ID' => absint(  $attachment_id ));
                $wpdb->update( $wpdb->posts , $data_array, $where );

			}
		}
    }else{

        if($draft_file){

            $comment_type = ($comment_type == 'draft')?$comment_type:'inherit';

            $data_array = array('post_status' => $comment_type);
            $where = array('ID' => absint(  $draft_file[0]->ID ));
            $wpdb->update( $wpdb->posts , $data_array, $where );
	    }

    }

	if ( isset( $_POST['badgeos_comment_nonce'] ) ) {
		global $wp;
		if(empty($wp->request)){
			$url = array_filter(explode('/', $_SERVER['REQUEST_URI']));
			$url = $url[count($url)-1].'/'.$url[count($url)];
		}else{
			$url = $wp->request;
		}
		wp_redirect( home_url($url) ); exit;
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
        'type__not_in' => 'draft'
	) );

	// If we have no comments, bail
	if ( empty( $comments ) )
		return;

	// Concatenate our output
	$output = '<div class="badgeos-submission-comments-wrap">';
	$output .= '<h4>' . sprintf( __( 'Comments:', 'badgeos' ), $submission_id ) . '</h4>';
	$output .= '<ul class="badgeos-submission-comments-list">';
	foreach( $comments as $key => $comment ) {
		$odd_even = $key % 2 ? 'even' : 'odd';
		$output .= badgeos_render_submission_comment( $comment, $odd_even );
	}
	$output .= '</ul><!-- .badgeos-submission-comments-list -->';
	$output .= '</div><!-- .badgeos-submission-comments-wrap -->';

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
	$achievement_id = get_post_meta( $submission_id, '_badgeos_submission_achievement_id', true );
	return ( 'submission_auto' == get_post_meta( $achievement_id, '_badgeos_earned_by', true ) );
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
	if ( $has_access && badgeos_achievement_user_exceeded_max_earnings( $user_id, $achievement_id ) ) {
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
 * @return string       The concatenated markup
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
		$sub_form .= '<p><textarea name="badgeos_nomination_content" id="badgeos_nomination_content"></textarea></p>';
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
 * @return string       The concatenated markup
 */
function badgeos_get_submission_form( $args = array() ) {
	global $post, $user_ID;

	// Setup our defaults
	$defaults = array(
		'heading'    => sprintf( '<h4>%s</h4>', __( 'Submission', 'badgeos' ) ),
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


    //check draft submission data
    $submission_data = get_submission_data_from_draft();
    //end check - draft submission data

    //check attachment in draft submission data
    $attachment_data = get_attachment_from_draft_submission($submission_data->ID, $user_ID);

    $attachment = null;

    if(!empty($attachment_data)){
        $attachment = render_attachment_from_draft_submission($attachment_data);
    }

	$sub_form = '<form class="badgeos-submission-form" method="post" enctype="multipart/form-data">';
		// submission form heading
		$sub_form .= '<legend>'. $args['heading'] .'</legend>';

		// submission comment
		$sub_form .= '<fieldset class="badgeos-submission-comment">';
		$sub_form .= '<p><textarea name="badgeos_submission_content" id="badgeos_submission_content">'.$submission_data->post_content.'</textarea></p>';
		$sub_form .= '</fieldset>';

        if(get_post_meta($post->ID, '_badgeos_all_attachment_submission', true)){
		// submission file upload
		$sub_form .= '<fieldset class="badgeos-file-submission">';
		$sub_form .= '<p><label>'. $args['attachment'] .' <input type="file" name="document_file" id="document_file" /></label></p>';
        if($attachment){
        $sub_form .= $attachment;
        }
		$sub_form .= '</fieldset>';
        }
		// submit button
		$sub_form .= '<p class="badgeos-submission-submit"><input type="submit" name="badgeos_submission_submit" value="'. $args['submit'] .'" />
		<input type="submit" name="badgeos_submission_draft" value="Save Draft" />
		</p>';
		// hidden fields
		$sub_form .= wp_nonce_field( 'badgeos_submission_form', 'submit_submission', true, false );
		$sub_form .= '<input type="hidden" name="achievement_id" value="' . absint( $args['achievement_id'] ) . '">';
		$sub_form .= '<input type="hidden" name="user_id" value="' . absint( $args['user_id'] ) . '">';

    if($submission_data){
        $sub_form .= '<input type="hidden" name="post_id" value="' . absint( $submission_data->ID ) . '">';
    }

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

	if ( ! is_user_logged_in() ) {
		return '<p class="error must-be-logged-in">' . __( 'You must be logged in to see results.', 'badgeos' ) . '</p>';
	}

	$args = badgeos_parse_feedback_args( $args );

	$output = '<div class="badgeos-submissions">';
	if ( empty( $args ) ) {
		$output .= '<p class="no-results">' . __( 'Sorry, no results to display', 'badgeos' ) . '</p>';
	} else {
		$feedback_posts = get_posts( $args );
		if ( ! empty( $feedback_posts ) ) {
			foreach( $feedback_posts as $feedback ) {
				if ( badgeos_is_submission_auto_approved( $feedback->ID ) && false === $args['show_auto_approved'] ) {
					continue;
				}
				$output .= call_user_func_array( "badgeos_render_{$feedback->post_type}", array( $feedback, $args ) );
			}
		}
	}
	$output .= '</div><!-- badgeos-submissions -->';

	return apply_filters( 'badgeos_get_submissions', $output, $args );

}

/**
 * Parse and prepare feedback list args.
 *
 * @since  1.4.0
 *
 * @param  array  $args Feedback args.
 * @return array        Modified feedback args.
 */
function badgeos_parse_feedback_args( $args = array() ) {

	$args = wp_parse_args( $args, array(
		'post_status' => 'publish',
		'post_type'   => 'submission',
		'status'      => 'all',
	) );

	// Handler for auto-approved results
	$args['show_auto_approved'] = in_array( $args['status'], array( 'all', 'auto-approved' ) );
	$args['status'] = ( 'auto-approved' == $args['status'] ) ? 'approved' : $args['status'];

	// Limit results by approval status
	if ( 'all' !== $args['status'] ) {
		$args['meta_query'][] = array(
			'key'   => "_badgeos_{$args['post_type']}_status",
			'value' => $args['status']
		);
	}

	// Limit results by achievement ID
	if ( isset( $args['achievement_id'] ) ) {
		$args['meta_query'][] = array(
			'key'   => "_badgeos_{$args['post_type']}_achievement_id",
			'value' => absint( $args['achievement_id'] ),
		);
	}

	// Limit results by author
	if ( empty( $args['author'] ) && 'nomination' !== $args['post_type'] ) {
		if ( ! badgeos_user_can_manage_submissions() ) {
			$args['author'] = get_current_user_id();
		}
	}

	return apply_filters( 'badgeos_get_feedback_args', $args );
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
	global $post;

	// Setup our empty args array
	$args = array();

	// Setup our author limit
	if ( ! empty( $user_id ) ) {
		// Use the provided user ID
		$args['author'] = absint( $user_id );
	} else {
		if ( ! badgeos_user_can_manage_submissions() ) {
			$args['author'] = get_current_user_id();
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
	global $post;

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

		// If user doesn't have access to settings,
		// restrict posts to ones they've authored
		if ( ! badgeos_user_can_manage_submissions() ) {
			$args['meta_query'][] = array(
				'key'   => '_badgeos_nominating_user_id',
				'value' => get_current_user_id()
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
	$attachments = get_posts(
		array(
			'post_type'      => 'attachment',
			'posts_per_page' => -1,
			'post_parent'    => $submission_id,
			'orderby'        => 'date',
			'order'          => 'ASC',
		)
	);

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

/**
 * Get the data saved from the draft
 * @return array
 */
function get_submission_data_from_draft(){

    global $post, $user_ID;

    global $wpdb;

    $post_title = sprintf( '%1$s: %2$s', get_post_type( absint( $post->ID ) ), get_the_title( absint( $post->ID ) ) );

    $table = $wpdb->posts;

    $query = "SELECT ID FROM ".$table." WHERE post_status='draft' AND post_type='submission' AND post_author = $user_ID AND post_title LIKE '%$post_title%' ORDER BY ID DESC LIMIT 1";

    $post_id = $wpdb->get_var($query);

    $submission_data = array();

    if ( $post_id ){
        $submission_data = get_post($post_id);
    }

    return $submission_data;

}

/**
 * Get the data from the submission form to be saved as draft
 * @param $post_id
 * @param $user_ID
 * @return array
 */
function get_attachment_from_draft_submission($post_id, $user_ID){

    global $wpdb;

    $table = $wpdb->posts;

    $query = "SELECT ID FROM ".$table." WHERE post_status='draft' AND post_type='attachment' AND post_author = $user_ID AND post_parent = ".$post_id." ORDER BY ID DESC LIMIT 1";

    $attachment_post_id = $wpdb->get_var($query);

    $attachment_data = array();

    if ( $attachment_post_id ){
        $attachment_data = get_post($attachment_post_id);
    }

    return $attachment_data;
}

/**
 * Function to render the attachment that is saved in draft
 * @param array $attachment
 * @return null|string
 */
function render_attachment_from_draft_submission($attachment = array()){

    $sub_form = null;

    if(!empty($attachment)){

        $userdata = get_userdata( $attachment->post_author );
        $display_name = is_object( $userdata ) ? $userdata->display_name : '';

        // Concatenate the markup
        $sub_form .= '<h6>' . sprintf( __( 'Submitted Draft Attachment:', 'badgeos' )) . '</h6>';
        $sub_form .= sprintf( __( '%1$s - uploaded %2$s by %3$s', 'badgeos' ),
            wp_get_attachment_link( $attachment->ID, 'full', false, null, $attachment->post_title ),
            get_the_time( 'F j, Y g:i a', $attachment ),
            $display_name
        );
    }

    return $sub_form;
}



