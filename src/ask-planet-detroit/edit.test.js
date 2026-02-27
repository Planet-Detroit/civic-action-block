import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';

// Mock WordPress packages (not available in test environment)
jest.mock( '@wordpress/block-editor', () => ( {
	useBlockProps: () => ( { className: 'wp-block-planet-detroit-ask-planet-detroit' } ),
	InspectorControls: ( { children } ) => <div data-testid="inspector">{ children }</div>,
} ), { virtual: true } );

jest.mock( '@wordpress/components', () => ( {
	PanelBody: ( { children, title } ) => <div data-testid="panel" title={ title }>{ children }</div>,
	TextControl: ( { label, value, onChange } ) => (
		<label>{ label }<input value={ value } onChange={ ( e ) => onChange( e.target.value ) } /></label>
	),
} ), { virtual: true } );

jest.mock( '@wordpress/i18n', () => ( { __: ( str ) => str } ), { virtual: true } );

import Edit from './edit';

function renderEdit( attributes = {}, setAttributes = jest.fn() ) {
	const defaults = {
		formTitle: 'Got a question about this?',
		formSubtitle: 'Ask our reporters.',
		...attributes,
	};
	return render( <Edit attributes={ defaults } setAttributes={ setAttributes } /> );
}

test( 'renders the form title in preview', () => {
	renderEdit();
	expect( screen.getByText( 'Got a question about this?' ) ).toBeInTheDocument();
} );

test( 'renders the subtitle in preview', () => {
	renderEdit();
	expect( screen.getByText( 'Ask our reporters.' ) ).toBeInTheDocument();
} );

test( 'renders frontend note in editor', () => {
	renderEdit();
	expect( screen.getByText( /form is live on the frontend/i ) ).toBeInTheDocument();
} );

test( 'renders sidebar settings panel', () => {
	renderEdit();
	expect( screen.getByTestId( 'inspector' ) ).toBeInTheDocument();
	expect( screen.getByTestId( 'panel' ) ).toBeInTheDocument();
} );

test( 'uses custom title from attributes', () => {
	renderEdit( { formTitle: 'Custom Title' } );
	expect( screen.getByText( 'Custom Title' ) ).toBeInTheDocument();
} );
