<?php

class BadgeOS_Activity_Test extends WP_UnitTestCase {

	public $default_achievement_id = 0;

	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * Set up our default post type for single site installs.
	 *
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
	 *
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
	 * @covers ::badgeos_achievement_last_user_activity()
	 */
	public function test_badgeos_achievement_last_user_activity_empty() {
		$user_id = $this->factory->user->create();

		badgeos_register_post_types();

		# Set it up so we have an achievement available, though not awarded.
		$achievement_id = $this->factory->post->create( array( 'post_title' => 'We are #1', 'post_type' => 'trophy' ) );

		$activity = badgeos_achievement_last_user_activity( $achievement_id, $user_id );
		$this->assertTrue( is_int( $activity ) );
		$this->assertEquals ( 0, $activity );
	}

	/**
	 * @covers ::badgeos_achievement_last_user_activity()
	 */
	public function test_badgeos_achievement_last_user_activity() {
		$user_id = $this->factory->user->create();

		badgeos_register_post_types();

		$achievement_id = $this->factory->post->create( array( 'post_title' => 'We are #1', 'post_type' => 'trophy' ) );
		badgeos_user_add_active_achievement( $user_id, $achievement_id );

		$activity = badgeos_achievement_last_user_activity( $achievement_id, $user_id );
		$this->assertTrue( is_int( $activity ) );
		$this->assertNotEquals ( 0, $activity );
	}

	/**
	 * @covers ::badgeos_user_get_active_achievements()
	 */
	public function test_badgeos_user_get_active_achievements_empty() {
		$user_id = $this->factory->user->create();

		# No active achievement set.
		$achievements = badgeos_user_get_active_achievements( $user_id );

		$this->assertEmpty( $achievements );

	}
	/**
	 * @covers ::badgeos_user_get_active_achievements()
	 */
	public function test_badgeos_user_get_active_achievements() {

		$user_id = $this->factory->user->create();

		badgeos_register_post_types();

		$achievement_id = $this->factory->post->create( array( 'post_title' => 'We are #1', 'post_type' => 'trophy' ) );

		badgeos_user_add_active_achievement( $user_id, $achievement_id );

		$activity = badgeos_achievement_last_user_activity( $achievement_id, $user_id );
		$this->assertTrue( is_int( $activity ) );
		$this->assertNotEquals ( 0, $activity );

		# Used for equals assertion
		$achievement_type = get_post_type( $achievement_id );

		badgeos_user_add_active_achievement( $user_id, $achievement_id );

		$achievements = badgeos_user_get_active_achievements( $user_id );

		$this->assertNotEmpty( $achievements );
		$this->assertTrue( is_array( $achievements ) );

		$this->assertObjectHasAttribute( 'ID', $achievements[ $achievement_id ] );
		$this->assertEquals( $achievement_id, $achievements[ $achievement_id ]->ID );

		$this->assertObjectHasAttribute( 'post_type', $achievements[ $achievement_id ] );
		$this->assertEquals( $achievement_type, $achievements[ $achievement_id ]->post_type );

		$this->assertObjectHasAttribute( 'points', $achievements[ $achievement_id ] );

		$this->assertObjectHasAttribute( 'last_activity_date', $achievements[ $achievement_id ] );

		$this->assertObjectHasAttribute( 'date_started', $achievements[ $achievement_id ] );

	}

	/**
	 * @covers ::badgeos_user_update_active_achievements()
	 */
	public function test_badgeos_user_update_active_achievements() {}

	/**
	 * @covers ::badgeos_user_get_active_achievement()
	 */
	public function test_badgeos_user_get_active_achievement() {}

	/**
	 * @covers ::badgeos_user_add_active_achievement()
	 */
	public function test_badgeos_user_add_active_achievement() {}

	/**
	 * @covers ::badgeos_user_update_active_achievement()
	 */
	public function test_badgeos_user_update_active_achievement() {}

	/**
	 * @covers ::badgeos_user_delete_active_achievement()
	 */
	public function test_badgeos_user_delete_active_achievement() {}

	/**
	 * @covers ::badgeos_user_update_active_achievement_on_earnings()
	 */
	public function test_badgeos_user_update_active_achievement_on_earnings() {}
}
