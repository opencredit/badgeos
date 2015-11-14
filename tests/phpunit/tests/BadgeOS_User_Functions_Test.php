<?php

class BadgeOS_User_Functions_Test extends WP_UnitTestCase {

	protected $user_id = 0;

	public function setUp() {
		parent::setUp();

		$this->user_id = $this->factory->user->create();
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
	 * @covers ::badgeos_get_user_achievements()
	 */
	public function test_badgeos_get_user_achievements() {

		/*
		 * Create our user, achievement type, and achievement in the achievement type.
		 */
		badgeos_register_achievement_type_cpt();
		$achievement_id = $this->factory->post->create( array( 'post_type' => 'trophy' ) );

		$this->assertTrue( badgeos_is_achievement( $achievement_id ) );

		badgeos_award_achievement_to_user( $achievement_id, $this->user_id );
		$args         = array(
			'user_id' => $this->user_id,
		);
		$achievements = badgeos_get_user_achievements( $args );
		$this->assertInternalType( 'array', $achievements );
		$this->assertNotEmpty( $achievements );

	}

	/**
	 * @covers ::badgeos_update_user_achievements()
	 */
	public function test_badgeos_update_user_achievements() {

		/*
		 * Set initial amount.
		 *
		 * Create new amount to update/replace all with.
		 *
		 * Reset to initial
		 *
		 * Add new amount to append to.
		 */
	}

	/**
	 * @covers ::badgeos_user_profile_data()
	 */
	public function test_badgeos_user_profile_data() {

	}

	/**
	 * @covers ::badgeos_save_user_profile_fields()
	 */
	public function test_badgeos_save_user_profile_fields() {

	}

	/**
	 * @covers ::badgeos_profile_award_achievement()
	 */
	public function test_badgeos_profile_award_achievement() {

	}

	/**
	 * @covers ::badgeos_process_user_data()
	 */
	public function test_badgeos_process_user_data() {
	}

	/**
	 * @covers ::badgeos_get_network_achievement_types_for_user()
	 */
	public function test_badgeos_get_network_achievement_types_for_user() {
	}

	/**
	 * @covers ::badgeos_can_notify_user()
	 */
	public function test_badgeos_can_notify_user() {

	}

	/**
	 * Clean up our post types.
	 * @after
	 */
	public function test_badgeos_post_types_cleanup() {
		parent::reset_post_types();
	}

}
