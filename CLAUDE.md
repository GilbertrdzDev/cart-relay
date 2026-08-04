# Cart Relay development guide

Cart Relay is a WordPress and WooCommerce plugin for importing and exporting the current cart with CSV files. The production plugin requires PHP 8.2 or later, WordPress 6.5 or later, and WooCommerce.

## Architecture

- `cart-relay.php` defines the plugin metadata and constants and starts `CartRelay\App\Core\Plugin`.
- `app/Core/` owns hook orchestration, component registration, rendering, and Vite asset integration.
- `app/Components/` contains the admin settings, cart controls, CSV import, and CSV export features.
- `app/Helpers/` contains settings, CSV, and WooCommerce product helpers.
- `resources/views/` contains PHP view components.
- `resources/assets/` contains TypeScript and stylesheet sources compiled by Vite into `dist/`.
- `tests/Unit/` contains isolated PHPUnit regression tests.

Register new WordPress behavior through `Loader` and the component interfaces. Keep WordPress-level identifiers prefixed with `cart_relay_`, PHP code under `CartRelay\App\`, and visible strings translatable with the `cart-relay` text domain.

## Local runtime

The repository is symlinked to `C:\laragon\www\wptest\wp-content\plugins\cart-relay`, served at `https://wptest.test`.

```powershell
cd C:\laragon\www\wptest
wp plugin status cart-relay
```

## Validation

```powershell
composer validate --strict
composer test
npm run typecheck
npm run build
```

Exercise import, export, settings, guest access, and cart refresh behavior in WordPress whenever those flows change. Keep the human-readable TypeScript and stylesheet sources available with any minified production assets.
