<?php

class BadgeOS_Points_Test extends WP_UnitTestCase {

	/**
	 * @covers badgeos_get_users_points()
	 */
	public function test_badgeos_get_users_points() {

		$actual_points = 250;

		$user_id = $this->factory->user->create();
		update_user_meta( $user_id, '_badgeos_points', $actual_points );
		$earned_points = badgeos_get_users_points( $user_id );

		$this->assertSame( $earned_points, $actual_points );
	}

	/**
	 * @covers badgeos_update_users_points()
	 */
	public function test_badgeos_update_users_points_increase() {

		$starting_points = 100;
		$additional_points = 150;
		$expected_points = 250;

		$user_id = $this->factory->user->create();
		update_user_meta( $user_id, '_badgeos_points', $starting_points );
		$actual_points = badgeos_update_users_points( $user_id, $additional_points );

		$this->assertSame( $actual_points, $expected_points );
	}

	/**
	 * @covers badgeos_update_users_points()
	 */
	public function test_badgeos_update_users_points_decrease() {

		$starting_points = 150;
		$additional_points = -100;
		$actual_points = 50;

		$user_id = $this->factory->user->create();
		update_user_meta( $user_id, '_badgeos_points', $starting_points );
		$new_total = badgeos_update_users_points( $user_id, $additional_points );

		$this->assertSame( $new_total, $actual_points );
	}

	/**
	 * @covers badgeos_update_users_points()
	 */
	public function test_badgeos_update_users_points_decrease_to_nonnegative() {

		$starting_points = 50;
		$additional_points = -100;
		$actual_points = 0;

		$user_id = $this->factory->user->create();
		update_user_meta( $user_id, '_badgeos_points', $starting_points );
		$new_total = badgeos_update_users_points( $user_id, $additional_points );

		$this->assertSame( $new_total, $actual_points );
	}

	/**
	 * @covers badgeos_update_users_points()
	 */
	public function test_badgeos_update_users_points_as_admin() {

		$starting_points = 150;
		$admin_points = 250;

		$user_id = $this->factory->user->create();
		update_user_meta( $user_id, '_badgeos_points', $starting_points );
		$new_total = badgeos_update_users_points( $user_id, $admin_points, 1 );

		$this->assertSame( $new_total, $admin_points );
	}

	/**
	 * @covers badgeos_award_user_points()
	 */
	public function test_badgeos_award_user_points() {

		$starting_points = 100;
		$achievement_points = 150;
		$actual_points = 250;

		$user_id = $this->factory->user->create();
		update_user_meta( $user_id, '_badgeos_points', $starting_points );

		$achievement_id = $this->factory->post->create();
		update_post_meta( $achievement_id, '_badgeos_points', $achievement_points );

		$new_total = badgeos_award_user_points( $user_id, $achievement_id );

		$this->assertSame( $new_total, $actual_points );
	}

}
