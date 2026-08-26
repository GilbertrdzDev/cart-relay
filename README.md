# Cart Relay for WooCommerce

Cart Relay lets WooCommerce stores import and export the current cart using CSV files. It supports simple products and specific product variations identified by product ID, variation ID, or SKU.

## Requirements

- WordPress 6.5 or later
- PHP 8.2 or later
- WooCommerce
- Node.js 24.x for asset development and production builds
- Composer 2 for PHP dependencies and tests

## Development

```powershell
composer install
npm ci
composer validate --strict
composer audit
composer lint
composer test
composer check-version
composer phpcs
npm audit --audit-level=high
npm run typecheck
npm run build
npm run check:i18n
composer package
```

The PHP application is under `app/`, PHP views are under `resources/views/`, and the TypeScript and stylesheet sources are under `resources/assets/`. Vite writes production assets to `dist/`.

The Git repository is the development source of truth. WordPress.org SVN is used only for finished stable releases and directory assets.

## Governance

Cart Relay is a maintainer-led project maintained and released by Gilbert Rodríguez. Public issues and pull requests are proposals for review; submitting one does not grant contributor, committer, ownership, or release access, and inclusion is not guaranteed.

Only releases published through the official WordPress.org listing and verified releases from this repository are official Cart Relay releases. See [CONTRIBUTING.md](CONTRIBUTING.md) for the contribution and release-control policy.

## Production package

Run `composer package` after the production build. The packaging command creates:

- `build/cart-relay.zip`, containing one top-level `cart-relay/` directory.
- `build/package-manifest.txt`, containing a SHA-256 checksum for every packaged file.

The package includes compiled assets and required production Composer dependencies. It excludes development sources and dependencies such as `node_modules/`, tests, tools, source maps, local configuration, caches, and secrets.

## Translations

All user-facing source strings must be written in English and use the `cart-relay` text domain. PHP strings use the WordPress gettext functions directly. TypeScript calls must also pass the literal `cart-relay` domain so the production JavaScript remains discoverable by WordPress.org after minification.

Cart Relay includes complete bundled translations for Spanish (`es_ES` and `es_CO`), French (`fr_FR`), Brazilian Portuguese (`pt_BR`), and Italian (`it_IT`). Each locale ships with an editable PO catalog, a compiled MO catalog for PHP, and a handle-based JSON catalog for the production JavaScript bundle.

The canonical translation template is `languages/cart-relay.pot`. Regenerate it after building the production assets:

```powershell
npm run build
wp i18n make-pot . languages/cart-relay.pot --domain=cart-relay --exclude=build,node_modules,vendor,tests,tools
npm run build:i18n
npm run check:i18n
composer package
```

`npm run build:i18n` rebuilds every bundled PO, MO, and JavaScript JSON catalog from the reviewed translations in `tools/build-translations.mjs`. `npm run check:i18n` verifies that all bundled locales cover every POT message, preserve formatting placeholders and plural forms, match their compiled files byte for byte, and remain discoverable in the production JavaScript bundles. WordPress.org-hosted MO language packs take priority for PHP when available; `wp_set_script_translations()` connects the frontend bundle to its bundled locale JSON catalog.

GitHub Actions validates pushes and pull requests. Publishing a non-draft GitHub Release from a version tag on `main` validates, packages, and publishes the finished release to WordPress.org SVN through the protected `wordpress-org` environment.

## Shortcodes

- `[cart_relay_buttons]` renders both cart tools.
- `[cart_relay_import_form]` renders the CSV import control.
- `[cart_relay_export_button]` renders the CSV export control.

## Security

Please report vulnerabilities privately as described in [SECURITY.md](SECURITY.md).

## License

Cart Relay is licensed under GPL-2.0-or-later. See [LICENSE](LICENSE) and [THIRD_PARTY_NOTICES.txt](THIRD_PARTY_NOTICES.txt).
