import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { createWpMock } from './mocks/wp.js';
import { loadScript } from './helpers/dom.js';

/** Finds the el(DraftStatusInfo)/el(PublishConfirmModal)/el(WritingStatusPanel) descriptors inside the registered plugin's render tree. */
function getPanelDescriptors( registerPlugin ) {
	const [ , config ] = registerPlugin.mock.calls[ 0 ];
	const tree = config.render();
	return {
		draftStatusInfo: tree.children[ 0 ].children[ 0 ],
		publishConfirmModal: tree.children[ 1 ],
		writingStatusPanel: tree.children[ 2 ].children[ 0 ],
	};
}

describe( 'initGutenbergPanel()', () => {
	afterEach( () => {
		delete window.wp;
		vi.useRealTimers();
	} );

	it( 'is a no-op when the wp globals are absent', async () => {
		document.body.innerHTML = '';

		await expect( loadScript() ).resolves.not.toThrow();
	} );

	it( 'registers the plugin and renders the Draft status row for a draft post', async () => {
		vi.useFakeTimers();
		const { wp } = createWpMock( {
			status: 'draft',
			meta: { _writing_complete: 'no', _writing_priority: 'medium', _writing_due_date: '2026-08-01' },
		} );
		window.wp = wp;

		await loadScript();
		vi.runAllTimers();

		expect( wp.plugins.registerPlugin ).toHaveBeenCalledWith( 'writing-status', expect.objectContaining( {
			render: expect.any( Function ),
		} ) );

		const { draftStatusInfo } = getPanelDescriptors( wp.plugins.registerPlugin );
		const rendered = draftStatusInfo.type();

		expect( rendered ).not.toBeNull();
	} );

	it( 'hides the Draft status row for a published post', async () => {
		vi.useFakeTimers();
		const { wp } = createWpMock( {
			status: 'publish',
			meta: { _writing_complete: 'yes' },
		} );
		window.wp = wp;

		await loadScript();
		vi.runAllTimers();

		const { draftStatusInfo } = getPanelDescriptors( wp.plugins.registerPlugin );

		expect( draftStatusInfo.type() ).toBeNull();
	} );

	it( 'intercepts the publish sidebar and shows a confirm popup when the draft is Incomplete', async () => {
		vi.useFakeTimers();
		const { wp, togglePublishSidebarMock } = createWpMock( {
			status: 'draft',
			meta: { _writing_complete: 'no' },
			isPublishSidebarOpened: true,
		} );
		window.wp = wp;

		await loadScript();
		vi.runAllTimers();

		const { publishConfirmModal } = getPanelDescriptors( wp.plugins.registerPlugin );

		// First render: the effect closes core's sidebar and schedules the
		// modal open, matching React's render-then-commit-effects order.
		expect( publishConfirmModal.type() ).toBeNull();
		expect( togglePublishSidebarMock ).toHaveBeenCalledTimes( 1 );

		// Second render: our own modal takes the sidebar's place.
		const modal = publishConfirmModal.type();
		expect( modal ).not.toBeNull();
		expect( modal.type ).toBe( 'Modal' );
	} );

	it( 'leaves the publish sidebar alone when the draft is already Complete', async () => {
		vi.useFakeTimers();
		const { wp, togglePublishSidebarMock } = createWpMock( {
			status: 'draft',
			meta: { _writing_complete: 'yes' },
			isPublishSidebarOpened: true,
		} );
		window.wp = wp;

		await loadScript();
		vi.runAllTimers();

		const { publishConfirmModal } = getPanelDescriptors( wp.plugins.registerPlugin );

		expect( publishConfirmModal.type() ).toBeNull();
		expect( togglePublishSidebarMock ).not.toHaveBeenCalled();
	} );

	it( '"Cancel" closes the popup without publishing', async () => {
		vi.useFakeTimers();
		const { wp, savePostMock } = createWpMock( {
			status: 'draft',
			meta: { _writing_complete: 'no' },
			isPublishSidebarOpened: true,
		} );
		window.wp = wp;

		await loadScript();
		vi.runAllTimers();

		const { publishConfirmModal } = getPanelDescriptors( wp.plugins.registerPlugin );
		publishConfirmModal.type();
		const modal = publishConfirmModal.type();

		const [ , actions ] = modal.children;
		const [ cancelButton ] = actions.children;
		cancelButton.props.onClick();

		expect( publishConfirmModal.type() ).toBeNull();
		expect( savePostMock ).not.toHaveBeenCalled();
	} );

	it( '"Publish Anyway" closes the popup and publishes the post', async () => {
		vi.useFakeTimers();
		const { wp, savePostMock } = createWpMock( {
			status: 'draft',
			meta: { _writing_complete: 'no' },
			isPublishSidebarOpened: true,
		} );
		window.wp = wp;

		await loadScript();
		vi.runAllTimers();

		const { publishConfirmModal } = getPanelDescriptors( wp.plugins.registerPlugin );
		publishConfirmModal.type();
		const modal = publishConfirmModal.type();

		const [ , actions ] = modal.children;
		const [ , publishButton ] = actions.children;
		publishButton.props.onClick();

		expect( savePostMock ).toHaveBeenCalledWith( { isPublish: true } );
		expect( publishConfirmModal.type() ).toBeNull();
	} );

	it( 'renders the priority/due-date panel from current meta and dispatches updates', async () => {
		vi.useFakeTimers();
		const { wp, editPostMock } = createWpMock( {
			status: 'draft',
			meta: { _writing_priority: 'high', _writing_due_date: '2026-08-01' },
		} );
		window.wp = wp;

		await loadScript();
		vi.runAllTimers();

		const { writingStatusPanel } = getPanelDescriptors( wp.plugins.registerPlugin );
		const panel = writingStatusPanel.type();

		const [ dueDateField, priorityField ] = panel.children;
		const dueDateInput = dueDateField.children[ 1 ];
		const prioritySelect = priorityField.children[ 1 ];

		expect( dueDateInput.props.value ).toBe( '2026-08-01' );
		expect( prioritySelect.props.value ).toBe( 'high' );

		dueDateInput.props.onChange( { target: { value: '2026-09-15' } } );

		expect( editPostMock ).toHaveBeenCalledWith( { meta: { _writing_due_date: '2026-09-15' } } );
	} );
} );
