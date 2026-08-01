import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { buildPublishWarningFixture, loadScript } from './helpers/dom.js';

describe( 'initPublishWarning()', () => {
	beforeEach( async () => {
		buildPublishWarningFixture();
		await loadScript();
	} );

	afterEach( () => {
		vi.restoreAllMocks();
	} );

	it( 'confirms before publishing an Incomplete draft', () => {
		const confirmSpy = vi.spyOn( window, 'confirm' ).mockReturnValue( true );

		const event = new MouseEvent( 'click', { bubbles: true, cancelable: true } );
		document.getElementById( 'publish' ).dispatchEvent( event );

		expect( confirmSpy ).toHaveBeenCalledWith(
			'This draft is marked as Incomplete. Are you sure you want to publish it?'
		);
		expect( event.defaultPrevented ).toBe( false );
	} );

	it( 'cancels the publish click when the user declines the confirmation', () => {
		vi.spyOn( window, 'confirm' ).mockReturnValue( false );

		const event = new MouseEvent( 'click', { bubbles: true, cancelable: true } );
		document.getElementById( 'publish' ).dispatchEvent( event );

		expect( event.defaultPrevented ).toBe( true );
	} );

	it( 'does not prompt when the draft is already marked Complete', () => {
		document.getElementById( 'writing_complete_hidden' ).value = 'yes';
		const confirmSpy = vi.spyOn( window, 'confirm' );

		document.getElementById( 'publish' ).click();

		expect( confirmSpy ).not.toHaveBeenCalled();
	} );
} );
