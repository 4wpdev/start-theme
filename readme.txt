=== Start-Theme ===
Requires at least: 6.4
Tested up to: 6.9
Requires PHP: 7.4
License: GPLv2 or later

Minimal Full Site Editing starter theme (folder `start-theme`):

* theme.json presets aligned with the featured strip layout
* Pattern: **Posts feed with Weather** — Query Loop with Cover + optional picks; grid group `st-featured-strip--mosaic-weather`, weather in `st-strip-weather-slot`. Picks (`stStripPostIds`) are mirrored into `query.include` + `orderBy: include` so the **block editor preview** matches the front (`query_loop_block_query_vars` only affects the front).

Dependencies:

* **4WP Weather** (`forwp/weather` block) must be active for the pattern to render the weather block.

== Styles (Sass) ==

Pattern CSS is authored under `assets/scss/`, compiled to `assets/start-theme-pattern.css`, and loaded on the front (`wp_enqueue_scripts`) and in the block editor (`add_editor_style`) so the strip mosaic matches in the canvas and pattern inserter previews.

1. `cd` into this theme directory.
2. `npm install`
3. `npm run build:css` (or `npm run watch:css` while editing)

== Lint (optional, dev) ==

* **SCSS:** `npm run lint:css` (or `npm run lint:css:fix` for safe auto-fixes).
* **PHP (WordPress Coding Standards):** once in this folder run `composer install`, then `npm run lint:php` or `npm run lint` (CSS + PHP).

Edit `assets/scss/_pattern-strip.scss`. Breakpoints and strip column sizing live in `theme.json` under `settings.custom.stripMosaic` (CSS `var(--wp--custom--strip-mosaic--…)`). Commit `assets/start-theme-pattern.css` so production does not require npm.

Pattern markup: group `st-featured-strip st-featured-strip--mosaic-weather`, Query `st-query-mosaic`, post template `st-query-mosaic__template`, then weather sibling. Hand-picked post IDs: `stStripPostIds` on Query (inspector + `query_loop_block_query_vars` filter).
