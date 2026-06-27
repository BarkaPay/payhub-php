# Contributing

Thanks for helping improve the PayHub PHP SDK.

## Development setup

```bash
composer install
```

## Checks (must pass before opening a PR)

```bash
composer test     # PHPUnit
composer stan     # PHPStan (level max)
composer cs       # php-cs-fixer (dry-run) — run `composer cs-fix` to apply
```

CI runs all three on PHP 8.1, 8.2 and 8.3.

## Scope & conventions

- The SDK mirrors the PayHub OpenAPI spec — keep the public surface in sync with
  the API. Prefer small, typed additions.
- No runtime dependencies: stick to `ext-curl` + `ext-json` so the SDK stays safe
  to embed inside WordPress / WooCommerce.
- Open an issue first for larger changes so we can align on the design.
- Add or update tests with every behaviour change; never call the live API from
  tests (use the injectable `HttpClientInterface`).

## Releasing (Packagist)

Packagist serves `barkapay/payhub-php` straight from Git tags — there is no
publish workflow to run. One-time setup by a maintainer:

1. Submit the repo once on https://packagist.org (`Submit` → the GitHub URL).
2. Enable the GitHub → Packagist webhook (Packagist shows the exact URL + token
   under the package's *Settings*; or install the Packagist GitHub App on the
   BarkaPay org). This auto-updates Packagist on every push/tag.

To cut a release: bump the version in `CHANGELOG.md`, commit, then

```bash
git tag vX.Y.Z
git push --follow-tags
```

Packagist picks up the new tag within seconds. The package follows SemVer; tags
must be `vX.Y.Z` (Composer also reads the matching `X.Y.Z` constraint).
