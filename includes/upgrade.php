<?php
/**
 * BadgeOS Core Updates
 *
 * @package BadgeOS
 * @subpackage Admin
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * INTERNAL: Set new upgrade version.
 *
 * @since alpha
 *
 * @param string $version Version number.
 */
function _badgeos_set_upgrade_version( $version = '' ) {
	update_option( 'badgeos_upgrade_version', absint( $version ) );
}

/**
 * INTERNAL: Get current upgrade version.
 *
 * @since  alpha
 *
 * @return integer Version number.
 */
function _badgeos_get_upgrade_version() {
	return (int) get_option( 'badgeos_upgrade_version' );
}

/**
 * Upgrade BadgeOS data to support the latest changesets.
 *
 * @since alpha
 */
function badgeos_core_upgrade() {

	$version = (int) _badgeos_get_upgrade_version();

	if ( $version < 1300 ) {
		badgeos_upgrade_1300();
	}

}
// add_action( 'admin_init', 'badgeos_core_upgrade', 20 );

/**
 * Delete all orphaned steps.
 *
 * @since alpha
 */
function badgeos_upgrade_1300() {

	$steps = get_posts( array(
		'post_type'      => 'step',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	) );

	foreach ( $steps as $step_id ) {
		$parent_achievement = badgeos_get_parent_of_achievement( $step_id );

		// Delete IF the step has no parent, or parent isn't earned by steps
		if ( ! isset( $parent_achievement->ID ) || 'triggers' !== get_post_meta( $parent_achievement->ID , '_badgeos_earned_by', true ) ) {
			// Make sure post is still a step, juuuuust in case
			if ( 'step' == get_post_type( $step_id ) ) {
				wp_delete_post( $step_id, true );
			}
		}
	}

	_badgeos_set_upgrade_version( '1300' );
}
