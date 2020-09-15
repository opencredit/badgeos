<?php
/**
 * CMB2 Custom Credit Field Type
 * @package CMB2 Custom Credit Field
 */

function cmb2_get_state_options( $value = false ) {
	
	$settings = ( $exists = badgeos_utilities::get_option( 'badgeos_settings' ) ) ? $exists : array();
	$credits = get_posts( array(
		'post_type'         =>	trim( $settings['points_main_post_type'] ),
		'posts_per_page'    =>	-1,
		'suppress_filters'  => false,
        'orderby' => 'ID',
        'order' => 'ASC',
    ) );
	if( count( $credits ) > 0 )
		$state_options = '';
	else
		$state_options = '<option value="0" selected>'.__( 'Point Type', 'badgeos' ).'</option>';
	foreach ( $credits as $credit ) {
        $plural_name = badgeos_utilities::get_post_meta( $credit->ID, '_point_plural_name', true );
        $post_type_plural  = '';
        if( ! empty( $plural_name ) ) {
            $post_type_plural = $plural_name;
        } else {
            $post_type_plural = $credit->post_title;
        }

        $state_options .= '<option value="'. $credit->ID .'" '. selected( $value, $credit->ID, false ) .'>'. $post_type_plural .'</option>';
    }

	return $state_options;
}


/**
 * Adds a custom field type for two point fields.
 * @param  object $field             The CMB2_Field type object.
 * @param  string $value             The saved (and escaped) value.
 * @param  int    $object_id         The current post ID.
 * @param  string $object_type       The current object type.
 * @param  object $field_type_object The CMB2_Types object.
 * @return void
 */
function cmb2_render_custom_credit_field( $field, $value, $object_id, $object_type, $field_type ) {
	
	/**
     * make sure we specify each part of the value we need.
     */
	$value = wp_parse_args( $value, array(
		$field->args['_name'] => '',
		$field->args['_name'] . '_type' => ''
	) );

	?>
	 <div class="alignleft">
		<?php echo $field_type->input( array(
			'class' => 'cmb_text_small',
			'name'  => $field_type->_name( '['.$field->args['_name'].']' ),
			'id'    => $field_type->_id( $field->args['_name'] ),
			'value' => $value[$field->args['_name']],
			'desc'  => '',
		) ); ?>
	</div>
	<div class="alignleft">
		<?php echo $field_type->select( array(
			'name'    => $field_type->_name( '['.$field->args['_name'].'_type]' ),
			'id'      => $field_type->_id( $field->args['_name'].'_type' ),
			'options' => cmb2_get_state_options( $value[$field->args['_name'].'_type'] ),
			'desc'    => '',
		) ); ?>
	</div>
	
	<?php
}
add_action( 'cmb2_render_credit_field', 'cmb2_render_custom_credit_field', 10, 5 );

/**
 * Sanitize the selected value.
 */
function cmb2_sanitize_custom_credit_field_callback( $override_value, $value ) {
	if ( is_array( $value ) ) {
		foreach ( $value as $key => $saved_value ) {
			$value[$key] = sanitize_text_field( $saved_value );
		}
		return $value;
	}
	return;
}
add_filter( 'cmb2_sanitize_credit_field', 'cmb2_sanitize_custom_credit_field_callback', 10, 2 );