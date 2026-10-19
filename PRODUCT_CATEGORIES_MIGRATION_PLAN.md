# Product Categories Migration Plan: live arrsys.com → Craft (local staging)

**Goal:** rebuild the live site's product category pages (`/all-products/…`) as Craft entries, at **exactly the same
URLs**, with their **SEO preserved** and the live **redirects** kept, in the new design
(`Arrowsystem_local/digital-label-printers/index.html` and `digital-flexible-packaging-printers/index.html`).
Editors get full create / edit / delete / reorder control in the control panel (CP).

**Status:** partly built (2026-10-14): the 13 **WooCommerce** categories are in Craft as the Product Categories structure, **without pages yet**, and homepage section C picks from them (`DEVELOPMENT_PHASES.md` → Phase 17). The **products** are in Craft too (104 product pages at `/product/{slug}/`, Phase 18). The category **pages** (this plan) come later. (The Industries plan, `INDUSTRIES_MIGRATION_PLAN.md`, is on hold.)
**Sources checked (2026-10-13):**
- the live Rank Math sitemap index `https://arrsys.com/sitemap_index.xml` (lists `post-sitemap1/2.xml`, `page-sitemap.xml`, `product-sitemap.xml`; no category sitemap)
- the live `page-sitemap.xml` and all 22 `/all-products/` URLs in it (fetched today)
- the live WooCommerce category list (REST API)
- the live `product-sitemap.xml`
- the design files above

---

## 1. What exists on the live site

### 1.1 Two different kinds of "category" on the live site

| Kind | URL | Used by the site? |
|---|---|---|
| **Category pages**: WordPress **pages** (built in Elementor), nested under the page "All Products" (WP page IDs: All Products 3641 → Label Finishers 3598 → Arrow Label Finishers 3718; confirmed via the live REST API) | `/all-products/…` | **Yes, visibly.** The menus, the footer, the product pages and the design link to these. **This plan migrates these.** |
| WooCommerce category archives (13, incl. "Uncategorized" with 51 products) | `/product-category/…` | **Not linked visibly anywhere, but live and indexable:** they return **200** with robots `index, follow` and their own canonical, and **every product page's breadcrumb schema (JSON-LD) points to one of them** (Home › *WooCommerce category* › Product). They're **not in the sitemap**, but Google can find, index and show them via that schema. See **decision D6**: they need a redirect or replacement, not a 404. |

### 1.2 The `/all-products/` tree: 16 real pages + 6 redirects

The sitemap lists 22 URLs. **6 of them are permanent redirects (301)** and only **16 are real pages**.

**Real pages (keep each URL exactly, with the trailing slash):**

| # | Live URL | Level | Live H1 | Live SEO title | Products shown | Design template |
|---|---|---|---|---|---|---|
| 1 | `/all-products/` | 0 (root) | All Products | All Products - Arrow Systems, Inc. | 20 | (overview, see 3.4) |
| 2 | `/all-products/digital-label-printers/arrow-digital-label-printers/` | 2 | ArrowJet Digital Label Printers | Digital Label Printers by ArrowJet \| Inkjet Solutions | 14 (12 visible) | **Category listing** (the design's own example) |
| 3 | `/all-products/digital-label-printers/trojan-digital-label-printers/` | 2 | Digital Label Printers by Trojan | Digital Label Printers by Trojan - Arrow Systems, Inc. | 3 | Category listing |
| 4 | `/all-products/label-finishers/` | 1 | Label Finishers | High-Performance Label Finishers \| Arrow Systems, Inc. | 11 | Category listing |
| 5 | `/all-products/label-finishers/arrow-label-finishers/` | 2 | Digital Label Finishing Equipments | Digital Label Finishers: Enhance Your Labeling Process | 10 | Category listing |
| 6 | `/all-products/printers/` | 1 | Printer | Advanced Digital Printers \| Arrow Systems, Inc. | 10 | Category listing |
| 7 | `/all-products/printers/gerber-printers/` | 2 | Printers by Gerber | Printers by Gerber - Arrow Systems, Inc. | 1 | Category listing |
| 8 | `/all-products/printers/trojan-printers/` | 2 | Printers by Trojan | Printers by Trojan - Arrow Systems, Inc. | 2 | Category listing |
| 9 | `/all-products/printers/quicklabel-printers/` | 2 | Printers by QuickLabel | Printers by QuickLabel - Arrow Systems, Inc. | 2 | Category listing |
| 10 | `/all-products/digital-flexible-packaging-printers/` | 1 | Digital Flexible Packaging Printer | Digital Flexible Packaging Printers \| Arrow Systems | 4 | **Product line page** (the design's own example) |
| 11 | `/all-products/corrugate-and-end-of-line-printing/` | 1 | Corrugate and End of Line Printing | Corrugate and End of Line Printing - Arrow Systems, Inc. | 5 | Category listing |
| 12 | `/all-products/corrugate-and-end-of-line-printing/printers-by-limitronic/` | 2 | Printers By Limitronic | Limitronic Printers: V6 Lite, V6 Standard & V7 Color | 5 | Category listing |
| 13 | `/all-products/print-and-cut-systems/` | 1 | Print And Cut Systems | Print and Cut Systems - Arrow Systems, Inc. | 2 | Category listing |
| 14 | `/all-products/plotters/` | 1 | Gerber Plotters | Gerber Plotters \| Arrow Systems for Precision Cutting | 3 | Category listing |
| 15 | `/all-products/fume-extraction/` | 1 | Fume Extraction | Fume Extraction - Arrow Systems, Inc. | 2 | Category listing |
| 16 | `/all-products/routers/` | 1 | Routers | Routers - Arrow Systems, Inc. | 2 | Category listing |

**Redirects on the live site (keep them as 301s):**

| From | To |
|---|---|
| `/all-products/digital-label-printers/` | `/all-products/digital-label-printers/arrow-digital-label-printers/` |
| `/all-products/printers/arrow-printers/` | `/all-products/printers/` |
| `/all-products/label-finishers/lemorau-label-finishers/` | `/all-products/label-finishers/` |
| `/all-products/label-finishers/quicklabel-label-finishers/` | `/all-products/label-finishers/` |
| `/all-products/cardboard-box-printing/` | `/digital-box-printers/` (a root page, not a category) |
| `/all-products/printers/kerajet-printers/` | `/` (homepage) |

Note: `/all-products/digital-label-printers/` only exists as a redirect, but it's still the **parent path** of two
real pages (#2, #3). In Craft it's a **hidden parent entry** (see 3.1).

### 1.3 SEO on the live pages (carry over as-is)

Every real page has:
- an **SEO title**, a **canonical** (its own URL, with a trailing slash), and **JSON-LD schema**
- **robots** `follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large` (the **Rank Math** format)

Some pages have weaker SEO, which is copied **as-is** (parity first) but listed for improvement (D7):

| Page | Issue |
|---|---|
| `/all-products/printers/trojan-printers/`, `/printers/quicklabel-printers/`, `/routers/` | **No meta description**, no social image |
| `/all-products/plotters/` | No social image |
| `/all-products/corrugate-and-end-of-line-printing/` | Description is "Printhead resolution: 180dpi" |
| `/all-products/printers/gerber-printers/` | Description is "Printing Technology: Thermal transfer" |
| `/all-products/digital-label-printers/trojan-digital-label-printers/` | Description is "DIGITAL LABEL PRINTERS BY TROJAN" |

The exact values per page come from the live `<head>` (cached copies are in the session scratchpad and are re-fetched
before entry).

### 1.4 Products (categories list products)

The live site has **107 product pages** (`product-sitemap.xml`, URL pattern `/product/{slug}/`). Every category page
shows product cards, so categories **depend on products** (see section 5).

---

## 2. What the new design specifies

### 2.1 Category listing: `digital-label-printers/index.html` (example: ArrowJet Digital Label Printers)

The design file lists its fields itself:

| # | Block | Content |
|---|---|---|
| 1 | **Header** | Breadcrumb (Home › Products › Category) · label "Product category" · **H1** (red part) · **H2 subtitle** · **intro** (rich text) · "**12** printers" count (automatic) + a short meta line · decorative **cut-out image** (optional) |
| 2 | **About the range** | Repeatable items: **Heading + Text** (3 on the example) |
| 3 | **Built for** | Label, **heading**, repeatable items: **Title + Text** (3 audience cards) |
| 4 | **Products** | Toolbar title (e.g. "ArrowJet Printer Lineup") + search; **product cards from the PRODUCT ENTRIES in this category**: image, name, tagline, key specs (Label / Value rows), link. Search and pagination (9 per page) come from `js/digital-label-printers.js`. |
| 5 | **Help band** (maroon) | "Not sure which printer fits?" + text + 2 buttons (Talk to a Specialist / Request a Quote) |
| 6 | **Related categories** | 3 cards: image, kicker, title, text, link ("Other Categories") |

### 2.2 Product line page: `digital-flexible-packaging-printers/index.html`

This is used by `/all-products/digital-flexible-packaging-printers/`, a richer informational page. Its blocks:

| # | Block | Content |
|---|---|---|
| 1 | Header | H1, intro (rich text), decorative image (optional), printer count (automatic) |
| 2 | Content block | Heading, text, image (optional), link (optional) |
| 3 | **Product groups** (repeatable) | Heading + products (product entries; a group with 1 product shows one wide card) |
| 4 | Numbered points | Heading + items (Heading, Text) |
| 5 | Content block | (the image side alternates) |
| 6 | Tabs | Heading + tabs (Name, Text, Image optional) |
| 7 | Point list | Heading + items (Bold title, Text) |
| 8 | Closing text | Heading + Text |
| — | Help band | As above |

### 2.3 Assets in the design

- **Category images:** `assets/categories/web/` (`category-digital-label-printers.webp`, `…-label-finishers`,
  `…-print-cut-inline`, plus 14 `fp-*`/`fpp-*` images for the flexible packaging page).
- **Product card images:** `assets/products/web/` (24 `listing-*.webp` images).
- **Styles and behaviour:** `digital-label-printers/css/digital-label-printers.css`,
  `js/digital-label-printers.js`, and `digital-flexible-packaging-printers/css|js`.

### 2.4 Live → design

The live pages are free-form Elementor layouts; the design is a **fixed structure + optional blocks**. Each page's
content is **re-arranged**: H1/H2/intro → header, headed paragraphs → About items, audience boxes → Built for,
product lists → product cards (from product entries), anything longer → the product-line blocks of 2.2.

---

## 3. Content model in Craft

### 3.1 Sections

| Section | Type | URI format | Template | Notes |
|---|---|---|---|---|
| **All Products** (`allProducts`) | **Single** | `all-products` | `products/_index` | The `/all-products/` overview page |
| **Product Categories** (`productCategories`) | **Structure**, max 2 levels | `all-products/{% if object.parent %}{{ object.parent.slug }}/{% endif %}{{ object.slug }}` | `products/_category` | Level 1 = `/all-products/label-finishers`, level 2 = `/all-products/label-finishers/arrow-label-finishers`. **Structure** = drag to reorder and nest. |

**Trailing slash:** set `->addTrailingSlashesToUrls()` in `config/general.php`, so URLs match live
(`/all-products/label-finishers/`). This applies site-wide (the same applies to the industries plan).

**Hidden parent:** `/all-products/digital-label-printers/` is only a redirect on live but is the parent of two real
pages. Create it as a **level-1 entry** with slug `digital-label-printers` that is **disabled** (or has a "Redirect
to" field). Its two children keep their URLs, and the redirect (3.5) handles the parent URL. Craft's redirects only
run when a URL would otherwise 404, so the parent entry must be **disabled** for the redirect to apply.

**Slug safety:** editors must **not** change a published category's slug, because the URL changes. Put a warning in
the slug instructions.

### 3.2 Entry type "Category": fields by tab

Reuse existing fields where they fit (✱ = already on staging).

**Card tab** (used by "Related categories", homepage section C and menus)

| Field | Handle | Type | Notes |
|---|---|---|---|
| Title | `title` | — | The category **name** (e.g. "Digital Label Finishers"); breadcrumb, cards, menus |
| Slug | `slug` | — | **Must equal the live slug** |
| Card Image | `cardImage` | Assets (1 image) | From `assets/categories/web/` |
| Card Kicker | `cardKicker` | Plain Text | e.g. "Finishing" |
| Card Summary | `cardSummary` | Plain Text | e.g. "Die-cutting, slitting and finishing for printed label rolls." |
| Card Link Text | `cardLinkLabel` | Plain Text | e.g. "View finishers" |

**Header tab**

| Field | Handle | Type | Notes |
|---|---|---|---|
| Heading (H1) | `sectionHeading` ✱ | Plain Text | `[brackets]` = red |
| Subtitle (H2) | `subheading` ✱ | Plain Text | |
| Intro | `categoryIntro` | **Rich text** (D1) or Plain Text | |
| Meta Line | `headerMeta` | Plain Text | e.g. "Inkjet label and packaging printing" (the count is automatic) |
| Header Image | `headerImage` | Assets (1) | Decorative cut-out, optional |

**Sections tab**

| Field | Handle | Type | Notes |
|---|---|---|---|
| About Items | `aboutItems` | Table (Heading, Text multi-line) | The headed paragraphs |
| Built For Label | `eyebrow` ✱ | Plain Text | e.g. "Who it's for" |
| Built For Heading | `builtForHeading` | Plain Text | `[brackets]` = red |
| Built For Items | `builtForItems` | Table (Title, Text) | Audience cards |
| Products Heading | `productsHeading` | Plain Text | e.g. "ArrowJet Printer Lineup" |
| Products | `categoryProducts` | **Entries (Products)** | The cards, **in this order** (see 5) |
| Extra Blocks | `pageBlocks` | Matrix (**Blocks view**) | The product-line blocks of 2.2: Content Block, Product Group, Numbered Points, Tabs, Point List, Closing Text. Only the flexible packaging page needs them at first. |

**Related tab**

| Field | Handle | Type | Notes |
|---|---|---|---|
| Help Heading | `helpHeading` | Plain Text | Default "Not sure which printer fits?" |
| Help Text | `helpText` | Plain Text | Default "Tell us your application, materials and run lengths." |
| Related Categories | `relatedCategories` | Entries (Product Categories, max 3) | Empty = 3 siblings chosen automatically |

Help band buttons: "Talk to a Specialist" (phone) and "Request a Quote" come from **Globals → Site Settings**.

**SEO tab:** see section 4.

**Layout choice:** the template shows the **product-line layout** when *Extra Blocks* has content (or add a Layout
dropdown, as for Text + Media on the homepage).

### 3.3 Matrix "Extra Blocks" block types (product-line page)

| Block type | Fields |
|---|---|
| Content Block | Heading ✱, Body (rich text), Image ✱ (`mediaImage`), Link ✱ (`ctaLink`) |
| Product Group | Heading ✱, Products (Entries → Products) |
| Numbered Points | Heading ✱, Items (Table: Heading, Text) |
| Tabs | Heading ✱, Tabs (Table: Name, Text) + optional image per tab (or a nested Matrix) |
| Point List | Heading ✱, Items (Table: Title, Text) |
| Closing Text | Heading ✱, Body |

All in **Blocks** view: Link fields can't be edited in a Cards slideout in Craft 5.11.4 (`DEVELOPMENT_PHASES.md`, Phase 3).

### 3.4 All Products page (`/all-products/`)

The live page lists 20 products in sections. The design has **no specific template** for it (the breadcrumb only
links to it). Proposal: header (H1 "All Products", intro) + a grid of the **level-1 categories** (card fields) +
SEO. → **Decision D5.**

### 3.5 Redirects (`config/redirects.php`)

```php
return [
    ['from' => 'all-products/digital-label-printers', 'to' => 'all-products/digital-label-printers/arrow-digital-label-printers/', 'statusCode' => 301],
    ['from' => 'all-products/printers/arrow-printers', 'to' => 'all-products/printers/', 'statusCode' => 301],
    ['from' => 'all-products/label-finishers/lemorau-label-finishers', 'to' => 'all-products/label-finishers/', 'statusCode' => 301],
    ['from' => 'all-products/label-finishers/quicklabel-label-finishers', 'to' => 'all-products/label-finishers/', 'statusCode' => 301],
    ['from' => 'all-products/cardboard-box-printing', 'to' => 'digital-box-printers/', 'statusCode' => 301],
    ['from' => 'all-products/printers/kerajet-printers', 'to' => '/', 'statusCode' => 301],
];
```

Check with and without the trailing slash. `/digital-box-printers/` isn't migrated yet; until it is, that redirect
lands on a 404 on staging (expected).

---

## 4. SEO: keep the rankings

| Live (Rank Math) | Craft field (SEO tab) | Output in `<head>` |
|---|---|---|
| SEO title | `seoTitle` | `<title>` (fallback: entry title + site name) |
| Meta description | `seoDescription` | `<meta name="description">`, `og:description` |
| Social image | `socialImage` (Assets, 1) | `og:image` / `twitter:image` (fallback: Card Image) |
| Robots | `seoNoIndex` (Lightswitch, default off) | Default = the live tag; on = `noindex` |
| Canonical | automatic | `<link rel="canonical" href="{entry.url}">` (trailing slash) |
| Schema | automatic | JSON-LD `CollectionPage` + `BreadcrumbList` (Home › Products › Parent › Category) |

Output from `_layouts/base.twig` (a shared `{% block seo %}`), reusable for industries, products and the blog. The paid
**SEOmatic** plugin is the alternative (D3).

---

## 5. The products dependency

Category pages are **mostly product cards**, and the design wants them to come from **product entries** (edited once,
shown on every category, industry and homepage section that uses them). So this migration includes a
**Products section with card-level data**. Full product detail pages are a separate, later migration.

| Item | Plan |
|---|---|
| Section **Products** (`products`) | Channel; URI `product/{slug}` (the live pattern), so URLs are already right when detail pages come |
| Card fields | Title (name), slug (= live slug), **Card Image**, **Tagline**, **Key Specs** (Table: Label, Value), Live Page URL (interim) |
| Which products | Only the ones shown on the 16 category pages (≈ 60–70 unique, deduplicated), not all 107 |
| Category link | Set on the **category** (*Products* Entries field), which keeps the live order per category; a product can be on several categories |
| Card link (interim) | Until product detail pages exist on staging, cards link to the **live product URL** (`https://arrsys.com/product/{slug}/`) → **decision D2** |
| Data source | The live category pages (card name, image, tagline, specs as shown) + the design's 24 `listing-*.webp` card images. The practice project's WooCommerce export (`craft-cms-practice/storage/imports/products.json`) can pre-fill names/slugs. |

---

## 6. Front end

| File | Based on |
|---|---|
| `web/assets/css/digital-label-printers.css` (+ the flexible packaging CSS), `web/assets/js/digital-label-printers.js` | Copied unchanged from the design (loaded on category pages only) |
| `templates/products/_category.twig` | `digital-label-printers/index.html` (listing layout) + `digital-flexible-packaging-printers/index.html` (product-line blocks) |
| `templates/products/_index.twig` | `/all-products/` (D5) |
| `templates/_components/product-card.twig` | One product card (image, name, tagline, specs, link). Shared by categories, industries and the homepage. |
| `templates/_components/category-card.twig` | One category card (Related categories, homepage section C) |
| `templates/_components/help-band.twig` | The maroon help band (shared with industries) |

Check each against the design files token by token, as for the homepage.

---

## 7. Connecting it to the rest of the site

- **Header menu** (Main Menu → Products mega menu): the category items (Digital Label Printers, Label Finishers, Print & Cut Inline, Flexible Packaging Printers, UV & Direct-to-Package, Partner Product Lines…) are **Custom URL** items to arrsys.com. Switch the ones that match a migrated category to **Entry** links.
- **Footer menu** (Products column): the same.
- **Homepage section C (Equipment Category Showcase):** built from category entries (Card fields), since this plan provides them.
- **Breadcrumbs:** Home › Products (`/all-products/`) › Parent › Category.

---

## 8. Migration steps (phases)

Back up the database before each phase. Record each phase in `DEVELOPMENT_PHASES.md`.

| Phase | Work | Result |
|---|---|---|
| **0. Decisions** | D1–D7 (section 10) | |
| **1. Groundwork** | Trailing-slash setting; SEO fields + `<head>` output; `config/redirects.php` (3.5); CKEditor if D1 = yes | URLs, SEO and redirects ready |
| **2. Products (cards)** | Section **Products** + card fields; create the ≈ 60–70 products shown on category pages, with card images | Product cards available |
| **3. Category structure** | Fields (3.2, 3.3), entry type **Category**, Structure section **Product Categories**, Single **All Products** | CRUD in the CP |
| **4. Templates** | Copy CSS/JS; `_category.twig`, `_index.twig`, card + help band components; token-check against the design | Pages render in the new design |
| **5. Pilot: 2 pages** | `/all-products/digital-label-printers/arrow-digital-label-printers/` (listing) and `/all-products/digital-flexible-packaging-printers/` (product line), both full with live SEO | Review both layouts with you before the rest |
| **6. Content: remaining 14** | Per page: re-fetch live, set the slug and parent, copy SEO from the live `<head>`, re-arrange the content into the fields, assign products in live order. Pre-fill with a one-off script, then **check every page by hand**. | All 16 pages + the hidden parent |
| **7. Connect** | Menus/footer → Entry links; homepage section C | Site-wide links use Craft pages |
| **8. Verify** | Section 9 checklist | Sign-off |

---

## 9. Acceptance checklist

- [ ] **URLs:** all 16 real pages return **200** at the exact live path (with the trailing slash).
- [ ] **Redirects:** all 6 redirect URLs return **301** to the same targets as live (with and without the trailing slash).
- [ ] **SEO parity:** a script compares each page's `<head>` with live: title, description, canonical path, robots,
      `og:*`. **0 differences** except the domain (and the intentional D7 fixes, listed).
- [ ] **H1 parity:** each H1 matches live (or the change is listed in the log).
- [ ] **Design:** the two pilot pages match their design files (token comparison); the others use the same structure.
- [ ] **Products:** every product card shown on a live category page is on the Craft page, **in the same order**;
      search and pagination work; the count is right.
- [ ] **CRUD:** create / edit / reorder / nest / disable / delete a test category in the CP and see the URL and
      listing follow; a disabled category 404s (or redirects if a rule exists).
- [ ] **Links:** breadcrumbs, related categories, menus, footer and homepage section C point to the right pages.
- [ ] **Quality:** no JavaScript errors, no broken images, alt text on product images, mobile layout matches.

---

## 10. Decisions needed before building

| # | Question | Recommendation |
|---|---|---|
| **D1** | Install **CKEditor** (free, first-party) for rich-text intros and blocks? | **Yes**. Live pages use lists, bold and links. |
| **D2** | Product cards: link to the **live product page** until product detail pages are built on Craft? | **Yes** (interim), then switch when product pages are migrated |
| **D3** | SEO: own fields or the paid **SEOmatic** plugin? | Own fields now; revisit SEOmatic (sitemaps, redirects UI) before launch |
| **D4** | Keep the 6 redirects exactly as live, incl. `kerajet-printers → /` and `cardboard-box-printing → /digital-box-printers/`? | **Yes** (parity) |
| **D5** | `/all-products/`: build it now (header + category grid), or keep it as the live page until later? | Build it now (simple) so the breadcrumb works |
| **D6** | WooCommerce `/product-category/…` archives (live and indexable, referenced by every product page's breadcrumb schema, but not linked visibly or in the sitemap): **301-redirect** each to the matching `/all-products/` page, or rebuild them? | **301-redirect** each one to its closest `/all-products/` page (e.g. `/product-category/label-finishing-equipments/` → `/all-products/label-finishers/`). First check **Google Search Console** (or a `site:arrsys.com/product-category` search) for which ones are indexed or get traffic. Confirm with the senior/SEO owner. |
| **D7** | Fix the weak SEO descriptions (1.3) during migration, or copy them as-is? | Copy as-is first; improve as a separate, approved change |

---

## 11. Risks

| Risk | Mitigation |
|---|---|
| A URL changes (slug edit, wrong parent, missing trailing slash) | Structure URI format, trailing-slash setting, slug warning, URL check |
| Redirect lost, or shadowed by an entry | `config/redirects.php`; keep redirect sources as **disabled** entries or none; check 301s |
| SEO values differ | Copied from live, automated parity check |
| Products missing or in the wrong order | Products created from what each live page shows; order set per category; count check |
| Live content changed since it was fetched | Re-fetch each page right before entry |
| Craft 5.11.4 Link fields in slideouts | Matrix in **Blocks** view |
