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
