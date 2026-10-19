# Industries Migration Plan: live arrsys.com → Craft (local staging)

**Goal:** move the live industry pages into Craft as **real, structured content** (one entry per industry, with
create / edit / delete / reorder in the control panel) instead of the live site's free-form Elementor pages, and build:
- the **Industries listing** and the **industry detail** pages in the new design
- **homepage section E** "Industries", with editor control over which industries appear

Every live URL is kept, and SEO is preserved. This follows the same approach as the blog (Phase 13) and the
exhibitions (Phase 16).

**Status:** **built** (2026-10-14, `DEVELOPMENT_PHASES.md` → Phase 20). Changes from this plan, as asked on 2026-10-14:
every industry page is a **Page Builder** (the fixed tabs of §3.2 became blocks: Industry Header, Recommended
Products, Content Block, Contact Form, Other Industries, Photo Gallery, Video, Rich Text, CTA Band), and **D1** is
**dynamic product selection** from the Products section (picked products, or products from product categories),
since the products now exist. D2–D8: the recommendations below.
This version (2026-10-14) replaces the 2026-10-13 draft. Since then, trailing slashes, CKEditor, the shared SEO
`<head>` output and the import/verify tooling all exist on staging, so the plan is shorter and re-checked against live.
**Sources checked (2026-10-14):**
- the live `page-sitemap.xml`, `/industries/` and the 12 industry pages
- the WordPress REST API (content + dates) and each page's `<head>` SEO
- the design `Arrowsystem_local/industries/` (`index.html`, `industry-detail.html`, CSS/JS, `assets/industries/web/`)
  and homepage section E in `Arrowsystem_local/index.html`
- staging's menus and blog content

---

## 1. What exists on the live site

### 1.1 Pages (all 200, all in `page-sitemap.xml`, canonical = their own URL)

| # | Live URL (kept exactly) | Live listing name | On `/industries/`? | Live H1 | What the page holds |
|---|---|---|---|---|---|
| 1 | `/aircraft-interior-placards/` | Aircraft Interior | ✅ | Aircraft Interior Placards and Instrument Panel Decals | ~490 words, image galleries, 2 products |
| 2 | `/digital-printing-for-beverage-labels/` | Beverages Labels | ✅ | Digital Printing Solutions for Beverage Labels | ~470 words, gallery, 1 product |
| 3 | `/decorative-solutions-for-building-materials/` | Building Materials | ✅ | Decorative Solutions For Building Materials | ~140 words, images |
| 4 | `/chemical-label-printing/` | Chemical Label Printing | ✅ | Chemical Labels Printing | ~380 words, 2 products |
| 5 | `/flexible-packaging-and-custom-mylar-bags/` | Flexible Packaging | ✅ | Flexible Solutions for Your Flexible Packaging | ~310 words, 3 products, contact form |
| 6 | `/government-and-military/` | Government  & Military | ✅ | Government & Military | ~190 words, gallery, 1 product |
| 7 | `/label-production-and-in-house-labeling/` | Label Packaging Printing | ✅ | Digital Label Printers and Label Cutters | ~180 words, 2 videos, 1 product |
| 8 | `/manufacturing-and-industrial-labels/` | Manufacturing & Industrial | ✅ | Manufacturing and Industrial Labels | ~230 words, gallery, 1 product |
| 9 | `/pharmaceutical-labeling/` | Pharmaceutical Labelling | ✅ | Digital Printing Solution For Pharmaceutical labels | ~260 words, gallery, 2 videos, 1 product, contact form |
| 10 | `/signs-and-graphics/` | Signs and Graphics | ✅ | Signs and Graphics | ~180 words, gallery, 1 product |
| 11 | `/corrugation-printing/` | — | ❌ (in the header menu) | Your Ultimate Corrugation printing & Packaging Solution | ~620 words, 1 product, contact form |
| 12 | `/wallpaper-printer/` | — | ❌ | ArrowJet Aqua 800M | Really a product page (~360 words, 27 images, specs) |
| — | `/industries/` | listing | — | "Industries That we serve" | 10 image cards, A–Z, no intro |

### 1.2 SEO (Rank Math)

Each page has its own SEO title (e.g. "Flexible Packaging Solutions & Custom Mylar Bags"), meta description,
canonical = its own URL with the trailing slash, robots `follow, index, max-snippet:-1, max-video-preview:-1,
max-image-preview:large`, Open Graph tags (with a social image on most) and schema. **No live SEO bugs were found**
(unlike the exhibitions).

### 1.3 Links that point at these pages today

- **Main menu** (Industries mega menu: 6 industries + "View all industries") and **footer** (3 industries + "All
  Industries"): Custom URL items pointing at arrsys.com.
- **35 blog posts (49 links)** link to industry pages on arrsys.com (the blog import kept links to not-yet-migrated
  pages pointing at live).
- **Homepage section E** (not built yet).

---

## 2. What the new design specifies

| Design file | Becomes | Sections |
|---|---|---|
| `industries/index.html` | `/industries/` | **1** header: label "Markets we serve", "Industries [We Serve]", intro (proposed copy), count "10 industries", a decorative mosaic of industry icons · **2** grid of equal cards (image, icon, number 01–10, name, short summary, "Explore industry →"), 3/2/1 columns · **3** maroon help band "Not sure which equipment fits?" |
| `industries/industry-detail.html` (sample: Flexible Packaging) | Each industry page | **1** header: label, H1 with a red part, intro, **Applications** tags, image, 2 buttons · **2** **Recommended equipment**: heading, intro, **product cards** (image, name, tagline, specs, link), "View all" link · **3** **content blocks** (repeatable: label, heading, rich text, image, optional link; the image side alternates) · **4** **contact panel + form** (Name*, Email*, Contact No.*, Message) · **5** "Other Industries" (3 cards) |
| `index.html` section **E** | Homepage "Industries" | Label "Markets we serve", heading, "View all industries", a **carousel of 6 tiles** (photo + name) with chips and arrows. The tiles are **marketing groupings**, not 1:1 industries: "Food And Beverages Labels" → beverage page, "Industrial & Chemical Labels" → manufacturing page, "Wines & Spirits" → **no live page** (links to the listing) |

Assets: `industries/css/industries.css`, `industry-detail.css`, `industries/js/industry-detail.js` (the form check and
detail behaviour); images in `assets/industries/web/`: the 10 listing photos, the 6 homepage tile photos, the Flexible
Packaging hero/content photos. The live pages are free-form, so each one is **re-arranged** into the design's blocks
(section 7); it is not a copy-paste.

---

## 3. Content model in Craft

### 3.1 Sections

| Section | Type | URI | Template | Notes |
|---|---|---|---|---|
| **Industries** (`industries`) | **Structure** (1 level) | `{slug}` → `/flexible-packaging-and-custom-mylar-bags/` | `industries/_entry` | One entry per industry. Drag order = listing order and the 01–10 numbers. Root URLs, exactly as live |
| **Industries Page** (`industriesPage`) | **Single** | `industries` | `industries/_index` | Listing texts, help band, SEO |

### 3.2 Entry type "Industry"

| Tab | Field | Handle | Type | Notes |
|---|---|---|---|---|
| Card | Name | `title` | — | Listing / card / breadcrumb name (D2) |
| Card | Slug | `slug` | — | **Must equal the live slug**; the instructions warn editors not to change it |
| Card | Card Photo | `featuredImage` ✱ | Assets (1) | The design's listing photo (D8) |
| Card | Card Summary | `cardSummary` | Plain Text (multi-line) | The short text on cards |
| Card | Icon | `menuIcon` ✱ | Dropdown (design sprite) | Card icon, header mosaic, mega menu |
| Card | Show on Listing | `showOnListing` | Lightswitch (default on) | Off for Corrugation Printing and Wallpaper Printer (as live, D5) |
| Header | Label, Heading (H1), Intro | `eyebrow` ✱, `sectionHeading` ✱, `sectionLead` ✱ | Plain Text | H1 `[brackets]` = red, e.g. "Flexible Solutions for Your [Flexible Packaging]" |
| Header | Applications | `applications` | Table (one column) | The tags under the intro |
| Header | Header Image | `mediaImage` ✱ | Assets (1) | With alt text |
| Header | Buttons | `primaryCta` ✱, `secondaryCta` ✱ | Link | Defaults: "Talk to a Specialist" → `#contact`, "Recommended Equipment" → `#equipment` |
| Equipment | Heading, Intro | `equipmentHeading`, `equipmentIntro` | Plain Text | e.g. "Printers for [Flexible Packaging]" |
| Equipment | Products | `productCards` | Matrix, Blocks view: **Product Card** (image, name, tagline, specs table, link) | **D1**: interim cards until a Products section exists |
| Equipment | View All Link | `viewAllLink` | Link | e.g. "All flexible packaging printers" |
| Content | Content Blocks | `industryBlocks` | Matrix, Blocks view | **Text + Image** (label, heading, **rich text** (CKEditor), image, YouTube link, optional link) · **Gallery** (heading, intro, photos) |
| Contact | Label, Heading, Text | `contactEyebrow`, `contactHeading`, `contactText` | Plain Text | Defaults "Contact With Our Team Now" / "Tell us your application…". Phones, email, address: Site Settings (already built) |
| Related | Other Industries | `relatedIndustries` | Entries (Industries, max 3) | Empty = the next 3 in the listing order, never the current page |
| SEO | SEO Title, Meta Description, Social Image, Hide from Search Engines, Canonical URL | `seoTitle` … `seoCanonical` ✱ | — | The same SEO fields as blog and exhibitions |

✱ = existing field reused. New photo fields upload to **Images → industries/**.
Matrix fields use **Blocks** view (the Craft 5.11.4 Link-field bug in Cards view).

### 3.3 Industries Page (Single)

Label, Heading ("Industries [We Serve]"), Intro (D6), help band (heading, text, button), SEO. The count and the icon
mosaic are generated from the entries.

### 3.4 Homepage section E: "Industries" (new Homepage Sections block) — **D3**

| Option | How it works | Fits the design? |
|---|---|---|
| **A. Tiles** (recommended) | A list of up to 6 **tiles**, each with **Name**, **Photo** and **Link** (an Industry entry **or** any URL), drag to order | Yes, exactly: tile names and photos can differ from the industry ("Food And Beverages Labels"), and "Wines & Spirits" can link to the listing |
| B. Industry picker | Pick up to 6 Industry entries (like the exhibitions section); tiles show the industry's own name and card photo | Only partly: the names change to the industry names, and "Wines & Spirits" (no page) can't appear |

Either way: label, heading, "View all industries" link, anchor ID; placed after D (where the design has it).

---

## 4. URLs, links and menus

| Item | Craft |
|---|---|
| `/industries/` + 12 × `/{slug}/` | Same paths and trailing slash as live |
| Root-level clash check | Industries share the root with blog posts and exhibitions; the import reports any slug already in use (none expected) |
| Main menu + footer industry links | Switched to **Entry** links → they follow the entries and work on staging |
| **35 blog posts / 49 links** to industry pages on arrsys.com | Rewritten to the new root-relative URLs once the pages exist (the same rule the blog import uses for migrated pages) |

---

## 5. SEO

Exactly as live, with the shared `_partials/seo` output: SEO title only where it differs from "{name} - Arrow Systems,
Inc." (most do), meta description, canonical = own URL, the live robots tag, Open Graph with the live social image.
Schema: `WebPage` + `BreadcrumbList` (Home › Industries › Industry); `CollectionPage` on `/industries/`. A
`verify.py` (like the blog's and exhibitions') checks **0 differences** against the live `<head>` for all 13 URLs.

---

## 6. Front end

| File | Based on |
|---|---|
| `web/assets/css/industries/{industries,industry-detail}.css`, `web/assets/js/industries/industry-detail.js` | The design's files, unchanged |
| `templates/industries/_index.twig` | Header + mosaic + count, card grid (Show on Listing, structure order), help band |
| `templates/industries/_entry.twig` | Header, Recommended equipment, content blocks (alternating), contact panel + form, Other Industries |
| `templates/_components/industry-card.twig` | Listing + "Other Industries" card |
| `templates/_components/contact-form.twig` | The contact panel + form, shared (later for products etc.) |
| `templates/_blocks/home/industries.twig` | Homepage section E (carousel; the controls come from the homepage's `site.js`) |

**Contact form:** like the newsletter, the design's form doesn't send anything yet. `industry-detail.js` checks the
fields and says so. It needs the HubSpot IDs (pending from the client) before launch.

---

## 7. Content migration

One import script in `storage/imports/industries/`, same pattern as blog and exhibitions, then a hand check of each page:

1. **Read** the 12 pages + `/industries/` from the REST API and each page's `<head>` (saved in `data/`).
2. **Card:** name (D2), slug, card photo (design, D8), summary, icon, Show on Listing.
3. **Header:** H1 → Heading (red part as the design shows it for Flexible Packaging; the H1 text stays as live), first
   paragraph → Intro, image → Header Image.
4. **Body → content blocks:** each "heading + text (+ image)" part → a **Text + Image** block (rich text keeps lists
   and links); image galleries ("Other applications", "Facts and figures") → **Gallery** blocks; YouTube videos →
   the block's video link. Photos are downloaded once into Assets → industries/ with their alt texts.
5. **Products** shown on the page → **Product Cards** (image, name, tagline, link to the live `/product/…/` page; D1).
6. **SEO** from the live `<head>`. **Contact** texts: defaults.
7. **Flexible Packaging** = the pilot, entered from the design's own sample (its texts, applications, 3 product cards,
   blocks, photos), then reviewed with you.
8. **Report** per page: what went where, and anything that didn't fit (for a hand fix).

---

## 8. Phases

Back up before each phase; record each in `DEVELOPMENT_PHASES.md`.

| Phase | Work | Result |
|---|---|---|
| **0. Decisions** | D1–D8 | |
| **1. Structure** | Fields, Industry entry type, Matrix block types, sections, homepage block type | CRUD in the CP |
| **2. Templates** | Copy CSS/JS; listing, detail, card, contact form, homepage E; token-check against the design | Pages in the new design |
| **3. Pilot** | Flexible Packaging from the design sample + live SEO; review with you | Sign-off on fields and layout |
| **4. Import** | The other 11 + the listing page; report; hand-check each page | All pages migrated |
| **5. Connect** | Homepage E, menus/footer → entries, rewrite the 49 blog links | Site-wide |
| **6. Verify** | Section 10 | Sign-off |

---

## 9. Decisions needed before building

| # | Question | Recommendation |
|---|---|---|
| **D1** | **Recommended equipment**: interim **Product Cards** per industry now (B), or build a **Products** section first (A)? | **B now**: no Products section exists yet; when one is built, the cards become an Entries field pointing at it |
| **D2** | **Names**: live listing names ("Beverages Labels", "Pharmaceutical Labelling", "Government  & Military") or the design's ("Beverage Labels", "Pharmaceutical Labeling", "Government & Military")? | **As live** (matches your exhibitions choice); editors can rename in the CP |
| **D3** | **Homepage section E**: tiles (A) or an industry picker (B)? See 3.4 | **A. Tiles**: it's the only way to match the design's tiles exactly |
| **D4** | The **"Wines & Spirits"** tile has no live page. Keep it linking to `/industries/` (as the design), or drop it? | **Keep as the design** (only possible with D3 = A) |
| **D5** | **Corrugation Printing** and **Wallpaper Printer**: migrate as industries hidden from the listing (as live)? Wallpaper Printer is really a product page | **As live**: both migrated, hidden from the listing, URLs working; revisit Wallpaper Printer when Products are built |
| **D6** | **Listing intro and help band**: the design's proposed copy (live has only the title "Industries That we serve")? | **Design copy** (it's part of the approved design), marked for client approval |
| **D7** | **Import**: automatic pre-fill of all 12 + a hand check of each page, or fully by hand? | **Automatic + hand check** (the pages are free-form, so every one gets a look) |
| **D8** | **Card photos**: the design's photos only, no fallback (like the exhibitions)? | **Yes**: the 10 listed industries have design photos; the 2 hidden ones don't need one |

---

## 10. Acceptance checklist

- [ ] **URLs:** `/industries/` and all 12 industry URLs return **200** at the exact live path (trailing slash).
- [ ] **SEO parity:** title, description, canonical, robots, og:image match live for all 13 URLs: **0 differences**.
- [ ] **Content:** every page's text, images, videos and products are present in the design's blocks (per-page report
      reviewed); photos downloaded, alt texts kept, none loading from arrsys.com.
- [ ] **Design:** listing and Flexible Packaging match `index.html` / `industry-detail.html` (token comparison);
      homepage E matches `index.html` E; the other pages follow the same structure.
- [ ] **Behaviour:** contact form validates (doesn't send until HubSpot), videos play, carousel works; no JavaScript
      errors; mobile layout correct.
- [ ] **CRUD:** create a test industry → it appears on the listing with the next number and gets its URL; reorder →
      the listing order changes; Show on Listing off → hidden but URL works; delete → gone.
- [ ] **Links:** menus, footer, homepage E, breadcrumbs and the 49 blog links point to the Craft pages; "Other
      Industries" never shows the current page.

---

## 11. Risks

| Risk | Mitigation |
|---|---|
| Free-form live pages don't map 1:1 to the design blocks | Text + Image and Gallery blocks; per-page report; hand check of every page (D7) |
| A slug changes by accident (URL lost) | Slug instructions warn editors; the URL check in `verify.py` |
| Products don't exist yet | Interim Product Cards (D1), replaced by an Entries field later |
| The contact form doesn't send | The design's own "not connected yet" message until the HubSpot IDs arrive |
| Root-level slug clash with blog posts / exhibitions | The import checks and reports (as it did for exhibitions) |
