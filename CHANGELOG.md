# Changelog

All notable changes to this project are documented here. This project follows
[Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- Initial PHP SDK: `Client` with `payments`, `transfers`, `balance`, `operators`
  resources plus `ping()` / `me()`.
- Typed DTOs (`Payment`, `Transfer`, `Balance`) and status enums.
- Webhook signature verification (`PayHub\Webhook`).
- Typed exception hierarchy mapped from the API error envelope.
- Zero-dependency curl transport with automatic retries on 429/503/network.
