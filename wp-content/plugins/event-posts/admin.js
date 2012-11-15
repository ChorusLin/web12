jQuery(document).ready(function() {
	jQuery('.datepicker').datepicker({
		dateFormat : 'yy-mm-dd',
		firstDay: 1,
		showWeek: true,
		showOn: "button",
		buttonImage: "<?php echo plugin_dir_url( __FILE__ ) . 'jquery-ui-theme-flick/images/calendar.gif'; ?>",
		buttonImageOnly: false
	});
});
