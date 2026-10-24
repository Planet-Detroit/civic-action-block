# Feature Spec: Ad-aware placement for the Civic Action Box block

**Date**: 2026-10-24
**Status**: Approved (Nina, Oct 24 2026: "plugin change yes")
**Ships as**: civic-action-block v1.5.0

---

## 1. Purpose

When a newsroom floats the Civic Action Box left or right so the story wraps
around it, the site's in-story ad inserter keeps dropping ads into the
paragraphs beside the box. Newspack's ad container clears floats
(`.newspack_global_ad { clear: both }`), so the first ad after the box jumps
below the box and drags every remaining paragraph down with it. The reader
sees a tall blank column beside the box (MinnPost screenshot, Oct 24 2026;
their accordion workaround looks the same once a reader opens it).

This change teaches the block to keep the paragraphs beside the box free of
auto-inserted ads. Ads that would land there move to the first paragraph
after the box's zone. Nothing is lost on mobile, where the box does not float.

## 2. Users

- **Primary user**: editors at Newspack newsrooms using the block (MinnPost,
  Bridge Michigan, Planet Detroit). They never see the mechanism; they see
  the story wrapping cleanly.
- **How they'll access it**: automatically, whenever the block is floated
  left or right on a site running Newspack's Super Cool Ad Inserter. One
  optional number in the block's sidebar tunes how many paragraphs stay
  ad-free.
- **How often**: every story that carries a floated box.

## 3. User Workflow

1. Editor adds the Civic Action Box block, pastes the HTML, picks left or
   right alignment (unchanged).
2. The block sidebar shows an **"Ads beside the box"** panel. On a Newspack
   site it says the in-story ads will be kept clear of the box, with a
   number field "Paragraphs to keep ad-free" (default 8). On a site whose
   ad plugin the block does not know, it says so and suggests center
   alignment or the pop-out panel instead.
3. Editor publishes. On the page, the story wraps around the box for the
   first N paragraphs with no ad in that stretch; the first displaced ad
   appears after paragraph N; later ads sit where the ad inserter put them.

## 4. Requirements

1. When the post contains a Civic Action Box aligned left or right AND the
   Super Cool Ad Inserter (Newspack) is active, remove every auto-inserted
   ad slot that falls within the N paragraphs following the block.
2. Re-insert the first removed ad slot directly after the Nth paragraph.
   If fewer than N paragraphs follow the block, place it after the last
   paragraph. Never place two ad slots next to each other; drop the
   relocated slot if one already sits there.
3. Other removed slots in the zone are dropped (not stacked).
4. Ad slots before the block, and after the zone, are untouched.
5. A center-aligned block changes nothing.
6. N is a block attribute `adFreeParagraphs` (integer, default 8, minimum 1,
   maximum 30), editable in the sidebar when alignment is left or right.
7. Paragraph counting matches the ad inserter: only top-level
   `core/paragraph` blocks count. Headings, images, groups do not.
8. The mechanism runs on `the_content` at priority 6: after the ad inserter
   (priority 5) has placed its shortcode blocks and before WordPress renders
   blocks (priority 9). It does nothing when the inserter is absent.
9. The editor sidebar shows which in-story ad plugin was detected (Newspack
   SCAIP, Ad Inserter, Advanced Ads, Broadstreet, none) and whether this
   block handles it. Only SCAIP is handled in v1.5.0.
10. Multiple boxes in one post each get their own zone.
11. Posts that already carry a manual `[ad]` shortcode are left alone
    (the inserter also leaves them alone).

## 5. Acceptance Criteria

PHP (tests/test-ad-placement.php, run via wp-env):

- [ ] When a floated box is followed by 10 paragraphs with ad slots after
      paragraphs 3 and 6, and N is 8, then no ad slot appears between the box
      and paragraph 8, exactly one ad slot appears right after paragraph 8,
      and the output still has all 10 paragraphs in order.
- [ ] When ad slots sit after paragraphs 3, 6 and 9 and N is 8, then the
      slot after 9 is untouched and only one slot appears after paragraph 8.
- [ ] When an ad slot already sits right after paragraph 8 and another falls
      in the zone, then only one slot appears after paragraph 8 (no doubling).
- [ ] When the box is followed by 4 paragraphs with an ad after paragraph 3
      and N is 8, then the ad appears after paragraph 4 (the last one).
- [ ] When an ad slot sits before the box, then it stays before the box.
- [ ] When the box is center aligned, then the content is returned unchanged.
- [ ] When the box is floated but SCAIP is not active, then the content is
      returned unchanged.
- [ ] When N is 3, then the zone is 3 paragraphs (attribute is honored).
- [ ] When headings and images sit among the paragraphs, then they do not
      count toward N.
- [ ] When two floated boxes are in one post, then each zone is cleared.
- [ ] When the content has no Civic Action Box, then it is returned unchanged
      byte for byte.
- [ ] Existing render tests (sanitization, alignment) still pass.

JavaScript (src/edit.test.js, src/block.test.js):

- [ ] `adFreeParagraphs` attribute exists with default 8.
- [ ] Sidebar shows the "Ads beside the box" panel with the number field when
      alignment is left or right, and hides the number field for center.
- [ ] Panel text names the detected ad plugin passed from PHP, and shows the
      "not handled" message for an unknown plugin.

## 6. Out of Scope

- Moving ads for Ad Inserter, Advanced Ads or Broadstreet. Hooks exist
  (Ad Inserter `ai_block_insertion_check`; Advanced Ads
  `advanced-ads-placement-content-injection-items` and
  `advanced-ads-content-injection-nodes-without-ads`) but they work on
  rendered HTML or per-block checks, not on block positions, and no partner
  newsroom uses them yet. Add an adapter when one does.
- Measuring the real box height in the browser. N is a paragraph count the
  editor can tune.
- Changing the Toolbox app's Site setup copy (separate repo; follow-up).
- Any change to the pasted box HTML or the wp_kses allowlist.

## 7. Connects To

- Newspack `super-cool-ad-inserter-plugin` (filters `the_content` at
  priority 5, inserts `<!-- wp:shortcode -->[ad number="N"]<!-- /wp:shortcode -->`).
- Newspack Ads (renders the slot; its `.newspack_global_ad` has `clear: both`).
- Civic Action Toolbox app, Settings → Site setup, "Placing the box in a
  WordPress story" (points newsrooms to this plugin).

## 8. Known Risks

- **If the zone is too short**: the ad still clears the float partway down
  the box. Editors raise N for long boxes. Default 8 fits an open accordion
  or full box of typical length on Newspack's desktop column.
- **If the zone is too long**: the first in-story ad appears later than the
  newsroom's ad settings intend, so slightly fewer ad views on box stories.
  The newsroom can lower N per story.
- **If SCAIP changes its markup**: the plugin would stop finding the slots
  and simply leave content unchanged (fail-safe: never drops paragraphs).
  Test fixtures pin the current markup.
- **Security**: the filter re-serializes blocks WordPress already parsed.
  It adds no new HTML and does not touch the wp_kses boundary in render.php.

## 9. Success Metrics

- MinnPost's story wraps cleanly with the full box or an open accordion.
- No partner reports the blank-column gap after upgrading to 1.5.0.
