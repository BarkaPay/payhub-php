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
