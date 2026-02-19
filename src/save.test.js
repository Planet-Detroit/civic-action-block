/**
 * Tests for save.js
 *
 * The save function returns null because this block uses dynamic
 * server-side rendering (render.php). This is intentional — it means
 * WordPress won't do block validation on the saved HTML, so we can
 * update the output format without breaking existing posts.
 */

import save from './save';

// save() must return null so WordPress uses render.php for output
test( 'save returns null for dynamic rendering', () => {
	expect( save() ).toBeNull();
} );
