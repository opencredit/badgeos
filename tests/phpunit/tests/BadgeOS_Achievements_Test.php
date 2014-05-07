<?php

class BadgeOS_Achievements_Test extends WP_UnitTestCase {

	/**
	 * @covers badgeos_is_achievement()
	 */
	public function test_badgeos_is_achievement() {

		// Setup an achievement type
		$achievement_type_post = $this->factory->post->create( array( 'post_title' => 'Trophy', 'post_type' => 'achievement-type' ) );
		badgeos_register_achievement_type_cpt();
		$achievement_id = $this->factory->post->create( array( 'post_type' => 'trophy' ) );

		$is_achievement = badgeos_is_achievement( $achievement_id );
		$this->assertTrue( $is_achievement );

	}

	/**
	 * @covers badgeos_is_achievement()
	 */
	public function test_badgeos_is_not_achievement() {
		$achievement_id = $this->factory->post->create();
		$is_achievement = badgeos_is_achievement( $achievement_id );
		$this->assertFalse( $is_achievement );
	}

	/**
	 * @covers badgeos_get_achievements()
	 */
	public function test_badgeos_get_achievements() {
		// $this->factory->post->create_many( 10, array( 'post_type' => 'achievement-type' ) );
		// badgeos_register_achievement_type_cpt();

		// $actual_achievements = get_posts( array( 'post_type' => 'achievement-type' ) );
		// $expected_achievements = badgeos_get_achievements();

		// $this->assertSame( $actual_achievements, $expected_achievements );
	}

	/**
	 * @covers badgeos_get_achievement_types()
	 */
	public function test_badgeos_get_achievement_types() {}

	/**
	 * @covers badgeos_get_achievement_types_slugs()
	 */
	public function test_badgeos_get_achievement_types_slugs() {}

	/**
	 * @covers badgeos_get_parent_of_achievement()
	 */
	public function test_badgeos_get_parent_of_achievement() {}

	/**
	 * @covers badgeos_get_children_of_achievement()
	 */
	public function test_badgeos_get_children_of_achievement() {}

	/**
	 * @covers badgeos_is_achievement_sequential()
	 */
	public function test_badgeos_is_achievement_sequential() {}

	/**
	 * @covers badgeos_achievement_user_exceeded_max_earnings()
	 */
	public function test_badgeos_achievement_user_exceeded_max_earnings() {}

	/**
	 * @covers badgeos_build_achievement_object()
	 */
	public function test_badgeos_build_achievement_object() {}

	/**
	 * @covers badgeos_get_hidden_achievement_ids()
	 */
	public function test_badgeos_get_hidden_achievement_ids() {}

	/**
	 * @covers badgeos_get_user_earned_achievement_ids()
	 */
	public function test_badgeos_get_user_earned_achievement_ids() {}

	/**
	 * @covers badgeos_get_user_earned_achievement_types()
	 */
	public function test_badgeos_get_user_earned_achievement_types() {}

	/**
	 * @covers badgeos_get_dependent_achievements()
	 */
	public function test_badgeos_get_dependent_achievements() {}

	/**
	 * @covers badgeos_get_required_achievements_for_achievement()
	 */
	public function test_badgeos_get_required_achievements_for_achievement() {}

	/**
	 * @covers badgeos_get_points_based_achievements()
	 */
	public function test_badgeos_get_points_based_achievements() {}

	/**
	 * @covers badgeos_bust_points_based_achievements_cache()
	 */
	public function test_badgeos_bust_points_based_achievements_cache() {}

	/**
	 * @covers badgeos_get_achievement_post_thumbnail()
	 */
	public function test_badgeos_get_achievement_post_thumbnail() {}

	/**
	 * @covers badgeos_get_achievement_earners()
	 */
	public function test_badgeos_get_achievement_earners() {}

	/**
	 * @covers badgeos_get_achievement_earners_list()
	 */
	public function test_badgeos_get_achievement_earners_list() {}

	/**
	 * @covers badgeos_ms_show_all_achievements()
	 */
	public function test_badgeos_ms_show_all_achievements() {}

	/**
	 * @covers badgeos_get_network_site_ids()
	 */
	public function test_badgeos_get_network_site_ids() {}

	/**
	 * @covers badgeos_achievement_set_default_thumbnail()
	 */
	public function test_badgeos_achievement_set_default_thumbnail() {}

}
