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
	 * Initialize the Complete/Incomplete buttons — two explicit choices
	 * rather than a single toggle, each click sets that specific value.
	 */
	function initCompletionToggle() {
		var completeBtn = document.getElementById('writing_complete_button');
		var incompleteBtn = document.getElementById('writing_incomplete_button');
		var hiddenInput = document.getElementById('writing_complete_hidden');

		if (!completeBtn || !incompleteBtn || !hiddenInput) {
			return;
		}

		var link = document.getElementById('writing-draft-status-toggle-link');
		var linkTextSpan = link ? link.querySelector('.writing-draft-status-link-text') : null;
		var linkIconSpan = link ? link.querySelector('.writing-draft-status-link-icon') : null;

		function setValue(newValue) {
			hiddenInput.value = newValue;

			completeBtn.classList.toggle('is-active', newValue === 'yes');
			completeBtn.setAttribute('aria-pressed', newValue === 'yes' ? 'true' : 'false');
			incompleteBtn.classList.toggle('is-active', newValue === 'no');
			incompleteBtn.setAttribute('aria-pressed', newValue === 'no' ? 'true' : 'false');

			if (link && linkTextSpan) {
				linkTextSpan.textContent = newValue === 'yes'
					? (link.getAttribute('data-complete-label') || 'Draft status: Complete')
					: (link.getAttribute('data-incomplete-label') || 'Draft status: Incomplete');
			}
			if (linkIconSpan) {
				linkIconSpan.textContent = newValue === 'yes' ? '✓' : '✗';
			}
		}

		completeBtn.addEventListener('click', function () { setValue('yes'); });
		incompleteBtn.addEventListener('click', function () { setValue('no'); });
	}

	/**
	 * Open/close the floating popup containing the completion toggle button,
	 * triggered by clicking the "Draft status: ..." link in the Publish box.
	 * Closes on outside click or Escape, matching standard popup UX.
	 */
	function initDraftStatusExpand() {
		var link = document.getElementById('writing-draft-status-toggle-link');
		var box = document.getElementById('writing-draft-status-box');

		if (!link || !box) {
			return;
		}

		function closeBox() {
			box.classList.remove('is-open');
			link.setAttribute('aria-expanded', 'false');
		}

		function openBox() {
			box.classList.add('is-open');
			link.setAttribute('aria-expanded', 'true');
		}

		link.addEventListener('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			if (box.classList.contains('is-open')) {
				closeBox();
			} else {
				openBox();
			}
		});

		document.addEventListener('click', function (e) {
			if (box.classList.contains('is-open') && !box.contains(e.target) && e.target !== link) {
				closeBox();
			}
		});

		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && box.classList.contains('is-open')) {
				closeBox();
				link.focus();
			}
		});

		// The post content editor (TinyMCE) runs in its own iframe/document,
		// invisible to the top-level document click listener above — without
		// this, clicking into the content area wouldn't close the popup. TinyMCE
		// creates the iframe asynchronously, often after this init runs, so poll
		// briefly rather than looking it up just once.
		function bindContentIframeClose() {
			var iframe = document.getElementById('content_ifr');
			if (!iframe) {
				return false;
			}
			try {
				iframe.contentWindow.document.addEventListener('click', function () {
					if (box.classList.contains('is-open')) {
						closeBox();
					}
				});
				return true;
			} catch (e) {
				// Cross-origin or not yet accessible; ignore.
				return false;
			}
		}

		if (!bindContentIframeClose()) {
			var iframeAttempts = 0;
			var iframePollId = setInterval(function () {
				iframeAttempts++;
				if (bindContentIframeClose() || iframeAttempts > 20) {
					clearInterval(iframePollId);
				}
			}, 250);
		}
	}

	/**
	 * Move the "Draft status" row to sit directly below core's own "Status"
	 * row in the Publish box. post_submitbox_misc_actions (which renders our
	 * row) fires after core's Status/Visibility/Publish-date rows, so this
	 * repositions it via DOM move rather than fighting the hook order.
	 */
	function repositionClassicDraftStatus() {
		var statusRow = document.querySelector('.misc-pub-post-status');
		var draftRow = document.querySelector('.misc-pub-draft-status');

		if (!statusRow || !draftRow || statusRow.nextSibling === draftRow) {
			return;
		}

		statusRow.parentNode.insertBefore(draftRow, statusRow.nextSibling);
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
		var toggleButtons = document.querySelectorAll('.draft-complete-toggle');
		toggleButtons.forEach(function (btn) {
			btn.addEventListener('click', onFieldChange);
		});

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

	/**
	 * Register a native Gutenberg panel in the Document tab sidebar.
	 *
	 * Reads and writes the three Writing Status meta keys via wp.data so
	 * Gutenberg's own save cycle persists them through the REST API. Bails
	 * immediately when wp.plugins is absent (Classic Editor, edit.php).
	 */
	function initGutenbergPanel() {
		if (
			typeof wp === 'undefined' ||
			!wp.plugins ||
			!wp.editPost ||
			!wp.element ||
			!wp.data ||
			!wp.components ||
			!wp.i18n
		) {
			return;
		}

		var el          = wp.element.createElement;
		var Fragment    = wp.element.Fragment;
		var __          = wp.i18n.__;
		var useSelect   = wp.data.useSelect;
		var useDispatch = wp.data.useDispatch;
		var Button      = wp.components.Button;
		var Dropdown    = wp.components.Dropdown;
		var HStack      = wp.components.__experimentalHStack;
		var Icon        = wp.components.Icon;

		/**
		 * Outline circle-check / circle-cross icons, drawn to match the
		 * stroke weight and 20x20 grid of core's own Status row icon
		 * (an outline circle, not a solid dashicon glyph).
		 */
		var completeIcon = el('svg', { viewBox: '0 0 20 20', xmlns: 'http://www.w3.org/2000/svg' },
			el('path', { fill: 'currentColor', d: 'M10 1a9 9 0 100 18 9 9 0 000-18zm0 16.2a7.2 7.2 0 110-14.4 7.2 7.2 0 010 14.4z' }),
			el('path', { fill: 'currentColor', d: 'M8.5 13.2 4.8 9.5l1.3-1.3 2.4 2.4 5.4-5.4 1.3 1.3z' })
		);
		var incompleteIcon = el('svg', { viewBox: '0 0 20 20', xmlns: 'http://www.w3.org/2000/svg' },
			el('path', { fill: 'currentColor', d: 'M10 1a9 9 0 100 18 9 9 0 000-18zm0 16.2a7.2 7.2 0 110-14.4 7.2 7.2 0 010 14.4z' }),
			el('path', { fill: 'currentColor', d: 'M13.5 7.6 11.9 6l-1.9 1.9L8.1 6 6.5 7.6 8.4 9.5l-1.9 1.9 1.6 1.6 1.9-1.9 1.9 1.9 1.6-1.6L11.6 9.5z' })
		);

		/**
		 * "Draft status" row, styled to match core's own Status/Publish/Slug
		 * rows (label left, value right) via the same editor-post-panel__row
		 * classes and HStack component core uses for those rows. The value is
		 * a Dropdown, the same component core uses for Status/Publish/Slug, so
		 * clicking it opens a floating popover rather than pushing content down.
		 */
		function DraftStatusInfo() {
			var editorData = useSelect(function (select) {
				var editor = select('core/editor');
				return {
					meta: editor.getEditedPostAttribute('meta') || {},
					status: editor.getEditedPostAttribute('status')
				};
			});
			var meta = editorData.meta;
			var postStatus = editorData.status;
			var editPost = useDispatch('core/editor').editPost;
			var isComplete = meta._writing_complete === 'yes';

			if (postStatus === 'publish' || postStatus === 'pending' || postStatus === 'future' || postStatus === 'private') {
				return null;
			}

			function setValue(value) {
				editPost({ meta: { _writing_complete: value } });
			}

			return el(HStack, { className: 'editor-post-panel__row ws-status-info' },
				el('div', { className: 'editor-post-panel__row-label' }, __('Draft status', 'writing-status')),
				el('div', { className: 'editor-post-panel__row-control' },
					el(Dropdown, {
						className: 'ws-status-info-dropdown',
						contentClassName: 'ws-status-info-popover',
						popoverProps: { placement: 'left-start', offset: 8, shift: true },
						renderToggle: function (props) {
							return el(Button, {
								variant: 'tertiary',
								className: 'is-compact',
								'aria-expanded': props.isOpen,
								onClick: props.onToggle
							},
								el(Icon, { icon: isComplete ? completeIcon : incompleteIcon, size: 18 }),
								isComplete ? __('Complete', 'writing-status') : __('Incomplete', 'writing-status')
							);
						},
						renderContent: function (contentProps) {
							return el('div', { className: 'ws-status-info-popover-content' },
								el(Button, {
									variant:   'secondary',
									isPressed: isComplete,
									className: 'ws-status-info-btn is-complete-btn',
									onClick:   function () { setValue('yes'); contentProps.onClose(); }
								}, el(Icon, { icon: completeIcon, size: 18 }), __('Complete', 'writing-status')),
								el(Button, {
									variant:   'secondary',
									isPressed: !isComplete,
									className: 'ws-status-info-btn is-incomplete-btn',
									onClick:   function () { setValue('no'); contentProps.onClose(); }
								}, el(Icon, { icon: incompleteIcon, size: 18 }), __('Incomplete', 'writing-status'))
							);
						}
					})
				)
			);
		}

		function WritingStatusPanel() {
			var meta = useSelect(function (select) {
				return select('core/editor').getEditedPostAttribute('meta') || {};
			});
			var editPost = useDispatch('core/editor').editPost;

			var priority = meta._writing_priority || 'none';
			var dueDate  = meta._writing_due_date  || '';

			function updateMeta(key, value) {
				var update = {};
				update[key] = value;
				editPost({ meta: update });
			}

			return el('div', { className: 'ws-panel-wrap' },
				el('div', { className: 'ws-panel-field' },
					el('label', { htmlFor: 'ws-due-date', className: 'ws-panel-label' }, __('Due Date', 'writing-status')),
					el('input', {
						id:        'ws-due-date',
						type:      'date',
						value:     dueDate,
						className: 'ws-panel-ctrl',
						onChange:  function (e) { updateMeta('_writing_due_date', e.target.value); }
					})
				),
				el('div', { className: 'ws-panel-field' },
					el('label', { htmlFor: 'ws-priority', className: 'ws-panel-label' }, __('Priority', 'writing-status')),
					el('select', {
						id:        'ws-priority',
						value:     priority,
						className: 'ws-panel-ctrl',
						onChange:  function (e) { updateMeta('_writing_priority', e.target.value); }
					},
						el('option', { value: 'none'   }, __('None',   'writing-status')),
						el('option', { value: 'low'    }, __('Low',    'writing-status')),
						el('option', { value: 'medium' }, __('Medium', 'writing-status')),
						el('option', { value: 'high'   }, __('High',   'writing-status')),
						el('option', { value: 'urgent' }, __('Urgent', 'writing-status'))
					)
				)
			);
		}

		wp.plugins.registerPlugin('writing-status', {
			render: function () {
				return el(Fragment, {},
					el(
						wp.editPost.PluginPostStatusInfo,
						{ className: 'ws-post-status-info' },
						el(DraftStatusInfo)
					),
					el(
						wp.editPost.PluginDocumentSettingPanel,
						{
							name:  'writing-status-panel',
							title: el('span', { style: { display: 'flex', alignItems: 'center', gap: '6px' } },
								el(wp.components.Icon, { icon: 'edit' }),
								__('Writing Status', 'writing-status')
							)
						},
						el(WritingStatusPanel)
					)
				);
			}
		});

		initGutenbergDraftStatusReposition();
	}

	/**
	 * Best-effort move of the "Draft status" row to sit directly below core's
	 * "Status" row in the Status & visibility panel.
	 *
	 * Gutenberg's PluginPostStatusInfo slot has no supported way to place a
	 * fill anywhere but last in that panel (right before "Move to trash"), so
	 * this repositions it via DOM move instead. It anchors structurally (our
	 * fill is moved to be the first row's next sibling) rather than by
	 * matching translated label text, but that structure is internal to core
	 * and undocumented — if a future WP version changes it, this silently
	 * no-ops and the row stays in its already-working fallback position at
	 * the bottom of the panel.
	 */
	function initGutenbergDraftStatusReposition() {
		function reposition() {
			var el = document.querySelector('.ws-post-status-info');
			if (!el || !el.parentElement) {
				return;
			}
			var parent = el.parentElement;
			var firstChild = parent.firstElementChild;
			if (firstChild && firstChild !== el && el.previousElementSibling !== firstChild) {
				parent.insertBefore(el, firstChild.nextSibling);
			}
		}

		// The sidebar panel mounts asynchronously; poll briefly until it exists.
		var attempts = 0;
		var pollId = setInterval(function () {
			attempts++;
			if (document.querySelector('.ws-post-status-info') || attempts > 20) {
				clearInterval(pollId);
				reposition();
			}
		}, 250);

		// The panel can re-render (e.g. toggling "Status & visibility"
		// open/closed), which resets DOM order — reapply when that happens.
		var editorRoot = document.getElementById('editor');
		if (editorRoot && typeof MutationObserver !== 'undefined') {
			var observer = new MutationObserver(function () {
				reposition();
			});
			observer.observe(editorRoot, { childList: true, subtree: true });
		}
	}

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			initCompletionToggle();
			initDraftStatusExpand();
			repositionClassicDraftStatus();
			initUnsavedWarning();
			initQuickEdit();
			initGutenbergPanel();
		});
	} else {
		initCompletionToggle();
		initDraftStatusExpand();
		repositionClassicDraftStatus();
		initUnsavedWarning();
		initQuickEdit();
		initGutenbergPanel();
	}
})();
