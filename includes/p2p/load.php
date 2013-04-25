<?php
/*
Plugin Name: P2P Bundle Example
Author: scribu
*/

require dirname( __FILE__ ) . '/scb/load.php';

scb_init( '_badgeos_p2p_load' );

function _badgeos_p2p_load() {
	add_action( 'plugins_loaded', '_badgeos_load_p2p_core', 20 );
}

function _badgeos_load_p2p_core() {
	if ( function_exists( 'p2p_register_connection_type' ) )
		return;

	define( 'P2P_PLUGIN_VERSION', '1.4-beta' );

	define( 'P2P_TEXTDOMAIN', 'badgeos' );

	foreach ( array(
		'storage', 'query', 'query-post', 'query-user', 'url-query',
		'util', 'side', 'type-factory', 'type', 'directed-type', 'indeterminate-type',
		'api', 'list', 'extra', 'item',
	) as $file ) {
		require dirname( __FILE__ ) . "/p2p-core/$file.php";
	}

	if ( is_admin() ) {
		foreach ( array( 'mustache', 'factory',
			'box-factory', 'box', 'fields',
			'column-factory', 'column',
			'tools'
		) as $file ) {
			require dirname( __FILE__ ) . "/p2p-admin/$file.php";
		}
	}

	// TODO: can't use activation hook
	add_action( 'admin_init', array( 'P2P_Storage', 'install' ) );
}
