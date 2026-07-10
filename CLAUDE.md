# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

WooCart Bridge is a WordPress plugin (PHP >= 8.2) whose purpose is importing/exporting WooCommerce carts via simple CSV files (SKU + quantity). The plugin scaffolding (Core, Loader, AssetManager, Component system) is in place, but the CSV import/export feature itself has not yet been implemented — `app/Components/` is currently an empty scaffold directory.

## Local WordPress environment

This working directory (`C:\devs\wordpress\plugins\woocart-bridge`) is symlinked into a local Laragon WordPress install's plugins folder at `C:\laragon\www\wptest\wp-content\plugins\woocart-bridge`. The site is served locally at `https://wptest.test`.

WP-CLI is on the system PATH (as `wp`) for validating/testing the plugin against the live local site (e.g. checking hooks, options, running the plugin's activation/deactivation, inspecting DB state). Run it from the WordPress root (`C:\laragon\www\wptest`), e.g.:
```
wp plugin list --path=C:\laragon\www\wptest
wp eval 'echo "test";' --path=C:\laragon\www\wptest
```

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

## Uso automático de Codex

- Claude decide de forma autónoma cuándo usar Codex, sin pedir permiso, siempre que su uso mejore la calidad del resultado.
- Usa Codex como revisor externo por defecto: el review-gate de Codex está habilitado por defecto en este proyecto.
- Flujo de trabajo: Claude Code implementa primero, y luego usa Codex para revisar, cuestionar o rescatar la implementación cuando corresponda.
- Después de implementar cambios medianos o grandes, ejecuta:
  ```
  /codex:review --background
  ```
- Ejecuta esa revisión especialmente cuando se modifica: lógica de negocio, imports/exports de carritos (CSV), hooks, AJAX, REST API, validaciones, permisos, nonces, sanitización, base de datos, configuración de build o tests.
- Para cambios riesgosos ejecuta:
  ```
  /codex:adversarial-review --base main --background
  ```
- Considera "cambios riesgosos" todo lo relacionado con: carrito (cart), orders, shipping, product meta, import/export CSV, admin pages, AJAX actions, REST endpoints, database schema, permissions, nonces, sanitización, validación, y compatibilidad con comportamiento existente.
- Usa Codex Rescue cuando te atasques, cuando haya bugs difíciles, o cuando necesites una segunda propuesta de implementación:
  ```
  /codex:rescue --background --fresh "<descripción clara del problema>"
  ```
- Usa Rescue especialmente cuando: no encuentres la causa raíz después de 2 intentos, un test siga fallando, el bug toque varios archivos, o haya incertidumbre técnica.
- Cuando lances un job en background, revisa siempre el resultado antes de cerrar la tarea:
  ```
  /codex:status
  /codex:result
  ```
- Si Codex encuentra problemas reales, corrígelos.
- No uses Codex para cambios mínimos como typos, formateo simple, renombres locales, comentarios o cambios visuales sin lógica.
- Valida siempre con los comandos disponibles del proyecto (`composer install`, `npm run build`, typecheck) o ejecución local cuando aplique.
- Antes de finalizar cualquier tarea, entrega un resumen corto indicando:
  - qué cambiaste
  - qué validaste
  - si se usó Codex
  - qué encontró Codex
  - qué quedó pendiente, si aplica