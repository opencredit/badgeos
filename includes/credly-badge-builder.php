<?php
/**
 * Credly Badge Builder
 *
 * @package BadgeOS
 * @subpackage Credly
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Return link to Badge Builder.
 *
 * @since  1.3.0
 *
 * @param  integer $post_id Post ID.
 * @return string           Admin link to media manager.
 */
function badgeos_get_badge_builder_link( $post_id = 0 ) {
	return admin_url('media-upload.php?post_id=' . $post_id . '&type=image&TB_iframe=1' );
}

/**
 * Add Badge Builder link to Featured Image meta box for achievement posts.
 *
 * @since  1.3.0
 *
 * @param  string  $content Meta box content.
 * @param  integer $post_id Post ID.
 * @return string           Potentially updated content.
 */
function badgeos_post_thumbnail_html( $content, $post_id ) {

	// Add output only for achievement posts
	if ( badgeos_is_achievement( $post_id ) )
		$content .= '<p><a href="' . badgeos_get_badge_builder_link( $post_id ) . '" class="thickbox badge-builder">' . __( 'Use Credly Badge Builder', 'badgeos' ) . '</a></p>';

	// Return the meta box content
	return $content;

}
add_filter( 'admin_post_thumbnail_html', 'badgeos_post_thumbnail_html', 10, 2 );
