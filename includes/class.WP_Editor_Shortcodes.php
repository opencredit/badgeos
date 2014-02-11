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
		add_action( 'admin_init', array( $this, 'add_shortcode_button' ) );
		add_filter( 'tiny_mce_version', array( $this, 'refresh_mce' ) );
		add_filter( 'mce_external_languages', array( $this, 'add_tinymce_lang' ), 10, 1 );
	}

	/**
	 * Add a button for shortcodes to the WP editor.
	 */
	public function add_shortcode_button() {
		if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') ) return;
		if ( get_user_option('rich_editing') == 'true') :
			add_filter( 'mce_external_plugins', array( $this, 'add_shortcode_tinymce_plugin' ) );
			add_filter( 'mce_buttons', array( $this, 'register_shortcode_button' ) );
		endif;
	}

	/**
	 *  function.
	 *
	 * @param array $arr
	 * @return array
	 */
	public function add_tinymce_lang( $arr ) {
		$strings = 'tinyMCE.addI18n({' . _WP_Editors::$mce_locale . ':{
    woocommerce:{
        insert: "' . esc_js( __( 'Insert Shortcode', 'woocommerce' ) ) . '",
        price_button: "' . esc_js( __( 'Product price/cart button', 'woocommerce' ) ) . '",
        product_by_sku: "' . esc_js( __( 'Product by SKU/ID', 'woocommerce' ) ) . '",
        products_by_sku: "' . esc_js( __( 'Products by SKU/ID', 'woocommerce' ) ) . '",
        product_categories: "' . esc_js( __( 'Product categories', 'woocommerce' ) ) . '",
        products_by_cat_slug: "' . esc_js( __( 'Products by category slug', 'woocommerce' ) ) . '",
        recent_products: "' . esc_js( __( 'Recent products', 'woocommerce' ) ) . '",
        featured_products: "' . esc_js( __( 'Featured products', 'woocommerce' ) ) . '",
        shop_messages: "' . esc_js( __( 'Shop Messages', 'woocommerce' ) ) . '",
        order_tracking: "' . esc_js( __( 'Order tracking', 'woocommerce' ) ) . '",
        my_account: "' . esc_js( __( 'My Account', 'woocommerce' ) ) . '",
        shop_messages_shortcode: "' . esc_js( apply_filters( "shop_messages_shortcode_tag", 'woocommerce_shop_messages' ) ) . '",
        order_tracking_shortcode: "' . esc_js( apply_filters( "woocommerce_order_tracking_shortcode_tag", 'woocommerce_order_tracking' ) ) . '"
    }
}})';

	    $arr['WooCommerceShortcodes'] = WC()->plugin_path() . '/assets/js/admin/editor_plugin_lang.php';
	    return $arr;
	}

	/**
	 * Register the shortcode button.
	 *
	 * @param array $buttons
	 * @return array
	 */
	public function register_shortcode_button( $buttons ) {
		array_push( $buttons, "|", "woocommerce_shortcodes_button" );
		return $buttons;
	}

	/**
	 * Add the shortcode button to TinyMCE
	 *
	 * @param array $plugin_array
	 * @return array
	 */
	public function add_shortcode_tinymce_plugin( $plugin_array ) {
		$plugin_array['WooCommerceShortcodes'] = WC()->plugin_url() . '/assets/js/admin/editor_plugin.js';
		return $plugin_array;
	}

	/**
	 * Force TinyMCE to refresh.
	 *
	 * @param int $ver
	 * @return int
	 */
	public function refresh_mce( $ver ) {
		$ver += 3;
		return $ver;
	}

}

new WC_Admin_Editor();
