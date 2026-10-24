<?php
/**
 * Ad-aware placement for the Civic Action Box block.
 *
 * Plain-English summary: when the box floats left or right, the story text
 * wraps around it. Newspack's in-story ad inserter (Super Cool Ad Inserter)
 * drops ad slots between paragraphs, and Newspack's ad container clears
 * floats, so the first ad beside the box jumps below it and drags the rest
 * of the story down, leaving a blank column. This file moves any ad slot
 * that would land beside the box to just after the box's "zone" of
 * paragraphs instead. See spec-ad-aware-placement.md.
 *
 * Timing: the inserter filters the_content at priority 5 and writes
 * <!-- wp:shortcode -->[ad number="N"]<!-- /wp:shortcode --> blocks.
 * WordPress renders blocks at priority 9. We work in between, at 6, while
 * the content is still block markup we can walk safely.
 *
 * @package civic-action-block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const CIVIC_ACTION_BLOCK_NAME             = 'planet-detroit/civic-action';
const CIVIC_ACTION_BLOCK_AD_ZONE_DEFAULT  = 8;
const CIVIC_ACTION_BLOCK_AD_ZONE_MIN      = 1;
const CIVIC_ACTION_BLOCK_AD_ZONE_MAX      = 30;

/**
 * Which in-story ad plugin is active on this site, and whether we handle it.
 *
 * Only Newspack's Super Cool Ad Inserter is handled in 1.5.0. The others are
 * detected so the editor can tell the newsroom honestly what will happen.
 *
 * @return array{slug:string,name:string,handled:bool}
 */
function civic_action_block_detect_ad_plugin() {
	if ( function_exists( 'scaip_insert_shortcode' ) ) {
		return array( 'slug' => 'scaip', 'name' => 'Newspack in-story ads (Super Cool Ad Inserter)', 'handled' => true );
	}
	if ( class_exists( 'Advanced_Ads' ) ) {
		return array( 'slug' => 'advanced-ads', 'name' => 'Advanced Ads', 'handled' => false );
	}
	if ( defined( 'AD_INSERTER_VERSION' ) || function_exists( 'ai_block_insertion_check' ) ) {
		return array( 'slug' => 'ad-inserter', 'name' => 'Ad Inserter', 'handled' => false );
	}
	if ( class_exists( 'Broadstreet' ) || defined( 'BROADSTREET_VERSION' ) ) {
		return array( 'slug' => 'broadstreet', 'name' => 'Broadstreet', 'handled' => false );
	}
	return array( 'slug' => 'none', 'name' => '', 'handled' => false );
}

/**
 * Remember whether the editor placed [ad] shortcodes by hand.
 *
 * Runs at priority 4, before the inserter. If hand-placed ads exist, the
 * inserter adds nothing and we must not move what the editor placed.
 * The flag is consumed by the very next civic_action_block_move_ads() call.
 *
 * @param string $content Post content before the inserter runs.
 * @return string Unchanged content.
 */
function civic_action_block_note_manual_ads( $content ) {
	// A plain pattern match rather than has_shortcode(): that helper returns
	// false whenever the shortcode is not registered, which would hide
	// hand-placed ads from us on a site where the inserter loads later.
	$manual = ( is_string( $content ) && 1 === preg_match( '/\[(ad|scaip)(\s|\])/', $content ) )
		|| ( function_exists( 'has_block' ) && has_block( 'super-cool-ad-inserter-plugin/scaip-sidebar', $content ) );
	civic_action_block_manual_ads_flag( $manual );
	return $content;
}

/**
 * Tiny state holder for the manual-ads flag (set at priority 4, read at 6).
 *
 * @param bool|null $set New value, or null to read and reset.
 * @return bool
 */
function civic_action_block_manual_ads_flag( $set = null ) {
	static $flag = false;
	if ( null !== $set ) {
		$flag = (bool) $set;
		return $flag;
	}
	$value = $flag;
	$flag  = false;
	return $value;
}

/**
 * Is this parsed block a floated Civic Action Box?
 *
 * Gutenberg omits an attribute equal to its default, and the default
 * alignment is "right", so a missing align means floated.
 *
 * @param array $block Parsed block.
 * @return bool
 */
function civic_action_block_is_floated_box( $block ) {
	if ( CIVIC_ACTION_BLOCK_NAME !== $block['blockName'] ) {
		return false;
	}
	$align = $block['attrs']['align'] ?? 'right';
	return in_array( $align, array( 'left', 'right' ), true );
}

/**
 * Is this parsed block an auto-inserted ad slot from the inserter?
 *
 * @param array $block Parsed block.
 * @return bool
 */
function civic_action_block_is_ad_slot( $block ) {
	return 'core/shortcode' === $block['blockName']
		&& 1 === preg_match( '/\[(ad|scaip)(\s|\])/', $block['innerHTML'] );
}

/**
 * Zone length for a box, clamped to a sane range.
 *
 * @param array $block Parsed Civic Action Box block.
 * @return int
 */
function civic_action_block_zone_length( $block ) {
	$n = (int) ( $block['attrs']['adFreeParagraphs'] ?? CIVIC_ACTION_BLOCK_AD_ZONE_DEFAULT );
	return max( CIVIC_ACTION_BLOCK_AD_ZONE_MIN, min( CIVIC_ACTION_BLOCK_AD_ZONE_MAX, $n ) );
}

/**
 * Move auto-inserted ad slots out of the paragraphs beside a floated box.
 *
 * Rules (spec §4): ad slots within the N paragraphs after a floated box are
 * removed; the first one is re-inserted right after the Nth paragraph (or
 * after the last block if the story ends sooner); never two slots in a row;
 * slots before the box or after the zone are untouched. Returns the content
 * byte for byte when there is nothing to do, so posts without the box or
 * sites without the inserter pay nothing.
 *
 * @param string $content Block markup, after the inserter has run.
 * @return string
 */
function civic_action_block_move_ads( $content ) {
	$manual = civic_action_block_manual_ads_flag();

	if ( ! is_string( $content ) || false === strpos( $content, 'wp:' . CIVIC_ACTION_BLOCK_NAME ) ) {
		return $content;
	}
	if ( ! function_exists( 'scaip_insert_shortcode' ) || $manual ) {
		return $content;
	}
	if ( false === strpos( $content, 'wp:shortcode' ) ) {
		return $content; // No ad slots at all.
	}

	$blocks   = parse_blocks( $content );
	$count    = count( $blocks );
	$out      = array();
	$in_zone  = false;
	$left     = 0;    // Paragraphs still to pass before the zone ends.
	$pending  = null; // First ad slot removed from the current zone.
	$changed  = false;

	for ( $i = 0; $i < $count; $i++ ) {
		$block = $blocks[ $i ];

		if ( civic_action_block_is_floated_box( $block ) ) {
			$out[]   = $block;
			$in_zone = true;
			$left    = civic_action_block_zone_length( $block );
			continue;
		}

		if ( $in_zone && civic_action_block_is_ad_slot( $block ) ) {
			if ( null === $pending ) {
				$pending = $block;
			}
			$changed = true; // Dropped or moved either way.
			continue;
		}

		$out[] = $block;

		if ( $in_zone && 'core/paragraph' === $block['blockName'] ) {
			$left--;
			if ( $left <= 0 ) {
				$in_zone = false;
				if ( null !== $pending ) {
					if ( ! civic_action_block_next_real_block_is_ad( $blocks, $i ) ) {
						$out[] = $pending;
					}
					$pending = null;
				}
			}
		}
	}

	// Story ended inside the zone: the carried ad goes after the last block.
	if ( null !== $pending ) {
		$out[] = $pending;
	}

	if ( ! $changed ) {
		return $content;
	}

	return serialize_blocks( $out );
}

/**
 * Look past whitespace-only blocks: is the next real block an ad slot?
 *
 * @param array $blocks All parsed blocks.
 * @param int   $i      Current index.
 * @return bool
 */
function civic_action_block_next_real_block_is_ad( $blocks, $i ) {
	$count = count( $blocks );
	for ( $j = $i + 1; $j < $count; $j++ ) {
		if ( null === $blocks[ $j ]['blockName'] && '' === trim( $blocks[ $j ]['innerHTML'] ) ) {
			continue;
		}
		return civic_action_block_is_ad_slot( $blocks[ $j ] );
	}
	return false;
}

add_filter( 'the_content', 'civic_action_block_note_manual_ads', 4 );
add_filter( 'the_content', 'civic_action_block_move_ads', 6 );

/**
 * Tell the editor which ad plugin the site runs so the block sidebar can
 * explain what will happen. Attached to the block's editor script handle.
 *
 * @param WP_Block_Type|false $block_type Result of register_block_type().
 */
function civic_action_block_expose_ad_plugin_to_editor( $block_type ) {
	if ( ! $block_type || empty( $block_type->editor_script_handles ) ) {
		return;
	}
	$info = civic_action_block_detect_ad_plugin();
	wp_add_inline_script(
		$block_type->editor_script_handles[0],
		'window.civicActionBlockAds = ' . wp_json_encode( $info ) . ';',
		'before'
	);
}
