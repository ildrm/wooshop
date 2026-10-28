# Local development and verification

Run these commands from `/Users/shahinilderemi/Documents/tmp/iranbroker-wp`. The user repository instruction requires the `rtk` prefix. Docker Desktop must be running. The environment is synthetic and binds loopback ports18880/18881/18882 only. Development configuration and credentials stay under ignored `.local/`; do not copy those values into a command, screenshot, ticket or package.

The installed inputs were verified: WordPress7.1.3, WooCommerce11.2.1, container PHP8.4.27, MariaDB10.11.19, WP-CLI2.12.0, host PHP8.5.8, Python3, Node26.12.0/npm11.20 and Playwright1.64.1 using existing Google Chrome156.0.8078.48. Initial order tests used legacy storage; record HPOS enablement and order-continuity evidence separately.

```sh
rtk proxy python3 tools/bootstrap.py --verify-only
rtk proxy python3 tools/dev.py prepare
rtk proxy python3 tools/dev.py up
rtk proxy python3 tools/dev.py status
```

The pinned input downloader also supports `rtk proxy python3 tools/bootstrap.py` for missing reviewed inputs. Its existing-cache verification was executed; missing-input network download through this new helper is NOT RUN locally. It fails on changed hashes, bounded size or unsafe paths. `tools/dev.py install` installs the cached WC archive and activates the custom source packages on both stores; it was executed during initial setup. Do not reinstall merely to inspect status.

```sh
rtk proxy python3 tools/dev.py wp outdoor eval-file /workspace/tools/seed.php outdoor configure-profile
rtk proxy python3 tools/dev.py wp smart-home eval-file /workspace/tools/seed.php smart-home configure-profile
```

Those explicit local-only seed commands were executed. They add stable synthetic fixtures, preserve merchant edits and use shared package code. The one-time `configure-profile` flag initializes native branding/navigation. All products and external supplier destinations are labeled synthetic. Mail returns a real blocked result; the guard does not pretend successful delivery. No approved payment, SMS, mail, search-index, malware-scanner or marketplace provider is configured.

Verification wrappers capture bounded redacted output, UTC timestamps, exit status and source fingerprints under `artifacts/verification/`. Source-specific worker reports remain in this directory. Use the fixed suites rather than claiming every test in the specifications was executed.

```sh
rtk proxy python3 tools/verify.py static
rtk proxy python3 tools/verify.py integration-outdoor
rtk proxy python3 tools/verify.py integration-smart-home
rtk proxy python3 tools/verify.py browser
rtk proxy python3 tools/verify.py package
rtk proxy python3 tools/verify.py hygiene
rtk proxy python3 tools/dev.py package-site
rtk proxy python3 tools/traceability.py
```

The underlying pure-domain, WP integration and browser commands have executed; the new combined integration wrapper's first full run is recorded separately in `verification-runs.csv`. `package-site` installs release ZIPs into the third database without custom source bind mounts. Rebuild ZIPs after source changes before this check. Artifacts are incomplete development builds; installing them locally is not a production deployment.

```sh
rtk proxy python3 tools/dev.py rotate-admin
rtk proxy python3 tools/dev.py stop
```

The rotation command was executed and sends new synthetic passwords through stdin, never argv/output. Stopping services retains bind-mounted local data. There is no production deployment command. The CI workflow is authored but remote CI is NOT RUN; it must be validated on its declared runner before any CI success claim.

Fixture administrator passwords are distinct per site. The legacy `admin` key is an Outdoor-only runner alias; other site values have their own keys. `rtk proxy python3 tools/credentials-check.py` performed nine native WordPress hash comparisons: each accepted only its own site password. It does not establish MFA or production authentication readiness.

`rtk proxy python3 tools/reproducibility.py` executed two consecutive builds of frozen source and compared archive hashes. Current theme/plugin checksums and file provenance are in `artifacts/releases/manifest.json`, with the measured result in `reproducibility.json`. The latest header correction was rebuilt and installed independently; runtime parity was verified without relying on matching version strings.

The installed third site also passed `tools/integration.py --site package-check` after the neutral synthetic profile was added. The first attempt failed its explicit missing-fixture precondition and remains in the verification history. `tools/independence.py` verified plugin/native theme and missing-companion/WooCommerce fallbacks, restored watched configuration and retained only annotated private/drafted probes. Its execution requires a matching manifest checksum and `--run`; use the exact recorded command in `independence-evidence.json`.

The bounded DB recovery tool is **only** for the synthetic third database. Its concrete backup/restore operation required separate user approval after automatic approval review rejected the first attempt; that approval was then supplied. Do not run it against the two source stores. It checks exact runtime/database/mount/credential scope, holds native maintenance, keeps a private0600 checksum-verified backup and verifies table/schema/order/ledger/settings equality. See `recovery-evidence.json` for actual outcome and attempt history. This is separate from production encrypted/offsite backups, RPO/RTO and provider reconciliation.
