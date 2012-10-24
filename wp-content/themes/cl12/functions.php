<?php
add_filter( 'twentyeleven_color_schemes', 'cl12_color_schemes' );
add_action( 'twentyeleven_enqueue_color_scheme', 'cl12_enqueue_color_scheme' );

function cl12_color_schemes( $color_schemes ) {
	$color_schemes['purple'] = array(
		'value' => 'purple',
		'label' => __( 'Purple', 'cl12' ),
		'thumbnail' => get_stylesheet_directory_uri() . '/inc/images/purple.png',
		'default_link_color' => '#854898'
	);
	return $color_schemes;
}

function cl12_enqueue_color_scheme( $color_scheme ) {
	if ( 'purple' == $color_scheme ) {
		wp_enqueue_style( 'purple', get_stylesheet_directory_uri() . '/colors/purple.css', array(), null );
	}
}

?>