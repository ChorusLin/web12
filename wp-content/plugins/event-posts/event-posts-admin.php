<?php

if ( !function_exists( 'add_action' ) )
	wp_die( 'You are trying to access this file in a manner not allowed.', 'Direct Access Forbidden', array( 'response' => '403' ) );

class EventPostsAdmin {

	function __construct( ) {
		add_action( 'admin_init', array( $this, 'create_metaboxes' ) );
		add_action( 'save_post', array( $this, 'save_meta' ), $priority = 1, $accepted_args = 2 );

		// Add custom CSS to style the metaboxes
		add_action('admin_print_styles-post.php', array( $this, 'metabox_css' ) );
		add_action('admin_print_styles-post-new.php', array( $this, 'metabox_css') );

		// Add jQuery scripts for the admin page
		add_action( 'admin_footer', array( $this, 'admin_footer_datepicker' ) );
	}

	function metabox_css() {
		wp_enqueue_style('your-meta-box', plugin_dir_url( __FILE__ ) . '/event-post-metabox.css');
	}

	/**
	 * Create event post metaboxes
	 * @link http://codex.wordpress.org/Function_Reference/add_meta_box
	 */
	function create_metaboxes() {
		// Enqueue jQuery datepicker files used for the event dates
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_style( 'jquery.ui.theme', plugins_url( '/jquery-ui-theme-flick/jquery-ui-1.9.1.custom.css', __FILE__ ) );

		add_meta_box( 'ept_metabox_event_date_start', 'Start Date and Time', 
			array( $this, 'metabox_event_date' ), 'event', 'side', 'default', array( 'id' => '_start') );
		add_meta_box( 'ept_metabox_event_date_end', 'End Date and Time',
		   	array( $this, 'metabox_event_date' ), 'event', 'side', 'default', array('id'=>'_end') );
		add_meta_box( 'event_location', 'Event Location',
			array( $this, 'event_location' ), 'event', 'normal', 'default', array('id'=>'_end') );
	}

	// Metabox HTML
	function metabox_event_date( $post, $args ) {
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

		// Metabox HTML
		echo '<input type="text" class="datepicker" name="' . $metabox_id . '_date" value="' . $date . '" size="10" maxlength="10" />';
		echo '<input type="text" name="' . $metabox_id . '_hour" value="' . $hour . '" size="2" maxlength="2"/>:';
		echo '<input type="text" name="' . $metabox_id . '_minute" value="' . $min . '" size="2" maxlength="2" />';
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

		// OK, we're authenticated: we need to find and save the data
		// We'll put it into an array to make it easier to loop though
		$metabox_ids = array( '_start', '_end' );

		foreach ( $metabox_ids as $key ) {
			$date = $_POST[$key . '_date'];
			$hour   = $_POST[$key . '_hour'];
			$min    = $_POST[$key . '_minute'];

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

		// Verify that the end date/time is after the start date/time
		if ( $events_meta[ '_end_timestamp' ] < $events_meta[ '_start_timestamp' ] ) {
			return;
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

	function event_location( ) {
		global $post;
		// Use nonce for verification
		wp_nonce_field( plugin_basename( __FILE__ ), 'ep_eventposts_nonce' );

		// The metabox HTML
		$event_location = get_post_meta( $post->ID, '_event_location', true );
		echo '<label for="_event_location">Location:</label>';
		echo '<input type="text" name="_event_location" value="' . $event_location  . '" />';
	}


	function admin_footer_datepicker() {
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
}

?>
