# Repository Guidelines

## Project Structure & Module Organization

Cart Relay is a WordPress/WooCommerce plugin for CSV cart import/export. The plugin entry point is `cart-relay.php`; it loads Composer autoloading, defines plugin constants, registers activation/deactivation hooks, and starts `CartRelay\App\Core\Plugin`.

PHP source lives in `app/` under the `CartRelay\App\` PSR-4 namespace. Core bootstrapping and hook orchestration are in `app/Core/`, reusable helpers are in `app/Helpers/`, marker contracts are in `app/Interfaces/`, and feature components should be added under `app/Components/`. Views and assets live under `resources/`: PHP partials in `resources/views/`, admin and frontend Vite entries in `resources/assets/admin/js/app-admin.js` and `resources/assets/front/js/app-front.js`, and SCSS beside each bundle. WordPress page templates live in `templates/`.

## Code Search

Use the CocoIndex Code `ccc` skill first when exploring or searching project code. Use semantic search for unknown functionality, flows, classes, methods, implementations, or references; write concrete natural-language queries that describe the code's behavior, and add language or path filters when useful. Use structural `ccc grep` for specific syntax patterns such as method calls, class or function definitions, and similar structures. Refresh the index when it may be stale before trusting results, and inspect returned snippets and line ranges before opening complete files. Avoid broad manual `grep`, `find`, recursive directory reads, or mass file opening unless CocoIndex is unavailable or insufficient. If CocoIndex fails, run its diagnostics or check its status before falling back to manual search.

## Build, Test, and Development Commands

- `composer install` installs PHP dependencies and generates autoload files.
- `npm install` installs Vite, Sass, SweetAlert2, and related frontend tooling.
- `npm run dev` starts Vite development mode for WordPress asset integration.
- `npm run build` builds production assets into `dist/`.
- `npm run serve` previews the production Vite build.

Node must satisfy `^20.19.0 || >=22.12.0`. PHP must be `>=8.2`.

## Local WordPress Runtime

This plugin repository is symlinked into the plugins directory of a local WordPress install at `C:\laragon\www\wptest`. The local site URL is `https://wptest.test`.

When validation needs WordPress, WooCommerce, database, or plugin state, use WP-CLI from the WordPress root. The `wp` command is already available through the system `PATH`; call `wp` directly rather than referencing `wp.bat`, for example:

```powershell
cd C:\laragon\www\wptest
wp plugin status cart-relay
```

## Coding Style & Naming Conventions

Follow the existing WordPress-oriented PHP style: tabs for indentation in PHP files, spaced WordPress function calls such as `defined( 'ABSPATH' ) || exit;`, and English comments for primary classes, methods, and functions. Keep classes namespaced under `CartRelay\App\` and name files after their classes, for example `app/Core/AssetManager.php`.

Register behavior through `Loader`, `AssetManager`, and component interfaces rather than calling WordPress hook APIs directly in feature code.

## Testing Guidelines

No PHPUnit, PHPCS, lint, or coverage configuration is currently present. Until those are added, validate changes manually in a local WordPress + WooCommerce install, and run `npm run build` for asset changes. If tests are introduced, place PHP tests in a dedicated `tests/` tree and document the new command here.

## Commit & Pull Request Guidelines

The current Git history uses brief informal messages, so prefer short imperative commits such as `Add cart CSV importer`. Pull requests should describe the change, mention affected plugin areas, list manual verification steps, and include screenshots or screen recordings for admin or frontend UI changes.

## Security & Configuration Tips

Keep direct-access guards on PHP files and preserve path validation in template and asset-loading code. Do not commit `vendor/`, `node_modules/`, local IDE files, credentials, or site-specific WordPress configuration.
