# Arrow Systems (Craft CMS staging): local setup for QA

This folder is the new Arrow Systems website (arrsys.com rebuilt in Craft CMS 5). These steps run it on your own
computer at **http://localhost:8081** so you can test the website and the admin panel.

Time needed: about 30 minutes (most of it is importing the database).

---

## 1. What you need

| Software | Version | Notes |
|---|---|---|
| **PHP** | 8.2 or newer | The easiest way is **XAMPP 8.2** (Windows, macOS or Linux), which includes PHP and MariaDB |
| **MySQL** or **MariaDB** | MySQL 8.0.17+ or MariaDB 10.4.6+ | Included in XAMPP |
| PHP extensions | bcmath, ctype, curl, dom, gd, intl, json, mbstring, openssl, pdo_mysql, zip | Already enabled in XAMPP 8.2. Check with `php -m` |
| Disk space | about 2 GB free | The site's images are about 700 MB |
| A browser | Chrome, Firefox, Safari or Edge | Test on phone sizes too (DevTools device mode) |

You do **not** need Composer, Node.js or Git: everything the site needs is already in the folder (`vendor/`).

> **XAMPP users:** use XAMPP's PHP in every command below.
> Linux: `/opt/lampp/bin/php` · macOS: `/Applications/XAMPP/xamppfiles/bin/php` · Windows: `C:\xampp\php\php.exe`.
> Where this guide says `php`, use that path (or add XAMPP's PHP folder to your PATH).

## 2. What you receive from the developer

1. The project folder (`arrow-system-local-staging/`).
2. A database file, e.g. `arrow-systems-local-staging--2026-10-19-....sql` (about 40 MB).
3. The values for the `.env` file (step 4): especially **CRAFT_SECURITY_KEY**, which must be the same as the
   developer's, or saved settings can't be read.

## 3. Create an empty database

Start **MySQL/MariaDB** (XAMPP Control Panel → MySQL → Start). You don't need XAMPP's Apache.

Create a database named `arrow_local_staging`, either in phpMyAdmin (http://localhost/phpmyadmin → New → name
`arrow_local_staging`, collation `utf8mb4_unicode_ci`), or from a terminal:

```bash
mysql -u root -e "CREATE DATABASE arrow_local_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

(XAMPP: `mysql` is in the same folder as XAMPP's PHP, e.g. `/opt/lampp/bin/mysql` or `C:\xampp\mysql\bin\mysql.exe`.)

## 4. Create the `.env` file

In the project folder, create a file named **`.env`** (no other extension) with this content. Change only the
database user / password if yours differ from XAMPP's default (`root`, no password):

```dotenv
CRAFT_APP_ID=CraftCMS--arrow-staging-qa
CRAFT_ENVIRONMENT=dev
CRAFT_SECURITY_KEY=<from the developer>
CRAFT_DEV_MODE=true
CRAFT_ALLOW_ADMIN_CHANGES=true
CRAFT_DISALLOW_ROBOTS=true

CRAFT_DB_DRIVER=mysql
CRAFT_DB_SERVER=127.0.0.1
CRAFT_DB_PORT=3306
CRAFT_DB_DATABASE=arrow_local_staging
CRAFT_DB_USER=root
CRAFT_DB_PASSWORD=
CRAFT_DB_SCHEMA=
CRAFT_DB_TABLE_PREFIX=

PRIMARY_SITE_URL=http://localhost:8081

RECAPTCHA_ENABLED=false
RECAPTCHA_SITE_KEY=
RECAPTCHA_SECRET_KEY=
```

`PRIMARY_SITE_URL` must stay `http://localhost:8081`: the site builds its links and image addresses from it.

## 5. Import the database

Open a terminal **in the project folder** and run (use the real file name and path):

```bash
php craft db/restore /path/to/arrow-systems-local-staging--2026-10-19-....sql
```

It takes a few minutes. Then check that Craft sees no unapplied changes:

```bash
php craft project-config/diff
```

It should say **"No pending project config YAML changes."** If it lists changes, run `php craft project-config/apply`.

## 6. Start the site

```bash
php craft serve --port=8081
```

Leave this terminal open while you test (closing it stops the site). Then open:

- Website: **http://localhost:8081/**
- Admin panel: **http://localhost:8081/admin**

## 7. Create your own admin login

The database has the developer's accounts. Create your own (in a second terminal, in the project folder):

```bash
php craft users/create --email=qa@example.com --username=qa --password="ChooseAPassword123" --admin=1
```

Log in at http://localhost:8081/admin with that username and password.

## 8. Good to know before testing

| Topic | What to expect |
|---|---|
| **Emails** | **No email is sent** from this copy (the site's mail account is blocked by Gmail, being fixed). Form submissions are still **saved**: see Admin → **Formie → Submissions**. Don't report "no email received" as a bug. |
| **reCAPTCHA** | Switched off until the client's keys arrive. Forms are protected by a hidden honeypot only. |
| **Links to arrsys.com** | Pages not rebuilt in Craft yet (most "Pages", the product category pages under `/all-products/…`, request-quote, try-a-free-sample …) still link to the **live** site, so some menu items open arrsys.com. `/all-products/` returns 404 here: expected. |
| **Test pages** | Pages the developer made to show every block: `/builder-test-post/`, `/product/builder-test-product/`, `/builder-test-industry/`, `/zz-test-parent/` and `/zz-test-parent/zz-test-child/`. They are hidden from search engines and will be deleted before launch. |
| **Site name** | Titles end with "Arrow Systems (Local Staging)": the staging label, it becomes "Arrow Systems, Inc." at launch. |
| **First visit is slow** | Images are resized the first time a page is opened; reload and it is fast. |
| **Analytics / tracking** | None on this copy. |

## 9. What to test (main areas)

| Area | Start here | Admin |
|---|---|---|
| Homepage | http://localhost:8081/ | Entries → Homepage |
| Blog listing, search, categories, tags | http://localhost:8081/blog/ · `/blog/?q=label` · `/category/digital-printing/` | Entries → Blog |
| Blog post | http://localhost:8081/roll-to-roll-vs-sheet-fed-label-printing-throughput-cost/ | Entries → Blog |
| Products (105) | http://localhost:8081/product/arrowjet-aqua-330r-lite-2/ (the new design) | Entries → Products |
| Industries (12) | http://localhost:8081/industries/ · `/pharmaceutical-labeling/` | Entries → Industries |
| Exhibitions | http://localhost:8081/exhibitions/ · `/upcoming-exhibitions/` | Entries → Exhibitions |
| Pages (new module) | http://localhost:8081/contact-us/ | Entries → Pages |
| Contact form | http://localhost:8081/contact-us/ (empty submit → errors; valid submit → thank-you message) | Formie → Forms / Submissions |
| Page builder | Edit any blog post, product, industry or page: blocks, "Add block" menu, Outline panel (right) | |
| Global settings | Phones, email, address, Stats Strip figures | Globals → Site Settings / Stats Strip |
| Menus | Header, footer, top bar | Navigation |

Please also check: phone and tablet sizes, no horizontal scrolling, images load, links open the right page,
and the browser console (F12) shows no errors.

When you report a bug, include: the URL, the screen size / browser, the steps, what you expected and what happened
(a screenshot helps).

## 10. Troubleshooting

| Problem | Fix |
|---|---|
| Page has no styles / images missing | Open the site at exactly **http://localhost:8081** (not 127.0.0.1 or another port), and check `PRIMARY_SITE_URL` in `.env` |
| "Address already in use" on start | Something else uses port 8081: stop it, or ask the developer (the URL is fixed to 8081) |
| Database connection error | MySQL/MariaDB not started, or wrong `CRAFT_DB_*` values in `.env` |
| `php` not found | Use XAMPP's full PHP path (section 1) |
| Admin says settings can't be read / errors about decryption | `CRAFT_SECURITY_KEY` differs from the developer's: copy theirs |
| Old content / strange caching | `php craft clear-caches/all`, then reload |
| Admin shows "pending project config changes" | `php craft project-config/apply` |

---

### For the developer handing the folder over

1. Take a fresh database copy: `/opt/lampp/bin/php craft db/backup` (the file appears in `storage/backups/`).
2. Zip the project folder **without** `.env`, `storage/backups/`, `storage/runtime/` and `storage/logs/` (they are
   large or private), and send the new `.sql` file and the `CRAFT_SECURITY_KEY` value separately.
3. Before sharing: the SMTP password of the mail account is stored in plain text in `config/project/project.yaml`
   (Settings → Email). Move it to an environment variable (e.g. `$SMTP_PASSWORD`) or clear it, so it isn't handed out
   with the folder.
