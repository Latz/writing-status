/**
 * Writing Status Indexer JavaScript
 *
 * Handles the completion status toggle button functionality
 * in the post editor meta box.
 *
 * @package WritingStatus
 * @since 1.5.0
 */

(function () {
	'use strict';

	/**
	 * Initialize the completion status toggle button
	 */
	function initCompletionToggle() {
		var button = document.getElementById('writing_complete_button');
		var hiddenInput = document.getElementById('writing_complete_hidden');

		if (!button || !hiddenInput) {
			return;
		}

		var iconSpan = button.querySelector('.writing-status-icon');
		var textSpan = button.querySelector('.writing-status-text');

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
		var hiddenInput    = document.getElementById('writing_complete_hidden');
		var dueDateInput   = document.querySelector('input[name="writing_due_date"]');
		var prioritySelect = document.getElementById('writing_priority');

		if (!hiddenInput && !dueDateInput && !prioritySelect) {
			return;
		}

		// Explicit flag: set true on any field change, reset after a confirmed save.
		// Avoids DOM-comparison pitfalls when Gutenberg saves without reloading the page.
		var dirty = false;

		var targetWindow = window.parent || window;

		function beforeUnloadHandler(e) {
			if (dirty) {
				e.preventDefault();
				e.returnValue = 'You have unsaved changes.';
				return e.returnValue;
			}
		}

		targetWindow.addEventListener('beforeunload', beforeUnloadHandler);

		function removeBeforeUnload() {
			targetWindow.removeEventListener('beforeunload', beforeUnloadHandler);
		}

		// Clean up when this frame navigates or unloads (Gutenberg iframe reload, classic editor redirect).
		window.addEventListener('unload', removeBeforeUnload);

		// Classic editor: clear on form submit so the save redirect is clean.
		var postForm = document.getElementById('post');
		if (postForm) {
			postForm.addEventListener('submit', removeBeforeUnload);
		}

		// Mark the meta box dirty and sync Gutenberg's own unsaved-changes state.
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
						_writing_complete: hiddenInput    ? hiddenInput.value    : undefined,
						_writing_due_date: dueDateInput   ? dueDateInput.value   : undefined,
						_writing_priority: prioritySelect ? prioritySelect.value : undefined
					}
				});
			}
		}

		function onFieldChange() {
			dirty = true;
			markGutenbergDirty();
		}

		var fields = [hiddenInput, dueDateInput, prioritySelect].filter(Boolean);
		fields.forEach(function (field) {
			field.addEventListener('change', onFieldChange);
		});
		var toggleButton = document.getElementById('writing_complete_button');
		if (toggleButton) {
			toggleButton.addEventListener('click', onFieldChange);
		}

		// Gutenberg: reset dirty after an explicit (non-autosave) save succeeds.
		// wp.data.subscribe fires on every state change; we track the saving
		// transition to detect completion without depending on an iframe reload.
		if (
			typeof window.parent.wp !== 'undefined' &&
			typeof window.parent.wp.data !== 'undefined' &&
			typeof window.parent.wp.data.subscribe === 'function'
		) {
			var wasSaving     = false;
			var wasAutosaving = false;

			var unsubscribe = window.parent.wp.data.subscribe(function () {
				var select = window.parent.wp.data.select('core/editor');
				if (!select) {
					return;
				}
				var isSaving     = select.isSavingPost();
				var isAutosaving = typeof select.isAutosavingPost === 'function' && select.isAutosavingPost();

				// Transition: was saving (not autosaving) → finished successfully.
				if (wasSaving && !wasAutosaving && !isSaving) {
					if (typeof select.didPostSaveRequestSucceed === 'function' &&
						select.didPostSaveRequestSucceed()) {
						dirty = false;
					}
				}

				wasSaving     = isSaving;
				wasAutosaving = isAutosaving;
			});

			// Unsubscribe when the frame unloads to prevent leaking listeners.
			window.addEventListener('unload', unsubscribe);
		}
	}

	/**
	 * Pre-populate Quick Edit fields with the current post's Writing Status values.
	 *
	 * WordPress renders one shared Quick Edit form per table; field values must be
	 * injected via JS each time the row opens. We wrap inlineEditPost.edit and read
	 * data attributes that displayCompletionColumn outputs in the hidden span.
	 */
	function initQuickEdit() {
		if (typeof window.inlineEditPost === 'undefined') {
			return;
		}

		var originalEdit = inlineEditPost.edit;

		inlineEditPost.edit = function (id) {
			originalEdit.apply(this, arguments);

			var postId = (typeof id === 'object') ? parseInt(this.getId(id), 10) : id;
			var dataEl = document.getElementById('writing-status-data-' + postId);
			if (!dataEl) {
				return;
			}

			var row = document.getElementById('edit-' + postId);
			if (!row) {
				return;
			}

			var sel  = row.querySelector('select[name="writing_complete"]');
			var date = row.querySelector('input[name="writing_due_date"]');
			var pri  = row.querySelector('select[name="writing_priority"]');

			if (sel)  { sel.value  = dataEl.getAttribute('data-complete')  || 'no'; }
			if (date) { date.value = dataEl.getAttribute('data-due-date')   || ''; }
			if (pri)  { pri.value  = dataEl.getAttribute('data-priority')   || 'none'; }
		};
	}

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			initCompletionToggle();
			initUnsavedWarning();
			initQuickEdit();
		});
	} else {
		initCompletionToggle();
		initUnsavedWarning();
		initQuickEdit();
	}
})();
