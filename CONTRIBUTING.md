# Contributing to Cart Relay

Cart Relay is a maintainer-led project maintained and released by Gilbert Rodríguez. The public repository provides source transparency, issue tracking, and release traceability; it does not operate as an open-commit or community-governed project.

## Reports and proposals

Bug reports, compatibility reports, and narrowly scoped improvement proposals may be submitted through GitHub. Feature proposals are evaluated against the published product scope, architecture, security requirements, user impact, and long-term maintenance cost. Submission does not guarantee acceptance or implementation.

Potential security vulnerabilities must be reported privately according to [SECURITY.md](SECURITY.md), not through a public issue.

## Development workflow

1. Branch from `develop` using `feature/<short-description>` or `fix/<short-description>`.
2. Keep changes focused and preserve the existing component architecture.
3. Add or update tests for behavioral changes.
4. Run the local validation commands.
5. Open a pull request into `develop` with the purpose, affected areas, and verification results.

The `main` branch represents stable release history. Release and hotfix branches are merged only after all validation passes.

## Pull requests

Discuss substantial changes with the maintainer before starting implementation. Unsolicited pull requests may be declined or closed when they fall outside the planned scope or create an unsuitable maintenance burden.

By submitting code for inclusion, the author agrees that the contribution may be distributed under the project's GPL-2.0-or-later license. The maintainer may request changes, decline the proposal, or implement the underlying idea differently.

## Local validation

```powershell
composer validate --strict
composer test
npm run typecheck
npm run build
npm run check:i18n
```

PHP identifiers, documentation, comments, configuration text, and UI copy should be written in English. Use the `CartRelay\App\` namespace and `cart_relay_` for new WordPress-level identifiers.

Every user-facing string must be translatable with the literal `cart-relay` text domain. After changing copy, rebuild the assets, regenerate `languages/cart-relay.pot` as documented in [README.md](README.md), and run the internationalization check. Do not rely on a variable or wrapper default for the domain because WordPress.org must be able to extract the literal call from the production package.

## Roles and release control

Opening an issue, submitting a pull request, participating in a discussion, or having code merged does not automatically grant WordPress.org contributor status, SVN commit access, repository permissions, ownership, support-representative status, or release access.

Project roles are assigned explicitly by the maintainer. Only the maintainer may approve releases, publish GitHub releases, or deploy the official plugin through the `cart-relay` WordPress.org SVN repository.

## Security

Report vulnerabilities through the private process in [SECURITY.md](SECURITY.md), not through a public issue.
