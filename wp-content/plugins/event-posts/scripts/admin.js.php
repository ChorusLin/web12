<?php function ept_admin_footer_javascript() { ?>
<script type="text/javascript">

function isValidDate( dateString ) {
	date = Date.parse( dateString );
	return date != null;
}

function parseHour( hourString ) {
	hour = parseInt( hourString );
	if ( hour == NaN )
		throw Error
	Date.validateHour(hour);
	return hour;
}

function parseMinute( minuteString ) {
	minute = parseInt( minuteString );
	if ( minute == NaN )
		throw Error
	Date.validateMinute(minute);
	return minute;
}

( function( $ ) {
	$(document).ready(function() {

		// Add a jQuery UI Datepicker to the date text boxes
		$('#ept_event_occasion .date').datepicker({
			dateFormat : 'yy-mm-dd',
			firstDay: 1,
			showWeek: true,
			showOn: "button",
			buttonImage: "<?php echo plugin_dir_url( __FILE__ ) . 'jquery-ui-theme-flick/images/calendar.gif'; ?>",
			buttonImageOnly: false
		});

		function getDateFromRow( table, startOrEnd ) {
			row = table.find( '._' + startOrEnd + '_time_row' );
			
			try {
				date = Date.parse( row.find('input.date').val() );

				hour = parseHour( row.find('input.hour').val() );
				date.addHours( hour );

				minute = parseMinute( row.find('input.minute').val() );
				date.addMinutes( minute );
			}
			catch( err ) {
				console.log( err );
			}
			return date;
		}

		function adjustEndTime( occasionTable ) {
			startDate = getDateFromRow( occasionTable, 'start');
			endDate = getDateFromRow( occasionTable, 'end');

			if ( endDate < startDate ) {
				endDate.set( { year: startDate.getFullYear(), month: startDate.getMonth(), day: startDate.getDate() } );
			}
			if ( endDate < startDate ) {
				endDate.set( { hour: startDate.getHours() + 1, minute: startDate.getMinutes() } );
			}

			occasionTable.find('._end_time_row input.date').val( $.datepicker.formatDate( 'yy-mm-dd', endDate ) );
			occasionTable.find('._end_time_row input.hour').val( endDate.getHours() );
			minute = endDate.getMinutes().toString();
			if ( minute.length < 2 ) {
				minute = "0" + minute;
			}
			occasionTable.find('._end_time_row input.minute').val( minute  );
		}

		// Validate changes of the date inputs
		$('#ept_event_occasion .date').change( function() {
			if ( isValidDate( $(this).val() ) ) {
				$(this).removeClass('error');
				$(this).val( $.datepicker.formatDate( 'yy-mm-dd', Date.parse( $(this).val() ) ) );

				adjustEndTime( $(this).closest('table') );
			}
			else {
				$(this).addClass('error');
			}
		});

		// Validate hour input changes
		$('#ept_event_occasion .hour').change( function() {
			try {
				parseHour( $(this).val() );
				$(this).removeClass('error');
				adjustEndTime( $(this).closest('table') );
			}
			catch ( err ) {
				$(this).addClass('error');
			}
		});

		// Validate minute input changes
		$('#ept_event_occasion .minute').change( function() {
			try {
				parseMinute( $(this).val() );
				$(this).removeClass('error');
				adjustEndTime( $(this).closest('table') );
			}
			catch ( err ) {
				$(this).addClass('error');
			}
		});

		// Hide/show the time input boxes depending on whether the event is marked as All day
		function updateTimeControlsVisibility( checkbox ) {
			if ( checkbox.is(':checked') ) {
				$('.ept_time').hide();
			}
			else {
				$('.ept_time').show();
			}
		}

		// Start with time inputs shown or hidden
		updateTimeControlsVisibility( $('.all-day-checkbox') );
		// Update when the checkbox status changes
		$('.all-day-checkbox').click( function() {
			updateTimeControlsVisibility( $(this) );
		});

		// Change visibility of "end time" controls based on selection of "Specify end time"
		function updateEndTimeVisibility( checkbox ) {
			if ( checkbox.is(':checked') ) {
				$('._end_time_row').show();
			}
			else {
				$('._end_time_row').hide();
			}
		}
		
		updateEndTimeVisibility( $('.show-end-time-checkbox') );
		$('.show-end-time-checkbox').click( function() {
			updateEndTimeVisibility( $(this) );
		});

	});
})( jQuery );
</script>

<?php } ?>
