<?php
//widget displays achievements earned for the logged in user
class earned_user_achievements_widget extends WP_Widget {

	//process the new widget
	function earned_user_achievements_widget() {
		$widget_ops = array(
			'classname' => 'earned_user_achievements_class',
			'description' => __( 'Displays all achievements earned by the logged in user', 'badgeos' )
		);
		$this->WP_Widget( 'earned_user_achievements_widget', __( 'BadgeOS Earned User Achievements', 'badgeos' ), $widget_ops );
	}

	//build the widget settings form
	function form( $instance ) {
		$defaults = array( 'title' => 'My Badges', 'number' => '10' );
		$instance = wp_parse_args( (array) $instance, $defaults );
		$title = $instance['title'];
		$number = $instance['number'];
		?>
            <p><?php _e( 'Title', 'badgeos' ); ?>: <input class="widefat" name="<?php echo $this->get_field_name( 'title' ); ?>"  type="text" value="<?php echo esc_attr( $title ); ?>" /></p>
			<p><?php _e( 'Number to Display (0 = all)', 'badgeos' ); ?>: <input class="widefat" name="<?php echo $this->get_field_name( 'number' ); ?>"  type="text" value="<?php echo absint( $number ); ?>" /></p>
        <?php
	}

	//save and sanitize the widget settings
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		$instance['number'] = absint( $new_instance['number'] );

		return $instance;
	}

	//display the widget
	function widget( $args, $instance ) {
		extract( $args );

		echo $before_widget;

		$title = apply_filters( 'widget_title', $instance['title'] );

		if ( !empty( $title ) ) { echo $before_title . $title . $after_title; };

		$achievements = badgeos_get_user_achievements();

		if ( is_array( $achievements ) ) {

			$number_to_show = absint( $instance['number'] );
			$thecount = 0;

			wp_enqueue_script( 'badgeos-achievements' );
			wp_enqueue_style( 'badgeos-widget' );

			echo '<ul class="widget-achievements-listing">';
			foreach ( $achievements as $achievement ) {

				//exclude step CPT entries from displaying in the widget
				if ( get_post_type( $achievement->ID ) != 'step' ) {
				
					$permalink = get_permalink( $achievement->ID );
					$title = get_the_title( $achievement->ID );

					$thumb = '';
					$image_attr = wp_get_attachment_image_src( get_post_thumbnail_id( $achievement->ID ), array( 50, 50 ) );

					if ( $image_attr ) {

						$img = '<img class="wp-post-image" width="'. absint( $image_attr[1] ) .'" height="'. absint( $image_attr[2] ) .'" src="'. esc_url( $image_attr[0] ) .'">';
						$thumb = '<a style="margin-top: -'. floor( absint( $image_attr[2] ) / 2 ) .'px;" class="badgeos-item-thumb" href="'. esc_url( $permalink ) .'">' . $img .'</a>';
					}

					$class = 'widget-badgeos-item-title';
					$item_class = $image_attr ? ' has-thumb' : '';
					$item_class .= credly_is_achievement_giveable( $achievement->ID ) ? ' share-credly' : '';


					echo '<li id="widget-achievements-listing-item-'. absint( $achievement->ID ) .'" class="widget-achievements-listing-item'. esc_attr( $item_class ) .'">';

					echo $thumb;
					echo '<a class="widget-badgeos-item-title '. esc_attr( $class ) .'" href="'. esc_url( $permalink ) .'">'. esc_html( $title ) .'</a>';
					echo '</li>';

					$thecount++;

					if ( $thecount == $number_to_show && $number_to_show != 0 )
						break;
				
				}
			}

			echo '</ul><!-- widget-achievements-listing -->';

		}

		echo $after_widget;
	}

}

add_action( 'wp_ajax_achievement_send_to_credly', 'badgeos_send_to_credly_handler' );
add_action( 'wp_ajax_nopriv_achievement_send_to_credly', 'badgeos_send_to_credly_handler' );
/**
 * hook in our credly ajax function
 */
function badgeos_send_to_credly_handler() {

	if ( ! isset( $_REQUEST['ID'] ) ) {
		echo json_encode( sprintf( '<strong class="error">%s</strong>', __( 'Error: Sorry, nothing found.', 'badgeos' ) ) );
		die();
	}

	$send_to_credly = $GLOBALS['badgeos_credly']->post_credly_user_badge( $_REQUEST['ID'] );

	if ( $send_to_credly ) {

		echo json_encode( sprintf( '<strong class="success">%s</strong>', __( 'Success: Sent to Credly!', 'badgeos' ) ) );
		die();

	} else {

		echo json_encode( sprintf( '<strong class="error">%s</strong>', __( 'Error: Sorry, Send to Credly Failed.', 'badgeos' ) ) );
		die();

	}
}
