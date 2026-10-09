# WPCalibrate Term Glossary

[![WordPress](https://img.shields.io/badge/WordPress-6.8%2B%20%7C%207.1%2B-21759b.svg?logo=wordpress&logoColor=white)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.2%20%7C%208.3%20Strict-777bb4.svg?logo=php&logoColor=white)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-GPL--2.0--or--later-blue.svg)](LICENSE.txt)
[![Updates](https://img.shields.io/badge/WordPress%20Dashboard-Auto%20Updates-success.svg)](#automatic-updates-from-wordpress-dashboard)

A high-performance, accessible, and enterprise-grade glossary engine and automatic term-linking plugin for WordPress. Define specialized vocabulary, acronyms, and technical terminology once, and automatically convert eligible terms into accessible tooltips and knowledge base links across your website.

---

## 📖 About & Purpose

Online publications, educational platforms, technical documentation sites, and eCommerce stores frequently use specialized vocabulary, acronyms, and industry jargon. Readers often get confused or bounce if definitions are inaccessible.

**WPCalibrate Term Glossary** solves this by providing:
- **Centralized Term Authority:** Manage glossary terms, synonyms, phonetic pronunciations, reference links, and categories.
- **Safe, Automated In-Content Detection:** Scans eligible article body copy and wraps defined terms in interactive tooltips and links.
- **Accessible Tooltips:** WCAG 2.2 AA compliant tooltips supporting mouse hover, keyboard focus (`Tab`/`Shift+Tab`, `Escape`), and mobile touch.
- **Non-Destructive Parsing:** Built on native `DOMDocument` and `DOMXPath` targeting text nodes; never naive regex replacements that corrupt HTML tags, existing anchor links, buttons, or scripts.
- **Public A-Z Glossary Index:** Complete searchable directory with instant client-side and REST filtering, alphabet navigation bar, and Gutenberg block.

---

## ✨ Features

### 🔍 Automated Term Detection & Linking
- **Text-Node Safe Parsing:** Evaluates text nodes without ever altering existing links (`<a>`), buttons (`<button>`), code blocks (`<code>`, `<pre>`), form fields, or scripts.
- **Deterministic Disambiguation:** Resolves compound terms gracefully. Longer multi-word phrases (e.g., *"Search Engine Optimization"*) always match before shorter sub-phrases (e.g., *"Search Engine"*).
- **Matching Priority Engine:** Assign custom numeric priorities to terms to override length-based matching order.
- **Case-Sensitivity Controls:** Global default with per-term case-sensitive overrides (e.g., matching acronym *"API"* without matching common words).
- **Configurable Link Modes:**
  - `tooltip_link`: Interactive tooltip popup with anchor link to the full term page.
  - `tooltip_only`: Interactive tooltip popup via an accessible button without page navigation.
  - `link_only`: Clean anchor link to the term page with no tooltip popup markup.

### 📚 Public Glossary Index & Navigation
- **A-Z Alphabet Directory:** Quick filter bar (`#`, `A` through `Z`) with dynamic active counts.
- **Live Search & Category Filtering:** Instant search bar with real-time feedback and server-side fallback.
- **Native Dynamic Gutenberg Block:** Drop the glossary directory into any layout via the `blocks/glossary-index` block.
- **Universal Shortcode:** Embed the directory anywhere with `[wpcalibrate_glossary]`.

### 🛡️ Enterprise Security & Data Integrity
- **Strict Capabilities:** Custom post type capabilities (`edit_wpctg_terms`, `publish_wpctg_terms`, etc.) with self-healing registration for administrators and editors.
- **Spreadsheet Formula Protection:** Exported CSV files neutralize Excel/Sheets formula injection vectors (`=`, `+`, `-`, `@`).
- **Safe CSV Importer:** Validates headers and rows with a **Dry-Run Preview** mode before writing to the database.
- **Strict Typing:** 100% strictly typed codebase (`declare(strict_types=1);`) compliant with PHP 8.2 and PHP 8.3.

### ⚡ Performance & Caching
- **Transient-Backed Index:** Normalizes and caches active glossary match patterns in WordPress transients (`wpctg_terms_matching_index`).
- **Instant Invalidation:** Automatically flushes cache upon post save, taxonomy changes, or settings updates, plus manual AJAX purge button.
- **High Scalability:** Evaluated and bench-tested across 5,000+ glossary terms under 600ms with peak memory usage below 0.6 MB.

### 🌐 SEO & Structured Data
- **Schema.org DefinedTerm JSON-LD:** Automatically injects semantic structured data on single term pages for rich search engine snippets.
- **Clean Semantic Markup:** Adheres to semantic HTML5 standards and responsive viewport adaptation.

---

## 🚀 Installation & Quick Start

### Installation via WordPress Dashboard
1. Download the latest `wpcalibrate-term-glossary-1.0.0.zip` from [Releases](https://github.com/zeeshanraza-official/wpcalibrate-term-glossary/releases).
2. In your WordPress admin, go to **Plugins > Add New > Upload Plugin**.
3. Choose the ZIP file and click **Install Now**.
4. Click **Activate Plugin**.

### Installation via FTP / Manual Upload
1. Extract `wpcalibrate-term-glossary-1.0.0.zip`.
2. Upload the `wpcalibrate-term-glossary` directory into your `/wp-content/plugins/` directory.
3. Activate the plugin in **Plugins > Installed Plugins**.

---

## 🛠️ Usage & Documentation

### 1. Creating Glossary Terms
1. Go to **WPCalibrate > Glossary Terms > Add New Term**.
2. Enter the Term Title (e.g., *"Application Programming Interface"*).
3. In the main editor, write the comprehensive term description.
4. In the **Glossary Term Details** meta box:
   - **Short Definition / Tooltip:** Enter a 1–2 sentence definition displayed inside tooltips.
   - **Phonetic Pronunciation:** (Optional) e.g., *"/ˌeɪ piː ˈaɪ/"*.
   - **External Source / Citation:** (Optional) Authoritative URL reference.
   - **Aliases / Synonyms:** Comma-separated alternative names (e.g., *"API, APIs"*).
   - **Matching Priority:** Integer priority (higher number takes matching precedence).
   - **Auto-link & Case Sensitivity:** Toggles for automatic linking and case-sensitive matching.
5. Click **Publish**.

### 2. Displaying the A-Z Glossary Index
Use either the Gutenberg Block or the Shortcode on any page:

#### Gutenberg Block
- In the block inserter, search for **Glossary Index** (under the WPCalibrate category) and insert it.

#### Shortcode
```text
[wpcalibrate_glossary]
```

#### Shortcode Attributes
| Attribute | Default | Description |
|---|---|---|
| `show_search` | `"true"` | Show or hide the live search toolbar (`true` / `false`). |
| `show_alphabet` | `"true"` | Show or hide the A-Z alphabet navigation bar (`true` / `false`). |
| `show_categories` | `"true"` | Show or hide the category dropdown filter (`true` / `false`). |
| `per_page` | `20` | Number of glossary terms per page (`1` to `100`). |
| `order` | `"ASC"` | Sort order (`"ASC"` or `"DESC"`). |
| `category` | `""` | Filter index to a specific category slug (e.g., `category="technology"`). |

*Example:*
```text
[wpcalibrate_glossary per_page="30" show_categories="false"]
```

### 3. Content Exclusions
- **Per-Post Exclusion:** While editing any Post or Page, check **"Exclude this content from glossary automatic linking"** in the sidebar meta box.
- **Section Exclusion:** Add the CSS class `wpctg-no-glossary` or the HTML attribute `data-wpctg-ignore="true"` to any container element to prevent term matching within that section:
```html
<div class="wpctg-no-glossary">
    <p>Terms inside this block will never be linked.</p>
</div>
```

---

## 🔄 Automatic Updates from WordPress Dashboard

This plugin supports seamless direct updates through the WordPress Admin Dashboard using GitHub-based release tracking.

By declaring:
```php
 * GitHub Plugin URI: https://github.com/zeeshanraza-official/wpcalibrate-term-glossary
 * Primary Branch:    main
```
You can manage updates directly from your dashboard using standard tools like [Git Updater](https://git-updater.com/) or GitHub webhook distribution pipelines.

---

## 🔌 Developer Hooks & Filters

WPCalibrate Term Glossary provides extensibility through native WordPress filters and actions:

| Hook / Filter | Type | Description |
|---|---|---|
| `wpctg_term_rewrite_slug` | Filter | Customize the URL rewrite slug (default: `'glossary'`). |
| `wpctg_settings_defaults` | Filter | Filter default plugin settings array. |
| `wpctg_single_term_meta_html` | Filter | Customize the metadata card rendered on single term pages. |
| `wpctg_schema_defined_term` | Filter | Customize the Schema.org `DefinedTerm` JSON-LD array. |
| `wpctg_candidate_terms` | Filter | Filter matching term candidates before DOM parsing runs. |
| `wpctg_content_before_detection` | Filter | Filter raw post HTML prior to glossary scanning. |
| `wpctg_content_after_detection` | Filter | Filter final HTML output after glossary terms are linked. |
| `wpctg_settings_updated` | Action | Fires immediately after settings are saved. |

---

## 📋 Changelog

### [1.0.1] - 2026-10-09
- **Fixed:** Resolved PHP 8 `ArgumentCountError` when saving/publishing terms caused by WordPress 4-argument meta sanitization filters.
- **Hardened:** Replaced built-in `intval` sanitize callback with userland `PostType::sanitize_priority()` method.
- **Hardened:** Made `$post` parameter optional in `TermMetaBox::save_meta_box()` and `ContentExclusion::save_exclusion_meta()` to prevent `TypeError` during hook dispatches.
- **Added:** Self-healing capability check (`Capabilities::ensure_caps()`) on `admin_init` ensuring administrators always have required permissions on direct FTP updates.
- **Added:** Full Site Editing (FSE) block theme compatibility in `SingleTerm::template_loader()`.
- **Added:** Dedicated **Plugin Overview Tab** (`src/Admin/Overview.php`) with architectural breakdown, how-to-use guide, and methods comparison table.
- **Added:** Background-adaptive branding logo icons (20x20px dark/white) for unified WordPress admin menu appearance.
- **Added:** Automated FTP deployment pipeline with live file system watcher (`sync-ftp.ps1`).

### [1.0.0] - 2026-10-08
- Initial production release.
- Custom Post Type `wpctg_term` and taxonomy `wpctg_category`.
- Safe DOMDocument/XPath HTML-aware term detection engine.
- 3 tooltip interaction modes (`tooltip_link`, `tooltip_only`, `link_only`).
- A-Z interactive Glossary Index with search, alphabet bar, and pagination.
- Native Gutenberg dynamic block (`blocks/glossary-index`) and shortcode (`[wpcalibrate_glossary]`).
- Read-only public REST API search route (`/wp-json/wpctg/v1/search`).
- Schema.org `DefinedTerm` JSON-LD structured data.
- UTF-8 CSV Import and Export with spreadsheet formula injection protection.
- Transient-based term matching cache with automated invalidation.

---

## 📄 License & Attribution

Distributed under the **GNU General Public License v2.0 or later** (GPL-2.0-or-later). See [`LICENSE.txt`](LICENSE.txt) for details.

Developed with ❤️ by **[WPCalibrate](https://wpcalibrate.com)**.
For support and inquiries: [support@wpcalibrate.com](mailto:support@wpcalibrate.com).
