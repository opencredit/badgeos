<?php

class BadgeOS_User_Functions_Test extends WP_UnitTestCase {

	/**
	 * @covers badgeos_get_user_achievements()
	 */
	public function test_badgeos_get_user_achievements() {
		//Need to set up some data.

		$user_id = $this->factory->user->create();
		$updated = badgeos_update_user_achievements( array( $user_id, ));
		$this->assertNotEmpty( $updated );
	}

	/**
	 * @covers badgeos_update_user_achievements()
	 */
	public function test_badgeos_update_user_achievements() {
		//Need to set up an achievement here

		$user_id = $this->factory->user->create();
		$updated = badgeos_update_user_achievements( array( $user_id, ));
		$this->assertNotEmpty( $updated );
	}

	/**
	 * @covers badgeos_process_user_data()
	 */
	public function test_badgeos_process_user_data() {}

	/**
	 * @covers badgeos_get_network_achievement_types_for_user()
	 */
	public function test_badgeos_get_network_achievement_types_for_user() {}

}
