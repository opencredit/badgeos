<?php
/**
 * CMB2 Date Range custom field type.
 * @package CMB2 Select Date Range Field Type
 */
/**
 * Sanitize the selected value.
 */
function cmb2_sanitize_select_date_range( $override_value, $value ) {
	$value = json_decode( $value, true );
	if ( is_array( $value ) ) {
		$value = array_map( 'sanitize_text_field', $value );
	} else {
		sanitize_text_field( $value );
	}

	return $value;

}
add_filter( 'cmb2_sanitize_date_range', 'cmb2_sanitize_select_date_range', 10, 2 );

function cmb2_render_select_date_range( $field, $escaped_value, $field_object_id, $field_object_type, $field_type ) {

	$url = plugin_dir_url( __FILE__ );
	wp_enqueue_style( 'jquery-ui-daterangepicker', $url. '/assets/jquery-ui-daterangepicker/jquery.comiseo.daterangepicker.css', array(), '0.4.0' );
	wp_register_script( 'moment', $url . '/assets/moment.min.js', array(), '2.10.3' );
	wp_register_script( 'jquery-ui-daterangepicker', $url. '/assets/jquery-ui-daterangepicker/jquery.comiseo.daterangepicker.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-button', 'jquery-ui-menu', 'jquery-ui-datepicker', 'moment' ), '1.4.0' );
	wp_enqueue_script( 'cmb2-daterange-picker',$url. '/assets/cmb2-daterange-picker.js', array( 'jquery-ui-daterangepicker' ), '1.0.0', true );

	$field_type->type = new CMB2_Type_Text( $field_type );
	$args = array(
		'type'  => 'text',
		'class' => 'regular-text date-range',
		'name'  => $field_type->_name(),
		'id'    => $field_type->_id(),
		'desc'  => $field_type->_desc( true ),
		'data-daterange' => json_encode( array(
			'id' => '#' . $field_type->_id(),
			'buttontext' => esc_attr( $field_type->_text( 'button_text', __( 'Select date range...' ) ) ),
		) ),
	);

	if ( $js_format = CMB2_Utils::php_to_js_dateformat( $field->args( 'date_format' ) ) ) {

		$atts = $field->args( 'attributes' );

		// Don't override user-provided datepicker values
		$data = isset( $atts['data-daterangepicker'] )
			? json_decode( $atts['data-daterangepicker'], true )
			: array();

		$data['altFormat'] = $js_format;
		$args['data-daterangepicker'] = function_exists( 'wp_json_encode' )
			? wp_json_encode( $data )
			: json_encode( $data );
	}
	// CMB2_Types::parse_args allows arbitrary attributes to be added
	$a = $field_type->parse_args( 'input', array(), $args );

	if ( $escaped_value ) {
		$escaped_value = function_exists( 'wp_json_encode' )
			? wp_json_encode( $escaped_value )
			: json_encode( $escaped_value );
	}
	$escaped_value = json_decode($escaped_value);
	printf(
		'
		<div class="cmb2-element"><input%1$s value=\'%2$s\'/><div id="%3$s-spinner" style="float:none;" class="spinner"></div></div>%4$s
		<script type="text/javascript">
			document.getElementById( \'%3$s\' ).setAttribute( \'type\', \'hidden\' );
			document.getElementById( \'%3$s-spinner\' ).setAttribute( \'class\', \'spinner is-active\' );
		</script>
		',
		$field_type->concat_attrs( $a, array( 'desc' ) ),
		$escaped_value,
		$field_type->_id(),
		$a['desc']
	);
	
}
add_action( 'cmb2_render_date_range', 'cmb2_render_select_date_range', 10, 5 );