<?php
/**
 * This file creates a shortcode for showing events inline in page, using event specific options
 */

add_shortcode( 'event_posts', 'event_posts_in_page' );

function event_posts_in_page( $atts ) {
	
	// Extract short code arguments with default attributes
	$default_atts = array(
		'post_status'      => 'publish',
		'posts_per_page'   => '-1',
		'period'           => 'future',
		'order'            => 'ASC'
	);
	
	$args = wp_parse_args( $atts, $default_atts );
	
	// Unchangeable arguments to the query
	$args['post_type'] = 'event';
	
	// Sort the query by event start date-time
	$compare_type = '>=';
	if ( $args['period'] == 'past' ) {
		$compare_type = '<';
		$args['order'] = 'DESC';
	}
	$args['meta_query'] = array(
		array(
			'key' => '_start_eventtimestamp',
			'compare' => $compare_type,
			'value' => date( 'Ymd' . '0000' )
		)
	);
	$args['orderby'] = 'meta_value_num';
	
	$event_posts = new WP_Query( $args );
	$output = '';
	if ( $event_posts->have_posts() ):
		while ( $event_posts->have_posts() ):
			$output .= apply_template_to_post( $event_posts );
		endwhile;
	endif;
	wp_reset_postdata();
	
	return $output;
}

function apply_template_to_post( $event_posts ) {
	$event_posts->the_post();
	
	// Create an output buffer where the output from applying the template is catched
	ob_start( );
	
	// Apply theme on the post
	require( plugin_dir_path( __FILE__ ) . "/posts_loop_template.php" );
	ob_get_contents( );

	// Get the contents of the output buffer and release it
	return ob_get_clean( );
}

?>