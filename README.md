# Arrow Systems website (Craft CMS)

The new Arrow Systems website: [arrsys.com](https://arrsys.com) rebuilt in **Craft CMS 5**. This repository holds the
templates, the project config, custom modules and the scripts that moved the content over from the live WordPress site.

## Tech stack

| | |
|---|---|
| CMS | Craft CMS 5.11 (Pro) |
| Plugins | Formie (forms), Navigation (menus), CKEditor (rich text) |
| PHP | 8.2 or newer |
| Database | MySQL 8.0.17+ or MariaDB 10.4.6+ |
| Front end | Twig templates, plain CSS and JavaScript (no build step) |

## Quick start

The full step-by-step guide is in **[QA_SETUP.md](QA_SETUP.md)**. In short:

```bash
git clone git@github.com:avadhkanaiya-maker/arrow-system.git arrow-system
cd arrow-system
composer install
# add .env (see QA_SETUP.md), unzip uploads.zip into web/uploads/, import the database
php craft serve --port=8081
```

- Website: http://localhost:8081
- Admin panel: http://localhost:8081/admin

The database dump, `uploads.zip` (images and PDFs) and the `.env` values are not in Git. Ask the developer for them.

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

- **Structure changes** (fields, sections, entry types) live in `config/project/`. After pulling, apply them with
  `php craft project-config/apply`. `php craft project-config/diff` should then say "No pending project config YAML changes."
- **Back up the database** before running an import script or changing structure: `php craft db/backup`.
- **Import scripts** in `storage/imports/` run from the project root, e.g.
  `php storage/imports/pages/29_flexible_packaging_color_label_printer.php`. They skip content that already exists.
- **Live URLs are kept.** Every migrated page, post, product and industry keeps its live slug. Old URLs that live
  already redirects are in `config/redirects.php`.
- If the site shows *"Calling unknown method … navigation()"* or a plugin seems missing, run `php craft clear-caches/all`.

## Documentation

| File | Contents |
|---|---|
| [QA_SETUP.md](QA_SETUP.md) | Setting up the project locally (for QA and new developers) |
| [QA_WALKTHROUGH.md](QA_WALKTHROUGH.md) | How menus, header and footer are managed in the admin panel |
| [DEVELOPMENT_PHASES.md](DEVELOPMENT_PHASES.md) | Development log: what was built in each phase and how |
| `*_MIGRATION_PLAN.md`, [CONTACT_PAGE_PLAN.md](CONTACT_PAGE_PLAN.md) | Plans for moving each content area from the live site |
