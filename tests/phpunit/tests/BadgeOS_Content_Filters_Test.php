<?php

class BadgeOS_Content_Filters_Test extends WP_UnitTestCase {

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
	 * @covers ::badgeos_is_main_loop()
	 */
	public function test_badgeos_is_main_loop() {}

	/**
	 * @covers ::badgeos_has_user_earned_achievement()
	 */
	public function test_badgeos_has_user_earned_achievement() {
		badgeos_register_achievement_type_cpt();
		$achievement_id = $this->factory->post->create( array( 'post_type' => 'trophy' ) );

		$this->assertTrue( badgeos_is_achievement( $achievement_id ) );

		$has_earned = badgeos_has_user_earned_achievement( $achievement_id, $this->user_id );
		$this->assertFalse( $has_earned );

		badgeos_award_achievement_to_user( $achievement_id, $this->user_id );

		$has_earned = badgeos_has_user_earned_achievement( $achievement_id, $this->user_id );
		$this->assertTrue( $has_earned );
	}

	/**
	 * Clean up our post types.
	 *
	 * @after
	 */
	public function test_badgeos_post_types_cleanup() {
		parent::reset_post_types();
	}

}
