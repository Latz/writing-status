import { beforeEach, describe, expect, it } from 'vitest';
import { buildRepositionFixture, loadScript } from './helpers/dom.js';

describe( 'repositionClassicDraftStatus()', () => {
	beforeEach( () => {
		buildRepositionFixture();
	} );

	it( 'moves the draft status row directly after the status row', async () => {
		await loadScript();

		const statusRow = document.querySelector( '.misc-pub-post-status' );
		const draftRow = document.querySelector( '.misc-pub-draft-status' );

		expect( statusRow.nextElementSibling ).toBe( draftRow );
	} );

	it( 'is a no-op when the draft status row is missing', async () => {
		document.querySelector( '.misc-pub-draft-status' ).remove();
		const statusRow = document.querySelector( '.misc-pub-post-status' );
		const html = document.body.innerHTML;

		await loadScript();

		expect( document.body.innerHTML ).toBe( html );
		expect( statusRow.nextElementSibling ).toBeNull();
	} );
} );
