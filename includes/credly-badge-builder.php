<?php
/**
 * Credly Badge Builder
 *
 * @package BadgeOS
 * @subpackage Credly
 * @author LearningTimes, LLC
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
	return $builder->render_link( $args );
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

	// Only add a link to achievement and achievement-type posts
	if ( badgeos_is_achievement( $post_id ) || 'achievement-type' == get_post_type( $post_id ) ) {
		// If no thumbnail, output standard badge builder link
		if ( ! has_post_thumbnail( $post_id ) ) {
			$content .= '<p>' . badgeos_get_badge_builder_link( array( 'post_id' => $post_id, 'link_text' => __( 'Use Credly Badge Builder', 'badgeos' ) ) ) . '</p>';
		// Otherwise, if thumbnail is a badge builder badge,`
		// output a "continue editing" link
		} else {
			$attachment_id = get_post_thumbnail_id( $post_id );
			$continue = get_post_meta( $attachment_id, '_credly_badge_meta', true );
			if ( $continue )
				$content .= '<p>' . badgeos_get_badge_builder_link( array( 'post_id' => $post_id, 'attachment_id' => $attachment_id, 'link_text' => __( 'Edit in Credly Badge Builder', 'badgeos' ), 'continue' => $continue ) ) . '</p>';
		}

	}

	// Return the meta box content
	return $content;

}
add_filter( 'admin_post_thumbnail_html', 'badgeos_badge_builder_filter_thumbnail_metabox', 10, 2 );

/**
 * Build icon credit text for Credly Badge Builder images
 *
 * @since  1.3.0
 *
 * @param  integer $attachment_id Image attachment ID.
 * @return string                 Icon credit text, empty if no credit.
 */
function badgeos_badge_builder_get_icon_credit( $attachment_id = 0 ) {

	// If image has badge builder icon meta
	if ( $icon_meta = get_post_meta( $attachment_id, '_credly_icon_meta', true ) ) {
		$icon_meta = (array) maybe_unserialize( $icon_meta );
		$credit = sprintf(
			__( 'Badge icon "%1$s (%2$s)" provided by %3$s under %4$s', 'badgeos' ),
			$icon_meta['noun'],
			$icon_meta['npid'],
			$icon_meta['attribute'],
			$icon_meta['license']
		);

	// Otherwise, there is no one to credit
	} else {
		$credit = '';
	}

	// Return the icon credit
	return $credit;
}
