#!/usr/bin/env python3
"""Refresh factual implementation checkpoint/dependency records, without changing specs."""
from pathlib import Path
from datetime import datetime, timezone
import csv
import hashlib
import json
import subprocess

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'docs/implementation'
now = datetime.now(timezone.utc).isoformat()
read_full = [
    'docs/07-plugin/plugin-kernel-and-module-lifecycle.md',
    'docs/07-plugin/activation-migration-deactivation-uninstall.md',
    'docs/07-plugin/settings-permissions-and-diagnostics.md',
    'docs/06-theme/customization-and-editor-permissions.md',
    'docs/07-plugin/module-catalog.csv',
    'docs/09-api-integrations/webhook-and-job-contracts.md',
    'docs/09-api-integrations/versioning-errors-idempotency-and-limits.md',
    'docs/11-security-privacy/identity-api-upload-and-content-security.md',
    'docs/11-security-privacy/supply-chain-secure-development-and-updates.md',
    'docs/13-operations/backups-recovery-and-runbooks.md',
    'docs/14-delivery/quality-gates-and-handover.md',
    'docs/14-delivery/future-engineering-standards-and-repository-layout.md',
]
read_selected = [
    'docs/02-requirements/requirements.csv', 'docs/02-requirements/nonfunctional-requirements.csv',
    'docs/00-governance/raci.csv', 'docs/14-delivery/work-breakdown-structure.csv',
    'docs/14-delivery/implementation-backlog.csv', 'docs/09-api-integrations/openapi.yaml',
    'docs/implementation/master-implementation-prompt.md',
]
reads = [{'path': path, 'sha256': hashlib.sha256((ROOT / path).read_bytes()).hexdigest(), 'reading_status': 'full' if path in read_full else 'selected portions', 'note': 'Actual bounded root intake; earlier unretained reads are not silently counted.'} for path in read_full + read_selected]
fingerprints = {str(p.relative_to(ROOT)): hashlib.sha256(p.read_bytes()).hexdigest() for p in sorted((ROOT / 'packages').rglob('*')) if p.is_file()}
root_evidence = {
    'schema_version': 1, 'worker': 'root', 'updated_at': now,
    'delivery_status': 'IN_PROGRESS; complete project acceptance not achieved', 'read_paths': reads,
    'source_fingerprints': fingerprints,
    'commands': [
        {'command': 'rtk proxy python3 tools/dev.py wp outdoor eval-file /workspace/tests/core/wordpress.php', 'result': 'PASS 25 actual WordPress/MariaDB checks after optional registry transitions', 'environment': 'WP7.1.3/WC11.2.1/PHP8.4.27/MariaDB10.11.19; legacy WC order storage at execution'},
        {'command': "rtk proxy npx playwright test --grep 'actual WordPress' --output artifacts/auth-browser", 'result': 'PASS 1 actual login/REST nonce/idempotency browser test; report stats expected1 unexpected0 duration9.263s', 'environment': 'Playwright1.64.1; installed Google Chrome156; localhost18880; synthetic credentials'},
        {'command': 'rtk proxy python3 tools/bootstrap.py --verify-only', 'result': 'PASS reviewed WP-CLI and WC input hashes', 'environment': 'Host filesystem; no new network download through this helper'},
        {'command': 'rtk proxy npm audit --json', 'result': 'BLOCKED two actual advisory attempts: ECONNRESET then ETIMEDOUT; both exit1; no vulnerability result', 'environment': 'Official registry.npmjs.org advisory API'},
        {'command': 'rtk proxy python3 tools/dev.py up', 'result': 'PASS restored healthy local services after daemon interruption; preserved existing database/source binds', 'environment': 'Official digest-pinned Docker images; no reinstall/database reset'},
        {'command': '.github/workflows/verify.yml', 'result': 'NOT_RUN remote CI workflow authored only', 'environment': 'ubuntu24.04 runner execution not available in this run'},
    ],
    'failures_and_limits': ['Initial login race exposed a synthetic fixture password in a failure context; removed artifact, rotated all fixture administrators and invalidated sessions.', 'Official Chromium download blocked403 location policy; used existing Chrome instead.', 'Initial product browser assertions expected old WC markup; corrected against installed WC11.2.1, preserving cart and supplier-behavior assertions.', 'Docker daemon stopped during execution; local runtime restarted without data reset.', 'Initial native CRUD evidence is legacy storage only; later explicit supported HPOS evidence is in assurance-followup.json.', 'No complete upstream/container vulnerability, WCAG, SEO, privacy, performance, disaster recovery or provider approval claim.'],
}
(OUT / 'evidence-root.json').write_text(json.dumps(root_evidence, indent=2) + '\n')

dependencies = [
    {'name': 'WordPress', 'version': '7.1.3', 'scope': 'external runtime', 'source': 'https://wordpress.org/download/releases/', 'integrity': 'wordpress Docker digest a928da055e6506c17189e232d226dc1f86bc1f229902a88e6ec063650149aba8', 'license': 'GPL-2.0-or-later', 'owner': 'WooCommerce Solution Specialist', 'cost': 'open source; hosting separate', 'verification': 'Installed runtime version; finite native local checks; not broad supported matrix', 'exit': 'Native export/data ownership; reviewed migration required'},
    {'name': 'WooCommerce', 'version': '11.2.1', 'scope': 'external runtime; not bundled', 'source': 'https://downloads.wordpress.org/plugin/woocommerce.11.2.1.zip', 'integrity': 'sha256:5317434cb381b5fdc7c1dd6ee258324f08c390976aaf8d1199f2a0e9a3ab8586', 'license': 'GPL-3.0-or-later', 'owner': 'WooCommerce Solution Specialist', 'cost': 'open source; optional providers separate', 'verification': 'Installed header/source/license; native CRUD/StoreAPI checks; initial legacy storage, HPOS evidence separate', 'exit': 'Preserve native products/orders; supported migration and obligation transfer'},
    {'name': 'PHP', 'version': '8.4.27 runtime;8.5.8 host', 'scope': 'runtime/development', 'source': 'https://www.php.net/releases/', 'integrity': 'Container digest pin; host runtime version observed', 'license': 'PHP-3.01', 'owner': 'DevOps/SRE Engineer', 'cost': 'open source', 'verification': 'Actual container execution and host lint/domain only', 'exit': 'Retest declared PHP compatibility before upgrade'},
    {'name': 'Python/zlib', 'version': 'Python3.14.5;zlib1.2.12', 'scope': 'development build tooling', 'source': 'Existing host runtime', 'integrity': 'Observed sys.version and zlib.ZLIB_RUNTIME_VERSION', 'license': 'PSF/zlib upstream terms;not bundled', 'owner': 'Release/Maintenance Owner', 'cost': 'open source', 'verification': 'Two consecutive frozen-source ZIP builds byte-identical in this environment', 'exit': 'Retest packaging and guard scripts before runtime upgrade'},
    {'name': 'Node.js/npm', 'version': 'Node26.12.0;npm11.20', 'scope': 'development tooling;not bundled', 'source': 'Existing host runtimes;root package-lock.json', 'integrity': 'Observed local CLI versions and npm package integrity fields', 'license': 'Upstream Node MIT/npm Artistic-2.0 terms;not redistributed', 'owner': 'Test Automation Engineer', 'cost': 'open source', 'verification': 'Browser tests/syntax checks executed; advisory service unavailable on two attempts', 'exit': 'Retest locked development dependencies before update'},
    {'name': 'MariaDB', 'version': '10.11.19-MariaDB-ubu2204', 'scope': 'local database', 'source': 'https://hub.docker.com/_/mariadb', 'integrity': 'sha256:d769e37960821dfdcf096954f6e7ac89f747e116c1bba98e48edd265dfc7deae', 'license': 'GPL-2.0-only core;image component licenses not exhaustively inventoried', 'owner': 'Database/Data Architect', 'cost': 'open source; operations separate', 'verification': 'SELECT VERSION; InnoDB transactions and real20worker reservation race; no MySQL support inference', 'exit': 'Supported SQL export/recovery plus business reconciliation'},
    {'name': 'WP-CLI', 'version': '2.12.0', 'scope': 'development', 'source': 'https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar', 'integrity': 'sha256:ce34ddd838f7351d6759068d09793f26755463b4a4610a5a5c0a97b68220d85c', 'license': 'MIT', 'owner': 'Release/Maintenance Owner', 'cost': 'open source', 'verification': 'Existing official installed PHAR copied locally; exact hash verified', 'exit': 'Development tool only; no shipped dependency'},
    {'name': 'Google Chrome', 'version': '156.0.8078.48', 'scope': 'existing development browser', 'source': 'existing /Applications/Google Chrome.app', 'integrity': 'Local installed executable version observed; not downloaded/bundled', 'license': 'Vendor terms; not redistributed', 'owner': 'Test Automation Engineer', 'cost': 'existing local installation', 'verification': 'Actual headless Playwright browser execution; temporary profiles', 'exit': 'Retest supported browser matrix; alternate engines NOT RUN'},
    {'name': 'actions/checkout', 'version': '4.2.2', 'scope': 'CI only; not executed remotely', 'source': 'https://github.com/actions/checkout/releases/tag/v4.2.2', 'integrity': 'commit:11bd71901bbe5b1630ceea73d27597364c9af683', 'license': 'Upstream repository license review pending;not redistributed', 'owner': 'Release/Maintenance Owner', 'cost': 'runner/service usage separate', 'verification': 'Official release/commit identity verified2026-10-28; persist-credentials:false; CI support/vulnerability execution remains NOT_RUN', 'exit': 'Replace CI checkout step; no runtime business dependency'},
]
lock = json.loads((ROOT / 'package-lock.json').read_text())
for path, package in lock['packages'].items():
    if not path:
        continue
    name = path.removeprefix('node_modules/')
    dependencies.append({'name': name, 'version': package['version'], 'scope': 'development only; not bundled', 'source': package['resolved'], 'integrity': package['integrity'], 'license': package.get('license', 'not recorded'), 'owner': 'Test Automation Engineer', 'cost': 'open source', 'verification': 'Locked npm input; lifecycle scripts ignored; real tests executed; advisory audit BLOCKED ECONNRESET', 'exit': 'Replace development runner with equivalent behavioral checks'})
for name in ['marketplace/payment/payout provider', 'MFA/step-up provider', 'mail/SMS/recipient verification provider', 'malware scanning provider', 'advanced bundles/composites/subscription/booking/service extensions', 'external search/feed/analytics provider']:
    dependencies.append({'name': name, 'version': 'UNSELECTED', 'scope': 'external prerequisite', 'source': 'docs/09-api-integrations/integration-catalog-and-data-mapping.md', 'integrity': 'NOT_AVAILABLE', 'license': 'UNVERIFIED;no purchase or redistribution authorized', 'owner': 'API/Integration Engineer', 'cost': 'unselected;potential usage/subscription/license cost', 'verification': 'BLOCKED selected exact version/account/contract and business decisions absent; independent implementation continues', 'exit': 'Approved export/obligation handoff and provider-specific exit plan required'})
with (OUT / 'dependency-and-provider-verification.csv').open('w', newline='') as handle:
    writer = csv.DictWriter(handle, fieldnames=list(dependencies[0])); writer.writeheader(); writer.writerows(dependencies)
sbom = {'schema_version': 1, 'generated_at': now, 'scope': 'Actual direct custom-product/runtime prerequisites and complete root npm lock dependency list; not an upstream WordPress/WooCommerce/container OS transitive SBOM', 'limitations': 'No claim of verified licensing for every upstream bundled component, vulnerability clearance, paid extension availability or production approval.', 'custom_products': [{'name': name, 'version': '0.1.0', 'license': 'GPL-2.0-or-later', 'third_party_runtime_code_bundled': False} for name in ['wooshop', 'wooshop-commerce']], 'dependencies': dependencies}
(OUT / 'dependency-sbom.json').write_text(json.dumps(sbom, indent=2) + '\n')
subprocess.run(['python3', 'tools/traceability.py'], cwd=ROOT, check=True)
with (OUT / 'work-package-status.csv').open() as handle: work_rows = list(csv.DictReader(handle))
with (OUT / 'requirements-implementation-matrix.csv').open() as handle: requirement_rows = list(csv.DictReader(handle))
with (OUT / 'verification-runs.csv').open() as handle: runs = list(csv.DictReader(handle))
revision = subprocess.run(['git', 'rev-parse', 'HEAD'], cwd=ROOT, capture_output=True, text=True, check=True).stdout.strip()
assurance_path = OUT / 'assurance-followup.json'
assurance = json.loads(assurance_path.read_text()) if assurance_path.exists() else {}
sites = assurance.get('site_results', {})
hpos_verified = all(sites.get(site, {}).get('hpos', {}).get('result') == 'PASS' for site in ['outdoor', 'smart-home'])
storage = 'Both source stores HPOS authoritative with native compatibility sync; explicit legacy-to-HPOS order/snapshot continuity PASS; see assurance-followup.json' if hpos_verified else 'Initial source tests legacy; inspect assurance-followup.json for current HPOS state'
state = {'schema_version': 1, 'updated_at': now, 'project': 'wooshop; independent outdoor and smart-home WordPress stores', 'repository_revision': revision, 'working_tree': 'Uncommitted implementation and original documentation preserved; no commit/push performed', 'source_fingerprint': hashlib.sha256(json.dumps(fingerprints, sort_keys=True).encode()).hexdigest(), 'status': 'IN_PROGRESS', 'delivery_conclusion': 'Implementation incomplete; not a verified release candidate', 'stage': 'WP-G011 optional module lifecycle integration and integrated runtime/package assurance', 'requirement_matrix': 'docs/implementation/requirements-implementation-matrix.csv', 'work_packages': {status: [r['work_package_id'] for r in work_rows if r['implementation_status'] == status] for status in ['IMPLEMENTED', 'IN_PROGRESS', 'NOT_STARTED', 'BLOCKED']}, 'environment': {'WordPress': '7.1.3', 'WooCommerce': '11.2.1', 'PHP': '8.4.27 runtime;8.5.8 host lint/domain', 'MariaDB': '10.11.19-MariaDB-ubu2204', 'browser': 'Playwright1.64.1 with existing Chrome156.0.8078.48', 'storage': storage, 'sites': ['http://localhost:18880', 'http://localhost:18881', 'http://localhost:18882'], 'isolation': 'Loopback-only, separate synthetic databases/users/uploads; outbound mail and application HTTP fenced'}, 'business_approvals': 'DEC-01..12 and required human signoffs pending; no live activation', 'schema': {'plugin_version': 1, 'tables': 15, 'policy': 'Additive dbDelta, all merged tables checked InnoDB; comprehensive resumable upgrades remain unfinished'}, 'source_roots': ['packages/theme/wooshop', 'packages/plugin/wooshop-commerce'], 'latest_recorded_checks': runs[-10:], 'missing_prerequisites': ['approved providers/licenses/accounts', 'business/legal/finance/domain/retention decisions', 'specialist human review', 'complete remote CI/advisory and environment compatibility verification'], 'remaining_engineering': ['Full26module lifecycle/dependency/drain behavior; three optional modules active slice', 'Complete196settings UI/inheritance/import/export; plugin configuration revision restore verified separately', 'Complete advanced commerce and marketplace checkout/refund/reconciliation flows', 'Full SEO/privacy/security/a11y/RTL/performance/recovery/migration/scale acceptance', 'Production update signing, deployment/recovery/runbooks and operational handover'], 'next_task': 'Finish optional module lifecycle and concurrency regressions, then frozen-source combined regression, reproducible rebuild and clean-package acceptance', 'next_inputs': ['docs/07-plugin/plugin-kernel-and-module-lifecycle.md', 'docs/07-plugin/activation-migration-deactivation-uninstall.md', 'docs/implementation/RESUME.md'], 'safety': 'Preserve databases and existing order/journal evidence; tests only guarded local fixtures; no real funds/messages/deployment; do not print ignored credentials'}
recovery_path = OUT / 'recovery-evidence.json'
recovery = json.loads(recovery_path.read_text()) if recovery_path.exists() else {}
state['bounded_recovery'] = {'status': recovery.get('status', 'NOT_RUN'), 'evidence': 'docs/implementation/recovery-evidence.json', 'scope': 'Only synthetic wooshop_package_check; explicit user approval supplied after initial auto-review denial', 'failed_stage': recovery.get('failed_stage'), 'maintenance': recovery.get('maintenance_restored_to_prior_state', recovery.get('failure_containment', 'See evidence'))}
if recovery.get('status') == 'PASS':
    state['stage'] = 'WP-G011 remaining foundation engineering; verified local artifact/independence/recovery checkpoint'
    state['next_task'] = 'Implement typed dependency/capability negotiation for the remaining module lifecycle, beginning with MOD-U002 bounded native search and dependency tests; preserve catalog evidence, verified003/004/005 cleanup rights and critical obligations. Then implement safe plugin configuration JSON import preview/remapping.'
elif recovery.get('status') in ['FAILED', 'FAIL']:
    state['stage'] = 'WP-G027 guarded third-site recovery verification; keep third site in maintenance'
    state['next_task'] = 'Read recovery-evidence.json and private recovery state before any third-site action. Complete reviewed original-backup restoration with hardcoded USE scope and all invariants; never redump a marker-contaminated baseline or reopen before validation.'
(OUT / 'IMPLEMENTATION_STATE.json').write_text(json.dumps(state, indent=2) + '\n')
print(json.dumps({'checkpoint': 'docs/implementation/IMPLEMENTATION_STATE.json', 'requirements': len(requirement_rows), 'status': state['delivery_conclusion']}))
