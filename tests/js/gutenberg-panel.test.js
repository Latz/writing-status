import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { createWpMock } from './mocks/wp.js';
import { loadScript } from './helpers/dom.js';

/** Finds the el(DraftStatusInfo)/el(WritingStatusPanel) descriptor inside the registered plugin's render tree. */
function getPanelDescriptors( registerPlugin ) {
	const [ , config ] = registerPlugin.mock.calls[ 0 ];
	const tree = config.render();
	return {
		draftStatusInfo: tree.children[ 0 ].children[ 0 ],
		writingStatusPanel: tree.children[ 1 ].children[ 0 ],
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
