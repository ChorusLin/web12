<?php

if ( ! function_exists( 'add_action' ) )
	wp_die( 'You are trying to access this file in a manner not allowed.', 'Direct Access Forbidden', array( 'response' => '403' ) );

require_once( 'scripts/admin.js.php' );

class EventPostsAdmin {

	function __construct( ) {
		add_action( 'admin_init', array( $this, 'metabox_css' ) );
		add_action( 'admin_init', array( $this, 'create_metaboxes' ) );
		add_action( 'save_post', array( $this, 'save_meta' ), $priority = 1, $accepted_args = 2 );

		// Add custom CSS to style the metaboxes
		add_action('admin_print_styles-post.php', array( $this, 'metabox_css' ) );
		add_action('admin_print_styles-post-new.php', array( $this, 'metabox_css') );

		// Add scripts for the admin page
		add_action( 'admin_footer', 'ept_admin_footer_javascript' );
	}

	function metabox_css() {
		wp_enqueue_style('your-meta-box', plugins_url( 'admin.css', __FILE__ ) );
	}

	/**
	 * Create event post metaboxes
	 * @link http://codex.wordpress.org/Function_Reference/add_meta_box
	 */
	function create_metaboxes() {
		// Enqueue jQuery datepicker files used for the event dates
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_style( 'jquery.ui.theme', plugins_url( 'scripts/jquery-ui-theme-flick/jquery-ui-1.9.1.custom.css', __FILE__ ) );
		wp_enqueue_script( 'date', plugins_url( 'scripts/date-se-SE.js', __FILE__ ) );

		wp_enqueue_style('your-meta-box', plugins_url( 'admin.css', __FILE__ ) );

		add_meta_box( 'ept_event_occasion', __('Date, Time and Location'),
			array( $this, 'metabox_event_occasion' ), 'event', 'normal' );
	}

	private function metabox_controls_all_day_event( $label, $activated ) {
		$checked_attr = $activated ? ' checked="checked"' : '';
		echo '<tr>';
		echo '<td colspan="*">';
		echo '<input type="checkbox" class="all-day-checkbox" name="all_day_event" ' . $checked_attr . '/>';
		echo $label . '</td>';
		echo '</tr>';
	}

	private function metabox_controls_show_end_time( $label, $activated ) {
		$checked_attr = $activated ? ' checked="checked"' : '';
		echo '<tr>';
		echo '<td colspan="*">';
		echo '<input type="checkbox" class="show-end-time-checkbox" name="show_end_time" ' . $checked_attr . '/>';
		echo $label . '</td>';
		echo '</tr>';
	}

	private function metabox_controls_date_time( $label, $id, $datetime, $show_time ) {
		echo '<tr class="' . $id . '_time_row">';

		// Date
		echo '<td><label for="' . $id . '_date">' . $label . ':</label></td>';
		echo '<td><input type="text" class="date" ';
		echo	'name="' . $id . '_date" ';
		echo	'value="' . date('Y-m-d', $datetime ) . '" ';
		echo	'size="10" maxlength="10" /></td>';

		// Time
		$visibility = $show_time ? '' : ' style="display: none;"';
		echo '<td class="ept_time"' . $visibility . '>';
		echo '<input type="text" ';
		echo 	'name="' . $id . '_hour" ';
		echo	'class="hour" ';
		echo	'value="' . date('H', $datetime ) . '" ';
		echo	'size="2" maxlength="2" />:';

		echo '<input type="text" ';
		echo	'name="' . $id . '_minute" ';
		echo	'class="minute" ';
		echo	'value="' . date('i', $datetime ) . '" ';
		echo	'size="2" maxlength="2" /></td>';

		echo '</tr>';
	}

	private function metabox_controls_location( $label, $location ) {
		echo '<tr>';
		echo '<td><label for="location">' . $label . ':</label></td>';
		echo '<td colspan="3"><input type="text" name="event_location" value="' . $location . '" /></td>';
		echo '</tr>';
	}

	// Occasion metabox
	function metabox_event_occasion( $post, $args ) {
		global $post;

		// Use nonce for verification
		wp_nonce_field( plugin_basename( __FILE__ ), 'ep_eventposts_nonce' );
		
		$timestamps = array();
		foreach ( array('_start', '_end') as $key ) {
			$timestamps[ $key ] = get_post_meta( $post->ID, $key . '_timestamp', true );
			if ( $timestamps[ $key ] == '' ) {
				$timestamps[ $key ] = time();
			}
		}

		$location = get_post_meta( $post->ID, '_location', $single = true );
		$all_day_event = get_post_meta( $post->ID, '_all_day', $single = true );
		$show_end_time = get_post_meta( $post->ID, '_show_end_time', $single = true );
		$show_end_time == 'FALSE' ? $show_end_time = false : true;

		echo '<table>';
		$this->metabox_controls_all_day_event( __('All Day Event'), $all_day_event );
		$this->metabox_controls_date_time( __('Start'), '_start', $timestamps['_start'], $show_time = ! $all_day_event );
		$this->metabox_controls_show_end_time( __('Specify End Time'), $activated = $show_end_time );
		$this->metabox_controls_date_time( __('End'), '_end', $timestamps['_end'], $show_time = ! $all_day_event );
		$this->metabox_controls_location( __('Location'), $location );
		echo '</table>';
	}

	// Save data from all metaboxes as post metadata
	function save_meta( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		if ( ! isset( $_POST['ep_eventposts_nonce'] ) )
			return;

		if ( ! wp_verify_nonce( $_POST['ep_eventposts_nonce'], plugin_basename( __FILE__ ) ) )
			return;

		// Is the user allowed to edit the post or page?
		if ( ! current_user_can( 'edit_post', $post->ID ) )
			return;

		$all_day_event = isset( $_POST['all_day_event'] );
		$events_meta['_all_day'] = $all_day_event;

		$time_ends = array( '_start', '_end' );

		foreach ( $time_ends as $key ) {
			$date = $_POST[ $key . '_date' ];

			$hour = $min = 0;
			if ( ! $all_day_event ) {
				$hour = $_POST[ $key . '_hour' ];
				$min  = $_POST[ $key . '_minute' ];
			}

			try {
				$date = new DateTime( $date );
				$date->add( new DateInterval( 'PT' . $hour . 'H' . $min . 'M' ) );
			}
			catch ( Exception $e ) {
				// The date specified in the metabox was not in a valid format
				return;
			}

			$events_meta[ $key . '_timestamp' ] = $date->getTimestamp();
		}

		if ( isset( $_POST['show_end_time'] ) ) {
			$events_meta['_show_end_time'] = "TRUE";

			// Verify that the end date/time is after the start date/time
			if ( $events_meta['_end_timestamp'] < $events_meta['_start_timestamp'] ) {
				return;
			}
		}
		else {
			$events_meta['_end_timestamp'] = $events_meta['_start_timestamp'];
			$events_meta['_show_end_time'] = "FALSE";
		}

		// Save event location
		$events_meta['_location'] = $_POST['event_location'];

	 
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
			if ( ! $value )
				delete_post_meta( $post->ID, $key );
		}
	}
}

?>
