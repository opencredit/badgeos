<?php
/**
 * Custom Shortcodes
 *
 * @package BadgeOS
 * @subpackage Front-end
 * @author Credly, LLC
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
 * Add help content for [badgeos_achievements_list] to BadgeOS Help page
 *
 * @since  1.2.0
 */
function badgeos_achievements_list_shortcode_help() { ?>
	<hr/>
	<p><strong>[badgeos_achievements_list]</strong> - <?php _e( 'Output a list of achievements of any type on any post or page.', 'badgeos' ); ?></p>
	<div style="padding-left:15px;">
		<ul>
			<li><strong><?php _e( 'Parameters', 'badgeos' ); ?></strong></li>
			<li>
				<div style="padding-left:15px;">
				<ul>
					<li>type - <?php printf( __( 'Type of achievements to list. Accepts: %1$s Default: %2$s', 'badgeos' ), '<code>"all", comma-separated list of achievement types (e.g. "badge,level,trophy"), individual achievement type (e.g. "badge")</code>', '<code>all</code>' ); ?></li>
					<li>limit - <?php printf( __( 'Number of achievements to display per page. Default: %s', 'badgeos' ), '<code>10</code>' ); ?></li>
					<li>show_filter - <?php printf( __( 'Display the filter options. Accepts: %1$s Default: %2$s', 'badgeos' ), '<code>true, false</code>', '<code>true</code>' ); ?></li>
					<li>show_search - <?php printf( __( 'Display the search form. Accepts: %1$s Default: %2$s', 'badgeos' ), '<code>true, false</code>', '<code>true</code>' ); ?></li>
					<li>wpms - <?php printf( __( 'Displays achievements of the same type from across a multisite network if multisite is enabled and a super admin enables network achievements. Accepts: %1$s Default: %2$s', 'badgeos' ), '<code>true, false</code>', '<code>false</code>' ); ?></li>
					<li>orderby - <?php printf( __( 'Specify how to order achievements. Accepts: %1$s Default: %2$s', 'badgeos' ), '<a href="http://codex.wordpress.org/Class_Reference/WP_Query#Order_.26_Orderby_Parameters" target="_blank">See WP_Query orderby parameters</a>', '<code>menu_order</code>' ); ?></li>
					<li>order - <?php printf( __( 'Specify the direction to order achievements. Accepts: %1$s Default: %2$s', 'badgeos' ), '<code>ASC, DESC</code>', '<code>ASC</code>' ); ?></li>
					<li>include - <?php __( 'Specify a comma-separated list of achievement IDs to include.', 'badgeos' ); ?></li>
					<li>exclude - <?php __( 'Specify a comma-separated list of achievement IDs to exclude.', 'badgeos' ); ?></li>
					<li>meta_key - <?php __( 'Specify a Custom Field meta_key to filter by. Requires a meta_value to be set.', 'badgeos' ); ?></li>
					<li>meta_value - <?php __( 'Specify a Custom Field meta_value to filter by. Requires a meta_key to be set.', 'badgeos' ); ?></li>
				</ul>
				</div>
			</li>
			<li><strong><?php _e( 'Example', 'badgeos' ); ?>:</strong> <code>[badgeos_achievements_list type="badges" limit="15"]</code></li>
		</ul>
	</div>
<?php }
add_action( 'badgeos_help_support_page_shortcodes', 'badgeos_achievements_list_shortcode_help' );

/**
 * Add help content for [badgeos_user_achievements] to BadgeOS Help page.
 *
 * @since  1.3.4
 */
function badgeos_user_achievements_shortcode_help() { ?>
	<hr/>
	<p><strong>[badgeos_user_achievements]</strong> - <?php _e( 'Display a list of achievements by any user on any post or page.', 'badgeos' ); ?></p>
	<div style="padding-left:15px;">
		<ul>
			<li><strong><?php _e( 'Parameters', 'badgeos' ); ?></strong></li>
			<li>
				<div style="padding-left:15px;">
					<ul>
						<li><?php _e( 'user', 'badgeos' ); ?> - <?php _e( 'The ID of the user to display.', 'badgeos' ); ?></li>
						<li><?php _e( 'type', 'badgeos' ); ?> - <?php _e( 'The achievement type to display from the user.', 'badgeos' ); ?></li>
						<li><?php _e( 'limit', 'badgeos' ); ?> - <?php _e( 'The maximum amount of achievements to display.', 'badgeos' ); ?></li>
					</ul>
				</div>
			</li>
			<li><strong><?php _e( 'Example', 'badgeos' ); ?>:</strong> <code>[badgeos_user_achievements user="1" type="badge" limit="5"]</code></li>
		</ul>
	</div>
<?php }
add_action( 'badgeos_help_support_page_shortcodes', 'badgeos_user_achievements_shortcode_help' );

/**
 * Add help content for [badgeos_achievement] to BadgeOS Help page
 *
 * @since  1.2.0
 */
function badgeos_achievement_shortcode_help() { ?>
	<hr/>
	<p><strong>[badgeos_achievement]</strong> - <?php _e( 'Display a single achievement on any post or page.', 'badgeos' ); ?></p>
	<div style="padding-left:15px;">
		<ul>
			<li><strong><?php _e( 'Parameters', 'badgeos' ); ?></strong></li>
			<li>
				<div style="padding-left:15px;">
				<ul>
					<li><?php _e( 'id', 'badgeos' ); ?> - <?php _e( 'The ID of the achievement to display.', 'badgeos' ); ?></li>
				</ul>
				</div>
			</li>
			<li><strong><?php _e( 'Example', 'badgeos' ); ?>:</strong> <code>[badgeos_achievement id="12"]</code></li>
		</ul>
	</div>
<?php }
add_action( 'badgeos_help_support_page_shortcodes', 'badgeos_achievement_shortcode_help' );

/**
 * Add help content for [badgeos_nomination] to BadgeOS Help page
 *
 * @since  1.2.0
 */
function badgeos_nomination_form_shortcode_help() { ?>
	<hr/>
	<p><strong>[badgeos_nomination]</strong> - <?php _e( 'Display nominations or nomination form for a given achievement. <strong>Note:</strong> Achievements will automatically display this on their single page if <strong>Earned By</strong> is set to <strong>Nomination</strong>.', 'badgeos' ); ?></p>
	<div style="padding-left:15px;">
		<ul>
			<li><strong><?php _e( 'Parameters', 'badgeos' ); ?></strong></li>
			<li>
				<div style="padding-left:15px;">
				<ul>
					<li>achievement_id - <?php _e( 'The ID of the achievement to be awarded.  Default: current post ID', 'badgeos' ); ?></li>
				</ul>
				</div>
			</li>
			<li><strong><?php _e( 'Example', 'badgeos' ); ?>:</strong> <code>[badgeos_nomination achievement_id="35"]</code></li>
		</ul>
	</div>
<?php }
add_action( 'badgeos_help_support_page_shortcodes', 'badgeos_nomination_form_shortcode_help' );

/**
 * Add help content for [badgeos_submission] to BadgeOS Help page
 *
 * @since  1.2.0
 */
function badgeos_submission_form_shortcode_help() { ?>
	<hr/>
	<p><strong>[badgeos_submission]</strong> - <?php _e( 'Display submissions or submission form for a given achievement. <strong>Note:</strong> Achievements will automatically display this on their single page if <strong>Earned By</strong> is set to <strong>Submission</strong>.', 'badgeos' ); ?></p>
	<div style="padding-left:15px;">
		<ul>
			<li><strong><?php _e( 'Parameters', 'badgeos' ); ?></strong></li>
			<li>
				<div style="padding-left:15px;">
				<ul>
					<li>achievement_id - <?php _e( 'The ID of the achievement to be awarded.  Default: current post ID', 'badgeos' ); ?></li>
				</ul>
				</div>
			</li>
			<li><strong><?php _e( 'Example', 'badgeos' ); ?>:</strong> <code>[badgeos_submission achievement_id="35"]</code></li>
		</ul>
	</div>
<?php }
add_action( 'badgeos_help_support_page_shortcodes', 'badgeos_submission_form_shortcode_help' );

/**
 * Add help content for [badgeos_submissions] to BadgeOS Help page
 *
 * @since  1.2.0
 */
function badgeos_submissions_shortcode_help() { ?>
	<hr/>
	<p><strong>[badgeos_submissions]</strong> - <?php _e( 'Generate a list of submissions on any post or page.', 'badgeos' ); ?></p>
	<div style="padding-left:15px;">
		<ul>
			<li><strong><?php _e( 'Parameters', 'badgeos' ); ?></strong></li>
			<li>
				<div style="padding-left:15px;">
				<ul>
					<li>limit - <?php printf( __( 'Number of submissions to display per page. Default: %1$s', 'badgeos' ), '<code>10</code>' ); ?></li>
					<li>status - <?php printf( __( 'Which Approval Status type to show on initial page load. Accepts: %1$s  Default: %2$s', 'badgeos' ), '<code>all, pending, auto-approved, approved, denied</code>', '<code>all</code>' ); ?></li>
					<li>show_filter - <?php printf( __( 'Display the filter select input. Accepts: %1$s  Default: %2$s', 'badgeos' ), '<code>true, false</code>', '<code>true</code>' ); ?></li>
					<li>show_search - <?php printf( __( 'Display the search form. Accepts: %1$s  Default: %2$s', 'badgeos' ), '<code>true, false</code>', '<code>true</code>' ); ?></li>
					<li>show_attachments - <?php printf( __( 'Display attachments connected to the submission. Accepts: %1$s  Default: %2$s', 'badgeos' ), '<code>true, false</code>', '<code>true</code>' ); ?></li>
					<li>show_comments - <?php printf( __( 'Display comments associated with the submission. Accepts: %1$s  Default: %2$s', 'badgeos' ), '<code>true, false</code>', '<code>true</code>' ); ?></li>
				</ul>
				</div>
			</li>
			<li><strong><?php _e( 'Example', 'badgeos' ); ?>:</strong> <?php printf( __( 'To show 15 pending submissions, %s', 'badgeos' ), '<code>[badgeos_submissions status="pending" limit="15"]</code>' ); ?></li>
		</ul>
	</div>
<?php }
add_action( 'badgeos_help_support_page_shortcodes', 'badgeos_submissions_shortcode_help' );

/**
 * Add help content for [badgeos_nominations] to BadgeOS Help page
 *
 * @since  1.2.0
 */
function badgeos_nominations_shortcode_help() { ?>
	<hr/>
	<p><strong>[badgeos_nominations]</strong> - <?php _e( 'Generate a list of nominations on any post or page.', 'badgeos' ); ?></p>
	<div style="padding-left:15px;">
		<ul>
			<li><strong><?php _e( 'Parameters', 'badgeos' ); ?></strong></li>
			<li>
				<div style="padding-left:15px;">
				<ul>
					<li>limit - <?php printf( __( 'Number of nominations to display per page. Default: %1$s', 'badgeos' ), '<code>10</code>' ); ?></li>
					<li>status - <?php printf( __( 'Which Approval Status type to show on initial page load. Accepts: %1$s  Default: %2$s', 'badgeos' ), '<code>all, pending, approved, denied</code>', '<code>all</code>' ); ?></li>
					<li>show_filter - <?php printf( __( 'Display the filter select input. Accepts: %1$s  Default: %2$s', 'badgeos' ), '<code>true, false</code>', '<code>true</code>' ); ?></li>
					<li>show_search - <?php printf( __( 'Display the search form. Accepts: %1$s  Default: %2$s', 'badgeos' ), '<code>true, false</code>', '<code>true</code>' ); ?></li>
				</ul>
				</div>
			</li>
			<li><strong><?php _e( 'Example', 'badgeos' ); ?>:</strong> <?php printf( __( 'To display 20 nominations and no search form, %s', 'badgeos' ), '<code>[badgeos_nominations show_search="false" limit="20"]</code>' ); ?></li>
		</ul>
	</div>
<?php }
add_action( 'badgeos_help_support_page_shortcodes', 'badgeos_nominations_shortcode_help' );

/**
 * Add help content for [credly_assertion_page] to BadgeOS Help page
 *
 * @since  1.3.0
 */
function badgeos_credly_assertion_page_help() { ?>
	<hr/>
	<p><strong>[credly_assertion_page]</strong> - <?php _e( 'Adds WordPress support for the Credly "Custom Assertion Location" feature – a setting available to Credly Pro members – to dynamically display official Credly badge information directly within your site.', 'badgeos' ); ?></p>
	<p><?php printf( __( 'Once you\'ve placed this shortcode on a page, copy that page\'s URL and append "?CID={id}" to the end (e.g. %1$s). Paste this full URL in the "Custom Assertion Location" field in your Credly Account Settings. All of your Credly badges will be linked back to this site where the official badge information is displayed automatically.', 'badgeos' ), site_url( '/assertion/?CID={id}' ) ); ?></p>
<?php }
add_action( 'badgeos_help_support_page_shortcodes', 'badgeos_credly_assertion_page_help' );
