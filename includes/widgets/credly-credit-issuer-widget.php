<?php
//widget displays Credly credit issuer badge
class credly_credit_issuer_widget extends WP_Widget {

	//process the new widget
	function credly_credit_issuer_widget() {
		$widget_ops = array(
			'classname' => 'credly_credit_issuer_class',
			'description' => __( 'Display the Credly Credit Issuer badge', 'badgeos' )
		);
		$this->WP_Widget( 'credly_credit_issuer_widget', __( 'BadgeOS Credly Credit Issuer', 'badgeos' ), $widget_ops );
	}

	//build the widget settings form
	function form( $instance ) {
		$defaults = array( 'credly_profile_url' => '' );
		$instance = wp_parse_args( (array) $instance, $defaults );
		$credly_profile_url = $instance['credly_profile_url'];
		?>
            <p><?php _e( 'Credly Profile URL', 'badgeos' ); ?>: <input class="widefat" name="<?php echo $this->get_field_name( 'credly_profile_url' ); ?>"  type="text" value="<?php echo esc_url( $credly_profile_url ); ?>" /></p>
        <?php
	}

	//save and sanitize the widget settings
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['credly_profile_url'] = ( ! empty( $new_instance['credly_profile_url'] ) ? esc_url( $new_instance['credly_profile_url'] ) : '' );

		return $instance;
	}

	//display the widget
	function widget( $args, $instance ) {
		extract( $args );

		echo $before_widget;

		//generate the URL
		$url = ( ! empty( $instance['credly_profile_url'] ) ? esc_url( $instance['credly_profile_url'] ) : 'https://credly.com' );
		
		//display the badge
		echo '<p><a href="'. esc_url( $url ) .'" target="_blank"><img src="' .esc_url( $GLOBALS['badgeos']->directory_url .'images/credly-credit-issuer.png' ). '" alt="'.__( 'Credly Credit Issuer', 'badgeos' ) .'" /></a></p>';

		echo $after_widget;
	}

}
