import metadata from './block.json';

test( 'block name is planet-detroit/ask-planet-detroit', () => {
	expect( metadata.name ).toBe( 'planet-detroit/ask-planet-detroit' );
} );

test( 'formTitle attribute has correct default', () => {
	expect( metadata.attributes.formTitle.default ).toBe( 'Got a question about this?' );
} );

test( 'formSubtitle attribute exists', () => {
	expect( metadata.attributes.formSubtitle ).toBeDefined();
	expect( metadata.attributes.formSubtitle.type ).toBe( 'string' );
} );

test( 'block uses server-side render.php', () => {
	expect( metadata.render ).toBe( 'file:./render.php' );
} );

test( 'HTML editing is disabled', () => {
	expect( metadata.supports.html ).toBe( false );
} );

test( 'uses block API version 3', () => {
	expect( metadata.apiVersion ).toBe( 3 );
} );

test( 'block category is widgets', () => {
	expect( metadata.category ).toBe( 'widgets' );
} );
