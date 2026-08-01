import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { buildDraftStatusExpandFixture, loadScript } from './helpers/dom.js';

describe( 'initDraftStatusExpand()', () => {
	let link;
	let box;

	beforeEach( async () => {
		// bindContentIframeClose() falls back to a 250ms setInterval poll
		// when the content_ifr iframe isn't synchronously bindable yet.
		// Fake timers let runAllTimers() flush that poll to completion (bind
		// or exhaust its 20 attempts) within the test instead of leaving a
		// real timer running past teardown, which crashes ("document is not
		// defined") once it fires against a torn-down environment.
		vi.useFakeTimers();

		buildDraftStatusExpandFixture();
		await loadScript();
		vi.runAllTimers();

		link = document.getElementById( 'writing-draft-status-toggle-link' );
		box = document.getElementById( 'writing-draft-status-box' );
	} );

	afterEach( () => {
		vi.useRealTimers();
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
