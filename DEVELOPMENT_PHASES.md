# Arrow Systems: Local Staging Development Log

This is a record of how the local staging site was built, phase by phase: what was created, how it was built, and
where it lives. Read it before changing the structure, and add to it when a new phase is done.

- **Project folder:** `/home/avadh/Desktop/arrow-system-local-staging`
- **Homepage design (HTML reference):** `/home/avadh/Desktop/Arrowsystem_local/index.html`
- **Reference project** (structure copied from here): `/home/avadh/Desktop/craft-cms-practice`

| Phase | What | Status |
|---|---|---|
| 0 | Project setup | ✅ Done |
| 1 | Site Settings (global set) | ✅ Done |
| 2 | User groups and users | ✅ Done (permissions to finish, see [Follow-ups](#follow-ups)) |
| 3 | Homepage: Hero Slider (structure, content, front end) | ✅ Done |
| 4 | Navigation menus: header + footer (CP and front end) | ✅ Done |
| 5 | Homepage: Benefit Cards (section B) | ✅ Done |
| 6 | Homepage: On-Demand Feature (section D) | ✅ Done (section C, Equipment Category Showcase, not built yet) |
| 7 | Homepage: Worldwide Locations (section H) | ✅ Done |
| 8 | Homepage: One Stop Solution (section I, background video) | ✅ Done |
| 9 | Homepage: Sustainable Printing CTA (section J) | ✅ Done |
| 10 | Homepage: End-of-Line Marking / Coding (section L) | ✅ Done (section K, blog: built in Phase 13) |
| 11 | Homepage: Industrial Printer Distributor (section M) | ✅ Done |
| 12 | Newsletter (section N, global set on every page) | ✅ Done (form not connected to HubSpot yet) |
| 13 | Blog migration: posts, categories, tags, `/blog/`, page builder, SEO, homepage section K | ✅ Done (see [Phase 13](#phase-13-blog-migration-live-arrsyscom--craft-with-the-neo-page-builder)) |
| 14 | Blog page builder moved from Neo to Matrix + custom code (Neo plugin removed) | ✅ Done (see [Phase 14](#phase-14-blog-page-builder-moved-from-neo-to-matrix-no-plugin)) |
| 15 | Cleanup: Neo's leftover data, old blog revisions, stale code, old comments, unused script helpers | ✅ Done (steps 1–5; see [Phase 15](#phase-15-cleanup-after-the-neo--matrix-move)) |
| 16 | Exhibitions: CRUD, listing, upcoming page, detail pages, homepage section G | ✅ Done (see [Phase 16](#phase-16-exhibitions-crud-listing-upcoming-detail-pages-homepage-section-g)) |
| 17 | Product categories (WooCommerce) + homepage section C (pick the categories) | ✅ Done (pages later; see [Phase 17](#phase-17-product-categories-from-woocommerce--homepage-section-c)) |
| 18 | Products: 104 product pages (`/product/{slug}/`) in the design's product page, flexible tabs, PDFs, redirects, menus | ✅ Done (see [Phase 18](#phase-18-products-product-pages-from-the-live-site-design-product-page)) |
| 19 | Products: Page Builder below the product header (Tabs, Video, Rich Text, Specifications, Downloads, FAQ, Image blocks) instead of the fixed Tabs / Video fields | ✅ Done (see [Phase 19](#phase-19-product-page-builder)) |
| 20 | Industries: `/industries/` + 12 industry pages (live URLs) built with a Page Builder (dynamic product selection), homepage section E, menus, links | ✅ Done (see [Phase 20](#phase-20-industries-page-builder-dynamic-products-homepage-section-e)) |
| 21 | One common page builder: the same content blocks in the blog, product and industry builders, plus each module's own blocks | ✅ Done (see [Phase 21](#phase-21-one-common-page-builder)) |
| 22 | Builder editors (blog, products, industries): blocks collapsed by default + an "Outline" navigator (Elementor-style) | ✅ Done (see [Phase 22](#phase-22-blog-editor-collapsed-blocks--outline-navigator)) |
| 23 | Blog search (`/blog/?q=…`, Craft's search index incl. article text) | ✅ Done (see [Phase 23](#phase-23-blog-search)) |
| 24 | Forms (Formie): Contact Us form = the live form | ✅ Form built; not on a page yet (Pages module) (see [Phase 24](#phase-24-forms-formie-contact-us)) |

---

## Phase 0: Project setup

### Installation

| Item | Value |
|---|---|
| Craft CMS | **5.11.4**, the same version as the arrow-systems server. Installed from the `craftcms/craft` starter |
| Edition | **Pro** (free trial; allowed because the site runs on `localhost`) |
| PHP | XAMPP PHP 8.2: always run Craft with `/opt/lampp/bin/php` |
| Database | MySQL (XAMPP), database **`arrow_local_staging`**, utf8mb4 |
| Site URL | `http://localhost:8081` (`PRIMARY_SITE_URL` in `.env`) |
| Control panel | `http://localhost:8081/admin` |
| Admin user | `admin` |
| Plugins | Navigation (`verbb/navigation` 4.0.7), see Phase 4 |
| Version control | None (no git). Use database backups (below). |

Settings live in `.env`: app ID, security key, database credentials, `PRIMARY_SITE_URL`, dev mode and admin changes on.

### Running the site

```bash
cd /home/avadh/Desktop/arrow-system-local-staging
/opt/lampp/bin/php craft serve --port=8081      # start the site
/opt/lampp/bin/php craft db/backup              # back up before any structure or content change
/opt/lampp/bin/php craft project-config/diff    # should say "No pending project config YAML changes."
```

Composer: run plain `composer` (system PHP) and add `--ignore-platform-req=ext-gd` to `require` commands.

### Fix: "Unable to verify your data submission" on login

Browsers share cookies between `localhost:8080` (the practice project) and `localhost:8081`. Both sites used the same
CSRF and session cookie names, so they overwrote each other's cookies and logins failed. This site now has its own
names, in `config/general.php`:

```php
->csrfTokenName('ARROW_STAGING_CSRF_TOKEN')
->phpSessionName('ArrowStagingSessionId')
```

### Email

**Settings → Email:** transport **SMTP** (Gmail, port 587, with authentication). The local machine has no
`sendmail`, so the Sendmail transport can't send. The SMTP password is currently saved in project config; see
[Follow-ups](#follow-ups).

---

## Phase 1: Site Settings (global set)

Company-wide details (logo, contact details, calls to action, social links), editable under **Globals → Site Settings**.

### How it was built

The structure was **copied from the practice project's project config, with the same UIDs**, and without any
content:

1. Copied these YAML files from `craft-cms-practice/config/project/` into this project's `config/project/`:
   the 11 field files, `globalSets/siteSettings--….yaml` and `volumes/images--….yaml`.
2. Added the **Uploads** filesystem to `config/project/project.yaml`.
3. Ran `php craft project-config/apply`, then `php craft project-config/write`.

Keeping the same UIDs means anything copied from the practice project later lines up with these fields instead of
creating duplicates.

### Asset storage (needed for Logo and Favicon)

| Item | Handle | Settings |
|---|---|---|
| Filesystem **Uploads** | `uploads` | Local folder `@webroot/uploads` (`web/uploads/`), public URL `@web/uploads` |
| Volume **Images** | `images` | Uses the Uploads filesystem. Layout: Title + **Alt Text** |

### Global set: Site Settings (`siteSettings`)

| Tab | Field | Handle | Type | Notes |
|---|---|---|---|---|
| Brand | Logo | `logo` | Assets | Images volume |
| Brand | Logo Alt Text | `logoAlt` | Plain Text | |
| Brand | Favicon | `favicon` | Assets | Images volume |
| Brand | Tagline | `siteTagline` | Plain Text | |
| Contact | Address | `address` | Plain Text | |
| Contact | Toll-free Phone | `tollFreePhone` | Plain Text | |
| Contact | Direct Phone | `directPhone` | Plain Text | |
| Contact | Sales Email | `salesEmail` | Email | |
| Calls to action | Request a Quote Link | `quoteLink` | Link | Used by every "Request a Quote" button |
| Calls to action | Free Sample Link | `sampleLink` | Link | Also the default 2nd button on every hero slide (Phase 3) |
| Social | Social Links | `socialLinks` | Table | Columns: **Network** (dropdown: Facebook, LinkedIn, YouTube, Instagram) + **URL** |

In templates the set is available everywhere as `siteSettings`, for example `{{ siteSettings.salesEmail }}`.
To add a social network: **Settings → Fields → Social Links → Network column ⚙ → Add an option** (label + lowercase
value). The template also needs an icon `#i-{value}` in the sprite.

**Content entered so far:** Logo (`ASI-Logo-4.webp`), Sales Email (`sales@arrsys.com`), Request a Quote Link
(`https://arrsys.com/request-quote/`), and Free Sample Link ("Try a Free Sample" → `https://arrsys.com/try-a-free-sample/`).

---

## Phase 2: User groups and users

Craft has no fixed roles like WordPress. Each role is a **user group** with its own permissions, plus an **Admin**
switch for full access. User groups need the **Pro** edition (Solo has none; Team allows one group).

### WordPress role → Craft

| WordPress | Craft |
|---|---|
| Administrator | A user with the **Admin** switch on (no group) |
| Editor | **Editor** group |
| Subscriber | **Subscriber** group |

### Groups (Settings → Users → User Groups)

| Group | Handle | Permissions now |
|---|---|---|
| Editor | `editor` | Access the control panel · Access the CP when the system is offline · Perform updates |
| Subscriber | `subscriber` | Access the control panel |

Groups and their permissions are saved in project config (`config/project/users/groups/`), so they deploy with the
site. **User accounts** are content in the database and have to be created on each environment.

### Users

| Username | Role |
|---|---|
| `admin` | Admin |
| `editor_one` | Editor group |

### How to manage users

- **New user:** Users → New user → Permissions tab → tick a group (or the Admin switch).
- **Passwords:** an admin can't type a new password for another user in the CP. Use the user's **⋯ menu → Send
  password reset email** or **Copy password reset URL**, or run `php craft users/set-password <username>`.
- **Test a role:** user's **⋯ → Sign in as** shows the CP as that user sees it.
- **Note:** new sections and global sets aren't added to existing groups automatically. Tick them in the group's
  permissions after creating them.

---

## Phase 3: Homepage: Hero Slider

Section A of the design (`index.html`, the "HERO SLIDER + FREE SAMPLE CTA" block).

**Decision:** each slide is a **ready-made banner image** (the artwork already contains the machine, logo and
headline), plus a caption bar under it with a **Category Label** and **Product Name** on the left and the
**buttons** (real label and link) on the right. The design's lead text, tags and narrow/wide option were left out on
purpose. The Product Name (or else the image's Alt Text) names the slide for screen readers.

### How the content is structured

```
Homepage (section: Single, URI __home__, template index)
└── Homepage entry (entry type: Homepage)
    └── Homepage Sections (Matrix: homepageSections)     ← more homepage sections get added here later
        └── Hero Slider (entry type: heroSlider)
            ├── Accessible Name (ariaLabel)
            └── Slides (Matrix: slides)
                └── Hero Slide (entry type: heroSlide) × n
                    ├── Banner Image (bannerImage)
                    ├── Category Label (categoryLabel)
                    ├── Product Name (productName)
                    ├── Primary Button (primaryCta)
                    └── Secondary Button (secondaryCta) → falls back to Site Settings → Free Sample Link
```

### Step 1: Design files copied (unchanged)

| From `Arrowsystem_local/` | To this project |
|---|---|
| `css/home.css` | `web/assets/css/site.css` |
| `js/home.js` | `web/assets/js/site.js` (runs the carousel through the `data-carousel…` attributes) |
| Icon sprite in `index.html` (lines 31–72, 40 icons) | `templates/_partials/icon-sprite.twig` |

### Step 2: Fields (Settings → Fields)

Created with a one-off PHP script (same result as creating them in the CP).

| Field | Handle | Type | Settings |
|---|---|---|---|
| Banner Image | `bannerImage` | Assets | Source: **Images** only · images only · min 1, max 1 · instructions: "Ready-made banner, 1920 × 600 px (16:5). Fill in the image's Alt Text." |
| Primary Button | `primaryCta` | Link | Link types: **URL** (default), Entry · **Show the label field** on · instructions: "Paste the link, then type the button text in Label (e.g. Explore Product)." |
| Secondary Button | `secondaryCta` | Link | Same as Primary · instructions: "Optional second button: paste the link and type the button text in Label. Leave empty to use Globals → Site Settings → Free Sample Link." |
| Accessible Name | `ariaLabel` | Plain Text | Name of the slider for screen readers |
| Category Label | `categoryLabel` | Plain Text | Optional. Small text above the name, e.g. "UV flatbed printer" (the design's "kicker"). Added later in Phase 3. |
| Product Name | `productName` | Plain Text | Optional. Large name under the banner, e.g. "ArrowJet MTS-48" (the design's slide title). Also names the slide for screen readers. Added later in Phase 3. |

### Step 3: Entry types and Matrix fields

Built in the CP in this order (each Matrix field needs its entry type first, and each parent entry type needs its
Matrix field):

**1. Entry type: Hero Slide** (`heroSlide`; CP name currently "Hero SLide")

| Setting | Value |
|---|---|
| Title field | Hidden. **Title Format:** `{{ object.bannerImage.one().title ?? 'Slide' }}` (each slide is named after its image) |
| Slug field | Hidden |
| Field layout (Content tab) | **Banner Image** (required) · **Category Label** · **Product Name** · **Primary Button** (required) · **Secondary Button** (optional) |

**2. Matrix field: Slides** (`slides`)

| Setting | Value |
|---|---|
| Entry types | Hero Slide |
| Min entries | 1 |
| View mode | **Blocks** (slides are edited inline on the Homepage page). Was Cards; changed because of the Link field bug below. |
| "New" button label | Add slide |

**3. Entry type: Hero Slider** (`heroSlider`)

| Setting | Value |
|---|---|
| Title field | Hidden. **Title Format:** `Hero slider` |
| Slug field | Hidden |
| Field layout (Content tab) | **Accessible Name** (optional) · **Slides** (required) |

**4. Matrix field: Homepage Sections** (`homepageSections`)

| Setting | Value |
|---|---|
| Entry types | Hero Slider (later homepage sections are added here) |
| View mode | Blocks (inline) |
| "New" button label | Add section |

**5. Entry type: Homepage** (`homepage`)

| Setting | Value |
|---|---|
| Title field | Shown (required) |
| Slug field | Hidden |
| Field layout (Content tab) | Title · **Homepage Sections** |

### Step 4: Section: Homepage

**Settings → Sections → New section**

| Setting | Value |
|---|---|
| Name / handle | Homepage / `homepage` |
| Type | **Single** (exactly one entry, created automatically) |
| Entry type | Homepage |
| Versioning | On |
| Site settings | 🏠 **homepage** ticked → URI `__home__` · Template **`index`** |
| Preview target | Primary entry page → `{url}` |

### Content entered: Entries → Homepage

Added with a one-off script (temporary, not kept in the project). The 5 banners are from
`Arrowsystem_local/assets/hero/`, uploaded to **Assets → Images** (`web/uploads/`), with each image's title set to the
machine name and **Alt Text** copied from `index.html`.

**Homepage Sections → Hero Slider**, Accessible Name: *Featured ArrowJet equipment*

| # | Banner image | Size | Category Label | Product Name | Primary Button | Secondary Button |
|---|---|---|---|---|---|---|
| 1 | ArrowJet MTS-48 (`arrowjet-mts-48.webp`) | 2560 × 1067 ⚠️ | UV flatbed printer | ArrowJet MTS-48 | Explore Product → `https://arrsys.com/product/arrowjet-mts-48/` | empty → Site Settings |
| 2 | ArrowJet Aqua 800M (`arrowjet-aqua-800m.webp`) | 1600 × 500 | Wide-web printer | ArrowJet Aqua 800M | Explore Product → `https://arrsys.com/product/arrowjet-aqua-800m/` | empty → Site Settings |
| 3 | ArrowJet Aqua 330R Hybrid Pro M (`arrowjet-aqua-330r-hybrid-pro-m.webp`) | 1600 × 500 | Hybrid digital label press | ArrowJet Aqua 330R Hybrid Pro M | Explore Product → `https://arrsys.com/product/arrowjet-aqua-330r-hybrid-pro-m/` | empty → Site Settings |
| 4 | ArrowJet Aqua 330R Lite 2 (`arrowjet-aqua-330r-lite-2.webp`) | 1600 × 500 | Inkjet label printer | ArrowJet Aqua 330R Lite 2 | Explore Product → `https://arrsys.com/product/arrowjet-aqua-330r-lite-2/` | empty → Site Settings |
| 5 | ArrowJet Nova 250R (`arrowjet-nova-250r.webp`) | 1600 × 500 | Print & cut inline | ArrowJet Nova 250R | Explore Product → `https://arrsys.com/product/arrowjet-nova250r-inline-digital-printer-and-laser-die-cutter-2/` | empty → Site Settings |

**Editing slides:** Entries → Homepage → Hero Slider. Each slide is shown inline: edit it there, drag to reorder, and
click the Homepage's **Save** (top right) to publish. Until then the changes are an unsaved draft and the site doesn't
change. To add a slide, upload the banner (with Alt Text), then click **Add slide**.

> **Known Craft 5.11.4 bug: Link fields in slideouts.** In a slideout panel (e.g. a Matrix *card* opened from its
> parent entry), a Link field's URL chip **⋯ → Edit** does nothing, so the URL can't be changed there (the Label can).
> It works on a full page and in inline blocks, so the Slides field uses **Blocks** view. Not fixed upstream as of
> Craft 5.11.5.1. Keep Matrix fields that contain Link fields in **Blocks** view, or edit those entries on their own page.

### Step 5: Templates (front end)

The homepage at `http://localhost:8081/` now shows the header and the hero slider.

| File | What it does |
|---|---|
| `templates/_layouts/base.twig` | Page shell from `index.html`: `<head>` (fonts, `site.css`, `craft.css`, `site.js`, favicon from Site Settings), icon sprite, skip link, header, `<main id="main">`, footer. Blocks: `title`, `head`, `content`. Header and footer are built from the menus (Phase 4). |
| `web/assets/css/craft.css` | Fixes and additions for the Craft templates, loaded after the design's `site.css` (which stays unchanged). Contains the **hero button click fix** (below). |
| ~~`templates/_partials/header.twig`~~ | The static header copied from the design. **Removed in Phase 4**: the header is now built from the Navigation menus (`templates/_partials/navigation/`). |
| `templates/index.twig` | Homepage: loops `entry.homepageSections.all()` and includes `_blocks/home/{type handle}.twig` (heroSlider → `hero-slider.twig`). Preloads the first banner. |
| `templates/_blocks/home/hero-slider.twig` | The design's hero markup: banner, then the caption bar with Category Label + Product Name (`hero-slide__kicker` / `hero-slide__title`) and the buttons. Class names and `data-carousel…` attributes are unchanged, so `site.js` runs it. Slide and dot labels use the Product Name (else the image's Alt Text). Button link **and text** come only from the CMS (no hard-coded text): the Label, or, if it's empty, Craft's default (the linked entry's title, or the URL). The secondary button falls back to `siteSettings.sampleLink`. |

**Hero button click fix (design bug):** on desktop (≥ 1024 px) the design's slider controls bar (`.hero__controls`)
is absolutely positioned across the full width of the caption row, on top of the buttons, so clicks on
"Explore Product" and the second button never reached them (the original `index.html` has the same bug; mobile was
fine). `craft.css` sets `pointer-events: none` on the bar and `auto` on its children, so the empty part lets clicks
through and the arrows, dots and pause stay clickable. Tested with real clicks at 1600, 1100 and 390 px.

**Why the header is required:** `site.js` (the design's `home.js`) sets up the header and menus first, without
checking they exist, and only then starts the carousel. Without the header the script stops with an error and the
slider never starts. Every page that uses `site.js` must include the header (`base.twig` does).

**Tested** in Chrome (desktop 1440 px and mobile 390 px): no JavaScript errors, no failed requests, and Next/Prev,
dots, pause/play, autoplay and the mega menu all work. The CP and 404 pages still work, and the project config is
in sync. Craft's starter `templates/index.twig` was replaced.

---

## Phase 4: Navigation menus (header and footer)

WordPress-style menu management with the **Navigation** plugin (`verbb/navigation` 4.0.7; paid: $19, then $5/year for updates, free trial on localhost): **Navigation → Menus**
in the CP sidebar. Set up the same as the practice project.

### How it was built

1. `composer require verbb/navigation:^4.0.4` (installed 4.0.7) and `php craft plugin/install navigation`.
2. Copied from `craft-cms-practice/config/project/` (same UIDs, like Site Settings): the 10 menu-item fields and the 4
   menu files in `navigation/menus/`. The menus' site setting was switched to this site, and the **Featured Product**
   field (an Entries field that picks from a *Products* section, which this project doesn't have yet) was left out
   of the Main Menu. Then `project-config/apply` and `project-config/write`.
3. Copied the menu items from the practice project's database with a one-off script (not kept in the project).

### Menus

| Menu | Handle | Levels | Used for | Items |
|---|---|---|---|---|
| Top Bar | `topBar` | 1 | Utility links above the header (Support, Order Supplies, Distributors, Contact) | 4 |
| Main Menu | `mainMenu` | 4 | Header menu. L1: menu items · L2: group headings (mega menus) or links · L3: categories / industries · L4: products | 79 |
| Footer Menu | `footerMenu` | 2 | L1: column headings (Group / Column) · L2: the links in each column | 30 |
| Legal Menu | `legalMenu` | 1 | Links in the footer's bottom bar, after the © text | 4 |

### Menu item fields (each appears only at the level where it applies)

| Field | Handle | Type | Shown on |
|---|---|---|---|
| Menu Style | `menuStyle` | Dropdown: Simple dropdown / Products mega menu / Industries mega menu | Main Menu, level 1 |
| Partner group | `isPartnerGroup` | Lightswitch | Main Menu, level 2 |
| Panel Title | `panelTitle` | Plain Text | Main Menu, level 3 |
| View All Text | `viewAllLabel` | Plain Text | Main Menu, level 3 |
| Menu Icon | `menuIcon` | Dropdown (icons from the design's sprite) | Main Menu, level 3 |
| Description | `itemDescription` | Plain Text | Main Menu, level 4 |

Every item also has the plugin's own **Label**, link type (Entry, Custom URL, Passive, Group / Column…) and **Open in
new window**.

**Main Menu → Menu content** (set once for the whole menu):

| Tab | Field | Handle | Value copied |
|---|---|---|---|
| Products box | Featured Text | `featuredText` | (empty, as in practice) |
| Industries box | Help Box Title | `helpTitle` | Not sure which equipment fits? |
| Industries box | Help Box Text | `helpText` | Tell us your application, materials and run lengths. |
| Industries box | Help Box Button | `helpLink` (Link) | Talk to a Specialist → `tel:+18886972749` |

### Content copied

- **117 items**, in the same order and nesting as the practice project, with the same labels, link types, new-window
  settings and item fields (checked item by item: 0 differences).
- **Entry links became Custom URL items.** 46 practice items linked to entries (products, industries, product
  categories, the Industries and Exhibitions listing pages) that don't exist here yet. They now point to the same
  pages on arrsys.com, using the URL from the approved design where it has one. When those sections are built, edit
  each item and switch it back to an **Entry** link.
- **Left out:** two leftover items titled "test" in the practice Main Menu, and the Featured Product (see above).
- **Passive** items (e.g. Distributors, Compare Models, Terms) are links without a destination yet, the same as practice.

### Featured box fields (added on staging)

The practice project's Products box used a **Featured Product** picker (needs a Products section). To keep the design's
featured box, the Main Menu's **Menu content → Products box** tab has these fields instead:

| Field | Handle | Type | Value |
|---|---|---|---|
| Featured Image | `featuredImage` | Assets (Images, 1 image) | `arrowjet-aqua-800m-cutout.webp` (1400 × 702, from the design's `assets/products/web/`) |
| Featured Title | `featuredTitle` | Plain Text | ArrowJet Aqua 800M |
| Featured Text | `featuredText` | Plain Text | 25″ wide-web inkjet for flexible packaging. |
| Featured Link | `featuredLink` | Link (URL / Entry, with label) | Explore Product → `https://arrsys.com/product/arrowjet-aqua-800m/` |

The box is shown when **Featured Title** is filled. The level-2 items under Products that aren't Group / Column
(Compare Models, Used Equipment) are listed under it.

### Menu content aligned with the design

The practice menus differed from the approved design in a few places, so on staging they were aligned with
`index.html`:
- **Description** (level-4 field) filled for 19 products: the grey second line under each product, e.g. "Entry-level
  commercial inkjet label printer". In practice it came from the linked product entries.
- **Menu Icon** (level-3 field) set on the 6 industry links (wine, pill, flask, package, factory, plane), which in
  practice also came from the linked entries.
- Order restored: the Digital Label Printers products and the Materials dropdown.
- "ArrowJet Aqua 330R" now links to `/product/arrowjet-aqua-330r/` (it pointed at the Lite 2 product).

### Front end

| File | What it does |
|---|---|
| `templates/_partials/navigation/header.twig` | Utility bar (**Top Bar**), logo, main menu (**Main Menu**), phone + Request a Quote (**Site Settings**), search |
| `templates/_partials/navigation/dropdown.twig` | Main Menu item with Menu Style "Simple dropdown" (Solutions, Materials, Resources, Support, About) |
| `templates/_partials/navigation/mega-products.twig` | Menu Style "Products mega menu": groups → categories → products, + the featured box |
| `templates/_partials/navigation/mega-industries.twig` | Menu Style "Industries mega menu": groups → industries with icons, "View all", + the help box |
| `templates/_partials/navigation/search.twig` | Search panel from the design (interim: searches arrsys.com) |
| `templates/_partials/navigation/footer.twig` | Brand, tagline, address, phones, email, social links (**Site Settings**), columns (**Footer Menu**), © + **Legal Menu** |
| `templates/_macros/nav.twig` | Menu item helpers: link attributes (an item with no link → `href="#" data-todo`), description, icon |
| `templates/_macros/links.twig` | Link field attributes and `tel:` formatting |

The markup is the design's, so `site.css` and `site.js` work unchanged. The templates are not cached, so menu edits
show on the site as soon as they're saved.

**Checked:**
- **Header:** the same 807 markup tokens as the design's header. The only differences are the category panel IDs (now
  generated from the names), the featured photo's URL, four links that now open real arrsys.com pages instead of `#`
  (View all industries, Guides & Articles, Exhibitions, and `/#on-demand`), and placeholder note texts.
- **Footer:** identical to the design's footer except the logo URL and the links that pointed at the design's mock-up pages.
- **In Chrome:** every menu opens, category switching works, search works, and the mobile drawer works. No JavaScript
  errors, no failed requests, and the hero slider is unchanged.
- **Live edits:** renaming a menu item shows on the site immediately.

---

## Phase 5: Homepage: Benefit Cards (section B)

Section B of the design (`index.html`, "BENEFIT CARDS"): three linked cards under the hero, each with an
illustration, a title and a small "more" line. The whole card is clickable (the design's `stretched-link`).

### How the content is structured

```
Homepage → Homepage Sections (homepageSections)
   ├── Hero Slider
   └── Benefit Cards (entry type: benefitStrip)
         ├── Accessible Name (ariaLabel, reused)
         └── Benefits (Matrix: benefits) → Benefit (entry type: benefitItem) × 3
                                              ├── Title          (the card title)
                                              ├── Illustration   (benefitIcon)
                                              └── Link           (benefitLink; its Label is the small line under the title)
```

### Step 1: Illustrations

The 3 SVGs from `Arrowsystem_local/assets/icons/` were uploaded to **Assets → Images** (`web/uploads/`):
`support-premium.svg`, `worldwide-premium.svg`, `sustainable-premium.svg`. They're decorative, so they have no Alt Text
and the template outputs `alt=""`.

### Step 2: Fields

| Field | Handle | Type | Settings |
|---|---|---|---|
| Illustration | `benefitIcon` | Assets | Images volume · images only · max 1 · instructions: "Square SVG illustration (128 × 128)." |
| Link | `benefitLink` | Link | Types: **URL** (default), Entry · label field on · URL settings: **allow anchors** (for `#locations`), root-relative URLs, custom schemes |

### Step 3: Entry types and Matrix

| Item | Handle | Settings |
|---|---|---|
| Entry type **Benefit** | `benefitItem` | Title field on (label "Title", required) · slug off · layout: Title, Illustration (required), Link (required) |
| Matrix field **Benefits** | `benefits` | Entry type: Benefit · min 1, max 3 · **Blocks** view (Cards would hit the slideout Link bug, see Phase 3) · button "Add benefit" |
| Entry type **Benefit Cards** | `benefitStrip` | Title field off, Title Format `B · Benefit cards` · layout: Accessible Name, Benefits (required) |
| **Homepage Sections** | `homepageSections` | Entry types now: Hero Slider, Benefit Cards |

### Step 4: Content: Entries → Homepage → Benefit Cards

Accessible Name: *Why Arrow Systems*

| # | Title | Illustration | Link label (small line) | Link |
|---|---|---|---|---|
| 1 | 24 × 7 Support | `support-premium.svg` | Request support | `https://arrsys.com/support/` |
| 2 | Worldwide Presence | `worldwide-premium.svg` | See our locations | `#locations` (Locations section, not built yet) |
| 3 | Sustainable | `sustainable-premium.svg` | Water-based ink options | `#sustainable` (section not built yet) |

### Step 5: Template

`templates/_blocks/home/benefit-strip.twig`: the design's markup with the three cards replaced by a loop over
`block.benefits`. `index.twig` already includes it, because the entry type handle `benefitStrip` maps to
`benefit-strip.twig`. No CSS or JS changes were needed (`site.css` already styles `.benefits`).

**Checked:**
- **Markup:** the same 54 tokens as the design's section; only the illustration URLs differ (they now come from the CMS).
- **Clicks:** clicking anywhere on a card (tested on the illustration) opens its link: card 1 opens the support page,
  cards 2 and 3 jump to `#locations` / `#sustainable`.
- **Layout:** desktop shows 3 cards in a row, mobile stacks them.
- **No regressions:** no JavaScript errors or failed requests, and the hero slider and menus are unchanged.

---

## Phase 6: Homepage: On-Demand Feature (section D)

Section D of the design (`index.html`, "ON-DEMAND PRODUCTION BENEFITS + CONTACT"), on the grey background:
- a centred heading block: small red label, heading with a red part, intro paragraph
- facts with icons, a small note and a **Contact Us Now** button
- a click-to-play YouTube video with a caption

The section has `id="on-demand"`, so the header's *Solutions → On-Demand Label Production* (`/#on-demand`) jumps to
it. Section C (Equipment Category Showcase) isn't built yet, so D sits under the Benefit Cards for now.

### How the content is structured

```
Homepage → Homepage Sections
   ├── Hero Slider · Benefit Cards
   └── On-Demand Feature (entry type: videoFeature)
         Heading tab:  Small Heading (eyebrow) · Heading (sectionHeading) · Intro (sectionLead)
         Video tab:    Video Link (videoUrl) · Video Cover Image (videoPoster) · Video Caption (videoCaption)
         Facts tab:    Facts (facts: Icon + Text) · Facts Note (factNote) · Button (ctaLink)
         Settings tab: Anchor ID (anchorId)
```

### Fields

| Field | Handle | Type | Notes |
|---|---|---|---|
| Small Heading | `eyebrow` | Plain Text | Small red label above the heading |
| Heading | `sectionHeading` | Plain Text | Words in `[square brackets]` are shown in red (`_macros/text.twig` → `highlight`) |
| Intro | `sectionLead` | Plain Text (multi-line) | Paragraph under the heading |
| Video Link | `videoUrl` | Plain Text | Any YouTube link; the template extracts the video ID (`youtubeId` macro) |
| Video Cover Image | `videoPoster` | Assets (Images, 1 image) | Shown before the video plays |
| Video Caption | `videoCaption` | Plain Text | Text under the video, and the Play button's screen-reader label |
| Facts | `facts` | Table | **Icon** (dropdown of design sprite icons: gauge, scan, droplet, leaf, printer, cpu, shield, clock, wrench, layers) + **Text** |
| Facts Note | `factNote` | Plain Text | Small print under the facts |
| Button | `ctaLink` | Link (URL default, Entry; label; anchors + root-relative URLs allowed) | Button text = Label |
| Anchor ID | `anchorId` | Plain Text | The section's `id` (for `/#…` menu links) |

These fields are generic, so later sections (E, G, K…) that have the same heading block can reuse Small Heading,
Heading, Intro, Button and Anchor ID.

### Entry type

**On-Demand Feature** (`videoFeature`): title field off, Title Format `D · On-demand feature`, slug off, four tabs
(Heading, Video, Facts, Settings), Heading required. Added to **Homepage Sections** after Benefit Cards.

### Content: Entries → Homepage → On-Demand Feature

| Field | Value |
|---|---|
| Small Heading | In-house production |
| Heading | Transform Your Label Production with [On-Demand Efficiency] |
| Intro | Take control of your brand’s image with Arrow Systems, Inc.’s Color Label Printers. … watch your brand soar. |
| Video Link | https://youtu.be/PbjRZJS6dbQ |
| Video Cover Image | `video-on-demand-label-production.webp` (1280 × 720, from the design's `assets/video/web/`) |
| Video Caption | ArrowJet Aqua 330R \| Next-Gen Digital Inkjet Printer |
| Facts | gauge · Up to 150 feet per minute · scan · Resolution of up to 1600 × 1600 dpi · droplet · Nestle Guidance Compliant water-based pigment inks |
| Facts Note | Maximum figures for ArrowJet color label printers. Capabilities vary by model; see each product page. |
| Button | Contact Us Now → `https://arrsys.com/contact-us/` |
| Anchor ID | on-demand |

### Templates

| File | What it does |
|---|---|
| `templates/_blocks/home/video-feature.twig` | The design's section D markup, filled from the fields. Class names and `data-video` / `data-video-play` are unchanged, so `site.js` runs the click-to-play video. |
| `templates/_macros/text.twig` | `highlight()` turns `[words]` into red text (`<span class="hl">`, escaped first), `plain()` strips the brackets, and `youtubeId()` gets the ID from a YouTube link. |

**Checked:**
- **Markup:** the same 81 tokens as the design's section; only the cover image URL differs (it comes from the CMS).
- **Video:** no YouTube requests before Play. Clicking **Play video** loads `youtube-nocookie.com/embed/PbjRZJS6dbQ` in place.
- **Links:** **Contact Us Now** opens the contact page, and the menu link *On-Demand Label Production* scrolls to the section.
- **Layout:** desktop puts the facts left and the video right; mobile shows video, then facts, then the button.
- **No regressions:** no JavaScript errors or failed requests.

---

## Phase 7: Homepage: Worldwide Locations (section H)

Section H of the design (`index.html`, "WORLDWIDE LOCATIONS"): a world map with a pin per office, plus text and a
**Learn More** button. The pins, clusters and tooltips are drawn by `site.js` from a (hidden) list of locations in
the page. The section's id will be `locations`, which the Benefit card "See our locations" (`#locations`) links to.

### Step 1: Map artwork

Copied `Arrowsystem_local/assets/maps/world-land-clean.svg` → `web/assets/maps/world-land-clean.svg` (a design file,
not editor content; served at `/assets/maps/world-land-clean.svg`).

### Step 2: Fields

| Field | Handle | Type | Settings |
|---|---|---|---|
| City | `city` | Plain Text | |
| Country | `country` | Plain Text | |
| Region | `region` | Dropdown | `north-america` (default) · `central-america` · `south-america` · `europe` · `mea` · `asia-pacific` |
| Relationship | `relationship` | Dropdown | `partner` Partner location (default) · `headquarters` Headquarters (red pin) |
| Latitude | `latitude` | Number | 4 decimals, -90 to 90 |
| Longitude | `longitude` | Number | 4 decimals, -180 to 180 (east positive, west negative) |
| Body Text | `sectionBody` | Plain Text (multi-line) | Paragraph under the intro |
| Show city list | `showLocationList` | Lightswitch | Off by default; on = also show the location list on screen |

Reused: Small Heading (`eyebrow`), Heading (`sectionHeading`), Intro (`sectionLead`), Button (`ctaLink`), Anchor ID (`anchorId`).

### Step 3: Locations section (built in the CP)

| Item | Handle | Settings |
|---|---|---|
| Entry type **Location** | `location` | Title field off, Title Format `{city}, {country}` · slug off · City, Country, Region, Relationship, Latitude, Longitude (City, Country, Latitude, Longitude required) |
| Section **Locations** | `locations` | Channel · entry type Location · no URLs · **Default Status: enabled** (a disabled location has no pin) |

### Step 4: Homepage section type (built in the CP)

**Locations Map** (`locationsMap`): title off, Title Format `H · Worldwide locations`, slug off.
- **Content tab:** Small Heading, Heading, Intro, Body Text, Button
- **Settings tab:** Show city list, Anchor ID

Added to **Homepage Sections** (now: Hero Slider, Benefit Cards, On-Demand Feature, Locations Map).

### Step 5: Content

**Entries → Locations:** the 17 locations from the design. Their latitude/longitude were converted back from the
design's map positions, so the pins land in the same places.

| Region | Locations |
|---|---|
| North America | **Niagara Falls, United States (Headquarters)** · Burlington, Canada · Mountainside, US · Romulus, US · West Allis, US |
| Central America & Caribbean | Guatemala City · San Salvador · Managua · Santo Domingo |
| South America | Buenos Aires |
| Europe | Tiel · Warsaw · Bratislava · Veszprém · Gerakas |
| Middle East & Africa | Sharjah · Cape Town |

**Entries → Homepage → H · Worldwide locations:**
- Small Heading: Global reach
- Heading: Our [Worldwide] Locations
- Intro: We provide advanced digital label…
- Body Text: Our local expertise…
- Button: Learn More → arrsys.com/contact-us/
- Show city list: off
- Anchor ID: `locations`

### Step 6: Template

`templates/_blocks/home/locations-map.twig`: the design's section H markup.
- **Left:** the map (`web/assets/maps/world-land-clean.svg`) and its legend.
- **Right:** Small Heading, Heading (`[Worldwide]` in red), Intro, Body Text, and the Learn More button.
- **Location list** (`data-locations-list`, hidden unless *Show city list* is on): built from **Entries → Locations**,
  grouped by Region in the design's order, Headquarters first, then in the order the locations were added.
- **Pin position:** `x = (longitude + 180) / 360 × 100`, `y = (85 − latitude) / 145 × 100`, the same projection as the
  map artwork.
- `site.js` draws the pins, clusters and tooltips from that list, unchanged from the design.
- The section's `id` is the Anchor ID (`locations`).

**Adding an office:** Entries → Locations → New entry (City, Country, Region, Relationship, Latitude, Longitude). Its
pin appears on the map automatically and joins a cluster if it's close to others. A location that's disabled has no pin.

### Step 7: Checks

- **Markup:** the same 184 tokens as the design's section, the same 17 locations in the same order, and the **same
  pin positions** (0.000 % difference). Only the map image URL differs.
- **Design vs staging, same script:** identical pins on desktop (6: clusters of 5, 4 and 5 + Buenos Aires, Sharjah,
  Cape Town) and on mobile (4: clusters of 9 and 6 + Buenos Aires, Cape Town). Clicking a pin shows its city; clicking
  a cluster lists its cities, with "Niagara Falls, United States (Headquarters)" first.
- **Links:** **Learn More** opens the contact page, and the Benefit card "See our locations" now scrolls to this section.
- **Layout:** desktop puts the map left and the text right; mobile stacks the map above the text.
- **No regressions:** no JavaScript errors or failed requests.

---

## Phase 8: Homepage: One Stop Solution (section I)

Section I of the design (`index.html`, "ONE STOP SOLUTION"): a full-width section with a **muted YouTube video
playing in the background** (loops, no controls), a dark overlay and white text on top:
- small label, heading "[One Stop] Solution", subheading, a paragraph
- a line above the button, then the **Contact us** button

`site.js` loads the video only when the section nears the viewport, and never for reduced-motion or Save-Data
visitors, who keep the poster image. It supports one background-video section per page (the first `data-bg-video`).

### Step 1: Poster image

Uploaded `Arrowsystem_local/assets/video/web/video-one-stop-solution.webp` (1280 × 720, the video's own thumbnail) to
**Assets → Images** (`web/uploads/`), with the title "One stop solution (video cover)" and no Alt Text (decorative).

### Step 2: Fields

| Field | Handle | Type | Instructions |
|---|---|---|---|
| Subheading | `subheading` | Plain Text | Line under the heading, e.g. “For Label And Flexible Packaging”. |
| Button Intro | `ctaLead` | Plain Text | Short line above the button, e.g. “For More Information, Contact Us Now”. |

Reused: Small Heading (`eyebrow`), Heading (`sectionHeading`), Body Text (`sectionBody`), Button (`ctaLink`), Video Link
(`videoUrl`), Video Cover Image (`videoPoster`), Anchor ID (`anchorId`).

### Step 3: Entry type (built in the CP)

**Background Video Section** (`videoBackgroundCta`): title off, Title Format `I · One stop solution`, slug off.
- **Content tab:** Small Heading, Heading (required), Subheading, Body Text, Button Intro, Button
- **Video tab:** Video Link, Video Cover Image
- **Settings tab:** Anchor ID

Added to **Homepage Sections**.

> **Fixed:** in this layout, Video Cover Image had its handle overridden to `videoPoster7` (field ⚙ → *Handle* in the
> layout designer; Craft can fill this in, e.g. after a field is dragged in twice). Templates use the field's real
> handle, so the override was removed; the image stayed attached. **Leave that per-layout Handle box empty.**

### Step 4: Content (entered in the CP)

| Field | Value |
|---|---|
| Small Heading | Labels · Flexible & corrugated packaging |
| Heading | [One Stop] Solution |
| Subheading | For Label And Flexible Packaging |
| Body Text | We give you the Next-Gen digital printing solutions … on a variety of substrates. |
| Button Intro | For More Information, Contact Us Now |
| Button | Contact us → `https://arrsys.com/contact-us/` |
| Video Link | https://youtu.be/RSJhcn80hzQ |
| Video Cover Image | `video-one-stop-solution.webp` |
| Anchor ID | one-stop |

Placed after Worldwide Locations (homepage order: Hero → Benefit Cards → On-Demand → Worldwide Locations → One Stop).

### Step 5: Template

`templates/_blocks/home/video-background-cta.twig`: the design's section I markup.
- `<section class="onestop" data-bg-video="{YouTube ID}">`, with the ID taken from Video Link by the `youtubeId` macro.
- The poster `<img>` and the empty `[data-bg-video-frame]`, where `site.js` puts the video.
- The overlay, then the text: Small Heading, Heading (`[One Stop]` in red), Subheading, Body Text, Button Intro, Button.
- The section's `id` is the Anchor ID.

**Checked:**
- **Markup:** the same 34 tokens as the design's section; only the poster URL differs (it comes from the CMS).
- **Video:** not loaded at the top of the page. When the section is scrolled near, `site.js` adds the muted, looping,
  controls-free `youtube-nocookie.com/embed/RSJhcn80hzQ` player. With **reduced motion**, no video is loaded and the
  poster stays.
- **Links:** **Contact us** opens the contact page.
- **Layout:** white text on the dark overlay on desktop and mobile.
- **No regressions:** no JavaScript errors or failed requests.

---

## Phase 9: Homepage: Sustainable Printing CTA (section J)

Section J of the design (`index.html`, "SUSTAINABLE PRINTING CTA"): a maroon band with centred white content (leaf
illustration, heading, one sentence, white button). Its id is `sustainable`, so the third Benefit card ("Sustainable →
Water-based ink options", `#sustainable`) now jumps here.

### Fields

None new. Reused: Heading (`sectionHeading`), Intro (`sectionLead`), Button (`ctaLink`), Anchor ID (`anchorId`).

### Entry type (built in the CP)

**CTA Band** (`ctaBand`): title off, Title Format `J · Sustainable printing CTA`, slug off.
- **Content tab:** Heading (required), Intro, Button
- **Settings tab:** Anchor ID

Added to **Homepage Sections**.

### Content (entered in the CP)

| Field | Value |
|---|---|
| Heading | Looking for a more Sustainable, Cost-effective Label Printing Solution? |
| Intro | Choose ArrowJet with water-based pigment inks for vibrant, eco-friendly labels that reduce setup costs and boost profitability |
| Button | Connect with our team now → `https://arrsys.com/contact-us/` |
| Anchor ID | sustainable |

Placed after One Stop Solution (homepage order: Hero → Benefit Cards → On-Demand → Worldwide Locations → One Stop →
Sustainable CTA).

### Template

`templates/_blocks/home/cta-band.twig`: the design's section J markup.
- `<section class="sustain">` → `.sustain__inner`.
- The design's **inline leaf SVG** (a fixed design element, so it's in the template, not a field).
- `<h2>` (Heading, `[brackets]` supported), `<p>` (Intro), and the white `btn btn--light btn--lg` button.

**Checked:**
- **Markup:** the same 22 tokens as the design's section. The only difference is the heading's internal id
  (`sustainable-title` instead of `sustain-title`), which is generated from the Anchor ID.
- **Links:** **Connect with our team now** opens the contact page, and the Benefit card "Sustainable" scrolls to the band.
- **Layout:** maroon band with white icon, heading, text and button on desktop and mobile.
- **No regressions:** no JavaScript errors or failed requests.

**Client note (from the design file):** keep evidence on file for the "eco-friendly" and cost claims (the copy is the
live site's wording).

---

## Phase 10: Homepage: End-of-Line Marking / Coding (section L)

Section L of the design (`index.html`, "END-OF-LINE MARKING / CODING"), on the grey background:
- **Left:** small label, heading with "Marking and Coding" in red, two paragraphs, and a red text link with an arrow
- **Right:** a photo in a rounded frame with a shadow

Its id is `marking`. The design notes say this section (and M) is **hidden on the live arrsys.com**; confirm with the
client whether it should show. Section K (blog) isn't built yet, so L sits after the Sustainable CTA for now.

### Step 1: Photo

`limitronic-limitag-color-coding.webp` (800 × 584) from the design's `assets/photos/web/`, uploaded to **Assets →
Images**. It's real content, so it has **Alt Text**: "Limitronic LIMITAG Color UV LED printer printing a full-color
mark on a seafood carton".

### Step 2: Field

| Field | Handle | Type | Settings |
|---|---|---|---|
| Image | `mediaImage` | Assets | Images volume · images only · max 1 · "Photo beside the text (about 4:3). Fill in the image’s Alt Text." |

Reused: Small Heading (`eyebrow`), Heading (`sectionHeading`), Body Text (`sectionBody`), Button (`ctaLink`), Anchor ID (`anchorId`).

### Step 3: Entry type

**Text + Image** (`textMedia`): title off, Title Format `L · End-of-line marking`, slug off.
- **Content tab:** Small Heading, Heading (required), Body Text, **Link** (the Button field with only its *label*
  changed to "Link", because it's shown as a text link; no handle override), Image
- **Settings tab:** Anchor ID

Added to **Homepage Sections**.

### Step 4: Content: Entries → Homepage → L · End-of-line marking

| Field | Value |
|---|---|
| Small Heading | End-of-line packaging |
| Heading | Custom [Marking and Coding] Solutions for End-of-Line Packaging |
| Body Text | Two paragraphs separated by an empty line: "Coding and marking solutions … for some time." / "Our approach is … at speeds up to 60 m/min." |
| Link | Corrugated & end-of-line printers → `https://arrsys.com/all-products/corrugate-and-end-of-line-printing/printers-by-limitronic/` |
| Image | `limitronic-limitag-color-coding.webp` |
| Anchor ID | marking |

### Step 5: Template

`templates/_blocks/home/text-media.twig`: the design's section L markup (`.marking`, `.marking__body`,
`figure.marking__media`).
- **Body Text** is split at empty lines, and each block becomes its own `<p>`. The design's CSS makes the first one
  larger and darker.
- The link is shown as `a.text-link` with the arrow icon.
- The photo uses its Alt Text.

**Checked:**
- **Markup:** the same 31 tokens as the design's section (both paragraphs and the alt text); only the photo URL differs.
- **Styling:** paragraph 1 is 18 px dark, paragraph 2 is 16 px grey, as in the design.
- **Layout:** desktop has two equal columns (text left, photo right); mobile stacks the text above the photo.
- **Links:** the link opens the Limitronic printers page.
- **No regressions:** no JavaScript errors or failed requests.

**Section M** now reuses this entry type with a **Layout** option; see Phase 11.

---

## Phase 11: Homepage: Industrial Printer Distributor (section M)

Section M of the design (`index.html`, "INDUSTRIAL PRINTER DISTRIBUTOR"), on a white background:
- **Left:** small label and heading ("Printer Distributor" in red)
- **Right:** one paragraph and the "Our Company →" text link
- no image

Its id is `distributor`. Like L, the design notes say it's **hidden on the live arrsys.com**.

### Same section type as L, with a Layout option

M has the same parts as L, so it **reuses the Text + Image type** instead of a new one:

| Change | Detail |
|---|---|
| New field **Layout** (`textMediaLayout`, Dropdown) | `textImage` "Text + image (photo on the right)" (default) · `headingText` "Heading + text (no image)" |
| Entry type `textMedia` | Renamed **Text + Media** (handle unchanged) · **Layout** added at the top of the Settings tab · **Title Format** is now the heading without brackets, so each block is named after its heading in the CP |
| Section L | Layout set to **Text + image**; its CP title is now "Custom Marking and Coding Solutions for End-of-Line Packaging" |

### Content: Entries → Homepage → Industrial Digital Printer Distributor

| Field | Value |
|---|---|
| Layout (Settings tab) | Heading + text (no image) |
| Small Heading | About Arrow Systems |
| Heading | Industrial Digital [Printer Distributor] |
| Body Text | We continue to develop new materials, … printing and process innovations. |
| Link | Our Company → `https://arrsys.com/our-company/` |
| Anchor ID | distributor |

Placed after section L.

### Template

`templates/_blocks/home/text-media.twig` now has both layouts:
- **`textImage`:** `section--surface` + `.marking` (text + photo), unchanged
- **`headingText`:** a plain `section` + `.distributor` (`.distributor__head` for the label and heading,
  `.distributor__body` for the paragraphs and link)

The heading block and body are shared, so both layouts behave the same.

**Checked:**
- **Markup:** section M has the same 26 tokens as the design (**no differences**); section L still matches its design
  section (only the photo URL differs).
- **Layout:** white background, heading left and text right on desktop, stacked on mobile.
- **Links:** **Our Company** opens the company page.
- **No regressions:** no JavaScript errors or failed requests.

**Homepage order now:** Hero → Benefit Cards → On-Demand → Worldwide Locations → One Stop → Sustainable CTA →
Latest Blog Posts (K, Phase 13) → End-of-Line Marking → Industrial Printer Distributor. Not built yet: C (Equipment Category Showcase), E (Industries),
F (Featured Printing Solutions), G (Innovation / Events), K (Industry Insights / Blog), N (Newsletter).

---

## Phase 12: Newsletter (section N)

Section N of the design (`index.html`, "NEWSLETTER"): a band above the footer with a heading ("Newsletter" in red), a
line of text, an email box + **Subscribe Now** button, a note with a Privacy Policy link, and a status line.

- **The form doesn't send data yet.** The design's script checks the email and says sign-up isn't connected. It will
  be connected to HubSpot once the client sends the portal ID and form GUID.
- **Consent:** the client should confirm whether a consent checkbox is needed for Canadian subscribers.
- **Approach:** a **Global Set** (`newsletter`), not a homepage section, so it's edited once and shown on every page
  by the base layout.

### Step 1: Fields

| Field | Handle | Type | Settings |
|---|---|---|---|
| Show Newsletter | `newsletterEnabled` | Lightswitch | Default **on**; off hides the band on every page |
| Email Placeholder | `emailPlaceholder` | Plain Text | e.g. “Your email” |
| Button Label | `buttonLabel` | Plain Text | e.g. “Subscribe Now” |
| Note Text | `noteText` | Plain Text | e.g. “You can unsubscribe at any time.” |
| Note Link | `noteLink` | Link | URL (default) / Entry, label field on, anchors + root-relative URLs allowed; shown as “See our {label}.” |
| HubSpot Portal ID | `hubspotPortalId` | Plain Text | Pending from the client; empty = form checks the email but doesn't send |
| HubSpot Form GUID | `hubspotFormId` | Plain Text | Pending from the client |

Reused: Heading (`sectionHeading`), Intro (`sectionLead`). Note Text + Note Link replace a rich-text note, because
staging doesn't have the CKEditor plugin.

### Step 2: Global set (built in the CP)

**Newsletter** (`newsletter`), under **Globals → Newsletter**:
- **Content tab:** Show Newsletter, Heading (required), Intro, Email Placeholder, Button Label, Note Text, Note Link
- **HubSpot tab:** HubSpot Portal ID, HubSpot Form GUID

### Step 3: Content (entered in the CP)

| Field | Value |
|---|---|
| Show Newsletter | On |
| Heading | Subscribe To Our [Newsletter] |
| Intro | Get the latest news and updates on upcoming events, market opportunities, and much more. |
| Email Placeholder | Your email |
| Button Label | Subscribe Now |
| Note Text | You can unsubscribe at any time. |
| Note Link | Privacy Policy → `https://arrsys.com/privacy-policy/` |
| HubSpot Portal ID / Form GUID | empty (pending from the client) |

### Step 4: Templates

| File | What it does |
|---|---|
| `templates/_partials/newsletter.twig` | The design's section N markup from `newsletter.*`; shown only when **Show Newsletter** is on. `data-integration` is `hubspot-pending` until both HubSpot fields are filled, then `hubspot` + `data-hubspot-portal` / `data-hubspot-form`. The note is "{Note Text} See our {Note Link label}." |
| `templates/_layouts/base.twig` | Includes the partial at the end of `<main>`, so the band is on every page, above the footer |

The ids `nl-email`, `nl-note`, `nl-status` and `data-newsletter` are kept, because `site.js` uses them.

**Checked:**
- **Markup:** the same 37 tokens as the design's section (**no differences**); it's the last section in `<main>`.
- **Form messages:** empty → "Enter your email address." · `abc` → "Enter a valid email address, like
  name@company.com." · a valid address → "Newsletter signup is not connected yet, so your email address has not been
  sent. In the meantime, email sales@arrsys.com…" (the design's own message).
- **Nothing is sent:** the form makes no POST or other request.
- **Links:** **Privacy Policy** opens the privacy page.
- **On/off:** **Show Newsletter** off removes the band from the page; on brings it back.
- **Layout:** desktop puts the text left and the form right; mobile stacks them.
- **No regressions:** no JavaScript errors or failed requests.

**Before launch:** get the HubSpot portal ID + form GUID from the client and build the HubSpot submission (the
`hubspot` branch of `site.js` / a small HubSpot embed), and confirm whether a consent checkbox is needed for
Canadian subscribers.

---

## Phase 13: Blog migration (live arrsys.com → Craft, with the Neo page builder)

> **Update (Phase 14):** the page builder below was moved from Neo to Craft's Matrix field the same day, with the same
> blocks; Neo, `config/neo.php` and the Lucide icons were removed. Where this phase says "Neo", read
> [Phase 14](#phase-14-blog-page-builder-moved-from-neo-to-matrix-no-plugin) for how it works now.

The live blog (WordPress + Elementor) moved into Craft: the posts, their categories and tags, the `/blog/` listing,
with **the same URLs**, the live SEO and the **new design** (`Arrowsystem_local/blogs/index.html` and
`blog-detail.html`). Post content is edited with **Neo** blocks. Plan and research: `BLOG_MIGRATION_PLAN.md`.

**Decisions (2026-10-13):** D1 CKEditor ✅ · D2 Neo ✅ (license before launch) · D3 **tags migrated**, not dropped ·
D4 the per-post Elementor contact form is replaced by the design's closing CTA box + the sidebar help box ·
D5 automatic import for all posts · D6 author **admin** · D7 both "Food Packaging" categories kept.

Everything was built by scripts in `storage/imports/blog/` (re-runnable, same idea as the practice project's
`storage/imports/arrow/`). Run them from the project root with `/opt/lampp/bin/php`:

| Script | What it does |
|---|---|
| `01_structure.php` | Fields, entry types, sections, the Neo field and its block types (source of truth for the blocks) |
| `02_blog_page.php` | Content of the Blog Page single (fills empty fields only) |
| `03_taxonomy.php` | The 45 live categories (same slugs and parents) and 245 tags |
| `04_pilot.php` | The pilot post, built from the design's semantic blocks (`--force` rebuilds it) |
| `05_import.php` | Imports the live posts (`--sample` = 10-post trial, `--slugs=a,b`, `--limit=N`, `--update` rebuilds existing posts except the pilot) and writes `report.md` |
| `06_homepage.php` | Homepage section K (latest posts) |
| `07_editor_tweaks.php` | Editor wording fixes (table instructions, Title hint) |
| `08_neo_to_matrix.php` | One-off (Phase 14): moved the blocks from Neo to Matrix and switched the field |
| `verify.py` | URL, archive and SEO parity checks (`python3 storage/imports/blog/verify.py`) |
| `_helpers.php`, `_converter.php` | Shared helpers; the Elementor → Neo converter |
| `data/` | The live export used by the scripts: REST posts / categories / tags / media, SEO from each post's `<head>`, the sitemaps, the live redirects |

### Step 1: Groundwork

| Item | Value |
|---|---|
| Plugins | **CKEditor 5.9.0** (free) and **Neo 5.5.13** (trial; $49 + $19/year on the live domain) |
| `config/general.php` | `addTrailingSlashesToUrls()` (URLs end with `/`, as live) and `pageTrigger('page/')` (`/blog/page/2/`, as live) |
| `config/neo.php` | Grid "Add block" menu with icons from `web/assets/icons/lucide/` (copied from the practice project) |
| `modules/builderui` | Search box + bigger tiles in Neo's "Add block" menu (copied from the practice project; registered in `config/app.php`, autoloaded via `composer.json`) |
| Time zone | **America/New_York** (was Los Angeles), so post dates and times match the live site |
| `config/redirects.php` | 301s: `/author/admin/` → `/blog/`; the 9 old post URLs the live site redirects; the three "…-2025" URLs → "…-2026" |

### Step 2: Sections and entry types

| Section | Type | URL | Template | Entry type: fields |
|---|---|---|---|---|
| **Blog** (`blog`) | Channel | `/{slug}/` (root, as live) | `blog/_entry` | **Blog Post**: Title, Slug, Post Date · Lead · Cover Image · **Article Body** (Neo) · *Settings:* Categories, Tags, More Insights · *SEO* |
| **Blog Page** (`blogPage`) | Single | `/blog/` (+ `/blog/page/N/`) | `blog/_index` | **Blog Page**: Small Heading, Heading, Intro · *Sidebar:* Help Box Title / Text / First Button / Second Button · *Default CTA:* CTA Heading, CTA Text, CTA Button · *SEO* |
| **Blog Categories** (`blogCategories`) | Structure, 3 levels | `/category/{parent}/{slug}/` | `blog/_category` | **Blog Category**: Title, Slug, Description · *SEO* |
| **Blog Tags** (`blogTags`) | Channel | `/tag/{slug}/` (noindex, as live) | `blog/_tag` | **Blog Tag**: Title, Slug |

**SEO tab** (posts, blog page, categories): SEO Title (`seoTitle`, empty = "{title} - {site name}"), Meta Description
(`seoDescription`), Social Image (`socialImage`, empty = cover), Hide from Search Engines (`seoNoIndex`).

### Step 3: The Neo field "Article Body" (`articleBody`)

| Group | Blocks (handle) | Fields |
|---|---|---|
| Text | Heading (`heading`) | Heading, Level (H2/H3/H4), Answer Lead, Show in "On this page", Contents Label, Anchor ID |
| | Rich Text (`richText`) | Text (CKEditor: H3/H4, bold, italic, links, lists, tables, quote, source for admins) |
| | Intro (`intro`), Callout (`callout`) | Text (CKEditor: bold, italic, links) · Callout: Style = Callout / Note |
| | Quote (`quote`) | Quote, Source |
| Lists | Key Takeaways (`takeaways`), Checklist (`checklist`) | Heading + Items (table) · Items |
| | Steps (`steps`) → Step (`step`) | Step: Title, Text |
| | Spec List (`specList`) | Terms (table: Term, Description) |
| | Comparison (`compare`) → 2 × Comparison Column (`compareColumn`) | Heading, Items, Column Style (Pros / Cons) |
| Media | Image (`image`), Gallery (`gallery`), Video (`video`), Data Table (`dataTable`) | Image + Caption + Width · Images + Columns · YouTube link + Caption · Caption + Table (CKEditor) |
| Products & CTA | Product List (`productList`) → Product (`productItem`) | Tag Line, Name, Link, Description |
| | Button (`button`), Call to Action (`cta`) | Link + Button Style · Heading, Text, Button |
| Q&A | FAQ (`faq`) → FAQ Item (`faqItem`) | Question, Answer (CKEditor) |
| Other | Divider (`divider`), HTML (`html`) | — · HTML Code (**admins only**: the block type uses Neo permissions) |

Automatic (no fields): **"On this page"** (from the H2 Heading and Key Takeaways blocks), **reading time** (words ÷ 225),
breadcrumb, schema, and the closing CTA (the post's own Call to Action block, else Blog Page → Default CTA).

### Step 4: Templates and assets

| File | What it does |
|---|---|
| `web/assets/css/blog.css`, `blog-detail.css`, `web/assets/js/blog.js`, `blog-detail.js` | The design's files, unchanged |
| `web/assets/css/craft.css` | + styles for things the design has no CSS for: H4, numbered lists, quote, image widths, gallery, video, button, divider. Also a fix for Button blocks: the design's article link colour (`.prose a`, maroon) overrode the red button's white text (maroon on maroon); `.prose .btn--primary` / `.btn--outline` get their own colours back (the design only did this for `.btn--light`). Checked on all 141 pages with buttons: 359 buttons, all ≥ 4.5:1 contrast |
| `templates/_layouts/base.twig` | + `{% block seo %}` (head) and `{% block beforeMain %}` (the post's reading-progress bar). Empty by default: the homepage output is unchanged |
| `templates/_partials/seo.twig` | Description, robots (live default or `follow, noindex`), canonical, prev/next, Open Graph, Twitter, JSON-LD |
| `templates/blog/_index.twig`, `_category.twig`, `_tag.twig` | Listing pages, 10 per page; `_partials/listing.twig` (header, filter, cards, pagination, sidebar), `category-filter.twig`, `pagination.twig`, `side-cta.twig`, `listing-head.twig` |
| `templates/blog/_entry.twig` | The post page; `blog/blocks/{handle}.twig` renders each block; `_partials/post-cta.twig` the closing box; `_macros/blog.twig` turns CKEditor tables into the design's `.table-wrap` / `.data-table` |
| `templates/_components/post-card.twig` | The blog card (listing, More Insights) |
| `templates/_blocks/home/latest-posts.twig` | Homepage section K (the homepage's own card style, as in the design) |

### Step 5: Pilot post and import

- **Pilot:** "Roll-to-Roll vs. Sheet-Fed Label Printing" built with the design's blocks. Compared with
  `blog-detail.html` token by token: header and rail **identical**; article 915 = 915 tokens, the only differences are
  generated IDs (table captions, CTA) and the table footer row's position in the markup (shown at the bottom all
  the same; its green "best value" cell highlight is not kept).
- **Import:** **343 posts** imported (352 published on live, minus the 9 that redirect there) + the pilot = 344 Blog entries,
  0 failures, about 33 minutes. Report per post: `storage/imports/blog/report-full-run.md` (full run) and `report.md`
  (the re-run of 70 posts after the image fixes below). Headings and images match live per post (the only headings
  left out are the repeated title / live H1 and the form's "Contact With Our Team Now").
  - **Covers:** the featured image; posts without one on live use the post's top banner, else the live `og:image`.
    **14 old posts have no image at all on live**, so they have no cover (the card shows a grey box): add one in the CP.
  - **3 images** don't exist on the live site either (404) and are missing: `10-2.png`, a "Close-up-of-a-compliant-cosmetic-label…"
    image and a Google-hosted image.
  - Notes to look at in the report: 35 links on images and 19 links on headings were dropped (Neo images / headings have
    no link), 4 images inside lists were dropped, 1 HTML block (GHS guide) kept as HTML.
- **Converter rules** (`_converter.php`): Elementor heading → Heading; text editor → Rich Text (headings inside the text
  → Heading blocks, `<dl>` → Spec List, images → Image, YouTube iframes → Video, tables stay in the Rich Text);
  image → Image (width from the live size); video → Video; accordion → FAQ; galleries / carousels → Gallery;
  button → Button; divider → Divider; html → HTML. Left out: sidebar columns (Recent / Trending posts), the Table of
  Contents widget (automatic now), the contact form and its heading (D4), the top banner (it's the cover image) and the
  repeated title / live H1.
- **Images:** downloaded once into **Assets → Images → blog/** with their alt text; none load from the live site.
- **SEO:** Lead + Meta Description = the live meta description; SEO Title only where the live title isn't the default
  "{title} - Arrow Systems, Inc."; canonical = the post URL.
- **Links** inside posts to other posts, categories, tags and `/blog/` are root-relative (they work on staging and
  live); links to pages not migrated yet (products, industries…) still point to arrsys.com.

### Step 6: Connected

- Main menu and footer **Guides & Articles** → the Blog Page (`/blog/`), via Navigation (linked to the entry).
- Homepage **K · Latest blog posts** (after J): Small Heading, Heading, **Posts to Show**, View All Link, Anchor ID.
  **Posts to Show** (added later the same day; reuses the Entries field `relatedPosts`, Blog posts, max 3): pick up to 3
  posts and drag them into order. Empty = the newest 3 automatically; 1 or 2 picked = the newest posts fill the rest.
  Checked: empty → identical homepage to before; 1 picked → it comes first + the 2 newest; 3 picked → exactly those,
  in the chosen order (test values removed again, so it's empty now). Backup before: `…2026-10-13-133709…`.

### Checks

All run on 2026-10-13 (`verify.py` + headless Chrome):
- **URLs:** all 352 live post URLs → 343 × **200**, 9 × **301** to the same target as live; all **346 sitemap URLs** OK;
  `/blog/` + `/blog/page/2/` … `/blog/page/35/` → 200, `/blog/page/36/` → 404; **45 categories** and **245 tags** → 200
  (tags noindex); `/author/admin/` → 301 `/blog/`.
- **SEO parity with live: 0 differences** in title, meta description, robots, canonical and og:image over all 343 posts
  (with the site name normalised).
- **Design:** pilot post header, rail and article match `blog-detail.html` token by token (see Step 5); `/blog/` header,
  first card, sidebar and pagination match `index.html` (only the posts/numbers differ); homepage K matches `index.html` K.
- **Behaviour:** "On this page" links all land, scroll-spy highlights the section, the progress bar moves, the FAQ opens,
  the category filter opens and searches, "New" chips show. No JavaScript errors, no failed requests, no broken images,
  **no images loaded from arrsys.com**, no sideways scroll on desktop or phone.
- **Editor (CP):** the post edits with Neo blocks; "Add block" shows the grouped icon grid with the search box; Step /
  Product / FAQ Item / Comparison Column only appear inside their parent block.
- **Homepage:** unchanged except the new section K (whole-page token comparison); renders in ~0.3 s like the blog pages.

### Notes for later

- **Live canonical loop:** on the live site, three "…-2026" posts have a canonical pointing to "…-2025", which 301s back
  to "…-2026". Staging uses each post's own URL as canonical (fixes it) and keeps the 2025 → 2026 redirects.
- The 9 posts that redirect on live are not imported (they're published in WordPress but nobody can open them).
- The blog search box still searches the live site (as in the design) until the new site has search.
- The sidebar "Product Categories" links go to the live product category pages until those are migrated.
- `/blog/` meta description is the design's text (the live one is "Blog Page 1"). Category descriptions follow live
  ("{name} Category") until filled in.
- On staging, titles end with "Arrow Systems (Local Staging)" (the site name); on live the site name should be
  "Arrow Systems, Inc.", which gives the same titles as today.
- Image transforms: XAMPP's GD can't write WebP, so the card / cover crops fall back to the original files on staging
  (CSS crops them). The live server should have WebP support (or Imagick) for real thumbnails.
- Tags aren't shown on the post page (the design has no tag list); the tag pages work at their old URLs (noindex).

---

## Phase 14: Blog page builder moved from Neo to Matrix (no plugin)

The blog's **Article Body** page builder was a Neo field (Phase 13). It's now Craft's own **Matrix** field plus a little
custom code, with the same blocks, the same content and the same front end, so the site no longer needs the Neo plugin
(no $49 + $19/year license). Decided 2026-10-13: "Matrix + custom code" rather than writing our own Neo clone.

### How the builder works now

| Neo (before) | Matrix (now) |
|---|---|
| Neo field `articleBody` with 24 block types | Matrix field `articleBody` (**Blocks** view, button "Add block"); each block is an **entry type** with the same handle (`heading`, `richText`, `faq`…) and the same fields |
| Block type groups Text · Lists · Media · Products & CTA · Q&A · Other | The same groups (entry type groups in the Matrix field settings) |
| Child blocks (Steps → Step, Comparison → 2 Columns, Product List → Product, FAQ → FAQ Item) | The parent block has a **nested Matrix field**: Steps → **Steps** (`stepItems`), Comparison → **Columns** (`compareColumns`, exactly 2), Product List → **Products** (`productItems`), FAQ → **Questions** (`faqItems`) |
| Lucide icons (`config/neo.php`) | Font Awesome icons chosen on each entry type (Settings → Entry Types) |
| Grid "Add block" menu + search (`modules/builderui`) | Craft's menu already has the search box and the groups; `modules/builderui` (rewritten) shows it as the same 4-column icon grid, for the Article Body field only |
| Collapse, drag, copy / paste, enable / disable | The same, built into Matrix Blocks view (⋯ menu on each block) |
| HTML block: Neo block-type permission (admins) | HTML block: its **HTML Code** field only shows for admins (field layout user condition) |
| Block names in the editor | The block header shows the icon + block name; headings, questions, products and CTAs also get a title from their text (title format), e.g. "FAQ Item: Is sheet-fed ever cheaper…" |

**Differences from Neo (agreed):** a child block can't be dragged into a *different* parent (copy and paste it instead);
child blocks show inside their parent block rather than as one indented tree; the HTML block's admin-only rule is
the field condition above.

### How the content was moved

| Step | What happened |
|---|---|
| 1 | Backup `…2026-10-13-114145…`; the queue run empty (Neo jobs) before the switch; the HTML of all 343 post pages (+ blog, category, tag pages and the homepage) saved as a "before" snapshot |
| 2 | `01_structure.php` (rewritten) created the 24 entry types, the 4 nested fields and the Matrix field (first as `articleBlocks`, next to Neo) |
| 3 | `08_neo_to_matrix.php` copied every post's Neo blocks into Matrix: same type, field values, order, enabled and collapsed state; child blocks into the nested fields. Also the 2 unsaved provisional drafts (one has an extra empty FAQ item someone was adding in the CP; it's kept) |
| 4 | A one-off check compared every block's type, order, enabled state and field values, Neo vs Matrix: **343 posts, 9,895 blocks, 0 differences**; the 2 drafts have the same number of blocks too |
| 5 | `08_neo_to_matrix.php --switch`: Neo field removed from the Blog Post layout and deleted; Matrix field renamed `articleBlocks` → `articleBody` |
| 6 | Templates: child blocks read from the nested fields (`blocks/steps.twig` → `block.stepItems`, `compare` → `compareColumns`, `productList` → `productItems`, `faq` → `faqItems`); `_entry.twig` eager-loads them and counts their words |
| 7 | Neo uninstalled (`plugin/uninstall neo`, `composer remove spicyweb/craft-neo`); `config/neo.php` and `web/assets/icons/lucide/` removed |
| 8 | The import scripts now write Matrix data (`builderValue()` in `_helpers.php`), so `04_pilot.php` / `05_import.php` still work |

### Checks

- **Front end unchanged:** all 348 snapshot pages (343 posts + `/blog/`, `/blog/page/2/`, a category, a tag, the
  homepage) compared with the "before" snapshot: **347 identical**; the pilot post differs only in the generated
  table-caption IDs (`table-<block id>-caption`, the blocks have new IDs).
- **Design:** the pilot still matches `blog-detail.html` exactly as before (header 0, rail 0, article only the known IDs /
  table-footer position).
- **`verify.py`:** all URLs, archives, categories, tags, redirects OK; **SEO parity 0 differences**.
- **Browser:** "On this page" links, scroll-spy, FAQ, progress bar; no JavaScript errors, broken images or sideways scroll.
- **Editor (CP, real clicks):** "Add block" opens the icon grid with the cursor in the search box; typing "faq" leaves
  only FAQ; adding FAQ adds its first question automatically (minimum 1); the post saves and the FAQ shows on the site
  in the design's markup. (Done on a test post, deleted afterwards.)
- **HTML block:** the HTML Code field is shown to `admin`, hidden for `editor_one`.
- **Scripts:** `04_pilot.php --force` and `05_import.php --update` work with Matrix (pilot and two posts rebuilt; output
  identical to before).
- **Neo gone:** plugin uninstalled and removed from `composer.json`; project config has no pending changes.

---

## Phase 15: Cleanup after the Neo → Matrix move

Backup before: `…2026-10-14-055848…`. Steps 1–5 of the cleanup list are done; still open: the unsaved test draft on
the Pharmaceutical post, caches, logs and old backups (the owner's call).

### Step 1: Neo's leftover data (database)

Neo was uninstalled in Phase 14, but its rows stayed in Craft's own tables. Removed with a one-off script, in one
transaction, touching only Neo's rows and the Matrix blocks replaced on 2026-10-13:

| Removed | Count |
|---|---|
| Neo block elements (in the trash, plus 2 orphans from an unsaved draft) | 24,468 → 0 |
| Neo field layouts + the deleted Neo field | 24 + 1 |
| Neo's block structures (every structure not used by Blog Categories or the 4 Navigation menus) | 1,129 |
| Matrix blocks replaced when the pilot was rebuilt / 2 posts re-imported / the test post deleted (trash) | 323 |
| Search index rows of elements that no longer exist | 2 |

**Kept on purpose:** the rest of Craft's trash (1 image from the hero slider work, `arrowjet-mts-48_…_towd.webp`),
so `craft gc --delete-all-trashed` was **not** used.

### Step 2: Old blog revisions

Revisions saved before the Matrix switch had their article in the deleted Neo field: restoring one would have brought
the post back with an empty Article Body. Checked first that every post's newest revision has the same blocks as the
post (343 / 343), then pruned:

```bash
php craft utils/prune-revisions --section=blog --max-revisions=1
```

447 older revisions deleted; each post keeps 1 revision (= its current content).

### Step 3: Stale code

- `modules/builderui/resources/cp.js`: no longer looks for `#fields-articleBlocks-field` (the temporary handle used
  during the move).
- `storage/imports/blog/01_structure.php`: removed the "create the Matrix field as articleBlocks while Neo owns
  articleBody" branch; the builder is always `articleBody`.
- The only remaining mention of `articleBlocks` is `08_neo_to_matrix.php`, the record of the one-off move.

### Step 4: Out-of-date "Neo" comments

Reworded to describe the Matrix builder: `config/app.php` (the builderui module comment), `web/assets/css/craft.css`
(blog block styles header), `modules/builderui/resources/cp.css` (header) and `01_structure.php` (builder section).
Kept on purpose: the `01_structure.php` file header note "Until 2026-10-13 this was a Neo field; see
08_neo_to_matrix.php" (accurate history), and `08_neo_to_matrix.php` itself (the record of the move).

### Step 5: Unused code in the import scripts

| File | Removed | Why |
|---|---|---|
| `_bootstrap.php` | `globalSet()` + its `use craft\elements\GlobalSet;` | Copied from the practice project; no blog script creates a global set |
| `_bootstrap.php` | `iconOptions()` | Copied from the practice project; never called |
| `_helpers.php` | `rowsOf()` (and its line in the header) | Never called |
| `_converter.php` | `use craft\helpers\StringHelper;` | Not used |

Checked: every script passes `php -l`; `04_pilot.php` runs (no-op, the pilot exists); the helpers + converter load and
convert a live post in memory; the site, `/admin` and the "Add block" grid (20 tiles, search focused) still work.

### Checks

- `verify.py`: all 352 live post URLs OK (343 × 200, 9 × 301 as live), all 346 sitemap URLs, `/blog/` pages 1–35 (36 →
  404), 45 categories, 245 tags (noindex), `/author/admin/` → 301; **SEO parity 0 differences**.
- Browser: `/blog/`, two posts, a category page and the homepage: no JavaScript errors, no failed requests, no broken
  images; "On this page", FAQ and the category filter work.
- CP: a post opens with all its blocks; "Add block" still opens the 20-tile icon grid with the search box focused; no
  draft created.
- Queue empty, no failed jobs. Database backup after the cleanup: `arrow-systems-local-staging--2026-10-14-060655--v5.11.4.sql` (the database backup shrank from 46.7 MB to 22.6 MB).

---

## Phase 16: Exhibitions (CRUD, listing, upcoming, detail pages, homepage section G)

The live site builds every trade show as a separate WordPress page. In Craft each show is now **one entry for its
whole life**: announced with dates / location / booth / logo, later given photos and its own page. Plan, research and
decisions: `EXHIBITIONS_MIGRATION_PLAN.md` (D1 build `/upcoming-exhibitions/` · D2, D3, D5 **as live** · D4
`ExhibitionEvent` schema · D6 automatic + manual homepage selection · D7 card photos = the design's only, no fallback).
Scripts: `storage/imports/exhibitions/` (`01_structure.php`, `02_content.php [--update]`, `verify.py`, `data/`).
Backups before: `…2026-10-14-063619…` (structure), `…064525…` (content), `…065535…` (blog slug move),
`…065957…` (canonical field).

### Structure

| Item | Value |
|---|---|
| **Exhibitions** (`exhibitions`) | **Structure** (1 level, manual order; starts in the live listing order), URI `{% if object.hasPage %}{{ object.slug }}{% endif %}`, template `exhibitions/_entry`. A show without a page has no URL |
| **Exhibition** entry type | *Content:* Show Name, Start Date (required), End Date, Show Dates As (Exact / Year only), Location (`city`), Booth, Tech Spotlight, Show Logo, Card Photo (`featuredImage`) · *Page:* Has a Page, Subtitle (`subheading`), Intro (`sectionLead`), Photos (`exhibitionPhotos`) · *SEO:* SEO Title, Meta Description, Social Image, Hide from Search Engines, **Canonical URL** (`seoCanonical`) |
| **Exhibitions Page** (single, `/exhibitions/`) | Small Heading, Heading, Intro, Header Photos (large + small), Large Photo Caption, Contact Link, SEO |
| **Upcoming Exhibitions Page** (single, `/upcoming-exhibitions/`) | Small Heading, Heading, Intro, Schedule a Meeting Link · *Bottom Band:* heading, text, two buttons · SEO |
| Homepage block **Exhibitions** (`exhibitionHighlights`) | Small Heading, Heading (`[red]`, " \| " = line break), Intro, **Shows to Display** (`selectedExhibitions`, max 3), All Exhibitions Link, Anchor ID. Placed after D |
| New fields | `startDate`, `endDate`, `datePrecision`, `boothNumber`, `techSpotlight`, `showLogo`, `exhibitionPhotos` (uploads to Images → exhibitions/), `hasPage`, `selectedExhibitions`, `seoCanonical` |

**Automatic (no fields):** upcoming vs past (from the dates: a show with exact dates that hasn't ended is upcoming),
the next show (ticket, hero stat, detail band), the year groups + filter (5 latest years, then one "earlier" group),
countdowns, the upcoming page's calendar and schedule (shows with exact dates in this year), previous/next show,
"N photos", ExhibitionEvent schema.

### Templates and assets

| File | What it does |
|---|---|
| `web/assets/css/exhibitions/{exhibitions,upcoming,detail}.css`, `web/assets/js/exhibitions/{exhibitions,upcoming,detail}.js` | The design's files, unchanged |
| `templates/exhibitions/_index.twig` | `/exhibitions/`: header + stats + photos, next-show ticket, year timeline |
| `templates/exhibitions/_upcoming.twig` | `/upcoming-exhibitions/`: 12-month calendar, numbered schedule, bottom band |
| `templates/exhibitions/_entry.twig` | Show page: header + facts + photos, gallery + lightbox, More Exhibitions, next-show band; canonical = Canonical URL or the page |
| `templates/_components/exhibition-card.twig` | Timeline card and homepage card |
| `templates/_blocks/home/exhibition-highlights.twig` | Homepage section G (picked shows first, then automatic) |
| `templates/_macros/exhibitions.twig` | Date ranges ("Sep 16–19, 2025" / "October 18–21, 2026" / year only), day ranges, title with the year in red |
| `templates/_layouts/base.twig` | + empty `{% block afterFooter %}` (the lightbox `<dialog>` sits after the footer, as in the design) |
| `templates/_partials/icon-sprite.twig` | + `i-expand`, `i-reg` (used by the exhibition pages) |
| `web/assets/css/craft.css` | + the world map behind section G (`site.css` points to the design folder's path) |

### Content

- **28 live shows**, in the live listing order: title = the live listing card name (as live, e.g. "GALLERY Chem Show
  2017", "GraphicsEXPO 2019", "Vitrum 2013 Milan Italy"), live slug, **year only** (D3), location where the live page
  states it, subtitle + intro + **all 268 photos** (Elementor gallery and FooGallery; count per show = live), Card
  Photo from the design (D7: shows without a design photo have none until one is picked), SEO title / description /
  canonical exactly as live (D5: Label Expo 2026's title "- Arrow Systems, Inc."; Drupa 2024 and Printing United 2022
  canonical → `/exhibitions/`). Label Expo 2026 uses the design's 16 photos and alt texts (the pilot).
- **4 upcoming 2026 shows** without pages (R2R, LOUPE, PRINTING United, PACK EXPO International) with exact dates,
  location, booth, logo (D2: LOUPE stays separate from Label Expo 2026).
- **Homepage section G** shows the design's 3 shows (LabelExpo 2025, Global Pouch Forum 2025, PackExpo 2024); clear
  "Shows to Display" for the automatic choice.
- **Logo fallback (added later the same day, on request):** an **upcoming** show without a Card Photo shows its **Show
  Logo** on its card instead, whole on white (`.event-card__media--logo` in `craft.css`; `_components/exhibition-card.twig`).
  Past shows still use only the Card Photo (D7). The Card Photo instructions mention it.
- **Menus:** main menu and footer "Exhibitions" → the Exhibitions Page.
- **URL clash fixed as live:** WordPress had a page **and** a blog post at `/labelexpo-brussels-2023/`; live shows the
  page. The exhibition took the URL; the blog post moved to `/labelexpo-brussels-2023-article/` (it could not be
  reached on live; change or disable it if wanted).

### Checks

- `verify.py`: `/exhibitions/`, all 28 show URLs and `/upcoming-exhibitions/` → 200; **SEO parity 0 differences**;
  photos 28/28 shows identical to live (268 photos).
- Token comparison with the design: detail page (header 119, gallery 205, More Exhibitions 59, next band 37, lightbox 39
  tokens) **identical**; listing header and ticket identical; timeline identical except the 3 live show names; upcoming
  page identical except the row ids (`show-<slug>`, the JS pairs them with the calendar dots); band identical; homepage
  G identical except dates shown as the year (D3) and "PackExpo 2024" (live name).
- Browser: countdown ("4 Days to go"), year filter, upcoming statuses (3 "Took place", PACK EXPO "Next show"),
  calendar dots, lightbox (open / next / close), homepage card links; no JavaScript errors, failed requests, broken
  images or sideways scroll (desktop + phone); no images from arrsys.com.
- CRUD lifecycle (test show, deleted afterwards): announced without a page → becomes the next-show ticket and joins
  the schedule, no URL; "Has a Page" on → gets `/test-expo-2026/`; deleted → everything as before.
- CP: Exhibitions list (32 shows, drag to reorder), edit form with Content / Page / SEO tabs.
- Blog unaffected: blog `verify.py` re-run: all 352 post URLs, 346 sitemap URLs, listing pages, categories, tags and
  redirects OK, SEO parity 0 differences.

---

## Phase 17: Product categories (from WooCommerce) + homepage section C

First step towards dynamic products (industries will pick real products later, and products need their categories
first). Category **pages** come later. Script: `storage/imports/product-categories/01_categories.php [--update]`
(data: `data/product_cat.json` = the live WooCommerce REST export). Backup before: `…2026-10-14-093752…`.

### Structure

| Item | Value |
|---|---|
| **Product Categories** (`productCategories`) | **Structure**, 2 levels, **no URLs yet** (category pages later; whether they use WooCommerce's `/product-category/…` or the live Elementor pages' `/all-products/…` URLs is decided then, see `PRODUCT_CATEGORIES_MIGRATION_PLAN.md`) |
| **Product Category** entry type | Category Name (title), Slug (= WooCommerce slug), parent · Card Title (`headingText`: optional shorter name for cards) · Card Photo (`featuredImage`) · Description (`sectionLead`) · Card Link (`ctaLink`: where the card links to until category pages exist) |
| Homepage block **Equipment Categories** (`categoryShowcase`) | Small Heading, Heading (`[red]`), **Categories to Display** (`selectedCategories`, Entries → Product Categories, max 6, drag to order; empty = the first 3), View All Link, Anchor ID. Placed after B (section C) |
| Template | `templates/_blocks/home/category-showcase.twig` (the design's section C markup); card link = the category page once it exists, else Card Link |

### Content

- **13 WooCommerce categories, as live:** Anytron, Digital label Printers, Digital Printing (→ Membrane Touch Switch),
  Gerber Printer, Label Finishing Equipments, Labels and Packaging, Plotters, Print and Cut Systems, Slitters, Thermal
  Transfer Printer, Uncategorized, Variable Data Printing; same names, slugs and parent; A–Z. (No descriptions or
  images on live.)
- **The design's 3 cards:** Digital label Printers → "Digital Label Printers", Label Finishing Equipments → "Digital
  Label Finishers", Print and Cut Systems → "Print & Cut Inline", with the design's photos (Images → categories/) and
  the live `/all-products/…` pages the design links to as Card Links.
- **Homepage section C** (after B): "Equipment" / "Arrow [Label & Packaging] Printers", those 3 cards, "View all label
  printers".

### Checks

- Section C vs the design: **80 = 80 tokens, 0 differences**; the rest of the homepage unchanged.
- Browser: 3 cards with photos and links, desktop + phone, no sideways scroll, no JavaScript errors, no failed requests.
- Picker: 2 picked → exactly those in that order; none → the first 3 categories; restored to the design's 3.

---

## Phase 18: Products (product pages from the live site, design product page)

All live product pages (`/product/{slug}/`, same URLs) as Craft entries, in the design's product page
(`Arrowsystem_local/digital-label-printers/product-detail.html`). Built on the practice project's product work (the
live data, the brands, the Elementor conversion approach), mapped to the design's fields. Scripts in
`storage/imports/products/`: `01_structure.php`, `02_products.php [--update] [--only=slug] [--dry]`, `03_menus.php`,
`verify.py`; `_extract.php` reads a cached live page. Data: `data/store-products.json` (WooCommerce Store API, 113),
`data/practice-brands.json`, `data/design-cards.json` (the design listing's 12 cards), `cache/` (the live pages,
fetched 2026-10-14). Report: `report.md`. Backups before: `…2026-10-14-100954…` (structure), `…101938…` (content),
`…102822…` (re-import with fixes), `…102914…` (menus).

### Structure

| Item | Value |
|---|---|
| **Products** (`products`) | Channel, URL `product/{slug}` (as live), template `products/_entry` |
| **Product** entry type, tab **Product** | Product Name (title), Slug · Small Heading (`eyebrow`, empty = the main category) · **Categories** (`productCategories`, Entries → Product Categories; the first = main category) · **Brand** (Dropdown: Arrow Systems, 3M, Anytron, Gerber, Limitronic, Trojan Label, Matan, QuickLabel) · SKU · **Main Image** (`mediaImage`) · **Headline** (`subheading`) · **Intro** (`richText`) · **Highlights** (`listItems`) |
| tab **Tabs** (since Phase 19: inside the Page Builder's Tabs block) | **Tabs** (`productTabs`, Matrix, Blocks view): **Text Tab** (Tab Title + rich text), **Specifications Tab** (Tab Title + label / value rows, `specItems`), **Downloads Tab** (Tab Title + **Downloads** (`downloadItems`): Label, File (Documents volume), Note e.g. "English" / "Español"). Any number, any titles, drag to order (live pages use Overview / Features / Brochure, but also Facestock, Adhesive, Data Sheets, FAQ…) |
| tab **Video** (since Phase 19: the Page Builder's Video block) | Video URL (YouTube), Poster (empty = YouTube's still), Caption |
| tab **Card** | Card Photo (`featuredImage`, 4:3; empty = Main Image) · Card Tagline · **Key Specs** (Table Label / Value) · **Related Products** (max 3; empty = other products in the main category) |
| tab **SEO** | SEO Title, Meta Description, Social Image, Hide from Search Engines, Canonical URL, **Robots (advanced)** (`seoRobots`: an exact live robots value, e.g. `noindex, nofollow, …`) |
| **Documents** volume | fs Uploads, `web/uploads/documents/`: brochures, data sheets |

### Templates and assets

- `templates/products/_entry.twig`: the design's 4 parts: header (breadcrumb Home › Products › main category ›
  product; image + Request A Sample form | headline, intro, highlights, Request a Quote / Request A Sample, Download
  Brochure (opens the first Downloads tab) + Talk to a Specialist, video); Tabs (left out when empty; ids from the tab
  titles); More products (the 3 related); the maroon help band (phone + quote link from Site Settings). Non-printer
  categories say "product" where the design says "printer".
- `templates/_components/product-card.twig`: the design's `pcard` (photo, name, tagline, key specs, Learn More).
- SEO: `og:type product`, `og:video` when there is a video, JSON-LD Product + BreadcrumbList (+ VideoObject).
  `_partials/seo.twig` got an optional `robots` value. Not copied from live: the hand-written Product schema with an
  `aggregateRating` (4.8 / 18 reviews) and a $0 offer, since the site has no reviews or prices to back them.
- Design files copied unchanged: `web/assets/css/products/{digital-label-printers,product-detail}.css`,
  `web/assets/js/products/product-detail.js` (one change: the "not connected" message's email subject uses the page's
  product instead of a fixed name). `i-download` added to the icon sprite.
- The Request A Sample form is **not connected** yet (HubSpot pending), as in the design: it validates and says so.

### Content

- **104 products** = every live product page that answers 200. The 9 Store API products that 301 on live are not
  imported. All 9, plus 5 old slugs that the design and the menus still use, have 301s in `config/redirects.php`
  (same targets as live).
- **Pilot `arrowjet-aqua-330r-lite-2`:** content from the design page (small heading, image, headline, intro,
  7 highlights, video + poster + caption, Overview / Features (11 specs) / Brochure (English + Spanish PDF), and the
  design's 3 related printers).
- **The other 103, from the live page:** H1 → name, first image → Main Image, next heading → Headline, the text before
  the tabs → Intro (its first bullet list → Highlights), each live tab → a Text / Specifications (2-column tables) /
  Downloads (PDF links) tab; Anytron FAQ accordions → a "FAQ" tab; key-figure image boxes → Highlights; video →
  Video URL. 6 pages not built with Elementor use the WooCommerce description. Images → Images → `products/`; PDFs →
  Documents.
- **Names = the live H1** (22 differ from the WooCommerce name, e.g. "ArrowJet Bolt 330R Hybrid Pro M").
- **Categories** from WooCommerce (52 products have none, as live), **brands** from the practice project (78),
  **SKU** from WooCommerce (37).
- **Cards:** the design listing's 12 products have its card photo, tagline and key specs; the others use the Main
  Image and no tagline yet (WooCommerce has no short descriptions).
- **SEO as live:** SEO Title only where the live title isn't "Name - Arrow Systems, Inc." (Rank Math custom titles),
  every meta description, Robots for `custom-roll-label` (noindex, nofollow, as live), self canonicals.
- **Menus:** the product links in the main menu and footer now point to the product entries (`03_menus.php`).
- Not imported (see `report.md`): the OverJet SP-800M "workflow" HTML widget, the MTS-48 image carousel, the Matan
  SprinG3 application slides.

### Checks

- `verify.py`: **104 / 104 product URLs → 200**; **14 / 14 redirects → 301** (the 9 live-redirected products + 5 old
  slugs); **SEO parity: 0 problems** (title, description, canonical, robots vs the live `<head>`); 3 products have no
  Main Image because live has none either (`3m_versatile-print-label-material-7908v…`, `custom-roll-label`,
  `plain-laminated-roll-808-mm`); homepage, `/blog/`, `/exhibitions/`, `/upcoming-exhibitions/` → 200.
- Pilot vs the design (`product-detail.html`, header to help band): **684 = 684 tokens**. The only differences are
  real URLs (local pages, uploads, live quote / category pages), the eyebrow "Digital label Printers" (the
  WooCommerce category name) and the third card's name "ArrowJet Aqua 330R Hybrid Pro M" (the live H1).
- Browser (headless Chrome; the pilot, Any-CUT I, EZCut 330R+, a 3M material with 7 download tabs, a label film,
  `custom-roll-label`, Limitronic V6 Lite, the homepage): tabs switch, Download Brochure opens its tab, the video
  plays on click, the sample form validates and shows its "not connected" message with the product in the email
  subject, 3 related products; no JavaScript errors, no failed requests, no broken images, no sideways scroll at 390 px.
- CRUD: a product created with 2 tabs (dragged order kept) → 200, renamed → shown, deleted → 404.
- Image alt text: every product image has one, except the decorative video poster.

---

## Phase 19: Product Page Builder

Everything below the product header is now one **Page Builder** field that editors fill themselves (add, remove,
drag blocks), instead of the separate Tabs and Video tabs. The header stays fixed fields (breadcrumb, name, Main Image,
Request A Sample form, headline, intro, highlights, buttons); More Products and the help band stay automatic.
Script: `storage/imports/products/04_page_builder.php` (structure + moving the content; `_helpers.php` holds the shared
`blockType()`). Backup before: `…2026-10-14-104547…`.

| Item | Value |
|---|---|
| **Page Builder** (`productBuilder`) | Matrix, Blocks view, on the Product's new **Page Builder** tab (after Product; the Tabs and Video tabs are gone). "Add block" opens the same icon grid as the blog's Article Body (`modules/builderui`, now for both fields) |
| Layout | **Tabs** (`tabsBlock`: the Tabs field: Text / Specifications / Downloads tabs, as before). Several Tabs blocks on one page work; repeated tab titles get unique ids |
| Content | **Rich Text** (`richText`, shared with the blog) · **Specifications** (`specsBlock`: Heading + label / value rows) · **Downloads** (`downloadsBlock`: Heading + files) · **FAQ** (`faq`, shared with the blog) |
| Media | **Product Video** (`productVideo`: YouTube URL, Poster, Caption, **Show In**: *Header* (default: beside the photo, under the buttons, where the design puts it) or *Page* (full width at the block's place); click to play) · **Image** (`image`, shared with the blog: Image, Caption, Width) |

- **Templates:** `templates/products/_entry.twig` renders the blocks in order inside the design's details section
  (`.pd-details__grid`); one partial per block in `templates/products/blocks/` (`tabsBlock`, `productVideo`,
  `richText`, `specsBlock`, `downloadsBlock`, `faq`, `image`, plus `_spec-list` and `_downloads` shared by tabs and
  blocks). Blocks outside the tabs use the tab panel's white card (`craft.css` → `.pd-block`, `.pd-figure`, `.pd-faq`).
- **Header quick link** "Download …" opens the first Downloads tab, else jumps to the first Downloads block. SEO
  `og:video` / VideoObject use the first Video block.
- **Content moved:** all 104 products: Video (29) → a Video block, then the tabs → a Tabs block.
- **Design fix (after review: "not following the design"):** at first every Video block rendered full width above the
  tabs, which moved the video out of the header on 29 products. Now Video blocks with Show In = Header (the default,
  all 29) render in the header exactly where the design has it; only Show In = Page videos render among the blocks.
  Also: **Card Title** (`headingText`, Card tab, as the practice project's `cardTitle`) so cards can use the design
  listing's shorter names (set for the 12 design products, e.g. "ArrowJet Hybrid Pro M"), and the category name in the
  small headings is shown in sentence case as the design ("Digital label printers").
  Pilot vs the design now: **684 = 684 tokens, no element or text differences** (only real URLs: 16), and the same page
  height at 1440 px (4362) and 390 px (7808); side-by-side screenshots identical. The old Tabs / Video values
  were emptied (0 left). `02_products.php` now writes the same two blocks, so a re-import keeps working (`--update`
  replaces a product's builder content).
- **Checks:** `verify.py`: 104 / 104 → 200, 14 / 14 redirects, SEO parity 0 problems, videos and PDFs all present;
  pilot vs the design: see the design fix above; a test product with all 7 block types
  (2 Tabs blocks with the same tab title, Video, Rich Text, Specifications, Downloads, FAQ, Image) rendered and worked
  in headless Chrome (tabs, video, FAQ, Download link, no JS errors, no sideways scroll), then deleted; CP: Page Builder
  tab with the icon grid (Layout / Content / Media, search focused), no JS errors; homepage and blog unchanged.

---

## Phase 20: Industries (Page Builder, dynamic products, homepage section E)

`/industries/` and the 12 live industry pages at **the same URLs as live** (root level, e.g.
`/flexible-packaging-and-custom-mylar-bags/`), built from the design (`Arrowsystem_local/industries/index.html`,
`industry-detail.html`, homepage section E). Every industry page is a **Page Builder** the editors arrange themselves;
the "Recommended Products" block picks products **dynamically** from the Products section. Decisions: the plan's
recommendations (`INDUSTRIES_MIGRATION_PLAN.md` §9), with D1 replaced by the dynamic product picker. Scripts in
`storage/imports/industries/`: `01_structure.php`, `02_content.php [--update] [--only=slug]`, `03_connect.php`,
`verify.py` (`_links.php` shared). Data: `cache/` (the 13 live pages, fetched 2026-10-14), `data/pages.json`. Report:
`report.md`. Backups before: `…2026-10-14-114214…` (structure), `…114802…` (content), `…115401…` / `…115502…` (connect).

### Structure

| Item | Value |
|---|---|
| **Industries** (`industries`) | Structure, 1 level, URL `{slug}` (root, as live), `industries/_entry`. Drag order = listing order and the 01, 02 … numbers |
| **Industry** entry type | Industry Name, Slug (must stay the live slug) · **Card**: Card Photo (`featuredImage`), Card Summary (`cardTagline`), Icon (`menuIcon`), **Show on Listing** (`showOnListing`) · **Page Builder** · **SEO** (title, description, social image, hide, canonical) |
| **Page Builder** (`industryBuilder`) | Matrix, Blocks view, icon-grid "Add block" (`modules/builderui`, now for all 3 builders). **Sections**: Industry Header (breadcrumb, small heading, H1 with `[red]` words, intro, Applications tags, image, 2 buttons) · **Recommended Products** (`productShowcase`: **Show** = *Picked products* (`selectedProducts`, drag to order) or *Products from the categories* (`selectedCategories` + **How Many**: new products in those categories appear automatically); heading, intro, View All link, anchor) · Content Block (image + small heading, heading, rich text, link; blocks in a row alternate the image side; no image = full width) · Contact Form (panel from Site Settings + form) · Other Industries (picked, else the next listed ones; never the page itself) · CTA Band (shared with the homepage). **Media**: Photo Gallery (`photoGallery`: heading + photos; the exhibition gallery + lightbox; 1–2 photos side by side) · Video (shared with the blog). **Text**: Rich Text (shared with the blog) |
| **Industries Page** (`industriesPage`) | Single, URL `industries`, `industries/_index`: small heading, heading, intro, Count Line, help band heading + text (buttons: phone + quote link from Site Settings), SEO |
| Homepage **Industries Carousel** (`industryCarousel`, section E) | Small heading, heading, **Tiles** (`industryTiles` → Industry Tile: Link to an industry entry or any URL; Name / Photo empty = the linked industry's own), View All link, anchor |
| Shared button fields | `primaryCta` / `secondaryCta` now also accept `#anchors` and `/root-relative` URLs (only loosened) |

### Templates and assets

- `templates/industries/_index.twig` (listing: header + the design's fixed icon mosaic + count, card grid of the
  industries with Show on Listing on, help band), `templates/industries/_entry.twig` (renders the builder blocks in
  order; consecutive Content Blocks share one section; adds the gallery CSS/JS + lightbox only when a gallery is
  used; without an Industry Header a plain header keeps the H1), `templates/industries/blocks/*.twig` (one per block),
  `templates/_components/industry-card.twig`, `templates/_blocks/home/industry-carousel.twig`. Product cards reuse
  `_components/product-card.twig`.
- Design files copied unchanged: `web/assets/css/industries/{industries,industry-detail}.css`,
  `web/assets/js/industries/industry-detail.js` (contact form check; **not connected** until HubSpot, as the design
  says). Small additions in `craft.css` (Video / Rich Text blocks, text-only Content Blocks, 1–2-photo galleries, lists
  in content-block text).
- SEO: `og:type article` (as live), WebPage + BreadcrumbList; `/industries/`: CollectionPage + BreadcrumbList.

### Content

- **Industries Page:** the design's texts (D6) + the live SEO and social image.
- **12 industries** in the live listing order; **names as live** (D2: "Beverages Labels", "Pharmaceutical Labelling",
  …); cards = the design's photo, summary, icon (D8). **Corrugation Printing** and **Wallpaper Printer** are not on the
  live listing → Show on Listing off, pages work (D5).
- **Pilot Flexible Packaging** = the design page (header + 4 application tags, 3 picked products, 2 content blocks,
  contact, 3 other industries).
- **The other 11 from their live page:** H1 → header (the industry name in red when the H1 contains it), first
  paragraph → intro, first photo → header image; each "Learn More → /product/…" teaser → **Recommended Products**
  (picked; old product slugs followed through the redirects; Wallpaper Printer → the ArrowJet Aqua 800M); each
  heading + text (+ photo) → Content Block; galleries → Photo Gallery; YouTube → Video; then Contact Form + Other
  Industries. Long sections that only end with a product (Corrugation, 2,816 characters) keep their text.
  Photos → Images → `industries/`. Not imported: the 80 × 80 feature icons of the Wallpaper page's image boxes (their
  text is kept) and the product teasers' own photos (the product cards show each product's photo).
- **Connected:** homepage section E after D (the design's 6 tiles: 5 link to industries, "Wines & Spirits" to
  `/industries/` as the design, D4); 11 menu items (main + footer) → the entries; **651 links** in rich text (blog
  posts, products …) and 1 Markdown link to live industry / product pages → root-relative; **141 Link fields** (blog
  buttons, 5 hero slides, product lists) → entry links to the products / industries.

### Layout pass (after review: "too much blank space, not side by side as live")

The first import split the live pages by heading, so text ran full width and photos ended up alone in their own grey
sections. Now:
- **Row-based import** (`02_content.php` → `liveBlocks()`): each live Elementor row (text in one column, a photo in the
  other) → one Content Block with its photo beside the text; a heading-only row → the heading of the next block or
  gallery; a text-only row right after a block continues it. Product-teaser rows (≤ 600 characters of text) → the
  product card; a long row that only ends with a product (Corrugation) keeps its text and photo. A button naming a
  product ("Gerber EDGE FX") → that product's photo + a link to it.
- **Content Block**: heading optional; a **Video URL** can replace the image (Label Production: video beside the text);
  without image / video: heading left, text right on a white panel (no heading: text in 2 columns).
- **Industry Header**: image empty = the industry's Card Photo (most live pages only have a background banner), else
  its Social Image; the Intro may have several paragraphs (blank line between); a long live intro keeps ~2 paragraphs
  (≤ 450 characters) and continues in a 2-column text block (Pharmaceutical).
- **Recommended Products with one product**: one wide card (photo left, details right) instead of 1 of 3 columns.
- The pilot is unchanged (still 584 = 584 tokens with the design). Expected `verify.py` notes: 7 product-teaser photos
  (Matan SprinG3, EZCut 330R, Hybrid Pro M, Aqua 330R), which the product cards replace.

### Checks

- `verify.py`: **13 / 13 URLs → 200** at the live paths; **SEO parity: 0 problems** (title, description, canonical,
  robots, og:image); content: every live photo and video present except the 5 product-teaser photos the product cards
  replace; homepage section E present; homepage, blog, exhibitions, products → 200.
- Design (token comparison): **Flexible Packaging 584 = 584 tokens**, **listing 477 = 477**, **homepage E 136 = 136**;
  the differences are URLs, two ids and the live names (D2). Side-by-side screenshots of Flexible Packaging and the
  design: identical, same page height (4998 px).
- Browser (headless Chrome; listing, 7 industry pages, homepage): contact form validates and says it isn't connected,
  gallery lightbox opens (photo counts right), videos play on click, homepage carousel controls + 6 chips; no
  JavaScript errors, no failed requests, no images from arrsys.com, no sideways scroll at 390 px.
- CRUD: a test industry (header, products from a category with How Many = 4 → 4 cards, content block, other
  industries) → 200 and on the listing (11 industries); Show on Listing off → off the listing, page still 200;
  deleted → 404. CP: Page Builder tab, icon grid (Sections / Media / Text), no JS errors.
- Regression: products (104 + 14 redirects, SEO 0), exhibitions (29 URLs, SEO 0, 268 = 268 photos), blog (352 posts,
  346 sitemap URLs, listing, 45 categories, 245 tags) all still pass.

---

## Phase 21: One common page builder

Every module's builder now offers **the same content blocks** (the blog's 20, in the same groups) plus **its own blocks
in a group of its own**, instead of a separate set per module. Script: `storage/imports/builder/01_common_blocks.php`
(idempotent; entry types are shared in Craft 5, so no content moved). Backup before: `…2026-10-15-052229…`.

| Builder | "Add block" menu |
|---|---|
| Blog → Article Body (`articleBody`) | Text · Lists · Media · Products & CTA · Q&A · Other (the common set, 20 blocks) |
| Products → Page Builder (`productBuilder`) | **Product**: Tabs, Specifications, Downloads, Product Video + the common set |
| Industries → Page Builder (`industryBuilder`) | **Industry**: Industry Header, Recommended Products, Content Block, Contact Form, Other Industries, CTA Band, Photo Gallery + the common set |

- **One template per common block:** `templates/blog/blocks/` moved to `templates/_builder/` and is used by all three
  (a block looks the same everywhere). Product and industry pages put consecutive common blocks in one `.prose` column
  (products: inside a white panel in the details section; industries: a section of its own) and load `blog-detail.css`
  only when a page uses them. The module templates keep only their own blocks (`products/blocks/`, `industries/blocks/`).
- **Removed duplicates:** the product versions of Rich Text, FAQ and Image and the industry versions of Rich Text and
  Video (templates + their `craft.css` rules); those blocks now render with the common templates (the Pharmaceutical
  page's video: the blog's embed).
- **Adding a block to every builder later:** add its entry type to Article Body (in a group), add `_builder/<handle>.twig`,
  re-run the script.
- Fixed on the way: a new product no longer shows an empty Highlights bullet (empty table rows are skipped).
- Fixed after review: the **Comparison** block showed red dots instead of ✓ / ✕ (the design's general `.prose` list rule
  outranked the comparison rule; also in the design file). `craft.css` repeats the tick / cross rules with more
  specificity, for all builders. Column Style labels corrected: "Pros (✓ ticks, white card)", "Cons / limits (✕ crosses,
  grey card)" (there was no green / red).
- Checks: blog (352 posts …), products (104 + 14 redirects, SEO 0), industries (13 URLs, SEO 0), exhibitions all pass;
  a test product and a test industry with Heading, Rich Text, Callout, Checklist, Image, FAQ and Video blocks rendered
  (200, no errors, blog styles loaded only there), then deleted; the pilots are unchanged (they use only their own
  blocks).

### Every block tested (2026-10-15)

`storage/imports/builder/test_all_blocks.php [create|delete]` makes 3 test pages (`/builder-test-post/`,
`/product/builder-test-product/`, `/builder-test-industry/`) holding **every block and every option** (30 / 35 / 43
blocks: heading levels, callout styles, image widths, gallery columns, button styles, pro / con, video in the header /
page, products picked / from a category / single, content block with image / video / text / plain …).
`node --experimental-websocket storage/imports/builder/test_all_blocks.mjs [out dir]` checks them in headless Chrome at
1440 px and 390 px: markup, computed styles, behaviour (tabs, FAQ, videos, lightbox, forms, quick links), JS errors,
failed requests, broken images, sideways scroll, and saves full-page screenshots. **Result: 211 checks pass, 0 fail**;
the screenshots were reviewed by eye. The test pages were deleted afterwards (re-create them any time).

Bugs found and fixed by this test:
- **Data Table** (and tables in Rich Text): a table without CKEditor's `<figure>` (pasted / source HTML) got an opening
  `<div class="table-wrap">` but no closing one, so every later block fell out of its column. Fixed in
  `_macros/blog.twig`. (Every table in the imported blog posts uses `<figure>`, so they were never affected.)
- **Heading / Key Takeaways → Anchor ID** were ignored outside the blog: now used everywhere.
- **`.spec-list`** is styled by both the blog CSS (cards) and the product CSS (rows); a product page using common blocks
  mixed them. `craft.css` pins each look to its place (cards in the common column, rows in the product's Tabs /
  Specifications blocks).
- **CTA Band**: `[red]` words were red on maroon (unreadable): now white, underlined, in every maroon band.
- **Content Block without heading or media**: its text now really flows into 2 columns.
- **Product spec rows** (tabs, Specifications block) now render `*italic*` / `**bold**`, as the field's instructions say.
- Not a bug: the Intro block's large maroon first letter is the design's drop cap (a capital "I" looks like a red bar).

---

## Phase 22: Blog editor: collapsed blocks + Outline navigator

Control panel only (no content, field or front-end change). First the blog's Article Body (reviewed), then the same on
the **product and industry Page Builders** (their builder is on a "Page Builder" tab: clicking an outline row or
"Expand all" opens that tab first). Files: `modules/builderui/resources/navigator.js` / `navigator.css` (registered in
`Module.php`; the field ids are listed at the top of `navigator.js`).

- **Collapsed by default:** every saved block opens collapsed (title bar + Craft's preview text), using Craft's own
  `collapse()`. New blocks stay open for typing. While Craft is still setting up a long post (121 blocks ≈ 10 s), the
  blocks already show as title bars only, so the page never shows a 65,000-px expanded list.
- **Outline panel** in the right sidebar, **as tall as the window**; the **sidebar has its own scrollbar** (blog posts
  only), so the outline is reached by scrolling the sidebar, not the article; the outline's list scrolls inside:
  one row per block (icon, type, its first text), **H2 Heading blocks as section markers** with the blocks under them
  indented, a **Find a block…** search, **Collapse all / Expand all**; clicking a row opens that block, scrolls to it
  and highlights it. Follows adds, deletes, moves and typing.
- Checked in a logged-in browser: test post (30 blocks) and the longest real post (121 blocks, 19 H2 sections): all
  collapsed on load, outline complete, click → block opened at the top of the window, expand / collapse all, search,
  a newly added block opens and appears in the outline, no JS errors, opening a post creates no draft. Product and
  industry editors unchanged. Then products / industries: the test product (35 blocks), test industry (43), ArrowJet
  Aqua 330R Lite (2), Flexible Packaging (6) and the blog post again: all collapsed on load, outline complete, row
  click opens the Page Builder tab and the block, expand / collapse all, search, sidebar scrolls, no JS errors, no
  drafts created.
- Note: Craft remembers collapsed blocks in the browser (localStorage); this only affects the editor's own view.

---

## Phase 23: Blog search

The design's "Search articles" box on `/blog/` (and the category / tag pages) still sent visitors to the live WordPress
search (the design's interim). Now it searches Craft:
- **Index:** no text field was searchable, so only titles were found ("BoPET" → 0 posts). `storage/imports/blog/09_search.php`
  marks the blog's text fields searchable (lead, Article Body and the text fields in its blocks: headings, rich text,
  lists, FAQ, steps, comparison, product list, captions, tables, CTA) and the blog's index was rebuilt
  (`craft resave/entries --section=blog --update-search-index`). New and edited posts are indexed by Craft automatically.
  The fields are shared, so product / industry text is searchable too (for a later site-wide search).
- **Results:** `/blog/?q=…` (`blog/_index.twig`): the matching posts, best match first, in the same grid (no large first
  card), 10 per page with the pagination keeping `?q=`, "47 articles for “mylar” · Clear search", a no-results hint; the
  box keeps the words. Search pages: `noindex`, title "Search: “…” - Blog".
- Checked: "mylar" 47 (was 12, titles only), "BoPET" 7, "thermal transfer" 24, nonsense → no results; page 2 / past
  the end (404); typed HTML escaped; `/blog/` without a search unchanged.
- Not done: the header's search icon (every page) still uses the live WordPress search; a site-wide `/search/` page is
  the next step.

### Blog top-up (2026-10-15)

Posts published on live after the Oct 13 export: `storage/imports/blog/10_fetch_new_posts.py` fetches only posts not in
`data/` yet (+ their images, new tags / categories, live SEO) into new data files, then `03_taxonomy.php` (adds missing
tags only) and `05_import.php --slugs=…` (existing posts are always skipped). Imported: `paper-vs-film-labels-where-each-works-and-where-it-fails`,
`in-house-label-printing-vs-print-on-demand-cost-by-volume` (+3 tags): 200, SEO = live, cover, categories, tags, found by
the blog search. Blog: 345 posts (+ the builder test post). Re-run the same 3 steps before launch for later posts.
**Top-up 2 (2026-10-27):** 7 posts (Oct 16–27) + 10 tags, then `11_social_images.php` and `12_live_seo.php --slugs=…`
(new option: without it the script gives posts edited on staging live's modified date back). Craft's time zone is now
Asia/Kolkata, so `postDate()` and 12 read WordPress dates as America/New_York explicitly. Live sent its "One moment,
please…" bot check for 2 posts' `<head>` (`water-based-vs-uv-cured-label-adhesive`,
`confectionery-flexible-packaging-in-house`): they have no live meta description / share image yet (fetch their SEO
later, then `05_import.php --slugs=… --update`); 10 now skips such pages instead of saving them.
**Drafts:** the 12 unpublished WordPress drafts are not in the public API; they need a WordPress export
(Tools → Export → Posts, Drafts) or an application password (Follow-up 16).

---

## Phase 24: Forms (Formie): Contact Us

Formie 3.1.48 (installed in the CP). The form **Contact Us** (`contactUs`, created in the CP) made the same as the live
form on `/contact-us/` (checked 2026-10-15) by `storage/imports/forms/01_contact_us.php` (idempotent):
- Fields (the live ones), all required, labels above, **two columns**: **Name** (one input, placeholder "Name") |
  **Email** ("Email") · **Number** (a **Phone field with a country / dial-code picker**, United States by default) |
  **Country** ("Enter your country") · **Message** ("Message") · **How Did You Hear About Us**. Button **Send**.
- **Phone numbers are checked on the server too** (`modules/forms`, registered in `config/app.php`): Formie validates the
  number for the chosen country only in the browser; the module rejects numbers that aren't valid for that country
  (libphonenumber, shipped with Formie), so "123" fails even without JavaScript. The rate limiting will go in the same
  module.
- Spam: Formie's **Honeypot** on (site-wide + this form; live has a hidden honeypot field). **reCAPTCHA v2** (live)
  needs this site's own keys (live's key only works on arrsys.com): Formie → Settings → Captchas → reCAPTCHA, then
  enable it on the form. Rate limiting is not set up yet (see the forms plan in chat: per-IP limit + edge rules).
- Kept: the form's 2 notifications (admin → `{systemEmail}`, visitor copy → `{field:emailAddress}`).
- Checked: an empty submission fails on all 6 fields; a bad email fails; US / UK / Indian numbers pass, "123" and
  letters fail; Honeypot saved in project config.
- Not done: the form is not shown on a page yet (`/contact-us/` comes with the Pages module).

---

## Phase 25: Product page, new design + global Stats Strip

Design source switched to the newer folder `/home/avadh/server/theglobalwebdev/demo/public_html/Arrowsystem`
(`digital-label-printers/product-detail.html`, rebuilt to the client's prototype on 2026-10-14). Its `css/product-detail.css`
and `js/product-detail.js` copied unchanged into `web/assets/{css,js}/products/`; 9 missing icons added to the sprite
(check, plus, ruler, columns, info, lock, sparkles, thermometer, shirt).
- **Globals → Stats Strip** (`statsStrip`, table `statItems`: Number, Label, Icon): the company figures (40+ / 2,500+ /
  800+ / 30+, from the design: TODO(client) confirm them). Shown by the **Stats Strip** block, which is in **every page
  builder** (products, industries, blog: Other group; homepage sections). Edit the figures once, every page updates.
  `_partials/stats-strip.twig`; styles in `craft.css` so it works on any page.
- **Product fields:** Gallery (more photos after the Main Image; each photo's new **Caption** = its label, e.g. "Front
  view"), Subtitle (the old Headline, shown in red), Header Specs (up to 4 tiles: value, label, note, icon). The **Intro**
  is the description under the subtitle (2–3 lines). The first header Video block becomes the gallery's last thumbnail.
- **Tabs:** new **Highlights** tab (panel heading + icon cards: icon, title, text) with "View full specifications" →
  the Specifications tab. Panels have the design's headings (Highlights / Specifications / Downloads).
- **New block "What You Can Print"** (`productApplications`, product builder): industry cards in a row with arrows;
  industries picked in the block, else automatic (the industries whose Recommended Products include the product).
  Heading with [red] words, intro with `{name}`, "View all industries" link.
- **Layout:** each builder block is now its own full-width section (stats, tabs, applications …). The inline sample
  form is gone: "Request a Sample" links to Site Settings → Sample Link (as the design).
- **Content** (`storage/imports/products/05_new_design.php` structure, `06_new_design_content.php` content, both
  idempotent): Stats Strip + What You Can Print added to all 105 products (existing blocks untouched; the importer
  `02_products.php` adds them too). Pilot `arrowjet-aqua-330r-lite-2` filled from the design: subtitle, description,
  4 header specs, Highlights tab (6, replaces the Overview text), 20 merged specifications, the design's 6 industries.
  Live name kept ("… Lite"; TODO(client) in the design).
- **One field per thing** (review 2026-10-16, `07_simplify.php`): the Short Description field was deleted (the Intro is
  the description; the pilot's design text moved into its Intro). The old bullet **Highlights** field (the previous
  design's header list) left the product form: the bullets of 55 products became a **Highlights tab** (one card per
  bullet, tick icon), the first tab of their Tabs block (4 products without tabs got a Tabs block). Header Specs stay
  empty until editors fill them (no tiles shown meanwhile). Text tabs as before (old styles kept in `craft.css`).
  TODO(editors): 45 imported intros are over 60 words (up to 293): shorten them to 2–3 lines.
- Checked: pilot vs design at 1440 px and 390 px (same layout; card texts come from each industry's own tagline);
  random products 200; the Stats Strip block renders in all 4 builders.

## Phase 26: Blog post cover, full width (design change 2026-10-16)

From the design's CHANGELOG (client feedback): the cover moved from a column beside the title to **full width under the
title, summary and meta**, shown **whole** at its own proportions (no 16:9 crop). `web/assets/css/blog-detail.css`
copied from the design (only the header rules changed; the corner marks hide on phones). `templates/blog/_entry.twig`:
the cover is no longer cropped to 1280 × 720; it is served up to 1600 px wide (never enlarged), since some live covers
are 2500 px / 5 MB. Checked against the design at 1440 and 390 px.

## Phase 27: Live share images (og:image) that differ from the cover

`storage/imports/blog/11_social_images.php` (idempotent, run after any blog import): where the live og:image is a
different picture from the post's featured image, the entry at the same URL gets it as its **Social Image** (the cover is
unchanged). 3 live URLs (data from 2026-10-15): `/eliminating-wasted-inventory-the-ultimate-guide-to-on-demand-box-printing-2026/`
and `/screen-printing-vs-digital-inkjet-for-corrugated-boxes/` (both share `ChatGPT-Image-Feb-11-2026-11_16_39-AM.png`),
and `/labelexpo-brussels-2023/` (`label-Expo-brussels-2023-1.jpg`), which on staging is the **exhibition** (live shows
the page at that URL; the post is at `-article`, Phase 16). Checked: og:image, twitter:image and the schema image now
match live on all 3. The 26 live posts without a featured image already match (their og:image is the cover).

## Phase 28: Blog SEO parity with every live post <head> (2026-10-19)

All 344 imported posts compared tag by tag with the live pages fetched today (scratch check, read-only). Fixed:
- **article:tag** (one per tag, as live) and **og:video** (the post's first YouTube Video block) added to
  `templates/blog/_entry.twig`; the cover preload now asks for the same file as the cover `<img>` (was the old crop).
- `storage/imports/blog/12_live_seo.php` (idempotent; `_helpers.php` postDate() fixed too, for future imports):
  publish date = WordPress's local `date` in America/New_York (the import used `date_gmt`, equal to `date` on older
  posts: 168 posts were 4–5 h early, 4 of them showed the wrong day); modified date = live `modified` (345 posts; a CP
  save sets it to now, as it should); share image alt text from live (292 images, also the cover's `<img alt>`).
Left different on purpose: site name "Arrow Systems (Local Staging)" in titles / og:site_name / publisher (set the site
name to "Arrow Systems, Inc." at launch); canonical of 3 "…-2026" posts (live points to the old "…-2025" URLs that 301
to these); og:image size (staging shares the original, live a resized copy) and file-name spelling (× encoded, trailing
"-" dropped); read time ±1 min on 175 posts (counted from the text); "Admin" vs "admin" author name on 69 posts (two
WordPress users, one Craft user); Yandex `ya:ovs:*` tags; schema: Person / Organization nested in BlogPosting (live:
separate nodes + WebSite / ImageObject); og:video on 59 posts where Rank Math found none, and on 10 posts another video.
Live changed after the import: `pharmaceutical-label-version-control-workflow` (modified 2026-10-16 on live).

## Phase 29: Pages module (structure only)

`PAGES_MIGRATION_PLAN.md` phase 1, no content (pages are added one by one). `storage/imports/pages/01_structure.php`
(idempotent), then `storage/imports/builder/01_common_blocks.php`:
- **Entries → Pages** (`pages`): a Structure, up to 3 levels, like WordPress Pages: create, drag under a parent (URL
  `/parent/child/` follows), reorder, drafts, revisions, preview, scheduling. URL `{parent uri}/{slug}`; Craft keeps
  every URL unique site-wide (a slug already used by a post / industry / exhibition gets a "-1" suffix: check it).
- **Page** entry type: Page Title, Slug · **Card** (Card Photo, Card Summary, for when a page is shown as a card) ·
  **Page Builder** · **SEO** (SEO Title, Meta Description, Social Image, Hide from Search Engines, Canonical URL, Robots).
- **Page Builder** (`pageBuilder`): group **Sections** = the industries' section blocks, shared (D3): **Page Header**
  (the "Industry Header" renamed; handle `industryHeader` unchanged, industries unaffected), Recommended Products,
  Content Block, Contact Form, CTA Band, Photo Gallery; + the common set (Text, Lists, Media, Products & CTA, Q&A,
  Other incl. Stats Strip). Collapsed blocks + Outline navigator as the other builders.
- **Front end:** `templates/pages/_entry.twig` extends `industries/_entry.twig` (one template for both): section
  blocks full width, common blocks in a text column; no Page Header block = a plain header (breadcrumb + title).
  Breadcrumb (and BreadcrumbList schema) = Home › parent pages › page (industries: Home › Industries › industry, as
  before). SEO: the shared partial, WebPage schema, Robots (advanced) honoured.
- **D5 (Layout option):** not added: "starts with a Page Header or not" covers both live templates for now.
- Checked: a test parent + child page (Page Header, Rich Text, Stats Strip, SEO fields): 200 at `/zz-test-parent/` and
  `/zz-test-parent/zz-test-child/`, breadcrumb + schema follow the parent, custom title / description, canonical; the
  editor shows the Outline; industry pages unchanged (breadcrumb, 200). The 2 test pages are kept for reference (Hide from Search Engines on).
- Not yet: the design's new section blocks (form + side column, tiles, features, related pages, answers list …) come
  with the pages that need them; the all-blocks test (`test_all_blocks.*`) doesn't cover Pages yet.

## Phase 30: Contact Us page + Form block (any Formie form on any page)

`storage/imports/pages/02_form_blocks.php` (structure) → `builder/01_common_blocks.php` → `03_contact_us.php` (the page;
data/contact-us.json read from live 2026-10-19). Decisions: D3–D6 as live, D7 reCAPTCHA, D8 dummy admin inbox.
- **Form block** (Page Builder → Sections): the admin picks any **Formie form**; Formie renders it and handles the
  submission (validation, honeypot / reCAPTCHA, Formie → Submissions, email notifications, thank-you message). Options:
  form heading; **Office Details** panel beside it (Office Heading; address, toll-free / direct phone, email from Site
  Settings; optional Google Maps embed); anchor id. Styled with the design's form card; Formie's theme set to the site's
  colours / fonts (`--fui-*` in craft.css). Template `industries/blocks/formBlock.twig`.
- **Distributors block**: heading, Google Maps embed, Distributor cards (Country, Company, Details rich text), 3 per row.
- **/contact-us/** (Pages): Page Header ("Contact [Us]", live banner) · Form (Contact Us form, "Arrow Systems, Inc. USA"
  panel + the live office map) · Distributors ("Distributor Location", the live My Map, the 19 live cards in live order,
  text as live incl. spellings, Cloudflare-protected emails decoded). Live SEO title / description. Top bar "Contact",
  main menu and footer "Contact Us", and the blog's default CTA button now link to the page.
- **Contact Us form** (`forms/01_contact_us.php`): every label shown, Formie's starter hints removed (as live); Admin
  Notification → admin_arrowsys@yopmail.com; reCAPTCHA on for the form.
- **reCAPTCHA v2 checkbox** (Formie): enabled / site key / secret key from `.env` (`RECAPTCHA_ENABLED=false`, keys empty
  until the client sends them; added to the .env.example files). To switch on: paste the keys, set it to true.
- Checked: page 200 at /contact-us/, live title / description / canonical / robots; desktop + phone screenshots; in a
  browser: empty form → 6 "cannot be blank" errors; filled form → thank-you message, submission saved (id 54861, kept for
  reference), no JS errors; notifications queued to the right addresses.
- **Email not delivered:** the site's SMTP (Gmail `phpdevs10.wpweb@gmail.com`) is refused by Google (534-5.7.9 "log in with
  your web browser"): needs a Gmail App Password (or another SMTP) in Settings → Email; then resend from Formie →
  Sent Notifications. Not done: per-IP rate limiting; the 204 text links to arrsys.com/contact-us/ inside blog posts.

## Phase 31: A general page builder (no block made for one page)

Review of Phase 30: blocks must be general. `storage/imports/pages/04_generic_builder.php` (idempotent; run after 02 /
03, then `builder/01_common_blocks.php`):
- **Form** block: only the Formie form + optional heading, intro, anchor (the office panel / map options removed). Now in
  the **common set** (group Products & CTA): blog posts, products, industries, pages and columns can all hold a form. The
  CP shows **"Edit form fields ↗"** under the chosen form (`modules/builderui/resources/cp.js`): Formie's form builder
  (fields, emails, thank-you message) in a new tab: Formie's builder can't run inside the entry form.
- **Columns** block (new, Pages + industries): two columns (50/50, 40/60, 60/40, 33/67, 67/33; stacked on phones), each
  with its own blocks (the whole common set incl. Form and HTML, minus the full-width Stats Strip) and a style: plain,
  white card, maroon panel. Fields `leftColumn` / `rightColumn` (Matrix can't be placed twice in one layout),
  `columnsLayout`, `columnStyle` (twice: leftStyle / rightStyle).
- **Card Grid** (was Distributors; handles renamed, content kept): heading ([red]), intro, cards (title, subtitle, image,
  text, link: whole card clickable), 2 / 3 / 4 per row. Pages + industries.
- **Contact Form** block removed from the Pages builder (the industries keep it: their 12 pages use it).
- **/contact-us/** rebuilt from general blocks only: Page Header · Columns [maroon panel: Rich Text (office address,
  phones, email) + HTML (the live office map) | white card: Form (Contact Us)] · Heading + HTML (the live distributor
  map) · Card Grid (the 19 distributors).
- The Pages builder now also gets the icon-grid "Add block" menu (was missing in cp.js).
- Fixed on the way: re-laying out a block type must keep its fields' layout elements (uids), or Craft 5 orphans the
  values entered; and a page can only use block types its builder already allows (Craft drops others silently). The DB
  was restored from the backup before the first run (`…-101806…`) and the fixed script re-run.
- Checked: page 200, desktop screenshot; in a browser: empty form → 6 errors, filled → thank-you + submission saved;
  CP: "Edit form fields" link → Formie's editor of the chosen form; other modules (product, industry, blog, home) 200.

## Phase 32: Section Settings (flexible layout controlled by the admin)

`storage/imports/pages/05_section_settings.php` (idempotent). Review: the Contact Us columns took too much space (both
columns stretched to the same height, article-size text in the panel / card).
- **Section Settings** tab on Columns, Card Grid and Form: **Space Above / Below** (none, small, normal, large),
  **Background** (white, light grey); Columns also **Vertical Alignment** (top: default, centre, equal height).
  `templates/_macros/layout.twig` sectionClass() → `.section--pt-* / --pb-* / --surface` (craft.css).
- Columns styled as a white card / maroon panel use compact content (the design's contact panel / form card scale: no
  article heading bar, 15px text, tight spacing, 4:3 embeds); a one-column Columns block = full width (16:9 embed).
- Contact Us: columns aligned top (the form card no longer stretches); "Distributor Location" + map in a one-column
  Columns block (full width, as live); Card Grid right under it (Space Above: none).
- Checked: desktop + phone screenshots; form submit in a browser (thank-you, no JS errors); other pages 200.

## Phase 33: Ready for Git (secrets out of the config, .gitignore)

- **SMTP login in `.env`:** Settings → Email now reads `$SMTP_USERNAME` / `$SMTP_PASSWORD` (`config/project/project.yaml`
  no longer holds the Gmail password); the values are in `.env`, empty entries added to the `.env.example.*` files.
  Checked: the same login is used (a test send still gets Gmail's 534 "log in with your web browser": the account needs
  an App Password, unchanged).
- **`.gitignore`** (besides Craft's /.env, /vendor): `web/uploads/` (706 MB, shared as a zip), `web/cpresources/`,
  `storage/backups|runtime|logs|composer-backups|config-deltas/` (config-deltas held an old copy of the SMTP password),
  `storage/imports/*/cache/` (saved live pages), `.env.*.local`. Kept: code, `config/project/`, the import scripts and
  their `data/`, the docs. First commit ≈ 21 MB. Checked: no `.env` secret appears in any file that would be committed.
- Next: `git init` + first commit (by the user), then `QA_SETUP.md` updated for clone + `composer install`.

## Phase 34: Dark header and footer (the design's 2026-10-19 version)

Source: the design on the server (`Arrowsystem/`, CHANGELOG 2026-10-19 "Dark header and footer" … "Lighter red on menu
hover … label-sheet pattern in the footer"). The design changed the look with one stylesheet and a light logo; the
header / footer HTML was already the same as the staging templates render.
- `web/assets/css/dark-chrome.css`: the design's file, copied unchanged; loaded after site.css (base.twig).
- **Globals → Site Settings → Brand:** + **Logo on Dark Background** (`logoLight`) = the design's
  ASI-Logo-4-light.webp (Images root); the header and footer use it (else the Logo). Logo's instructions updated.
- **Main Menu** (Navigation), as the design's menus now (`storage/imports/navigation/01_dark_header.php`, idempotent,
  finds items by title): Gerber Plotters → end of Label Finishers ("Gerber cutting plotters"); "Partner Product Lines"
  → **Specialty Printers** (Matan SprinG3, Gerber Edge FX, Corrugated & End-of-Line) as the last category of "Arrow
  Systems equipment"; Gerber Foils, Gerber Premium Cast Vinyl Film, 3M Aviation Films → **Materials**; the empty
  "Partner Brands" group deleted. Menu management unchanged (same menus, item types and fields).
- Checked: header / footer structure = the design's (left: links to staging's own pages, the admin's cut-out featured
  photo); screenshots design vs staging at 1440 and 390 px (header, footer: identical); Products mega (Specialty
  Printers) and Materials open with the design's items; no JS errors.
- Not copied (not header / footer): the design's homepage hero video changes in home.css / home.js.

## Phase 35: Homepage hero, video slides (uploaded file or YouTube link)

The design's hero video (Arrowsystem/, CHANGELOG 2026-10-19 hero video entries). `storage/imports/homepage/01_hero_video.php`
(idempotent; keeps the existing slide fields' layout elements, so the slides keep their content):
- **Hero Slide** fields: **Slide Type** (Image / Video), **Video File** (upload, video files, into Images → hero/) and
  **YouTube Link** (the existing Video Link field; any YouTube URL or the ID), both shown only for Slide Type = Video
  (field layout conditions); **Banner Image / Video Poster** (for a video: the poster, until it plays and for reduced
  motion); **Display Time** (seconds; empty = 7 s, or 20 s for a video).
- Front end: `_blocks/home/hero-slider.twig` outputs the design's video slide (`hero-slide--video`, `data-interval`,
  `.hero-slide__video` with `data-hero-video-src` for a file, else `data-hero-video`); a file wins over a link.
  `web/assets/css/site.css` and `js/site.js` replaced by the design's current home.css / home.js (they differ from the
  previous copies only by the hero video handling): muted autoplay, no controls, replay from the start, keeps playing
  while hidden (no YouTube pause button on return), controls' width measured.
- Content: the design's video slide added first: "ArrowJet MTS-48 in Action" (YouTube HjWQU9GJ4bI, the design's poster,
  Explore Product → the MTS-48 product, 20 s). The 5 image slides unchanged.
- Fixed on the way: a field layout condition set from a script needs `elementType` in its config and the layout saved
  first, else Craft silently drops it.
- Checked in a browser: YouTube slide → player loads and plays; an uploaded MP4 (temporary test, removed) → native video
  plays at once, wins over the link; Next / 6 dots work; no JS errors; desktop + phone screenshots. Upload limits:
  Craft's maxUploadFileSize default 16 MB, PHP (XAMPP) 40 MB.

## Phase 36: Products mega menu, a featured product per category

`storage/imports/navigation/02_category_featured.php` (idempotent): Main Menu items get **Featured Product** (Entries →
Products, one). On a Products **category** item (Digital Label Printers, Label Finishers …) it sets the box on the right
while that category is open: the product's Card Photo (else Main Image), name (Card Title else title), Card Tagline,
"Explore Product" → its page. Categories without a pick show the menu's own box (Navigation → Main Menu → Products box),
as before; the "Compare Models / Used Equipment" links stay under every box. Featured Text's help text corrected (it
promised a tagline fallback the menu box doesn't have).
- Templates: `_partials/navigation/mega-feature.twig` (one box), `mega-products.twig` (the default box + one hidden box
  per category with a pick). `web/assets/js/craft.js` (new, loaded after site.js; Craft-only behaviour, the design's
  site.js unchanged): shows the open category's box, else the default (follows site.js's aria-expanded: click, keyboard,
  default first category). Without JS / without picks: the default box only, as before. Phones: no box (design).
- Checked in a browser with 2 temporary picks (removed after): opening each category switches the box; a category
  without a pick shows the default; no JS errors. No picks set yet: the site looks as before.

## Phase 37: Products box removed from the Main Menu content

`storage/imports/navigation/03_remove_products_box.php` (idempotent): each category now picks its own Featured Product
(Phase 36), so the hand-typed menu-level box is gone: the "Products box" tab left Navigation → Main Menu's own fields
(the "Industries box" tab stays) and the fields Featured Title / Featured Text / Featured Link were deleted (used only
there; Featured Image is the shared Card Photo field and stays). A category without a Featured Product shows only the
extra links (Compare Models, Used Equipment) in a compact box (`mega__feature--links`, craft.css). Featured Product's
help text updated.
- Checked in a browser: a category with a pick shows its product + the links; without one, the links only; no JS
  errors; screenshot. Note: a test run overwrote the admin's pick on Digital Label Printers (Digital Sleeking Film),
  restored right after.

## Phase 38: Product List block, dynamic products with photos

`storage/imports/builder/02_product_list_dynamic.php` (idempotent; keeps the item's layout elements, so the 19 existing
items keep their content). Each Product of a Product List (common block: blog, products, industries, pages, columns):
- **Product or Link** (first): choose a product **entry** → its photo (Card Photo, else Main Image), name (Card Title,
  else title) and text (Card Tagline) show automatically, as on the Recommended Products cards; or paste a URL.
- The item's own **Image** (new, optional), **Name** (no longer required) and **Description** override them; Tag Line
  as before. A URL item needs its Name; an item with no name at all is left out.
- `_builder/productList.twig` (works for a link to any entry type); `craft.css`: the photo at the top of the card
  (4:3, whole product on a light ground, like pcard__media); cards without a photo keep the text-only look. The blog's
  product-card design (blog-detail.css) is otherwise unchanged.
- Checked: every page using the block renders (the Roll-to-Roll post: 9 products, 9 photos; test pages: the URL item
  text-only); screenshots desktop + phone; no sideways scroll at 390 px.

## Phase 39: Page Header full-width banner; tables in collapsed blocks fixed

- **Header Style** on the Page Header block (Pages + industries; `storage/imports/pages/06_header_style.php`, keeps the
  layout elements): **Image beside text** (default, unchanged) or **Full-width banner**: the text first (breadcrumb, small
  heading, H1, intro, tags, buttons), then the image on its own across the full content width, shown whole (not cropped,
  no text on it) with the design's corner marks, as the blog posts' cover (review: the first version put the text on the
  image). `industryHeader.twig` + `.page-banner` / `.page-banner__cover` in craft.css; image served up to 1600 px wide.
  Without an image it falls back to the default. /contact-us/ switched to the banner (as live).
- **"Add item" in tables did nothing** (Applications, Items, Spec List … inside a page-builder block): Craft's
  EditableTable finishes setting up only when visible, else on the next window resize; our builders start blocks
  collapsed (navigator.js), so a table in a collapsed block never finished. navigator.js now sends that resize whenever
  a block opens (also nested ones). Checked in the CP: open the Page Builder tab, open the Page Header, Add item → rows
  1 → 2 → 3 (not saved).
- Checked: banner screenshots desktop + phone (aligned with the page edges, readable); industry pages unchanged (200).

## Phase 40: Editor sidebar: entry settings above the Outline

Review: the entry's settings (Slug, Parent, Author, Post / Expiry Date, Enabled, notes, ID …) back above the Outline
(`modules/builderui/resources/navigator.js` + `navigator.css`, all builders: blog, products, industries, pages):
- the Outline is the last item of the right-hand column; the column is no longer sticky itself (it runs the page's full
  height), so the settings scroll away with the page and the **Outline sticks** under Craft's top bar, as tall as the
  window (its list scrolls); near the end of the page it shrinks to the column's end, so it is always whole.
- Checked in the CP (long blog post, Contact Us, a product): top of the page = settings, then the Outline; scrolled =
  the Outline stuck at 71–797 px (window 813); end = whole (71–621 / 74–637); no JS errors.

## Phase 41: Formie Submissions "Cards" view crash fixed

Switching Formie → Submissions to the Cards view failed with "Attempt to read property "value" on string"
(craft/helpers/Cp.php:1049): Formie 3.1.48 (the latest 3.x) gives Craft the submission status colours as plain strings
("green"), Craft 5.11 expects its Color enum. `modules/forms/StatusesWithColors.php` (Formie's Statuses service with
`getStatusesArray()` returning Color enums) replaces Formie's `statuses` service once the plugins are loaded
(`modules/forms/Module.php`). Marked `shortcut:`: remove when upgrading to Formie 4 (statuses reworked; 4.0 is from
2026-10-16, a major upgrade with migrations, not taken now). Checked: the card renders with its status label in PHP and
in the CP (Cards view: 4 cards, 4 status labels, no HTTP errors); no new log errors.

## Phase 42: Our Company page (/our-company/)

`storage/imports/pages/07_our_company.php`. Live (https://arrsys.com/our-company/) has only a banner, the H1 and 3
paragraphs; no design file exists. Redesigned with general blocks only, the live text word for word (typos too):
Page Header (Full-width banner: "Our [Company]", paragraph 1, the live banner Our-Company.webp shown whole, buttons
Request a Quote / Contact Us) · Stats Strip · Card Grid "Segments We Serve" (the 6 segments paragraph 1 names, each
linked to its industry page with that page's card photo and summary; materials → live Label Materials, the design's
material photo) · Content Block (paragraph 2, "Request a Free Quote") · CTA Band (paragraph 3, Contact Us). SEO as live
(default title, the live meta description, which live cuts off mid-sentence). Main Menu and Footer "Our Company" now
link to the page. Checked: 200, title / description / canonical / robots / og:image; desktop + phone screenshots.

## Phase 43: Support page (/support/)

`storage/imports/pages/08_support.php`, from live https://arrsys.com/support/ (no design file); live text word for
word, general blocks only: Page Header (text only: the live background is a plain dark texture; buttons Call
1-888-697-2749 and Send a Support Request → #support-form) · Columns [maroon panel: Contact Support (toll-free, direct,
email, headquarters, the support partners line) | white card: Form block "Send a Support Request" with the Contact Us
Formie form: live's text promises "the form below" but live has none] · CTA Band "Planning to Add [Capacity]?" (Request
a Quote). SEO as live (title "Label Press & Finisher Support | Arrow Systems, Inc.", description). The Page Header
buttons now also accept phone and email links (any page). Top bar "Support", Main Menu and Footer "Request Support"
link to the page. Fixed in 07/08: the block-count self-check compared Craft's count (a string) strictly.
Checked: 200, SEO tags, tel: button, form anchor; desktop + phone screenshots.

## Phase 44: Page Header widths, Banner Height; the Support banner image

- **Full-width banner:** the header text now spans the full content width too (intro without the 54-character cap).
  **Image beside text:** an even 50 / 50 split on large screens (the design had 1fr / 1.05fr; industries too).
  craft.css, two-class selectors (industry-detail.css loads after it).
- **Banner Height** (new Page Header option, shown only for Full-width banner, `06_header_style.php`): Whole image
  (default) or Short strip (cropped band, 160–320 px tall): `.page-banner__cover--strip`.
- **/support/:** the Support banner **inner-banner-support-new.jpg** (1165 × 376: "24/7 Service, working with you around
  the clock" + the ASI logo; live media library id 82) on the Page Header, Full-width banner, whole image
  (`08_support.php`). First version wrongly took the page's CSS background (v1016-b-08-scaled.jpg, a plain dark
  texture): the current live HTML / CSS / REST content don't reference the banner (found in the media library); the
  texture asset was deleted.
- Checked: screenshots of /support/, /our-company/ (banner, whole) and an industry page (50 / 50); the Banner Height
  field shows for banner headers only.

## Phase 45: Customer Service form and page (/customer-service/)

- **Formie form "Customer Service"** (`customerService`, `storage/imports/forms/02_customer_service.php`): Formie's own
  duplicate of Contact Us (same settings, honeypot, reCAPTCHA via .env, thank-you message, both notifications, but its
  own fields), reshaped to the live form: Name*, Email*, Phone No.* (country picker, checked by modules/forms),
  Message*, button Send. Admin Notification → admin_arrowsys@yopmail.com. The script stops if the copy shares fields
  with Contact Us (a plain `duplicateElement()` does that; Contact Us stays 6 fields).
- **/customer-service/** (`storage/imports/pages/09_customer_service.php`), live text word for word, general blocks:
  Page Header, Image beside text ("We Provide 24 X 7 Customer [Service]", the live sentence, the live photo
  people-working-call-center-1.jpg → Images / pages, button "Fill Up the Form" → `#customer-service-form`) + Form block
  ("Fill Up Our Customer Service form", the Customer Service form). SEO as live (default title, the live description).
- Main Menu and Footer "Customer Service" now link to the page.
- Checked: 200, title / description / canonical, the 4 fields render; screenshots at 1440 and 390 px (no sideways
  scroll); the script's second run changes nothing.
- **Form Width** (review: "form is too small"; `storage/imports/pages/10_form_width.php`): a new option on the Form
  block's Section Settings: Wide (default, 1100 px), Narrow (820 px, the old width), Full width (the content width).
  It applies when the form is a section of its own; inside Columns the column sets the width. Only /customer-service/
  had a section-level Form block, so no other page changed.

## Phase 46: Card Shadow option (Card Grid)

- **Card Shadow** (new option on the Card Grid block's Section Settings, `storage/imports/pages/11_card_shadow.php`):
  None (default, the flat bordered cards as before), Soft (`--shadow-md`), Strong (`--shadow-lg`); linked cards still
  lift on hover. `cardGrid.twig` adds `card-grid__list--shadow-soft|strong`; the styles are in craft.css.
- Checked: existing grids render unchanged; Soft tried on /our-company/ (screenshot), then set back to None.

## Phase 47: CSS Class and Custom CSS (admin-written styles)

- **CSS Class** (`cssClass`, `storage/imports/pages/12_custom_css.php`): on the Section Settings tab of all 31 block
  types of the Pages and Industries builders (the shared text blocks show it in the blog and product builders too, and
  work there). Section blocks get the classes on their `<section>` (Page Header, Content Block on its card, Stats
  Strip …); text blocks (Heading, Rich Text, Image …) get a `<div class="…">` around them. Only valid class names are
  output (`_macros/layout.twig`: `customClass`, `builderBlock`; `sectionClass` adds it too).
- **Custom CSS** (`customCss`, code textarea): a "Custom CSS" tab on pages, industries, products and blog posts (that
  page only) and on Globals → Site Settings (every page). `_layouts/base.twig` writes both into one `<style>` after
  the site's CSS (site-wide first, then the page's); `<` is removed so the text can't close the tag.
- Checked: pages, an industry, a product, a blog post load (200); a test class + CSS on /customer-service/ (invalid
  names dropped, a `</style><script>` attempt neutralised) and a class on a Rich Text block on /support/, both reverted.
- Product-only blocks (Tabs, Specs, Downloads, Video, Applications) and homepage sections don't have CSS Class.

## Phase 48: Request a Quote form and page (/request-quote/)

- **Formie form "Request a Quote"** (`requestQuote`, `storage/imports/forms/03_request_quote.php`): Formie's duplicate
  of Contact Us (own fields; settings, captchas and notifications as Contact Us), reshaped to the live form: Name* |
  Email*, Phone No.* | Company, How Did You Hear About Us | Product Required*, Message*, button Send. Admin
  Notification → admin_arrowsys@yopmail.com. Contact Us unchanged.
- **/request-quote/** (`storage/imports/pages/13_request_quote.php`). Live has only the H1 and the form (no image, none
  in the media library). Page Header "Request [Quote]" (no image) + Columns: maroon panel "Contact Us" (toll-free,
  direct, email, headquarters from Site Settings, as /support/) | white card with the form (anchor `quote-form`).
  SEO as live: title "Request Quote - Arrow Systems, Inc.", description "Request Quote".
- **Links moved to the page:** Footer "Request a Quote"; Site Settings → Request a Quote Link (header and mobile
  buttons); the Request a Quote buttons on /our-company/ (Page Header), /support/ (CTA Band), an industry Content Block
  and the blog listing. Links typed inside rich text (7 product / blog texts, 1 industry text) still go to arrsys.com.
- **Page Header, generic:** without any image the text now spans the full width (`page-banner` layout), instead of
  half the width beside an empty column.
- Checked: 200, title / description, the 7 fields render, header button → the page; screenshots at 1440 and 390 px;
  the scripts' second runs change nothing.

## Phase 49: Contact Us submissions from the live site

- **642 live submissions** (Elementor export, 2022-08-22 to 2026-10-14) imported into the Formie form Contact Us
  (`storage/imports/forms/04_live_submissions.php contactUs <csv>`): Name, Email, Number, Country, Message, How Did You
  Hear About Us, the visitor's IP and the original date (the live site's time, UTC-4); title = that date, as Formie's
  own titles; status New. Not kept: Elementor's submission ID, user agent, referrer.
- The export writes text JSON-style (`\r\n`, `\"`, `\/`); the script decodes it, so messages keep line breaks, quotes
  and non-Latin text. No emails sent (a directly saved submission triggers no notifications or integrations); saved
  without validation (old numbers predate the per-country phone check).
- Runs again safely (skips rows with the same email and date). The CSV holds personal data: it stays in Downloads,
  not in the project. First run's duplicate check compared dates in different timezones and imported twice: restored
  the backup taken just before, fixed, re-run.
- The live data includes spam (SEO / Instagram offers, Cyrillic messages): imported as is, not marked as spam.
- **Request a Quote: 157 live submissions** (2022-08-22 to 2026-09-29) imported the same way into the Formie form
  Request a Quote (`04_live_submissions.php requestQuote ~/Downloads/request-a-quote-submission.csv`). The script is
  now general: any form, each CSV column into the field with the same label (stops on a column without one).
- **Customer Service: 14 live submissions** (2022-11-20 to 2026-10-18) imported into the Formie form Customer Service
  (`04_live_submissions.php customerService ~/Downloads/customer-service-submissions.csv`).

## Phase 50: Become a Dealer form and page (/become-a-dealer/)

- **Formie form "Become a Dealer"** (`becomeADealer`, `storage/imports/forms/05_become_a_dealer.php`): Formie's
  duplicate of Contact Us, reshaped to the live form: Name* | Email*, Contact No.* (country picker) | Country*,
  Existing Business History (several lines, "Brief us in Few Words"), Existing Dealership details | Sales and Service
  History, Reason For Becoming a Dealer*, button Send. Admin Notification → admin_arrowsys@yopmail.com.
- **/become-a-dealer/** (`storage/imports/pages/14_become_a_dealer.php`), live text word for word: Page Header, Image
  beside text ("Join Us To Dominate The Fast-Moving Label and Packaging [Industries]", the photo uploaded with the
  live page: the handshake, media id 9924; button → `#dealer-form`) + Form block ("Fill Up The Form, We Will Get Back
  To You"). SEO: the live title and description.
- Live has no menu link to the page (only search finds it); here About → Become a Dealer and Footer → Company →
  Become a Dealer now link to it.
- Checked: 200, title / description, the 8 fields render, both menu links; screenshots at 1440 and 390 px; the
  scripts' second runs change nothing.

## Phase 51: Tabs block on Pages / industries; Gerber Foils page (/gerber-foils/)

- **Tabs block, general** (`storage/imports/pages/15_tabs_block.php`): the products' Tabs block (text, specifications,
  downloads, highlights tabs) added to the Pages and Industries builders (Sections, after Card Grid), with Section
  Settings (spacing, background, CSS Class). `industries/blocks/tabsBlock.twig` renders it in its own section and
  `industries/_entry` loads the products' tab styles / script when a page has one.
  - So that can happen safely: product-detail.css's two page-wide spacing rules moved to digital-label-printers.css
    (product pages load both: unchanged); product-detail.js's gallery code only runs for the product gallery (a Photo
    Gallery on the same page would have thrown an error); products' detail sections take the Section Settings classes.
  - Generic styles (craft.css): tables in text (tabs) get borders and striped rows; lists in Card Grid cards get bullets.
- **/gerber-foils/** (`storage/imports/pages/16_tabbed_page.php gerber_foils`, content in `pages/data/gerber_foils.json` taken from
  the live page): Page Header, Full-width banner (Gerber-Foils.jpg, uploaded with the live page), "EDGE Series
  [Foils]" + the live intro, button Request a Quote; Tabs: the six foil series as on live (text, devices / colours /
  lengths table, part-number and bulletin links); Card Grid: the four feature lists. SEO: the live title and
  description, social image gerberfoils.jpg (live og:image). Main Menu → Materials → Gerber Foils links here.
- The live page's two small images (gspinc-era logos) are gone on live (404): left out. Its gspinc.com links now
  redirect to lectra.com's homepage (Lectra owns Gerber): kept as live, to review with the client.
- Checked: 200, SEO tags, 6 tabs / 6 tables / every live link; tab switching in a browser, no script errors; a product
  page unchanged; screenshots at 1440 and 390 px.

## Phase 52: Gerber Premium Cast Vinyl Film page (/gerber-premium-cast-vinyl-film/)

- Same live layout as Gerber Foils (intro + Elementor tabs: text, table, links), so the page script is now general:
  `storage/imports/pages/16_tabbed_page.php <name>` builds a page from `pages/data/<name>.json` (page details: live URL,
  slug, title, eyebrow, heading, SEO, banner and social image; the intro; the tabs as HTML; optional feature lists →
  Card Grid). Gerber Foils' data file now carries its page details too (that page exists: re-running skips it).
- **/gerber-premium-cast-vinyl-film/** (`16_tabbed_page.php gerber_premium_cast_vinyl_film`): Page Header, Full-width
  banner (Gerber-Premium-Cast-Vinyl-Film.jpg, uploaded with the live page), "Gerber Premium Cast [Vinyl Film]" + the
  live intro, button Request a Quote; Tabs: 180mC Controltac Film, 220 High Performance, 225 High Performance, Gerber
  ImageCast (text, widths / lengths / colours table, part-number and bulletin links: 16, as live). SEO: default title
  (as live), the live description, social image vinylfilm.jpg. Main Menu → Materials → Gerber Premium Cast Vinyl Film
  links here. Its gspinc.com links redirect to lectra.com, as on Gerber Foils.
- Checked: 200, SEO tags, 4 tabs / 4 tables / 16 links; tab switching, no script errors; screenshots at 1440 and 390 px.

## Phase 53: Scotchgard SGH6 product rearranged (review: "cluttered, segments not visible")

- `/product/scotchgard-paint-protection-film-sgh6-84812-8-mil-transparent-12-in-x-40-yd/`
  (`storage/imports/products/08_scotchgard_sgh6_layout.php`). The import had put the whole live description in the
  header's text column (description, highlights, two empty headings "Suggested applications" / "Typical properties",
  two tables) and the suggested applications in a tab labelled "Highlights". Now, with the existing blocks and the live
  text unchanged: header text = Product Description; Tabs = Highlights (the 7 highlights, check icons) · Suggested
  Applications (the 8 surfaces) · Typical Properties ("Details" and "Dimensions and Classifications", each its own
  table). Stats Strip and Applications blocks unchanged.
- craft.css: the label column of tables in text is 40% wide, so stacked tables line up.
- 5 other 3M products have the same imported header text ("Typical properties" etc.): not changed (see the list in the
  reply of 2026-10-21); same treatment on request.

## Phase 54: "All elements" + grid menus in the page builders (CP, 2026-10-22)

- Review: "give one more tab called All elements … popup in grid view like Elementor". In every builder (Pages,
  Industries, Products, Blog, and the Columns inside them) the add row is now **All elements** (Craft's full menu: every
  group, search box, icon tiles in a 4-column grid) followed by one button per group (Sections, Text, Lists, Media,
  Products & CTA, Q&A, Other). Each group's popup is the same tile grid (it was one tile per row).
- Craft shows either the full button or the group buttons, depending on the room; `modules/builderui/resources/cp.css`
  now shows both (and grids every menu), `cp.js` labels the full one "All elements". CSS / JS only, no field changes.
- Checked in the CP (headless, as admin): the Customer Service page's builder (All elements popup, Text popup) and a
  blog post's builder; no script errors.

## Phase 55: CTA Band, both buttons set by the admin (2026-10-22)

- Review: "why two buttons when there is a button field?". The Pages / industries CTA Band always added "Talk to a
  Specialist" (a call to Site Settings' toll-free number) before the block's Button. Now the block has **Button** (white)
  and **Second Button** (outlined; the existing secondaryCta link field), each shown only when filled in, nothing added
  automatically (`industries/blocks/ctaBand.twig`; the homepage's CTA band shows a Second Button too).
- Buttons (the shared `ctaLink` field) accept phone and email links, as the Page Header's buttons do.
- Existing CTA Bands keep their look (`storage/imports/pages/17_cta_band_buttons.php`): Button = Talk to a Specialist
  (tel:+18886972749), Second Button = their old Button: /support/ (Request a Quote), /our-company/ ×2 (Contact Us,
  arrow system), /builder-test-industry/. The first run copied page links as URLs (Support's and Our Company's second
  button disappeared): repaired from the backup and fixed in the script. Homepage CTA band unchanged.

## Phase 56: Pages, Card tab removed (2026-10-22)

- Review: "remove the Card tab, there is no need of it". The page entry type's Card tab (Card Photo `featuredImage`, Card
  Summary `cardTagline`) is gone (`storage/imports/pages/18_remove_page_card_tab.php`); page tabs are now Page · Page
  Builder · SEO · Custom CSS. Nothing listed pages as cards; Card Photo was only a fallback for the header / social
  image, and every page that had one uses the same image in its Page Header (checked per page first: no visible change).
- The fields stay on industries, products, blog posts and exhibitions. `industries/_entry.twig` reads a page's Card
  Photo through `??` (absent field = no image); `16_tabbed_page.php` and its data files no longer set the card fields.
  (01_structure.php created the tab; the older one-off page scripts still mention the fields but only run on a database
  without those pages.)
- /our-company/ (2026-10-22): the closing CTA Band "Over 40 Years of [Experience]" had been removed when a "Draft 1"
  was applied at 11:35 IST; restored from the trash (block 55052, text and buttons unchanged) after "first title",
  before the test band "testing CTA". Earlier (11:25) the first "testing CTA" was lost the same way after the CTA Band
  script edited that block while a draft of the page was open: scripts now check for open drafts before editing blocks.

## Phase 57: 3M Aviation Films page (/aviation-films/)

- `storage/imports/pages/19_aviation_films.php`, from the live page (H1 "3M Aviation Films", "Product Category", a grid
  of 3M products). General blocks only: Page Header, Full-width banner (3M_Banner_website-1.webp, uploaded with the
  live page: plane + 3M logo, the version without the words; live doesn't show it now), "3M Aviation [Films]", button
  Request a Quote; Recommended Products "Product [Category]": 15 picked products in the live order (product cards:
  photo, name, Learn More). SEO: the live title and description. Main Menu → Materials → 3M Aviation Films links here.
- Recommended Products takes up to 24 picked products (was 12), for any page or industry.
- Live's 16th product, "Microfluidic Diagnostic Tape 9965", no longer exists on live (its link redirects to this page):
  left out. Live's "Versatile Print Label 7908V" linked to the A7322 film: here its own product (no photo, on live either).
- Checked: 200, title, 15 cards, menu link, no broken images; screenshots at 1440 and 390 px.

## Phase 58: product pages' help band managed in the CP (2026-10-22) — superseded by Phase 59 (per product)

- Review: "Not sure which product fits? should be managed by the admin, not fixed; keep the front end as it is". The
  maroon band at the bottom of every product page now reads Globals → Site Settings → **Product Help Band** (new tab,
  after Calls to action; `storage/imports/products/09_product_help_band.php`): Heading (`{type}` = "printer" on
  printer products, else "product"; [words] underlined; empty = no band), Text, Button, Second Button (any link type;
  each left out when empty). Reuses the sectionHeading / sectionLead / ctaLink / secondaryCta fields.
- Filled with what the template showed: "Not sure which {type} fits?", "Tell us your application, materials and run
  lengths.", Talk to a Specialist (tel:+18886972749), Request a Quote (the Request a Quote page). `products/_entry.twig`
  renders the band from them.
- Checked: the band's heading, text and both buttons are the same as before on a film product and a printer product.

## Phase 59: product help band per product (2026-10-22)

- Review: "it should not be global, manageable per product; front end the same". Each product has a **Help Band** tab
  (after Page Builder): Heading ([words] underlined; empty = no band), Text, Button, Second Button (each left out when
  empty). The Site Settings tab from Phase 58 is removed (`storage/imports/products/10_product_help_band_per_product.php`;
  09 marked superseded). `products/_entry.twig` renders the band from the product's own fields.
- All 105 products filled with what they showed: "Not sure which printer fits?" (22, main category slug contains
  "printer", as the template decided) or "…product fits?" (83), "Tell us your application, materials and run lengths.",
  Talk to a Specialist (tel:+18886972749), Request a Quote (the Request a Quote page). Saved without validation (the band
  only). The script stops if a product has an open draft (none had).
- New products: fill the Help Band tab, else no band.
- Checked: all 104 enabled product pages (200) show the same band text and buttons as before.

## Phase 60: Media Carousel block (2026-10-22)

- Review: "image carousel option in the page builder, images as well as videos". New general block **Media Carousel**
  (Pages and Industries builders, Sections group, after Tabs; `storage/imports/pages/20_media_carousel.php`): Heading
  ([red] words), Intro, **Slides** (Matrix `carouselSlides`, cards view, entry type Carousel Slide: Image, Video File (an
  uploaded MP4, native player, the Image as poster), YouTube Link (click to play, the site's [data-video] player),
  Caption; a slide shows its video file, else its YouTube video, else its image), Section Settings (spacing, background,
  CSS Class).
- Front end: `industries/blocks/mediaCarousel.twig` (one slide at a time, scroll-snap track), styles `.mcar` in craft.css,
  behaviour in craft.js (arrows and dots move one slide and wrap around; the dot follows swipes; leaving a slide pauses
  its video; without JS the track still scrolls / swipes).
- Checked on a temporary page (deleted, with its test video): image, YouTube (plays) and uploaded-video slides (plays,
  pauses when left); next / previous / dots / wrap-around / swipe; desktop and 390 px; no script errors.

## Phase 61: 3M Crystal Glass Finishes 7725SE-314 (48 in × 150 ft) rearranged; product layout audit (2026-10-22)

- Review: "design mismatched, you entered all contents inside intro instead of the page builder". Same import problem as
  Scotchgard (Phase 53). `storage/imports/products/11_product_sections_layout.php <slug> [--dry]` (general version of 08):
  header text = Product Description (its "(Download)" links are the download tabs' files); Tabs = Highlights (one point per
  live paragraph, a bold lead-in → its title) · Suggested Applications · Typical Properties (Details + Dimensions and
  Classifications tables) · the existing Downloads tabs kept as on live ("Data Sheets (2)", "Installation Guide").
  Stops on an open draft. Applied to this product only; checked by screenshots (header + every tab).
- Audit of all 103 live products against staging (sections, tabs, tables; side-by-side screenshots for samples): list
  given to the user to decide (13 more 3M products with the same problem; spec tiles / FAQ / process sections on some
  machines; Arrow materials' Applications list; test data on a few products). Dry runs of 11 on the 13 3M products: works,
  but before applying: Highlights written differently on some (1–2 points), tab name to follow live ("Specifications"),
  9962 has no Tabs block.

## Phase 62: product downloads as live, all products (2026-10-22)

- Review: "live has 2 brochures, staging 3 … fix the brochure issue in all products". Every product's Downloads tabs were
  compared with the named download links of its live page (56 products, 105 files). Live puts a small PDF icon before
  each brochure name; the icon is its own link (often to a compressed copy, sometimes to another product's brochure),
  and the import had turned it into an extra download named "<product> Brochure".
- `storage/imports/products/12_fix_downloads.php`: 18 icon-made downloads removed (Gerber GSxplus / GS750 Plus / GS15plus,
  ArrowCut Nova 250R / 330R, ArrowJet Nova 250R / 250R+ / 330R, Aqua 330R Hybrid / Hybrid Pro / Hybrid Pro M / Lite,
  ArrowJet UV, Arrow EZCut 330R / 330R+ / 350R, Bolt 330R Hybrid Pro M, Matan SprinG3); ArrowJet Aqua 330R Lite
  (`arrowjet-aqua-330r-lite-2`) now links live's brochure `arrowjet-aqua-330r-lite.pdf` (new file in Documents; it pointed
  to ArrowJet-Aqua-330R.pdf); 3M Screen Print UV Gloss Clear's file titled "Safety Data Sheet" as live. Stops on open
  drafts; the removed files stay in Assets.
- Checked: a fresh comparison of every product = all 84 files match live's named links (titles as live, minus live's
  " (Download)"); only Builder Test Product's test files are not on live. Controltac's live page has a stray
  "Scotchpak 9738 Backing Film Flyer" link inside its description (copied from Scotchpak), not a download: left as is.
- QA: the new file `web/uploads/documents/arrowjet-aqua-330r-lite.pdf` must go with the next uploads zip.

## Phase 63: one product request form with a hidden Product field (2026-10-22)

- Live: 98 of 105 product pages embed their own copy of an Elementor form "Product Request Quote" (98 form IDs): Name,
  Email, Contact Number, Country/Region (placeholders), button "Request A Sample", under the product photo; a submission
  knew its product only by the page. 7 pages (WooCommerce default) have none. The header's "Try A Free Sample!" button
  goes to /try-a-free-sample/ (a separate "Free Sample Request" form; not built here).
- Decision (user): one form, hidden field instead of copies, button "Request a Free Sample".
  `storage/imports/forms/06_product_request_quote.php`: Formie form **Product Request Quote** (`productRequestQuote`, copy
  of Contact Us: honeypot, reCAPTCHA by .env, notifications; admin → admin_arrowsys@yopmail.com): Name, Email, Contact
  Number (Phone, country picker), Country/Region, all required, labels hidden on screen (screen readers read them) +
  hidden **Product** (filled by the template) and **Product Page** (Formie fills the page address).
- `products/_entry.twig`: the form under the photos on every product (`#request-sample`), Product = the product's name
  (`craft.formie.populateFormValues`); the header's sample button → "Request a Free Sample", scrolls to the form. craft.css:
  left column (photos + form); on phones the order is photo → name / description / buttons → form.
- Checked: a browser submission on Arrow EZcut 330R saved Product "Arrow EZcut 330R 13" Blade Finisher" and its page address
  (test submission deleted); desktop / 390 px; no script errors. Formie → Submissions → Product Request Quote lists every
  product request with its Product.
- Not done: Request a Quote button → pre-filled Product Required; the Try a Free Sample page; importing live's product
  form submissions (export "Product Request Quote" from Elementor, then 04_live_submissions.php with the page → Product).

## Phase 64: live product form submissions imported (2026-10-22)

- Source: the user's export of every Elementor form (`~/Downloads/arrow-system-all-form-submission/`, 190 CSVs, one per
  form copy). The product form: 89 "Product Request Quote (<id>)" files, **1,007 submissions** (2022-11-08 → 2026-10-20).
- Form: `06_product_request_quote.php` adds three hidden fields filled only by the import: Live Submission ID, Live Form,
  User Agent (no other live value is dropped).
- `storage/imports/forms/07_product_request_quote_submissions.php <folder>`: the field headings are empty in the export
  (live's fields have only placeholders), so fields are read by position: Name, Email, Contact Number, Country/Region
  (26 older copies have no Country); Referrer → Product Page (as recorded, with tracking codes) and Product (the staging
  product with that address; 11 addresses not on staging → their words, e.g. "Arrowjet Aqua 330r", 145 rows from
  /product/arrowjet-aqua-330r/); Submission ID / Form Name (ID) / User Agent → the hidden fields; User IP → IP; Created At
  → date (UTC-4) and title. No emails; saved without validation; skips Live Submission IDs already in Formie.
- Checked: all 1,007 in once each, a second run adds nothing; spot-checked newest, a 3-field copy, a retired product.
  The form also holds the 5 test submissions from today (no Live Submission ID).
- Not imported (in the same export, on request): 9 untitled older product-page forms (28 rows), "All product Form"
  (6, a category page), "Request Quote From Blog" (73 files), "Free Sample Request", "Dealer", landing pages, newsletter.

## Phase 65: more live submissions from the full export (2026-10-22, groups A + B)

- A, forms already imported, re-run with the full export's files (`04_live_submissions.php`, skips what is there):
  Contact Us 642 (+0), Request a Quote 158 (+1, sent on live 2026-10-21), Customer Service 14 (+0).
- B, forms that exist on staging: **Dealer** → Become a Dealer, 91 (2022-09 → 2026-09; `04_live_submissions.php` now trims
  column headings: live's "Existing Business History " has a trailing space); the **9 untitled product-page copies** → Product
  Request Quote, 28 (`07` now reads them too; their old product addresses — Anytron AnyCut / AnyJet, LF-200 — are not on
  staging, Product = their words). "All product Form" was left out after a look: its 4th field is a message and its 6
  rows are all spam (imported, then deleted again).
- Totals now: Contact Us 646, Request a Quote 159, Customer Service 14, Become a Dealer 91, Product Request Quote 1,040
  (1,035 live + 5 tests). Second runs add nothing.
- Still in the export (not imported): Request Quote From Blog 306, Free Sample Request 3, Careers ("New Form") 60 with
  CV links, event / campaign landing pages ~203, newsletter 333.

## Phase 66: the blog's quote form, as live, on every post; live submissions imported (2026-10-22)

- Live: an Elementor form "Request Quote From Blog" at the end of 244 of 354 posts (one copy per post), heading "Contact
  With Our Team Now", labels shown: Name*, Email*, Contact No.* ("Place your contact no. with country code"), Message
  ("Place your requirement or a query"), button "Send". The blog import (D4) had replaced it by a closing CTA box.
- `storage/imports/forms/08_blog_quote_form.php`: Formie form **Request Quote From Blog** (`requestQuoteFromBlog`; copy of
  Contact Us): those fields (Contact No. = Phone with a country picker) + hidden Blog Post (filled by the template), Blog
  Post Page (Formie: the address), Live Submission ID, Live Form, User Agent (import only). Blog Page's "Default CTA" tab →
  **Article Form**: Form (this one), Heading ("Contact With Our Team Now"), Intro (emptied, live has none), Button (only
  when no form is chosen).
- `blog/_entry.twig`: the chosen form at the end of every post (`#contact-our-team`, Blog Post = the post's title); the red
  CTA box only when no form is chosen. The sidebar's "Not sure which equipment fits?" box is unchanged.
- `storage/imports/forms/09_blog_quote_submissions.php <folder>`: 73 files, **306** submissions (2022-08 → 2026-10): Blog
  Post / Blog Post Page from the Referrer; without one, from the post whose live content holds that form copy
  (blog/data/posts-*.json) — 107 rows; 69 have no post (67 from copy 6284ef8, in no live post today). Skips by Live
  Submission ID.
- Checked: all 346 posts (200) have the form; a browser submission saved Blog Post "Mylar Bag Printer" + its address (test
  deleted); desktop / 390 px; a second import adds nothing.
- Correction (review: "I do not want the form in every post", e.g. paper-vs-film-labels-where-each-works-and-where-it-fails
  has none on live): blog posts got a **Quote Form** switch (Settings tab, off by default;
  `storage/imports/forms/10_blog_quote_form_per_post.php`), on for the posts whose live content holds the form (235 on
  staging), off for the other 111, which show the closing CTA box as before. `blog/_entry.twig` shows the form only when
  the switch is on. Not changed (open draft since 2026-10-16): 10-step-guide-for-the-best-frozen-food-pouches-with-water-based-inks
  (should be on). Checked: 234 posts with the form, 112 without; paper-vs-film has none.

## Phase 67: Product Request Quote submissions show and filter by product (2026-10-22)

- Asked: "how can I tell which submission belongs to which product in the listing? Elementor has a dropdown filter."
  Formie's list has fixed columns and filters only option, long-text and element fields, so:
- **Titles "Product · date"**: the form's title format is `{field:product} · {timestamp}` (06_product_request_quote.php);
  the 1,040 saved before were retitled the same way, keeping their dates (no Product → the date alone, 8).
- **Product (entry)**: a new Entries field on the form (products section, visibility "disabled", so it's not on the page).
  `modules/forms` sets it on every save from the Product Page address (`/product/<slug>/`). Formie → Submissions →
  Product Request Quote → Filter → Add a filter → **Product (entry)** → Choose → pick products → Apply. (The rule's
  operator reads "has a value"; with products chosen it lists theirs.)
- `storage/imports/forms/11_product_request_quote_list.php` (one-off, idempotent): links + retitles the existing ones.
  785 linked (62 products); 255 not, their live product page isn't on staging (Product keeps the live words).
- Checked: list titles in the CP; the filter offers Product (entry) and its picker lists products; the filter query for
  White PP returns exactly its 2; a browser submission on White PP got "White PP · <date>" + the link (test deleted).
  Searching the product name still works too.

## Phase 68: Try a Free Sample and Careers: pages, forms, live submissions (2026-10-22)

- `storage/imports/forms/12_free_sample_and_careers.php`: two Formie forms, copies of Contact Us (honeypot, reCAPTCHA,
  thank-you, Admin Notification → admin_arrowsys@yopmail.com), live's labels word for word:
  - **Free Sample Request** (`freeSampleRequest`): Sample type* (live's 5 options, radio buttons as the design) · Full
    name* | Email* · Phone (optional) | Country / region* · Product or material of interest ("Optional") · What would you
    like to test? · button "Request my sample".
  - **Careers** (`careers`): Name* | Email* · Contact* (phone, country picker) · Upload Your CV* (one file, PDF / Word /
    image, 10 MB) · button "Send".
  - CVs go to a new volume **Career CVs** (`careerCvs`): `storage/career-cvs`, outside web/, no public URLs; open them
    in the CP (Assets → Career CVs, or the submission → the file → Download). `storage/career-cvs/` is git-ignored.
    **Moving the site (QA / production): copy that folder with the database**, or the CV links are empty.
- `storage/imports/pages/21_free_sample_and_careers.php` (general blocks):
  - **/try-a-free-sample/**: Page Header "Request a Free [Sample]" + live's line; Columns 3-2: the form (card) | panel
    "Talk to a Specialist" (Site Settings contacts) + "Looking for something else?" (Request a Quote, Order Supplies,
    Request Support). Site Settings → Sample Link and 10 blog Button blocks linking to the live page now link here
    (none of those posts had an open draft); 12 links typed in rich text left as they are.
  - **/careers/**: Page Header "Looking For A Career Change? Join Arrow [System]!"; Columns 1-1: the form (card) | live's
    illustration (downloaded, alt as live); Rich Text: the open position (R&D Engineer), resume to shaan@arrsys.com.
    Live links it from no menu; none added.
- Live submissions with `04_live_submissions.php` (now downloads file fields' files into the field's volume):
  Free Sample Request **3**, Careers **60** (all 60 CVs downloaded and attached).
- Checked: both pages at 1440 / 390 px (no sideways scroll, no JS errors); a browser submission on each saved (sample
  type, product; the CV in Career CVs), then deleted; the CP's Download returns the PDF; /storage/career-cvs/ is 404.

## Phase 69: Try a Free Sample as the design (2026-10-22)

- Review: "for request a free sample take reference from this page" (Arrowsystem/try-a-free-sample). General parts only:
  - **Page Header → Header Style "Small illustration"** (new option): compact header, the image small at the right as
    decoration, desktop only. Here: the design's sample pack, `pages/free-sample-pack.svg` (taken from the design page).
  - **Form block heading** now has "* Required field" beside it with a line under it (every Form block with a heading,
    e.g. Become a Dealer).
  - **Formie styles for any form** (craft.css): Heading fields = numbered steps (1, 2, 3 + a line); radio buttons = cards,
    an option label `**Title** description` shows the bold part as the title; optional fields' labels end in
    "(optional)" (Contact Us, Request a Quote, Become a Dealer … too); a form whose last field is an HTML field has it
    beside the submit button (with an arrow), stacked on phones.
  - **Side boxes** for an HTML block in a column: `side-stack` with `contact-panel` and `side-card` / `side-links`.
- Free Sample Request form (forms/12): steps "What would you like to try?" · "Your details" · "Your project" (Heading
  fields); Sample type's label hidden (kept "Sample type": the live import matches it), options with the design's lines,
  emails show the plain value; Product hint below the field; Privacy note (HTML field, the Newsletter privacy link);
  button "Request My Sample". Submissions unchanged (same values).
- `storage/imports/pages/22_free_sample_design.php`: the page's blocks rebuilt (stops on an open draft): Page Header
  (small illustration) · Columns 2-1 on grey: form card "Your sample request" | HTML block (Talk to a Specialist panel
  with the Site Settings contacts + Looking for something else?: Request a Quote, Order Supplies, Request Support).
- Not as the design: no icons in the sample cards (Formie options have none), Product is plain text (the design's
  suggestion list isn't a Formie field), the phone keeps its country picker.
- Checked: 1440 / 390 px against the design, no sideways scroll, no JS errors; a card picked by a click and the form
  sent (test deleted); Become a Dealer and Contact Us forms still fine.

## Phase 70: blog cover in a fixed full-width frame (2026-10-22)

- Review: "the banner image is too small but it is being stretched and displaying too large" (e.g. /print-custom-mylar-bags/:
  a 768 × 640 cover shown 1280 × 1067). 146 of 332 covers are taller than 16:9 and 69 are under 1200 px wide.
- `blog/_entry.twig` + craft.css: the cover sits in a fixed full-width frame, **21:9** (1280 × 549 at full width; **4:3** on
  phones); the image is shown whole inside it, at most at its own size (never enlarged or cropped), centred on the
  frame's light grey (a blurred copy of the image behind it was removed on review). Covers wider than 21:9 fill the width.
- Checked: the Mylar post 658 × 549 (was 1280 × 1067); a 1920 × 1080 cover 975 × 549; phones 358 × 269 frame.
- Not changed: Pages' Page Header "Full-width banner" (it has its own "Short strip" height option).

## Phase 71: Navigation without the Categories and Dynamic item types (2026-10-23)

- Review: "remove it completely as they are not in use". No menu item uses them (Entries 54, Custom URL 35, Passive 18,
  Group / Column 9; Categories, Assets, Dynamic 0). `modules/builderui/Module.php` drops Categories and Dynamic from the
  Navigation plugin's item types (its register event), so they're gone from every menu's Add panel and Permissions tab.
  Assets stays (for PDF brochures later). The plugin itself is unchanged; delete those lines to bring them back.
- Checked: Main Menu's Add panel lists Entries, Assets, Passive, Group / Column, Custom URL; all four menus open fine.
  The website guide's Item types stop updated.
- Also removed (review: "remove the partner group toggle from the menus"): the Main Menu items' **Partner group** switch
  (field isPartnerGroup, no item had it on; it only added a border above a Products mega menu group heading).
  `storage/imports/navigation/04_remove_partner_group.php`; `.mega__label--partner` gone from mega-products.twig and
  site.css. Backup: arrow-systems-local-staging--2026-10-23-061200.
- Menu item fields renamed and regrouped (review: "change Second Line to Description; View All Text and its URL together,
  named View All Text URL; meaningful names"), `storage/imports/navigation/05_menu_item_fields.php`. Main Menu Edit panel:
  Label · link · **Open in New Tab** · **Dropdown Style** (level 1, was Menu Style) · **Category Panel Heading** (was Panel
  Title), **View All Text**, **View All Text URL** (new Link field viewAllUrl), **Featured Product** (level 3 Passive =
  Products categories) · **Industry Icon** (level 3 Entry / Custom URL, was Icon) · **Description** (level 4, was Second
  Line). Every field has a one-line help text. Open in New Tab (was New Window) in all four menus, only on items with a
  link. The 4 Products categories that had a link (Digital Label Printers, Label Finishers, Flexible Packaging Printers,
  UV & Direct-to-Package) got it as View All Text URL and became Passive; mega-products.twig reads viewAllUrl (the 5
  "View all" links on the page unchanged). Plugin help texts: `translations/en-US/navigation.php`. Website guide and
  QA_WALKTHROUGH.md use the new names. Backup: arrow-systems-local-staging--2026-10-23-064420.

## Phase 72: Thermal Lamination Film page (2026-10-23)

- From live https://arrsys.com/thermal-lamination-film/ (no design file): the H1 and a grid of 12 films (photo, name,
  Learn More). `storage/imports/pages/23_thermal_lamination_film.php`, general blocks only (as /aviation-films/):
  Page Header "Thermal Lamination [Film]" (eyebrow Materials, no image: live has none) · Recommended Products "Our
  Lamination [Films]": the 10 films with a product page, picked in live order, **4 per row** as live · SEO title and
  description as live. Main Menu › Materials › Thermal Lamination Film now links to the page (was live's address).
- Left out: **PET Pouch Film** and **Hot Stamping Foil**: no product page on live (their Learn More goes to "#").
- Recommended Products got the Card Grid's **Cards per Row** (2 / 3 / 4, default 3: other pages unchanged; large
  screens only, craft.css `.pgrid--2/--4`).
- The BOPP film's **Card Title** = "BOPP Thermal Lamination Film" (live's card name; its product page keeps the full
  name). Backup: arrow-systems-local-staging--2026-10-23-124109.
- Checked: 1440 / 390 px against live, no sideways scroll, no JS errors; /aviation-films/ still 3 per row.

## Phase 73: ArrowJet UV Label Printers page (2026-10-23)

- From live https://arrsys.com/arrowjet-uv-label-printers/ (no design file; the cards are the design's product
  listing cards): the H1, an intro and 3 printers. `storage/imports/pages/24_arrowjet_uv_label_printers.php`, general
  blocks only: Page Header "ArrowJet UV Label [Printers]" + live's intro (eyebrow Products, no image as live) ·
  Recommended Products "Our UV [Printers]": ArrowJet UV 330H, ArrowJet UV, ArrowJet MTS-48 (live order) · SEO as live.
- The 3 products' **Card Tagline** and **Key Specs** (empty before) filled with live's text (4 / 5 / 3 specs); they
  show wherever these products appear as cards.
- Now link to the page instead of live's address: Footer › Products › UV Printers; Main Menu › UV & Direct-to-Package ›
  ArrowJet UV and ArrowJet UV 330H (as before: they pointed at this listing, not their product pages); that category's
  View All Text URL. 13 links typed in rich text (blog posts) left as they are.
- Checked: 1440 / 390 px against live, no sideways scroll, no JS errors. Backup: …-2026-10-23-125508.

## Phase 74: /all-products/ pages, part 1: All Products, Label Finishers, Arrow Label Finishers (2026-10-23)

- Approach (instead of PRODUCT_CATEGORIES_MIGRATION_PLAN.md's category template): **nested Pages** with general blocks.
  The Pages structure gives the live paths from the parent pages: All Products → Label Finishers → Arrow Label
  Finishers = `/all-products/label-finishers/arrow-label-finishers/`; breadcrumbs follow the parents.
- `storage/imports/pages/25_all_products.php` (data-driven: add the other category pages to its PAGES list):
  | Page | H1 (live) | Blocks |
  |---|---|---|
  | `/all-products/` | All Products | Recommended Products "ArrowJet Digital Label [Printers]" (9, View all → live's digital label printers page until built) + "Digital Label [Finishers]" (10, View all → Label Finishers) |
  | `/all-products/label-finishers/` | Label Finishers | "Our Label [Finishers]" (10) |
  | `/all-products/label-finishers/arrow-label-finishers/` | Digital Label Finishing Equipments | "Arrow Label [Finishers]" (10, incl. Arrow Lam 380) |
  SEO titles and descriptions as live.
- Live's mix-ups not copied: "Arrow EZCut 330A" → links to the 350R (350R shown once); "Arrow ezcut 330S" (a sheet
  cutter) → links to the 330R+ (shown as the 330R+); "Aries Laser Label Finishing" → its address redirects to the
  Taurus Laser Finisher (shown as Taurus); "Arrow slitter" → its address redirects to the homepage (shown as Arrow
  Slitter 1500, same photo).
- 11 finishers' product cards filled where empty (live's text): Card Tagline, Key Specs, and a short Card Title where
  the product name is long (Aries Semi-Rotary Finishing, ArrowCut Nova 330R / 250R, Arrow Eco-300, Arrow EZCut 330R,
  Arrow Slitter 1500). The printers already had theirs.
- Now link to the pages: Footer › Label Finishers, Main Menu › Finish Labels, the Label Finishers category's View All
  Text URL, the Label Finishing Equipments category card, a Button in the blog post "Label Printer vs. Sticker
  Printer…" (no open draft). The script skips links under an open draft and says so.
- `config/redirects.php`: live's 301s `label-finishers/lemorau-label-finishers` and `…/quicklabel-label-finishers` →
  `/all-products/label-finishers/`.
- Checked: the 3 pages at 1440 px (hub against live) and 390 px, breadcrumbs, no sideways scroll, no JS errors; both
  redirects 301. Backup: …-2026-10-23-132020.

## Phase 75: /all-products/ pages, part 2: Printers, Gerber Printers (2026-10-23)

- Added to `storage/imports/pages/25_all_products.php` (same approach as Phase 74):
  | Page | H1 (live) | Blocks |
  |---|---|---|
  | `/all-products/printers/` | Printer | "ArrowJet Digital Label [Printers]" (9, live order; View all → live's digital label printers page until built) |
  | `/all-products/printers/gerber-printers/` | Printers by Gerber | "Gerber [Printers]": Gerber EDGE FX (one product: the wide card) |
  SEO titles and descriptions as live (Gerber's description is live's "Printing Technology: Thermal transfer").
- Left out: **ArrowJet VDP** (on live's Printers page): its product address now redirects to the Limitronic printers
  page and the product isn't on staging (staging redirects `product/arrowjet-vdp` the same way).
- Gerber EDGE FX card: tagline + 5 Key Specs from live (were empty).
- Now link here: the "Gerber Printers" buttons on the industry pages Government & Military, Manufacturing & Industrial,
  Signs and Graphics.
- `config/redirects.php`: live's 301s `printers/arrow-printers` → `/all-products/printers/`, `printers/kerajet-printers`
  → `/` (both tested). Checked: both pages at 1440 px, Gerber at 390 px, breadcrumbs, all product photos load, no JS
  errors. Backup: …-2026-10-23-133433.
- Fix (review: "you didn't implement the banner, see the live page"): live's banners are **Elementor section
  backgrounds**, which the page reading missed. Checked every page built today in a browser: two have one, now in their
  Page Header as a Full-width banner shown whole (1300 × 400, text and logos in the image): **Gerber Printers**
  (`GERBER-PRINTERS.jpg`) and **Arrow Label Finishers** (`Label-Finishers.jpg`), Assets › Images › pages. The script's
  PAGES entries take a `banner`; it also adds one to an existing page's header (not under an open draft). All Products,
  Label Finishers, Printers, Thermal Lamination Film and ArrowJet UV have none on live. Backup: …-2026-10-23-141403.

## Phase 76: /all-products/ pages, part 3: Trojan Printers, QuickLabel Printers (2026-10-26)

- Live https://arrsys.com/all-products/printers/trojan-printers/ and …/quicklabel-printers/ are **empty**: only the H1
  ("Printers by Trojan" / "Printers by QuickLabel"), no products, no description, no banner (checked in a browser; the
  Wayback Machine shows them empty since at least April 2024). No menu on live or staging links to them (nor to
  /all-products/printers/); live's sitemap lists them.
- Added to `storage/imports/pages/25_all_products.php`, filled with the brands' printers on staging:
  | Page | H1 | Products |
  |---|---|---|
  | `/all-products/printers/trojan-printers/` | Printers by [Trojan] | Trojan T2-L, Trojan T2-C, Trojan T4 |
  | `/all-products/printers/quicklabel-printers/` | Printers by [QuickLabel] | QuickLabel QL-300 (wide card) |
  SEO titles as live (live has no descriptions). **Trojan T2** left out: switched off on staging on 2026-10-22 (by
  hand; still live on arrsys.com).
- Product cards: T2-L and T2-C tagline + Key Specs from live's "Digital Label Printers by Trojan" page (typos fixed:
  "dp" → "dpi", a missing bracket); Card Titles "Trojan T2-C", "Trojan T4", "QuickLabel QL-300"; T4 and QL-300 have no
  live listing, so only their subtitle ("All-in-One Digital Label Printer and Finisher", "5 Color Laser Label
  Printer"). Checked: both pages at 1440 px, Trojan at 390 px, breadcrumbs, photos, no JS errors. Backup:
  …-2026-10-26-054908.

## Phase 77: 404 and 403 pages in the site design (2026-10-26)

- Craft showed its own error pages. Now `templates/404.twig` (Page Not [Found]) and `templates/403.twig` (Access
  [Denied]) use one layout, `templates/_layouts/error.twig`: the site header, footer and newsletter; a page header in the
  industry pages' look with "Error 404/403", the heading, a plain message, **Go to Homepage** and **Contact Us**, the
  toll-free number (Site Settings), and the status code drawn large and outlined (maroon) at the right (above the text
  on phones); then **Popular Pages**: All Products, Industries, Guides & Articles, Support, Request a Quote, Try a Free
  Sample (link cards; a page that doesn't exist is left out). `noindex, follow`; the real 404 / 403 status is kept.
  Styles: craft.css (`.error-hero`, `.error-links`). The wording is in 404.twig / 403.twig (one line each).
- **Dev mode:** with `CRAFT_DEV_MODE=true` (local staging) Craft shows its developer error screen with the stack trace
  for every error, by design; these pages show when dev mode is off (QA, production). Checked on a temporary server
  with dev mode off: `/this-page-does-not-exist/` → 404 with this page, `/actions/utilities/index` (logged out) → 403
  with this page; 1440 / 390 px, no JS errors.
- Also in dev mode (review: "the new 404 page is not working" on staging): `modules/web/ErrorHandler.php` (Craft's error
  handler, registered in the new `config/app.web.php`) shows the 404 / 403 pages for site requests even with dev mode
  on; every other error (500s …) still gets Craft's developer screen with the stack trace. Checked on staging (dev
  mode on): `/gerber/plotters` → 404 page, `/actions/utilities/index` → 403 page, homepage and admin login unchanged.
- Admin panel too (review: a Reviewer opening Settings got Craft's developer screen / bare "Unauthorized" box): for
  admin-panel page requests the handler renders the same 403 / 404 template (site mode, so Site Settings and menus
  load) with `inCp`: a staff message ("Your account doesn't have permission to open this part of the admin panel. Ask
  an administrator…"), **Back to Dashboard** and **Go to Website**, no Popular Pages. JSON requests keep Craft's JSON
  errors; if the page fails, Craft's own page is shown. The base and error layouts now link CSS / JS with
  `siteUrl('assets/…')` (was `url()`, which points under /admin/ during admin requests; same addresses on the site).
  Checked as qa_reviewer: /admin/settings → 403 page, /admin/this-does-not-exist → 404 page, JSON request → JSON 403;
  website pages unchanged.

## Phase 78: /customer-service/ restored (2026-10-26)

- Review: "the design of this page is broken". Its revision history: on 2026-10-21 16:18 (IST) one save from the control
  panel ("Applied Draft 1", admin account), while Custom CSS was being tried out, deleted the **Form block** "Fill Up Our
  Customer Service form", added an **empty Columns** block and an **empty Data Table** (8 blank columns: the empty box
  of lines), gave the Page Header the class `contact-us-section` and the page the CSS `.contact-us-section {
  background: red; }` (the red header). No script changed the page then.
- `storage/imports/pages/26_restore_customer_service.php`: the Form block back from the trash (the same block, the
  Customer Service form), the two empty blocks to the trash, the test class and CSS removed. Page = Page Header + Form,
  as built in Phase 45. Checked at 1440 px. Backup: …-2026-10-26-071129.

## Phase 79: /all-products/plotters/ (Gerber Plotters) (2026-10-26)

- From live https://arrsys.com/all-products/plotters/ (H1 "Gerber Plotters", 3 plotters with tagline + 4 specs, a
  section-background banner `Gerber-Envision-375-Plotter.jpg`, found with the browser check). Added to
  `storage/imports/pages/25_all_products.php`: Page Header "Gerber [Plotters]" with the banner (Full-width, whole) ·
  Recommended Products "Our Gerber [Plotters]": Gerber GS15plus, Gerber Envision 375, Gerber GSxplus (live order) · SEO
  title and description as live.
- The 3 plotters' cards (empty before): tagline + Key Specs from live (live's "13.25” 327 mm)" → "13.25″ (327 mm)").
- Now link here: Footer › Products › Plotters, Main Menu › Label Finishers › Gerber Plotters (were live's address).
- Checked: 1440 px against live, 390 px, breadcrumb Home › All Products › Gerber Plotters, photos, no JS errors.
  Backup: …-2026-10-26-071431.

## Phase 80: Digital Label Printers listing (2026-10-26)

- Live https://arrsys.com/all-products/digital-label-printers/arrow-digital-label-printers/ (its parent
  `/all-products/digital-label-printers/` 301s to it), built as the design `Arrowsystem/digital-label-printers`
  (client-revised 2026-10-16); SEO title and description as live.
- **Page Header, two general options** (`storage/imports/pages/27_header_subtitle_cutout.php`,
  `industries/blocks/industryHeader.twig`, `craft.css`): **Subtitle** (the existing Subheading field: an H2 line under the
  heading) and Header Style **"Cut-out image beside text"** (a product cut-out shown whole and large beside the text, no
  frame or crop marks; hidden on phones and tablets, as the design's `.cat-hero__art`). Any page can use them.
- `storage/imports/pages/25_all_products.php` now also takes `header` (more Page Header fields), `after` (Card Grid /
  CTA Band sections after the products) and `enabled => false`. Added:
  - `/all-products/digital-label-printers/`: switched off, + redirect in `config/redirects.php` (as live)
  - `/all-products/digital-label-printers/arrow-digital-label-printers/` "ArrowJet Digital Label Printers": Page Header
    (subtitle, intro, the Aqua 330R cut-out) · Recommended Products "ArrowJet Printer [Lineup]" (the design's 12, its
    order) · Card Grid (the 3 "about the range" paragraphs) · Card Grid "Built for Commercial [Label Production]" · CTA
    Band "Not sure which printer fits?" (Talk to a Specialist tel, Request a Quote page) · Card Grid "Other [Categories]"
    (Digital Label Finishers → staging page; Print & Cut Inline and Label Materials → live, not built yet).
  - Images from the design into Assets › Images › pages: arrowjet-aqua-330r, category-digital-label-finishers,
    category-print-cut-inline, material-holography-1 (.webp).
- Now link here: Main Menu "Print Labels", Footer "Digital Label Printers", the Products mega menu's View All, the
  "View all digital label printers" links on All Products and Printers, product category "Digital label Printers", 28
  blog posts' Button blocks. **Not relinked (open drafts):** Homepage, blog post "10 Step Guide For the best Frozen Food
  Pouches…": apply or delete the drafts, then run 25 again.
- Left out (no block fields for them): the design's search box and pagination (12 fit one page), "12 printers" line,
  section eyebrows ("Who it's for", "Keep exploring"), card kickers and "View …" card links, centred headings.
- Checked: 1440 px against the design, 390 px, breadcrumb Home › All Products › ArrowJet Digital Label Printers, parent
  301. Backups: …-2026-10-26-072631 (before 27), …-2026-10-26-073223 (before the page).

## Phase 81: Limitronic listing (2026-10-26)

- Live https://arrsys.com/all-products/corrugate-and-end-of-line-printing/printers-by-limitronic/ (no design page; built
  as the other brand pages). Added to `storage/imports/pages/25_all_products.php`:
  - `/all-products/corrugate-and-end-of-line-printing/`: **switched off placeholder** (the Corrugate listing, #13, not
    built yet; 404 until then), there so the Limitronic page gets its live path. Building #13 means switching it on and
    adding its blocks (the script skips pages that exist).
  - `…/printers-by-limitronic/` "Printers by Limitronic": Page Header "Printers by [Limitronic]" with live's
    section-background banner `LIMITRONIC-3.jpg` (Full-width, whole; found with the browser check) · Recommended Products
    "Limitronic [Printers]": V6 Lite, V6 Standard, V7 Color, V7 White, V6 Color (live order) · SEO title and description
    as live.
- The 5 products' cards (empty before): tagline + 4 Key Specs from live; live's split lines joined ("0.2-192 m/min" +
  "depending resolution" → "0.2–192 m/min depending on resolution"), "injket" → "inkjet", "5 0 mm" → "50 mm". The cards
  show each product's own photo (live's listing uses other photos for V7 Color and V7 White).
- Now link here: Main Menu "Corrugated & End-of-Line Printers" and "Industrial & End-of-Line Printing" (were live's
  address); `config/redirects.php` `product/arrowjet-vdp` → this page on staging (was live's address).
- Checked: 1440 px, 390 px, breadcrumb Home › All Products › Printers by Limitronic (the switched-off parent is left
  out), placeholder 404, `/product/arrowjet-vdp/` 301 here. Backup: …-2026-10-26-075535.

## Phase 82: Corrugate and End of Line Printing listing (2026-10-26)

- Live https://arrsys.com/all-products/corrugate-and-end-of-line-printing/ (no design page, no banner: browser check):
  H1 "Corrugate and End of Line Printing" + "Printers By Limitronic", the same 5 printers as the Limitronic page.
- `storage/imports/pages/25_all_products.php`: the Phase 81 placeholder is built in place (a switched-off page whose spec
  is no longer switched off: its blocks replaced, SEO set, switched on; stops on an open draft). Page Header
  "Corrugate and End of Line [Printing]" · Recommended Products "Printers by [Limitronic]" (live order; the cards filled
  in Phase 81) · SEO title as live; SEO description as live too, "Printhead resolution: 180dpi" (live's own, worth a
  better one).
- The Limitronic page's breadcrumb now shows it: Home › All Products › Corrugate and End of Line Printing › Printers by
  Limitronic.
- Checked: 1440 px against live, 390 px, the page = Page Header + Recommended Products only. Backup: …-2026-10-26-075914.

## Phase 83: Homepage hero, revised design: slim slide captions + intro row (2026-10-26)

- Design `Arrowsystem/index.html` + `css/home.css` (2026-10-23): each slide's caption is one slim line (tag · product
  name · "Explore product" link, no buttons), and a fixed row under the slider holds the page's only H1, a description
  and two buttons (Book a Demo, Try a Free Sample). The equipment heading below became an H2.
- `storage/imports/homepage/02_hero_intro.php` (existing fields only):
  - Hero Slide: Category Label → **Tag Name**, Primary Button → **Explore Product Link** (its Label = the link text),
    Secondary Button taken off the slide (slide 6 had a test value "try now": no longer shown).
  - Hero Slider block: + **Intro under the slider**: Heading (the H1), Description, First Button, Second Button.
  - Content into the homepage's Hero Slider block: the design's heading and description, Book a Demo →
    `mailto:sales@arrsys.com?subject=Demo%20request` (the design's interim, no demo page yet), Try a Free Sample →
    /try-a-free-sample/. Written with the user's OK while the admin's unsaved homepage draft (Draft 325, 2026-10-23) was
    open: that draft only has its own copy of the Benefit cards, the Hero Slider block is shared, so applying it keeps
    the intro. The script stops if a draft has its own copy of the Hero Slider block.
- Templates: `_blocks/home/hero-slider.twig` (caption line, intro row after the slider), `_blocks/home/category-showcase.twig`
  (H1 → H2 `.section-title--xl`). `site.css`: the hero caption / controls / intro CSS = the design's.
- Not as the design: the video slide (slide 1) still shows its caption ("Watch it run · ArrowJet MTS-48 in Action"),
  the design has none there: clear its Tag Name and Product Name to hide it. Slide links read "Explore Product" (the
  slides' Label), the design "Explore product".
- Checked: 1440, 1100 and 390 px against the design, one H1, 6 caption lines for 6 slides, project config in sync.
  Backup: …-2026-10-26-091155.

## Phase 84: Intro under the slider, per slide (2026-10-26)

- Review: "support of Intro under the slider for each slide, from admin as well as frontend". Supersedes Phase 83's one
  fixed row on the Hero Slider block (`02_hero_intro.php` steps 2–3: don't run 02 again).
- `storage/imports/homepage/03_hero_slide_intro.php` (existing fields only):
  - Hero Slide: + **Intro under the slider**: Intro Heading (`sectionHeading`), Intro Description (`sectionLead`), Intro
    First Button (`ctaLink`), Intro Second Button (`secondaryCta`), before Display Time.
  - The block's intro copied to all 6 slides (same text and buttons for now; Book a Demo stays an email link, Try a
    Free Sample an entry link), then the block's intro fields taken off.
  - Same open admin draft as Phase 83 (Draft 325, only the Benefit cards): the Hero Slider block and its slides are
    shared, so applying it keeps the slides' intros. The script stops if a draft has its own copy of the block.
- Front end (`_blocks/home/hero-slider.twig`, `site.css`): the intro row sits inside its slide, under the caption, and
  swaps with it (same fade as the caption; hidden slides are inert, so their buttons can't be tabbed to). The first
  slide with an intro has the H1, the others an H2 with the same look. A slide with an empty Intro Heading has no row.
- Checked: in Chrome, slide 1's intro shown and the rest hidden + inert; after Next, only slide 2's; one H1; 1440 and
  390 px as before; project config in sync. Backup: …-2026-10-26-092046.

## Phase 85: Switched-off homepage gives a 404, not an error (2026-10-26)

- Review: switching the Homepage single off showed "Variable "entry" does not exist in "index" at line 7". With no
  enabled entry at `/`, Craft falls back to rendering `templates/index.twig` as a plain template, without `entry`
  (with dev mode off it would have been a 500). The other section templates start with `_`, so only `index.twig` had this.
- `templates/index.twig`: `{% if entry is not defined %}{% exit 404 %}{% endif %}` before the first use of `entry`, so
  `/` shows the site's 404 page while the homepage is off.
- Checked: rendering `index` without an entry raises the 404 (Craft's ErrorHandler unwraps it from Twig); `/` with the
  homepage on is still 200 with its 6 slide intros. No content or config changes.

## Phase 86: Links to live's all-products pages → ours (2026-10-27)

- Review: "fix the navigation for product detail page or anywhere we are calling live site's all-product page".
- Templates: product pages' breadcrumb "Products" (link + BreadcrumbList schema) → `/all-products/`
  (`products/_entry.twig`); blog sidebar Product Categories → our Digital Label Printers (the ArrowJet listing, as the
  redirect), Label Finishers and Gerber Plotters pages (`blog/_partials/listing.twig`).
- Content (`25_all_products.php`, rerun): its link step now also rewrites rich text (blog bodies, intros) to the
  page's path, and maps the switched-off Digital Label Printers address to where `config/redirects.php` sends it.
  123 links now point here: blog bodies and intros, and the homepage's Category Showcase and Limitronic Text & Media
  buttons (no longer under a draft).
  - Fix in the same step: a block that belongs to a draft (its owner is the draft, not the post) was not seen as
    "under a draft"; the check now uses the owner's canonical entry. The first run had saved 2 such draft blocks
    (Why Cannabis Labels Fail…, Pharmaceutical Label Version Control…): put back exactly from the backup.
- Left on live: links to pages not built on staging (Flexible Packaging Printers — menu + 20 blog links, Fume
  Extraction, Print & Cut Systems, live's brand pages under digital-label-printers), and the links in 4 posts with an
  open draft (10 Step Guide…, Blade vs. Laser…, Why Cannabis Labels Fail…, Pharmaceutical Label Version Control…):
  apply or delete those drafts, then rerun 25.
- Checked: product breadcrumb and schema → `/all-products/`; sidebar and rewritten post links load (200); a rescan
  finds no other link to a built page on live; script self-check passes; rerun changes nothing. Backup: …-2026-10-27-065518.

## Phase 87: Menu Save no longer switches new items on (2026-10-27)

- Review: a new menu item shows as off until the menu's Save, and Save then switched it on even though its Enabled
  switch was off. Navigation (4.0.7) stages each added item switched off with a "waiting" flag, and Save switched
  every waiting item on (`Node::publishPendingAdd()`).
- `modules/builderui/Module.php` (not vendor, so `composer install` keeps it): before a menu item is saved, if it was
  waiting and this save publishes it, it stays off. An item whose switch is turned on and saved (its slide-out Save,
  or Enable in the builder) goes live right then and stops waiting, so only items with the switch off reach Save.
- Also applies to duplicated items (staged the same way): they stay off after Save until switched on.
- Checked (rolled back, real staging/publish code, Legal Menu): an item left off stays off after Save; one switched on
  directly and one switched on in the slide-out (draft applied) are on; without the hook the first comes out on
  (the bug). No content or config changes. Backup: …-2026-10-27-085353.

## Phase 88: Buttons keep their space above when the text above is empty (2026-10-27)

- Review: the Page Header's buttons sat right under the intro (0px); admins had to add the space with custom CSS.
  The design spaced buttons with the field above them (the Page Header's Applications tags), so leaving that field
  empty put the buttons on the text. An audit of every button and link on 207 pages (1280 and 390 px), plus each
  block with its optional text removed, found the same in 5 more places.
- `web/assets/css/craft.css` ("Space above buttons"): each now has its own gap, the design's gap with the field filled.
  - Page Header: 26px under the intro / subtitle / heading (was 0; with tags still 26).
  - CTA Bands (page builder, homepage, product pages, Upcoming Exhibitions) without an Intro: 28px under the heading.
  - On-Demand Feature without a note: 24px under the facts.
  - Background Video section without a Button Intro: 24px (was 14; still 14 right under a Button Intro).
  - Blog sidebar box without text: 18px; Exhibitions / Upcoming heroes without an intro: 30px.
- Unchanged (already spaced by themselves): product header, article Button block, post CTA, Locations, Text + Media
  and Content Block links, form submit buttons, the hero slider.
- Checked: every case above goes from 0 (or 14) to the gap listed, and every case with the field filled measures the
  same as before. No custom CSS on the site sets button spacing, so nothing doubles. No content changes.

## Phase 89: Answers module, the live /answers/ hub + 30 answers (2026-10-27)

Live https://arrsys.com/answers/ is a WordPress page with 30 child pages (24 in the design, 6 added on live on Oct 20
and 26), each built from hand-written Elementor HTML widgets. Built as **its own module** (`PAGES_MIGRATION_PLAN.md`
D1: own section, not nested Pages), content from general page-builder blocks only. Scripts in
`storage/imports/answers/` (data: `cache/`, fetched 2026-10-27; backup before: `…-2026-10-27-114835`):
- `01_structure.php`: **Entries → Answers** (`answers`, Structure, 1 level: drag = listing order, URL
  `answers/{slug}`, `answers/_entry` = the Pages / industries template) · **Answer** entry type: Answer Title, Slug ·
  Card: Card Summary, Card Icon · Page Builder (the shared `pageBuilder`) · SEO · **Answers Page** single (`answers`,
  `answers/_index`, the design's listing: header, search + 9 per page from the design's `answers.js`, help band:
  small heading, heading, text, button + the toll-free phone). Card Icon = the menus' icon list + 11 design icons (also
  in the icon sprite). General options added: **Small Heading** on Heading, Card Grid and CTA Band; **Section Link** on
  Card Grid (at the right of the heading, as Recommended Products).
- `02_content.php` (`--dry` prints the mapping; `--update` rebuilds, stops on open drafts): reads each live widget by
  structure, not class names: short answer → Callout · H1 + audience + button + product photo → Page Header · criteria
  → Card Grid · product fits → Card Grid with photos, fit label as subtitle, tags as a list, link · decision table →
  Heading + intro + Data Table · workflow → Heading + Steps (titled steps) or a numbered list, + the related links ·
  questions → Heading (#faq) + FAQ (+ the "Next step" band some pages put in that widget) · CTA → CTA Band · linked
  answers → Card Grid. SEO from each live head (title only when not the default, description, robots when not the site
  default, og:image as Social Image), live publish date (America/New_York), hub order + card text. Links to staging
  pages root-relative; 2 broken live links (`/contact-us//-us/`, a doubled all-products path) → their page.
- `03_menus.php`: Main Menu and Footer "Answers" (Custom items without a URL) → the Answers Page.
- Answers get **FAQPage** structured data from their FAQ blocks (`industries/_entry.twig`, answers only).
- The CP Outline names blocks by their heading, not the Small Heading (`navigator.js`).
- `verify.py`: /answers/ 200 with 30 cards in the live order; 30 × 200; SEO 0 differences (29 compared); every live
  text found except: the hero product photo's label (and on the 6 newer pages its "Equipment to evaluate" note), the
  "The short answer" tag, and the cards' "View Product Page / Request Quote" buttons (the cards link to the product).
  FAQPage on every answer. Create / edit / delete of an answer checked (test entry hard-deleted); CP form + Outline;
  1440 / 390 px; search and pages; other modules 200.
- Open: `reliable-color-consistency-digital-label-printers`: live sent its bot check for the page's `<head>`, so its
  SEO title / description are not set yet (re-fetch `cache/<slug>.html`, then `02_content.php --slugs=… --update`).
  Links to 6 live pages not on staging yet stay on arrsys.com (anytron-products, arrow-materials, flexible-packaging …).

## Phase 90: Digital Label Printers by Trojan (2026-10-27)

- Live https://arrsys.com/all-products/digital-label-printers/trojan-digital-label-printers/ (no design page; no banner:
  browser check): H1 "Digital Label Printers by Trojan" + 3 printers. Added to `storage/imports/pages/25_all_products.php`
  under the switched-off `/all-products/digital-label-printers/` (breadcrumb Home › All Products › this page): Page Header
  "Digital Label Printers by [Trojan]" · Recommended Products "Trojan Digital Label [Printers]": Trojan T2-L, Trojan T2-C
  (their cards were filled from this live page in Phase 76) · SEO title and description as live.
- Review: "you still missed one product from the live": **Trojan T2** is now on the page too (3 printers, live order). It
  had been switched off on staging ("Draft 1" applied in the CP on 2026-10-16); the script switches it on (`SWITCH_ON`;
  /product/trojan-t2/ → 200, as live) and fills its card from live (same text as the T2-C). Trojan Printers
  (`/all-products/printers/trojan-printers/`) gets it as well (T2-L, T2-C, T2, T4). The script now also updates an
  existing page's product list when its spec names more products (only when the spec covers every product on the page;
  not under an open draft). Backup: …-2026-10-27-130340.
- Links now pointing here: the blog post "The Beginner's Guidebook To Digital Printing" (was live's address). The same
  run also relinked the 7 posts imported today (Blog top-up 2) to the /all-products/ pages built before; links under
  open drafts left (Blade vs. Laser…, Why Cannabis Labels Fail…, 10 Step Guide…, Pharmaceutical Label Version Control…).
- Checked: 1440 / 390 px, breadcrumb, SEO, the parent still 301s. Backup: …-2026-10-27-125654.

## Phase 91: Contact Us admin email template; no visitor email (2026-10-27)

- Live's Contact Us form (Elementor) sends one email: To sales@arrsys.com, Subject "New Contact Form Submission", From
  email@arrsys.com / "Arrow Systems, Inc.", Reply-To email@arrsys.com, no Cc / Bcc, no copy to the visitor (read from
  live's form settings, screenshots from the user).
- `storage/imports/forms/13_contact_us_email_template.php` (idempotent): Formie → Settings → Email Templates →
  **Contact Us – Admin** (`contactUsAdmin`) = `templates/_emails/contact-us-admin.twig`; the Contact Us **Admin
  Notification** uses it, Subject "New Contact Form Submission" (as live), Content = a short intro (the template lists
  the fields itself); the **User Notification** is switched off (as live; switch it on in the form builder if wanted).
  Formie links templates per notification (Notifications → a notification → Templates → Email Template), not per form.
- The template: email-safe HTML (tables, inline styles, 600 px): the logo (`web/assets/email/arrow-systems-logo.png`, a
  PNG copy: Outlook doesn't show WebP), form name, subject as heading, the notification's Content, every filled-in field
  (label + Formie's value output, line breaks kept), a "Reply to <visitor email>" button, footer with the date and a
  link to the submission in the admin panel.
- Unchanged on purpose: To (the dummy inbox until launch, D8; live: sales@arrsys.com), From (site default; live:
  email@arrsys.com), Reply-To (the visitor; live: email@arrsys.com).
- Checked: rendered for a test submission (not saved, not sent) at 600 and 400 px; second run changes nothing.
  Backup: …-2026-10-27-134548.

## Phase 92: Exhibitions header buttons managed in the CP (2026-10-28)

- Review: the Exhibitions page's Contact Link "is not reflecting on the frontend": it only showed on the next-show ticket,
  hidden while no show is upcoming (PACK EXPO International, the last one, ended 2026-10-21); the header's "Upcoming
  Exhibitions" / "Past Exhibitions" buttons were fixed in the template.
- `storage/imports/exhibitions/03_header_buttons.php`: Exhibitions page → **First Button** (the Contact Link, relabelled;
  still the next-show ticket's Contact button too) + **Second Button** (`secondaryCta`, filled "Past Exhibitions" →
  `#past`). `exhibitions/_index.twig`: the header shows each button when it has a text and a URL, no other condition
  (first red, second outline); the fixed buttons are gone.
- Checked: both set / first without text / second without URL / neither → 2 / 1 / 1 / 0 buttons (no empty row).
  Backup: …-2026-10-28-051507.

## Phase 93: The admin email template for every form (2026-10-29)

- The Phase 91 template had nothing Contact Us-specific (it lists the form's own fields, every form has `emailAddress`
  for the Reply button), so all forms share it: renamed **Admin Notification** (`adminNotification`) =
  `templates/_emails/admin-notification.twig` (YAML + file renamed; 13 updated to the new handle). It now also leaves
  out fields set to not "Include in Email" or hidden by conditions, as Formie's `{allFields}` does.
- `storage/imports/forms/14_admin_email_all_forms.php` (idempotent): the Admin Notification of Customer Service,
  Request a Quote, Become a Dealer, Product Request Quote, Request Quote From Blog, Free Sample Request, Careers uses
  it; Subject per form ("New Quote Request", "New Job Application", …); Content = a short intro instead of Formie's
  default text with `{allFields}` (would have listed the fields twice). Careers › Upload Your CV: email link = the
  control panel URL (CV volume has no public URLs, the link was empty); the CV is still attached.
- Unchanged: recipients, Reply-To, User Notifications (still on for the 7 forms).
- Code changed on the server directly (template, YAML, project.yaml), local copy identical. Checked: all 8 rendered for
  their newest submission (not sent), all 8 screenshotted; second run changes nothing. Backups: …-2026-10-29-063849
  (before), …-2026-10-29-063952 (after). Server: no DB import (user); Apply YAML Changes + the same settings set by
  hand in the server CP.

## Phase 94: Each form its own admin email template (2026-10-29)

- User: a separate template per form, same design for now, so one form's email can change later without the others.
- `templates/_emails/admin-notification.twig` is the shared design, now in blocks (`header`, `intro`, `details`,
  `reply`, `footer`). `templates/_emails/admin/<form>.twig` (8 files) only extend it; to change one form's email,
  override a block in its file (e.g. `{% block reply %}{% endblock %}` drops the Reply button).
- `storage/imports/forms/15_admin_email_template_per_form.php` (idempotent): Formie → Settings → Email Templates →
  **<Form> – Admin** (`<formHandle>Admin`) per form, and its Admin Notification uses it. "Admin Notification" stays
  registered (unused) for a new form. 13 and 14 no longer set the template (re-running them can't undo 15).
- Checked: all 8 render the same email as before (only whitespace differs); a block override changes only that part;
  13 → 14 → 15 re-run changes nothing. Backup before: …-2026-10-29-071516.
- Server: template files, YAML and project.yaml copied; Apply YAML Changes + pick each form's own template in its
  Admin Notification in the CP (no DB import).

## Follow-ups

| # | Item | Where |
|---|---|---|
| 1 | **Move the SMTP password out of project config.** It's saved in plain text in `config/project/project.yaml`. Put the host, username and password in `.env` and enter `$SMTP_…` variables under Settings → Email. Also consider changing that password. | Settings → Email |
| 2 | **Editor group has "Perform updates"**, which lets editors update Craft and plugins. Remove it unless intended. | User Groups → Editor |
| 3 | **Give Editor the content permissions:** Homepage section (view, save, drafts), Site Settings global set, Images volume. | User Groups → Editor |
| 4 | **MTS-48 banner is 2.4:1** (2560 × 1067) while the others are 16:5. Ask for a 16:5 version (e.g. 1600 × 500) and replace the file. | Assets → Images |
| 5 | Rename entry type "Hero **SL**ide" → "Hero Slide" (the handle `heroSlide` stays). | Settings → Entry Types |
| 6 | Optional: on Hero Slide's layout, set **Banner Image** to provide the element thumbnail, so slide cards show the banner. | Entry type → field ⚙ |
| 7 | There's an empty, inactive user record without a username (ID 3), probably an unfinished new user. Delete it if it's not needed. | Users |
| 8 | Before going live: buy a **Craft Pro** license ($399, then $99/year; user groups need it) and a **Navigation** license ($19, then $5/year), and attach them on the production domain. Prices checked 2026-10-13. | Plugin Store |
| 9 | ~~Neo license~~: **not needed** any more, the blog's page builder is Matrix since Phase 14. | — |
| 10 | Before going live: make sure the server's PHP image library supports **WebP** (GD with WebP or Imagick), so blog thumbnails are real crops. | Server |
| 11 | **Request A Sample** form on product pages: connect it to HubSpot (portal ID / form GUID), as the design notes. Until then it validates and says it isn't connected. | `web/assets/js/products/product-detail.js` |
| 12 | **Product card taglines:** only the design's 12 listing products have one (WooCommerce has no short descriptions). Fill in Card Tagline / Key Specs as products appear on listings. | Entries → Products → Card |
| 13 | Live content worth a look (shown as published): tab title typo "Specificaion" (Limitronic V6 Lite / V6 2 Color), Lumina 48F "Brochure" tab without a file, Limitronic spec groups without content, the OverJet SP-800M workflow widget and MTS-48 / Matan slides (not imported). | Entries → Products |
| 14 | **Contact form on industry pages**: connect to HubSpot (same as the sample and newsletter forms). | `web/assets/js/industries/industry-detail.js` |
| 15 | Industry pages imported from live: give the headers **Applications** tags and the Recommended Products **intros / View All links**, and replace the live product-teaser headings where wanted (the pilot shows the target). Product cards of non-design products still need taglines / key specs (follow-up 12). | Entries → Industries |
| 16 | **12 blog drafts** on live (unpublished, not in the public API): send a WordPress export (Tools → Export → Posts, status Drafts) or an application password, then import them as disabled entries. | WordPress admin |
| 17 | ~~**Per-product sample / quote requests**~~ — done in Phase 63 (one form + hidden Product); open: quote pre-fill, Try a Free Sample page, live submissions import. (deferred 2026-10-22). Live product pages each embed a "Request A Sample" form (Name, Email, Contact Number, Country/Region) tied to the product by its page; staging's product buttons are plain links, so requests don't record the product. Proposed: one Formie form "Request a Sample" with a hidden Product field filled per product page (`craft.formie.populateFormValues`), and Request a Quote → `/request-quote/?product=…` pre-filling Product Required; import the live sample submissions later (`04_live_submissions.php`). | products/_entry.twig, Formie |

---

## Backups taken (`storage/backups/`)

| File | Taken before |
|---|---|
| `arrow-systems-local-staging--2026-10-12-090626--v5.11.4.sql` | Copying Site Settings |
| `arrow-systems-local-staging--2026-10-12-095955--v5.11.4.sql` | Creating the hero fields |
| `arrow-systems-local-staging--2026-10-12-110327--v5.11.4.sql` | Adding the hero slider content |
| `arrow-systems-local-staging--2026-10-12-112049--v5.11.4.sql` | Adding Category Label + Product Name |
| `arrow-systems-local-staging--2026-10-12-113541--v5.11.4.sql` | Button fields: URL as default link type |
| `arrow-systems-local-staging--2026-10-12-120018--v5.11.4.sql` | Installing Navigation |
| `arrow-systems-local-staging--2026-10-12-120104--v5.11.4.sql` | Copying the menus and items |
| `arrow-systems-local-staging--2026-10-12-120603--v5.11.4.sql` | Featured box fields + front-end menus |
| `arrow-systems-local-staging--2026-10-12-120921--v5.11.4.sql` | Aligning the menu content with the design |
| `arrow-systems-local-staging--2026-10-12-125747--v5.11.4.sql` | Benefit Cards structure and content |
| `arrow-systems-local-staging--2026-10-13-051112--v5.11.4.sql` | On-Demand Feature (section D) |
| `arrow-systems-local-staging--2026-10-13-054704--v5.11.4.sql` | Worldwide Locations: map file + fields |
| `arrow-systems-local-staging--2026-10-13-060408--v5.11.4.sql` | Worldwide Locations: 17 locations + section content |
| `arrow-systems-local-staging--2026-10-13-061437--v5.11.4.sql` | One Stop Solution: poster + fields |
| `arrow-systems-local-staging--2026-10-13-062250--v5.11.4.sql` | One Stop Solution: handle-override fix |
| `arrow-systems-local-staging--2026-10-13-063558--v5.11.4.sql` | End-of-Line Marking (section L) |
| `arrow-systems-local-staging--2026-10-13-063925--v5.11.4.sql` | Industrial Printer Distributor (section M) + Layout option |
| `arrow-systems-local-staging--2026-10-13-064318--v5.11.4.sql` | Newsletter: fields |
| `arrow-systems-local-staging--2026-10-13-100755--v5.11.4.sql` | Blog: installing CKEditor + Neo, URL settings |
| `arrow-systems-local-staging--2026-10-13-101540--v5.11.4.sql` | Blog: fields, sections, Neo field (`01_structure.php`) |
| `arrow-systems-local-staging--2026-10-13-102802--v5.11.4.sql` | Blog: categories and tags (`03_taxonomy.php`) |
| `arrow-systems-local-staging--2026-10-13-103332--v5.11.4.sql` | Blog: 10-post trial import |
| `arrow-systems-local-staging--2026-10-13-103806--v5.11.4.sql` | Blog: full import |
| `arrow-systems-local-staging--2026-10-13-111238--v5.11.4.sql` | Blog: re-import of 70 posts (cover / image fixes) |
| `arrow-systems-local-staging--2026-10-13-112457--v5.11.4.sql` | After the blog migration (finished state) |
| `arrow-systems-local-staging--2026-10-13-114145--v5.11.4.sql` | Before moving the page builder from Neo to Matrix |
| `arrow-systems-local-staging--2026-10-13-115910--v5.11.4.sql` | Content copied to Matrix, before switching the field and removing Neo |
| `arrow-systems-local-staging--2026-10-13-121515--v5.11.4.sql` | After Phase 14 (Matrix builder, Neo removed) |
| `arrow-systems-local-staging--2026-10-14-055848--v5.11.4.sql` | Before the cleanup (Phase 15) |
| `arrow-systems-local-staging--2026-10-14-060655--v5.11.4.sql` | After the cleanup (Phase 15) |
| `arrow-systems-local-staging--2026-10-14-063619--v5.11.4.sql` | Exhibitions: before the structure (Phase 16) |
| `arrow-systems-local-staging--2026-10-14-064525--v5.11.4.sql` | Exhibitions: before the content import |
| `arrow-systems-local-staging--2026-10-14-065535--v5.11.4.sql` | Exhibitions: before moving the clashing blog post's slug |
| `arrow-systems-local-staging--2026-10-14-065957--v5.11.4.sql` | Exhibitions: before the Canonical URL field |
| `arrow-systems-local-staging--2026-10-14-070716--v5.11.4.sql` | After Phase 16 (exhibitions) |
| `arrow-systems-local-staging--2026-10-14-093752--v5.11.4.sql` | Before product categories (Phase 17) |
| `arrow-systems-local-staging--2026-10-14-094001--v5.11.4.sql` | After Phase 17 (product categories + section C) |
| `arrow-systems-local-staging--2026-10-14-100954--v5.11.4.sql` | Products: before the structure (Phase 18) |
| `arrow-systems-local-staging--2026-10-14-101938--v5.11.4.sql` | Products: before the content import |
| `arrow-systems-local-staging--2026-10-14-102822--v5.11.4.sql` | Products: before the re-import with fixes |
| `arrow-systems-local-staging--2026-10-14-102914--v5.11.4.sql` | Products: before linking the menus |
| `arrow-systems-local-staging--2026-10-14-103236--v5.11.4.sql` | After Phase 18 (products) |
| `arrow-systems-local-staging--2026-10-14-104547--v5.11.4.sql` | Before the product Page Builder (Phase 19) |
| `arrow-systems-local-staging--2026-10-14-104739--v5.11.4.sql` | Before switching `02_products.php` to the builder |
| `arrow-systems-local-staging--2026-10-14-105022--v5.11.4.sql` | After Phase 19 (product Page Builder) |
| `arrow-systems-local-staging--2026-10-14-110341--v5.11.4.sql` | Before the video Show In option (design fix) |
| `arrow-systems-local-staging--2026-10-14-110439--v5.11.4.sql` | Before Card Title |
| `arrow-systems-local-staging--2026-10-14-110706--v5.11.4.sql` | After the design fix (Phase 19) |
| `arrow-systems-local-staging--2026-10-14-114214--v5.11.4.sql` | Industries: before the structure (Phase 20) |
| `arrow-systems-local-staging--2026-10-14-114802--v5.11.4.sql` | Industries: before the content import |
| `arrow-systems-local-staging--2026-10-14-115401--v5.11.4.sql` | Industries: before connecting (homepage E, menus, links) |
| `arrow-systems-local-staging--2026-10-14-115502--v5.11.4.sql` | Industries: before turning Link fields into entry links |
| `arrow-systems-local-staging--2026-10-14-120902--v5.11.4.sql` | After Phase 20 (industries) |
| `arrow-systems-local-staging--2026-10-14-133423--v5.11.4.sql` | Industries: before the row-based re-import (layout pass) |
| `arrow-systems-local-staging--2026-10-14-133740--v5.11.4.sql` | After the industries layout pass |
| `arrow-systems-local-staging--2026-10-15-052229--v5.11.4.sql` | Before the common page builder (Phase 21) |
| `arrow-systems-local-staging--2026-10-15-053154--v5.11.4.sql` | After Phase 21 (common page builder) |
| `arrow-systems-local-staging--2026-10-15-060926--v5.11.4.sql` | After testing every block + the fixes |
| `arrow-systems-local-staging--2026-10-16-045009--v5.11.4.sql` | After Phase 25 (new product page design + Stats Strip) |
| `arrow-systems-local-staging--2026-10-16-051309--v5.11.4.sql` | Before `07_simplify.php` (Short Description + bullet Highlights still there) |
| `arrow-systems-local-staging--2026-10-16-051449--v5.11.4.sql` | After `07_simplify.php`: current state |

Restore with `/opt/lampp/bin/php craft db/restore storage/backups/<file>.sql`.
