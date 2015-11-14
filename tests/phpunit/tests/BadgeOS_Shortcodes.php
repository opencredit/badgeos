<?php

class BadgeOS_Shortcodes_Test extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * @covers ::badgeos_shortcode_help_render_help()
	 */
	public function test_badgeos_shortcode_help_render_help() {
		/*$this->markTestIncomplete(
				'Needs further work with attributes and example.'
		);*/

		$shortcode = new stdClass();

		$shortcode->name = 'Test Shortcode';
		$shortcode->slug = 'test_shortcode';
		$shortcode->description = 'Test description';
		$shortcode->attributes = array( 'attr' => array( 'description' => 'Test attr description', 'values' => array( 'value_1' => 'Value 1', 'value_2' => 'Value 2' ), 'default' => 'value_1' ) );

		$expected =
			'<hr/>
			<h3>Test Shortcode &ndash; [test_shortcode]</h3><p>Test description</p><ul style="margin:1em 2em; padding:1em;"><li><strong>Attributes:</strong></li><li><strong>attr</strong> – Test attr description <em>Accepts: <code>Value 1, Value 2</code> Default: <code>value_1</code></em></li></ul><p>Example: <code>[test_shortcode attr="value_1"]</code></p>';

		ob_start();
		badgeos_shortcode_help_render_help( $shortcode );
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertHTMLstringsAreEqual( $expected, $actual );
	}

	/**
	 * @covers ::badgeos_shortcode_help_render_attributes()
	 */
	public function test_badgeos_shortcode_help_render_attributes() {}

	/**
	 * @covers ::badgeos_shortcode_help_render_example()
	 */
	public function test_badgeos_shortcode_help_render_example() {}

	/**
	 * @covers ::badgeos_shortcode_help_attributes()
	 */
	public function test_badgeos_shortcode_help_attributes() {}

	/**
	 * @covers ::badgeos_shortcode_submissions_handler()
	 */
	public function test_badgeos_shortcode_submissions_handler() {}
}
