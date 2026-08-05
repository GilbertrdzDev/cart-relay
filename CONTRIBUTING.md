# Contributing to Cart Relay

## Development workflow

1. Branch from `develop` using `feature/<short-description>` or `fix/<short-description>`.
2. Keep changes focused and preserve the existing component architecture.
3. Add or update tests for behavioral changes.
4. Run the local validation commands.
5. Open a pull request into `develop` with the purpose, affected areas, and verification results.

The `main` branch represents stable release history. Release and hotfix branches are merged only after all validation passes.

## Local validation

```powershell
composer validate --strict
composer test
npm run typecheck
npm run build
```

PHP identifiers, documentation, comments, configuration text, and UI copy should be written in English. Use the `CartRelay\App\` namespace and `cart_relay_` for new WordPress-level identifiers.

## Security

Report vulnerabilities through the private process in `SECURITY.md`, not through a public issue.
