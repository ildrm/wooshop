#!/usr/bin/env python3
"""Refresh derived implementation views; never changes authoritative specifications."""
from pathlib import Path
from datetime import datetime, timezone
import csv
import hashlib
import json

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'docs/implementation'

def read_csv(path):
    with (ROOT / path).open(newline='') as handle:
        return list(csv.DictReader(handle))

def write_csv(name, rows):
    if not rows:
        raise SystemExit('Refusing empty derived registry: ' + name)
    with (OUT / name).open('w', newline='') as handle:
        writer = csv.DictWriter(handle, fieldnames=list(rows[0]))
        writer.writeheader()
        writer.writerows(rows)

reports = []
for path in sorted(OUT.glob('evidence-*.json')):
    reports.append((str(path.relative_to(ROOT)), json.loads(path.read_text())))
for name in ['assurance-followup.json', 'independence-evidence.json', 'recovery-evidence.json', 'credential-isolation-evidence.json']:
    path = OUT / name
    if path.exists(): reports.append((str(path.relative_to(ROOT)), json.loads(path.read_text())))
ledger = read_csv('docs/implementation/documentation-read-ledger.csv')
for row in ledger:
    readers = []
    full = False
    for path, report in reports:
        for item in report.get('read_paths', []):
            if isinstance(item, str):
                item = {'path': item, 'reading_status': 'selected portions'}
            if item.get('path') != row['path']:
                continue
            if item.get('sha256') and item['sha256'] != row['sha256']:
                readers.append(report.get('worker', report.get('task', path)) + ':fingerprint mismatch')
                continue
            scope = (str(item.get('reading_status', item.get('scope', item.get('status', 'selected portions'))))).lower()
            full = full or scope in ['full', 'full document', 'read']
            readers.append(report.get('worker', report.get('task', path)) + ':' + scope)
    if readers:
        row['reading_status'] = 'READ_FULL' if full else 'READ_SELECTED_PORTIONS'
        row['reader'] = ';'.join(readers)
        row['gaps'] = 'See source evidence for exact read scope; normative draft status is not human approval.'
write_csv('documentation-read-ledger.csv', ledger)

requirements = read_csv('docs/02-requirements/requirements.csv') + read_csv('docs/02-requirements/nonfunctional-requirements.csv')
backlog = {r['requirement_id']: r for r in read_csv('docs/14-delivery/implementation-backlog.csv')}
packages = {r['id']: r for r in read_csv('docs/14-delivery/work-breakdown-structure.csv')}
raci = {r['item_id']: r for r in read_csv('docs/00-governance/raci.csv')}
theme = json.loads((ROOT / 'packages/theme/wooshop/coverage.json').read_text())
worker_rows = {}
for path, report in reports:
    items = report.get('requirements', [])
    if isinstance(items, dict): items = [{'id': key, 'status': value} for key, value in items.items()]
    for requirement in items:
        identifier = requirement.get('id', requirement.get('requirement_id'))
        worker_rows[identifier] = (path, requirement)

root_scope = {
    'FR-D001': ('compose.yaml;tests/core/package-smoke.php;tools/credentials-check.py', 'Three local installations have distinct database/salt/entity/option values and native administrator passwords; full indirect isolation matrix remains incomplete. See assurance-followup.json and credential-isolation-evidence.json.'),
    'FR-D002': ('packages/theme/wooshop;tools/independence.py', 'Installed third theme native WC browse/product/cart/account passed with companion inactive; complete abrupt marketplace dependency-loss/obligation acceptance remains incomplete. See independence-evidence.json.'),
    'FR-D003': ('packages/plugin/wooshop-commerce;tools/independence.py', 'Installed plugin persistence/private wishlist native form passed under Twenty Twenty-Four1.6; complete all-module independence acceptance remains incomplete. See independence-evidence.json.'),
    'FR-G002': ('tools/dev.py;compose.yaml', 'Separate databases and users; full indirect isolation acceptance is incomplete.'),
    'FR-G005': ('packages/plugin/wooshop-commerce/src/Catalog/Products.php', 'Native type descriptors and explicit unsupported provider gates; full extension matrix pending.'),
    'FR-G007': ('packages/plugin/wooshop-commerce;packages/theme/wooshop', 'Native WC CRUD and theme/plugin separation; full independence and ownership acceptance pending.'),
    'FR-G010': ('packages/plugin/wooshop-commerce/src/Settings/Validator.php', 'Unapproved marketplace activation rejected; all business decisions remain pending.'),
    'FR-G011': ('packages/plugin/wooshop-commerce/src/Settings;packages/theme/wooshop', 'Bounded typed preview/save and plugin revision restore; full visual controls/native per-property reset incomplete.'),
    'FR-G012': ('profiles;tools/seed.php', 'Shared synthetic profiles; reviewed real catalog evidence and full onboarding incomplete.'),
    'FR-G015': ('tools/traceability.py;docs/implementation', 'Derived registry preserves IDs; complete behavioral trace coverage pending.'),
    'FR-G016': ('docs/implementation/dependency-and-provider-verification.csv', 'Actual local dependencies recorded; legal/procurement decisions unresolved.'),
    'FR-G019': ('docs/implementation/release-readiness.md', 'No release or production approval; all required signoffs pending.'),
    'FR-G020': ('profiles/neutral.json;tools/dev.py', 'Generic neutral profile and third package installation; full portability acceptance pending.'),
    'FR-U026': ('packages/plugin/wooshop-commerce/src/Kernel/ModuleRegistry.php;tests/catalog/lifecycle.php;tools/module-concurrency.py', 'Three optional modules verified100checks perstore plus real2-process transaction/disable barrier; remaining23modules and full dependency/drain paths incomplete.'),
    'FR-U027': ('packages/plugin/wooshop-commerce/src/Settings;packages/plugin/wooshop-commerce/src/Kernel/Plugin.php', 'Typed settings/audit/conflicts, immutable revisions and previewed restore verified by24 real checks perstore and actual adminbrowser; full scoped controls/report/export/import acceptance incomplete.'),
    'FR-U028': ('packages/plugin/wooshop-commerce/src/Catalog;packages/plugin/wooshop-commerce/src/Marketplace', 'Versioned native capability contracts; selected provider adapters unavailable.'),
    'FR-U029': ('packages/plugin/wooshop-commerce/src/Jobs/Queue.php', 'Real leases/dedup/retry/current authorization; full event outbox/notification/reconciliation incomplete.'),
    'FR-U030': ('packages/plugin/wooshop-commerce/wooshop-commerce.php;packages/plugin/wooshop-commerce/src/Infrastructure/Schema.php', 'Idempotent additive schema and retention; resumable migration/preflight/obligation transfer incomplete.'),
    'FR-U031': ('profiles/outdoor.json;tools/seed.php', 'Synthetic schema/profile baseline; full composite values and real domain evidence incomplete.'),
    'FR-U032': ('profiles/smart-home.json;tools/seed.php', 'Synthetic schema/profile baseline; full composite values and real domain evidence incomplete.'),
    'FR-U033': ('profiles;tools/seed.php;compose.yaml', 'Two independent fixture stores; safe general configuration import/remapping incomplete.'),
    'FR-U035': ('packages/theme/wooshop/coverage.json', 'Per-control partial/native/unavailable status recorded for all196controls; exact range/inheritance acceptance incomplete.'),
    'FR-U036': ('packages/theme/wooshop;packages/plugin/wooshop-commerce/src/Settings/Validator.php', 'Native global editor capability and bounded settings; complete seller branding moderation incomplete.'),
    'FR-U062': ('packages/plugin/wooshop-commerce/src/Jobs;packages/plugin/wooshop-commerce/src/Kernel', 'Persistent queue and safe diagnostics; operator replay/bundle UI incomplete.'),
    'SEC-S005': ('packages/plugin/wooshop-commerce/src/Security;packages/plugin/wooshop-commerce/src/Customer/Session.php', 'Native cookie REST nonce and signed guest session/scoped APIs; full adversarial matrix incomplete.'),
    'SEC-S006': ('packages/plugin/wooshop-commerce/src/Support;packages/plugin/wooshop-commerce/src/Settings', 'Prepared bounded storage/schema allowlists; broad input/egress/security verification incomplete.'),
    'SEC-S008': ('packages/plugin/wooshop-commerce/src/Settings;packages/theme/wooshop', 'No raw executable visual settings; full scoped asset/import/editor acceptance incomplete.'),
    'SEC-S015': ('tools/security.py;tools/bootstrap.py;tools/package.py;.github/workflows/verify.yml', 'Pinned inputs/packages/hygiene checks; advisory audit failed with network reset; full supply-chain/update signing pending.'),
    'NFR-S001': ('packages/theme/wooshop;tests/browser', 'Keyboard/mobile/landmark subset; complete WCAG and assistive technology review NOT RUN.'),
    'NFR-S004': ('tools/recovery.py;tests/core/recovery.php', 'Bounded third-database backup/restore drill tracked in recovery-evidence.json; production RPO/RTO, encrypted offsite/file backups, erasure-gap replay and full reconciliation remain incomplete.'),
    'FR-S004': ('tools/recovery.py;tests/core/recovery.php;docs/implementation/recovery-evidence.json', 'Guarded synthetic restore tooling and measured evidence; complete operational runbooks/escalations and production drills remain incomplete.'),
    'NFR-S005': ('packages/plugin/wooshop-commerce/src/Inventory;packages/plugin/wooshop-commerce/src/Finance', 'Actual20worker inventory race and balanced journal tests; complete settlement/crash/provider acceptance incomplete.'),
    'NFR-S006': ('packages/theme/wooshop/assets;packages/theme/wooshop/languages', 'Logical RTL styles and authored Persian strings; fluent/native locale and complete critical RTL task verification incomplete.'),
    'NFR-S007': ('docs/implementation/dependency-and-provider-verification.csv', 'Exact executed local matrix bounded; broad browser/provider/version matrix incomplete.'),
    'NFR-S010': ('tools/verify.py;tools/traceability.py;tests', 'Actual finite tests and unmet full acceptance retained; all MUST coverage remains incomplete.'),
    'NFR-S011': ('packages/theme/wooshop;packages/plugin/wooshop-commerce;tools/independence.py', 'Actual three-case installed independence matrix passed with restored state; complete forced-loss/provider/owed-obligation acceptance remains incomplete.'),
    'NFR-S012': ('tests/core/package-smoke.php;packages/plugin/wooshop-commerce/src/Infrastructure/Schema.php', 'Migration preservation test prepared; full upgrade/restore reconciliation not established.'),
}
matrix = []
for requirement in requirements:
    identifier = requirement['id']; work_id = backlog.get(identifier, {}).get('work_package_id', '')
    implementation = 'NOT_STARTED'; verification = 'NOT_RUN'; paths = ''; tests = ''; observations = 'No implementation evidence assigned; not omitted from scope.'; evidence = ''; blockers = ''
    if identifier in theme['requirements']:
        implementation = theme['requirements'][identifier]['implementation']; paths = 'packages/theme/wooshop'; tests = 'tests/theme;tests/browser/storefront.spec.js'; evidence = 'docs/implementation/evidence-implement_theme.json'; observations = 'Theme coverage.json records exact partial controls/screens/components. Full acceptance remains unverified.'
    if identifier in worker_rows:
        evidence, item = worker_rows[identifier]; status = item.get('status', 'PARTIAL')
        implementation = 'IN_PROGRESS' if status not in ['NOT_STARTED', 'BLOCKED'] else status
        paths = 'packages/plugin/wooshop-commerce/src/' + ('Marketplace;packages/plugin/wooshop-commerce/src/Inventory;packages/plugin/wooshop-commerce/src/Finance' if 'marketplace' in evidence else 'Catalog;packages/plugin/wooshop-commerce/src/Customer;packages/plugin/wooshop-commerce/src/Merchandising;packages/plugin/wooshop-commerce/src/Seo')
        tests = 'tests/marketplace' if 'marketplace' in evidence else 'tests/catalog'
        observations = item.get('evidence_scope', json.dumps({k: item.get(k, []) for k in ['implemented_features', 'unfinished', 'blocked']}, ensure_ascii=False))
        blockers = ';'.join(item.get('blocked', []))
        if status == 'BLOCKED_OR_NOT_IMPLEMENTED':
            implementation = 'NOT_STARTED'; observations += ' Unfinished engineering remains NOT_STARTED; provider prerequisites do not blanket-block independent work.'
    if identifier in root_scope:
        implementation = 'IN_PROGRESS'; paths, observations = root_scope[identifier]; evidence = ';'.join(filter(None, [evidence, 'docs/implementation/evidence-root.json']))
    if identifier in ['FR-D024', 'FR-D028']:
        implementation = 'IMPLEMENTED'; verification = 'PASS'; observations += ' Acceptance scope: internal exact money or atomic reservation domain on executed local database. No provider payment/settlement support implied.'
    if identifier == 'FR-G001':
        implementation = 'NOT_APPLICABLE'; observations = 'Documentation-only assignment restriction explicitly superseded by current user implementation request/master prompt. Original normative row retained unchanged.'; blockers = 'DEV-AUTH-001'
    matrix.append({'requirement_id': identifier, 'requirement_registry': 'docs/02-requirements/' + ('nonfunctional-requirements.csv' if identifier.startswith('NFR-') else 'requirements.csv'), 'specification': 'docs/' + requirement['specification_path'], 'acceptance_test_id': requirement['test_id'], 'owner_role': requirement['owner'], 'work_package_id': work_id, 'accountable_role': packages.get(work_id, {}).get('accountable', requirement['owner']), 'scope': requirement['applicability'], 'dependencies': requirement['dependencies'], 'implementation_paths': paths, 'executable_checks': tests, 'implementation_status': implementation, 'verification_status': verification, 'evidence': evidence, 'observed_scope_and_remaining_acceptance': observations, 'environment_provider_scope': 'Local synthetic WP7.1.3/WC11.2.1/PHP8.4.27/MariaDB10.11.19; external providers NOT VERIFIED', 'external_approval': 'Not supplied; affected business activation remains closed', 'blocker_or_deviation_ids': blockers, 'release_gate': raci.get(work_id, {}).get('phase_gate', 'Full acceptance and required specialist approval pending')})
write_csv('requirements-implementation-matrix.csv', matrix)

work_rows = []
for identifier, package in packages.items():
    status = 'IN_PROGRESS'; note = 'Named local slices exist; complete package exit gate has not passed.'
    if identifier == 'WP-G002': status = 'BLOCKED'; note = 'Human jurisdiction/funds/currency/retention decisions not supplied.'
    if identifier in ['WP-G001', 'WP-G006', 'WP-G016', 'WP-G021', 'WP-G027', 'WP-G028', 'WP-G029', 'WP-G030', 'WP-G032']:
        status = 'NOT_STARTED'; note = 'Package acceptance not executed. Specifications remain in scope; human/provider prerequisites recorded separately.'
    if identifier == 'WP-G027' and (ROOT / 'tools/recovery.py').exists():
        status = 'IN_PROGRESS'; note = 'Guarded bounded third-site backup/restore implemented; execution scope in recovery-evidence.json. Full performance/resilience/production recovery gate incomplete.'
    work_rows.append({'work_package_id': identifier, 'objective': package['objective'], 'accountable': package['accountable'], 'implementation_roles': package['responsible'], 'review_perspectives': raci.get(identifier, {}).get('consulted', 'QA/Test Engineer;Solution/Software Architect'), 'prerequisites': package['prerequisites'], 'implementation_status': status, 'verification_status': 'BLOCKED' if status == 'BLOCKED' else 'NOT_RUN', 'exit_gate': package['acceptance_exit_gate'], 'observed_status': note, 'evidence': 'docs/implementation/requirements-implementation-matrix.csv', 'human_signoff': 'NOT_PROVIDED'})
write_csv('work-package-status.csv', work_rows)

runs = []
for path, report in reports:
    for index, command in enumerate(report.get('commands', []), 1):
        if isinstance(command, str): command = {'command': command}
        reported = str(command.get('result', 'NOT_RUN'))
        result = 'PASS' if reported.startswith('PASS') else ('FAIL' if reported.startswith('FAIL') else ('BLOCKED' if reported.startswith('BLOCKED') else 'NOT_RUN'))
        runs.append({'run_id': Path(path).stem + '-' + str(index), 'recorded_at': report.get('updated_at', 'not recorded'), 'executed_at': command.get('executed_at', 'Not separately timestamped; occurred before source report update'), 'command': command.get('command', command.get('tool', 'See evidence')), 'cwd': str(ROOT), 'environment': str(command.get('environment', 'See named local environment in evidence')), 'exit_status': command.get('exit_status', 'not separately retained'), 'result': result, 'reported_result': reported, 'source_fingerprint': 'See evidence source-file fingerprints; report may predate followup changes', 'evidence': path, 'limitations': str(command.get('observed', command.get('scope', 'Provider/live/human approval not implied')))})
    if Path(path).name in ['assurance-followup.json', 'independence-evidence.json', 'recovery-evidence.json', 'credential-isolation-evidence.json']:
        result = report.get('result', report.get('status', 'NOT_RUN'))
        if str(result).startswith('BLOCKED'): result = 'BLOCKED'
        if result not in ['PASS', 'FAIL', 'FAILED', 'NOT_RUN', 'BLOCKED']:
            result = 'NOT_RUN'  # Detailed scoped sub-results remain authoritative; do not infer a summary pass.
        runs.append({'run_id': Path(path).stem + '-summary', 'recorded_at': report.get('finished_at', report.get('updated_at', report.get('executed_at', 'See evidence'))), 'executed_at': report.get('started_at', report.get('executed_at', 'See scoped command timestamps in evidence')), 'command': report.get('command', 'See exact scoped commands in evidence'), 'cwd': str(ROOT), 'environment': 'Isolated synthetic local matrix; installed runtime and provider limits in evidence', 'exit_status': report.get('exit_status', 'See evidence'), 'result': 'FAIL' if result == 'FAILED' else result, 'reported_result': str(report.get('status', report.get('result', 'See scoped results'))), 'source_fingerprint': 'See script/runtime/manifest hashes in evidence', 'evidence': path, 'limitations': 'Passing scoped sub-results do not complete the whole requirement or a human release gate.'})
for path in sorted((ROOT / 'artifacts/verification').glob('*.json')):
    report = json.loads(path.read_text())
    runs.append({'run_id': path.stem, 'recorded_at': report['finished_at'], 'executed_at': report['started_at'], 'command': ' '.join(report['command']), 'cwd': report['cwd'], 'environment': report['environment_scope'], 'exit_status': report['exit_status'], 'result': report['result'], 'reported_result': report['result'], 'source_fingerprint': report['source_fingerprint'], 'evidence': str(path.relative_to(ROOT)), 'limitations': 'Finite named suite only; full requirement verification remains distinct.'})
write_csv('verification-runs.csv', runs)
print(json.dumps({'requirements': len(matrix), 'work_packages': len(work_rows), 'read_ledger_entries': len(ledger), 'verification_records': len(runs), 'result': 'derived views refreshed; implementation incomplete'}))
