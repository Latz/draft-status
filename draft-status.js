/**
 * Draft Status Indexer JavaScript
 *
 * Handles the completion status toggle button functionality
 * in the post editor meta box.
 *
 * @package DraftStatus
 * @since 1.5.0
 */

(function () {
	'use strict';

	/**
	 * Initialize the completion status toggle button
	 */
	function initCompletionToggle() {
		var button = document.getElementById('draft_complete_button');
		var hiddenInput = document.getElementById('draft_complete_hidden');

		if (!button || !hiddenInput) {
			return;
		}

		var iconSpan = button.querySelector('.draft-status-icon');
		var textSpan = button.querySelector('.draft-status-text');

		button.addEventListener('click', function () {
			var isComplete = hiddenInput.value === 'yes';
			var newValue = isComplete ? 'no' : 'yes';

			// Update hidden input
			hiddenInput.value = newValue;

			// Update button state
			if (newValue === 'yes') {
				button.classList.remove('is-incomplete');
				button.classList.add('is-complete');
				button.setAttribute('aria-pressed', 'true');
				iconSpan.textContent = '✓';
				textSpan.textContent = button.getAttribute('data-complete-text') || 'Complete';
			} else {
				button.classList.remove('is-complete');
				button.classList.add('is-incomplete');
				button.setAttribute('aria-pressed', 'false');
				iconSpan.textContent = '✗';
				textSpan.textContent = button.getAttribute('data-incomplete-text') || 'Incomplete';
			}
		});
	}

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initCompletionToggle);
	} else {
		initCompletionToggle();
	}
})();
