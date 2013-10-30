<?php
/**
 * Credly Badge Builder Class
 *
 * @package BadgeOS
 * @subpackage Classes
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

class Credly_Badge_Builder {

	/**
	 * SDK URL for accessing badge builder.
	 * @var string
	 */
	public $sdk_url = 'https://credly.com/badge-builder/';

	/**
	 * The site's Credly API key (false if none).
	 * @var string|bool
	 */
	public $credly_api_key = '';

	/**
	 * Temp token used for running badge builder.
	 * @var string
	 */
	public $temp_token = '';

	/**
	 * Instantiate the badge builder.
	 *
	 * @since 1.3.0
	 *
	 */
	public function __construct() {

		// Fetch the credly API key
		$this->credly_api_key = credly_get_api_key();

		add_action( 'wp_ajax_badge-builder-save-badge', array( $this, 'ajax_save_badge' ) );
		add_action( 'wp_ajax_badge-builder-generate-link', array( $this, 'ajax_generate_link' ) );

	}

	/**
	 * Fetch the temp badge builder token.
	 *
	 * @since 1.3.0
	 */
	public function fetch_temp_token() {

		// If we have a valid Credly API key
		if ( $this->credly_api_key ) {

			// Trade the key for a temp token
			$response = wp_remote_post(
				trailingslashit( $this->sdk_url ) . 'code',
				array(
					'body' => array(
						'access_token' => $this->credly_api_key
					)
				)
			);

			// If the response was not an error
			if ( ! is_wp_error( $response ) ) {
				// Decode the response
				$response_body = json_decode( $response['body'] );

				// If response was successful, return the temp token
				if ( isset( $response_body->temp_token ) )
					return $response_body->temp_token;
			}
		}

		// If we made it here, we couldn't retrieve a token
		return false;
	}

	/**
	 * Generate the badge builder link URI.
	 *
	 * @since  1.3.0
	 *
	 * @param  array  $args Args for building the link.
	 * @return string       URL for loading a badge builder.
	 */
	public function generate_link( $args = array() ) {

		// Setup and parse our default args
		$defaults = apply_filters( 'credly_badge_builder_generate_link_defaults', array(
			'continue'  => null,
		) );
		$args = wp_parse_args( $args, $defaults );

		if ( $this->credly_api_key ) {
			$link = add_query_arg(
				array(
					'continue'  => rawurlencode( json_encode( $args['continue'] ) ),
					'TB_iframe' => 'true',
				),
				esc_url( trailingslashit( $this->sdk_url ) . 'embed/' . $this->fetch_temp_token() )
			);
		} else {
			$link = '#TB_inline?width=' . $args['width'] . '&height=' . $args['width'] . '&inlineId=teaser';
		}

		// Return our generated link
		return $link;
	}

	/**
	 * Render the badge builder link.
	 *
	 * @since  1.3.0
	 *
	 * @param  array  $args Args for setting link attributes.
	 * @return string       HTML markup for anchor tag.
	 */
	public function render_link( $args = array() ) {

		// Setup and parse our default args
		$defaults = apply_filters( 'credly_badge_builder_render_link_defaults', array(
			'attachment_id' => get_post_thumbnail_id(),
			'width'         => '960',
			'height'        => '540',
			'continue'      => null,
			'link_text'     => __( 'Use Credly Badge Builder', 'badgeos' ),
		) );
		$args = wp_parse_args( $args, $defaults );

		// Build our link tag
		$embed_url = $this->generate_link( $args );
		$output = '<a href="' . $embed_url . '" class="thickbox badge-builder-link" data-width="' . $args['width'] . '" data-height="' . $args['height'] . '" data-attachment_id="' . $args['attachment_id'] . '">' . $args['link_text'] . '</a>';

		// Include teaser output if we have no API key
		if ( ! $this->credly_api_key )
			$output .= '<div id="teaser" style="display:none;"><a href="' . admin_url( 'admin.php?page=badgeos_sub_credly_integration' ) . '"><img src="' . $GLOBALS['badgeos']->directory_url . 'images/badge-builder-teaser.png" alt="Enable Credly Integration to use the Badge Builder"></a></div>';

		// Include our proper scripts
		add_thickbox();
		wp_enqueue_script( 'credly-badge-builder' );

		// Return our markup
		return apply_filters( 'credly_render_badge_builder', $output, $embed_url, $args['width'], $args['height'] );
	}

	/**
	 * Generate a badge builder link via AJAX.
	 *
	 * @since 1.3.0
	 */
	public function ajax_generate_link() {

		// Build continue param
		$attachment_id = isset( $_REQUEST['attachment_id'] ) ? absint( $_REQUEST['attachment_id'] ) : 0;
		$continue      = $attachment_id ? get_post_meta( $attachment_id, '_credly_badge_meta', true ) : null;

		// Send back the link
		wp_send_json_success( array( 'link' => $this->generate_link( array( 'continue' => $continue ) ) ) );
	}

	/**
	 * Save badge builder data via AJAX.
	 *
	 * @since 1.3.0
	 */
	public function ajax_save_badge() {

		// If this wasn't an ajax call from admin, bail
		if ( ! defined( 'DOING_AJAX' ) || ! defined( 'WP_ADMIN' ) )
			wp_send_json_error();

		// Grab all our data
		$post_id    = $_REQUEST['post_id'];
		$image      = $_REQUEST['image'];
		$icon_meta  = $_REQUEST['icon_meta'];
		$badge_meta = $_REQUEST['all_data'];

		// Upload the image
		$attachment_id = $this->media_sideload_image( $image, $post_id );

		// Set as featured image
		set_post_thumbnail( $post_id, $attachment_id, __( 'Badge created with Credly Badge Builder', 'badgeos' ) );

		// Store badge builder meta
		$this->update_badge_meta( $attachment_id, $badge_meta, $icon_meta );

		// Build new markup for the featured image metabox
		$metabox_html = _wp_post_thumbnail_html( $attachment_id, $post_id );

		// Return our success response
		wp_send_json_success( array( 'attachment_id' => $attachment_id, 'metabox_html' => $metabox_html ) );

	}

	/**
	 * Upload an image and return the attachment ID.
	 *
	 * Ripped from wp-admin/includes/media.php because
	 * core's version returns a full <img> string rather
	 * than the attachment ID, or an array of data, or any
	 * other useful thing. Yeah, I don't get it either.
	 *
	 * @since  1.3.0
	 *
	 * @param  string  $file    Image URI.
	 * @param  integer $post_id Post ID.
	 * @param  string  $desc    Image description.
	 * @return integer          Attachment ID.
	 */
	function media_sideload_image( $file, $post_id, $desc = null ) {
		if ( ! empty( $file ) ) {
			// Download file to temp location
			$tmp = download_url( $file );

			// Set variables for storage
			// fix file filename for query strings
			preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );
			$file_array['name'] = basename($matches[0]);
			$file_array['tmp_name'] = $tmp;

			// If error storing temporarily, unlink
			if ( is_wp_error( $tmp ) ) {
				@unlink($file_array['tmp_name']);
				$file_array['tmp_name'] = '';
			}

			// do the validation and storage stuff
			$id = media_handle_sideload( $file_array, $post_id, $desc );

			// If error storing permanently, unlink
			if ( is_wp_error($id) ) {
				@unlink($file_array['tmp_name']);
			}

			// Send back the attachment ID
			return $id;
		}
	}

	/**
	 * Update badge attachment meta
	 *
	 * @since 1.3.0
	 *
	 * @param integer $attachment_id Attachment ID.
	 * @param string  $badge_meta    Badge Builder Meta.
	 * @param string  $icon_meta     Badge Icon Meta.
	 */
	public function update_badge_meta( $attachment_id = 0, $badge_meta = null, $icon_meta = null ) {

		// Bail if no attachment ID
		if ( ! $attachment_id )
			return;

		if ( ! empty( $badge_meta ) )
			update_post_meta( $attachment_id, '_credly_badge_meta', $badge_meta );

		if ( ! empty( $icon_meta ) ) {
			update_post_meta( $attachment_id, '_credly_icon_meta', $icon_meta );
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', badgeos_badge_builder_get_icon_credit( $attachment_id ) );
		}
	}

}
