# Exhibitions Migration Plan: live arrsys.com → Craft (local staging)

**Goal:** move the live exhibitions into Craft as **real, structured content** (one entry per show, with CRUD in the
control panel) instead of the live site's one-WordPress-page-per-show, and build:
- the **homepage section G** "Our Innovation in Focus", with editor control over which shows appear
- the **exhibition listing**, the **upcoming shows** and the **exhibition detail** pages, in the new design

Every live URL is kept, and SEO is preserved.

**Status:** ✅ **built on staging 2026-10-14** (details and checks: `DEVELOPMENT_PHASES.md` → Phase 16), with these decisions:
- **D1** build the upcoming page at `/upcoming-exhibitions/` · **D2** as live: LOUPE and Label Expo 2026 stay two
  entries · **D3** as live: past shows show the **year only**; exact dates only where the live site states them (the
  2026 upcoming shows) · **D4** `ExhibitionEvent` schema · **D5** SEO exactly as live, live bugs included (no fixes) ·
  **D6** automatic fallback + manual selection on the homepage · **D7** card photos = the design's photos only, the
  editor picks the rest, **no fallback** to a gallery photo.
- Because past shows only have a year, the Exhibitions section is a **Structure** (manual order, starting in the live
  listing order) instead of a Channel sorted by date.
**Sources checked (2026-10-14):**
- the live `page-sitemap.xml`, `/exhibitions/` and its 28 linked show pages
- the WordPress REST API (all 28 show pages + `/exhibitions/` + the old `/upcoming-exhibitions/` page, with content)
- the design files `Arrowsystem_local/index.html` (section G) and `Arrowsystem_local/exhibitions/` (`index.html`,
  `upcoming.html`, `exhibition-detail.html`, their CSS/JS, 34 images in `assets/exhibitions/web/`)
- the practice project's exhibitions build (`craft-cms-practice/storage/imports/arrow/09_exhibitions_industries.php`), as reference

---

## 1. What exists on the live site

### 1.1 Pages

| URL | What it is | In sitemap / indexed |
|---|---|---|
| `/exhibitions/` | Listing: title, "Upcoming Exhibitions" link card, then **28 show cards** (image + title), newest first | ✅ index |
| `/upcoming-exhibitions/` | Was the upcoming-shows page (R2R, LOUPE, PRINTING United, PACK EXPO 2026). **Now 301 → `/exhibitions/`**, but the page still exists in WordPress | ❌ (redirects) |
| **28 show pages** at the root, e.g. `/labelexpo-2025/`, `/drupa-2024/`, `/vitrum-2013-milan-italy/` | One WordPress **page** per show (no custom post type) | ✅ index, all 28 in `page-sitemap.xml` |

The 28 shows, newest first: Label Expo 2026, Warsaw Pack 2026, LabelExpo 2025, Global Pouch Forum 2025, PackExpo 2024,
LabelExpo 2024 (Chicago), Drupa 2024, Gulf Print Pack 2024, Labelexpo Brussels 2023, Graphics Canada 2023,
Label Expo 2023, Pack Expo 2022, Printing United 2022, Label Expo 2022, Automate 2022, Gulf Print Pack 2022,
Pack Expo Las Vegas 2021, Graphics Canada 2019, LabelEXPO 2018, Chem Show 2017, PackEXPO 2017, World of Concrete 2017,
LabelEXPO 2016, Glass Show Sept. 2015, GlassBuild America 2015, GlassBuild America 2014, Vitrum 2013, Glassbuild America 2013.

### 1.2 What a show page holds

| Item | Live |
|---|---|
| Title (H1) | e.g. "LabelExpo 2025" |
| Subtitle (H2) | Some shows: "Arrow Systems, Inc. at Labelexpo Europe (Barcelona)" |
| Intro text | A short paragraph on the newer shows (2025–2026); 2–3 lines or nothing on older ones |
| Photo gallery | Every show except LabelEXPO 2018 (no photos). **Three gallery formats**: Elementor gallery (2024–2026), a gallery shortcode plugin (2021–2024), the classic WordPress gallery (2013–2019). About 5–45 photos per show |
| Dates, location, booth | **Not stored as data.** Sometimes in the title ("Global Pouch Forum 2025, Florida") or the intro |
| Contact form, newsletter | Shared site-wide blocks (not per show) |

### 1.3 SEO (Rank Math)

- Each show: SEO title "{title} - Arrow Systems, Inc.", its own meta description, canonical = its URL, robots
  `follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large`, schema `Article` + `BreadcrumbList`.
- **Live SEO bugs found:**
  - Label Expo 2026's title is just "- Arrow Systems, Inc." (empty).
  - `/exhibitions/` has the meta description "Arrow System's exhibitions images will be displayed here".

---

## 2. What the new design specifies

| Design file | Becomes | Sections |
|---|---|---|
| `index.html` section **G** | Homepage "Our Innovation in Focus" | Eyebrow "Exhibitions", heading with a red word + line break, lead, "All exhibitions" link, **3 show cards** (photo, tag "Past exhibition" / upcoming, title, location, dates) |
| `exhibitions/index.html` | `/exhibitions/` | **1** header with a photo collage and stats ("Past exhibitions 28", "Next show Oct 18 · PACK EXPO International") · **2** "Upcoming Exhibitions" **ticket** for the next show (logo, dates, location, booth, live countdown) · **3** "Where We Have Exhibited" **timeline** grouped by year (2026, 2025 … "2013–2021"), with a year filter |
| `exhibitions/upcoming.html` | Upcoming shows page | Header with a **12-month calendar** of the year's shows · numbered **show schedule** (logo, dates, location, booth, Tech Spotlight, "Schedule a Meeting"); shows that have ended move to "Took place earlier" automatically (JS) · "Can't make it to a show?" CTA band |
| `exhibitions/exhibition-detail.html` | Each show page (`/{slug}/`) | Header (eyebrow "Past exhibition", title with red year, subtitle, intro, facts: Location / Year / "16 photos", photo collage) · **gallery** with a lightbox · "More Exhibitions" (all + previous/next show) · "Meet us at the next show" band (hides itself after the show ends) |

Design assets to copy (unchanged): `exhibitions/css/exhibitions.css`, `upcoming.css`, `detail.css`,
`exhibitions/js/exhibitions.js`, `upcoming.js`, `detail.js`, plus the 34 curated photos/logos in
`assets/exhibitions/web/` and 3 in `assets/events/web/` (homepage cards).

---

## 3. Content model in Craft (the best-practice part)

**One entry per show, for its whole life.** An upcoming show is created with its dates, location, booth and logo.
After the show, the same entry gets its photos and intro and turns into a past-show page. "Upcoming" or "past" is
worked out from the dates; nobody has to move or retag anything.

### 3.1 Sections

| Section | Type | URI | Template | Notes |
|---|---|---|---|---|
| **Exhibitions** (`exhibitions`) | **Channel** | `{% if object.hasPage %}{{ object.slug }}{% endif %}` → `/labelexpo-2025/` | `exhibitions/_entry` | Root-level URLs, exactly as live. A show **without** a page (e.g. an announced show with no photos yet) gets no URL at all |
| **Exhibitions Page** (`exhibitionsPage`) | **Single** | `exhibitions` | `exhibitions/_index` | Listing texts, header photos, SEO |
| **Upcoming Exhibitions Page** (`upcomingExhibitionsPage`) | **Single** | `upcoming-exhibitions` | `exhibitions/_upcoming` | Only if **D1** = build the page |

### 3.2 Entry type "Exhibition": fields

| Tab | Field | Handle | Type | Notes |
|---|---|---|---|---|
| Content | Title | `title` | — | e.g. "LabelExpo 2025"; `[brackets]` = red part (the design shows the year in red) |
| Content | Slug | `slug` | — | **Must equal the live slug** |
| Content | Start Date | `startDate` | Date | Required. Drives order, "upcoming/past", the year groups, countdowns |
| Content | End Date | `endDate` | Date | Optional (one-day shows) |
| Content | Show Dates As | `datePrecision` | Dropdown | "Exact dates" / "Year only". **Year only** for old shows whose dates aren't known (D3) |
| Content | Location | `city` ✱ | Plain Text | e.g. "Chicago, IL, USA" (reuses the existing `city` field, relabelled) |
| Content | Booth | `boothNumber` | Plain Text | Empty = "To be announced" |
| Content | Tech Spotlight | `techSpotlight` | Plain Text | Upcoming shows; empty = "To be announced" |
| Content | Show Logo | `showLogo` | Assets (1) | Upcoming ticket / schedule (the show's own logo) |
| Content | Card Photo | `featuredImage` ✱ | Assets (1) | Cards (homepage, listing, "More Exhibitions") and the detail collage |
| Page | Has a Page | `hasPage` | Lightswitch | On = the show has its own page at `/{slug}/` (all 28 live shows) |
| Page | Subtitle | `subheading` ✱ | Plain Text | e.g. "Arrow Systems, Inc. at Labelexpo Europe (Barcelona)" |
| Page | Intro | `sectionLead` ✱ | Plain Text | Lead paragraph under the title |
| Page | Gallery | `galleryImages` ✱ | Assets (many) | The show photos, in order; the first one is the collage's main photo |
| SEO | SEO Title, Meta Description, Social Image, Hide from Search Engines | `seoTitle` … ✱ | — | Same SEO fields as the blog |

✱ = existing field reused (with its own label/instructions on this layout).
Automatic (no fields): upcoming/past status, the year groups and filter, countdowns, "N photos", previous/next show,
the next-show band, schema.

### 3.3 The Exhibitions Page and Upcoming page (Singles)

Eyebrow, heading (`[red]`), intro, header photos (collage main + inset), CTA texts/links ("Schedule a Meeting",
"Contact Us", "Can't make it to a show?" band), SEO. Same reusable fields as the Blog Page.

### 3.4 Homepage section G: "Exhibitions" (new Homepage Sections block)

| Field | Notes |
|---|---|
| Small Heading, Heading (`[red]` words + line break), Intro | "Exhibitions" / "Our [Innovation] in Focus" / "Global exhibitions. Real connections. Lasting impact." |
| **Shows to Display** (Entries → Exhibitions, max 3, drag to order) | **Manual selection**, as asked. Empty = automatic (D6): the next upcoming show(s) first, then the latest past shows, 3 in total. 1–2 picked = the rest filled automatically (same behaviour as the blog section K) |
| All Exhibitions Link | → the Exhibitions Page |
| Anchor ID | `events` (as in the design) |

Placed between **D** and **H**, matching the design order (E and F aren't built yet).

---

## 4. URLs, redirects and menus

| URL | Craft |
|---|---|
| `/exhibitions/` | Exhibitions Page single |
| 28 × `/{slug}/` | Exhibition entries with **Has a Page** on (same slugs, trailing slash) |
| `/upcoming-exhibitions/` | **D1:** the design's upcoming page **or** keep the live 301 → `/exhibitions/` in `config/redirects.php` |
| Upcoming shows without a page | No URL; they appear on the listing / upcoming page and the homepage |

**Root-level URL clash check:** shows, blog posts (`/{slug}/`) and future industry pages all share the root. The
import reports any slug already used by another section (none expected: the live site keeps them unique).

Menus: the main menu and footer **Exhibitions** links (now pointing at arrsys.com) get linked to the Exhibitions Page
entry.

---

## 5. SEO

| Live | Craft |
|---|---|
| Title "{title} - Arrow Systems, Inc." | SEO Title empty = default; custom titles copied only where they differ |
| Meta descriptions | Copied per show; `/exhibitions/` gets the design's text (D5) |
| Canonical, robots | Automatic, same as live (the blog's `_partials/seo` include) |
| Schema `Article` | **D4:** `ExhibitionEvent` (name, startDate, endDate, location, image) + `BreadcrumbList` (Home › Exhibitions › show), which suits a show better than `Article` |
| Label Expo 2026's empty title | Fixed (D5) |

---

## 6. Front end

| File | Based on |
|---|---|
| `web/assets/css/exhibitions/*.css`, `web/assets/js/exhibitions/*.js` | The design's files, unchanged (fixes, if any, go in `craft.css`) |
| `templates/_blocks/home/exhibitions.twig` | Homepage section G (`index.html` G markup) |
| `templates/_components/exhibition-card.twig` | Card used by the homepage and the timeline (the design's two card variants) |
| `templates/exhibitions/_index.twig` | Header + stats, next-show ticket, year timeline + filter |
| `templates/exhibitions/_upcoming.twig` | Calendar + schedule (if D1) |
| `templates/exhibitions/_entry.twig` | Detail: header, gallery with lightbox, More Exhibitions, next-show band |

Everything date-based (upcoming vs past, countdown, the "Took place earlier" list) is worked out in Twig from today's
date, and the design's JS keeps it correct between page caches, as the design intends.

---

## 7. Content migration (automated, then checked)

One-off import script `storage/imports/exhibitions/`, same pattern as the blog:

1. **Read** the 28 show pages + `/exhibitions/` + the old upcoming page from the WordPress REST API, and each page's
   `<head>` SEO.
2. **Per show:** title, slug, subtitle, intro; **photos from all three gallery formats** (full-size originals,
   original order, alt text); card photo (D7); SEO title/description; **Has a Page** on.
3. **Dates and locations:** exact dates where the design or the live pages state them (2026 shows, LabelExpo 2025,
   Global Pouch Forum 2025, PackExpo 2024; locations from titles/intros such as "Chicago", "Barcelona", "Milan, Italy");
   **Year only** for the rest (D3).
4. **Upcoming 2026 shows** from the old upcoming page: R2R, LOUPE (Labelexpo Americas), PRINTING United, PACK EXPO
   International, with dates, location, booth, logo, and **Has a Page** off (D2: LOUPE vs Label Expo 2026).
5. **Report** per show: photo count (Craft / live), anything missing.

Images go to **Assets → Images → exhibitions/**, downloaded once; none load from the live site.

---

## 8. Phases

Back up the database before each phase; record each phase in `DEVELOPMENT_PHASES.md`.

| Phase | Work | Result |
|---|---|---|
| **0. Decisions** | D1–D7 | |
| **1. Structure** | Fields, Exhibition entry type, sections, singles, homepage block type | CRUD in the CP |
| **2. Templates** | Copy CSS/JS; listing, upcoming, detail, homepage G, card component; token-check against the design | Pages in the new design |
| **3. Pilot** | Label Expo 2026 entered from the design sample; review with you | Sign-off on fields and layout |
| **4. Import** | All 28 shows + 4 upcoming; report | Content migrated |
| **5. Connect** | Homepage G (manual selection + automatic fallback), menu/footer links, redirects | Site-wide |
| **6. Verify** | Section 10 checklist | Sign-off |

---

## 9. Decisions needed before building

| # | Question | Recommendation |
|---|---|---|
| **D1** | **Upcoming page**: build the design's `upcoming.html` at `/upcoming-exhibitions/` (the live site 301s that URL to `/exhibitions/` today), or keep the redirect and show upcoming shows only on `/exhibitions/` + homepage? | **Build it** at `/upcoming-exhibitions/`: it's in the design, the URL was live before, and the 301 is easy to drop |
| **D2** | **LOUPE (Labelexpo Americas) Sep 2026** on the old upcoming page is very likely the same show as the **Label Expo 2026** page. One entry or two? | **One entry** (Label Expo 2026, dates Sep 15–17, booth 1335) **if the client confirms**; two until then |
| **D3** | Old shows without known dates: **year only**, or research exact dates? | **Year only** now; exact dates can be added in the CP anytime |
| **D4** | Schema: keep the live `Article`, or use `ExhibitionEvent`? | **ExhibitionEvent** (dates + location in search results) |
| **D5** | Fix the two live SEO bugs (Label Expo 2026's empty title, the `/exhibitions/` description)? | **Fix** both (the design has proper texts) |
| **D6** | Homepage section G when no shows are picked: automatic (next upcoming first, then latest past), or always manual? | **Automatic fallback + manual override** (like the blog section K) |
| **D7** | Card photos: the design's curated photos (34 in `assets/exhibitions/web/`), or the live listing images? | **Design photos** where they exist, live listing image otherwise |

---

## 10. Acceptance checklist

- [ ] **URLs:** `/exhibitions/` and all 28 show URLs return **200** at the exact live path (trailing slash);
      `/upcoming-exhibitions/` follows D1.
- [ ] **SEO parity:** title, description, canonical, robots match live for every show (except the D5 fixes).
- [ ] **Content:** every show has its title, subtitle, intro and **all its live photos** (count per show = live).
- [ ] **Design:** homepage G, listing, upcoming and detail pages match the design files (token comparison);
      countdown, year filter, calendar and lightbox work; no JavaScript errors; mobile layout correct.
- [ ] **CRUD:** create an upcoming show (no page) → it appears in the ticket/schedule/homepage; give it photos and
      switch on "Has a Page" → it gets its URL and moves to "past" after its end date; edit, disable, delete.
- [ ] **Homepage selection:** pick 1/2/3 shows → shown in that order, the rest filled automatically; none picked =
      automatic.
- [ ] **No images from arrsys.com**, alt texts kept.

---

## 11. Risks

| Risk | Mitigation |
|---|---|
| Three different gallery formats on live | One parser per format, tested on a show of each; photo counts compared per show |
| Dates/locations aren't data on live | Exact where known, "Year only" otherwise (D3); editable in the CP |
| Old (2013–2015) photos are small (~300 px) | Kept as they are; flagged in the report for the client |
| A show slug clashes with a blog post or another root URL | The import checks and reports clashes |
| Upcoming/past switches with the date while pages are cached | Status from the date in Twig + the design's JS on the client side |
