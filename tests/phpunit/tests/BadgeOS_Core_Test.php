<?php

class BadgeOS_Core_Test extends WP_UnitTestCase {

	public function test_badgeos_has_version_number() {
		$this->assertNotNull( BadgeOS::$version );
	}

	/**
	 * @covers BadgeOS::includes
	 */
	public function test_badgeos_includes_exist() {

		$this->assertFileExists( BOS_DIRECTORY_PATH . 'includes/class.BadgeOS_Plugin_Updater.php' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'includes/class.Credly_Badge_Builder.php' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'includes/post-types.php' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'includes/admin-settings.php' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'includes/achievement-functions.php' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'includes/activity-functions.php' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'includes/ajax-functions.php' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'includes/logging-functions.php' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'includes/meta-boxes.php' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'includes/points-functions.php' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'includes/triggers.php' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'includes/steps-ui.php' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'includes/shortcodes.php' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'includes/content-filters.php' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'includes/submission-actions.php' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'includes/rules-engine.php' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'includes/user.php' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'includes/credly.php' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'includes/credly-badge-builder.php' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'includes/widgets.php' );

	}

	/**
	 * @covers BadgeOS::register_scripts_and_styles
	 */
	public function test_badgesos_assets_exist() {
		// CSS
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'css/admin.css' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'css/badgeos-front.css' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'css/badgeos-single.css' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'css/badgeos-widgets.css' );

		// JS
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'js/admin.js' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'js/badgeos-achievements.js' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'js/credly-badge-builder.js' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'js/credly.js' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'js/steps-ui.js' );

		// Images
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'images/arrows.png' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'images/badge-builder-teaser.png' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'images/badgeos_icon.png' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'images/badgeos_screen_icon.png' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'images/credly-credit-issuer.png' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'images/spinner.gif' );
		$this->assertFileExists( BOS_DIRECTORY_PATH . 'images/ui_handle.png' );
	}

	/**
	 * @covers BadgeOS::credly_init
	 */
	public function test_badgeos_initialized_credly() {
		$this->assertInstanceOf( 'BadgeOS_Credly', $GLOBALS['badgeos_credly'] );
	}

	/**
	 * @covers badgeos_get_directory_path()
	 */
	public function test_badgeos_get_directory_path_matches() {
		$this->assertSame( BOS_DIRECTORY_PATH, badgeos_get_directory_path() );
	}

	/**
	 * @covers badgeos_get_directory_url()
	 */
	public function test_badgeos_get_directory_url_matches() {
		$this->assertSame( plugins_url( BOS_DIRECTORY_PATH ), badgeos_get_directory_url() );
	}

	/**
	 * @covers badgeos_is_debug_mode()
	 */
	public function test_badgeos_is_debug_mode_true() {

		// Set debug mode to true
		$settings = get_option( 'badgeos_settings' );
		$settings['debug_mode'] = true;
		update_option( 'badgeos_settings', $settings );

		$this->assertTrue( badgeos_is_debug_mode() );
	}

	/**
	 * @covers badgeos_is_debug_mode()
	 */
	public function test_badgeos_is_debug_mode_false() {

		// Set debug mode to false
		$settings = get_option( 'badgeos_settings' );
		$settings['debug_mode'] = false;
		update_option( 'badgeos_settings', $settings );

		$this->assertFalse( badgeos_is_debug_mode() );
	}

}
