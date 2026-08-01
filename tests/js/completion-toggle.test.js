import { beforeEach, describe, expect, it } from 'vitest';
import { buildCompletionToggleFixture, loadScript } from './helpers/dom.js';

describe( 'initCompletionToggle()', () => {
	beforeEach( async () => {
		buildCompletionToggleFixture();
		await loadScript();
	} );

	it( 'marks Complete active and updates the popup link on click', () => {
		document.getElementById( 'writing_complete_button' ).click();

		expect( document.getElementById( 'writing_complete_hidden' ).value ).toBe( 'yes' );
		expect( document.getElementById( 'writing_complete_button' ).classList.contains( 'is-active' ) ).toBe( true );
		expect( document.getElementById( 'writing_complete_button' ).getAttribute( 'aria-pressed' ) ).toBe( 'true' );
		expect( document.getElementById( 'writing_incomplete_button' ).classList.contains( 'is-active' ) ).toBe( false );
		expect( document.getElementById( 'writing_incomplete_button' ).getAttribute( 'aria-pressed' ) ).toBe( 'false' );
		expect( document.querySelector( '.writing-draft-status-link-text' ).textContent ).toBe( 'Draft status: Complete' );
		expect( document.querySelector( '.writing-draft-status-link-icon' ).textContent ).toBe( '✓' );
	} );

	it( 'marks Incomplete active and updates the popup link on click', () => {
		document.getElementById( 'writing_incomplete_button' ).click();

		expect( document.getElementById( 'writing_complete_hidden' ).value ).toBe( 'no' );
		expect( document.getElementById( 'writing_incomplete_button' ).classList.contains( 'is-active' ) ).toBe( true );
		expect( document.getElementById( 'writing_incomplete_button' ).getAttribute( 'aria-pressed' ) ).toBe( 'true' );
		expect( document.getElementById( 'writing_complete_button' ).classList.contains( 'is-active' ) ).toBe( false );
		expect( document.querySelector( '.writing-draft-status-link-text' ).textContent ).toBe( 'Draft status: Incomplete' );
		expect( document.querySelector( '.writing-draft-status-link-icon' ).textContent ).toBe( '✗' );
	} );
} );
