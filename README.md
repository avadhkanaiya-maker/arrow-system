# Arrow Systems website (Craft CMS)

The new Arrow Systems website: [arrsys.com](https://arrsys.com) rebuilt in **Craft CMS 5**. This repository holds the
templates, the project config, custom modules and the scripts that moved the content over from the live WordPress site.

## Tech stack

| | |
|---|---|
| CMS | Craft CMS 5.11 (Pro) |
| Plugins | Formie (forms), Navigation (menus), CKEditor (rich text) |
| PHP | 8.2 or newer, with bcmath, ctype, curl, dom, gd, intl, json, mbstring, openssl, pdo_mysql, zip |
| Database | MySQL 8.0.17+ or MariaDB 10.4.6+ |
| Front end | Twig templates, plain CSS and JavaScript (no build step) |
| Local URL | http://localhost:8081 (fixed: links and image addresses are built from it) |

---

## Local setup

### 1. Install the software

| Software | Where to get it |
|---|---|
| **XAMPP 8.2** (PHP 8.2 + MariaDB) | https://www.apachefriends.org |
| **Composer 2** | https://getcomposer.org/download/ |
| **Git** | https://git-scm.com |

About 2 GB of free disk space is needed (the images are about 700 MB).

**Use XAMPP's PHP and MySQL in every command below.** Their paths:

| OS | PHP | MySQL |
|---|---|---|
| Windows | `C:\xampp\php\php.exe` | `C:\xampp\mysql\bin\mysql.exe` |
| macOS | `/Applications/XAMPP/xamppfiles/bin/php` | `/Applications/XAMPP/xamppfiles/bin/mysql` |
| Linux | `/opt/lampp/bin/php` | `/opt/lampp/bin/mysql` |

The easiest way is to add these folders to your PATH, so `php` and `mysql` work directly. On Windows that means adding
`C:\xampp\php` and `C:\xampp\mysql\bin` to PATH. Check:

```bash
php -v          # PHP 8.2.x
php -m          # the extensions listed above, gd and intl included
composer -V     # Composer 2.x
mysql --version
```

### 2. Get the files from the developer

These are **not in Git**:

1. **The database file**, e.g. `arrow-systems-local-staging--2026-10-27-....sql` (about 50 MB)
2. **`uploads.zip`**: the site's images and PDFs (about 700 MB)
3. **The `CRAFT_SECURITY_KEY` value** for `.env`. It must be the same as the developer's, or saved settings can't be read.

### 3. Clone the repository

```bash
git clone git@github.com:avadhkanaiya-maker/arrow-system.git arrow-system
cd arrow-system
```

(Windows with XAMPP: clone into `C:\xampp\htdocs\arrow-system`.) Run every following command **in this folder**.

### 4. Install the PHP libraries

```bash
composer install
```

This installs Craft CMS, Formie, Navigation and CKEditor into `vendor/` at the versions in `composer.lock`.

If Composer stops with *"requires ext-gd"*, your terminal's PHP has no GD. Point Composer at XAMPP's PHP, or run:

```bash
composer install --ignore-platform-req=ext-gd
```

### 5. Add the images

Unzip `uploads.zip` into `web/uploads/`:

```bash
# macOS / Linux
unzip uploads.zip -d web/uploads
```

```powershell
# Windows (PowerShell)
Expand-Archive -Path uploads.zip -DestinationPath web\uploads
```

You should get `web/uploads/blog/`, `web/uploads/products/`, `web/uploads/industries/` … and **not**
`web/uploads/uploads/blog/`.

### 6. Create the `.env` file

Create a file named `.env` in the project folder with this content:

```dotenv
CRAFT_APP_ID=CraftCMS--arrow-staging-local
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

# Leave empty locally: no email is sent (form submissions are still saved)
SMTP_USERNAME=
SMTP_PASSWORD=
```

Change `CRAFT_DB_USER` / `CRAFT_DB_PASSWORD` only if your MySQL login is not XAMPP's default (`root`, no password).
Keep `PRIMARY_SITE_URL` exactly `http://localhost:8081`.

### 7. Create the database and import it

Start **MySQL** in the XAMPP Control Panel (Apache is not needed). Then create an empty database:

```bash
mysql -u root -e "CREATE DATABASE arrow_local_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

(Or in phpMyAdmin, http://localhost/phpmyadmin: New → `arrow_local_staging`, collation `utf8mb4_unicode_ci`.)

Import the database file (use its real path):

```bash
php craft db/restore path/to/arrow-systems-local-staging--2026-10-27-....sql
```

Then clear the caches and check that the database matches the code:

```bash
php craft clear-caches/all
php craft project-config/diff     # should say: No pending project config YAML changes.
php craft plugin/list             # CKEditor, Formie and Navigation: Installed Yes, Enabled Yes
```

If `project-config/diff` lists changes, run `php craft up`.

### 8. Start the site

```bash
php craft serve --port=8081
```

Leave this terminal open (closing it stops the site), then open:

- Website: **http://localhost:8081/**
- Admin panel: **http://localhost:8081/admin**

### 9. Create your own admin login

In a second terminal, in the project folder:

```bash
php craft users/create --email=you@example.com --username=yourname --password="ChooseAPassword123" --admin=1
```

To reset a password later: `php craft users/set-password <username>`.

---

## Getting the latest changes

```bash
git pull
composer install             # only does something when composer.lock changed
php craft up                 # applies structure changes (fields, sections, entry types) and migrations
php craft clear-caches/all
```

When the developer sends a **new database file**, import it as in step 7 (this replaces your local content) and
create your login again (step 9). A new `uploads.zip` is unzipped over `web/uploads/`.

---

## Daily commands

| Task | Command |
|---|---|
| Start the site | `php craft serve --port=8081` |
| Back up the database (file in `storage/backups/`) | `php craft db/backup` |
| Restore a backup | `php craft db/restore storage/backups/<file>.sql` |
| Apply structure changes from Git | `php craft up` |
| Check structure is in sync | `php craft project-config/diff` |
| Clear all caches | `php craft clear-caches/all` |
| List plugins | `php craft plugin/list` |
| Run an import script | `php storage/imports/pages/29_flexible_packaging_color_label_printer.php` |

**Windows:** `php craft db/backup` needs `mysqldump`. If it fails with *"mysqldump … failed with exit code 1"*, add
`C:\xampp\mysql\bin` to PATH (or run `$env:Path += ";C:\xampp\mysql\bin"` in PowerShell first).

---

## Troubleshooting

| Problem | Fix |
|---|---|
| *"Calling unknown method: CraftVariable::navigation()"* or a plugin seems missing | `php craft clear-caches/all`, then check `php craft plugin/list` |
| Images missing everywhere | `uploads.zip` not in `web/uploads/` (step 5), or one folder level too deep |
| Page has no styles / wrong links | Open exactly http://localhost:8081 (not 127.0.0.1 or another port); check `PRIMARY_SITE_URL` |
| "Class … not found" / vendor errors | `composer install` |
| "Address already in use" | Another program uses port 8081: stop it |
| Database connection error | MySQL not started, or wrong `CRAFT_DB_*` values in `.env` |
| `php` not found | Use XAMPP's full PHP path (step 1) or add it to PATH |
| Decryption errors / settings can't be read | `CRAFT_SECURITY_KEY` differs from the developer's |
| Admin shows "pending project config changes" | `php craft up` |
| First page load is slow | Images are resized on first view; reload |

---

## Project structure

| Folder | What is in it |
|---|---|
| `config/` | Craft config (`general.php`, `redirects.php` …) and `project/`: the project config YAML (sections, fields, entry types, plugin settings) |
| `templates/` | Twig templates: `_layouts/`, `_partials/` (header, footer, newsletter), `_builder/` (common page-builder blocks), and one folder per section (`blog/`, `products/`, `industries/`, `pages/`, `exhibitions/`) |
| `web/assets/` | The site's CSS, JavaScript and images |
| `web/uploads/` | Uploaded images and documents (not in Git) |
| `modules/` | Custom modules: `builderui` (admin page-builder tweaks), `forms` (Formie additions), `web` (error pages) |
| `storage/imports/` | Content migration scripts from the live site, one folder per area (`blog/`, `products/`, `pages/` …) |

## Working on the site

- **Structure changes** (fields, sections, entry types) are saved to `config/project/` when made in the admin panel.
  Commit those YAML files with your change.
- **Back up the database** before running an import script or changing structure: `php craft db/backup`.
- **Import scripts** in `storage/imports/` run from the project root and skip content that already exists.
- **Live URLs are kept.** Every migrated page, post, product and industry keeps its live slug. Old URLs that live
  already redirects are in `config/redirects.php`.
- **No email is sent locally**: form submissions are saved under Admin → Formie → Submissions.

## Handing a copy to QA or another developer

```bash
php craft db/backup                                   # database → storage/backups/<file>.sql
cd web/uploads && zip -r ../../uploads.zip . && cd ../..   # images (blog/, products/ … at the zip's top level)
```

Send the `.sql` file, `uploads.zip` and the `CRAFT_SECURITY_KEY` value (not your whole `.env`: it holds the SMTP login).

## Documentation

| File | Contents |
|---|---|
| [QA_SETUP.md](QA_SETUP.md) | Setup guide for QA, with what to test |
| [QA_WALKTHROUGH.md](QA_WALKTHROUGH.md) | How menus, header and footer are managed in the admin panel |
| [DEVELOPMENT_PHASES.md](DEVELOPMENT_PHASES.md) | Development log: what was built in each phase and how |
| `*_MIGRATION_PLAN.md`, [CONTACT_PAGE_PLAN.md](CONTACT_PAGE_PLAN.md) | Plans for moving each content area from the live site |
