# Wooshop

One reusable WordPress block theme and one modular WooCommerce companion plugin, shared by independent outdoor and smart-home stores.

**Implementation is incomplete.** Local native storefront/catalog/customer flows and marketplace stock/money foundations exist; provider-backed marketplace purchases, all documented controls/modules and release verification remain unfinished. See [implementation status](docs/implementation/README.md) and [release readiness](docs/implementation/release-readiness.md).

Source packages are in `packages/theme/wooshop` and `packages/plugin/wooshop-commerce`. Local synthetic stores run on loopback ports18880/18881. No production deployment, package publication or real-funds operation is configured.

```sh
rtk proxy python3 tools/bootstrap.py --verify-only
rtk proxy python3 tools/dev.py up
rtk proxy python3 tools/verify.py static
```

See [validated commands and prerequisites](docs/implementation/environment-and-command-reference.md). Secrets, downloaded dependencies, databases, browser evidence and development ZIPs remain in ignored `.local/` or `artifacts/`. The original project specifications are preserved under [docs](docs/README.md); [resume instructions](docs/implementation/RESUME.md) describe the next unfinished slice.
