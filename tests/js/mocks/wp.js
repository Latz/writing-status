import { vi } from 'vitest';

/**
 * Builds a fresh minimal `wp` global stub covering the APIs
 * initGutenbergPanel() touches (wp.plugins, wp.editPost, wp.element,
 * wp.data, wp.components, wp.i18n). createElement returns a plain
 * { type, props, children } descriptor instead of rendering real React,
 * since tests only assert structure/behavior, not pixel output.
 *
 * @param {object} [state] Mutable editor state read by useSelect/select.
 * @param {object} [state.meta] Post meta (_writing_complete, _writing_priority, _writing_due_date).
 * @param {string} [state.status] Post status.
 * @param {boolean} [state.isPublishSidebarOpened] core/edit-post publish-sidebar-open flag.
 * @return {object} A fresh wp mock object.
 */
export function createWpMock( state = {} ) {
	const editorState = {
		meta: {},
		status: 'draft',
		isSavingPost: false,
		isAutosavingPost: false,
		didPostSaveRequestSucceed: true,
		...state,
	};

	const editPostState = {
		isPublishSidebarOpened: state.isPublishSidebarOpened || false,
	};

	const hookBox = { value: undefined };

	const editPostMock = vi.fn( ( update ) => {
		if ( update.meta ) {
			Object.assign( editorState.meta, update.meta );
		}
	} );
	const savePostMock = vi.fn();
	const togglePublishSidebarMock = vi.fn( () => {
		editPostState.isPublishSidebarOpened = ! editPostState.isPublishSidebarOpened;
	} );

	const editorSelectors = {
		getEditedPostAttribute: ( attribute ) => {
			if ( attribute === 'meta' ) {
				return editorState.meta;
			}
			if ( attribute === 'status' ) {
				return editorState.status;
			}
			return undefined;
		},
		isSavingPost: () => editorState.isSavingPost,
		isAutosavingPost: () => editorState.isAutosavingPost,
		didPostSaveRequestSucceed: () => editorState.didPostSaveRequestSucceed,
	};
	const editorDispatchers = {
		editPost: editPostMock,
		savePost: savePostMock,
	};

	const editPostStoreSelectors = {
		isPublishSidebarOpened: () => editPostState.isPublishSidebarOpened,
	};
	const editPostStoreDispatchers = {
		togglePublishSidebar: togglePublishSidebarMock,
	};

	// Both `select`/`dispatch` (used directly) and `useSelect`/`useDispatch`
	// (which receive this same function as their `select` callback arg) key
	// off the store name so the mock behaves for both core/editor and
	// core/edit-post, matching how initGutenbergPanel() reads each store.
	const selectStore = ( storeName ) =>
		storeName === 'core/edit-post' ? editPostStoreSelectors : editorSelectors;
	const dispatchStore = ( storeName ) =>
		storeName === 'core/edit-post' ? editPostStoreDispatchers : editorDispatchers;

	const subscribers = [];

	const wp = {
		plugins: {
			registerPlugin: vi.fn(),
		},
		editPost: {
			PluginPostStatusInfo: 'PluginPostStatusInfo',
			PluginDocumentSettingPanel: 'PluginDocumentSettingPanel',
		},
		element: {
			createElement: ( type, props, ...children ) => ( {
				type,
				props: props || {},
				children,
			} ),
			Fragment: 'Fragment',
			// Single-slot hook state persisted for the lifetime of this mock
			// instance (`hookBox`, declared once per createWpMock() call below)
			// rather than reset per call — real React persists state across
			// re-renders of the same component instance, and PublishConfirmModal
			// is the only component under test that uses useState, so one shared
			// slot is enough to fake that without a full hooks reconciler.
			useState: ( initial ) => {
				if ( hookBox.value === undefined ) {
					hookBox.value = initial;
				}
				const setValue = ( next ) => {
					hookBox.value = typeof next === 'function' ? next( hookBox.value ) : next;
				};
				return [ hookBox.value, setValue ];
			},
			// Runs the effect synchronously on every "render" (every call to the
			// component function) instead of deferring to a commit phase — close
			// enough for these plain-descriptor tests, which call the component
			// function directly once per simulated render rather than going
			// through a real reconciler.
			useEffect: ( effect ) => effect(),
		},
		data: {
			useSelect: ( mapSelect ) => mapSelect( selectStore ),
			useDispatch: ( storeName ) => dispatchStore( storeName ),
			select: selectStore,
			dispatch: dispatchStore,
			subscribe: ( listener ) => {
				subscribers.push( listener );
				return () => {
					const index = subscribers.indexOf( listener );
					if ( index !== -1 ) {
						subscribers.splice( index, 1 );
					}
				};
			},
		},
		components: {
			Button: 'Button',
			Dropdown: 'Dropdown',
			Icon: 'Icon',
			Modal: 'Modal',
			__experimentalHStack: 'HStack',
		},
		i18n: {
			__: ( text ) => text,
		},
	};

	return {
		wp,
		editorState,
		editPostState,
		editPostMock,
		savePostMock,
		togglePublishSidebarMock,
		notifySubscribers: () => subscribers.forEach( ( listener ) => listener() ),
	};
}
