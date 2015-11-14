<?php

class BadgeOS_Feedback_Test extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * Set up our default post type for single site installs.
	 * @before
	 */
	public function test_badgeos_achievement_type_create_non_ms() {
		if ( is_multisite() ) {
			return;
		}
		$this->factory->post->create( array( 'post_title' => 'Trophy', 'post_type' => 'achievement-type' ) );
	}

	/**
	 * Set up our default post type for multisite installs
	 * @before
	 */
	public function test_badgeos_achievement_type_create_ms() {
		/*if ( ! is_multisite() ) {
			return;
		}

		# Create a site in the multisite and switch to it.
		$b = $this->factory->blog->create();
		switch_to_blog( $b );

		badgeos_register_post_types();

		$this->factory->post->create( array( 'post_title' => 'Trophy', 'post_type' => 'achievement-type' ) );*/
	}

	/**
	 * @covers ::badgeos_save_nomination_data()
	 */
	public function test_badgeos_save_nomination_data() {
	}

	/**
	 * @covers ::badgeos_create_nomination()
	 */
	public function test_badgeos_create_nomination() {
	}

	/**
	 * @covers ::badgeos_process_submission_review()
	 */
	public function test_badgeos_process_submission_review() {
	}

	/**
	 * @covers ::badgeos_save_submission_data()
	 */
	public function test_badgeos_save_submission_data() {
	}

	/**
	 * @covers ::badgeos_create_submission()
	 */
	public function test_badgeos_create_submission() {
	}

	/**
	 * @covers ::badgeos_save_comment_data()
	 */
	public function test_badgeos_save_comment_data() {
	}

	/**
	 * @covers ::badgeos_get_comments_for_submission()
	 */
	public function test_badgeos_get_comments_for_submission() {
	}

	/**
	 * @covers ::badgeos_is_submission_auto_approved()
	 */
	public function test_badgeos_is_submission_auto_approved() {

		#$this->markTestIncomplete( 'We need a way to get the submission ID.' );
		# Set up our user.
		/*$user_id = $this->factory->user_create( array( 'role', 'subscriber') );

		# Setup an achievement type
		badgeos_register_achievement_type_cpt();
		$achievement_id = $this->factory->post->create( array( 'post_type' => 'trophy' ) );

		# Create our submission.
		$submitted = badgeos_create_submission( $achievement_id, 'Submission', 'Content', $user_id );

		# We successfully created the submission.
		$this->assertTrue( $submitted );

		# Attach our achievement ID to the submission.
		add_post_meta( $submission_id, '_badgeos_submission_achievement_id', $achievement_id, true );

		add_post_meta( $achievement_id, '_badgeos_earned_by', 'submission-auto', true );
		echo get_post_meta( $achievement_id, '_badgeos_earned_by', true );
		$submission = badgeos_is_submission_auto_approved( $achievement_id );

		$this->assertTrue( $submission );*/
	}

	/**
	 * @covers ::badgeos_check_if_user_has_feedback()
	 */
	public function test_badgeos_check_if_user_has_feedback() {

		$this->assertFalse( false );

		$this->assertTrue( true );
	}

	/**
	 * @covers ::badgeos_check_if_user_has_submission()
	 */
	public function test_badgeos_check_if_user_has_submission() {

		$this->assertFalse( false );

		$this->assertTrue( true );
	}

	/**
	 * @covers ::badgeos_check_if_user_has_nomination()
	 */
	public function test_badgeos_check_if_user_has_nomination() {

		$this->assertFalse( false );

		$this->assertTrue( true );
	}

	/**
	 * @covers ::badgeos_user_has_access_to_submission_form()
	 */
	public function test_badgeos_user_has_access_to_submission_form() {
		# Check logged out, check exceeded max earnings, check without pending submissions, check with pending submissions
		$this->assertFalse( false );

		$this->assertTrue( true );
	}

	/**
	 * @covers ::badgeos_get_feedback()
	 */
	public function test_badgeos_get_feedback() {
		# Check if string, we're getting html potentially
		# If not logged in, check contains "<p class="error must-be-logged-in">"
		# If logged in but no args, check contains "<p class="no-results">"
		# If logged in with args, check return value of badgeos_render_{$feedback->post_type}()
	}

	/**
	 * @covers ::badgeos_get_submissions()
	 */
	public function test_badgeos_get_submissions() {
		#follow test_badgeos_get_feedback with "submissions" specifically set.
	}

	/**
	 * @covers ::badgeos_get_user_submissions()
	 */
	public function test_badgeos_get_user_submissions() {
		# Check not empty.
	}

	/**
	 * @covers ::badgeos_get_user_nominations()
	 */
	public function test_badgeos_get_user_nominations() {
		# Check not empty.
	}

	/**
	 * @covers ::badgeos_get_submission_attachments()
	 */
	public function test_badgeos_get_submission_attachments() {
		# check contains '<h4>' . sprintf( __( 'Submitted Attachments:', 'badgeos' ), $submission_id ) . '</h4>' . '<ul class="badgeos-attachments-list">';
	}

	/**
	 * @covers ::badgeos_set_submission_status()
	 */
	public function test_badgeos_set_submission_status() {
	}

	/**
	 * @covers ::badgeos_parse_feedback_args()
	 */
	public function test_badgeos_parse_feedback_args() {
	}

	/**
	 * Clean up our post types.
	 * @after
	 */
	public function test_badgeos_post_types_cleanup() {
		parent::reset_post_types();
	}
}
