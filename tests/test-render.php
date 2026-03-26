<?php
/**
 * Server-side render tests for the Civic Action Box block.
 *
 * Run with: npx wp-env run tests-cli --env-cwd=wp-content/plugins/civic-action-block wp eval-file tests/test-render.php
 *
 * These test render.php — the code that outputs the block on the frontend.
 * This is the security boundary: all user-pasted HTML passes through
 * wp_kses sanitization here before reaching readers.
 */

// Using a class with static properties because wp eval-file runs in
// a scope where PHP global variables aren't accessible inside functions.
class TestRunner {
	public static $passed = 0;
	public static $failed = 0;
}

function render_block_with( $attributes ) {
	$serialized = '<!-- wp:planet-detroit/civic-action ' . wp_json_encode( $attributes ) . ' /-->';
	return do_blocks( $serialized );
}

function assert_contains( $haystack, $needle, $test_name ) {
	if ( strpos( $haystack, $needle ) !== false ) {
		TestRunner::$passed++;
		echo "  PASS: $test_name\n";
	} else {
		TestRunner::$failed++;
		echo "  FAIL: $test_name\n";
		echo "    Expected to find: $needle\n";
		echo "    In output: " . substr( $haystack, 0, 200 ) . "...\n";
	}
}

function assert_not_contains( $haystack, $needle, $test_name ) {
	if ( strpos( $haystack, $needle ) === false ) {
		TestRunner::$passed++;
		echo "  PASS: $test_name\n";
	} else {
		TestRunner::$failed++;
		echo "  FAIL: $test_name\n";
		echo "    Expected NOT to find: $needle\n";
		echo "    In output: " . substr( $haystack, 0, 200 ) . "...\n";
	}
}

function assert_empty_output( $output, $test_name ) {
	if ( empty( trim( $output ) ) ) {
		TestRunner::$passed++;
		echo "  PASS: $test_name\n";
	} else {
		TestRunner::$failed++;
		echo "  FAIL: $test_name\n";
		echo "    Expected empty output but got: " . substr( $output, 0, 200 ) . "\n";
	}
}

// ─── Basic rendering ────────────────────────────────────────────

echo "\n=== Basic Rendering ===\n";

// Empty content should produce no output (no empty boxes in articles)
$output = render_block_with( array( 'civicHtml' => '', 'boxTitle' => 'Title' ) );
assert_empty_output( $output, 'Empty civicHtml renders nothing' );

// Block wrapper div must have the correct WordPress class
$output = render_block_with( array( 'civicHtml' => '<p>Test</p>', 'boxTitle' => 'Title' ) );
assert_contains( $output, 'wp-block-planet-detroit-civic-action', 'Wrapper has block class' );

// Content must appear inside the content wrapper
assert_contains( $output, 'wp-block-planet-detroit-civic-action__content', 'Content wrapper present' );
assert_contains( $output, 'Test', 'Civic HTML content is rendered' );

// ─── Box title ──────────────────────────────────────────────────

echo "\n=== Box Title ===\n";

// Title is now part of the pasted civic HTML, not rendered separately by the block.
// The block should NOT add its own title heading even if boxTitle attribute is set.
$output = render_block_with( array( 'civicHtml' => '<p>Test</p>', 'boxTitle' => 'My Title' ) );
assert_not_contains( $output, 'wp-block-planet-detroit-civic-action__title', 'Block does not render separate title (title is in pasted HTML)' );

// ─── Content preservation ───────────────────────────────────────

echo "\n=== Content Preservation ===\n";

// Links must be preserved (civic action box links to meetings, agencies, etc.)
$html = '<a href="https://egle.michigan.gov" target="_blank" rel="noopener">EGLE</a>';
$output = render_block_with( array( 'civicHtml' => $html ) );
assert_contains( $output, 'href="https://egle.michigan.gov"', 'Link href preserved' );
assert_contains( $output, 'target="_blank"', 'Link target preserved' );

// Inline styles must be preserved (civic-action-builder uses inline styles)
$html = '<div style="background: #f0f5f8; padding: 20px;"><p style="color: #333;">Text</p></div>';
$output = render_block_with( array( 'civicHtml' => $html ) );
assert_contains( $output, 'background: #f0f5f8', 'Inline styles preserved' );

// Mailto links must work (for contacting representatives)
$html = '<a href="mailto:rep@house.mi.gov">Email</a>';
$output = render_block_with( array( 'civicHtml' => $html ) );
assert_contains( $output, 'href="mailto:rep@house.mi.gov"', 'Mailto links preserved' );

// ─── Security: dangerous content must be stripped ───────────────

echo "\n=== Security (Sanitization) ===\n";

// Script tags — most critical XSS vector.
// wp_kses strips the <script> tags (making the code non-executable)
// but may leave the text content as harmless plain text. The key check
// is that the <script> tag itself is gone.
$html = '<p>Safe</p><script>alert("xss")</script>';
$output = render_block_with( array( 'civicHtml' => $html ) );
assert_contains( $output, 'Safe', 'Safe content survives sanitization' );
assert_not_contains( $output, '<script', 'Script tags stripped (not executable)' );

// Event handlers — second most common XSS vector
$html = '<div onclick="alert(1)"><img src="x" onerror="alert(2)"></div>';
$output = render_block_with( array( 'civicHtml' => $html ) );
assert_not_contains( $output, 'onclick', 'onclick handler stripped' );
assert_not_contains( $output, 'onerror', 'onerror handler stripped' );

// iframes — prevents embedding malicious external pages
$html = '<p>Content</p><iframe src="https://evil.com"></iframe>';
$output = render_block_with( array( 'civicHtml' => $html ) );
assert_not_contains( $output, '<iframe', 'iframe tags stripped' );
assert_contains( $output, 'Content', 'Safe content preserved alongside iframe' );

// javascript: URLs in href — another XSS vector
$html = '<a href="javascript:alert(1)">Click</a>';
$output = render_block_with( array( 'civicHtml' => $html ) );
assert_not_contains( $output, 'javascript:', 'javascript: URLs stripped' );

// ─── Alignment ──────────────────────────────────────────────────

echo "\n=== Alignment ===\n";

$output = render_block_with( array( 'civicHtml' => '<p>Test</p>', 'align' => 'right' ) );
assert_contains( $output, 'alignright', 'Right alignment class present' );

$output = render_block_with( array( 'civicHtml' => '<p>Test</p>', 'align' => 'left' ) );
assert_contains( $output, 'alignleft', 'Left alignment class present' );

$output = render_block_with( array( 'civicHtml' => '<p>Test</p>', 'align' => 'center' ) );
assert_contains( $output, 'aligncenter', 'Center alignment class present' );

// ─── Results ────────────────────────────────────────────────────

echo "\n" . str_repeat( '─', 40 ) . "\n";
$p = TestRunner::$passed;
$f = TestRunner::$failed;
echo "Results: $p passed, $f failed\n";

if ( $f > 0 ) {
	exit( 1 );
}
echo "All tests passed!\n";
