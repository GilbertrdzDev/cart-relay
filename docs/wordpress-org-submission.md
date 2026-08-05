# WordPress.org submission

## Current publication identity

- Display name: `Cart Relay for WooCommerce`
- Requested slug: `cart-relay`
- Text domain: `cart-relay`
- Main file: `cart-relay.php`
- Initial version: `1.0.0`
- WordPress.org contributor: `gilbertrdzdev`
- Development repository: `https://github.com/GilbertrdzDev/cart-relay`
- Repository visibility: public

The public plugin directory currently returns no published plugin for `cart-relay`. The submission form remains the authoritative place to confirm that the slug can be assigned.

## Manual prerequisites

1. Verify that the `gilbertrdzdev` WordPress.org account can sign in and that its email is current.
2. Allow direct email from `plugins@wordpress.org` during review.
3. Create the directory artwork listed below without placing it inside the executable plugin package.

## Directory assets

These files are stored under `.wordpress-org/` in Git so the deployment workflow can copy them to the top-level WordPress.org SVN `assets/` directory:

- `banner-772x250.png`: primary directory banner.
- `banner-1544x500.png`: high-density version of the same banner.
- `icon-128x128.png`: standard plugin icon.
- `icon-256x256.png`: high-density plugin icon.
- `screenshot-1.png`: the Cart Relay settings screen.
- `screenshot-2.png`: import preview on the classic WooCommerce cart page.
- `screenshot-3.png`: successful cart import result.

The corresponding screenshot captions are maintained in the `Screenshots` section of `readme.txt`.

## Pre-submission validation

Before uploading the initial ZIP:

1. Install dependencies from the lock files.
2. Run Composer validation and the PHP test suite.
3. Run TypeScript type checking and the production asset build.
4. Generate the production ZIP with `composer package`.
5. Run WordPress Plugin Check against the extracted production package, including `plugin_repo`, `security`, `performance`, `accessibility`, and `general` checks.
6. Validate `readme.txt` with the WordPress.org readme validator.
7. Install the ZIP on a clean supported WordPress and PHP environment.
8. Test activation with WooCommerce, settings persistence, import, export, deactivation, uninstall, and a fresh reinstall.
9. Inspect the ZIP and confirm that it contains one top-level `cart-relay/` directory and no secrets, caches, source maps, tests, internal agent files, or development dependencies.

## Initial submission

1. Sign in at `https://wordpress.org/plugins/developers/add/`.
2. Confirm the final display name and requested slug before uploading because an approved slug cannot be renamed.
3. Upload the validated production ZIP.
4. Respond to review email from `plugins@wordpress.org` from the maintainer's direct account.
5. Do not deploy to SVN until the plugin is approved and WordPress.org grants commit access.

## Automated releases after approval

The first WordPress.org submission remains manual. After approval and SVN access:

1. Create the protected GitHub environment named `wordpress-org`.
2. Add the WordPress.org SVN-specific password as the environment secret `SVN_PASSWORD`. Do not use or share the normal account password.
3. Add the repository variable `WORDPRESS_ORG_DEPLOY_ENABLED` with the value `true` only when automated SVN publishing is authorized.
4. Merge the finished release into `main`, create a matching semantic version tag such as `v1.0.0`, and publish a non-draft, non-prerelease GitHub Release.

The release workflow verifies that the tag is on `main`, confirms that the tag, plugin header, version constant, `readme.txt`, and `package.json` versions match, runs all PHP and frontend checks, builds the production ZIP, runs Plugin Check on the staged package, attaches the ZIP and manifest to the GitHub Release, and then deploys the staged package and `.wordpress-org/` assets to WordPress.org SVN when deployment is enabled.

Git remains the development source of truth. WordPress.org SVN is used only for finished releases and directory assets.

## Accepted development-tree findings

Plugin Check is run against the generated production package, not the repository root. Repository-only files such as PHPUnit tests, packaging tools, CI configuration, and development dependencies are intentionally excluded from the ZIP. The final staged package must pass all `plugin_repo`, `security`, `performance`, `accessibility`, and `general` checks without errors or warnings before release.
