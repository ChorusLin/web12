<?php
/*
Plugin Name: CL Upcoming
Description: Denna widget hämtar den händelse som är närmast på gång i kalendariet och visar i en fin cirkel
Version: 0.1
Author: Jonas Frid
*/

class CL_Upcoming_Widget extends WP_Widget {
	
	public function __construct() {
		parent::__construct(
			'cl_upcoming_widget',
			'CL kommande händelser',
			array( 'description' => __( 'Visa kommande händelser i kalendariet', 'text_domain' ), )
		);
		
		wp_register_style( 'cl-upcoming-style', plugins_url( 'cl-upcoming.css', __FILE__ ) );
		
		// $widget_ops = array(
		// 	'classname' => 'upcoming-events'
		// );
		// $control_ops = array();
		// $this->WP_Widget( 'CL_Upcoming_widget', 'CL Kalendarie-aktuellt', $widget_ops, $control_ops );
	}
	
	function form( $instance ) {
		echo '<p class="no-options-widget">' . __('There are no options for this widget.') . '</p>';
		return 'noform';
	}
	
	function update( $new_instance, $old_instance ) {
		
	}
	
	public function widget( $args, $instance ) {
		// used when the sidebar calls in the widget
		require_once( plugin_dir_path(__FILE__) . '/in_sidebar.php' );
	}
}

function cl_upcoming_add_stylesheet() {
	wp_enqueue_style( 'cl-upcoming-style' );
}

add_action( 'wp_enqueue_scripts', 'cl_upcoming_add_stylesheet' );
add_action( 'widgets_init', create_function( '', 'register_widget( "cl_upcoming_widget" );' ) );

?>