import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { buildQuickEditFixture, loadScript } from './helpers/dom.js';

describe( 'initQuickEdit()', () => {
	let originalEdit;

	beforeEach( () => {
		buildQuickEditFixture( 42 );
		originalEdit = vi.fn();
		window.inlineEditPost = { edit: originalEdit, getId: vi.fn( ( row ) => row.id ) };
	} );

	afterEach( () => {
		delete window.inlineEditPost;
	} );

	it( 'is a no-op when inlineEditPost is not present', async () => {
		delete window.inlineEditPost;

		await expect( loadScript() ).resolves.not.toThrow();
	} );

	it( 'calls the original edit handler and prefills the row from data attributes', async () => {
		await loadScript();

		window.inlineEditPost.edit( 42 );

		expect( originalEdit ).toHaveBeenCalledWith( 42 );

		const row = document.getElementById( 'edit-42' );
		expect( row.querySelector( 'select[name="writing_complete"]' ).value ).toBe( 'yes' );
		expect( row.querySelector( 'input[name="writing_due_date"]' ).value ).toBe( '2026-08-15' );
		expect( row.querySelector( 'select[name="writing_priority"]' ).value ).toBe( 'high' );
	} );

	it( 'is a no-op when there is no data element for the post', async () => {
		await loadScript();

		expect( () => window.inlineEditPost.edit( 999 ) ).not.toThrow();
		expect( originalEdit ).toHaveBeenCalledWith( 999 );
	} );
} );
