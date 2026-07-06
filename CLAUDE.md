# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

WooCart Bridge is a WordPress plugin (PHP >= 8.2) whose purpose is importing/exporting WooCommerce carts via simple CSV files (SKU + quantity). The plugin scaffolding (Core, Loader, AssetManager, Component system) is in place, but the CSV import/export feature itself has not yet been implemented — `app/Components/` is currently an empty scaffold directory.

## Commands

Backend (PHP, via Composer):
```
composer install
```
There is no PHPUnit/PHPCS config in this repo yet — do not assume `composer test` or `composer lint` exist unless you add them yourself.

Frontend (Vite build for admin/front JS+SCSS bundles):
```
npm install
npm run dev      # vite dev server
npm run build    # production build to dist/
npm run serve    # preview a production build
```
Requires Node `^20.19.0 || >=22.12.0` (see `package.json` engines).

There are no automated tests configured in this repository currently.

## Architecture

### Boot sequence
`woocart-bridge.php` is the plugin entry file WordPress reads. It defines `WOOCART_BRIDGE_DIR_PATH`/`WOOCART_BRIDGE_DIR_URL`, wires `register_activation_hook`/`register_deactivation_hook` to `App\Core\Activator`/`Deactivator`, and on `plugins_loaded` instantiates `App\Core\Plugin` and calls `->run()`.

`Plugin::run()` is the orchestrator:
1. `setLocale()` — registers `I18n::loadPluginTextdomain` on `plugins_loaded`.
2. `setAdminHooks()` / `setFrontendHooks()` — construct `Admin`/`Front`, which each register their own Vite asset bundle and hooks.
3. `registerComponents()` — iterates classes added via `Plugin::addComponents(...$classes)` and, for each, checks which of the marker interfaces it implements (see below) and calls the matching registration method.
4. `asset_manager->init()` — hooks the deferred asset dispatch into `wp_enqueue_scripts` / `admin_enqueue_scripts`.
5. `loader->run()` — actually calls `add_action`/`add_filter`/`add_shortcode` for everything queued during steps 1-4.

**Nothing touches WordPress's hook API directly until `Loader::run()` fires.** All of `Plugin`, `Admin`, `Front`, and Components only *queue* hooks by calling `$loader->add_action(...)` / `add_filter(...)` / `add_shortcode(...)`; the actual `add_action()`/`add_filter()`/`add_shortcode()` WP calls happen once, in one place, at the end of the boot sequence.

### Component extension pattern
New features are added as "Components" in `app/Components/`, registered by calling `Plugin::addComponents(SomeComponent::class, ...)`. A Component opts into lifecycle hooks purely by implementing interfaces from `app/Interfaces/`:
- `EnqueueStyle` / `EnqueueScript` — `enqueue_styles(AssetManager)` / `enqueue_scripts(AssetManager)`
- `HasActions` / `HasFilters` — `register_actions(Loader)` / `register_filters(Loader)`
- `HasShortcodes` — `register_shortcodes(Loader)` (instance-based, for components registering multiple shortcodes)
- `Shortcode` — `static::register_shortcode(Loader)` + `static::render(array $atts, string $content = '')` (for a single self-contained shortcode class)

A Component can implement any combination of these; `Plugin::registerComponents()` checks each with `instanceof` and calls whichever methods apply. This is the intended place to build the CSV import/export functionality described in `composer.json`.

### AssetManager (`app/Core/AssetManager.php`)
Central registry for both classic WP assets and Vite-built assets, split into frontend/admin pools:
- `style()`/`script()`/`module()` and `admin_style()`/`admin_script()`/`admin_module()` queue plain `wp_enqueue_style`/`wp_enqueue_script`/`wp_enqueue_script_module` calls (version defaults to file mtime when `version: true` is passed).
- `vite()` / `frontend_vite()` / `admin_vite()` queue entries built via `Kucrut\Vite\enqueue_asset` (the `kucrut/vite-for-wp` Composer package), pointing at `dist/` — this is what makes `npm run dev` (HMR) vs `npm run build` transparent to PHP.
- Admin-only assets support a `screens` option (`admin_style`/`admin_script`/`admin_module`, or `options['screens']` for `admin_vite`) to restrict enqueueing to specific `get_current_screen()->id` values.
- All asset paths are validated against path traversal / absolute paths (`normalize_relative_path`); handles are sanitized via `sanitize_key`.
- Actual enqueueing is deferred: `init()` hooks `dispatch_frontend_assets`/`dispatch_admin_assets` onto `wp_enqueue_scripts`/`admin_enqueue_scripts` via the `Loader`.

### ComponentCompiler (`app/Core/ComponentCompiler.php`)
Singleton PHP template renderer (accessed as `Plugin::$component`). `render('some.dotted.name', $data)` maps dots to directory separators under `resources/views/components/` and includes the file with `$data` extracted into scope (output-buffered). Component names are validated by regex and resolved paths are checked against the base directory to prevent traversal — treat this the same way you'd treat any other template-include boundary (don't relax those checks when adding new components).

### Frontend build (Vite)
`vite.config.ts` uses `@kucrut/vite-for-wp`'s `v4wp()` plugin with two entrypoints, output to `dist/`:
- `resources/assets/admin/js/app-admin.js` → imports `bootstrap-admin.js`, `woocart-bridge-admin.js`, `../css/woocart-bridge-admin.scss`
- `resources/assets/front/js/app-front.js` → imports `bootstrap-front.js`, `woocart-bridge-front.js`, `../css/woocart-bridge-front.scss`

`resources/helpers/utils/WoocartBridgeHelpers.js` is a shared static-method utility class (SweetAlert2-based loading/error UI helpers, ajax error formatting, URL query param parsing) meant to be imported by the admin/front bundles.

### PHP helpers (`app/Helpers/`)
- `Str` — camel/snake/kebab/studly case conversion helpers (with internal memoization caches).
- `Arr::addForKey()` — insert into an array before/after a matched key or `key`-in-value match.
- `BC::parseExtraData()` — parses legacy `key=value|key=value` delimited strings.
- `PageTemplater` — registers custom page templates from `templates/` into the WP page-attributes template dropdown (`theme_page_templates` filter) and serves them via `template_include`; templates are registered through `Admin::templates()` (`PageTemplater->addTemplates([...])->run()`), currently empty.

## Comment style (from user's global CLAUDE.md)
Comments must be in English. Only document methods/functions/classes and other primary code elements — no line-by-line inline comments.