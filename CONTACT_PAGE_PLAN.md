# Contact Us Page Plan: live arrsys.com/contact-us/ → Craft (local staging)

**Goal:** rebuild `/contact-us/` in Craft at the same URL, with its SEO, in the new design's style: the **form**
(Formie "Contact Us", already built to match live: Phase 24) and the **content** (head office details, maps, the
distributor list), everything editable in the control panel, nothing hard-coded.

**Status:** built 2026-10-19 (`DEVELOPMENT_PHASES.md` Phase 30). Decisions: D1 Pages module; D3–D6 as live (data, Google
map embeds, schema, one list in live order); D7 reCAPTCHA v2 (keys to come, switched in .env); D8 admin emails to
admin_arrowsys@yopmail.com for now. D2 changed: the distributors are cards inside a Distributors block on the page
(not the homepage Locations, so the homepage map is untouched).
**Sources checked (2026-10-15):** the live page and its `<head>`, the design folder (no contact page design; the
closest parts: `request-quote/index.html` "form + side column", the industry contact panel, homepage section H
"Worldwide locations"), staging (Formie form `contactUs`, Site Settings, the Locations section).

---

## 1. What the live page has

| # | Live section | Content |
|---|---|---|
| 1 | Header banner | A background image (no text in the page content) |
| 2 | **Head office + form** (2 columns) | Left: "Arrow Systems, Inc. USA", 2440 Jerauld Ave., Niagara Falls, New York 14305, USA · Toll free +1-888-697-2749 (US / Canada) · Contact directly +1-716-285-2974 · Email · **Google Maps embed** of the address. Right: "Contact Us" + the **form** (Name, Email, Number, Country, Message, How Did You Hear About Us, reCAPTCHA, honeypot, Send) |
| 3 | **Distributor Location** | Heading + an embedded map (a Google map in a shortcode iframe) |
| 4 | **Distributor cards** (19, 3 per row) | Country · company · address · phone · email / website. Guatemala, Puerto Rico, Venezuela, Dominican Republic, Nicaragua, El Salvador (all Grupo SG), Netherlands (Katun), USA (Capital Mailing Equipment), Netherlands (CONVERPACK), South Africa (Flexo Machines), USA (EZ Hi Tech), USA (Mueller Graphic Supply), Poland (Wolff Poligrafia), Canada (Total Solutions), Dubai (Quantum Digital Technologies), Slovakia & Czech Republic (MAILTEC), Hungary & Ireland (Door2world), Greece & Cyprus (BUSINESSWARE), Argentina (WABI) |

**SEO (keep as live):** title "Contact Us | Arrow Systems, Inc. – Get in Touch Today", meta description "Have questions
or need assistance? Contact us at Arrow Systems, Inc. for expert support on printers, label finishers, and packaging
solutions.", canonical = own URL, robots `follow, index …`, og:type `article`.

**Live content problems found** (D3):
- "Puerto Rico · Grupo SG" shows a **Panama City** address and a +507 (Panama) phone.
- "Venezuela · Grupo SG" shows a **San José** (Costa Rica) address and a +506 (Costa Rica) phone.
- "Nicaragua" shows a +503 (El Salvador) phone; El Salvador has no phone.
- Spelling: "Guatamaela", "Nicarauga".

---

## 2. What staging already has (reuse, don't duplicate)

| Piece | Where | Use here |
|---|---|---|
| The form | Formie `contactUs` (Phase 24): the live fields, 2 columns, phone with country picker, honeypot, notifications | Placed on the page through a form picker; nothing re-typed |
| Head office details | Globals → **Site Settings**: address, toll-free and direct phone, sales email (used by the header, footer, product and industry pages) | The left column reads them; one place to change a phone number |
| Distributors | Entries → **Locations** (18: the homepage "Worldwide locations" map; fields: city, country, region, relationship, latitude, longitude) | **16 of the 19 live distributors already exist** (matched by city). Missing: Panama City ("Puerto Rico"), San José ("Venezuela"), CONVERPACK (Zuidplas, NL). Two Locations are not on the contact page: Niagara Falls (head office), New Delhi |
| Design parts | `contact-panel` (maroon details panel), `contact-form` / `enquiry-form` (form styles), the homepage locations map (pins from latitude / longitude), page header | The page is built from these; no new visual language |

---

## 3. Content model

### 3.1 The page

The page is an entry at `/contact-us/` with a page builder (D1: the first page of the **Pages** module,
`PAGES_MIGRATION_PLAN.md`), made of these blocks:

| Block | New? | What the editor sets |
|---|---|---|
| **Page Header** | reuse (Industry Header, made generic) | Small heading, H1 "Contact [Us]", intro, banner image |
| **Contact Section** (head office + form) | new | Heading over the form ("Contact Us"), **Form** (a Formie form picker: any Formie form, so the same block also serves Request a Quote, Become a Dealer …), Office heading ("Arrow Systems, Inc. USA"), **show map** on/off. Address, phones and email come from Site Settings |
| **Distributors** | new | Heading ("Distributor Locations"), intro, **map** on/off, which locations (all marked "show on contact page", grouped by region or country) |
| Common blocks | reuse | Rich Text, FAQ, CTA band … if the editor wants more |

### 3.2 Locations: one list for the homepage map and the contact page

Add to the **Location** entry type (the homepage map keeps working as it is):

| Field | Handle | Type |
|---|---|---|
| Company | `company` | Plain text ("Grupo SG") |
| Address | `address` | Plain text, multi-line |
| Phone(s) | `phones` | Table (label, number): e.g. Tel, Mobile, Fax |
| Email | `email` | Email |
| Website | `website` | Link (URL) |
| Show on Contact page | `showOnContact` | Lightswitch (Niagara Falls and New Delhi off) |
| Display name | `headingText` (reuse) | Optional, e.g. "Slovakia and Czech Republic", "Greece & Cyprus" (the live card titles) |

Order = the Locations structure order (drag in the CP), as on live.

---

## 4. Front end

- `templates/pages/_entry.twig` (Pages module) + two new block templates: `contact-section.twig`,
  `distributors.twig`.
- **Contact Section:** 2 columns as live (desktop), stacked on phones: the maroon **contact panel** (design) with
  the Site Settings details and a map, next to the **form** card. The form is rendered by Formie with **our own field
  templates** (Formie supports custom templates), so it uses the design's `contact-form` classes, labels, error
  messages and button, not Formie's default look. Formie's JS handles validation (the phone country picker included),
  the honeypot, and the success message; the server checks run as before (`modules/forms`).
- **Maps** (D4): head office = a "Show map" button that loads the Google map on click (no Google cookies / scripts
  until asked), plus a "Get directions" link; distributors = the design's world map from homepage section H with a pin
  per distributor (latitude / longitude already stored), clicking a pin highlights its card.
- **Distributor cards:** a 3-column grid (2 on tablets, 1 on phones): country, company, address, phone links
  (`tel:`), email (`mailto:`), website; grouped under region headings if D6 says so.
- **SEO:** the shared `_partials/seo` with the live title / description / canonical / robots; schema: `ContactPage`
  + the `Organization` with its address, phones and email (D5).

---

## 5. Form handling (what's done, what's left)

| Item | Status |
|---|---|
| Fields, layout, required, validation (browser + server, phone per country) | ✅ Phase 24 |
| Honeypot | ✅ |
| reCAPTCHA (live has v2 checkbox) or Cloudflare Turnstile | Needs this site's keys (D7) |
| **Rate limiting** (client request) | Planned: per-IP limit in `modules/forms` (e.g. 5 submissions / 10 min, then a friendly "please wait" message) + an edge rule on the server / Cloudflare |
| Notifications | ✅ Admin notification (to `{systemEmail}`) + visitor copy; **set the real recipients** (e.g. sales@arrsys.com) (D8) |
| Success message / redirect | Formie message in the page ("Thank you…"), as live |
| Submissions | Formie → Submissions in the CP (search, export CSV, spam folder) |
| HubSpot | Later: Formie's HubSpot integration once the client sends the token |

---

## 6. Migration steps

1. **Locations:** add the fields (3.2); fill company / address / phones / email / website for the 16 matching
   Locations from the live cards; add the 3 missing distributors (with latitude / longitude); "show on contact page"
   off for Niagara Falls and New Delhi; set the display names; order as live.
2. **Blocks:** Contact Section + Distributors (+ Page Header made generic) and their templates; Formie field templates
   in the design's style.
3. **Page:** `/contact-us/` with Page Header, Contact Section (form `contactUs`), Distributors; live SEO.
4. **Links:** main menu / footer "Contact" and "Contact Us" items → the page entry (as for products / industries);
   links in text to `arrsys.com/contact-us/` → `/contact-us/`.
5. **Verify** (section 8), back up, record a phase in `DEVELOPMENT_PHASES.md`.

---

## 7. Decisions needed

| # | Question | Recommendation |
|---|---|---|
| **D1** | Build the page **in the Pages module** (needs its first step: Pages section + page builder), or a one-off "Contact Page" single now? | **Pages module**: Contact Us is the first page; no rework later |
| **D2** | Distributors: extend the existing **Locations** (one list for the homepage map and the contact page), or a separate list? | **Extend Locations**: one place to edit; 16 of 19 already there |
| **D3** | The live data problems (Puerto Rico / Venezuela with Panama / Costa Rica addresses, the Nicaragua phone, spellings): copy as live, or fix? | **Fix the spellings; keep the addresses as live and flag them** for the client to confirm |
| **D4** | Maps: live Google embeds as they are, or the design's world map for distributors + a click-to-load Google map for the office? | **Design map + click-to-load** (faster, no Google tracking until asked, matches the homepage) |
| **D5** | Schema: as live (WebPage / Article), or `ContactPage` + Organization address / phones? | **ContactPage + Organization** (helps the business panel in Google; title / description stay as live) |
| **D6** | Distributor cards: as live (one list, live order), or grouped by region (Americas, Europe, Africa & Middle East)? | **Grouped by region** (the region is already stored); live order inside each group |
| **D7** | Captcha: reCAPTCHA v2 (as live) or Cloudflare Turnstile (free, usually no puzzle)? | **Turnstile**, with honeypot + rate limiting |
| **D8** | Who receives the form emails (admin notification)? | The client's sales inbox (e.g. sales@arrsys.com), set in the form's notification |

---

## 8. Acceptance checklist

- [ ] `/contact-us/` returns 200; title, description, canonical, robots as live; schema valid.
- [ ] Head office details come from Site Settings (change a phone in Site Settings → the page updates).
- [ ] Form: renders in the design's style; required + phone-per-country validation; honeypot; rate limit; success
      message; submission saved in the CP; notification emails sent (tested with a local mail catcher).
- [ ] Distributors: all 19 live distributors present (company, address, phones, email, website), editable in
      Locations; the homepage map still works.
- [ ] Maps: the office map loads on click; distributor pins match the cards.
- [ ] Desktop + phone screenshots reviewed; no JS errors; no sideways scroll; keyboard and screen-reader friendly form.
- [ ] Menus / footer / text links point at the Craft page.

## 9. Risks

| Risk | Mitigation |
|---|---|
| Formie's default markup doesn't match the design | Custom Formie field templates using the design's classes |
| Spam once the page is public | Honeypot + captcha (D7) + per-IP rate limit + server-side validation |
| Distributor data out of date on live (wrong countries / phones) | D3: flagged for the client; editors fix it in Locations |
| Google map embeds slow the page / set cookies | D4: click-to-load |
