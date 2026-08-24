# Contributing

1. Open an issue describing the business rule, invalid input, or regression.
2. Keep each branch focused on one behavior change.
3. Add a test that demonstrates the expected rule or failure.
4. Run:

```bash
composer validate --strict --no-check-publish
composer check
```

5. Update the README or changelog when the public contract changes.

Never commit generated `vendor/` dependencies, real customer financial data, credentials, or proprietary rate feeds.
