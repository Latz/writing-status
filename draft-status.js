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

	/**
	 * Warn the user before navigating away with unsaved meta box changes.
	 *
	 * In Gutenberg, meta boxes run inside an iframe so we attach the
	 * beforeunload listener to the parent window instead. In the classic
	 * editor window === window.parent, so the same code path works for both.
	 */
	function initUnsavedWarning() {
		var hiddenInput    = document.getElementById('draft_complete_hidden');
		var dueDateInput   = document.querySelector('input[name="draft_due_date"]');
		var prioritySelect = document.getElementById('draft_priority');

		if (!hiddenInput && !dueDateInput && !prioritySelect) {
			return;
		}

		var initial = {
			complete: hiddenInput    ? hiddenInput.value    : null,
			dueDate:  dueDateInput   ? dueDateInput.value   : null,
			priority: prioritySelect ? prioritySelect.value : null
		};

		function isDirty() {
			return (
				(hiddenInput    && hiddenInput.value    !== initial.complete) ||
				(dueDateInput   && dueDateInput.value   !== initial.dueDate)  ||
				(prioritySelect && prioritySelect.value !== initial.priority)
			);
		}

		// Gutenberg renders meta boxes in an iframe; attach to the top window.
		var targetWindow = window.parent || window;

		function beforeUnloadHandler(e) {
			if (isDirty()) {
				e.preventDefault();
				// Non-empty string required by some browsers to trigger the dialog.
				e.returnValue = 'You have unsaved changes.';
				return e.returnValue;
			}
		}

		targetWindow.addEventListener('beforeunload', beforeUnloadHandler);

		// Classic editor: remove warning on normal post save.
		var postForm = document.getElementById('post');
		if (postForm) {
			postForm.addEventListener('submit', function () {
				targetWindow.removeEventListener('beforeunload', beforeUnloadHandler);
			});
		}

		// Gutenberg: also integrate with wp.data so the editor's own
		// "unsaved changes" system is aware of our field changes.
		function markGutenbergDirty() {
			if (
				typeof window.parent.wp === 'undefined' ||
				typeof window.parent.wp.data === 'undefined'
			) {
				return;
			}
			var dispatch = window.parent.wp.data.dispatch;
			if (!dispatch) {
				return;
			}
			var editor = dispatch('core/editor');
			if (editor && typeof editor.editPost === 'function') {
				editor.editPost({
					meta: {
						_draft_complete: hiddenInput    ? hiddenInput.value    : undefined,
						_draft_due_date: dueDateInput   ? dueDateInput.value   : undefined,
						_draft_priority: prioritySelect ? prioritySelect.value : undefined
					}
				});
			}
		}

		var fields = [hiddenInput, dueDateInput, prioritySelect].filter(Boolean);
		fields.forEach(function (field) {
			field.addEventListener('change', markGutenbergDirty);
		});
		// Toggle button fires a click, not a change event on the hidden input.
		var toggleButton = document.getElementById('draft_complete_button');
		if (toggleButton) {
			toggleButton.addEventListener('click', markGutenbergDirty);
		}
	}

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			initCompletionToggle();
			initUnsavedWarning();
		});
	} else {
		initCompletionToggle();
		initUnsavedWarning();
	}
})();
