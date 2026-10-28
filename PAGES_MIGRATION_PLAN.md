# Pages Migration Plan: WordPress "Pages" → Craft (local staging)

**Goal:** a **Pages** module that works like WordPress Pages (create / edit / delete, parent and child pages with nested
URLs, drag to reorder, drafts, revisions, preview, scheduling, SEO), built with the **common page builder** (the same
blocks as the blog, products and industries) plus a set of **page section blocks** from the new design. Then move the
live site's remaining WordPress pages into it at **the same URLs**, with their SEO and redirects.

**Status:** phase 1 built (2026-10-19, `DEVELOPMENT_PHASES.md` Phase 29): the Pages section, Page entry type and Page
Builder, empty (pages are built one by one; no live content migrated). D3 and D5 taken as recommended / simplified (see
Phase 29); the other decisions are still open.
**Sources checked (2026-10-15):** the live `page-sitemap.xml` (139 URLs), the WordPress REST API (all 140 pages: parent,
template, title), every page's live HTTP status and robots tag, the design folder `Arrowsystem_local/`, and staging.

---

## 1. What exists on the live site (140 WordPress pages)

| Group | Pages | In this plan? |
|---|---|---|
| Already migrated to Craft (homepage, `/blog/`, `/industries/` + 12 industries, `/exhibitions/` + 28 shows) | 42 | No (done) |
| Product category pages `/all-products/…` | 16 | No: `PRODUCT_CATEGORIES_MIGRATION_PLAN.md` (but see D9) |
| Redirect on live (301 / 302) | 16 | **Yes: their redirects** (section 6) |
| **Still to migrate** | **66** | **Yes** |

### 1.1 The 66 pages to migrate (all return 200; all in the sitemap)

| Kind | Pages | Design file? |
|---|---|---|
| **Answers** hub + 24 child pages (`/answers/…/`, live parent page "Answers") | 25 | ✅ `answers/index.html`, `answers/answer-detail.html` |
| **Latest News** + 1 child (`/latest-news/arrow-systems-to-exhibit-at-pack-expo-east-2026/`) | 2 | ❌ |
| **Forms:** Request Quote, Try A Free Sample, Contact Us, Become a Dealer, Careers | 5 | ✅ request-quote, try-a-free-sample; ❌ the other 3 |
| **Landing / materials:** Flexible Packaging, Gerber Foils, Arrow Material, Arrow Materials | 4 | ✅ all 4 |
| **Company / legal:** Our Company, Customer Service, Support, Privacy Policy, Used Equipment | 5 | ❌ |
| **Brand & product landing pages:** Anytron Products, Gerber, Lemorau, Limitronic, QuickLabel, Trojan, FiltraBox, The Arrow OverJet, The ArrowJet Aqua, ArrowJet UV Label Printers, Digital Box Printers, Color Label Printer, GHS & Industrial Label Printers, Industrial Digital Printers, Industrial Printing, Labels, Pre-Cut Roll Labels, Pharmaceutical, Security Printing, Aviation Films, Gerber Cut Only Vinyl Applications, Gerber Premium Cast Vinyl Film, Thermal Lamination Film | 23 | ❌ |
| **WooCommerce system pages:** Cart, My Account (both `noindex` on live) | 2 | ❌ (no shop in Craft) |

Built with Elementor: 53 of 66. Not Elementor (classic / theme templates): Limitronic, The ArrowJet Aqua, The Arrow
OverJet, FiltraBox, Pre-Cut Roll Labels, Lemorau, QuickLabel, Trojan, Industrial Digital Printers, Gerber, Gerber Cut
Only Vinyl Applications, Cart, My Account. Live robots: `follow, index` everywhere except Cart and My Account
(`noindex`). Canonical = own URL.

### 1.2 What the design adds for pages

| Design file | Sections (each becomes a reusable block) |
|---|---|
| `request-quote/`, `try-a-free-sample/` | Page header · **Form + side column** (form; contact details / sample info beside it) |
| `flexible-packaging/` | Page header · **Product spotlight** · Content block · **Tiles** (industries) · **Product group** (product cards) · **Media cards** · **Image card** |
| `gerber-foils/` | Page header · **Tabs** · **Features** (headed bullet lists) · Help band · **Related pages** |
| `arrow-materials/` | Page header · **Tabbed products** · Help band · Related pages |
| `arrow-material/` | Page header · **Materials filter + grid** · Help band |
| `answers/index.html` | Page header · **Answers list** (search + cards of the child pages) · Help band |
| `answers/answer-detail.html` | Page header · Buying criteria · Recommended fits · Decision table · Workflow steps · FAQ (+ FAQPage schema) · Help band, all of which map to **existing common blocks** (Takeaways / Checklist, Product List, Data Table, Steps, FAQ) |

---

## 2. How WordPress Pages map to Craft

| WordPress | Craft (this plan) |
|---|---|
| Pages menu, Add New | Entries → **Pages** (a **Structure** section) → New entry |
| Parent page + nested URL (`/answers/child/`) | Drag a page under another; URL format `{parent.uri}/{slug}` gives `/answers/child/` automatically |
| Page order (menu_order) | Drag to reorder in the structure |
| Page template (Default, Elementor Full Width, No sidebar …) | A **Layout** option on the page (D5), not separate templates |
| Elementor / Gutenberg blocks | The **Page Builder** (common blocks + page section blocks) |
| Draft, Pending, Scheduled, Private | Drafts, revisions, live preview and post date are built in; "private" = disabled; no password-protected pages (plugin, not planned) |
| Featured image, excerpt | Card Photo + Summary (used when a page is shown as a card: related pages, answers list) |
| Yoast / Rank Math | The same SEO fields as the other modules (title, description, social image, noindex, canonical) |
| Menus (Appearance → Menus) | Navigation plugin: menu items link to the page entries (they follow URL changes) |

---

## 3. Content model

### 3.1 Section

| Section | Type | URL | Template |
|---|---|---|---|
| **Pages** (`pages`) | Structure, up to 3 levels | `{parent.uri}/{slug}` → `/our-company/`, `/answers/digital-label-printer-upgrades-from-flexo/` | `pages/_entry` |

### 3.2 Entry type "Page"

| Tab | Field | Notes |
|---|---|---|
| Page | Title, Slug, Parent | Slug must stay the live slug (instructions warn) |
| Page | **Layout** (D5) | Default (page header + builder) / Full width (builder only, the first block is the header) |
| Page Builder | **Page Builder** (`pageBuilder`) | Section 4 |
| Card | Card Photo, Summary | Shown when the page appears as a card (Related Pages, Answers list, Tiles) |
| SEO | SEO Title, Meta Description, Social Image, Hide from Search Engines, Canonical URL, Robots (advanced) | Same fields as the other modules |

### 3.3 Page Builder = common blocks + page sections

The common set (20 blocks, already shared by blog / products / industries) **plus** a **Sections** group:

| Block | Source | New? |
|---|---|---|
| Page Header (breadcrumb, small heading, H1 with `[red]`, intro, tags, image, 2 buttons) | today's **Industry Header**, made generic (D3) | reuse |
| Recommended Products / **Product Group** (picked or by category) | today's Recommended Products | reuse |
| Content Block (image or video beside text) | today's | reuse |
| Contact Form (panel + form) | today's | reuse |
| CTA Band / Help band | today's | reuse |
| Photo Gallery | today's | reuse |
| **Form + side column** (quote / sample / contact / dealer / careers forms) | request-quote, try-a-free-sample designs | new |
| **Product Spotlight** (one product, big) | flexible-packaging design | new |
| **Tiles** (industries or pages as tiles) | flexible-packaging design | new |
| **Media Cards** / **Image Card** | flexible-packaging design | new |
| **Tabs** (generic: text / specs / downloads / products per tab) | gerber-foils, arrow-materials designs (the product Tabs block, generalised) | reuse + extend |
| **Features** (headed bullet lists) | gerber-foils design | new |
| **Related Pages** (picked pages, else siblings) | gerber-foils, arrow-materials designs | new |
| **Materials grid** (filter + product cards) | arrow-material design | new (D7) |
| **Answers list** (child pages as searchable cards) | answers design | new |

Every new block gets its partial in `templates/_blocks/sections/` (or similar) and is added to the **industries'**
builder too, so pages and industries share one section set (D3).

---

## 4. Templates and front end

- `templates/pages/_entry.twig`: Layout "Default" = page header (breadcrumb from the page's ancestors: Home › Answers ›
  this page) + the builder; "Full width" = only the builder. Common blocks in a `.prose` column (as on industries),
  section blocks full width, the design's CSS per block (copied unchanged from the design folders).
- Forms (D6): the design's forms with their "not connected yet" behaviour, ready for HubSpot like the other forms.
- SEO: the shared `_partials/seo` (as live: title, description, canonical, robots), WebPage + BreadcrumbList schema;
  Answers pages also FAQPage when they hold an FAQ block (as the design shows).

---

## 5. Content migration

Same approach as industries (Phase 20, row-based import), one script per step in `storage/imports/pages/`:

1. Fetch the 66 live pages + REST data (title, parent, dates) into `cache/`, `data/`.
2. Create the Pages structure with the **live parent / child tree** (Answers → 24 children, Latest News → 1 child).
3. **Design pages first, as pilots** (7): Request Quote, Try A Free Sample, Flexible Packaging, Gerber Foils, Arrow
   Material, Arrow Materials, Answers (+ one answer page from `answer-detail.html`). Built from the design files with
   the live text; token comparison against the design.
4. **The other pages, row by row** from live Elementor: heading + text + photo → Content Block; galleries → Photo
   Gallery; videos → Video; product links → Product Group; FAQ accordions → FAQ; tables → Data Table; forms → Form
   block; the 13 non-Elementor pages from their HTML content into Rich Text / Content Blocks.
5. **Answers children:** buying criteria / fits / decision tables / steps / FAQ → the matching common blocks.
6. SEO from each live `<head>`; links inside text to migrated pages → root-relative (the shared `_links.php`).
7. Menus: main menu + footer items that point at these pages → linked to the entries (as products / industries).
8. Report per page (what went where, what needs a hand check), like `industries/report.md`.

---

## 6. URLs and redirects

- All 66 pages keep their live URL (root level or nested under their parent). The import checks for clashes with blog
  posts, industries and exhibitions at the root (none expected; reported if found).
- **Redirects to add** to `config/redirects.php`, as live: `/all-products/cardboard-box-printing/` → `/digital-box-printers/`,
  `/corrugated/` → `/all-products/corrugate-and-end-of-line-printing/`, `/kerajet/` → (product page, redirected),
  `/order-label-materials/` → `/arrow-materials/`, `/packaging/` → `/flexible-packaging/`, `/shop/` → the label
  printers category, `/try-free-samples/` → `/try-a-free-sample/`, `/checkout/` → `/cart/` (or D4).
- **Found while checking (not pages, needs your call):** live now **301-redirects** `/drupa-2024/`,
  `/printing-united-2022/` and `/upcoming-exhibitions/` to `/exhibitions/`, but staging serves them as pages
  (Phase 16 built them from the live pages of that time). See D10.

---

## 7. Phases

| Phase | Work | Result |
|---|---|---|
| 0 | Decisions D1–D10 | |
| 1 | Pages section, Page entry type, Layout, Card + SEO fields; Page Builder = common + section blocks (existing ones made generic) | Create / nest / reorder pages in the CP |
| 2 | New section blocks (Form, Product Spotlight, Tiles, Media / Image Card, Tabs, Features, Related Pages, Answers list, Materials grid) + templates + the design CSS / JS | Every block renders as the design |
| 3 | The 7 design pilots (+ 1 answer page) | Review with you |
| 4 | Import the rest (row-based), Answers children, report, hand check | All 66 pages |
| 5 | Redirects, menus, links in text | Site-wide |
| 6 | Verify: 66 URLs 200 at the live path, SEO parity 0 differences, design token checks, the all-blocks test (`test_all_blocks.*`, extended to pages), browser checks, CRUD (create / nest / move / delete) | Sign-off |

Back up before each phase; record each in `DEVELOPMENT_PHASES.md`.

---

## 8. Decisions needed

| # | Question | Recommendation |
|---|---|---|
| **D1** | **Answers** (hub + 24): ordinary Pages nested under "Answers" (as live), or their own section with fixed fields? | **Pages, nested**: the design's answer sections all map to common blocks (Takeaways, Product List, Data Table, Steps, FAQ); one module to learn · **Decided 2026-10-27: own Answers section** (CRUD for answers), content from the general blocks: built, `DEVELOPMENT_PHASES.md` Phase 89 |
| **D2** | **Latest News** (+1 child): pages as live, or move into the blog as posts? | **Pages as live** now (URLs kept); merging into the blog is a later content decision |
| **D3** | One **section block set** shared by Pages **and** Industries (rename "Industry Header" → "Page Header", etc., handles unchanged), or separate sets? | **Shared**: same idea as the common page builder; nothing changes on the industry pages |
| **D4** | **Cart / My Account / Checkout** (WooCommerce, no shop in Craft): migrate, or 301 them? | **301 to the label printers category** (the same target live uses for `/shop/`) |
| **D5** | WordPress **page templates**: a Layout option (Default / Full width), or one layout only? | **Layout option** with those 2 values; covers every live template used |
| **D6** | **Forms** (quote, sample, contact, dealer, careers): the design's forms "not connected yet" until HubSpot (as the other forms), or a Craft form plugin now? | **Design forms, HubSpot later**: consistent with the rest of the site |
| **D7** | **Materials grid** with filters (arrow-material design): build now, or the page with Product Group blocks first? | **Product Group first**, the filter block when the materials data is clear (the 2 materials pages overlap: see D8) |
| **D8** | **Duplicates**: Arrow Material vs Arrow Materials, Pharmaceutical (page) vs Pharmaceutical Labeling (industry): keep all as live? | **Keep as live** (URLs and SEO); flag for a content review |
| **D9** | The 16 **`/all-products/` category pages**: keep them in the product-categories plan, or build them as Pages with Product Group blocks? | **Keep the category plan**: it ties the pages to the Product Categories data (dynamic product lists) |
| **D10** | `/drupa-2024/`, `/printing-united-2022/`, `/upcoming-exhibitions/` now **redirect on live** but are pages on staging: follow live (301) or keep? | **Follow live** (301 to `/exhibitions/`), keep the entries for history; your earlier "build it" for the upcoming page may still stand: confirm |

---

## 9. Acceptance checklist

- [ ] Pages CRUD like WordPress: create, nest (URL follows the parent), reorder, draft, preview, schedule, delete.
- [ ] All 66 URLs return 200 at the exact live path; the 16 live redirects match live.
- [ ] SEO parity with live: 0 differences (title, description, canonical, robots, og:image).
- [ ] The 7 design pages match their design files (token comparison); every page uses only builder blocks.
- [ ] Every new block covered by the all-blocks test; 0 failing checks; no JS errors, no sideways scroll on phones.
- [ ] Menus, footer and text links point at the Craft pages; nothing loads from arrsys.com.

## 10. Risks

| Risk | Mitigation |
|---|---|
| 53 Elementor pages with free-form layouts | The row-based converter from industries + per-page report + hand check |
| A changed slug or parent changes the URL (lost SEO) | Slug / parent instructions; `verify.py` checks every live URL |
| Root-level URL clashes (pages, posts, industries, exhibitions share `/slug/`) | The import checks and reports before saving |
| Forms don't send until HubSpot is connected | The design's honest "not connected yet" message, as elsewhere |
| Brand pages overlap the product category pages | D9 keeps them separate; links between them checked |
