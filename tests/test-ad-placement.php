<?php
/**
 * Tests for ad-aware placement (spec-ad-aware-placement.md).
 *
 * Run with: npx wp-env run tests-cli --env-cwd=wp-content/plugins/civic-action-block wp eval-file tests/test-ad-placement.php
 *
 * Plain-English summary: when the Civic Action Box floats left or right,
 * Newspack's ad inserter must not drop an ad into the paragraphs beside
 * the box (the ad clears the float and leaves a blank column). These tests
 * feed the plugin the block markup the ad inserter produces and check where
 * the ad slots end up.
 */

class AdTestRunner {
	public static $passed = 0;
	public static $failed = 0;
}

function ad_assert( $condition, $test_name, $detail = '' ) {
	if ( $condition ) {
		AdTestRunner::$passed++;
		echo "  PASS: $test_name\n";
	} else {
		AdTestRunner::$failed++;
		echo "  FAIL: $test_name\n";
		if ( $detail ) {
			echo "    $detail\n";
		}
	}
}

// ─── Fixture builders: the block markup WordPress stores ─────────

function para( $n ) {
	return "<!-- wp:paragraph -->\n<p>Paragraph $n</p>\n<!-- /wp:paragraph -->\n\n";
}

function heading( $text ) {
	return "<!-- wp:heading -->\n<h2>$text</h2>\n<!-- /wp:heading -->\n\n";
}

function image() {
	return "<!-- wp:image -->\n<figure class=\"wp-block-image\"><img src=\"x.jpg\" alt=\"\"/></figure>\n<!-- /wp:image -->\n\n";
}

// Exactly what Super Cool Ad Inserter appends after a paragraph.
function ad( $n ) {
	return '<!-- wp:shortcode -->[ad number="' . $n . '"]<!-- /wp:shortcode -->';
}

function box( $attrs = array() ) {
	$attrs = array_merge( array( 'civicHtml' => '<div class="civic-action-box"><p>Box</p></div>' ), $attrs );
	return '<!-- wp:planet-detroit/civic-action ' . wp_json_encode( $attrs ) . " /-->\n\n";
}

// Returns the sequence of top-level blocks as short labels, e.g. "P1 P2 AD P3".
// Lets each test state the expected order in one readable string.
function sequence( $content ) {
	$labels = array();
	foreach ( parse_blocks( $content ) as $block ) {
		if ( 'core/paragraph' === $block['blockName'] ) {
			preg_match( '/Paragraph (\d+)/', $block['innerHTML'], $m );
			$labels[] = 'P' . ( $m[1] ?? '?' );
		} elseif ( 'core/shortcode' === $block['blockName'] ) {
			$labels[] = 'AD';
		} elseif ( 'planet-detroit/civic-action' === $block['blockName'] ) {
			$labels[] = 'BOX';
		} elseif ( 'core/heading' === $block['blockName'] ) {
			$labels[] = 'H';
		} elseif ( 'core/image' === $block['blockName'] ) {
			$labels[] = 'IMG';
		}
		// Whitespace-only "blocks" between real blocks are ignored.
	}
	return implode( ' ', $labels );
}

// Build a story: $before paragraphs, the box, then $after paragraphs,
// with ads after the paragraph numbers in $ads_after (counting from 1 overall).
function story( $before, $after, $ads_after, $box_attrs = array() ) {
	$content = '';
	$n       = 0;
	for ( $i = 0; $i < $before; $i++ ) {
		$n++;
		$content .= para( $n );
		if ( in_array( $n, $ads_after, true ) ) {
			$content .= ad( $n );
		}
	}
	$content .= box( $box_attrs );
	for ( $i = 0; $i < $after; $i++ ) {
		$n++;
		$content .= para( $n );
		if ( in_array( $n, $ads_after, true ) ) {
			$content .= ad( $n );
		}
	}
	return $content;
}

// ─── Without the ad inserter: nothing happens ────────────────────

echo "\n=== Inserter absent ===\n";

ad_assert( ! function_exists( 'scaip_insert_shortcode' ), 'Precondition: SCAIP stub not defined yet' );

$content = story( 0, 10, array( 3, 6 ) );
$out     = civic_action_block_move_ads( $content );
ad_assert( $out === $content, 'Floated box but no SCAIP: content unchanged byte for byte' );

// Stub the inserter's presence for the rest of the tests. The plugin only
// checks that this function exists; it never calls it. Declared inside an
// if-block on purpose: PHP hoists plain top-level functions to file start,
// which would make the "inserter absent" tests above see it too early.
if ( ! function_exists( 'scaip_insert_shortcode' ) ) {
	function scaip_insert_shortcode( $content = '' ) {
		return $content;
	}
}

// ─── Core behavior ───────────────────────────────────────────────

echo "\n=== Zone clearing (default 8 paragraphs) ===\n";

// Ads after P3 and P6 both fall beside the box; one moves to after P8.
$out = civic_action_block_move_ads( story( 0, 10, array( 3, 6 ) ) );
$seq = sequence( $out );
ad_assert( 'BOX P1 P2 P3 P4 P5 P6 P7 P8 AD P9 P10' === $seq, 'Two in-zone ads collapse to one after paragraph 8', "Got: $seq" );
ad_assert( 10 === substr_count( $out, '<!-- wp:paragraph -->' ), 'All 10 paragraphs survive' );
ad_assert( false !== strpos( $out, '[ad number="3"]' ), 'The first removed ad is the one re-inserted (keeps its slot number)' );

// An ad already past the zone is untouched.
$seq = sequence( civic_action_block_move_ads( story( 0, 12, array( 3, 6, 9 ) ) ) );
ad_assert( 'BOX P1 P2 P3 P4 P5 P6 P7 P8 AD P9 AD P10 P11 P12' === $seq, 'Ad after paragraph 9 stays; one relocated after paragraph 8', "Got: $seq" );

// No doubling: an ad already sits right after P8.
$seq = sequence( civic_action_block_move_ads( story( 0, 10, array( 3, 8 ) ) ) );
ad_assert( 'BOX P1 P2 P3 P4 P5 P6 P7 P8 AD P9 P10' === $seq, 'Relocated ad dropped when a slot already follows paragraph 8', "Got: $seq" );

// Story ends before the zone does: ad goes after the last paragraph.
$seq = sequence( civic_action_block_move_ads( story( 0, 4, array( 3 ) ) ) );
ad_assert( 'BOX P1 P2 P3 P4 AD' === $seq, 'Short story: ad lands after the last paragraph', "Got: $seq" );

// Ads before the box are not our business.
$seq = sequence( civic_action_block_move_ads( story( 4, 10, array( 3, 7 ) ) ) );
ad_assert( 'P1 P2 P3 AD P4 BOX P5 P6 P7 P8 P9 P10 P11 P12 AD P13 P14' === $seq, 'Ad before the box stays; in-zone ad moves past 8 paragraphs after the box', "Got: $seq" );

echo "\n=== Alignment ===\n";

$content = story( 0, 10, array( 3, 6 ), array( 'align' => 'center' ) );
ad_assert( civic_action_block_move_ads( $content ) === $content, 'Center-aligned box: content unchanged' );

$seq = sequence( civic_action_block_move_ads( story( 0, 10, array( 3 ), array( 'align' => 'left' ) ) ) );
ad_assert( 'BOX P1 P2 P3 P4 P5 P6 P7 P8 AD P9 P10' === $seq, 'Left-aligned box is treated as floated', "Got: $seq" );

// Gutenberg omits an attribute equal to its default ("right"), so a
// box with no align attribute at all is a floated box.
$seq = sequence( civic_action_block_move_ads( story( 0, 10, array( 3 ) ) ) );
ad_assert( 'BOX P1 P2 P3 P4 P5 P6 P7 P8 AD P9 P10' === $seq, 'Missing align attribute means right (floated)', "Got: $seq" );

echo "\n=== Zone length attribute ===\n";

$seq = sequence( civic_action_block_move_ads( story( 0, 10, array( 2, 5 ), array( 'adFreeParagraphs' => 3 ) ) ) );
ad_assert( 'BOX P1 P2 P3 AD P4 P5 AD P6 P7 P8 P9 P10' === $seq, 'adFreeParagraphs = 3 clears only 3 paragraphs', "Got: $seq" );

// Clamping is tested on the helper directly: a zone of 0 or a negative
// number behaves like 1, so a bad value can never switch the feature off silently.
ad_assert( 1 === civic_action_block_zone_length( array( 'attrs' => array( 'adFreeParagraphs' => 0 ) ) ), 'adFreeParagraphs 0 is clamped to 1' );
ad_assert( 1 === civic_action_block_zone_length( array( 'attrs' => array( 'adFreeParagraphs' => -5 ) ) ), 'Negative adFreeParagraphs is clamped to 1' );
ad_assert( 8 === civic_action_block_zone_length( array( 'attrs' => array() ) ), 'Missing adFreeParagraphs falls back to 8' );

$seq = sequence( civic_action_block_move_ads( story( 0, 10, array( 3 ), array( 'adFreeParagraphs' => 999 ) ) ) );
ad_assert( 'BOX P1 P2 P3 P4 P5 P6 P7 P8 P9 P10 AD' === $seq, 'adFreeParagraphs above 30 is clamped to 30 (ad lands at the end here)', "Got: $seq" );

echo "\n=== Only paragraphs count ===\n";

$content = box() . para( 1 ) . para( 2 ) . heading( 'Section' ) . image() . para( 3 ) . ad( 3 ) . para( 4 ) . para( 5 ) . para( 6 ) . para( 7 ) . para( 8 ) . para( 9 );
$seq     = sequence( civic_action_block_move_ads( $content ) );
ad_assert( 'BOX P1 P2 H IMG P3 P4 P5 P6 P7 P8 AD P9' === $seq, 'Headings and images do not count toward the zone', "Got: $seq" );

echo "\n=== Two boxes ===\n";

// Second box 4 paragraphs after the first restarts the zone from there.
$content = story( 0, 4, array( 3 ) ) . story( 0, 10, array() );
// story() numbering restarts, so relabel: just check no AD sits in either zone.
$out = civic_action_block_move_ads( $content );
$seq = sequence( $out );
ad_assert( 'BOX P1 P2 P3 P4 BOX P1 P2 P3 P4 P5 P6 P7 P8 AD P9 P10' === $seq, 'Each floated box clears its own zone; carried ad lands after the second zone', "Got: $seq" );

echo "\n=== Leave alone ===\n";

$content = para( 1 ) . para( 2 ) . para( 3 ) . ad( 3 ) . para( 4 );
ad_assert( civic_action_block_move_ads( $content ) === $content, 'No box in the post: content unchanged byte for byte' );

$content = story( 0, 10, array() );
ad_assert( civic_action_block_move_ads( $content ) === $content, 'Box but no ads: content unchanged byte for byte' );

echo "\n=== Manual ad shortcodes are respected ===\n";

// An editor who placed [ad] by hand before the inserter ran gets exactly
// what they placed. The plugin records that at priority 4, before SCAIP.
$content = story( 0, 10, array( 3 ) );
civic_action_block_note_manual_ads( $content );
ad_assert( civic_action_block_move_ads( $content ) === $content, 'Hand-placed [ad] shortcode in the zone stays put' );
// Flag resets after one pass.
$seq = sequence( civic_action_block_move_ads( story( 0, 10, array( 3 ) ) ) );
ad_assert( 'BOX P1 P2 P3 P4 P5 P6 P7 P8 AD P9 P10' === $seq, 'Manual-ad flag applies to one post only', "Got: $seq" );

echo "\n=== Hook wiring ===\n";

ad_assert( 6 === has_filter( 'the_content', 'civic_action_block_move_ads' ), 'Filter runs at priority 6 (after SCAIP at 5, before block rendering at 9)' );
ad_assert( 4 === has_filter( 'the_content', 'civic_action_block_note_manual_ads' ), 'Manual-ad check runs at priority 4 (before SCAIP)' );

echo "\n=== Ad plugin detection ===\n";

$info = civic_action_block_detect_ad_plugin();
ad_assert( 'scaip' === $info['slug'], 'Detects Super Cool Ad Inserter when its function exists', 'Got: ' . wp_json_encode( $info ) );
ad_assert( true === $info['handled'], 'SCAIP is marked as handled' );

// ─── Results ─────────────────────────────────────────────────────

echo "\n" . str_repeat( '─', 40 ) . "\n";
echo 'Results: ' . AdTestRunner::$passed . ' passed, ' . AdTestRunner::$failed . " failed\n";
if ( AdTestRunner::$failed > 0 ) {
	exit( 1 );
}
echo "All ad placement tests passed!\n";
