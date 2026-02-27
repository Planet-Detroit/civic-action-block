import save from './save';

test( 'save returns null for dynamic server-side rendering', () => {
	expect( save() ).toBeNull();
} );
