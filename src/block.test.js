/**
 * Tests for block.json — the block's configuration.
 *
 * These verify that the block metadata is correct. If any of these
 * fail, the block may not register properly in WordPress or may
 * behave unexpectedly in the editor.
 */

import metadata from './block.json';

// Block must have the correct name so WordPress can identify it
test( 'block name is planet-detroit/civic-action', () => {
	expect( metadata.name ).toBe( 'planet-detroit/civic-action' );
} );

// Block must support left, right, and center alignment for floating
test( 'block supports left, right, and center alignment', () => {
	expect( metadata.supports.align ).toEqual( [ 'left', 'right', 'center' ] );
} );

// Default alignment should be right (standard for sidebar-style content)
test( 'default alignment is right', () => {
	expect( metadata.attributes.align.default ).toBe( 'right' );
} );

// civicHtml stores the pasted HTML from civic-action-builder
test( 'civicHtml attribute exists and defaults to empty string', () => {
	expect( metadata.attributes.civicHtml ).toBeDefined();
	expect( metadata.attributes.civicHtml.type ).toBe( 'string' );
	expect( metadata.attributes.civicHtml.default ).toBe( '' );
} );

// boxTitle lets editors customize the heading above the civic action content
test( 'boxTitle attribute defaults to Civic Action Toolbox', () => {
	expect( metadata.attributes.boxTitle ).toBeDefined();
	expect( metadata.attributes.boxTitle.default ).toBe( 'Civic Action Toolbox' );
} );

// HTML editing must be disabled — editors paste from civic-action-builder,
// they should not hand-edit the block's raw HTML in WordPress
test( 'HTML editing is disabled', () => {
	expect( metadata.supports.html ).toBe( false );
} );

// Block must use server-side rendering (render.php) for frontend output
test( 'block uses server-side render.php', () => {
	expect( metadata.render ).toBe( 'file:./render.php' );
} );

// API version 3 is required for modern block features
test( 'uses block API version 3', () => {
	expect( metadata.apiVersion ).toBe( 3 );
} );
