<?php
/**
 * Logging Functionality
 *
 * @package BadgeOS
 * @subpackage Logging
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

use BadgeOS\Log;

/**
 * Posts a log entry when a user unlocks any achievement post
 *
 * @since  1.0.0
 * @param  integer $object_id    The post id of the activity we're logging
 * @param  integer $user_id    The user ID
 * @param  string  $action     The action word to be used for the generated title
 * @param  string  $title      An optional default title for the log post
 * @return integer             The post ID of the newly created log entry
 */
function badgeos_post_log_entry( $object_id, $user_id = 0, $action = 'unlocked', $title = '' ) {
	// Get the current user if no ID specified
	if ( empty( $user_id ) )
		$user_id = get_current_user_id();

    // Load factory class and write log
    $badgeos_settings = get_option( 'badgeos_settings' );
    $log = new $badgeos_settings['log_factory']();
    return $log->write($title, array(
        'object_id' => $object_id,
        'user_id'   => $user_id,
        'action'    => $action,
    ));
}
