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

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->directory_path = plugin_dir_path( dirname( __FILE__ ) );
		$this->directory_url  = plugin_dir_url( dirname( __FILE__ ) );

		add_action( 'admin_footer',  array( $this, 'add_shortcode_popup' ) );
		add_action( 'media_buttons', array( $this, 'add_shortcode_button'), 20 );
		add_action( 'admin_head', array( $this, 'add_shortcode_css' ) );
	}

	public function add_shortcode_popup() { ?>
		<script>
			//return our end result and send to the post editor.
			function badgeos_insert_shortcode(){
				var shortcode, attributes;

				shortcode = jQuery('#select_shortcode option:selected').val();
				attributes = badgeos_get_attributes( shortcode );

				window.send_to_editor( badgeos_create_shortcode(attributes) );
			}

			//Grab our values from the inputs and add to our attributes object.
			function badgeos_get_attributes(shortcode) {
				var attributes = {};

				switch( shortcode ) {

				}

				attributes.shortcodename = shortcode;

				return attributes;
			}

			//Concatenate all of our attributes into one string.
			function badgeos_create_shortcode(attributes) {
				var shortcode = '[';
				shortcode += attributes.shortcodename;

				shortcode += ']';

				return shortcode;
			}

			jQuery(document).ready(function($){
				//Handle changing the html used for the selected shortcode.
				$('#select_shortcode').on('change',function(){
					var selected = $('#select_shortcode option:selected').val();

					switch( selected ) {
						case "credly_assertion_page":

							break;

						default:

							break;
					}
				});
			});

		</script>
		<?php

		$this->add_shortcode_popup_html();
	}

	public function add_shortcode_popup_html() { ?>
		<div id="select_badgeos_shortcode" style="display:none;">
			<div class="wrap">
				<div>
					<h3><?php _e( 'Insert a shortcode', 'badgeos' ); ?></h3>

					<p><?php _e( 'Select a shortcode below to add it to your post or page.', 'badgeos' ); ?></p>

					<select id="select_shortcode">
						<option value=""><?php _e( 'Select a shortcode', 'badgeos' ); ?></option>
						<?php
							foreach( badgeos_get_shortcodes() as $name => $shortcode ) { ?>
								<option value="<?php echo $shortcode ?>"><?php echo $name; ?></option>
								<?php
							}
						?>
					</select>
					<div id="shortcode_options"></div>
					<div>
						<input id="badgeos_insert" type="button" class="button-primary" value="<?php esc_attr_e( 'Insert Shortcode', 'badgeos' ); ?>" onclick="badgeos_insert_shortcode();"/>
						<a id="badgeos_cancel" class="button" href="#" onclick="tb_remove(); return false;"><?php _e( 'Cancel', 'badgeos' ); ?></a>
					</div>
				</div>
			</div>
		</div>

	<?php
	}

	//Action target that adds the "Insert Form" button to the post/page edit screen
	public function add_shortcode_button() {
		// do a version check for the new 3.5 UI
			// display button matching new UI
		echo '<a id="insert_badgeos_shortcodes" href="#TB_inline?width=480&inlineId=select_badgeos_shortcode" class="thickbox button badgeos_media_link" title="' . esc_attr__( 'Add BadgeOS Shortcode', 'badgeos' ) . '">' . __( 'Add BadgeOS Shortcode', 'badgeos' ) . '</a>';
	}

	public function add_shortcode_css() {
		echo '<style>.wp-core-ui a.badgeos_media_link{ padding-left: 0.4em; } </style>';
	}

}

new WP_Editor_Shortcodes();

function badgeos_get_shortcodes() {
	return array(
		__( 'Achievements List', 'badgeos' )        => 'badgeos_achievements_list',
		__( 'User Achievements List', 'badgeos' )   => 'badgeos_user_achievements',
		__( 'Single Achievement', 'badgeos' )       => 'badgeos_achievement',
		__( 'Nomination Form', 'badgeos' )          => 'badgeos_nomination',
		__( 'Submission Form', 'badgeos' )          => 'badgeos_submission',
		__( 'Nominations List', 'badgeos' )         => 'badgeos_nominations',
		__( 'Submissions List', 'badgeos' )         => 'badgeos_submissions',
		__( 'Credly Assertion Page', 'badgeos' )    => 'credly_assertion_page'
	);
}
