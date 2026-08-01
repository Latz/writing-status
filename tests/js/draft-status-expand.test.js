import { beforeEach, describe, expect, it } from 'vitest';
import { buildDraftStatusExpandFixture, loadScript } from './helpers/dom.js';

describe( 'initDraftStatusExpand()', () => {
	let link;
	let box;

	beforeEach( async () => {
		buildDraftStatusExpandFixture();
		await loadScript();
		link = document.getElementById( 'writing-draft-status-toggle-link' );
		box = document.getElementById( 'writing-draft-status-box' );
	} );

	it( 'opens on link click and closes on a second click', () => {
		link.click();
		expect( box.classList.contains( 'is-open' ) ).toBe( true );
		expect( link.getAttribute( 'aria-expanded' ) ).toBe( 'true' );

		link.click();
		expect( box.classList.contains( 'is-open' ) ).toBe( false );
		expect( link.getAttribute( 'aria-expanded' ) ).toBe( 'false' );
	} );

	it( 'closes on an outside click', () => {
		link.click();
		expect( box.classList.contains( 'is-open' ) ).toBe( true );

		document.body.click();

		expect( box.classList.contains( 'is-open' ) ).toBe( false );
	} );

	it( 'closes on Escape and returns focus to the link', () => {
		link.click();
		expect( box.classList.contains( 'is-open' ) ).toBe( true );

		document.dispatchEvent( new window.KeyboardEvent( 'keydown', { key: 'Escape' } ) );

		expect( box.classList.contains( 'is-open' ) ).toBe( false );
		expect( document.activeElement ).toBe( link );
	} );

	it( 'closes when the TinyMCE content iframe is clicked', () => {
		link.click();
		expect( box.classList.contains( 'is-open' ) ).toBe( true );

		const iframe = document.getElementById( 'content_ifr' );
		iframe.contentWindow.document.dispatchEvent( new window.Event( 'click' ) );

		expect( box.classList.contains( 'is-open' ) ).toBe( false );
	} );
} );
