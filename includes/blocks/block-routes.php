<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the rest api routes
 *
 * @param $categories
 * @param $post
 *
 * @return none
 */
function badgeos_register_api_end_points() {

    register_rest_route( 'badgeos', '/block-point-types', array(
      'methods' => 'GET', 
      'callback' => 'badgeos_block_point_types_list',
      'permission_callback' => '__return_true',
    ) ); 

    register_rest_route( 'badgeos', '/block-achievements-award-list/(?P<achievement>[a-zA-Z0-9_-]+)/(?P<user_id>[a-zA-Z0-9_-]+)', array(
      'methods' => 'GET',
      'callback' => 'badgeos_block_achievements_award_list',
      'permission_callback' => '__return_true',
    ) );

    register_rest_route( 'badgeos', '/ranks', array(
      'methods' => 'GET',
      'callback' => 'badgeos_block_ranks_list',
      'permission_callback' => '__return_true',
    ) );

    register_rest_route( 'badgeos', '/achievement-types', array(
      'methods' => 'GET',
      'callback' => 'badgeos_achievement_types_list',
      'permission_callback' => '__return_true',
    ) );

    register_rest_route( 'badgeos', '/achievements', array(
        'methods' => 'GET',
        'callback' => 'badgeos_achievements_list',
        'permission_callback' => '__return_true',
    ) );

    register_rest_route( 'badgeos', '/rank-types', array(
      'methods' => 'GET',
      'callback' => 'badgeos_ranks_list',
      'permission_callback' => '__return_true',
    ));

    register_rest_route( 'badgeos', '/user-lists', array(
      'methods' => 'GET',
      'callback' => 'badgeos_users_list_block',
      'permission_callback' => '__return_true',
    ));
}
add_action( 'rest_api_init', 'badgeos_register_api_end_points' );

/**
 * Returns the list of achievements award.
 *
 * @return $posts
 */
function badgeos_block_achievements_award_list($request) {


  global $wpdb;

  $badgeos_settings   = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
  $achievement_id     = sanitize_text_field( $request['achievement'] );
  $user_id            = sanitize_text_field( $request['user_id'] );
  $q                  = sanitize_text_field( $request['q'] );

  $sql = "SELECT entry_id as value, CONCAT(achievement_title, ' : ', entry_id) as label, entry_id as value FROM ".$wpdb->prefix."badgeos_achievements where post_type!='".$badgeos_settings['achievement_step_post_type']."'";

  // Build our query
  if ( !empty( $q ) ) {
      $sql .= " and achievement_title LIKE '%". $q."%'";
  }

  // Build our query
  if ( !empty( $achievement_id ) && intval( $achievement_id ) ) {
      $sql .= " and ID = '". $achievement_id."'";
  }
  
  // Build our query
  if ( ! empty( $user_id ) && intval( $user_id ) ) {
      $sql .= " and user_id = '". $user_id."'";
  }

  // Fetch our results (store as associative array)
  $results = $wpdb->get_results( $sql, 'ARRAY_A' );

  // Return our results
  wp_send_json( $results );
}

/**
 * Returns the list of ranks..
 *
 * @return $posts
 */
function badgeos_block_ranks_list( $data ) {
  
  $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
  $rank_types = get_posts( array(
      'post_type'      =>	$badgeos_settings['ranks_main_post_type'],
      'posts_per_page' =>	-1,
  ) );

  $ranks = array();
  foreach( $rank_types as $rtype ) {
      $records = get_posts( array(
          'post_type'      =>	$rtype->post_name,
          'posts_per_page' =>	-1,
      ) );

      foreach( $records as $record ) {
          $ranks[] = [ 'value' => $record->ID, 'label' => $record->post_title ];
      }
  }

 	return $ranks;
}

/**
 * Returns the list of ranks..
 *
 * @return $posts
 */
function badgeos_users_list_block( $data ) {
  
  $users = get_users();
  $userList = array();

  foreach( $users as $user ) {
      $userList[] = [ 'value' => $user->ID, 'label' => $user->user_login ];
  }

 	return $userList;
}

/**
 * Returns the list of ranks..
 *
 * @return $posts
 */
function badgeos_ranks_list( $data ) {
  
  $badgeos_settings   = get_option( 'badgeos_settings' );
  $rank_types  = get_posts( array(
      'post_type'      =>	$badgeos_settings['ranks_main_post_type'],
      'posts_per_page' =>	-1,
  ) );
  
  // Get our achievement posts
  $posts = array();
  foreach( $rank_types as $rank ) {
    $posts[] = [ 'value'=> $rank->post_name, 'label'=>$rank->post_title];
  }

 	return $posts;
}

/**
 * Returns the list of achievements..
 *
 * @return $posts
 */
function badgeos_block_point_types_list( $data ) {
  
  $badgeos_settings   = get_option( 'badgeos_settings' );
  $achievement_types  = get_posts( array(
      'post_type'      =>	$badgeos_settings['points_main_post_type'],
      'posts_per_page' =>	-1,
  ) );
  
  // Get our achievement posts
  $posts = array();
  foreach( $achievement_types as $achievement ) {
    $posts[] = [ 'value'=> $achievement->post_name, 'label'=>$achievement->post_title];
  }

 	return $posts;
}

/**
 * Returns the list of achievements..
 *
 * @return $posts
 */
function badgeos_achievements_list( $data ) {


  $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
  $types = badgeos_get_achievement_types_slugs();
  $step_key = array_search( trim( $badgeos_settings['achievement_step_post_type'] ), $types );
  if ( $step_key )
      unset( $types[$step_key] );

	$args = array(
        'post_type'                => $types,
        'suppress_filters'         => false,
        'achievement_relationsihp' => 'any',
        'posts_per_page'          =>	-1,
        'post_status'             => 'publish'
    );
   
  // Get our achievement posts
  $achievements = get_posts( $args );
  $posts = array();
  foreach( $achievements as $achievement ) {
    $posts[] = [ 'value'=> $achievement->ID, 'label'=>$achievement->post_title];
  }

 	return $posts;
}

/**
 * Returns the list of achievements..
 *
 * @return $posts
 */
function badgeos_achievement_types_list( $data ) {

  $badgeos_settings   = get_option( 'badgeos_settings' );
  $achievement_types  = get_posts( array(
      'post_type'      =>	$badgeos_settings['achievement_main_post_type'],
      'posts_per_page' =>	-1,
  ) );
  
  // Get our achievement posts
  $posts = array();
  foreach( $achievement_types as $achievement ) {
    $posts[] = [ 'value'=> $achievement->post_name, 'label'=>$achievement->post_title];
  }

 	return $posts;
}