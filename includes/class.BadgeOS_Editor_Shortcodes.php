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
							'<a target="_blank" href="%s">' . __( 'Help/Support', 'badgeos' ) . '</a>',
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

				<div id="shortcode_options" class="alignleft clear">
					<?php
					$count = 1;
					foreach( $shortcodes as $shortcode ) {
						if ( $count == 1 ) {
							echo '<div class="alignleft" id="' . $shortcode->slug . '_wrapper">';
						} else {
							echo '<div class="alignleft" style="display: none" class="hidden" id="' . $shortcode->slug . '_wrapper">';
						}
					$count++;
						foreach( $shortcode->attributes as $attribute ) {
							if ( 'integer' == $attribute['type'] ) {
								$this->text_input( array( 'name' => $shortcode->name, 'slug' => $shortcode->slug, 'attributes' => $attribute ) );
							} else {
								$this->select_input( array( 'name' => $shortcode->name, 'slug' => $shortcode->slug, 'attributes' => $attribute ) );
							}

						}
						echo '</div>';
					}
					?>
				</div>
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
		//wp_enqueue_script( 'badgeos-shortcodes-embed', $this->directory_url . "/js/badgeos-shortcode-embed$min.js", array( 'jquery' ), '', true );
	}

	public function text_input( $shortcode ) {
		echo '<div class="badgeos_input alignleft"><label for="' . $shortcode['name'] . '">' . $shortcode['attributes']['name'] . '</label><br/>';
		echo '<input id="' . $shortcode['name'] . '" name="' . $shortcode['name'] . '" type="text" />';
		if ( $shortcode['attributes']['description'] ) {
			echo '<br/><span>' . $shortcode['attributes']['description'] . '</span>';
		}
		echo '</div>';
	}

	public function select_input( $shortcode ) {

		echo '<div class="badgeos_input alignleft"><label for="' . $shortcode['name'] . '">' . $shortcode['attributes']['name'] . '</label><br/>';
		echo '<select id="badgeos_' . $shortcode['slug'] . '" name="badgeos_' . $shortcode['slug'] . '">';
		if ( $shortcode['attributes']['default'] ) {
			echo '<option selected="selected" value="' . $shortcode['slug'] . '">' . $shortcode['slug'] . '</option>';
		} else {
			echo '<option value="' . $shortcode['slug'] . '">' . $shortcode['name'] . '</option>';
		}
		echo '</select>';

		if ( $shortcode['attributes']['description'] ) {
			echo '<br/><span>' . $shortcode['attributes']['description'] . '</span>';
		}
		echo '</div>';
	}

	public function select_bool_input( $shortcode ) {

		echo '<div class="badgeos_input alignleft"><label for="' . $shortcode['name'] . '">' . $shortcode['attributes']['name'] . '</label><br/>';
		echo '<select id="badgeos_' . $shortcode['slug'] . '" name="badgeos_' . $shortcode['slug'] . '">';
		if ( $shortcode['attributes']['default'] ) {
			echo '<option selected="selected" value="' . $shortcode['slug'] . '">' . $shortcode['slug'] . '</option>';
		} else {
			echo '<option value="' . $shortcode['slug'] . '">' . $shortcode['name'] . '</option>';
		}
		echo '</select>';

		if ( $shortcode['attributes']['description'] ) {
			echo '<br/><span>' . $shortcode['attributes']['description'] . '</span>';
		}
		echo '</div>';
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

function badgeos_get_awardable_achievements() {
	$achievements = badgeos_get_achievement_types();

	unset( $achievements['step'] );
	unset( $achievements['submission'] );
	unset( $achievements['nomination'] );
	unset( $achievements['badgeos-log-entry'] );

	return wp_list_pluck( $achievements, 'single_name' );
}


/*
Use badgeos_activity_trigger_post_select_ajax_handler() to fetch posts in achievement type. Consider "post_select_ajax" for action
select achievement type from dropdown list. This would be fine for localized data.
 */
