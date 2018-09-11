<?php

/**
 * Add Child step posts to trash
 *
 * @param $post_id
 */
function badgeos_before_trash_post( $post_id ) {
    global $post_type;
    $badge_post_types = badgeos_get_achievement_types_slugs();
    if ( in_array( $post_type, $badge_post_types ) ) {
        $children = badgeos_get_achievements( array( 'children_of' => $post_id, 'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash') ) );
        if( $children ) {
            foreach( $children as $child ) {
                $post = array( 'ID' => $child->ID, 'post_status' => 'trash' );
                wp_update_post($post);
            }
        }
    }
}
add_action( 'wp_trash_post','badgeos_before_trash_post' );

/**
 * Untrash child posts
 *
 * @param $post_id
 */
function badgeos_restore_posts_from_trash( $post_id ) {
    global $post_type;
    $badge_post_types = badgeos_get_achievement_types_slugs();
    if ( in_array( $post_type, $badge_post_types ) ) {
        $children = badgeos_get_achievements( array( 'children_of' => $post_id,'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash') ) );
        if( $children ) {
            foreach( $children as $child ) {
                $post = array( 'ID' => $child->ID, 'post_status' => 'publish' );
                wp_update_post($post);
            }
        }
    }
}
add_action('untrash_post', 'badgeos_restore_posts_from_trash');

/**
 * Delete child posts permanently
 *
 * @param $post_id
 */
function badgeos_delete_posts_permanently( $post_id ) {

    global $post_type;
    $badge_post_types = badgeos_get_achievement_types_slugs();
    if ( in_array( $post_type, $badge_post_types ) ) {
        $children = badgeos_get_achievements( array( 'children_of' => $post_id,'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash') ) );
        if( $children ) {
            foreach( $children as $child ) {
                wp_delete_post( absint($child->ID), true ) or die("Unable to delete");
            }
        }
    }
}
add_action( 'before_delete_post', 'badgeos_delete_posts_permanently' );