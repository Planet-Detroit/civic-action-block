# Civic Action Gutenberg Block — Status & Next Steps

**Last updated:** 2026-02-19
**Status:** Plugin built, tested, and ready for production deployment.

---

## What This Is

A custom Gutenberg block plugin for Planet Detroit's WordPress site (Newspack theme). It wraps the HTML output from the [civic-action-builder](https://civic-action-builder.vercel.app/) tool in a block that supports WordPress alignment (left/right/center float), so the civic action box can sit inline next to article text — like a right-aligned image.

## What's Done

### Build & source files

All source files are written and the plugin builds successfully (`npm run build`).

```
civic-action-block/
├── civic-action-block.php    # Plugin entry — registers block from build/
├── package.json              # @wordpress/scripts build toolchain
├── .wp-env.json              # Local dev environment config
├── .gitignore
├── src/
│   ├── block.json            # Block config: align support, attributes
│   ├── index.js              # Block registration
│   ├── edit.js               # Editor: paste UI → live preview, sidebar controls
│   ├── save.js               # Returns null (dynamic server-side rendering)
│   ├── style.scss            # Frontend: alignment widths, mobile responsive
│   ├── editor.scss           # Editor-only: placeholder styling
│   ├── render.php            # Server-side output with wp_kses sanitization
│   ├── block.test.js         # Tests: block metadata validation
│   ├── edit.test.js          # Tests: editor component behavior
│   └── save.test.js          # Tests: dynamic rendering return value
├── tests/
│   └── test-render.php       # Tests: server-side rendering & security
├── build/                    # Compiled output (ready to install)
└── node_modules/             # Dependencies (installed)
```

### Testing — all passed (2026-02-19)

**JavaScript tests** (17 tests via `npm test`):
- Block metadata: name, alignment support, attributes, API version
- Save function: returns null for dynamic rendering
- Editor: paste UI, preview mode, Apply button, Clear button, title display

**PHP render tests** (22 tests via `npm run test:php`):
- Basic rendering: empty content, wrapper class, content output
- Box title: renders h3, hides when empty, XSS escaping
- Content preservation: links, inline styles, mailto
- Security: script tags stripped, event handlers stripped, iframes stripped, javascript: URLs stripped
- Alignment: right/left/center classes applied correctly

### Manual testing — all passed (2026-02-19, Newspack theme)

| Test | Result |
|------|--------|
| Install in WordPress | Done — plugin activates cleanly |
| Paste HTML, verify preview | Done — civic-action-builder HTML renders in editor |
| Right-align with text wrap | Done — text wraps around block on frontend |
| Left-align | Done — works correctly |
| Center align | Done — centered with correct max-width |
| Mobile (narrow viewport) | Done — stacks to full width below 767px |
| Save/reload persistence | Done — content and alignment preserved |
| Works with Newspack theme | Done — this is the production theme |

### Key design decisions

- **Dynamic rendering** (`save.js` returns null, `render.php` handles output) — avoids block validation errors if we change markup later
- **wp_kses sanitization** in render.php — allows inline styles, SVGs, and links that the civic-action-builder generates, but strips anything dangerous
- **No API dependency** — block stores pasted HTML as a block attribute, no calls to ask-planet-detroit on page load
- **Sidebar controls** — box title text field, Replace Content button, Clear button
- **Explicit CSS floats with !important** — needed to override block theme layout systems; safe on classic themes like Newspack too

### Important finding: block themes vs classic themes

The plugin's CSS float/wrap behavior **does not work on block themes** like Twenty Twenty-Five. Block themes use `is-layout-constrained` which centers each block independently, preventing adjacent text from wrapping around floats. This is a fundamental limitation of the block theme layout system, not a bug in our plugin. The Newspack theme (Planet Detroit's production theme) is a classic theme and handles floats correctly.

---

## What's NOT Done

### Nice to have (future)

- **Phase 2:** "Analyze Article" button in block sidebar that calls `/api/analyze-article` directly (no need for external civic-action-builder tool)
- **Block transforms:** Allow converting from a Custom HTML block to this block
- **Block patterns:** Pre-built templates for common civic action configurations
