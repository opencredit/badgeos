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
include( plugin_dir_path( dirname( __FILE__ ) ) . 'includes/shortcodes/badgeos_nomination.php' );
include( plugin_dir_path( dirname( __FILE__ ) ) . 'includes/shortcodes/badgeos_nominations.php' );
include( plugin_dir_path( dirname( __FILE__ ) ) . 'includes/shortcodes/badgeos_submission.php' );
include( plugin_dir_path( dirname( __FILE__ ) ) . 'includes/shortcodes/badgeos_submissions.php' );
include( plugin_dir_path( dirname( __FILE__ ) ) . 'includes/shortcodes/credly_assertion_page.php' );

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


/**
 * Handle Submissions/Nominations shortcode output
 *
 * @since  1.4.0
 *
 * @param array $atts Array of shortcode attributes.
 * @param string $type Submission type.
 * @param string $shortcode Shortcode name.
 * @param array $defaults_override Array of overrides to set for default attributes.
 *
 * @return string
 */
function badgeos_shortcode_submissions_handler( $atts = array(), $type = 'submission', $shortcode = '', $defaults_override = array() ) {

	// Setup defaults and allow override
	$defaults = array_merge(
		array(
			'type'             => $type,
			'limit'            => '10',
			'status'           => 'all',
			'show_filter'      => true,
			'show_search'      => true,
			'show_attachments' => true,
			'show_comments'    => true,
			'wpms'             => false
		),
		$defaults_override
	);

	// Parse shortcode attributes
	$atts = shortcode_atts( $defaults, $atts, $shortcode );

	// Get initial feedback HTML
	$feedback_html = badgeos_render_feedback( $atts );

	// Setup $badgeos_feedback array
	$badgeos_feedback = $atts;

	// Set current user ID
	$badgeos_feedback[ 'user_id' ] = get_current_user_id();

	// AJAX url
	$badgeos_feedback[ 'ajax_url' ] = esc_url( admin_url( 'admin-ajax.php', 'relative' ) );

	// AJAX data
	$badgeos_feedback[ 'ajax_data' ] = array(
		'action'           => 'get-feedback',
		'type'             => $badgeos_feedback[ 'type' ],
		'limit'            => $badgeos_feedback[ 'limit' ],
		'show_attachments' => $badgeos_feedback[ 'show_attachments' ],
		'show_comments'    => $badgeos_feedback[ 'show_comments' ],
		'wpms'             => $badgeos_feedback[ 'wpms' ],
		'user_id'          => $badgeos_feedback[ 'user_id' ]
	);

	// Filter fields to get values from form
	$badgeos_feedback[ 'filters' ] = array(
		'status' => '',
		'search' => ''
	);

	if ( $badgeos_feedback[ 'show_filter' ] ) {
		$badgeos_feedback[ 'filters' ][ 'status' ] = '.badgeos-feedback-filter select';
	}

	if ( $badgeos_feedback[ 'show_search' ] ) {
		$badgeos_feedback[ 'filters' ][ 'search' ] = '.badgeos-feedback-search-input';
	}

	/**
	 * Filter $badgeos_feedback to allow for overrides processed by JavaScript.
	 *
	 * @since 1.4.0
	 *
	 * @param array $badgeos_feedback {
	 *     Array of feedback options sent to JavaScript.
	 *
	 *     @type string $action AJAX action to use.
	 *     @type string $type Type of submission to use (submission/notification/etc).
	 *     @type int $limit Number of items to show.
	 *     @type boolean $show_attachments Show attachments in the list.
	 *     @type boolean $show_comments Show comments in the list.
	 *     @type boolean $wpms Show feedback from other sites in the list.
	 *     @type int $user_id User ID to base the list off of.
	 *     @type array $filters List of filter names and selectors to send in request.
	 * }
	 * @param array $atts {
	 *     Array of shortcode attributes that were used.
	 *
	 *     @type string $type Type of submission to use (submission/notification/etc).
	 *     @type int $limit Number of items to show.
	 *     @type boolean $show_filter Show filter in the list.
	 *     @type boolean $show_search Show search in the list.
	 *     @type boolean $show_attachments Show attachments in the list.
	 *     @type boolean $show_comments Show comments in the list.
	 *     @type boolean $wpms Show feedback from other sites in the list.
	 * }
	 * @param string $shortcode Which shortcode called the Feedback AJAX functionality.
	 */
	$badgeos_feedback = apply_filters( 'badgeos_feedback_ajax_js', $badgeos_feedback, $atts, $shortcode );

	// Enqueue and localize our JS
	wp_enqueue_script( 'badgeos-achievements' );
	wp_localize_script( 'badgeos-achievements', 'badgeos_feedback', $badgeos_feedback );

	// Return initial feedback HTML
	return $feedback_html;

}