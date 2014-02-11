<?php
/**
 * Add-on Updater Class
 *
 * @package BadgeOS
 * @subpackage Classes
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

// Uncomment this line for testing
//set_site_transient( 'update_plugins', null );

/**
 * Our add-on updater class.
 *
 * Original codebase by Pippin Williamson
 */
class BadgeOS_Plugin_Updater {

	private $api_url   = '';
	private $api_data  = array();
	private $name      = '';
	private $slug      = '';
	private $version   = '';
	private $item_name = '';

	/**
	 * Class constructor.
	 *
	 * @since 1.2.0
	 *
	 * @param string $_api_url The URL pointing to the custom API endpoint.
	 * @param string $_plugin_file Path to the plugin file.
	 * @param array $_api_data Optional data to send with API calls.
	 * @return void
	 */
	function __construct( $_api_data = null ) {

		// Setup our add-on data
		$this->api_data  = urlencode_deep( $_api_data );
		$this->api_url   = 'http://badgeos.org/';
		$this->name      = plugin_basename( $_api_data['plugin_file'] );
		$this->slug      = basename( $_api_data['plugin_file'], '.php' );
		$this->version   = $_api_data['version'];
		$this->item_name = $_api_data['item_name'];

		// Setup the license
		$this->api_data['license'] = trim( get_option( $this->slug . '-license_key' ) );
		$this->api_data['license_status'] = trim( get_option( $this->slug . '-license_status' ) );

		// Set up hooks.
		$this->hook();
	}

	/**
	 * Set up Wordpress filters to hook into WP's update process.
	 *
	 * @since 1.2.0
	 */
	private function hook() {
		add_filter( 'badgeos_licensed_addons', array( $this, 'register_licensed_addon' ) );
		add_action( 'badgeos_settings_validate', array( $this, 'validate_license' ) );
		add_filter( 'plugins_api', array( $this, 'plugins_api_filter' ), 10, 3 );
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'pre_set_site_transient_update_plugins_filter' ) );
	}

	/**
	 * Activate a new license or deactivate a deleted license
	 *
	 * @since 1.2.0
	 *
	 * @param array $input Input data sent via $POST
	 */
	public function validate_license( $input ) {

		// Activate our license if we have license data
		if ( isset( $input['licenses'][$this->slug] ) && ! empty( $input['licenses'][$this->slug] ) )
			$this->activate_license( $input['licenses'][$this->slug] );

		// Otherwise, deactivate the previous license
		else
			$this->deactivate_license();

	}

	/**
	 * Add this add-on to the list of registered licesned add-ons
	 *
	 * @since  1.2.0
	 *
	 * @param  array $licensed_addons The current licensed addons
	 * @return array                  The updated list of licensed add-ons
	 */
	public function register_licensed_addon( $licensed_addons ) {

		// Add our add-on to the array
		$licensed_addons[$this->slug] = $this->api_data;

		// Return all licensed add-ons
		return $licensed_addons;
	}

	/**
	 * Check for Updates at the defined API endpoint and modify the update array.
	 *
	 * This function dives into the update api just when Wordpress creates its update array,
	 * then adds a custom API call and injects the custom plugin data retrieved from the API.
	 * It is reassembled from parts of the native Wordpress plugin update code.
	 * See wp-includes/update.php line 121 for the original wp_update_plugins() function.
	 *
	 * @since 1.2.0
	 *
	 * @param  array $_transient_data Update array build by Wordpress.
	 * @return array                  Modified update array with custom plugin data.
	 */
	function pre_set_site_transient_update_plugins_filter( $_transient_data ) {

		if ( empty( $_transient_data ) ) return $_transient_data;

		$to_send = array( 'slug' => $this->slug );

		$api_response = $this->api_request( 'plugin_latest_version', $to_send );

		if ( false !== $api_response && is_object( $api_response ) && isset( $api_response->new_version ) ) {
			if( version_compare( $this->version, $api_response->new_version, '<' ) )
				$_transient_data->response[$this->name] = $api_response;
		}

		return $_transient_data;
	}


	/**
	 * Updates information on the "View version x.x details" page with custom data.
	 *
	 * @since 1.2.0
	 *
	 * @param  mixed  $_data
	 * @param  string $_action
	 * @param  object $_args
	 * @return object $_data
	 */
	function plugins_api_filter( $_data, $_action = '', $_args = null ) {
		if ( ( $_action != 'plugin_information' ) || !isset( $_args->slug ) || ( $_args->slug != $this->slug ) ) return $_data;

		$to_send = array( 'slug' => $this->slug );

		$api_response = $this->api_request( 'plugin_information', $to_send );
		if ( false !== $api_response ) $_data = $api_response;

		return $_data;
	}

	/**
	 * Calls the API and, if successfull, returns the object delivered by the API.
	 *
	 * @since 1.2.0
	 *
	 * @param  string       $_action The requested action.
	 * @param  array        $_data   Parameters for the API action.
	 * @return false|object
	 */
	private function api_request( $_action, $_data ) {

		$data = array_merge( $this->api_data, $_data );

		if ( $data['slug'] != $this->slug )
			return;

		if ( empty( $data['license'] ) )
			return;

		$api_params = array(
			'edd_action' => 'get_version',
			'license'    => $data['license'],
			'name'       => $data['item_name'],
			'slug'       => $this->slug,
			'author'     => $data['author']
		);
		$request = wp_remote_post( $this->api_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		if ( ! is_wp_error( $request ) ) {
			$request = json_decode( wp_remote_retrieve_body( $request ) );
			if ( $request && isset( $request->sections ) )
				$request->sections = maybe_unserialize( $request->sections );
			return $request;
		} else {
			return false;
		}
	}

	/**
	 * Handle license key activation
	 *
	 * This returns true on a successful server response,
	 * even if the server responds with 'invalid' for the
	 * license status.
	 *
	 * @since  1.2.0
	 *
	 * @param  string $license The license key to activate
	 * @return bool            False on failure, true on success
	 */
	public function activate_license( $license = '' ) {

		// Run a quick security check
		if ( ! check_admin_referer( 'badgeos_settings_nonce', 'badgeos_settings_nonce' ) )
			return; // get out if we didn't click the Activate button

		// If the license hasn't changed, bail here
		if ( $license == $this->api_data['license'] && 'valid' == $this->api_data['license_status'] )
			return false;

		// Setup our API Request data
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => trim( $license ),
			'item_name'  => urlencode( $this->item_name )
		);

		// Call the remote API
		$response = wp_remote_get( add_query_arg( $api_params, $this->api_url ), array( 'timeout' => 15, 'sslverify' => false ) );

		// Make sure the response came back okay
		if ( is_wp_error( $response ) )
			return false;

		// Decode the license data
		// $license_data->license will be "active", "inactive" or "invalid"
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// Store the data in the options table
		update_option( $this->slug . '-license_key', $license );
		update_option( $this->slug . '-license_status', $license_data->license );

		return true;

	}

	/**
	 * Handle license key deactivation
	 *
	 * @since  1.2.0
	 */
	public function deactivate_license() {

		// Run a quick security check
		if ( ! check_admin_referer( 'badgeos_settings_nonce', 'badgeos_settings_nonce' ) )
			return false; // get out if we didn't click the Activate button

		// Retrieve the license from the database
		$license = trim( get_option( $this->slug . '-license_key' ) );

		// If there isn't one, bail here
		if ( empty( $license ) )
			return false;

		// Setup our API Request data
		$api_params = array(
			'edd_action' => 'deactivate_license',
			'license'    => $license,
			'item_name'  => urlencode( $this->item_name ) // the name of our product in EDD
		);

		// Call the custom API.
		$response = wp_remote_get( add_query_arg( $api_params, $this->api_url ), array( 'timeout' => 15, 'sslverify' => false ) );

		// Make sure the response came back okay
		if ( is_wp_error( $response ) )
			return false;

		// Decode the license data
		// $license_data->license will be either "deactivated" or "failed"
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// Clear our stored license options
		delete_option( $this->slug . '-license_key' );
		update_option( $this->slug . '-license_status', 'inactive' );

		return true;
	}

	/**
	 * Check a license for validity
	 *
	 * @since  1.2.0
	 *
	 * @return string 'valid' if license is valid, 'invalid' otherwise
	 */
	public function check_license() {

		$license = trim( get_option( $this->slug . '-license_key' ) );

		$api_params = array(
			'edd_action' => 'check_license',
			'license'    => $license,
			'item_name'  => urlencode( $this->item_name )
		);

		// Call the custom API.
		$response = wp_remote_get( add_query_arg( $api_params, $this->api_url ), array( 'timeout' => 15, 'sslverify' => false ) );


		if ( is_wp_error( $response ) )
			return false;

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if( 'valid' == $license_data->license ) {
			echo 'valid'; exit;
			// this license is still valid
		} else {
			echo 'invalid'; exit;
			// this license is no longer valid
		}
	}

}
