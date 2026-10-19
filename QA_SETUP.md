# Arrow Systems (Craft CMS staging): local setup for QA

This repository is the new Arrow Systems website (arrsys.com rebuilt in Craft CMS 5). These steps run it on your own
computer at **http://localhost:8081** so you can test the website and the admin panel.

Time needed: about 30–45 minutes (most of it is downloading dependencies and importing the database).

---

## 1. What you need

| Software | Version | Notes |
|---|---|---|
| **PHP** | 8.2 or newer | The easiest way is **XAMPP 8.2** (Windows, macOS or Linux), which includes PHP and MariaDB |
| **MySQL** or **MariaDB** | MySQL 8.0.17+ or MariaDB 10.4.6+ | Included in XAMPP |
| PHP extensions | bcmath, ctype, curl, dom, gd, intl, json, mbstring, openssl, pdo_mysql, zip | Already enabled in XAMPP 8.2. Check with `php -m` |
| **Composer** | 2.x | https://getcomposer.org/download/ (installs the site's PHP libraries) |
| **Git** | any recent version | To clone the repository |
| Disk space | about 2 GB free | The site's images are about 700 MB |
| A browser | Chrome, Firefox, Safari or Edge | Test on phone sizes too (DevTools device mode) |

> **XAMPP users:** use XAMPP's PHP in every command below.
> Linux: `/opt/lampp/bin/php` · macOS: `/Applications/XAMPP/xamppfiles/bin/php` · Windows: `C:\xampp\php\php.exe`.
> Where this guide says `php`, use that path (or add XAMPP's PHP folder to your PATH, so Composer uses it too).

## 2. What you receive from the developer

1. **Access to the repository:** https://github.com/avadhkanaiya-maker/arrow-system (ask the developer to add your
   GitHub account if it is private).
2. **`uploads.zip`:** the site's images and PDFs (about 700 MB; not in Git).
3. **The database file**, e.g. `arrow-systems-local-staging--2026-10-19-....sql` (about 40 MB; not in Git).
4. **The `CRAFT_SECURITY_KEY` value** for the `.env` file (step 6). It must be the same as the developer's, or saved
   settings can't be read.

## 3. Get the code

```bash
git clone https://github.com/avadhkanaiya-maker/arrow-system.git arrow-system
cd arrow-system
```

All the following commands are run **in this folder**.

## 4. Install the PHP libraries

```bash
composer install
```

This downloads Craft CMS and its plugins (Formie, CKEditor, Navigation) into `vendor/`, at the exact versions the
developer tested (`composer.lock`). It takes a few minutes the first time.

> If Composer stops with *"requires ext-gd"*, your command line uses a PHP without the GD extension. Either point
> Composer at XAMPP's PHP (e.g. `/opt/lampp/bin/php /usr/local/bin/composer install`), or run
> `composer install --ignore-platform-req=ext-gd` (the site then still runs with XAMPP's PHP, which has GD).

## 5. Add the images

Unzip **`uploads.zip`** into **`web/uploads/`**, so that you get folders like `web/uploads/blog/`,
`web/uploads/products/`, `web/uploads/industries/` … (not `web/uploads/uploads/blog/`).

## 6. Create the `.env` file

Create a file named **`.env`** (no other extension) in the project folder with this content. Change only the database
user / password if yours differ from XAMPP's default (`root`, no password):

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

# Leave empty: QA copies don't send email (form submissions are still saved)
SMTP_USERNAME=
SMTP_PASSWORD=
```

`PRIMARY_SITE_URL` must stay `http://localhost:8081`: the site builds its links and image addresses from it.

## 7. Create the database and import it

Start **MySQL/MariaDB** (XAMPP Control Panel → MySQL → Start). You don't need XAMPP's Apache.

Create an empty database named `arrow_local_staging`, either in phpMyAdmin (http://localhost/phpmyadmin → New → name
`arrow_local_staging`, collation `utf8mb4_unicode_ci`), or from a terminal:

```bash
mysql -u root -e "CREATE DATABASE arrow_local_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

(XAMPP: `mysql` is next to XAMPP's PHP, e.g. `/opt/lampp/bin/mysql` or `C:\xampp\mysql\bin\mysql.exe`.)

Then import the database file (use its real name and path):

```bash
php craft db/restore /path/to/arrow-systems-local-staging--2026-10-19-....sql
```

It takes a few minutes. Then check that the database matches the code:

```bash
php craft project-config/diff
```

It should say **"No pending project config YAML changes."** If it lists changes, run `php craft up`.

## 8. Start the site

```bash
php craft serve --port=8081
```

Leave this terminal open while you test (closing it stops the site). Then open:

- Website: **http://localhost:8081/**
- Admin panel: **http://localhost:8081/admin**

## 9. Create your own admin login

The database has the developer's accounts. Create your own (in a second terminal, in the project folder):

```bash
php craft users/create --email=qa@example.com --username=qa --password="ChooseAPassword123" --admin=1
```

Log in at http://localhost:8081/admin with that username and password.

## 10. Getting a newer version later

When the developer says a new version is ready:

```bash
git pull
composer install            # only does something if the libraries changed
php craft up                # applies structure changes (new fields, blocks …)
```

If they also send a **new database file**, import it as in step 7 (it replaces your local content, including your
test submissions), and recreate your login (step 9). If they send a new **uploads.zip**, unzip it over `web/uploads/`.

## 11. Good to know before testing

| Topic | What to expect |
|---|---|
| **Emails** | **No email is sent** from your copy. Form submissions are still **saved**: see Admin → **Formie → Submissions**. Don't report "no email received" as a bug. |
| **reCAPTCHA** | Switched off until the client's keys arrive. Forms are protected by a hidden honeypot only. |
| **Links to arrsys.com** | Pages not rebuilt in Craft yet (most "Pages", the product category pages under `/all-products/…`, request-quote, try-a-free-sample …) still link to the **live** site, so some menu items open arrsys.com. `/all-products/` returns 404 here: expected. |
| **Test pages** | Pages the developer made to show every block: `/builder-test-post/`, `/product/builder-test-product/`, `/builder-test-industry/`, `/zz-test-parent/` and `/zz-test-parent/zz-test-child/`. Hidden from search engines; deleted before launch. |
| **Site name** | Titles end with "Arrow Systems (Local Staging)": the staging label, it becomes "Arrow Systems, Inc." at launch. |
| **First visit is slow** | Images are resized the first time a page is opened; reload and it is fast. |
| **Analytics / tracking** | None on this copy. |

## 12. What to test (main areas)

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
| Page builder | Edit any blog post, product, industry or page: blocks, "Add block" menu, Outline panel (right), Section Settings tab on Columns / Card Grid / Form | |
| Global settings | Phones, email, address, Stats Strip figures | Globals → Site Settings / Stats Strip |
| Menus | Header, footer, top bar | Navigation |

Please also check: phone and tablet sizes, no horizontal scrolling, images load, links open the right page, and the
browser console (F12) shows no errors.

When you report a bug, include: the URL, the screen size / browser, the steps, what you expected and what happened
(a screenshot helps).

## 13. Troubleshooting

| Problem | Fix |
|---|---|
| Images missing everywhere | `uploads.zip` not unzipped into `web/uploads/` (step 5), or one folder level too deep |
| Page has no styles | Open the site at exactly **http://localhost:8081** (not 127.0.0.1 or another port), and check `PRIMARY_SITE_URL` in `.env` |
| "Class … not found" / vendor errors | Run `composer install` (step 4) |
| "Address already in use" on start | Something else uses port 8081: stop it (the URL is fixed to 8081) |
| Database connection error | MySQL/MariaDB not started, or wrong `CRAFT_DB_*` values in `.env` |
| `php` not found | Use XAMPP's full PHP path (section 1) |
| Errors about decryption / settings can't be read | `CRAFT_SECURITY_KEY` differs from the developer's: copy theirs |
| Old content / strange caching | `php craft clear-caches/all`, then reload |
| Admin shows "pending project config changes" | `php craft up` |

---

### For the developer handing over

1. Push the latest code to GitHub and give QA access to the repository.
2. Take a fresh database copy: `/opt/lampp/bin/php craft db/backup` (the file appears in `storage/backups/`).
3. Zip the images: `cd web/uploads && zip -r ../../uploads.zip .` (so the zip has `blog/`, `products/` … at its top).
4. Send QA the `.sql` file, `uploads.zip` and the `CRAFT_SECURITY_KEY` value (not your whole `.env`: it holds the
   SMTP login).
