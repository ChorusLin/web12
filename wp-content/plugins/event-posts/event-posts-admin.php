<?php

if ( !function_exists( 'add_action' ) )
	wp_die( 'You are trying to access this file in a manner not allowed.', 'Direct Access Forbidden', array( 'response' => '403' ) );

class EventPostsAdmin {

	function __construct( ) {
		add_action( 'admin_init', array( $this, 'metabox_css' ) );
		add_action( 'admin_init', array( $this, 'create_metaboxes' ) );
		add_action( 'save_post', array( $this, 'save_meta' ), $priority = 1, $accepted_args = 2 );

		// Add custom CSS to style the metaboxes
		add_action('admin_print_styles-post.php', array( $this, 'metabox_css' ) );
		add_action('admin_print_styles-post-new.php', array( $this, 'metabox_css') );

		// Add jQuery scripts for the admin page
		add_action( 'admin_footer', array( $this, 'admin_footer_datepicker' ) );
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
		wp_enqueue_style( 'jquery.ui.theme', plugins_url( 'jquery-ui-theme-flick/jquery-ui-1.9.1.custom.css', __FILE__ ) );

		//wp_enqueue_script( 'jquery-validate' plugins_url( 'jquery/jquery-validate-blabla.js' ) ); // TODO: Hämta hem jQuery-validate

		wp_enqueue_style('your-meta-box', plugins_url( 'admin.css', __FILE__ ) );

		add_meta_box( 'ept_event_occasion', __('Date, Time and Location'),
			array( $this, 'metabox_event_occasion' ), 'event', 'normal' );
	}

	private function metabox_date_time_controls( $label, $id, $datetime ) {
		echo '<tr>';
		// Date
		echo '<td><label for="' . $id . '_date">' . $label . ':</label></td>';
		echo '<td><input type="text" class="datepicker date" name="' . $id . '_date" value="' . date('Y-m-d', $datetime ) . '" size="10" maxlength="10" /></td>';
		// Time
		echo '<td><input type="text" name="' . $id . '_hour" value="' . date('H', $datetime ) . '" size="2" maxlength="2" />:';
		echo '<input type="text" name="' . $id . '_minute" value="' . date('i', $datetime ) . '" size="2" maxlength="2" /></td>';
		echo '</tr>';
	}

	private function metabox_location_controls( $label, $location ) {
		echo '<tr>';
		echo '<td><label for="location">' . $label . ':</label></td>';
		echo '<td colspan="3"><input type="text" name="event_location" value=' . $location . ' /></td>';
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

		$location = get_post_meta( $post->ID, '_location', true );

		echo '<table>';
		$this->metabox_date_time_controls( __('Start'), '_start', $timestamps['_start'] );
		$this->metabox_date_time_controls( __('End'), '_end', $timestamps['_end'] );
		$this->metabox_location_controls( __('Location'), $location );
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

		// OK, we're authenticated: we need to find and save the data
		$metabox_ids = array( '_start', '_end' );

		foreach ( $metabox_ids as $key ) {
			$date = $_POST[ $key . '_date' ];
			$hour = $_POST[ $key . '_hour' ];
			$min  = $_POST[ $key . '_minute' ];

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

		// Get the event location
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

	function admin_footer_datepicker() {
		?>
		<script type="text/javascript">
		( function( $ ) {
			$(document).ready(function() {
				$('.datepicker').datepicker({
					dateFormat : 'yy-mm-dd',
					firstDay: 1,
					showWeek: true,
					showOn: "button",
					buttonImage: "<?php echo plugin_dir_url( __FILE__ ) . 'jquery-ui-theme-flick/images/calendar.gif'; ?>",
					buttonImageOnly: false
				});
				$('.event-date').keyup( function() {
					$(this).validate({
						rules: {
							field: {
								date: true
							}
						}
					})
				});
			});
		})( jQuery );
		</script>
		<?php
	}
}

?>
