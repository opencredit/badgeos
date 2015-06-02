<?php

class BadgeOS_Credly_Helpers_Test extends WP_UnitTestCase {

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
	 * @covers credly_issue_badge()
	 */
	public function test_credly_issue_badge() {}

	/**
	 * @covers badgeos_credly_get_api_key()
	 */
	public function test_badgeos_credly_get_api_key() {
		#credly_settings option key
	}

	/**
	 * @covers credly_get_api_key()
	 */
	public function test_credly_get_api_key() {
		#credly_settings option key
	}

	/**
	 * @covers credly_is_achievement_giveable()
	 */
	public function test_credly_is_achievement_giveable() {

		#$this->markTestIncomplete( 'Need a user based setting still' );
		/**
		 * Need to figure out how to check if a user can/has sent to credly already.
		 */
		/*badgeos_register_achievement_type_cpt();
		$achievement_id = $this->factory->post->create( array( 'post_type' => 'trophy' ) );
		$badge_id = add_post_meta( $achievement_id, '_badgeos_credly_badge_id', '34313', true );

		$this->assertTrue( '34313' == get_post_meta( $achievement_id, '_badgeos_credly_badge_id', true ) );

		add_post_meta( $achievement_id, '_badgeos_credly_is_giveable', 'true', true );
		$this->assertTrue( credly_is_achievement_giveable( $achievement_id ) );

		add_post_meta( $achievement_id, '_badgeos_credly_is_giveable', 'false', true );
		$this->assertFalse( credly_is_achievement_giveable( $achievement_id ) );

		add_post_meta( $achievement_id, '_badgeos_credly_is_giveable', '', true );
		$this->assertFalse( credly_is_achievement_giveable( $achievement_id ) );

		add_post_meta( $achievement_id, '_badgeos_credly_is_giveable', 'randomstring', true );
		$this->assertFalse( credly_is_achievement_giveable( $achievement_id ) );

		add_post_meta( $achievement_id, '_badgeos_credly_is_giveable', 0, true );
		$this->assertFalse( credly_is_achievement_giveable( $achievement_id ) );

		add_post_meta( $achievement_id, '_badgeos_credly_is_giveable', 1, true );
		$this->assertFalse( credly_is_achievement_giveable( $achievement_id ) );

		add_post_meta( $achievement_id, '_badgeos_credly_is_giveable', true, true );
		$this->assertFalse( credly_is_achievement_giveable( $achievement_id ) );

		add_post_meta( $achievement_id, '_badgeos_credly_is_giveable', false, true );
		$this->assertFalse( credly_is_achievement_giveable( $achievement_id ) );*/

	}

	/**
	 * @covers credly_fieldmap_get_fields()
	 */
	public function test_credly_fieldmap_get_fields() {}

	/**
	 * @covers credly_fieldmap_list_options()
	 */
	public function test_credly_fieldmap_list_options() {}

	/**
	 * @covers credly_fieldmap_get_field_value()
	 */
	public function test_credly_fieldmap_get_field_value() {}

	/**
	 * Clean up our post types.
	 *
	 * @after
	 */
	public function test_badgeos_post_types_cleanup() {
		parent::reset_post_types();
	}

}
