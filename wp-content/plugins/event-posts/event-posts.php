<?php
/*
Plugin Name: Event Posts
Plugin URI: http://www.wptheming.com
Description: Creates a custom post type for events with associated metaboxes.
Version: 0.1
Author: Devin Price
Author URI: http://www.wptheming.com
License: GPLv2 or later
*/


if ( !function_exists( 'add_action' ) )
	wp_die( 'You are trying to access this file in a manner not allowed.', 'Direct Access Forbidden', array( 'response' => '403' ) );

function ep_eventposts_activation() {
	ep_eventposts();

	/**
	 * Flushes rewrite rules on plugin activation to ensure event posts don't 404
	 * http://codex.wordpress.org/Function_Reference/flush_rewrite_rules
	 */
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'ep_eventposts_activation' );

require_once ( 'events-in-page.php' );
require_once ( 'event-posts-admin.php' );

class EventPosts {
	function __construct() {
		add_action( 'init', array( $this, 'register' ) );

		new EventPostsAdmin();
	}

	function register() {
		/**
		 * Enable the event custom post type
		 * http://codex.wordpress.org/Function_Reference/register_post_type
		 */
		$labels = array(
			'name' => __( 'Events', 'eventposttype' ),
			'singular_name' => __( 'Event', 'eventposttype' ),
			'add_new' => __( 'Add New Event', 'eventposttype' ),
			'add_new_item' => __( 'Add New Event', 'eventposttype' ),
			'edit_item' => __( 'Edit Event', 'eventposttype' ),
			'new_item' => __( 'Add New Event', 'eventposttype' ),
			'view_item' => __( 'View Event', 'eventposttype' ),
			'search_items' => __( 'Search Events', 'eventposttype' ),
			'not_found' => __( 'No events found', 'eventposttype' ),
			'not_found_in_trash' => __( 'No events found in trash', 'eventposttype' )
		);

		$args = array(
			'labels' => $labels,
			'public' => true,
			'supports' => array( 'title', 'editor', 'thumbnail' ),
			'capability_type' => 'post',
			'rewrite' => array("slug" => "event"), // Permalinks format
			'menu_position' => 5,
			'menu_icon' => plugin_dir_url( __FILE__ ) . '/images/calendar-icon.gif',  // Icon Path
			'has_archive' => true,
			'taxonomies' => array( 'category' )
		); 

		register_post_type( 'event', $args );
	}
}


// Display the date
function eventposttype_get_the_event_date() {
    global $post;
    $eventdate = '';
    $month = get_post_meta( $post->ID, '_month', true );
    $eventdate = eventposttype_get_the_month_abbr($month);
    $eventdate .= ' ' . get_post_meta( $post->ID, '_day', true ) . ',';
    $eventdate .= ' ' . get_post_meta( $post->ID, '_year', true );
    $eventdate .= ' at ' . get_post_meta( $post->ID, '_hour', true );
    $eventdate .= ':' . get_post_meta( $post->ID, '_minute', true );
    echo $eventdate;
}


/**
 * Customize Event Query using Post Meta
 * 
 * @link http://www.billerickson.net/customize-the-wordpress-query/
 * @param object $query data
 *
 */
function ep_event_query( $query ) {

	// http://codex.wordpress.org/Function_Reference/current_time
	$current_time = current_time('mysql'); 
	list( $today_year, $today_month, $today_day, $hour, $minute, $second ) = split( '([^0-9])', $current_time );
	$current_timestamp = $today_year . $today_month . $today_day . $hour . $minute;

	global $wp_the_query;
	
	if ( $wp_the_query === $query && ! is_admin() && is_post_type_archive( 'event' ) ) {
		$meta_query = array(
			array(
				'key' => '_start_timestamp',
				'value' => $current_timestamp,
				'compare' => '>'
			)
		);
		$query->set( 'meta_query', $meta_query );
		$query->set( 'orderby', 'meta_value_num' );
		$query->set( 'meta_key', '_start_timestamp' );
		$query->set( 'order', 'ASC' );
		$query->set( 'posts_per_page', '2' );
	}

}

add_action( 'pre_get_posts', 'ep_event_query' );

function ept_admin_footer_datepicker() {
	?>
	<script type="text/javascript">
	jQuery(document).ready(function(){
		jQuery('.datepicker').datepicker({
			dateFormat : 'yy-mm-dd',
			firstDay: 1,
			showWeek: true,
			showOn: "button",
			buttonImage: "<?php echo plugin_dir_url( __FILE__ ) . 'jquery-ui-theme-flick/images/calendar.gif'; ?>",
			buttonImageOnly: false
		});
	});
	</script>
	<?php
}
add_action( 'admin_footer', 'ept_admin_footer_datepicker' );


function init_event_posts( ) {
	new EventPosts( );
}
add_action( 'plugins_loaded', 'init_event_posts' );
?>
