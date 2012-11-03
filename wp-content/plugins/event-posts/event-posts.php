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

/**
 * Flushes rewrite rules on plugin activation to ensure event posts don't 404
 * http://codex.wordpress.org/Function_Reference/flush_rewrite_rules
 */

function ep_eventposts_activation() {
	ep_eventposts();
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'ep_eventposts_activation' );

include( 'events-in-page.php' );

function ep_eventposts() {
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

add_action( 'init', 'ep_eventposts' );

/**
 * Adds event post metaboxes for start time and end time
 * http://codex.wordpress.org/Function_Reference/add_meta_box
 *
 * We want two time event metaboxes, one for the start time and one for the end time.
 * Two avoid repeating code, we'll just pass the $identifier in a callback.
 * If you wanted to add this to regular posts instead, just swap 'event' for 'post' in add_meta_box.
 */
function ep_eventposts_metaboxes() {
	// Enqueue jQuery datepicker used for the event dates
	wp_enqueue_script( 'jquery-ui-datepicker' );
	wp_enqueue_style( 'jquery.ui.theme', plugins_url( '/jquery-ui-theme-flick/jquery-ui-1.9.1.custom.css', __FILE__ ) );

	add_meta_box( 'ept_metabox_event_date_start', 'Start Date and Time', 'ept_metabox_event_date', 'event', 'side', 'default', array( 'id' => '_start') );
	add_meta_box( 'ept_metabox_event_date_end', 'End Date and Time', 'ept_metabox_event_date', 'event', 'side', 'default', array('id'=>'_end') );
	add_meta_box( 'ept_event_location', 'Event Location', 'ept_event_location', 'event', 'normal', 'default', array('id'=>'_end') );
}
add_action( 'admin_init', 'ep_eventposts_metaboxes' );


// Metabox HTML
function ept_metabox_event_date($post, $args) {
	$metabox_id = $args['args']['id'];
	global $post, $wp_locale;

	// Use nonce for verification
	wp_nonce_field( plugin_basename( __FILE__ ), 'ep_eventposts_nonce' );

	// $time_adj = current_time( 'timestamp' );
	$timestamp = get_post_meta( $post->ID, $metabox_id . '_timestamp', true );
	if ( $timestamp == '' ) {
		$timestamp = time();
	}
	
	$year = date( 'Y', $timestamp );
	$month = date( 'n', $timestamp );
	$day = date( 'd', $timestamp );
	$hour = date( 'H', $timestamp );
	$min = date( 'i', $timestamp );

	$date = $year . '-' . $month . '-' . $day;
	echo '<input type="text" class="datepicker" name="' . $metabox_id . '_date" value="' . $date . '" size="10" maxlength="10" />';

	// echo '<input type="text" name="' . $metabox_id . '_year" value="' . $year . '" size="4" maxlength="4" />';

	// $month_s = '<select name="' . $metabox_id . '_month">';
	// for ( $i = 1; $i <= 12; $i++ ) {
	// 	$month_s .= "\t\t\t" . '<option value="' . zeroise( $i, 2 ) . '"';
	// 	if ( $i == $month )
	// 		$month_s .= ' selected="selected"';
	// 	$month_s .= '>' . $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) ) . "</option>\n";
	// }
	// $month_s .= '</select>';

	// echo $month_s;
	// echo '<input type="text" name="' . $metabox_id . '_day" value="' . $day  . '" size="2" maxlength="2" />';
	echo '<input type="text" name="' . $metabox_id . '_hour" value="' . $hour . '" size="2" maxlength="2"/>:';
	echo '<input type="text" name="' . $metabox_id . '_minute" value="' . $min . '" size="2" maxlength="2" />';
}

function ept_event_location() {
	global $post;
	// Use nonce for verification
	wp_nonce_field( plugin_basename( __FILE__ ), 'ep_eventposts_nonce' );
	// The metabox HTML
	$event_location = get_post_meta( $post->ID, '_event_location', true );
	echo '<label for="_event_location">Location:</label>';
	echo '<input type="text" name="_event_location" value="' . $event_location  . '" />';
}

// Save the Metabox Data

function ep_eventposts_save_meta( $post_id, $post ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		return;

	if ( ! isset( $_POST['ep_eventposts_nonce'] ) )
		return;

	if ( ! wp_verify_nonce( $_POST['ep_eventposts_nonce'], plugin_basename( __FILE__ ) ) )
		return;

	// Is the user allowed to edit the post or page?
	if ( ! current_user_can( 'edit_post', $post->ID ) )
		return;

	// OK, we're authenticated: we need to find and save the data
	// We'll put it into an array to make it easier to loop though
	$metabox_ids = array( '_start', '_end' );

	foreach ( $metabox_ids as $key ) {
		$year   = $_POST[$key . '_year'];
		$month  = $_POST[$key . '_month'];
		$day    = $_POST[$key . '_day'];
		$hour   = $_POST[$key . '_hour'];
		$min    = $_POST[$key . '_minute'];
		
		$year = ( $year < 0 ) ? date('Y') : $year;
		$month = ( $month <= 0 || $month > 12 ) ? date('n') : $month;
		$day = sprintf( '%02d', $day );
		$day = ( $day > 31 ) ? 31 : $day;
		$day = ( $day <= 0 ) ? date('j') : $day;
		$hour = sprintf( '%02d', $hour );
		$hour = ( $hour > 23 ) ? 23 : $hour;
		$min = sprintf( '%02d', $min );
		$min = ( $min > 59 ) ? 59 : $min;
		
		$events_meta[ $key . '_timestamp' ] = mktime( $hour, $min, 0, $month, $day, $year );
    }
 
	// Add values of $events_meta as custom fields
	foreach ( $events_meta as $key => $value ) { // Cycle through the $events_meta array!
		// Don't store custom data twice
		if ( $post->post_type == 'revision' )
			return;
		
		// If $value is an array, make it a CSV (unlikely)
		$value = implode( ',', (array) $value );
		
		// If the custom field already has a value
		if ( get_post_meta( $post->ID, $key, FALSE ) ) {
			update_post_meta( $post->ID, $key, $value );
		// If the custom field doesn't have a value
		} else {
			add_post_meta( $post->ID, $key, $value );
		}
		// Delete if blank
		if ( !$value )
			delete_post_meta( $post->ID, $key );
	}
}

add_action( 'save_post', 'ep_eventposts_save_meta', $priority = 1, $accepted_args = 2 );

/**
 * Helpers to display the date on the front end
 */

// Get the Month Abbreviation
 
function eventposttype_get_the_month_abbr($month) {
	global $wp_locale;
	for ( $i = 1; $i < 13; $i++ ) {
		if ( $i == $month )
			$monthabbr = $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) );
	}
    return $monthabbr;
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

// Add custom CSS to style the metabox
add_action('admin_print_styles-post.php', 'ep_eventposts_css');
add_action('admin_print_styles-post-new.php', 'ep_eventposts_css');

function ep_eventposts_css() {
	wp_enqueue_style('your-meta-box', plugin_dir_url( __FILE__ ) . '/event-post-metabox.css');
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
?>
