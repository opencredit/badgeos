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
		add_action( 'admin_head', array( $this, 'styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ), 99 );
	}

	public function add_shortcode_popup() { ?>
		<div id="select_badgeos_shortcode" style="display:none;">
			<div class="wrap">
				<div>
					<h3><?php _e( 'Insert a shortcode', 'badgeos' ); ?></h3>

					<p><?php _e( 'Select a shortcode below to add it to your post or page.', 'badgeos' ); ?></p>

					<select id="select_shortcode">
						<option value="unselected"><?php _e( 'Select a shortcode', 'badgeos' ); ?></option>
						<?php
							foreach( badgeos_get_shortcodes() as $name => $shortcode ) { ?>
								<option value="<?php echo $shortcode ?>"><?php echo $name; ?></option>
								<?php
							}
						?>
					</select>
					<div id="shortcode_options"></div>
					<div>
						<input id="badgeos_insert" type="button" class="button-primary" value="<?php esc_attr_e( 'Insert Shortcode', 'badgeos' ); ?>" />
						<a id="badgeos_cancel" class="button" href="#"><?php _e( 'Cancel', 'badgeos' ); ?></a>
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

	public function styles() {
		echo '<style>.wp-core-ui a.badgeos_media_link{ padding-left: 0.4em; } #shortcode_options { min-height: 200px; padding-top: 20px; }</style>';
	}

	public function scripts() {
		wp_enqueue_script( 'badgeos-shortcodes-embed', $this->directory_url . '/js/badgeos-shortcode-embed.js', array( 'jquery' ), '', true );
		wp_localize_script( 'badgeos-shortcodes-embed', 'badgeos_shortcodes', $this->default_parameters() );
		wp_localize_script( 'badgeos-shortcodes-embed', 'badgeos_shortcode_bool', $this->bools() );
	}

	public function default_parameters() {
		$defaults = apply_filters( 'badgeos_shortcodes_defaults', array() );

        $defaults['badgeos_achievements_list']  = array(
			array( 'param' => 'type', 'type' => 'text' ),
			array( 'param' => 'limit', 'type' => 'text' ),
			array( 'param' => 'show_filter', 'type' => 'bool' ),
			array( 'param' => 'show_search', 'type' => 'bool' ),
			array( 'param' => 'wpms', 'type' => 'bool' ),
			array( 'param' => 'orderby', 'type' => 'text' ),
			array( 'param' => 'order', 'type' => 'text' ),
			array( 'param' => 'include', 'type' => 'text' ),
			array( 'param' => 'exclude', 'type' => 'text' ),
			array( 'param' => 'meta_key', 'type' => 'text' ),
			array( 'param' => 'meta_value', 'type' => 'text' )
		);
        $defaults['badgeos_user_achievements']  = array(
			array( 'param' => 'user', 'type' => 'text' ),
			array( 'param' => 'type', 'type' => 'text' ),
			array( 'param' => 'limit', 'type' => 'text' )
		);
        $defaults['badgeos_achievement'] = array(
			array( 'param' => 'id', 'type' => 'text' )
		);
        $defaults['badgeos_nomination'] = array(
        	array( 'param' => 'achievement_id', 'type' => 'text' )
        );
        $defaults['badgeos_submission'] = array(
			array( 'param' => 'achievement_id', 'type' => 'text' )
		);
        $defaults['badgeos_submissions'] = array(
			array( 'param' => 'limit', 'type' => 'text' ),
			array( 'param' => 'status', 'type' => 'text' ),
			array( 'param' => 'show_filter', 'type' => 'bool' ),
			array( 'param' => 'show_search', 'type' => 'bool' ),
			array( 'param' => 'show_attachments', 'type' => 'bool' ),
			array( 'param' => 'show_comments', 'type' => 'bool' )
		);
        $defaults['badgeos_nominations'] = array(
			array( 'param' => 'limit', 'type' => 'text' ),
			array( 'param' => 'status', 'type' => 'text' ),
			array( 'param' => 'show_filter', 'type' => 'bool' ),
			array( 'param' => 'show_search', 'type' => 'bool' )
		);

		return $defaults;
	}

	public function bools() {
		return array( __( 'True', 'badgeos' ), __( 'False', 'badgeos' ) );
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
