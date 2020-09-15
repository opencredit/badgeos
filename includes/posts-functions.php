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


/**
 * Helper function to get a post field.
 * Important: On network wide installs, this function will return the post field from main site, so use only for points, achievements and ranks posts fields
 *
 * @param string                $field      The post field.
 * @param integer|WP_Post|null  $post_id    Post ID.
 * @return false|string                     Post field on success, false on failure.
 */
function badgeos_get_post_field( $field, $post_id = null  ) {

    if ( empty( $post_id ) && isset( $GLOBALS['post'] ) )
        $post_id = $GLOBALS['post'];

    /**
     * if we got a post object, then return their field
     */
    if ( $post_id instanceof WP_Post ) {
        return $post_id->$field;
    } else if( is_object( $post_id ) ) {
        return $post_id->$field;
    }

    return get_post_field( $field, $post_id );
}

/**
 * Helper function to get a post.
 * Important: On network wide installs, this function will return the post from main site, so use only for points, achievements and ranks posts
 *
 * @param int    $post_id       Post ID.
 * @return false| WP_Post        Post object on success, false on failure.
 */
function badgeos_get_post( $post_id ) {

    /**
     * if we got a post object, then return their field
     */
    if ( $post_id instanceof WP_Post ) {
        return $post_id;
    }

    global $wpdb;
    return badgeos_utilities::badgeos_get_post( $post_id );
}