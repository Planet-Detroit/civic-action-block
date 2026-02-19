# Civic Action Block — Maintenance Guide

## What This Plugin Does

This is a WordPress plugin that adds a "Civic Action Box" block to the Gutenberg editor. It lets you take the HTML output from the [civic-action-builder tool](https://civic-action-builder.vercel.app/) and embed it in an article with text wrapping — similar to how you'd right-align an image so article text flows around it.

## How to Use It

1. **Create or edit a post** in WordPress
2. **Write your article text first** (at least a few paragraphs)
3. **Place your cursor** between paragraphs where you want the box
4. **Click the + button**, search for "Civic Action", and insert the block
5. **Go to the civic-action-builder tool**, generate civic actions for your article, and **copy the HTML** from the Output tab
6. **Paste the HTML** into the block's textarea and click **Apply**
7. **Set alignment** using the toolbar (right is default — text will wrap around the left side)
8. **Publish** the post

## How to Tell If It's Working

- **In the editor:** You should see a styled preview of the civic action box after pasting and clicking Apply
- **On the frontend:** The box should float to the right (or left/center depending on setting) with article text wrapping around it
- **On mobile:** The box should stack to full width instead of floating

## Common Problems

### Text isn't wrapping around the block
- Make sure there are **text paragraphs above and below** the block — floats need adjacent content to wrap
- Make sure alignment is set to **right** or **left** (center doesn't float)
- Do a **hard refresh** (Cmd+Shift+R on Mac) to clear cached CSS

### Block shows raw HTML instead of a preview
- You may need to click **Apply** after pasting the HTML
- If it still doesn't work, try **Replace Content** in the sidebar and paste again

### Block disappeared after saving
- Check if the `civicHtml` content was accidentally cleared — use the WordPress revision history to restore

### The box looks different than expected
- The civic-action-builder generates all styling as inline CSS, so the look is determined by the builder tool, not this plugin
- This plugin only handles the wrapper (alignment, float, max-width)

## Running Tests

**JavaScript tests** (editor behavior):
```bash
npm test
```

**PHP render tests** (server-side output and security — requires wp-env running):
```bash
npm run test:php
```

## Dependencies

| Dependency | What it does | What breaks if it's down |
|-----------|-------------|------------------------|
| WordPress | Hosts the plugin | Everything |
| Newspack theme | Provides the CSS context for text wrapping | Float/wrap may not work on other themes (especially block themes) |
| civic-action-builder | Generates the HTML you paste into the block | You can't create new civic action boxes, but existing ones still display |

## Files That Matter

| File | What it does |
|------|-------------|
| `src/render.php` | Controls what appears on the frontend — this is the security boundary |
| `src/style.scss` | Controls float, alignment, and responsive behavior |
| `src/edit.js` | Controls the editor experience (paste UI, preview, sidebar) |
| `src/block.json` | Block configuration (name, attributes, alignment support) |
| `civic-action-block.php` | Plugin entry point — just registers the block |

## Rebuilding After Changes

If you change any source files in `src/`, you need to rebuild:
```bash
npm run build
```

For development with live reloading:
```bash
npm run start
```

To create a zip for uploading to WordPress:
```bash
npm run plugin-zip
```
