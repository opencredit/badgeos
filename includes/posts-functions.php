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

function badgeos_clone_entries_of_ranks_to_multisite_setup( $post_id, $post_object ) {
    global $post;
    global $wpdb;
    
    $badgeos_settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
    $rank_type = $badgeos_settings['ranks_main_post_type'];

    if ( ! empty( $post->ID ) ){
        $post_id = $post->ID;
    } else if ( isset( $_GET['post'] ) ){
        $post_id = $_GET['post'];
    }

    $get_post   = get_post( $post_id, ARRAY_A );                    // post object
    $post_type  = get_post_type( $post_id );                        // badgeos post type
    $post_title = get_the_title( $post_id );                        // post title
    $post_meta   = get_post_meta( $post_id );                       // post meta
    $all_network_ids = badgeos_get_network_site_ids();              // multisite network ids
    $current_blog_id = get_current_blog_id();                       // current blog id

    // update network ids array with blog ids where data will be copied
    // avoid multiple copies of post on same site
    if ( ($key = array_search( $current_blog_id, $all_network_ids ) ) !== false ) {
        unset( $all_network_ids[$key] );
    }

    // check if we are copying badges post only 
    if ( in_array( $post_type, badgeos_get_rank_types_slugs() ) ||
        in_array( $post_type, array( $rank_type ) ) ){
        foreach ( $all_network_ids as $id ) {
            switch_to_blog( $id );
            if ( ! post_exists( $post_title ) ) {
                $get_post['ID'] = ''; // empty id field, to tell wordpress that this will be a new post
                $get_post['post_status'] = 'publish'; // empty id field, to tell wordpress that this will be a new post
                $inserted_post_id = wp_insert_post( $get_post ); // insert the post
                update_post_meta($inserted_post_id,'post_meta', $post_meta);
                foreach ( $post_meta as $key => $value ) {
                    update_post_meta( $inserted_post_id, $key, $value[0] );
                }
                update_post_meta( $inserted_post_id, '_badgeos_show_in_menu', 'on'); 
            }
            restore_current_blog();
        }   
    }
    
    // switch to current blog
    switch_to_blog( $current_blog_id );
}
add_action( 'save_post', 'badgeos_clone_entries_of_ranks_to_multisite_setup', 15, 2 );