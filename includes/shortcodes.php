<?php
/**
 * Custom Shortcodes
 *
 * @package BadgeOS
 * @subpackage Front-end
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

include( plugin_dir_path( dirname( __FILE__ ) ) . 'includes/shortcodes/badgeos_achievements_list.php' );
include( plugin_dir_path( dirname( __FILE__ ) ) . 'includes/shortcodes/badgeos_achievement.php' );
include( plugin_dir_path( dirname( __FILE__ ) ) . 'includes/shortcodes/badgeos_user_earned_achievements.php' );
include( plugin_dir_path( dirname( __FILE__ ) ) . 'includes/shortcodes/badgeos_user_earned_ranks.php' );
include( plugin_dir_path( dirname( __FILE__ ) ) . 'includes/shortcodes/badgeos_user_earned_points.php' );
include( plugin_dir_path( dirname( __FILE__ ) ) . 'includes/shortcodes/badgeos_rank.php' );
include( plugin_dir_path( dirname( __FILE__ ) ) . 'includes/shortcodes/badgeos_ranks.php' );
include( plugin_dir_path( dirname( __FILE__ ) ) . 'includes/shortcodes/evidence-shortcode.php' );
/**
 * Register a new BadgeOS Shortcode
 *
 * @since  1.4.0
 *
 * @param  array  $args Shortcode Args.
 * @return object       Shortcode Object.
 */
function badgeos_register_shortcode( $args ) {
	return new BadgeOS_Shortcode( $args );
}

/**
 * Get all registered BadgeOS shortcodes.
 *
 * @since  1.4.0
 *
 * @return array Registered BadgeOS shortcodes.
 */
function badgeos_get_shortcodes() {
	return apply_filters( 'badgeos_shortcodes', array() );
}

/**
 * Add all shortcodes to the help page.
 *
 * @since 1.4.0
 */
function badgeos_help_support_page_shortcodes() {
	foreach ( badgeos_get_shortcodes() as $shortcode ) {
		badgeos_shortcode_help_render_help( $shortcode );
	}
}
add_action( 'badgeos_help_support_page_shortcodes', 'badgeos_help_support_page_shortcodes' );

/**
 * Render help section for a given shortcode.
 *
 * @since 1.4.0
 *
 * @param object $shortcode Shortcode object.
 */
function badgeos_shortcode_help_render_help( $shortcode = array() ) {
	printf(
		'
		<hr/>
		<h3>%1$s &ndash; [%2$s]</h3>
		<p>%3$s</p>
		<ul style="margin:1em 2em; padding:1em;">
		<li><strong>%4$s</strong></li>
		%5$s
		</ul>
		<p>%6$s</p>
		',
		$shortcode->name,
		$shortcode->slug,
		$shortcode->description,
		__( 'Attributes:', 'badgeos' ),
		badgeos_shortcode_help_render_attributes( $shortcode->attributes ),
		badgeos_shortcode_help_render_example( $shortcode )
	);
}

/**
 * Render attributes portion of shordcode help section.
 *
 * @since  1.4.0
 *
 * @param  array $attributes Shortcode attributes.
 * @return string            HTML Markup.
 */
function badgeos_shortcode_help_render_attributes( $attributes ) {
	$output = '';
	if ( ! empty( $attributes ) ) {
		foreach ( $attributes as $attribute => $details ) {
			$accepts = ! empty( $details['values'] ) ? sprintf( __( 'Accepts: %s', 'badgeos' ), '<code>' . implode( ', ', $details['values'] ) . '</code>' ) : '';
			$default = ! empty( $details['default'] ) ? sprintf( __( 'Default: %s', 'badgeos' ), '<code>' . $details['default'] . '</code>' ) : '';
			$output .= sprintf(
				'<li><strong>%1$s</strong> – %2$s <em>%3$s %4$s</em></li>',
				esc_attr( $attribute ),
				esc_html( $details['description'] ),
				$accepts,
				$default
			);

		}
	}
	return $output;
}

/**
 * Render example shortcode usage for help section.
 *
 * @since  1.4.0
 *
 * @param  array  $shortcode Shortcode object.
 * @return string            HTML Markup.
 */
function badgeos_shortcode_help_render_example( $shortcode = array() ) {
	$attributes = wp_list_pluck( $shortcode->attributes, 'default' );
	$examples = array_map( 'badgeos_shortcode_help_attributes', array_keys( $attributes ), array_values( $attributes ) );
	$flattened_examples = implode( ' ', $examples );
	return sprintf( __( 'Example: %s', 'badgeos' ), "<code>[{$shortcode->slug} {$flattened_examples}]</code>" );
}

/**
 * Render attribute="value" for attributes in shortcode example.
 *
 * @since  1.4.0
 *
 * @param  string $key   Key name.
 * @param  string $value Value.
 * @return string        key="value".
 */
function badgeos_shortcode_help_attributes( $key, $value ) {
	return "{$key}=\"$value\"";
}
