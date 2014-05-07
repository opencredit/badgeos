<?php
/**
 * BadgeOS Shortcodes WP Editor Class
 *
 * @package BadgeOS
 * @subpackage Classes
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class BadgeOS_Editor_Shortcodes {

	public function __construct() {
		$this->directory_path = plugin_dir_path( dirname( __FILE__ ) );
		$this->directory_url  = plugin_dir_url( dirname( __FILE__ ) );
		$this->shortcodes     = badgeos_get_shortcodes();

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 99 );
		add_action( 'media_buttons', array( $this, 'render_button'), 20 );
		add_action( 'admin_footer',  array( $this, 'render_modal' ) );

	}

	/**
	 * Enqueue and localize relevant admin_scripts.
	 *
	 * @since  1.4.0
	 */
	public function admin_scripts() {
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$min = '';
		wp_enqueue_script( 'badgeos-select2', $this->directory_url . "js/select2/select2$min.js", array( 'jquery' ), '', true );
		wp_enqueue_script( 'badgeos-shortcodes-embed', $this->directory_url . "js/badgeos-shortcode-embed$min.js", array( 'jquery', 'badgeos-select2' ), '', true );
		wp_localize_script( 'badgeos-shortcodes-embed', 'badgeos_shortcode_embed_messages', $this->get_localized_text() );
		wp_enqueue_style( 'badgeos-select2-css', $this->directory_url . 'js/select2/select2.css' );
	}

	/**
	 * Get localized JS text.
	 *
	 * @since 1.4.0
	 *
	 * @return array Array of translated text
	 */
	public function get_localized_text() {
		return array(
			'id_placeholder'          => __( 'Select a Post', 'badgeos' ),
			'id_multiple_placeholder' => __( 'Select Post(s)', 'badgeos' ),
			'user_placeholder'        => __( 'Select a user', 'badgeos' ),
			'post_type_placeholder'   => __( 'Default: All', 'badgeos' ),
		);
	}

	/**
	 * Render shortcode modal insert button.
	 *
	 * @since 1.4.0
	 */
	public function render_button() {
		echo '<a id="insert_badgeos_shortcodes" href="#TB_inline?width=660&height=800&inlineId=select_badgeos_shortcode" class="thickbox button badgeos_media_link" data-width="800">' . __( 'Add BadgeOS Shortcode', 'badgeos' ) . '</a>';
	}

	/**
	 * Render shortcode modal content.
	 *
	 * @since 1.4.0
	 */
	public function render_modal() { ?>
		<div id="select_badgeos_shortcode" style="display:none;">
			<div class="wrap">
				<h3><?php _e( 'Insert a BadgeOS shortcode', 'badgeos' ); ?></h3>
				<p><?php printf( __( 'See the %s page for more information', 'badgeos' ), '<a target="_blank" href="' . admin_url( 'admin.php?page=badgeos_sub_help_support' ) . '">' . __( 'Help/Support', 'badgeos' ) . '</a>' ); ?></p>
				<div class="alignleft">
					<select id="select_shortcode"><?php echo $this->get_shortcode_selector(); ?></select>
				</div>
				<div class="alignright">
					<a id="badgeos_insert" class="button-primary" href="#" style="color:#fff;"><?php esc_attr_e( 'Insert Shortcode', 'badgeos' ); ?></a>
					<a id="badgeos_cancel" class="button-secondary" href="#"><?php esc_attr_e( 'Cancel', 'badgeos' ); ?></a>
				</div>
				<div id="shortcode_options" class="alignleft clear">
					<?php echo $this->get_shortcode_sections(); ?>
				</div>
			</div>
		</div>

	<?php
	}

	private function get_shortcode_selector() {
		$output = '';
		foreach( $this->shortcodes as $shortcode ) {
			$output .= sprintf( '<option value="%1$s">%2$s</option>', $shortcode->slug, $shortcode->name );
		}
		return $output;
	}

	private function get_shortcode_sections() {
		$output = '';
		foreach( $this->shortcodes as $shortcode ) {
			$output .= $this->get_shortcode_section( $shortcode );
		}
		return $output;
	}

	private function get_shortcode_section( $shortcode = array() ) {
		$output = '<div class="shortcode-section alignleft" id="' . $shortcode->slug . '_wrapper">';
		$output .= sprintf( '<p><strong>%1$s</strong> - %2$s</p>', "[{$shortcode->slug}]", $shortcode->description );
		foreach( $shortcode->attributes as $slug => $attribute ) {
			$attribute['slug'] = $slug;
			$output .= $this->get_input( array( 'shortcode' => $shortcode, 'attribute' => $attribute ) );
		}
		$output .= '</div>';
		return $output;
	}

	private function get_input( $args = array() ) {
		switch( $args['attribute']['type'] ) {
			case 'select' :
				return $this->get_select_input( $args );
			case 'text' :
			case 'select2' :
			default :
				return $this->get_text_input( $args );
		}
	}

	private function get_text_input( $args = array() ) {
		return sprintf(
			'
			<div class="badgeos_input alignleft">
				<label for="%1$s">%2$s</label>
				<br/>
				<input class="%1$s %4$s" id="%1$s" name="%5$s" type="text" data-slug="%5$s" data-shortcode="%6$s" value="%7$s" />
				<p class="description">%3$s</p>
			</div>
			',
			$args['shortcode']->slug . '_' . $args['attribute']['slug'],
			$args['attribute']['name'],
			$args['attribute']['description'],
			$args['attribute']['type'],
			$args['attribute']['slug'],
			$args['shortcode']->slug,
			$args['attribute']['default']
		);
	}

	private function get_select_input( $args = array() ) {
		return sprintf(
			'
			<div class="badgeos_input alignleft">
				<label for="%1$s">%2$s</label>
				<br/>
				<select class="%1$s %7$s" id="%1$s" name="%5$s" data-slug="%5$s" data-shortcode="%6$s">%3$s</select>
				<p class="description">%4$s</p>
			</div>
			',
			$args['shortcode']->slug . '_' . $args['attribute']['slug'],
			$args['attribute']['name'],
			$this->get_select_options( $args['attribute'] ),
			$args['attribute']['description'],
			$args['attribute']['slug'],
			$args['shortcode']->slug,
			$args['attribute']['type']
		);
	}

	private function get_select_options( $attribute = array() ) {
		$options = array();
		if ( ! empty( $attribute['values'] ) ) {
			foreach( $attribute['values'] as $value => $label ) {
				$options[] = sprintf(
					'<option %1$s value="%2$s">%3$s</option>',
					selected( $value, $attribute['default'], false ),
					$value,
					$label
				);
			}
		}
		return implode( "\n", $options );
	}
}

function badgeos_shortcodes_add_editor_button() {
	new BadgeOS_Editor_Shortcodes();
}
add_action( 'admin_init', 'badgeos_shortcodes_add_editor_button' );
