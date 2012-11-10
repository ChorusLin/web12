<?php

if ( ! function_exists( 'add_action' ) )
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

	private function metabox_all_day_event_controls( $label, $enabled ) {
		$enabled_attr = $enabled ? ' checked="checked"' : '';
		echo '<tr>';
		echo '<td colspan="*">';
		echo '<input type="checkbox" class="all-day-checkbox" name="all_day_event" value="whole_day" ' . $enabled_attr . '/>';
		echo $label . '</td>';
		echo '<tr>';
	}

	private function metabox_date_time_controls( $label, $id, $datetime, $show_time ) {
		// All day event?
		echo '<tr>';

		// Date
		echo '<td><label for="' . $id . '_date">' . $label . ':</label></td>';
		echo '<td><input type="text" class="datepicker date" ';
		echo	'name="' . $id . '_date" ';
		echo	'value="' . date('Y-m-d', $datetime ) . '" ';
		echo	'size="10" maxlength="10" /></td>';

		// Time
		$visibility = $show_time ? '' : ' style="display: none;"';
		echo '<td class="ept_time"' . $visibility . '>';
		echo '<input type="text" ';
		echo 	'name="' . $id . '_hour" ';
		echo	'value="' . date('H', $datetime ) . '" ';
		echo	'size="2" maxlength="2" />:';
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

		$location = get_post_meta( $post->ID, '_location', $single = true );
		$all_day_event = get_post_meta( $post->ID, '_all_day', $single = true );

		echo '<table>';
		$this->metabox_all_day_event_controls( __('All Day Event'), $all_day_event );
		$this->metabox_date_time_controls( __('Start'), '_start', $timestamps['_start'], $show_time = ! $all_day_event );
		$this->metabox_date_time_controls( __('End'), '_end', $timestamps['_end'], $show_time = ! $all_day_event );
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

				// Hide/show the time input boxes depending on whether the event is marked as All day
				function update_checkbox_visibility( checkbox ) {
					if ( checkbox.is(':checked') ) {
						$('.ept_time').hide();
					}
					else {
						$('.ept_time').show();
					}
				}

				// Start with time inputs shown or hidden
				update_checkbox_visibility( $('.all-day-checkbox') );
				// Update when the checkbox status changes
				$('.all-day-checkbox').click( function() {
					update_checkbox_visibility( $(this) );
				});
			});
		})( jQuery );
		</script>
		<?php
	}
}

?>
