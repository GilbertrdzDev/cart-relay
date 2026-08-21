=== Cart Relay for WooCommerce ===
Contributors: gilbertrdzdev
Tags: woocommerce, cart, csv, import, export
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Import and export WooCommerce carts with straightforward CSV files based on product IDs, variation IDs, SKUs, and quantities.

== Description ==

Cart Relay gives customers a controlled way to download the current WooCommerce cart as a CSV file and restore products from a compatible CSV file.

The plugin provides:

* Cart export with product ID, variation ID, SKU, product name, quantity, price, and subtotal.
* Cart import by product ID, variation ID, or SKU.
* Validation for product status, purchasability, supported product type, quantity, and stock.
* Merge and replace import modes.
* Optional restriction to logged-in customers.
* Configurable button labels and placement on the classic WooCommerce cart page.
* Shortcodes for custom placement.

Cart Relay requires WooCommerce. Its automatic cart placement is designed for the classic cart template. The controls can also be placed with `[cart_relay_buttons]`, `[cart_relay_import_form]`, and `[cart_relay_export_button]`.

Human-readable TypeScript and stylesheet sources, dependency manifests, and build instructions are available in the [public development repository](https://github.com/GilbertrdzDev/cart-relay).

= Privacy =

Cart Relay does not contact external services, add tracking, or transmit cart data outside the WordPress site.

Uploaded CSV files are processed during the current request and are not stored by Cart Relay. Imported products are added to the customer's normal WooCommerce cart session. Plugin configuration is stored in the `cart_relay_settings` WordPress option and is removed when the plugin is uninstalled.

== Installation ==

1. Install and activate WooCommerce.
2. Upload the Cart Relay plugin folder to `/wp-content/plugins/` or install the release ZIP through **Plugins > Add New > Upload Plugin**.
3. Activate **Cart Relay for WooCommerce**.
4. Open **WooCommerce > Cart Relay** to configure the cart tools.
5. Visit the classic WooCommerce cart page or add one of the provided shortcodes to a page.

== Screenshots ==

1. Configure cart import, export, access, labels, and placement from the Cart Relay settings page.
2. Review validated products, quantities, prices, and subtotals before importing a CSV file.
3. Confirm a successful import on the WooCommerce cart page with updated products and quantities.

== Frequently Asked Questions ==

= Which CSV columns can I use for imports? =

Every import must include `quantity` and at least one of `product_id`, `variation_id`, or `sku`. Files exported by Cart Relay can be imported directly.

= Which WooCommerce product types are supported? =

Cart Relay imports simple products and specific variations. Variable parent products require a variation ID or a variation SKU.

= Does Cart Relay work with the WooCommerce Cart block? =

The automatic controls target the classic cart template. Cart Relay does not currently inject controls into the WooCommerce Cart block. Shortcodes can be used in compatible shortcode-based layouts.

= Are uploaded CSV files stored? =

No. Cart Relay reads the temporary upload during the request and does not retain the file.

= Does Cart Relay send data to another service? =

No. The plugin has no telemetry and does not contact an external service.

== Changelog ==

= 1.0.1 =

* Added responsive settings tabs.
* Excluded development-only dependency metadata from the production package.

= 1.0.0 =

* Initial release.
* Added CSV cart import and export.
* Added merge and replace import modes.
* Added settings for availability, labels, login requirements, and classic cart placement.
