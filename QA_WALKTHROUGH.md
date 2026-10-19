# QA walkthrough: the admin panel, section by section

A guided tour of every admin screen, what each field does, where it shows on the website, and what to test.
Set the site up first with **`QA_SETUP.md`**. Admin: **http://localhost:8081/admin** · Website: **http://localhost:8081/**

**Modules covered so far:** 1. Blog · 2. Exhibitions · 3. Industries. (Products, Pages, Forms, Globals and Navigation
will follow in the same format.)

### How to use this document

- Each section explains a screen, then lists **test cases**: *Do* → *Expected*. Mark each one Pass / Fail.
- **Use test content.** Create new posts named **`QA TEST …`** rather than editing real posts. If you must edit a real
  post, don't change its **slug** (its web address must stay as on the live site) and undo your change afterwards.
- **Delete your test content** at the end (section 1.12).
- After saving in the admin, reload the website page to see the change. If something looks stale, run
  `php craft clear-caches/all` and reload.
- A bug report needs: URL, browser + screen size, steps, expected, actual, screenshot.

---

# 1. Blog

## 1.0 Overview: where the blog lives in the admin

| Admin screen | What it controls | Website |
|---|---|---|
| **Entries → Blog** | The articles (346 imported from the live site) | Each post at `/{slug}/`, e.g. `/roll-to-roll-vs-sheet-fed-label-printing-throughput-cost/` |
| **Entries → Blog Page** | The blog home: heading, sidebar box, the default closing box of articles, SEO | `/blog/` |
| **Entries → Blog Categories** | 45 categories, nested up to 3 levels | `/category/{slug}/`, `/category/{parent}/{child}/` |
| **Entries → Blog Tags** | 248 tags | `/tag/{slug}/` |
| **Entries → Homepage → "Latest Blog Posts" block** | The 3 article cards on the homepage | `/` (section "More Insights …") |

Website pages of the blog: `/blog/` (listing, 10 per page, `/blog/page/2/` …), `/blog/?q=word` (search), category and
tag pages (same layout as `/blog/`), and the post pages.

## 1.1 The posts list (Entries → Blog)

What you see: a table of all posts with **Title, Post Date, Status** and more, a **search box**, a **status filter**
(All / Live / Pending / Disabled …), sorting by column, **pagination**, and the **New entry** button (top right).
Ticking rows shows **bulk actions** (Enable, Disable, Delete …).

| # | Do | Expected |
|---|---|---|
| 1.1.1 | Open Entries → Blog | The list loads; the total is about 346 posts |
| 1.1.2 | Type `label` in the search box | Only matching posts are listed |
| 1.1.3 | Sort by Post Date (click the column) both ways | Order changes newest ↔ oldest |
| 1.1.4 | Click a post title | The post opens in the editor |
| 1.1.5 | Change the table's columns (the ⚙ / "Customize" option under the table) | Columns can be added / removed; it doesn't change the website |

## 1.2 Creating and editing a post: the layout of the editor

Click **New entry** (or open a post). The editor has:

- **Tabs** across the top: **Content**, **Settings**, **SEO**.
- **The right-hand panel**: the **Outline** (a map of the post's blocks, see 1.4), then the post's details: **Slug**,
  **Author**, **Post Date**, **Expiry Date**, **Enabled** switch, and read-only info (ID, status, created / updated).
- The **Save** button (top right; its ▾ menu has "Save and add another", etc.), **Preview**, **View** and
  **Create a draft**.

### Content tab

| Field | What it does | Shows on the website |
|---|---|---|
| **Title** (required) | The post's headline. Words in **[square brackets]** show in **red** on the page | The big H1 of the post; card titles; the browser tab (without the brackets) |
| **Lead** | 1–2 sentences under the title | Under the H1, and the excerpt on blog cards |
| **Cover Image** | The main picture. Shown whole at its own shape, full width under the title | Post header; the image on blog cards; the share image if no Social Image is set |
| **Article Body** | The article itself, built from blocks (section 1.3) | The article column |

### Settings tab

| Field | What it does | Shows on the website |
|---|---|---|
| **Categories** | One or more categories. **The first one is the main category** (drag to reorder) | Category chips in the post header (all), the breadcrumb (main), "Filed under" at the end (all), the post on each category's page |
| **Tags** | Any number of tags | "Tagged" at the end of the post; the post on each tag's page |
| **More Insights** | Up to 3 hand-picked related articles | The "More Insights" cards under the post. Empty = the newest posts of the main category, then the newest posts overall, to make 3 |

### SEO tab

| Field | What it does | Check in the page source (right-click → View page source) |
|---|---|---|
| **SEO Title** | The title in Google and the browser tab | `<title>`. Empty = the post title + " - Arrow Systems, Inc." (staging shows "Arrow Systems (Local Staging)") |
| **Meta Description** | The text under the title in Google (about 150–160 characters) | `<meta name="description">`; also the og/twitter description |
| **Social Image** | The picture when the link is shared (LinkedIn, Facebook …) | `<meta property="og:image">`. Empty = the Cover Image |
| **Hide from Search Engines** | Tells Google not to list the page | `<meta name="robots" content="follow, noindex">` when on; `follow, index, …` when off |

### Right-hand panel

| Item | What it does |
|---|---|
| **Slug** | The web address: `/{slug}/`. Must be unique; for imported posts it must stay as on the live site |
| **Author** | Shown as "By {name}" in the post header |
| **Post Date** | The publish date shown on the post and used for ordering. A **future** date = scheduled (status "Pending"; not on the website until then) |
| **Expiry Date** | Optional: the post disappears from the website after this date |
| **Enabled** | Off = the post is hidden from the website (status "Disabled") |

### Test cases: creating a post

| # | Do | Expected |
|---|---|---|
| 1.2.1 | New entry → Title `QA TEST [Blog] Post` → Save without anything else | Saves; status Live; opens at `/qa-test-blog-post/`; "Blog" in the H1 is red; the browser tab shows "QA TEST Blog Post - Arrow Systems (Local Staging)" |
| 1.2.2 | Add a Lead and a Cover Image (upload any image) → Save | Lead under the H1; image full width under the title, not cropped (try a tall and a very wide image) |
| 1.2.3 | Settings → add 2 categories, then drag the second to the top → Save | Header shows both chips; breadcrumb shows the one now first; "Filed under" lists both |
| 1.2.4 | Settings → add 2 tags → Save | "Tagged" with both at the end of the post; each tag page (`/tag/…/`) lists the post |
| 1.2.5 | Settings → More Insights: pick 1 post → Save | "More Insights" shows that post first, then 2 automatic ones (3 cards) |
| 1.2.6 | SEO → fill SEO Title and Meta Description → Save | Page source: `<title>` = your SEO Title; description = yours |
| 1.2.7 | SEO → Social Image: pick a different image → Save | `og:image` = the new image; the cover on the page is unchanged |
| 1.2.8 | SEO → Hide from Search Engines on → Save | Page source: robots = `follow, noindex`; switch it off again afterwards |
| 1.2.9 | Change the Slug to `qa-test-renamed` → Save | The post opens at `/qa-test-renamed/` |
| 1.2.10 | Give a second test post the same slug | Craft makes it unique (adds `-1`) or shows an error; no two posts share an address |
| 1.2.11 | Post Date → tomorrow → Save | Status "Pending"; the post is not on `/blog/` and its URL returns 404 until then |
| 1.2.12 | Enabled → off → Save | Not on `/blog/`; its URL returns 404; on again → back |
| 1.2.13 | Save with an empty Title | Error "Title cannot be blank"; nothing saved |

## 1.3 Article Body: the page builder

The article is a list of **blocks**. Each block is one kind of content (a heading, a paragraph, an image …).

**Working with blocks**

| Action | How |
|---|---|
| **Add a block** | "Add block" under the blocks (or the ⋯ menu of a block → add above / below). The menu shows the blocks as **icons in groups** (Text, Lists, Media, Products & CTA, Q&A, Other) with a **search box**: type to filter |
| **Collapse / expand** | Click a block's title bar. **Saved blocks open collapsed**, showing a one-line preview |
| **Reorder** | Drag a block by its handle (the ⁘ icon on the right of its title bar) |
| **Copy / paste / duplicate / delete** | The block's **⋯** menu |
| **Disable a block** | The block's ⋯ menu → Disable: it stays in the editor but is hidden on the website |

**Blocks and what to check** (the website shows each block in the article column, in the order given)

| Block (group) | Fields | What to check on the website |
|---|---|---|
| **Heading** (Text) | Heading*, Level (H2 / H3 / H4), Answer Lead, Show in "On this page", Contents Label, Anchor ID | H2 = a section heading with a small red bar above it and listed in **On this page** (unless "Show in On this page" is off); H3/H4 smaller and not listed; Answer Lead = one bold sentence under it; Contents Label = shorter text in the list; Anchor ID = the `#link` |
| **Rich Text** (Text) | Text (editor: paragraphs, bold, italic, lists, links, tables) | Formatting as typed; links clickable; bullet lists with red markers; tables scroll sideways on phones |
| **Intro** (Text) | Text* | A larger opening paragraph |
| **Callout** (Text) | Text*, Style (Callout / Note) | Callout = red bar on the left; Note = grey small print |
| **Quote** (Text) | Quote*, Source | A styled quotation with the source under it |
| **Key Takeaways** (Lists) | Heading*, Items* (one per row; `*italic*`, `**bold**`), Anchor ID | A boxed list; its heading appears in **On this page** |
| **Checklist** (Lists) | Items* | A list with red ticks |
| **Steps** (Lists) | Steps*: each Step has Title*, Text | Numbered steps 1, 2, 3 … |
| **Spec List** (Lists) | Terms*: term + explanation per row | Term / explanation pairs |
| **Comparison** (Lists) | Columns*: each has Heading*, Items*, Column Style (Pros ✓ white card / Cons ✕ grey card) | Two cards side by side (stacked on phones), ticks or crosses as chosen |
| **Image** (Media) | Image*, Caption, Width (Full / ¾ / ½ / ⅓) | The image at the chosen width, caption under it; alt text = the image's "Alternative Text" (set on the image in Assets) |
| **Gallery** (Media) | Images*, Columns (2 / 3 / 4) | A grid with that many columns (fewer on phones) |
| **Video** (Media) | Video Link* (YouTube), Caption | A YouTube player (16:9); a wrong link shows nothing |
| **Data Table** (Media) | Caption, Table* (tick "Header row" in the table menu) | A table with its caption above; header row bold |
| **Product List** (Products & CTA) | Products*: each has Tag Line, Name*, Link, Description | Product cards; the name links to the product |
| **Button** (Products & CTA) | Button* (link + label), Button Style (Red / Outline / Text link with arrow) | A button with the label, opening the link |
| **Call to Action** (Products & CTA) | Heading*, Text, Button | The red closing box. **Replaces the default closing box** of the post (see 1.6) |
| **Form** (Products & CTA) | Form* (any Formie form), Heading, Intro, Anchor ID | The form in a white card; it submits and shows the form's thank-you message. An **"Edit form fields ↗"** link under the chosen form opens it in Formie |
| **FAQ** (Q&A) | Questions*: each has Question*, Answer* | Questions that open / close on click |
| **Divider** (Other) | none | A horizontal line |
| **HTML** (Other) | HTML Code* | The code output as-is (e.g. an embed). Admins only |
| **Stats Strip** (Other) | none | The company figures (40+, 2,500+ …) from Globals → Stats Strip |

(\* = required)

**Test cases: page builder**

| # | Do | Expected |
|---|---|---|
| 1.3.1 | In a QA TEST post, add **one of every block**, fill the required fields, Save | Saves; every block shows on the website as in the table above; nothing overlaps; no errors in the browser console (F12) |
| 1.3.2 | Save a block with a required field empty | The block is marked with an error; nothing saved |
| 1.3.3 | Open "Add block", type `vid` in the menu's search | Only "Video" is left |
| 1.3.4 | Drag the last block to the top → Save | Same new order on the website |
| 1.3.5 | ⋯ → Duplicate a block → Save | The block appears twice |
| 1.3.6 | ⋯ → Disable a block → Save | Hidden on the website, still in the editor (greyed) |
| 1.3.7 | Add 3 H2 Headings, an H3 and a Key Takeaways | **On this page** lists the 3 H2s + Key Takeaways (not the H3); clicking an item scrolls to it; on phones the list folds |
| 1.3.8 | Add text to many blocks, compare the "min read" in the post header | Reading time ≈ words ÷ 225, rounded up (at least 1 min) |
| 1.3.9 | Phone width (DevTools, 390 px): open the test post | Nothing wider than the screen; tables scroll inside themselves; images fit |

## 1.4 The Outline panel (right-hand side of the editor)

A map of the post's blocks, for long articles.

| Part | What it does |
|---|---|
| **Block count** | "29 blocks" at the top |
| **Find a block…** | Filters the list by block type or text |
| **Collapse all / Expand all** | Collapses or expands every block in the editor |
| **The list** | One row per block: icon, type, a preview of its text. H2 headings are highlighted and the blocks under them are indented, like a table of contents |
| **Click a row** | Opens that block, scrolls to it and highlights it briefly |

| # | Do | Expected |
|---|---|---|
| 1.4.1 | Open a long imported post (e.g. "5 Technological Advancements Driving Digital Printing in Packaging") | The Outline lists every block; the list scrolls inside its box; only the list and the page have scrollbars |
| 1.4.2 | Type `faq` in Find a block… | Only FAQ rows remain |
| 1.4.3 | Click a row near the bottom | The editor jumps to that block and opens it |
| 1.4.4 | Add / delete / reorder a block, or type in one | The Outline updates within a second |
| 1.4.5 | Collapse all, then Expand all | All blocks collapse, then all open |

## 1.5 Drafts, revisions, preview

Craft keeps the history of every post.

| # | Do | Expected |
|---|---|---|
| 1.5.1 | Open a QA TEST post → **Create a draft** → change the Title → **Save draft** | The website still shows the old title; the editor shows a "Draft" notice |
| 1.5.2 | In the draft, click **Preview** | A preview of the changed post (side by side or in a new tab), without publishing |
| 1.5.3 | **Apply draft** | The website shows the new title |
| 1.5.4 | Open the revision menu (the post's name / "Current" menu at the top) → pick an older revision → **Revert content from this revision** | The post goes back to that version |
| 1.5.5 | Entries → Blog: status filter "Drafts" | Your unsaved drafts are listed |

## 1.6 Blog Page (Entries → Blog Page)

One entry that controls `/blog/` and the shared parts of all articles.

| Tab | Field | Shows on the website |
|---|---|---|
| Content | **Title** | The breadcrumb ("Blog") and default page title |
| Content | **Small Heading** | The red label above the blog heading, e.g. "Guides & Articles" |
| Content | **Heading** | The big heading on `/blog/`; [brackets] = red |
| Content | **Intro** | The paragraph under it |
| Sidebar | **Help Box Title / Text / First Button / Second Button** | The red box in the `/blog/` sidebar **and** in every article's right rail |
| Default CTA | **CTA Heading / CTA Text / CTA Button** | The red closing box at the end of **every article without its own Call to Action block**. Empty heading = no box |
| SEO | SEO Title, Meta Description, Social Image, Hide from Search Engines | `/blog/` page source |

| # | Do | Expected |
|---|---|---|
| 1.6.1 | Change Heading to `QA [Test] Blog` → Save | `/blog/` heading changes; "Test" red. Undo afterwards |
| 1.6.2 | Change Help Box Title → Save | Changes on `/blog/` **and** in an article's right rail |
| 1.6.3 | Change CTA Heading → Save | The closing box of a normal article (e.g. "Diagnosing Supplement Label Peeling: HDPE Bottle Failures") changes. Undo afterwards |
| 1.6.4 | Add a **Call to Action** block to a QA TEST post | That post shows its own box, not the default; other posts keep the default |
| 1.6.5 | CTA Button → the link now points to the Contact Us page entry | The button opens `/contact-us/` |

## 1.7 Blog Categories (Entries → Blog Categories)

A tree: categories can sit under other categories (up to 3 levels). **Drag** to reorder or nest.

| Field | What it does |
|---|---|
| **Title** | The category name (chips, filter, breadcrumb, its page heading) |
| **Slug** (right panel) | Its address: `/category/{slug}/`, or `/category/{parent}/{slug}/` when nested |
| **Description** | Optional intro under the name on the category page |
| SEO tab | As for posts |

Rules: a category page lists its posts **and its sub-categories' posts** (as live). The **Category** filter on `/blog/`
lists only categories that have posts (not "Uncategorized"), with counts; "Popular" = the 6 biggest.

| # | Do | Expected |
|---|---|---|
| 1.7.1 | Open `/category/digital-printing/` | Its posts, 10 per page; breadcrumb Home › Blog › Digital Printing |
| 1.7.2 | New category `QA Test Category`, assign it to a QA TEST post | It appears in the `/blog/` Category filter with count 1; its page lists the post |
| 1.7.3 | Drag `QA Test Category` under another category → Save | Its address becomes `/category/{parent}/qa-test-category/`; the parent's page now also lists the QA post |
| 1.7.4 | Category with no posts | Not in the filter; its page shows no posts |

## 1.8 Blog Tags (Entries → Blog Tags)

A flat list. Each tag has a **Title** and a **Slug**; its page is `/tag/{slug}/` (same layout as `/blog/`).

| # | Do | Expected |
|---|---|---|
| 1.8.1 | New tag `QA Test Tag` → add it to a QA TEST post | `/tag/qa-test-tag/` lists the post; the post shows "Tagged QA Test Tag" |
| 1.8.2 | Open any existing tag page from a post's "Tagged" links | Lists the posts with that tag |

## 1.9 Homepage: "Latest Blog Posts" block (Entries → Homepage → Homepage Sections)

| Field | What it does |
|---|---|
| Small Heading, Heading | The section's label and heading ([brackets] = red) |
| **Posts to Show** | Up to 3 picked posts, in that order. Empty = the 3 newest; fewer than 3 picked = newest fill the rest |
| Button | The "View all" link (e.g. to `/blog/`) |
| Anchor ID | For links to this section |

| # | Do | Expected |
|---|---|---|
| 1.9.1 | Leave Posts to Show empty | The homepage shows the 3 newest posts |
| 1.9.2 | Pick 1 post → Save | That post first, then the 2 newest. Undo afterwards |

## 1.10 Website checks (no admin needed)

| # | Page | Check |
|---|---|---|
| 1.10.1 | `/blog/` | Heading, "N articles · Latest {date}", search box, Category filter, 10 post cards, pagination, sidebar (Product Categories, Recent Blog Posts, help box) |
| 1.10.2 | `/blog/page/2/` and the last page | Next 10 posts; a page past the last (e.g. `/blog/page/999/`) returns **404** |
| 1.10.3 | `/blog/?q=label` | Results for "label", best matches first; a word with no results shows a "no results" message |
| 1.10.4 | A post | Breadcrumb, category chips, "By author", date, "N min read", H1, lead, cover; On this page + help box (right rail on desktop); article; closing box; Filed under / Tagged; More Insights (3 cards); newsletter band; footer |
| 1.10.5 | A post's page source | `<title>`, description, canonical = its own URL, robots, `og:type` = article, `og:image`, `article:published_time`, `article:tag` (one per tag), and JSON-LD (BlogPosting + BreadcrumbList) |
| 1.10.6 | A post with a YouTube video | Page source has `og:video` |
| 1.10.7 | Phone (390 px) and tablet (768 px) | No sideways scrolling; On this page folds; cards stack |
| 1.10.8 | Keyboard only (Tab / Enter) | Every link, the filter, search and FAQ work without a mouse; focus is visible |

## 1.11 Known limitations (please don't report these as bugs)

- **Emails** are not sent from QA copies; **reCAPTCHA** is off (see `QA_SETUP.md`).
- **Sidebar "Product Categories"** on the blog pages is fixed in the template (4 links to the live site), not editable in
  the admin; the product category pages aren't built yet.
- **The Form block's "Section Settings" tab** (Space Above / Below, Background) has no effect inside a blog post: those
  settings apply when the Form is a full-width section on a Page.
- Live-site differences that are intended: page titles end with "(Local Staging)"; 3 posts' canonical points to
  themselves instead of an old redirected address; reading time can differ by 1 minute from the live site.
- 12 posts that are **drafts on the live site** were not imported (no access to WordPress drafts).

## 1.12 Clean-up after testing

Delete what you created: Entries → Blog → tick your **QA TEST** posts → Delete; the same for `QA Test Category` and
`QA Test Tag`; undo any change to Blog Page or the Homepage block. Deleted entries go to the trash and can be restored
for a while (Entries → status filter "Trashed").

---

# 2. Exhibitions

## 2.0 Overview

| Admin screen | What it controls | Website |
|---|---|---|
| **Entries → Exhibitions** | Every trade show Arrow attends or attended (32): dates, location, booth, logo, photos | Each show **with a page** at `/{slug}/` (28 of them), e.g. `/labelexpo-2025/`; shows without a page appear only in lists |
| **Entries → Exhibitions Page** | The `/exhibitions/` page: texts, header photos, contact button | `/exhibitions/` |
| **Entries → Upcoming Exhibitions Page** | The `/upcoming-exhibitions/` schedule page: texts, buttons, bottom band | `/upcoming-exhibitions/` |
| **Entries → Homepage → "Exhibitions" block** | The 3 show cards on the homepage | `/` |

How a show is classed (automatic, from its dates):
- **Upcoming / next show** = the first show with **exact dates** that **hasn't ended**. It gets the "ticket" with a
  countdown on `/exhibitions/` and the "Meet us at the next show" band on show pages.
- **Past** = its end date (or start date for a one-day show) is before today.
- On **19 Oct 2026** the next show is PACK EXPO International (18–21 Oct). After that no future show is entered, so the
  upcoming parts **hide themselves**. That's correct behaviour: test it with your own future show (2.2).

## 2.1 The shows list (Entries → Exhibitions)

A **structure** (an ordered list you can drag). **The order here is the order of the timeline** on `/exhibitions/` and of
"More Exhibitions" (newer / older) on show pages.

| # | Do | Expected |
|---|---|---|
| 2.1.1 | Open Entries → Exhibitions | 32 shows, newest first |
| 2.1.2 | Drag a past show one place up → open `/exhibitions/` | Its card moves accordingly in its year group. Drag it back afterwards |

## 2.2 A show, tab by tab

### Content tab

| Field | What it does | Shows on the website |
|---|---|---|
| **Show Name** (required) | The show's name | Cards, ticket, schedule row, page H1 (its year in red) |
| **Start Date** (required) | First day. Decides upcoming / past, the countdown and the year group | Dates everywhere; year group on `/exhibitions/` |
| **End Date** | Last day; empty = a one-day show | "Oct 18–21, 2026" style dates |
| **Show Dates As** | Exact dates, or **Year only** (for old shows whose dates aren't known) | Year only = just "2025"; such a show is never "upcoming" |
| **Location** | City, country | Cards, ticket, page facts |
| **Booth** | Booth number | Ticket / schedule; empty = "To be announced" |
| **Tech Spotlight** | What Arrow shows there | Ticket / schedule; empty = "To be announced" |
| **Show Logo** | The show's own logo | The ticket and the schedule; the card image of an **upcoming** show that has no Card Photo |
| **Card Photo** | The card image (4:3) | Homepage cards, the `/exhibitions/` timeline, "More Exhibitions" |

### Page tab

| Field | What it does | Shows on the website |
|---|---|---|
| **Has a Page** | On = the show gets its page at `/{slug}/`. Off = listed only (cards link to its row on `/upcoming-exhibitions/`) | The show page exists or not (off → its URL is 404) |
| **Subtitle** | e.g. "Arrow Systems, Inc. at Labelexpo Europe (Barcelona)" | Under the page H1 |
| **Intro** | A paragraph | Under the subtitle |
| **Photos** | The gallery; **the first two are also the large photos at the top**; drag to reorder | Header collage + gallery grid with a lightbox |

### SEO tab
SEO Title, Meta Description, Social Image (empty = the Card Photo), Hide from Search Engines, and **Canonical URL**
(leave empty; only for a page that should point Google to another page).

### Test cases: shows

| # | Do | Expected |
|---|---|---|
| 2.2.1 | New entry: `QA TEST Expo`, Start Date = 2 months from today, End Date = 3 days later, Show Dates As = Exact, Location `Test City`, Has a Page **off** → Save | On `/upcoming-exhibitions/` in the schedule; on `/exhibitions/` it is the **ticket** with a **countdown** (if no other show comes before it); Booth and Tech Spotlight say "To be announced"; no page of its own |
| 2.2.2 | Fill Booth `A12` and Tech Spotlight → Save | Ticket and schedule show them instead of "To be announced" |
| 2.2.3 | Add a Show Logo (no Card Photo) → Save | The ticket shows the logo; its card on the homepage (2.5) shows the logo whole on white |
| 2.2.4 | Has a Page **on**, Subtitle, Intro, 4 Photos → Save | `/qa-test-expo/` exists: eyebrow "upcoming", H1 with the year red, subtitle, intro, facts, the first 2 photos as the collage, a gallery of all 4 |
| 2.2.5 | On the show page click a gallery photo | The lightbox opens; ← → keys and the arrows move between photos; the counter updates; **Esc** closes and focus returns to the photo |
| 2.2.6 | Show page bottom | "More Exhibitions" (cards, newer / older) and the "Meet us at the next show" band (the next announced show) |
| 2.2.7 | Change the dates to last month → Save | It is no longer the ticket; on `/upcoming-exhibitions/` it moves to "Took place earlier"; the page eyebrow says past |
| 2.2.8 | Show Dates As = **Year only** → Save | Only the year is shown; never treated as upcoming |
| 2.2.9 | Save without a Start Date | Error; nothing saved |
| 2.2.10 | Has a Page on, slug = an existing blog post's slug | Craft makes the address unique (adds `-1`) or shows an error; the blog post keeps its address |

## 2.3 Exhibitions Page (Entries → Exhibitions Page) → `/exhibitions/`

| Field | Shows on the website |
|---|---|
| **Title**, **Small Heading**, **Heading** ([brackets] = red), **Intro** | The page header |
| **Header Photos** | First = the large photo, second = the small one |
| **Large Photo Caption** | The label on the large photo |
| **Contact Link** | The "Contact Us" button next to the next-show ticket |
| SEO tab | `/exhibitions/` page source |

Page structure: header (texts, stats: number of past shows + the next show, the two photos) → **Upcoming Exhibitions**
(the ticket; hidden when nothing is announced) → **Where We Have Exhibited**: shows **with a page**, grouped by year
(the 5 latest years, then one "Earlier" group), with a **year filter**.

| # | Do | Expected |
|---|---|---|
| 2.3.1 | Open `/exhibitions/` | Header with stats; ticket (when a future show exists); year groups; clicking a year in the filter shows only that year |
| 2.3.2 | Change Heading and Large Photo Caption → Save | Both change. Undo afterwards |
| 2.3.3 | A show with Has a Page off | Not in the "Where We Have Exhibited" timeline |
| 2.3.4 | Phone width | Ticket, filter and cards stack; no sideways scroll |

## 2.4 Upcoming Exhibitions Page (Entries → Upcoming Exhibitions Page) → `/upcoming-exhibitions/`

| Tab | Field | Shows on the website |
|---|---|---|
| Content | Title, Small Heading, Heading, Intro | The page header |
| Content | **Schedule a Meeting Link** | The "Schedule a Meeting" buttons on the schedule |
| Bottom Band | Band Heading, Band Text, First Button, Second Button | The band at the bottom ("Can't make it to a show?") |
| SEO | as usual | page source |

The schedule lists the **year's shows with exact dates** (this year; or next year's when this year has none) by start
date. Each is marked **past / on now / next** from today's date, and the ones that ended move to **"Took place earlier"**.

| # | Do | Expected |
|---|---|---|
| 2.4.1 | Open `/upcoming-exhibitions/` | This year's shows in date order; ended ones under "Took place earlier"; the next one highlighted with a countdown |
| 2.4.2 | Change Band Heading → Save | The bottom band changes. Undo afterwards |

## 2.5 Homepage: "Exhibitions" block (Entries → Homepage → Homepage Sections)

| Field | What it does |
|---|---|
| Small Heading, **Heading** ([brackets] = red, ` \| ` = line break), Intro | The section texts |
| **Shows to Display** | Up to 3 picked shows, in that order. Empty / fewer than 3 = filled with the next announced shows, then the latest past shows that have a page |
| All Exhibitions Link | The "all exhibitions" button |
| Anchor ID | For links to this section |

| # | Do | Expected |
|---|---|---|
| 2.5.1 | Shows to Display empty | 3 cards: your QA TEST Expo (if future) first, then the latest past shows |
| 2.5.2 | Pick 1 show → Save | That one first, the rest filled automatically. Undo afterwards |

## 2.6 Website checks

| # | Page | Check |
|---|---|---|
| 2.6.1 | `/labelexpo-2025/` (a past show page) | Header collage, gallery + lightbox, More Exhibitions, next-show band; page source has `og:image`, a title, and JSON-LD with an **ExhibitionEvent** |
| 2.6.2 | Every card on `/exhibitions/` | Opens the show's page |
| 2.6.3 | Keyboard: the year filter, the gallery and the lightbox | All usable with Tab / Enter / arrows / Esc |

## 2.7 Known limitations
- No future show is entered after PACK EXPO (21 Oct 2026), so the upcoming parts hide from 22 Oct: expected.
- 4 shows have no page (announced / schedule-only), as decided; their cards point to the schedule.
- `/drupa-2024/` and `/printing-united-2022/` are pages here but redirect on the live site (an open decision).

## 2.8 Clean-up
Delete `QA TEST Expo` (Entries → Exhibitions); undo changes to the two pages and the homepage block.

---

# 3. Industries

## 3.0 Overview

| Admin screen | What it controls | Website |
|---|---|---|
| **Entries → Industries** | The industry pages (12 + a test page), each built with a **page builder** | `/{slug}/`, e.g. `/flexible-packaging-and-custom-mylar-bags/` |
| **Entries → Industries Page** | The `/industries/` listing: texts and the help band | `/industries/` |
| **Entries → Homepage → "Industries Carousel" block** | The industry tiles on the homepage | `/` |
| Products (cross-link) | A product page's **"What You Can Print"** lists the industries whose **Recommended Products** pick that product | `/product/…/` |

Reference page with every block: **`/builder-test-industry/`** (hidden from search engines).

## 3.1 The industries list (Entries → Industries)

A structure: **the order here is the order of the cards on `/industries/`** (numbered 01, 02 …) and of "Other Industries".

| # | Do | Expected |
|---|---|---|
| 3.1.1 | Drag an industry one place up → `/industries/` | Its card and number move. Drag it back |

## 3.2 An industry, tab by tab

### Card tab

| Field | What it does | Shows on the website |
|---|---|---|
| **Industry Name** (required) | The name | Card titles, breadcrumb, browser tab |
| **Slug** (right panel) | Address `/{slug}/`; keep the live slug for imported industries | |
| **Card Photo** | 4:3 photo | `/industries/` cards, Other Industries, homepage tiles, product pages' "What You Can Print"; also the header image when the Page Header has none |
| **Card Summary** | 1–2 sentences | Under the card title (same places) |
| **Icon** | A small line icon | On the card and the listing header mosaic |
| **Show on Listing** | Off = the page works but has no card on `/industries/` (e.g. Corrugation Printing, Wallpaper Printer, as live) | `/industries/` grid |

### Page Builder tab
The whole page, top to bottom, built from blocks (3.3). Same editor as the blog (collapsed blocks, Add block menu with
icons and search, drag, ⋯ menu, **Outline** panel on the right: see 1.3 and 1.4).

### SEO tab
SEO Title, Meta Description, Social Image (empty = the Page Header image, else the Card Photo), Hide from Search
Engines, Canonical URL.

## 3.3 Industry page builder blocks

**Industry blocks** (group "Industry"; each is a full-width section of the page):

| Block | Fields | What to check on the website |
|---|---|---|
| **Page Header** | Small Heading, **Heading (H1)\*** ([red] words), Intro (an empty line = new paragraph), Applications (tags), Image, Button 1, Button 2 | Breadcrumb Home › Industries › name; H1 with red words; tags under the intro; image on the right (empty = Card Photo); buttons (e.g. `#contact` scrolls to the Contact block). **Put it first.** Without it the page gets a plain header (breadcrumb + name) |
| **Recommended Products** | Small Heading, Heading, Intro, **Show** (Picked products / Products from the categories), Products, Categories, How Many, View All Link, Anchor ID | Product cards, 3 per row. *Picked* = exactly those, in your order. *Categories* = products of the chosen categories, up to How Many (new products appear automatically). One card = centred |
| **Content Block** | Small Heading, Heading, Text, Image, Video Link, Link, Anchor ID | Image beside text. **Several in a row alternate the image side** (left, right, left …). A Video Link replaces the image with a click-to-play video. No image and no video = text only |
| **Columns** | Column Widths, Left / Right Column Style (Plain, White card, Maroon panel), Left Column, Right Column (any common block in each); **Section Settings** tab | Two columns side by side (stacked on phones). Only one column filled = one full-width column |
| **Card Grid** | Heading, Intro, Cards per Row (2/3/4), Cards (Title\*, Subtitle, Image, Text, Link); **Section Settings** tab | A grid of cards; a card with a Link is clickable as a whole |
| **Contact Form** | Small Heading, Heading, Text, Anchor ID | The maroon panel (phones, email, address from Globals → Site Settings) + a form. **See 3.7: this form is not connected** |
| **Other Industries** | Small Heading, Heading, Other Industries (up to 3), View All Link | 3 industry cards: the picked ones, else the next 3 in the listing order (wrapping round), **never the page itself** |
| **CTA Band** | Heading\*, Intro, Button, Anchor ID (Settings tab) | A maroon band with a button |
| **Photo Gallery** | Small Heading, Heading, Photos\*, Anchor ID | A photo grid; click → lightbox (arrows, Esc) |

**Section Settings** (tab on Columns and Card Grid): **Space Above / Below** (None, Small, Normal, Large),
**Background** (White, Light grey), and for Columns **Vertical Alignment** (Top, Centre, Equal height).

**Common blocks** (groups Text, Lists, Media, Products & CTA, Q&A, Other: the **same 22 blocks as the blog**, table in
1.3): placed between section blocks, consecutive ones share one text column.

### Test cases: industry pages

| # | Do | Expected |
|---|---|---|
| 3.3.1 | New industry `QA TEST Industry`: Card Photo, Card Summary, Icon → Save (no blocks) | `/qa-test-industry/` shows a plain header with the name; a card on `/industries/` (Show on Listing is on by default) at the end of the grid |
| 3.3.2 | Add a **Page Header** with `QA [Test] Industry`, intro of 2 paragraphs (empty line between), 2 Applications, no image, Button 1 → `#contact` | Header with red "Test", 2 paragraphs, tags, the Card Photo as image; Button 1 scrolls to the Contact block (add one in 3.3.6) |
| 3.3.3 | **Recommended Products**: Show = Picked, pick 2 products, drag to reorder | 2 cards in your order; on each product's page, "What You Can Print" now lists `QA TEST Industry` |
| 3.3.4 | Same block: Show = Products from the categories, choose a category, How Many 6 | Up to 6 products of that category |
| 3.3.5 | Add **3 Content Blocks** in a row (one with a Video Link) | Image sides alternate; the video plays on click |
| 3.3.6 | Add **Contact Form**, Anchor ID `contact` | Panel with Site Settings phones / email / address + the form (see 3.7) |
| 3.3.7 | Add **Columns**: left Maroon panel (Rich Text), right White card (Form → the Contact Us form); Section Settings → Vertical Alignment Top | Panel and card side by side, each only as tall as its content; the form submits (thank-you message) and the submission appears in Formie → Submissions |
| 3.3.8 | Change Columns → Vertical Alignment = Equal height | The shorter column grows to match the taller |
| 3.3.9 | Add **Card Grid**, 4 per row, 5 cards, one with a Link | 4 per row on desktop (2 on tablet, 1 on phone); the linked card is clickable as a whole |
| 3.3.10 | Section Settings: Space Above None, Background Light grey | The section loses its top space and gets a grey background |
| 3.3.11 | Add **Other Industries** (empty) | 3 cards: the next industries in the list, not this one |
| 3.3.12 | Add **CTA Band** and **Photo Gallery** (5 photos) | Band with button; gallery opens a lightbox |
| 3.3.13 | Add common blocks (Heading, Rich Text, FAQ) between two section blocks | They appear in one text column between the sections |
| 3.3.14 | Card tab → Show on Listing off → Save | No card on `/industries/`; `/qa-test-industry/` still works |
| 3.3.15 | Phone width | Header image under the text; columns, cards and content blocks stack; no sideways scroll |

## 3.4 Industries Page (Entries → Industries Page) → `/industries/`

| Field | Shows on the website |
|---|---|
| Page Title, Small Heading, Heading ([brackets] = red), Intro | The header |
| **Count Line** | After "N industries" (N = industries on the listing), e.g. "Labels, packaging and industrial printing" |
| **Help Band Heading / Text** | The maroon band under the cards; its buttons are the phone and quote link from Globals → Site Settings |
| SEO tab | page source |

| # | Do | Expected |
|---|---|---|
| 3.4.1 | Open `/industries/` | Header with "10 industries" (+ yours when on the listing), numbered cards in structure order, help band |
| 3.4.2 | Change Count Line and Help Band Heading → Save | Both change. Undo afterwards |

## 3.5 Homepage: "Industries Carousel" block (Entries → Homepage → Homepage Sections)

| Field | What it does |
|---|---|
| Small Heading, Heading ([red] words) | Section texts |
| **Tiles** | Each Industry Tile: **Link** (an industry, or any URL), **Name** and **Photo** (empty = the linked industry's own) |
| View All Link, Anchor ID | The "View all industries" link; the section anchor |

| # | Do | Expected |
|---|---|---|
| 3.5.1 | Open the homepage industries carousel | Tiles scroll sideways; arrows move them; each tile opens its industry |
| 3.5.2 | Add a tile linking to `QA TEST Industry` with Name and Photo empty → Save | The tile shows the industry's name and Card Photo. Remove it afterwards |

## 3.6 Website checks

| # | Page | Check |
|---|---|---|
| 3.6.1 | `/flexible-packaging-and-custom-mylar-bags/` and 2 other industries | All sections show; images load; buttons and anchors work; Other Industries never shows the page itself |
| 3.6.2 | `/corrugation-printing/`, `/wallpaper-printer/` | Pages work but are not cards on `/industries/` (as live) |
| 3.6.3 | Page source of an industry | `<title>`, description, canonical, `og:type` article, `og:image`, JSON-LD WebPage + BreadcrumbList (Home › Industries › name) |
| 3.6.4 | A product picked in an industry's Recommended Products | Its product page lists the industry under "What You Can Print" |

## 3.7 Known limitations
- **The Contact Form block's form is not connected:** it checks the fields and then says it isn't connected yet; nothing
  is saved or emailed. The real, working form is the **Form** block (used on Contact Us). Replacing the Contact Form
  block on the industry pages is pending a decision.

## 3.8 Clean-up
Delete `QA TEST Industry` (Entries → Industries); remove your homepage tile; undo changes to the Industries Page.
