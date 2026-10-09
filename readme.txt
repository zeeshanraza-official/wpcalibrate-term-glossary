=== WPCalibrate Term Glossary ===
Contributors: wpcalibrate
Donate link: https://wpcalibrate.com/
Tags: glossary, terms, dictionary, tooltip, auto link
Requires at least: 6.8
Tested up to: 7.1.3
Requires PHP: 8.2
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create, manage, display, and automatically link glossary terms and definitions across your WordPress website.

== Description ==

WPCalibrate Term Glossary is a lightweight, performance-tuned glossary management and auto-linking plugin designed for modern WordPress websites. It allows site owners to define specialized terms, synonyms, and definitions once and have them automatically highlighted across eligible website articles with accessible tooltips and dedicated term definitions.

= Key Features =

* **Custom Post Type & Taxonomies:** Manage glossary terms, synonyms, phonetic pronunciations, source citations, and categories.
* **Intelligent Auto-Linking:** Automatically highlights glossary terms in eligible content using a safe, HTML-aware DOM parser that will never break page markup.
* **Deterministic Overlap Resolution:** Longer phrases automatically take precedence over shorter overlapping words (e.g. "Search Engine Optimization" matches before "Search Engine").
* **Accessible Tooltips:** WCAG 2.2 AA compliant interactive tooltips supporting pointer hover, keyboard navigation (Tab/Focus), and mobile/touch tap.
* **A-Z Glossary Index:** Interactive alphabet navigation bar, instant client-side and REST live search, and category filtering with non-JavaScript fallback.
* **Gutenberg Block & Shortcode:** Easily drop the glossary index into any page or post using the native dynamic block or `[wpcalibrate_glossary]` shortcode.
* **Schema.org Structured Data:** Injects clean `DefinedTerm` and `DefinedTermSet` JSON-LD schemas for search engines.
* **CSV Import & Export:** Bulk import and export glossary terms with dry-run validation, duplicate updating, and formula injection protection.
* **Per-Content Exclusions:** Easily disable glossary auto-linking for specific posts, pages, or HTML blocks via custom classes.
* **Zero Bloat & Completely Free:** No external tracking, no telemetry, no paid upsells, and zero third-party dependencies.

== Installation ==

1. Upload the `wpcalibrate-term-glossary` folder to the `/wp-content/plugins/` directory, or install the ZIP file via **Plugins > Add New** in WordPress.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **WPCalibrate > Term Glossary** to configure auto-linking and display settings.
4. Add glossary terms under **WPCalibrate > Glossary Terms > Add New Term**.
5. Add the `[wpcalibrate_glossary]` shortcode or the **Glossary Index** Gutenberg block to any page to display the alphabetical index.

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =
No. WooCommerce is completely optional. If WooCommerce is installed, its `product` post type can optionally be enabled for glossary term detection in settings.

= Will auto-linking alter text inside links, buttons, or code blocks? =
No. The plugin explicitly ignores links (`<a>`), buttons, code blocks (`<code>`, `<pre>`), script tags, input elements, and headings (by default) to preserve site functionality and design hierarchy.

= How do I exclude a specific article from glossary auto-linking? =
When editing a post or page, look for the "Glossary Processing" box in the sidebar and check "Disable automatic glossary linking for this content".

= How do I exclude a specific section inside an article? =
Add the class `wpctg-no-glossary` or attribute `data-wpctg-ignore="true"` to any HTML container element.

= What happens to my data if I deactivate or uninstall the plugin? =
Deactivation preserves all glossary terms and settings. By default, uninstalling the plugin also preserves your terms and data. If you wish to permanently erase all glossary terms upon uninstall, enable the "Delete all WPCalibrate Term Glossary data" option in Advanced Settings.

== Screenshots ==

1. WPCalibrate Term Glossary admin settings overview.
2. Glossary term editor with aliases, phonetic pronunciation, and tooltip controls.
3. Accessible frontend hover and tap tooltip on an auto-linked term.
4. Interactive A-Z glossary index with live search and category filter.

== Changelog ==

= 1.0.0 =
* Initial production release.
* Added custom post type `wpctg_term` and taxonomy `wpctg_category`.
* Added HTML-aware term detection with Unicode and longest-phrase matching precedence.
* Added accessible desktop and touch tooltips.
* Added dynamic Gutenberg block and `[wpcalibrate_glossary]` shortcode.
* Added CSV import/export with dry-run validation and formula injection protection.
* Added Schema.org DefinedTerm JSON-LD structured data.
