<?php

class BadgeOS_Post_Types_Test extends WP_UnitTestCase {

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
	public function test_badgeos_achievement_type_create() {
		if ( is_multisite() ) {
			return;
		}
		# Need to remove this one so we can test badgeos_register_post_types()
		remove_action( 'init', 'badgeos_register_post_types' );
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
	 * @covers badgeos_register_achievement_type()
	 */
	public function test_badgeos_register_achievement_type() {
		global $badgeos;

		$this->assertArrayNotHasKey( 'badge', $badgeos->achievement_types );

		badgeos_register_achievement_type( 'badge', 'badges' );

		$this->assertArrayHasKey( 'badge', $badgeos->achievement_types );
		$this->assertTrue( in_array( 'badge', $badgeos->achievement_types['badge'] ) );
		$this->assertTrue( in_array( 'badges', $badgeos->achievement_types['badge'] ) );

		_unregister_post_type( 'badge' );
	}

	/**
	 * @covers badgeos_register_achievement_type_cpt()
	 */
	public function test_badgeos_register_achievement_type_cpt() {
		#badgeos_register_achievement_type_cpt
	}

	/**
	 * @covers badgeos_register_post_types()
	 */
	public function test_badgeos_register_post_types() {

		#$this->assertFalse( post_type_exists( 'achievement-type' ) );
		#$this->assertFalse( post_type_exists( 'step' ) );
		#$this->assertFalse( post_type_exists( 'submission' ) );
		#$this->assertFalse( post_type_exists( 'nomination' ) );
		#$this->assertFalse( post_type_exists( 'badgeos-log-entry' ) );

		badgeos_register_post_types();

		$this->assertTrue( post_type_exists( 'achievement-type' ) );
		$this->assertTrue( post_type_exists( 'step' ) );
		$this->assertTrue( post_type_exists( 'submission' ) );
		$this->assertTrue( post_type_exists( 'nomination' ) );
		$this->assertTrue( post_type_exists( 'badgeos-log-entry' ) );

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
