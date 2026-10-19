# Blog Migration Plan: live arrsys.com → Craft (local staging), with a Neo page builder

**Goal:** move the live blog (352 posts, the `/blog/` listing and the post categories) into Craft:
- every post and archive keeps **exactly the same URL**, and **SEO is preserved**
- the **new design** is used (`Arrowsystem_local/blogs/index.html` and `blog-detail.html`)
- post content is built with the **Neo** page builder, so editors can manage it easily: add, reorder, nest and
  remove blocks

**Status:** ✅ **built on staging (2026-10-13)** with decisions D1 yes, D2 yes, D3 *migrate tags*, D4 as recommended, D5 automatic, D6 *admin*, D7 keep both. How it was built and what was checked: `DEVELOPMENT_PHASES.md` → Phase 13. **The page builder was then moved from Neo to Craft's Matrix field (Phase 14), so D2 / the Neo license no longer applies.** (The Industries and Product Categories plans are on hold.)
**Sources checked (2026-10-13), all fetched today:**
- the live sitemaps `sitemap_index.xml` → `post-sitemap1.xml`, `post-sitemap2.xml` (346 URLs)
- the WordPress REST API (all **352** posts with their content, the categories, the tag count)
- the live `/blog/`, `/blog/page/2/`, category, tag and author pages, and one post page
- the design files above

---

## 1. What exists on the live site

### 1.1 Posts

| Item | Live |
|---|---|
| Published posts | **352** (346 in the sitemap) |
| URL pattern | **root level**: `/{slug}/`, e.g. `/roll-to-roll-vs-sheet-fed-label-printing-throughput-cost/` |
| Dates | 2018 → 2026 (2026 alone: 124 posts) |
| Author | All by one user, "admin" (ID 1) |
| Built with | **Elementor: 351 posts**; 1 classic-editor post |
| Length | Median ≈ 1,200 words, longest ≈ 3,900 |
| Categories per post | Usually 1 (248 posts), up to 15 |
| Tags per post | Usually 1 (204 posts); 14 posts have none |
| Images | **559 unique images** in post content (almost all on arrsys.com; a few on Unsplash / Google), plus featured images |
| Duplicate slugs | None |

**Elementor widgets used in posts** (these decide the Neo blocks and the import mapping):

| Widget | Uses | Posts using it | Becomes (Neo block, section 3.3) |
|---|---|---|---|
| heading | 4,199 | 350 | **Heading** (H2/H3/H4, feeds "On this page") |
| text-editor | 4,017 | 351 | **Rich Text** (paragraphs, lists, links, tables; 216 tables, 301 definition lists across posts) |
| image | 504 | 212 | **Image** (+ caption) |
| table-of-contents | 251 | 250 | *(not a block)*: generated automatically from the Heading blocks ("On this page") |
| form | 245 | 245 | *(not migrated per post)*: replaced by the design's **Call to Action** block + the shared specialist card (D4) |
| button | 219 | 142 | **Button** |
| video | 119 | 103 | **Video** (YouTube) |
| accordion | 105 | 105 | **FAQ** → **FAQ Item** child blocks |
| media-carousel / gallery / image-gallery / image-carousel | 51 | ≈ 45 | **Gallery** |
| divider | 17 | 17 | **Divider** |
| html | 3 | 1 | **HTML** (admins only) |

### 1.2 Listing and archive pages

| URL | Live | Indexed? | Count |
|---|---|---|---|
| `/blog/` | Post listing, 10 per page, newest first, + sidebar (Product Categories, Recent Blog Posts) | ✅ index (canonical self) | — |
| `/blog/page/2/` … `/blog/page/35/` | Paginated listing | ✅ index (canonical self) | 35 pages |
| `/category/{slug}/`, `/category/{parent}/{child}/` | Category archive (nested, up to 3 levels) | ✅ **index** | **45** categories (one empty: "Financing") |
| `/tag/{slug}/` | Tag archive | ❌ **noindex** | 245 tags |
| `/author/admin/` | Author archive | ❌ **noindex** | 1 |

Note: the categories include two "Food Packaging" entries (`/category/digital-printing/food-packaging/` with 28
posts, and `/category/food-packaging-2/` with 24). Both URLs stay.

### 1.3 SEO (Rank Math)

Every post has its own **SEO title**, **meta description**, **canonical** (its own URL, trailing slash), robots
`follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large`, Open Graph tags, and **schema**:
`BlogPosting`, `WebPage`, `BreadcrumbList`, `Person`. The listing has `CollectionPage` + `BreadcrumbList`.

---

## 2. What the new design specifies

### 2.1 Listing: `blogs/index.html`

- **Header:** breadcrumb, title, decorative illustration.
- **Post list:** the **newest post as a large card**, then the next 9 (**10 per page**). Each card has an image,
  category, date, title, excerpt and "Read more".
- **Sidebar:** Product Categories links, Recent Blog Posts.
- **Pagination.**
- Notes in the file: the live excerpts start with "Table of Contents" + the repeated title, so the design uses each
  post's own **summary line** instead. "Leave a Comment", the "admin" author link and the sidebar stock photo are
  dropped.

### 2.2 Detail: `blogs/blog-detail.html` (example: Roll-to-Roll vs. Sheet-Fed, 3,127 words)

| # | Block | Content |
|---|---|---|
| 1 | **Post header** | Breadcrumb · category link · date · **reading time** (from the word count) · **H1** (red part) · **lead** sentence · **cover image** |
| 2 | **Article + rail** | Rail: **"On this page"** (built from the article's H2s, with scroll-spy) + specialist card ("Not sure which equipment fits?", Talk to a Specialist / Request a Quote). Article: the blocks below. |
| 3 | **Post foot** | "Filed under {category}" + "Back to all articles" |
| 4 | **More Insights** | 3 related/recent post cards |

**Article blocks in the design** (they become the Neo block types):
- an intro paragraph
- **Key Takeaways** (check-list)
- **H2 sections with a short "answer" lead**, and H3/H4
- rich text paragraphs
- **numbered steps** (title + text)
- a **figure with a caption**
- a **definition / spec list** (term + text)
- **callout** and **note** boxes
- a **pro / con comparison** (2 columns)
- a **data table** with a caption
- a **product list** (tag, name, link)
- an **FAQ** accordion
- a closing **Call to Action** box

### 2.3 Design assets

`Arrowsystem_local/blogs/css/blog.css`, `blog-detail.css`, `js/blog.js`, `js/blog-detail.js` (scroll-spy, FAQ,
TOC), and 25 images in `assets/blog/web/`.

---

## 3. Content model in Craft

### 3.1 Sections

| Section | Type | URI | Template | Notes |
|---|---|---|---|---|
| **Blog** (`blog`) | **Channel** | `{slug}` → `/roll-to-roll-…-cost/` | `blog/_entry` | Root-level URLs, exactly as live. Post date = the live publish date. |
| **Blog Page** (`blogPage`) | **Single** | `blog` | `blog/_index` | Listing texts + SEO. Paginated as `/blog/page/2/` (3.4). |
| **Blog Categories** (`blogCategories`) | **Structure** (3 levels) | `category/{% if object.parent %}{{ object.parent.uri\|replace('category/','') }}/{% endif %}{{ object.slug }}` | `blog/_category` | Gives `/category/digital-printing/label-printing/`, as live. Entries (not Craft "categories"), as recommended in Craft 5. |

### 3.2 Entry type "Post": fields

| Tab | Field | Handle | Type | Notes |
|---|---|---|---|---|
| Content | Title | `title` | — | The post title (also the H1); `[brackets]` = red part |
| Content | Slug | `slug` | — | **Must equal the live slug** |
| Content | Post Date | `postDate` | — | The live publish date (drives order, "date", schema) |
| Content | Lead | `sectionLead` ✱ | Plain Text | The summary line under the H1; also the card excerpt |
| Content | Cover Image | `featuredImage` | Assets (1) | The live featured image, with alt text |
| Content | **Article Body** | `articleBody` | **Neo** | The page builder (3.3) |
| Settings | Categories | `blogCategories` | Entries (Blog Categories) | First one = the main category (breadcrumb, "Filed under") |
| Settings | Call to Action | `ctaHeading`, `ctaText`, `ctaLink` ✱ | Plain Text / Link | Optional. The design's closing box (also the replacement for the old per-post form). |
| Settings | More Insights | `relatedPosts` | Entries (Blog, max 3) | Optional. Empty = 3 latest posts from the same category. |
| SEO | SEO Title, Description, Social Image, No-index | `seoTitle`, `seoDescription`, `socialImage`, `seoNoIndex` | — | Section 5 |

Automatic (not fields): **reading time** (word count of the Neo blocks ÷ ≈ 225), **"On this page"** (from the
Heading blocks marked "Show in contents"), the breadcrumb, and schema.

### 3.3 The Neo field "Article Body": block types

Built like the practice project's Neo setup (grid menu with icons, groups, search, Content / Style / Advanced tabs),
but with **blog-specific blocks that match the design**. Nesting uses Neo's parent/child rules.

| Group | Block (handle) | Fields | Children | From live / design |
|---|---|---|---|---|
| **Text** | Heading (`heading`) | Text, Level (H2/H3/H4), Anchor ID (auto from the text), *Show in contents* (H2 default on), Answer lead (optional) | — | Elementor heading · design H2 + `.answer` |
| | Rich Text (`richText`) | Body (**CKEditor**: paragraphs, bold/italic, links, lists, **tables**) | — | Elementor text-editor |
| | Intro (`intro`) | Text | — | design `.post-intro` |
| | Callout (`callout`) | Text (rich), Style: Callout / Note | — | design `.callout`, `.note` |
| | Quote (`quote`) | Quote, Source | — | (1 blockquote on live) |
| **Lists** | Key Takeaways (`takeaways`) | Heading, Items (Table: text) | — | design check-list |
| | Steps (`steps`) | Heading (optional) | **Step** (Title, Text) | design numbered steps |
| | Spec List (`specList`) | Heading (optional), Items (Table: Term, Description) | — | design `<dl>` (301 on live) |
| | Comparison (`compare`) | — | **Column** ×2 (Heading, Items, Style: Pro / Con) | design pro/con |
| **Media** | Image (`image`) | Image, Caption, Size | — | Elementor image · design figure |
| | Gallery (`gallery`) | Images, Columns, Style: Grid / Carousel | — | Elementor gallery / carousels |
| | Video (`video`) | YouTube link, Caption | — | Elementor video (103 posts) |
| | Data Table (`dataTable`) | Caption, Table (Table field; rows/columns) | — | design data table (complex tables can stay in Rich Text) |
| **Products & CTA** | Product List (`productList`) | Heading | **Product Item** (Tag, Name, Link; later an Entries link to Products) | design product list |
| | Button (`button`) | Link, Style | — | Elementor button |
| | Call to Action (`cta`) | Heading, Text, Button | — | design closing box |
| **Q&A** | FAQ (`faq`) | Heading (optional) | **FAQ Item** (Question, Answer rich text) | Elementor accordion (105 posts) · design FAQ |
| **Other** | Divider (`divider`) | — | — | Elementor divider |
| | HTML (`html`) | Code | — | Elementor html (1 post); **admins only** via Neo block permissions |

Editor experience: Neo's **grid** "Add block" menu with icons (as in the practice project; the practice project's
`modules/builderui` adds the search box and the bigger tiles), and blocks collapsible, draggable,
copy/paste-able. Only the Text group is allowed at the top level of a FAQ answer, etc. (Neo child-block rules).

### 3.4 URL, pagination and redirect settings (`config/general.php`, `config/redirects.php`)

| Setting | Why |
|---|---|
| `->addTrailingSlashesToUrls()` | Live URLs end with `/` (canonicals too) |
| `->pageTrigger('page/')` | Craft paginates as `/blog/p2` by default; this makes it `/blog/page/2/`, as live |
| Redirect `/tag/{slug}/` → `/blog/` (301) | Tag pages are **noindex** on live and aren't migrated (D3) |
| Redirect `/author/admin/` → `/blog/` (301) | noindex on live; the design drops the author link |
| Redirect `/category/{empty}/` → `/blog/` | E.g. "Financing" (0 posts), if not migrated |

**URL collisions:** posts, industries and other root pages all share the root (`/{slug}/`). WordPress already keeps
them unique; check that no future Craft root entry (industry, page) takes a post's slug (the import script reports
any clash).

---

## 4. Neo on staging

- **Install:** `composer require spicyweb/craft-neo` (the practice project runs **5.5.13** on the same Craft 5.11.4)
  + `php craft plugin/install neo`. Free trial on localhost.
- **License:** paid, **$49 once + $19/year** for updates (Plugin Store, 2026-10-13), needed on the live domain.
- **CKEditor** (free, first-party) is needed for the Rich Text / FAQ answer / Callout fields. Staging doesn't have it yet.
- Matrix fields inside Neo blocks, if any, use **Blocks** view (the Craft 5.11.4 slideout Link bug).

---

## 5. SEO

| Live (Rank Math) | Craft | Output |
|---|---|---|
| SEO title | `seoTitle` | `<title>` (fallback: post title + site name) |
| Meta description | `seoDescription` | `<meta name="description">`, `og:description` |
| Social image | `socialImage` | `og:image` (fallback: Cover Image) |
| Robots | `seoNoIndex` (default off) | Default = the live tag |
| Canonical | automatic | Post URL with the trailing slash; `/blog/page/N/` canonical = itself (as live) |
| Schema | automatic | `BlogPosting` (headline, datePublished, dateModified, image, author "Arrow Systems, Inc." or "admin" → D6), `BreadcrumbList`; listing `CollectionPage` |

Shared `{% block seo %}` in `_layouts/base.twig` (the same plan as the other migrations).

---

## 6. Front end

| File | Based on |
|---|---|
| `web/assets/css/blog.css`, `blog-detail.css`, `web/assets/js/blog.js`, `blog-detail.js` | Copied unchanged from the design |
| `templates/blog/_index.twig` | `blogs/index.html`: newest large card + 9, paginated by 10 (`{% paginate %}`), sidebar (categories, recent posts) |
| `templates/blog/_category.twig` | Same layout, filtered by category (`/category/…`) |
| `templates/blog/_entry.twig` | `blogs/blog-detail.html`: header, rail (TOC from Heading blocks + specialist card), Neo blocks, post foot, More Insights |
| `templates/blog/blocks/{handle}.twig` | One template per Neo block type |
| `templates/_components/post-card.twig` | Shared by the listing, More Insights and **homepage section K** |

---

## 7. Content migration (automated, then checked)

352 posts is too many to re-type, so a **one-off import script** (PHP console script, run on staging) does the bulk:

1. **Read** each post from the WordPress REST API (`/wp-json/wp/v2/posts`): title, slug, date, modified, categories,
   featured image, and the rendered Elementor HTML. Read the Rank Math SEO title/description from each live page's
   `<head>` (or the Rank Math REST field if enabled).
2. **Convert** the Elementor widgets to Neo blocks using the mapping in 1.1 / 3.3:
   - heading → Heading
   - text-editor → Rich Text, with `<dl>` → Spec List and complex tables kept in Rich Text
   - image → Image
   - video → Video
   - accordion → FAQ + FAQ Items
   - gallery / carousel → Gallery
   - button → Button
   - divider → Divider
   - TOC → dropped (it's automatic)
   - form → dropped (D4)

   The practice project's `storage/imports/_elementor.php` (an Elementor → Matrix converter) is the starting point.
3. **Images:** download each image once (559 + featured) into an Assets folder **Blog**, keep the alt text, and
   rewrite the references.
4. **Categories:** create the 45 category entries with the same slugs/parents first, then assign them to the posts.
5. **Lead / excerpt:** from the Rank Math description or the first sentence (the live excerpts are unusable).
6. **Report:** per post, list anything not converted (unknown widgets, external images, empty blocks) for a
   manual check.

The **design's semantic blocks** (Key Takeaways, Steps, Comparison, Callout, Product List…) are for **new posts and
hand-polished ones**. The automatic import uses the basic blocks. The sample post from the design is entered by
hand as the pilot.

---

## 8. Phases

Back up the database before each phase. Record each phase in `DEVELOPMENT_PHASES.md`.

| Phase | Work | Result |
|---|---|---|
| **0. Decisions** | D1–D7 (section 10) | |
| **1. Groundwork** | Install **Neo** + **CKEditor**; trailing slash + `pageTrigger`; SEO fields + `<head>` output; redirects | Settings in place |
| **2. Structure** | Sections Blog / Blog Page / Blog Categories; Post fields; the **Neo field** with all block types, groups, icons and child rules | CRUD + the page builder in the CP |
| **3. Templates** | Copy CSS/JS; listing, category, post templates, block templates, post card; token-check against the design | Pages render in the new design |
| **4. Pilot (by hand)** | The design's sample post "Roll-to-Roll vs. Sheet-Fed Label Printing" built with the Neo blocks + live SEO; review the editor experience with you | Sign-off on blocks and design |
| **5. Import script** | Build the converter; run on **10 posts** first (mixed years, with video / FAQ / gallery); review | Mapping confirmed |
| **6. Full import** | Categories → all 352 posts → images; read the report; fix what's flagged | All posts migrated |
| **7. Connect** | Homepage section K (latest 3 posts); menu "Guides & Articles" / footer links → `/blog/`; sidebar links | Site-wide |
| **8. Verify** | Section 9 checklist | Sign-off |

---

## 9. Acceptance checklist

- [ ] **Post URLs:** all 346 sitemap URLs (and all 352 posts) return **200** at the exact live path with the
      trailing slash.
- [ ] **Archives:** `/blog/`, `/blog/page/2/` … `/blog/page/35/` and all 45 `/category/…` URLs return 200; tag and
      author URLs 301 to `/blog/`.
- [ ] **SEO parity:** a script compares title, description, canonical path, robots and `og:*` with live for every
      post: **0 differences** (except the domain).
- [ ] **Content:** for every post, the heading count and image count match live (± the dropped TOC/form). Spot-check
      20 posts by eye (old and new, with tables, FAQ, video, gallery).
- [ ] **Design:** the pilot post and the listing match `blog-detail.html` / `index.html` (token comparison);
      "On this page" lists the H2s and scroll-spy works; the FAQ opens/closes; the reading time shows.
- [ ] **Editor experience:** in the CP, an editor can add, reorder, nest (FAQ → items, Steps → steps), copy and
      delete blocks, and preview the post.
- [ ] **CRUD:** create / edit / disable / delete a test post and category; listing, category page and pagination follow.
- [ ] **Quality:** no broken images (all downloaded; none still loading from the live site), alt texts kept, no
      JavaScript errors, mobile layout matches.

---

## 10. Decisions needed before building

| # | Question | Recommendation |
|---|---|---|
| **D1** | Install **CKEditor** (free) for rich text inside Neo blocks? | **Yes**. Posts are rich text (lists, links, tables). |
| **D2** | Buy the **Neo** license for the live site ($49 + $19/year)? | Yes, if Neo is the chosen builder (free to trial on staging) |
| **D3** | Tags: drop them (noindex on live) and **301 `/tag/*` → `/blog/`**, or migrate them? | Drop + redirect |
| **D4** | The per-post Elementor **contact form** (245 posts): replace with the design's CTA box + specialist card, or keep a form per post? | Replace (the design has no form in posts); the shared contact form can come later |
| **D5** | Import: **automatic** for all 352 + manual fixes from the report, or hand-build some? | Automatic for all; hand-polish the most-visited posts later |
| **D6** | Author: keep "admin" (as live schema) or show "Arrow Systems, Inc."? | "Arrow Systems, Inc." in schema; no author link (as the design) |
| **D7** | Keep the two "Food Packaging" categories as on live, or merge one with a redirect? | Keep both for now (parity); merge later as an SEO decision |

---

## 11. Risks

| Risk | Mitigation |
|---|---|
| Elementor HTML converts imperfectly (odd widgets, nested inner sections) | Mapping table, a 10-post trial run, a per-post report, manual fixes |
| URL or pagination format differs | Trailing slash + `pageTrigger('page/')`; URL parity check over all 346 sitemap URLs |
| Images lost or still hot-linked from the live site | Download every image into Assets; the check fails on any arrsys.com image URL |
| SEO values differ | Copied from the live `<head>`; automated parity check |
| Root-level slug clash with other sections | The import reports clashes; editors are warned in the slug field |
| Neo cost / dependency | Trial on staging; license before launch (D2) |
| Live posts change during the work | Re-run the import for posts modified after the first run (it uses the WordPress `modified` date) |
