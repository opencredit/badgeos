<?php
/**
 * Credly API
 *
 * @package BadgeOS
 * @subpackage BadgeOS_Credly_API
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

//Check if we've defined our url elsewhere, if not, set it here.
if ( !defined( 'BADGEOS_CREDLY_API_URL' ) ) {
    define( 'BADGEOS_CREDLY_API_URL', 'https://api.credly.com/v0.2/' );
}

if ( !defined( 'BADGEOS_CREDLY_API_STAGING_URL' ) ) {
    define( 'BADGEOS_CREDLY_API_STAGING_URL', 'https://apistaging.credly.com/v0.2/' );
}

/**
 * Build our Credly object
 *
 * @since 1.0.0
 */
class BadgeOS_Credly_API {

	/**
	 * @var string Credly Account API Key
	 */
	public $api_key;

	/**
	 * @var string Credly Account API Secret
	 */
	public $api_secret;

	/**
	 * @var bool Sandbox mode
	 */
	public $sandbox_mode = false;

	/**
	 * @var array Methods
	 */
	private $methods = array(

	);

	/**
	 * Setup BadgeOS_Credly_API
	 *
	 * @param string $api_key Credly API Key
	 */
	public function __construct( $api_key = '', $api_secret = '', $sandbox_mode = false ) {

		$this->api_key = $api_key;
		$this->api_secret = $api_secret;
		$this->sandbox_mode = $sandbox_mode;

	}

	/**
	 * Make request to Credly API
	 *
	 * @param string $api_method API method
	 * @param array $data Data to send
	 * @param string $method Request Method
	 *
	 * @return object|WP_Error JSON object response from API, or WP_Error if error
	 */
	private function request( $api_method, $data, $request_method = 'GET' ) {

		$url = BADGEOS_CREDLY_API_URL;

		if ( $this->sandbox_mode ) {
			$url = BADGEOS_CREDLY_API_STAGING_URL;
		}

		// Allow API URL override.
		$url = apply_filters( 'badgeos_credly_api_request_url', $url, $api_method, $data, $request_method );

		$url .= $api_method . '?access_token=' . $this->api_key;

		$request_method = strtoupper( $request_method );

		if ( !in_array( $request_method, array( 'GET', 'POST', 'PUT', 'DELETE' ) ) ) {
			$request_method = 'GET';
		}

		$args = array(
			'method'      => $request_method,
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => array(),
			'body'        => $data,
			'cookies'     => array()
		);

		$args = apply_filters( 'badgeos_credly_api_request_args', $args, $api_method, $data, $request_method );

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_body = wp_remote_retrieve_body( $response );

		if ( !empty( $response_body ) ) {
			$json_response = @json_decode( $response_body );

			if ( is_object( $json_response ) && isset( $json_response->meta ) ) {
				if ( 200 == $json_response->meta->status_code && isset( $json_response->data ) ) {
					return $json_response->data;
				}

				return new WP_Error( 'badgeos_credly_' . strtolower( $json_response->meta->status ), $json_response->meta->message, $json_response );
			}

			return new WP_Error( 'badgeos_credly_invalid_json', __( 'Invalid JSON Response', 'badgeos' ), $response_body );
		}

		return new WP_Error( 'badgeos_credly_invalid_request', __( 'Invalid Request', 'badgeos' ), $response_body );

	}

    /**
     * Dynamically handle API methods
     *
     * @var string $method Method name
     * @var array $args Method arguments
     *
     * @return mixed
     */
    public function __call( $method, $args ) {

        $method = (string) $method;

        if ( method_exists( $this, '_' . $method ) ) {
            return call_user_func_array( array( $this, '_' . $method ), $args );
		}

		$data = array();

		if ( isset( $args[ 0 ] ) && is_array( $args[ 0 ] ) ) {
			$data = $args[ 0 ];
		}

		$request_method = 'GET';

		if ( isset( $args[ 1 ] ) ) {
			$request_method = $args[ 1 ];
		}
		elseif ( isset( $this->methods[ $method ] ) ) {
			$request_method = $this->methods[ $method ];
		}
		elseif ( !empty( $this->methods ) ) {
			foreach ( $this->methods as $api_method => $api_request_method ) {
				if ( preg_match( '/^' . preg_quote( $api_method, '/' ) . '$/', $method ) ) {
					$request_method = $api_request_method;

					break;
				}
			}
		}

		return $this->request( $method, $data, $request_method );

    }

} // End BadgeOS_Credly_API class