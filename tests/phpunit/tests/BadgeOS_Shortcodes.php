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

	public function assertHTMLstringsAreEqual( $expected_string, $string_to_test ) {
		$expected_string = $this->normalize_string( $expected_string );
		$string_to_test  = $this->normalize_string( $string_to_test );
		$compare         = strcmp( $expected_string, $string_to_test );
		if ( 0 !== $compare ) {
			$compare       = strspn( $expected_string ^ $string_to_test, "\0" );
			$chars_to_show = 50;
			$start         = ( $compare - 5 );
			$pointer       = '|--->>';
			$sep           = "\n" . str_repeat( '-', 75 );
			$compare       = sprintf(
					$sep . "\nFirst difference at position %d:\n\n  Expected: \t%s\n  Actual: \t%s\n" . $sep,
					$compare,
					substr( $expected_string, $start, 5 ) . $pointer . substr( $expected_string, $compare, $chars_to_show ),
					substr( $string_to_test, $start, 5 ) . $pointer . substr( $string_to_test, $compare, $chars_to_show )
			);
		}

		return $this->assertEquals( $expected_string, $string_to_test, ! empty( $compare ) ? $compare : null );
	}

	public function normalize_string( $string ) {
		return trim( preg_replace( array(
				'/[\t\n\r]/', // Remove tabs and newlines
				'/\s{2,}/', // Replace repeating spaces with one space
				'/> </', // Remove spaces between carats
		), array(
				'',
				' ',
				'><',
		), $string ) );
	}
}
