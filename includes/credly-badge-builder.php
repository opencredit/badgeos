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
function badgeos_get_badge_builder_link( $args ) {
	$builder = new Credly_Badge_Builder();
	return $builder->render_badge_builder_link( $args );
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
function badgeos_badge_builder_filter_thumbnail_metabox( $content, $post_id ) {

	// Add output only for achievement posts that have no thumbnails
	if ( badgeos_is_achievement( $post_id ) ) {
		if ( ! has_post_thumbnail( $post_id ) ) {
			$content .= '<p>' . badgeos_get_badge_builder_link( array( 'link_text' => __( 'Use Credly Badge Builder', 'badgeos' ) ) ) . '</p>';
		} else {
			$attachment_id = get_post_thumbnail_id( $post_id );
			$continue = get_post_meta( $attachment_id, '_credly_badge_meta', true );
			$content .= '<p>' . badgeos_get_badge_builder_link( array( 'link_text' => __( 'Edit in Credly Badge Builder', 'badgeos' ), 'continue' => $continue ) ) . '</p>';
		}

	}

	// Return the meta box content
	return $content;

}
add_filter( 'admin_post_thumbnail_html', 'badgeos_badge_builder_filter_thumbnail_metabox', 10, 2 );
