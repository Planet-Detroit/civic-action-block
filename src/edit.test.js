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

// When content exists, show a preview of the civic action box
test( 'shows preview when civicHtml has content', () => {
	renderEdit( { civicHtml: '<div>Test content</div>' } );
	expect( screen.queryByPlaceholderText( 'Paste HTML here...' ) ).not.toBeInTheDocument();
	expect( screen.getByText( 'Test content' ) ).toBeInTheDocument();
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

// The box title should be displayed in the preview
test( 'preview shows the box title', () => {
	renderEdit( { civicHtml: '<div>Content</div>', boxTitle: 'My Custom Title' } );
	expect( screen.getByText( 'My Custom Title' ) ).toBeInTheDocument();
} );

// When boxTitle is empty, no title heading should render
test( 'preview hides title when boxTitle is empty', () => {
	renderEdit( { civicHtml: '<div>Content</div>', boxTitle: '' } );
	expect( screen.queryByText( 'Civic Action Toolbox' ) ).not.toBeInTheDocument();
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
