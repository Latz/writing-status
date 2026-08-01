import { vi } from 'vitest';

/**
 * Dynamically (re-)imports the plugin script. writing-status.js is an
 * unbundled IIFE that runs its init functions immediately on load, so each
 * test needs a fresh module instance importing against the DOM/globals it
 * just set up rather than importing named exports.
 */
export async function loadScript() {
	vi.resetModules();
	await import( '../../../writing-status.js' );
}

/**
 * Markup for the classic-editor completion toggle + popup, as rendered by
 * WritingStatusRenderer::renderDraftStatusRow()/renderCompletionMetaBox().
 */
export function buildCompletionToggleFixture() {
	document.body.innerHTML = `
		<div class="misc-pub-section misc-pub-post-status">Status: Draft</div>
		<a id="writing-draft-status-toggle-link" href="#" aria-expanded="false"
			data-complete-label="Draft status: Complete" data-incomplete-label="Draft status: Incomplete">
			<span class="writing-draft-status-link-icon"></span>
			<span class="writing-draft-status-link-text"></span>
		</a>
		<div id="writing-draft-status-box" class="misc-pub-section misc-pub-draft-status">
			<button type="button" id="writing_complete_button" class="draft-complete-toggle"></button>
			<button type="button" id="writing_incomplete_button" class="draft-complete-toggle"></button>
			<input type="hidden" id="writing_complete_hidden" name="writing_complete" value="no" />
		</div>
	`;
}

/**
 * Markup for the classic-editor publish-warning check: the completion
 * toggle fixture plus a #publish button, as rendered on post.php/post-new.php.
 */
export function buildPublishWarningFixture() {
	buildCompletionToggleFixture();
	document.getElementById( 'writing_complete_hidden' ).setAttribute(
		'data-publish-warning',
		'This draft is marked as Incomplete. Are you sure you want to publish it?'
	);
	const publishBtn = document.createElement( 'button' );
	publishBtn.type = 'submit';
	publishBtn.id = 'publish';
	document.body.appendChild( publishBtn );
}

export function buildDraftStatusExpandFixture() {
	buildCompletionToggleFixture();
	const iframe = document.createElement( 'iframe' );
	iframe.id = 'content_ifr';
	document.body.appendChild( iframe );
}

export function buildRepositionFixture() {
	document.body.innerHTML = `
		<div class="misc-pub-section misc-pub-post-status">Status: Draft</div>
		<div class="misc-pub-section misc-pub-draft-status">Draft status</div>
	`;
}

export function buildUnsavedWarningFixture() {
	document.body.innerHTML = `
		<form id="post">
			<input type="hidden" id="writing_complete_hidden" name="writing_complete" value="no" />
			<input type="date" name="writing_due_date" value="" />
			<select id="writing_priority" name="writing_priority">
				<option value="none">None</option>
				<option value="high">High</option>
			</select>
			<button type="button" class="draft-complete-toggle"></button>
		</form>
	`;
}

export function buildQuickEditFixture( postId ) {
	// <tr> must live inside a <table>/<tbody> — jsdom (like real browsers)
	// foster-parents/drops bare <tr> markup set via innerHTML otherwise.
	document.body.innerHTML = `
		<span id="writing-status-data-${ postId }"
			data-complete="yes" data-due-date="2026-08-15" data-priority="high"></span>
		<table><tbody>
			<tr id="edit-${ postId }">
				<td>
					<select name="writing_complete"><option value="no">No</option><option value="yes">Yes</option></select>
					<input type="date" name="writing_due_date" />
					<select name="writing_priority">
						<option value="none">None</option>
						<option value="high">High</option>
					</select>
				</td>
			</tr>
		</tbody></table>
	`;
}
