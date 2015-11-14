<?php

class BadgeOS_Triggers_Test extends WP_UnitTestCase {

	protected $user_id = 0;
	public function setUp() {
		parent::setUp();

		$this->user_id = $this->factory->user->create();
	}

	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * @covers ::badgeos_get_activity_triggers()
	 */
	public function test_badgeos_get_activity_triggers() {

		/*
		 * Check for all of our array keys needed for core use.
		 */
		$triggers = badgeos_get_activity_triggers();

		$this->assertTrue( is_array( $triggers ) );

		// WordPress-specific
		$this->assertArrayHasKey( 'wp_login', $triggers );
		$this->assertArrayHasKey( 'badgeos_new_comment', $triggers );
		$this->assertArrayHasKey( 'badgeos_specific_new_comment', $triggers );
		$this->assertArrayHasKey( 'badgeos_new_post', $triggers );
		$this->assertArrayHasKey( 'badgeos_new_page', $triggers );
		// BadgeOS-specific
		$this->assertArrayHasKey( 'specific-achievement', $triggers );
		$this->assertArrayHasKey( 'any-achievement', $triggers );
		$this->assertArrayHasKey( 'all-achievements', $triggers );

	}

	/**
	 * @covers ::badgeos_load_activity_triggers()
	 */
	public function test_badgeos_load_activity_triggers() {
		$this->markTestIncomplete(
				'Needs further work with unlock hooks.'
		);

		global $wp_filter;
		$triggers = badgeos_get_activity_triggers();
		badgeos_load_activity_triggers();

		foreach ( $triggers as $trigger => $label ) {
			$this->assertEquals( '10', has_action( $trigger, 'badgeos_trigger_event' ) );
		}

		foreach( badgeos_get_achievement_types_slugs() as $achievement_type ) {
			$this->assertArrayHasKey( 'badgeos_unlock_' . $achievement_type, $wp_filter );
			$this->assertArrayHasKey( 'badgeos_unlock_all_' . $achievement_type, $wp_filter );
		}
	}

	/**
	 * @covers ::badgeos_trigger_event()
	 */
	public function test_badgeos_trigger_event() {}

	/**
	 * @covers ::badgeos_trigger_get_user_id()
	 */
	public function test_badgeos_trigger_get_user_id() {}

	/**
	 * @covers ::badgeos_get_user_triggers()
	 */
	public function test_badgeos_get_user_triggers() {
		$triggers = get_user_meta( $this->user_id, '_badgeos_triggered_triggers', true );
	}

	/**
	 * @covers ::badgeos_get_user_trigger_count()
	 */
	public function test_badgeos_get_user_trigger_count() {}

	/**
	 * @covers ::badgeos_update_user_trigger_count()
	 */
	public function test_badgeos_update_user_trigger_count() {}

	/**
	 * @covers ::badgeos_reset_user_trigger_count()
	 */
	public function test_badgeos_reset_user_trigger_count() {}

	/**
	 * @covers ::badgeos_publish_listener()
	 */
	public function test_badgeos_publish_listener() {}

	/**
	 * @covers ::badgeos_approved_comment_listener
	 */
	public function test_badgeos_approved_comment_listener() {}
}
