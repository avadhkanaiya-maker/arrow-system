# QA Walkthrough: Menus, Header and Footer

This guide walks you through how the website's menus, header and footer are managed from the admin panel, and where
each thing you change shows up on the website. Read it with both screens open side by side: the admin panel in one
tab (`http://localhost:8081/admin`) and the website in another (`http://localhost:8081`).

---

## 1. The big picture: what you see on the website, and where it comes from

Open the homepage and look at the top and the bottom of the page. Everything you see there is managed from two places in
the admin panel: **Navigation** (the menus) and **Globals → Site Settings** (logo, phone numbers, address and so on).

| On the website | What it is | Managed in the admin panel at |
|---|---|---|
| Thin dark strip at the very top (Support · Order Supplies · Distributors · Contact) | Top Bar | Navigation → **Top Bar** |
| Logo on the left of the header | Site logo | Globals → Site Settings → **Brand** |
| Products, Solutions, Industries, Materials, Resources, Support, About | Main menu | Navigation → **Main Menu** |
| Phone number in the header | Toll-free phone | Globals → Site Settings → **Contact** |
| Red **Request a Quote** button | Quote button | Globals → Site Settings → **Calls to action** |
| Magnifying-glass icon | Search | Not editable (see "Things to know" at the end) |
| Footer: logo, short text, address, phones, email, social icons | Footer contact block | Globals → Site Settings (Brand, Contact, Social) |
| Footer: the five link columns (Products, Industries, Resources, Support, Company) | Footer Menu | Navigation → **Footer Menu** |
| Bottom line of the footer: © Arrow Systems, Inc. + Privacy Policy, Terms … | Legal Menu | Navigation → **Legal Menu** + the Logo Alt Text |

A useful habit while testing: **change one thing, save, then refresh the website** to see where it appears.

---

## 2. Where to find the menus in the admin panel

In the left sidebar of the admin panel, click **Navigation**. You will see four menus:

1. **Main Menu**: the menu in the header.
2. **Top Bar**: the thin strip above the header.
3. **Footer Menu**: the link columns in the footer.
4. **Legal Menu**: the small links at the very bottom.

Click a menu to open it. You will see its items as a tree: items further to the right sit "inside" the item above
them. You can drag an item up and down to change its position, or drag it left and right to move it in and out of
another item.

### Adding an item

At the top of the menu screen you can add a new item. You choose what kind of item it is:

| Item type | When to use it | What happens on the website |
|---|---|---|
| **Entries** | Link to a page that exists on this website (a page, product, industry, blog post …) | The link always goes to that page, even if its web address changes later. The label follows the page's title unless you type your own. |
| **Custom URL** | Link to anything else: the live arrsys.com site, YouTube, a page anchor like `/#on-demand` | Goes to exactly the address you type. |
| **Passive** | A heading that opens a menu but isn't a link itself (for example "Products" in the main menu), or a placeholder for a page that isn't built yet | Clicking it does nothing on its own. |
| **Group / Column** | A heading that groups links (a footer column title, a group in a mega menu) | Shown as a heading, not a link. |

### What you can set on every item

Click any item to open its settings panel on the right.

- **Label**: the text visitors see. For items linked to a page, it copies that page's title until you change it.
- **The link itself**: the page you picked, or the address you typed.
- **Open in new window**: when switched on, the link opens in a new browser tab. Use it for links that leave the
  site, such as YouTube.
- **Enabled**: switch an item off to hide it from the website without deleting it. Switch it back on to bring it back.

**Try it:** open **Top Bar**, click **Contact**, change the label to "Contact Us", save, and refresh the website: the
top strip now says "Contact Us". Change it back afterwards.

---

## 3. Top Bar

**What it controls:** the thin dark strip above the header, on large screens. On phones and tablets, the same links
appear at the bottom of the mobile menu (open the menu with the ☰ button and scroll down).

**How it's built:** a simple list, one level only. Each item is one link. The order in the admin panel is the
order on the website, from left to right.

**Currently:** Support, Order Supplies, Distributors (a placeholder, no page yet), Contact.

**Test ideas:**
- Reorder two items and check the order changes on the website, on desktop and in the mobile menu.
- Switch one item off and confirm it disappears from both places.

---

## 4. Main Menu

This is the most important menu. Each item on the first level (Products, Solutions, Industries …) becomes a word in the
header. When a visitor hovers or clicks it, a panel opens. **What that panel looks like is decided by a setting on the
first-level item, called Menu Style.**

### Menu Style (first-level items only)

Click a first-level item, for example **Solutions**, and look for **Menu Style**. There are three choices:

| Menu Style | What visitors see | Used for |
|---|---|---|
| **Simple dropdown** | A plain list of links under the word | Solutions, Materials, Resources, Support, About |
| **Products mega menu** | A wide panel: categories on the left, products in the middle, a featured product box on the right | Products |
| **Industries mega menu** | A wide panel: industry groups with small icons, plus a help box on the right | Industries |

Changing the Menu Style changes the whole panel's layout, so the items inside need to be arranged the way that style
expects (explained below).

### 4a. Simple dropdown

**How to arrange it:** the first-level item (for example "About") holds a list of links directly inside it.

```
About                     ← first level, Menu Style: Simple dropdown
  Our Company             ← links shown in the dropdown, top to bottom
  Become a Dealer
  Contact Us
```

**Test ideas:** add a new link inside "About", save, and check it appears in the dropdown in the same position.

### 4b. Products mega menu

This one has four levels. Open **Products** in the admin panel and compare it with the website's Products panel.

```
Products                                  ← level 1: the word in the header (Menu Style: Products mega menu)
  Arrow Systems equipment                 ← level 2, Group / Column: a group heading on the left
    Digital Label Printers                ← level 3: a category on the left side
      ArrowJet Eco 330R                   ← level 4: a product link in the middle
      ArrowJet Aqua 330R
    Label Finishers
      …
  Compare Models                          ← level 2, NOT a group: an extra link under the box on the right
  Used Equipment                          ← level 2, NOT a group: an extra link under the box on the right
```

**What each level does on the website:**

- **Level 2, Group / Column items** become the small headings on the left, such as "Arrow Systems equipment".
  - They have one extra setting, **Partner group**. Switch it on when the group lists partner brands; the heading then
    gets the separate "Partner Brands" look.
- **Level 2, ordinary links** (Compare Models, Used Equipment) don't appear on the left. They appear as small links in
  the box on the right.
- **Level 3 items are the categories.** Hovering a category on the left opens its products in the middle. Each
  category has these extra settings:

  | Setting | What it does | Where you see it |
  |---|---|---|
  | **Panel Title** | An optional heading above the product list, e.g. "ArrowJet digital label printers" | Top of the middle area, when the category is open |
  | **View All Text** | The text of a "View all …" link under the product list; it goes to the category's own link | Bottom of the middle area. Empty = no "View all" link. A category without its own link (Passive) never shows one. |
  | **Featured Product** | Pick one product to feature for this category | The box on the right, while this category is open: the product's card photo, name, short tagline and an "Explore Product" link. Empty = the box shows only the extra links. |

- **Level 4 items are the products** listed in the middle. They have one extra setting, **Second Line**: a short grey
  line under the product name (large screens only). Empty = no second line.

**Currently featured:** Digital Label Printers → Digital Sleeking Film, Label Finishers → Trojan T4, Specialty Printers
→ Gerber GSxplus. The other categories have no featured product, so their right-hand box shows only the extra links.

**Try it:**
1. Open the **Flexible Packaging Printers** category (level 3) and pick a **Featured Product**. Save.
2. On the website, open Products and hover **Flexible Packaging Printers**: the box on the right now shows that product.
   Hover another category: the box changes to that category's product, or to just the links.
3. Clear the Featured Product again afterwards.

**Test ideas:**
- The featured box changes as you move between categories.
- "View all …" appears only where View All Text is filled in.
- Changing a product's Card Photo or Card Tagline (on the product itself) changes the featured box too.
- On a phone, the categories open and close as a list; the featured box isn't shown there (by design).

### 4c. Industries mega menu

```
Industries                                ← level 1 (Menu Style: Industries mega menu)
  Labels                                  ← level 2, Group / Column: a group heading
    Beverage Labels                       ← level 3: a link with a small icon
    Pharmaceutical Labeling
  Packaging
    …
  View all industries                     ← level 2, NOT a group: shown as a "View all" link under the last group
```

- **Group / Column items** become the group headings (Labels, Packaging, Specialty).
- **The links inside them** show with a small line icon. Each link has an **Icon** setting with a list of icons.
  - Leave it empty and the icon comes from the industry page itself (the industry's **Card** tab → **Icon**).
  - Pick one here to override it in the menu only.
- **An ordinary level-2 link** ("View all industries") becomes the red "View all …" link under the last group.

**The help box on the right** ("Not sure which equipment fits?" with a "Talk to a Specialist" button) isn't a menu
item. It's set on the Main Menu itself: open **Main Menu** and switch to the **Industries box** tab. It has three fields:

| Field | Where it shows |
|---|---|
| **Help Box Title** | Bold heading of the box |
| **Help Box Text** | The sentence under it |
| **Help Box Button** | The button's text and where it goes |

Leave the title empty to hide the box completely. Like the featured product box, it shows on large screens only.

**Test ideas:** change an industry link's icon and check it in the menu; edit the help box text; add a fourth group.

---

## 5. Footer Menu

**What it controls:** the five link columns in the footer.

**How it's built:** two levels.

```
Products                     ← level 1, Group / Column: the column heading (shown in capitals with a red square)
  Digital Label Printers     ← level 2: the links in that column, top to bottom
  Label Finishers
Industries
  …
```

- Each first-level item is one column; the order in the admin panel is the column order from left to right.
- Add a new first-level Group / Column to add a sixth column.

**Test ideas:** move a link from one column to another by dragging; rename a column heading; check the footer on a
phone (the columns stack).

---

## 6. Legal Menu

**What it controls:** the small links on the right of the very bottom line (Privacy Policy, Terms, Accessibility
Statement, Your Privacy Choices). One level only.

The text on the left of that line (**© Arrow Systems, Inc.**) is not part of this menu. It comes from
Globals → Site Settings → Brand → **Logo Alt Text**.

---

## 7. Site Settings: the logo and contact details in the header and footer

In the left sidebar click **Globals**, then **Site Settings**. It has five tabs; the header and footer use the first
four.

### Brand tab

| Field | What it's for | Where it shows |
|---|---|---|
| **Logo** | The logo with dark lettering, for light backgrounds | Used by the header and footer only if "Logo on Dark Background" is empty |
| **Logo on Dark Background** | The logo with light lettering | Header (top left) and footer (top of the contact block), both dark |
| **Logo Alt Text** | The company name, read out by screen readers | Hidden text on the logo, the © line in the footer, and in place of the logo if no logo image is set |
| **Favicon** | The small icon in the browser tab | Browser tab (currently empty) |
| **Tagline** | One short line about the company | Footer, under the logo |

### Contact tab

| Field | Where it shows |
|---|---|
| **Address** | Footer contact block (map-pin icon) |
| **Toll-free Phone** | Header (next to the Request a Quote button), the "Call …" button in the mobile menu, and the footer ("Toll-free"). Tapping it on a phone starts a call. |
| **Direct Phone** | Footer, under the toll-free number ("Direct") |
| **Sales Email** | Footer (envelope icon); clicking it opens a new email |

Leave a field empty and that line simply disappears from the footer. If Toll-free Phone is empty, the phone number also
disappears from the header.

### Calls to action tab

| Field | Where it shows |
|---|---|
| **Request a Quote Link** | The red **Request a Quote** button in the header and at the top of the mobile menu. You set both the button text and where it goes. |
| **Free Sample Link** | Not in the header or footer; used by "Try a Free Sample" buttons elsewhere on the site |

### Social tab

**Social Links**: one row per network (Facebook, LinkedIn, YouTube, Instagram) with its address. Each row becomes a
round icon in the footer, in the same order. Delete a row to remove that icon.

### Custom CSS tab

This tab is for design tweaks across the whole site and isn't part of the menus. You don't need to test it here.

**Try it:** change the Toll-free Phone, save, and check the header, the mobile menu and the footer all show the new
number. Change it back afterwards.

---

## 8. How it all behaves on phones and tablets

Resize the browser window narrower than a laptop screen (or use the browser's phone view):

- The main menu words disappear and a **☰** button appears on the right of the header.
- The ☰ button opens a full menu:
  - at the top, the **Request a Quote** and **Call …** buttons
  - then the main menu: each first-level item opens and closes its links
  - at the bottom, the **Top Bar** links
- The Products featured box, the Industries help box and the grey "Second Line" under product names are left out on
  small screens to keep the menu short. This is intended.
- The footer columns stack underneath each other.

---

## 9. Things to know before reporting a bug

- **Links to arrsys.com are expected.** Many menu items still point to pages on the live site, because those pages
  haven't been rebuilt on this site yet. As each page is built, its menu item is switched to the new page. A link
  going to arrsys.com is not a bug by itself.
- **Placeholders do nothing when clicked.** Some items are Passive on purpose because their page doesn't exist yet:
  Distributors, Compare Models, Download Brochure, Find a Distributor, Partner Brands, Terms, Accessibility Statement,
  Your Privacy Choices. Clicking them does nothing.
- **Search goes to the live site for now.** The search icon opens a search box, but the results come from arrsys.com
  until search is set up on the new site.
- **Changes show immediately.** There's no publish step for menus: save and refresh the website.
- **Menu settings are for the developer.** Things like how many levels a menu allows are under Navigation → Settings.
  Please don't change those during testing.

---

## 10. Quick test checklist

- [ ] Rename, reorder, add, disable and delete an item in each of the four menus; the website follows every change.
- [ ] An item linked to a page keeps working after that page's title or web address is changed.
- [ ] "Open in new window" opens a new tab; without it, the link opens in the same tab.
- [ ] Switching a first-level item's Menu Style changes its panel (simple list / Products layout / Industries layout).
- [ ] Products: Panel Title, View All Text, Featured Product, Partner group and Second Line each appear where described
      in section 4b.
- [ ] Industries: the item Icon overrides the industry's own icon; the help box fields update the box; an empty
      title hides it.
- [ ] Site Settings: logo, tagline, address, both phones, email, Request a Quote button and social icons update the
      header and footer.
- [ ] Phone view: ☰ menu opens and closes, all levels are reachable, the top bar links are at the bottom, the call
      button calls.
- [ ] Keyboard: Tab through the header; menus open with Enter and close with Escape.
