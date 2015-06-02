<?php

class BadgeOS_Achievements_Test extends WP_UnitTestCase {

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

		$this->factory->post->create( array( 'post_title' => 'Trophy', 'post_type' => 'achievement-type' ) );*/
	}

	/**
	 * @covers ::badgeos_is_achievement()
	 */
	public function test_badgeos_is_achievement() {

		// Setup an achievement type
		badgeos_register_achievement_type_cpt();
		$achievement_id = $this->factory->post->create( array( 'post_type' => 'trophy' ) );

		$is_achievement = badgeos_is_achievement( $achievement_id );
		$this->assertTrue( $is_achievement );
	}

	/**
	 * @covers ::badgeos_is_achievement()
	 */
	public function test_badgeos_is_not_achievement() {
		# Create a normal post.
		$not_achievement_id = $this->factory->post->create();

		$is_achievement = badgeos_is_achievement( $not_achievement_id );
		$this->assertFalse( $is_achievement );

	}

	/**
	 * @covers ::badgeos_get_achievements()
	 */
	public function test_badgeos_get_achievements() {
		// $this->factory->post->create_many( 10, array( 'post_type' => 'achievement-type' ) );
		// badgeos_register_achievement_type_cpt();

		// $actual_achievements = get_posts( array( 'post_type' => 'achievement-type' ) );
		// $expected_achievements = badgeos_get_achievements();

		// $this->assertSame( $actual_achievements, $expected_achievements );


		#_unregister_post_type( 'achievement-type' );
	}

	/**
	 * @covers ::badgeos_get_achievement_types()
	 */
	public function test_badgeos_get_achievement_types() {
		# Set some achievement types.
		badgeos_register_achievement_type( 'Trophy', 'Trophies' );
		badgeos_register_achievement_type( 'Quest', 'Quests' );

		# Fetch our registered achievements.
		$achievements = badgeos_get_achievement_types();

		$this->assertNotEmpty( $achievements );

		# Check on our default achievement type of Step
		$this->assertArrayHasKey( 'step', $achievements );
		$step = array( 'single_name' => 'step', 'plural_name' => 'steps' );
		$this->assertEquals( $step, $achievements['step'] );

		$this->assertArrayHasKey( 'trophy', $achievements );
		$step = array( 'single_name' => 'trophy', 'plural_name' => 'trophies' );
		$this->assertEquals( $step, $achievements['trophy'] );

		$this->assertArrayHasKey( 'quest', $achievements );
		$step = array( 'single_name' => 'quest', 'plural_name' => 'quests' );
		$this->assertEquals( $step, $achievements['quest'] );

	}

	/**
	 * @covers ::badgeos_get_achievement_types_slugs()
	 */
	public function test_badgeos_get_achievement_types_slugs() {
		# Set some achievement types.
		badgeos_register_achievement_type( 'Trophy', 'Trophies' );
		badgeos_register_achievement_type( 'Quest', 'Quests' );

		# Fetch our registered achievements.
		$achievement_slugs = badgeos_get_achievement_types_slugs();

		$this->assertNotEmpty( $achievement_slugs );

		$this->assertContains( 'step', $achievement_slugs );
		$this->assertContains( 'trophy', $achievement_slugs );
		$this->assertContains( 'quest', $achievement_slugs );

	}

	/**
	 * @covers ::badgeos_get_parent_of_achievement()
	 */
	public function test_badgeos_get_parent_of_achievement() {}

	/**
	 * @covers ::badgeos_get_children_of_achievement()
	 */
	public function test_badgeos_get_children_of_achievement() {}

	/**
	 * @covers ::badgeos_is_achievement_sequential()
	 */
	public function test_badgeos_is_achievement_sequential() {}

	/**
	 * @covers ::badgeos_achievement_user_exceeded_max_earnings()
	 */
	public function test_badgeos_achievement_user_exceeded_max_earnings() {}

	/**
	 * @covers ::badgeos_build_achievement_object()
	 */
	public function test_badgeos_build_achievement_object() {}

	/**
	 * @covers ::badgeos_get_hidden_achievement_ids()
	 */
	public function test_badgeos_get_hidden_achievement_ids() {}

	/**
	 * @covers ::badgeos_get_user_earned_achievement_ids()
	 */
	public function test_badgeos_get_user_earned_achievement_ids() {}

	/**
	 * @covers ::badgeos_get_user_earned_achievement_types()
	 */
	public function test_badgeos_get_user_earned_achievement_types() {}

	/**
	 * @covers ::badgeos_get_dependent_achievements()
	 */
	public function test_badgeos_get_dependent_achievements() {}

	/**
	 * @covers ::badgeos_get_required_achievements_for_achievement()
	 */
	public function test_badgeos_get_required_achievements_for_achievement() {}

	/**
	 * @covers ::badgeos_get_points_based_achievements()
	 */
	public function test_badgeos_get_points_based_achievements() {}

	/**
	 * @covers ::badgeos_bust_points_based_achievements_cache()
	 */
	public function test_badgeos_bust_points_based_achievements_cache() {}

	/**
	 * @covers ::badgeos_get_achievement_post_thumbnail()
	 */
	public function test_badgeos_get_achievement_post_thumbnail() {}

	/**
	 * @covers ::badgeos_get_achievement_earners()
	 */
	public function test_badgeos_get_achievement_earners() {}

	/**
	 * @covers ::badgeos_get_achievement_earners_list()
	 */
	public function test_badgeos_get_achievement_earners_list() {}

	/**
	 * @covers ::badgeos_ms_show_all_achievements()
	 */
	public function test_badgeos_ms_show_all_achievements() {}

	/**
	 * @covers ::badgeos_get_network_site_ids()
	 */
	public function test_badgeos_get_network_site_ids() {}

	/**
	 * @covers ::badgeos_achievement_set_default_thumbnail()
	 */
	public function test_badgeos_achievement_set_default_thumbnail() {}

	/**
	 * @covers ::badgeos_flush_rewrite_on_published_achievement()
	 */
	public function test_badgeos_flush_rewrite_on_published_achievement() {}

	/**
	 * @covers ::badgeos_flush_rewrite_rules()
	 */
	public function test_badgeos_flush_rewrite_rules() {}

	/**
	 * @covers ::badgeos_maybe_update_achievement_type()
	 */
	public function test_badgeos_maybe_update_achievement_type() {}

	/**
	 * @covers ::badgeos_achievement_type_changed()
	 */
	public function test_badgeos_achievement_type_changed() {}

	/**
	 * @covers ::badgeos_update_achievement_types()
	 */
	public function test_badgeos_update_achievement_types() {}

	/**
	 * @covers ::badgeos_update_achievements_achievement_types()
	 */
	public function test_badgeos_update_achievements_achievement_types() {}

	/**
	 * @covers ::badgeos_update_p2p_achievement_types()
	 */
	public function test_badgeos_update_p2p_achievement_types() {}

	/**
	 * @covers ::badgeos_update_earned_meta_achievement_types()
	 */
	public function test_badgeos_update_earned_meta_achievement_types() {}

	/**
	 * @covers ::badgeos_update_active_meta_achievement_types()
	 */
	public function test_badgeos_update_active_meta_achievement_types() {}

	/**
	 * @covers ::badgeos_get_unserialized_achievement_metas()
	 */
	public function test_badgeos_get_unserialized_achievement_metas() {}

	/**
	 * @covers ::badgeos_get_achievement_metas()
	 */
	public function test_badgeos_get_achievement_metas() {}

	/**
	 * @covers ::badgeos_update_meta_achievement_types()
	 */
	public function test_badgeos_update_meta_achievement_types() {}

	/**
	 * @covers ::badgeos_achievement_type_rename_redirect()
	 */
	public function test_badgeos_achievement_type_rename_redirect() {}

	/**
	 * @covers ::badgeos_achievement_type_update_messages()
	 */
	public function test_badgeos_achievement_type_update_messages() {}

	/**
	 * Clean up our post types.
	 *
	 * @after
	 */
	public function test_badgeos_post_types_cleanup() {
		parent::reset_post_types();
	}

}
