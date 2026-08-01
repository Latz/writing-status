import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import { createWpMock } from './mocks/wp.js';
import { buildUnsavedWarningFixture, loadScript } from './helpers/dom.js';

function dispatchBeforeUnload() {
	const event = new window.Event( 'beforeunload', { cancelable: true } );
	window.dispatchEvent( event );
	return event;
}

describe( 'initUnsavedWarning()', () => {
	beforeEach( () => {
		buildUnsavedWarningFixture();
	} );

	afterEach( () => {
		// Each loadScript() call registers a fresh beforeunload listener on the
		// shared jsdom window without removing the previous test's; dispatching
		// 'unload' triggers every accumulated instance's own cleanup (the plugin
		// wires this itself via `window.addEventListener('unload', removeBeforeUnload)`),
		// keeping tests isolated from one another.
		window.dispatchEvent( new window.Event( 'unload' ) );
		delete window.wp;
	} );

	it( 'does not warn before any field has changed', async () => {
		await loadScript();

		const event = dispatchBeforeUnload();

		expect( event.defaultPrevented ).toBe( false );
	} );

	it( 'warns after a tracked field changes', async () => {
		await loadScript();

		document.querySelector( 'select#writing_priority' ).dispatchEvent( new window.Event( 'change' ) );

		const event = dispatchBeforeUnload();

		expect( event.defaultPrevented ).toBe( true );
	} );

	it( 'stops warning after the form is submitted', async () => {
		await loadScript();

		document.querySelector( 'select#writing_priority' ).dispatchEvent( new window.Event( 'change' ) );
		document.getElementById( 'post' ).dispatchEvent( new window.Event( 'submit', { cancelable: true } ) );

		const event = dispatchBeforeUnload();

		expect( event.defaultPrevented ).toBe( false );
	} );

	it( 'syncs the dirty flag into Gutenberg on field change', async () => {
		const { wp, editPostMock } = createWpMock( { status: 'draft' } );
		window.wp = wp;
		await loadScript();

		document.querySelector( 'input[name="writing_due_date"]' ).value = '2026-09-01';
		document.querySelector( 'input[name="writing_due_date"]' ).dispatchEvent( new window.Event( 'change' ) );

		expect( editPostMock ).toHaveBeenCalledWith(
			expect.objectContaining( { meta: expect.objectContaining( { _writing_due_date: '2026-09-01' } ) } )
		);
	} );

	it( 'clears dirty once Gutenberg reports a successful save', async () => {
		const { wp, notifySubscribers, editorState } = createWpMock( { status: 'draft' } );
		window.wp = wp;
		await loadScript();

		document.querySelector( 'select#writing_priority' ).dispatchEvent( new window.Event( 'change' ) );
		expect( dispatchBeforeUnload().defaultPrevented ).toBe( true );

		editorState.isSavingPost = true;
		notifySubscribers();
		editorState.isSavingPost = false;
		editorState.didPostSaveRequestSucceed = true;
		notifySubscribers();

		expect( dispatchBeforeUnload().defaultPrevented ).toBe( false );
	} );
} );
