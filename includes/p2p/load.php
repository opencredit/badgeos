<?php
/*
Plugin Name: P2P Bundle Example
Author: scribu
*/

// Include SCB library
require_once dirname( __FILE__ ) . '/scb/load.php';

/**
 * Load P2P.
 *
 * @since 1.3.4
 */
function _badgeos_p2p_load() {
	if ( function_exists( 'p2p_register_connection_type' ) )
		return;

	define( 'P2P_PLUGIN_VERSION', '1.6.3-alpha' );
	define( 'P2P_TEXTDOMAIN', 'badgeos' );

	require_once dirname( __FILE__ ) . '/p2p-core/init.php';

	register_uninstall_hook( __FILE__, array( 'P2P_Storage', 'uninstall' ) );

	if ( is_admin() ) {
		_badgeos_p2p_load_admin();
	}

}
scb_init( '_badgeos_p2p_load' );

/**
 * Load P2P admin functionality.
 *
 * @since 1.3.4
 */
function _badgeos_p2p_load_admin() {
	P2P_Autoload::register( 'P2P_', dirname( __FILE__ ) . '/p2p-admin' );

	new P2P_Box_Factory;
	new P2P_Column_Factory;
	new P2P_Dropdown_Factory;
	new P2P_Tools_Page;
}
