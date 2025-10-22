/**
 * LearnDash FluentCart Integration - Admin JavaScript
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Initialize Select2 for course and group selectors
		if ($.fn.select2) {
			$('.ld-fc-select2').select2({
				placeholder: 'Select...',
				allowClear: true,
				width: '100%'
			});
		}
	});

})(jQuery);
