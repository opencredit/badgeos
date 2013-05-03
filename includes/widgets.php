<?php

// use widgets_init action hook to execute custom function
add_action( 'widgets_init', 'badgeos_register_widgets' );

 //register our widget
function badgeos_register_widgets() {

	register_widget( 'earned_user_achievements_widget' );

}

require_once( badgeos_get_directory_path() .'includes/widgets/earned-user-achievements-widget.php' );

