<?php
/**
 * WP Editor Shortcodes Class
 *
 * @package BadgeOS
 * @subpackage Classes
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WP_Editor_Shortcodes {

	public function __construct() {
		$this->directory_path = plugin_dir_path( dirname( __FILE__ ) );
		$this->directory_url  = plugin_dir_url( dirname( __FILE__ ) );

		add_action( 'admin_footer',  array( $this, 'add_shortcode_popup' ) );
		add_action( 'media_buttons', array( $this, 'add_shortcode_button'), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ), 99 );
	}

	/**
	 * Provide our markup to be used within the thickbox popup.
	 *
	 * @since 1.4.0
	 */
	public function add_shortcode_popup() { ?>
		<div id="select_badgeos_shortcode" style="display:none;">
			<div class="wrap">
				<h3><?php _e( 'Insert a BadgeOS shortcode', 'badgeos' ); ?></h3>

				<p>
					<?php echo sprintf( __( 'See the %s page for more information', 'badgeos' ),
						sprintf(
							'<a href="%s">' . __( 'Help/Support', 'badgeos' ) . '</a>',
							admin_url( 'admin.php?page=badgeos_sub_help_support' )
						)
					); ?>
				</p>
				<div class="alignleft">
				<?php $shortcodes = badgeos_get_shortcodes(); ?>
				<select id="select_shortcode">
					<option value="unselected">--</option>
					<?php
						foreach( $shortcodes as $shortcode ) { ?>
							<option value="<?php echo $shortcode->slug ?>"><?php echo $shortcode->name; ?></option>
							<?php
						}
					?>
				</select>
				</div>
				<div class="alignright">
					<input id="badgeos_insert" type="button" class="button-primary" value="<?php esc_attr_e( 'Insert Shortcode', 'badgeos' ); ?>" />
					<a id="badgeos_cancel" class="button" href="#"><?php _e( 'Cancel', 'badgeos' ); ?></a>
				</div>

				<div id="shortcode_options" class="alignleft clear"></div>
			</div>
		</div>

	<?php
	}

	/**
	 * Add our button markup to the media buttons area above the post editor.
	 *
	 * @since 1.4.0
	 */
	public function add_shortcode_button() {
		echo '<a id="insert_badgeos_shortcodes" href="#TB_inline?width=480&inlineId=select_badgeos_shortcode" class="thickbox button badgeos_media_link" title="' . esc_attr__( 'Add BadgeOS', 'badgeos' ) . '">' . __( 'Add BadgeOS', 'badgeos' ) . '</a>';
	}

	/**
	 * Enqueue and localize our scripts
	 *
	 * @since  1.4.0
	 */
	public function scripts() {
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script( 'badgeos-shortcodes-embed', $this->directory_url . "/js/badgeos-shortcode-embed$min.js", array( 'jquery' ), '', true );
		wp_localize_script( 'badgeos-shortcodes-embed', 'badgeos_shortcodes', $this->default_parameters() );
		wp_localize_script( 'badgeos-shortcodes-embed', 'badgeos_shortcode_bool', $this->bools() );
		wp_localize_script( 'badgeos-shortcodes-embed', 'badgeos_shortcode_messages', $this->messages() );
	}

	/**
	 * Return a filtered array of default shortcode parameters.
	 *
	 * @since  1.4.0
	 *
	 * @return array  array of available shortcodes and their parameters
	 */
	public function default_parameters() {
		$shortcodes = badgeos_get_shortcodes();

		$defaults = array();

		foreach( $shortcodes as $name => $shortcode_properties ) {
			$defaults[ $shortcode_properties->slug ] = array();
			foreach ( $shortcode_properties->attributes as $attribute => $value ) {
				$parameters = array();
				$parameters['param'] = isset( $attribute ) ? $attribute : '';
				$parameters['type'] = isset( $value['type'] ) ? $value['type'] : '';
				$parameters['default_text'] = isset( $value['description'] ) ? $value['description'] : '';
				$parameters['default'] = isset( $value['default'] ) ? $value['default'] : '';

				$defaults[ $shortcode_properties->slug ][] = $parameters;
			}

		}

		return $defaults;
	}

	/**
	 * Localize boolean values to be used with modal popup and select inputs
	 *
	 * @since  1.4.0
	 *
	 * @return array  array of i10n-ready boolean text.
	 */
	public function bools() {
		return array( __( 'True', 'badgeos' ), __( 'False', 'badgeos' ) );
	}

	/**
	 * Localize messages to be used with the modal popup.
	 *
	 * @since  1.4
	 *
	 * @return array  array of i10n-ready messages.
	 */
	public function messages() {
		return array(
			'noparams' => __( 'This shortcode does not have any attributes', 'badgeos' ),
			'starting' => __( 'Select a shortcode from the dropdown above to configure attributes. Once you have all desired attributes, click "Insert Shortcode"', 'badgeos' )
		);
	}

}

new WP_Editor_Shortcodes();

/**
 * Return a filtered array of available shortcode names and shortcode
 *
 * @since  1.4.0
 *
 * @return array  array of available shortcodes.
 */
function badgeos_get_shortcodes() {
	return apply_filters( 'badgeos_shortcodes', array() );
}
