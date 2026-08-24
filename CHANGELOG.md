# Changelog

## [Unreleased]

### Domain correctness

- Validate and normalize dates, user IDs, user/operation types, amounts, and currencies when an `Operation` is created.
- Replace silent unsupported-state behavior with explicit exceptions.
- Store private-withdrawal allowance state by user and ISO week-year so out-of-order rows remain correct.
- Use explicit half-up rounding at the fee boundary.
- Harden exchange-rate and conversion validation.

### Input and operations

- Validate CSV readability and required headers.
- Include the failing source row in data-validation errors.
- Add explicit CLI usage and failure exit codes.
- Remove generated Composer dependencies from the current repository tree and add a root `.gitignore`.

### Tests and CI

- Add operation, currency, CSV, unordered-input, and ISO year-boundary coverage.
- Run strict Composer validation, lint, and PHPUnit on PHP 8.1, 8.2, and 8.3.
