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
composer package
```

The PHP application is under `app/`, PHP views are under `resources/views/`, and the TypeScript and stylesheet sources are under `resources/assets/`. Vite writes production assets to `dist/`.

The Git repository is the development source of truth. WordPress.org SVN will be used only for stable distribution after the plugin is approved.

## Production package

Run `composer package` after the production build. The packaging command creates:

- `build/cart-relay.zip`, containing one top-level `cart-relay/` directory.
- `build/package-manifest.txt`, containing a SHA-256 checksum for every packaged file.

The package includes compiled assets and required production Composer dependencies. It excludes development sources and dependencies such as `node_modules/`, tests, tools, source maps, local configuration, caches, and secrets.

GitHub Actions validates pushes and pull requests. Publishing a non-draft GitHub Release from a version tag on `main` also validates and packages the release. WordPress.org SVN deployment remains disabled until directory approval and explicit repository configuration.

## Shortcodes

- `[cart_relay_buttons]` renders both cart tools.
- `[cart_relay_import_form]` renders the CSV import control.
- `[cart_relay_export_button]` renders the CSV export control.

## Security

Please report vulnerabilities privately as described in [SECURITY.md](SECURITY.md).

## License

Cart Relay is licensed under GPL-2.0-or-later. See [LICENSE](LICENSE) and [THIRD_PARTY_NOTICES.txt](THIRD_PARTY_NOTICES.txt).
