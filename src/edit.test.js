/**
 * Tests for the Edit component (editor UI).
 *
 * These test the block's behavior in the WordPress editor:
 * - Shows paste UI when no content exists
 * - Shows preview when content has been applied
 * - Apply button works
 * - Clear functionality works
 *
 * We use virtual mocks for WordPress packages because @wordpress/*
 * packages aren't installable in isolation (they have internal
 * dependencies not published to npm). This is standard practice
 * for WordPress block testing outside of the wp-env test runner.
 */

import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';

// Virtual mocks — these don't require the actual packages to exist
jest.mock( '@wordpress/block-editor', () => ( {
	useBlockProps: () => ( { className: 'wp-block-planet-detroit-civic-action' } ),
	InspectorControls: ( { children } ) => <div data-testid="inspector">{ children }</div>,
} ), { virtual: true } );

jest.mock( '@wordpress/components', () => ( {
	PanelBody: ( { children, title } ) => <div data-testid="panel" title={ title }>{ children }</div>,
	TextControl: ( { label, value, onChange } ) => (
		<label>
			{ label }
			<input value={ value } onChange={ ( e ) => onChange( e.target.value ) } />
		</label>
	),
	Button: ( { children, onClick, disabled, variant, isDestructive, ...props } ) => (
		<button onClick={ onClick } disabled={ disabled } data-variant={ variant } { ...props }>
			{ children }
		</button>
	),
} ), { virtual: true } );

jest.mock( '@wordpress/i18n', () => ( {
	__: ( str ) => str,
} ), { virtual: true } );

jest.mock( '@wordpress/element', () => ( {
	...jest.requireActual( 'react' ),
} ), { virtual: true } );

import Edit from './edit';

// Helper: render the Edit component with given attributes
function renderEdit( attributes = {}, setAttributes = jest.fn() ) {
	const defaultAttrs = {
		civicHtml: '',
		boxTitle: 'Civic Action Toolbox',
		...attributes,
	};
	return {
		setAttributes,
		...render( <Edit attributes={ defaultAttrs } setAttributes={ setAttributes } /> ),
	};
}

// When there's no content yet, show the paste textarea so the editor
// can paste HTML from the civic-action-builder tool
test( 'shows paste UI when civicHtml is empty', () => {
	renderEdit( { civicHtml: '' } );
	expect( screen.getByPlaceholderText( 'Paste HTML here...' ) ).toBeInTheDocument();
	expect( screen.getByText( 'Apply' ) ).toBeInTheDocument();
} );

// When content exists, show the preview AND an editable HTML textarea
// so editors can modify the HTML after pasting
test( 'shows preview when civicHtml has content', () => {
	renderEdit( { civicHtml: '<div>Test content</div>' } );
	expect( screen.getByText( 'Test content' ) ).toBeInTheDocument();
} );

// The HTML textarea should be visible and populated with the civicHtml
// so editors can see and edit the raw HTML after pasting
test( 'shows editable HTML textarea when civicHtml has content', () => {
	renderEdit( { civicHtml: '<div>Test content</div>' } );
	const textarea = screen.getByLabelText( 'HTML Code' );
	expect( textarea ).toBeInTheDocument();
	expect( textarea.value ).toBe( '<div>Test content</div>' );
} );

// Editing the HTML textarea should update the civicHtml attribute
// so changes are saved to the block
test( 'editing HTML textarea updates civicHtml attribute', () => {
	const setAttributes = jest.fn();
	renderEdit( { civicHtml: '<div>Old</div>' }, setAttributes );
	const textarea = screen.getByLabelText( 'HTML Code' );
	fireEvent.change( textarea, { target: { value: '<div>Updated</div>' } } );
	fireEvent.blur( textarea );
	expect( setAttributes ).toHaveBeenCalledWith( { civicHtml: '<div>Updated</div>' } );
} );

// The Apply button should be disabled when the textarea is empty
// to prevent saving blank content
test( 'Apply button is disabled when textarea is empty', () => {
	renderEdit( { civicHtml: '' } );
	expect( screen.getByText( 'Apply' ) ).toBeDisabled();
} );

// Clicking Apply should save the pasted HTML as the civicHtml attribute
test( 'Apply button saves pasted HTML to attributes', () => {
	const setAttributes = jest.fn();
	renderEdit( { civicHtml: '' }, setAttributes );

	const textarea = screen.getByPlaceholderText( 'Paste HTML here...' );
	fireEvent.change( textarea, { target: { value: '<div>Civic content</div>' } } );
	fireEvent.click( screen.getByText( 'Apply' ) );

	expect( setAttributes ).toHaveBeenCalledWith( {
		civicHtml: '<div>Civic content</div>',
	} );
} );

// Title is now part of the pasted HTML, not rendered separately by the block.
// The editor preview should NOT add its own title heading.
test( 'preview does not render a separate title heading', () => {
	renderEdit( { civicHtml: '<div>Content</div>', boxTitle: 'My Custom Title' } );
	expect( screen.queryByText( 'My Custom Title' ) ).not.toBeInTheDocument();
} );

// Sidebar should have the Replace and Clear buttons when content exists
test( 'sidebar shows Replace and Clear buttons when content exists', () => {
	renderEdit( { civicHtml: '<div>Content</div>' } );
	expect( screen.getByText( 'Replace Content' ) ).toBeInTheDocument();
	expect( screen.getByText( 'Clear' ) ).toBeInTheDocument();
} );

// Clear button should remove the civic HTML content
test( 'Clear button removes content', () => {
	const setAttributes = jest.fn();
	renderEdit( { civicHtml: '<div>Content</div>' }, setAttributes );

	fireEvent.click( screen.getByText( 'Clear' ) );
	expect( setAttributes ).toHaveBeenCalledWith( { civicHtml: '' } );
} );

// ─── Ads beside the box (spec-ad-aware-placement.md) ─────────────
// PHP tells the editor which in-story ad plugin the site runs by setting
// window.civicActionBlockAds before the editor script loads.

afterEach( () => {
	delete window.civicActionBlockAds;
} );

// On a Newspack site the panel explains ads are kept clear and offers the number field
test( 'ads panel shows the paragraph count field for a floated box on a handled ad plugin', () => {
	window.civicActionBlockAds = { slug: 'scaip', name: 'Newspack in-story ads', handled: true };
	renderEdit( { civicHtml: '<div>Content</div>', align: 'right', adFreeParagraphs: 8 } );
	// The PanelBody mock puts the title in a title attribute, not in text.
	expect( screen.getByTitle( 'Ads beside the box' ) ).toBeInTheDocument();
	expect( screen.getByText( /Newspack in-story ads/ ) ).toBeInTheDocument();
	expect( screen.getByLabelText( 'Paragraphs to keep ad-free' ) ).toHaveValue( '8' );
} );

// Changing the number saves it to the block
test( 'changing the paragraph count updates adFreeParagraphs', () => {
	window.civicActionBlockAds = { slug: 'scaip', name: 'Newspack in-story ads', handled: true };
	const setAttributes = jest.fn();
	renderEdit( { civicHtml: '<div>Content</div>', align: 'right', adFreeParagraphs: 8 }, setAttributes );
	fireEvent.change( screen.getByLabelText( 'Paragraphs to keep ad-free' ), { target: { value: '12' } } );
	expect( setAttributes ).toHaveBeenCalledWith( { adFreeParagraphs: 12 } );
} );

// Center alignment does not float, so there is nothing to keep clear
test( 'ads panel hides the paragraph count field when the box is centered', () => {
	window.civicActionBlockAds = { slug: 'scaip', name: 'Newspack in-story ads', handled: true };
	renderEdit( { civicHtml: '<div>Content</div>', align: 'center', adFreeParagraphs: 8 } );
	expect( screen.queryByLabelText( 'Paragraphs to keep ad-free' ) ).not.toBeInTheDocument();
	expect( screen.getByText( /nothing to keep clear/i ) ).toBeInTheDocument();
} );

// A site whose ad plugin we do not handle gets a plain warning and a workaround
test( 'ads panel warns when the site ad plugin is not handled', () => {
	window.civicActionBlockAds = { slug: 'advanced-ads', name: 'Advanced Ads', handled: false };
	renderEdit( { civicHtml: '<div>Content</div>', align: 'right', adFreeParagraphs: 8 } );
	expect( screen.getByText( /Advanced Ads/ ) ).toBeInTheDocument();
	expect( screen.getByText( /can't yet keep its ads clear/i ) ).toBeInTheDocument();
	expect( screen.queryByLabelText( 'Paragraphs to keep ad-free' ) ).not.toBeInTheDocument();
} );

// No ad plugin detected (or PHP info missing): say so, no field
test( 'ads panel says no ad plugin was found when PHP passes none', () => {
	renderEdit( { civicHtml: '<div>Content</div>', align: 'right', adFreeParagraphs: 8 } );
	expect( screen.getByText( /no in-story ad plugin/i ) ).toBeInTheDocument();
	expect( screen.queryByLabelText( 'Paragraphs to keep ad-free' ) ).not.toBeInTheDocument();
} );
