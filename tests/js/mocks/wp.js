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

	const editPostMock = vi.fn( ( update ) => {
		if ( update.meta ) {
			Object.assign( editorState.meta, update.meta );
		}
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
		},
		data: {
			useSelect: ( mapSelect ) => mapSelect( () => editorSelectors ),
			useDispatch: () => ( { editPost: editPostMock } ),
			select: () => editorSelectors,
			dispatch: () => ( { editPost: editPostMock } ),
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
			__experimentalHStack: 'HStack',
		},
		i18n: {
			__: ( text ) => text,
		},
	};

	return {
		wp,
		editorState,
		editPostMock,
		notifySubscribers: () => subscribers.forEach( ( listener ) => listener() ),
	};
}
