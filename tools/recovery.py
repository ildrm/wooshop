#!/usr/bin/env python3
"""Bounded, coordinated local recovery drill. Only package-check / wooshop_package_check."""
import argparse
import datetime
import fcntl
import hashlib
import json
import os
from pathlib import Path
import re
import secrets
import shutil
import stat
import subprocess
import sys
import time
import tempfile

ROOT = Path(__file__).resolve().parents[1]
LOCAL = ROOT / '.local'
BACKUPS = LOCAL / 'backups'
EVIDENCE = ROOT / 'docs/implementation/recovery-evidence.json'
SITE = 'package-check'
DATABASE = 'wooshop_package_check'
USER = 'wooshop_package'
URL = 'http://localhost:18882'
MAX_BYTES = 134217728
PREFIX = 'WOOSHOP_RECOVERY '
COMPOSE = ['docker', 'compose', '--profile', 'verification', '--env-file', str(LOCAL / 'compose.env'), '-f', str(ROOT / 'compose.yaml')]
COMMANDS = []
DOCS = ['docs/13-operations/backups-recovery-and-runbooks.md', 'docs/12-quality/performance-concurrency-and-recovery-tests.md', 'docs/02-requirements/nonfunctional-requirements.csv', 'docs/02-requirements/requirements.csv', 'docs/14-delivery/work-breakdown-structure.csv', 'docs/12-quality/test-cases.csv', 'compose.yaml', '.gitignore', 'tools/dev.py', 'tools/dev-guard.php', 'tests/core/native-checkout-fixture.php', 'packages/plugin/wooshop-commerce/src/Infrastructure/Schema.php', 'packages/plugin/wooshop-commerce/src/Infrastructure/Database.php', 'packages/plugin/wooshop-commerce/src/Marketplace/Schema.php', 'packages/plugin/wooshop-commerce/src/Settings/SettingsService.php']


class RecoveryError(Exception):
    """Only fixed, redacted diagnostic messages may leave the command boundary."""


def sha256(path):
    digest = hashlib.sha256()
    with open(path, 'rb') as source:
        for chunk in iter(lambda: source.read(65536), b''):
            digest.update(chunk)
    return digest.hexdigest()


def json_write(path, value, private=False):
    temporary = path.with_name(path.name + '.tmp')
    if path.is_symlink() or temporary.is_symlink():
        raise RecoveryError('Refused symlink for recovery evidence.')
    descriptor = os.open(temporary, os.O_WRONLY | os.O_CREAT | os.O_TRUNC, 0o600 if private else 0o644)
    os.fchmod(descriptor, 0o600 if private else 0o644)
    with os.fdopen(descriptor, 'w') as target:
        json.dump(value, target, indent=2, ensure_ascii=False)
        target.write('\n')
        target.flush()
        os.fsync(target.fileno())
    os.replace(temporary, path)


def base_evidence():
    return {'schema_version': 1, 'scope': {'site': SITE, 'url': URL, 'database': DATABASE, 'db_user': USER, 'source_stores_allowed': False},
            'status': 'NOT_RUN', 'requirements': {'WP-G027': 'PARTIAL', 'NFR-S004': 'PARTIAL', 'FR-S004': 'PARTIAL'},
            'accountable_role': 'DevOps/SRE Engineer', 'human_signoffs_claimed': False,
            'codebase_memory': {'tier': 'Verify', 'project': 'Users-shahinilderemi-Documents-tmp-iranbroker-wp', 'generation': '2026-10-28T10:38:10Z', 'positive_lookup': 'tools.dev compose/wp/package_site; three results, no pagination remainder', 'coverage': 'Six task evidence paths no recorded issue/metadata_match; tools scope has excluded __pycache__, tests/core no recorded issue. Direct exact source read used; no exhaustive coverage claim.'},
            'installed_source_verification': [{'path': '.local/cache/wp-cli.phar', 'sha256': sha256(LOCAL / 'cache/wp-cli.phar'), 'read_scope': 'vendor/wp-cli/maintenance-mode-command/src/MaintenanceModeCommand.php; numeric literal timestamp detection verified'}, {'path': '.local/sites/package-check/wp-includes/load.php', 'sha256': sha256(LOCAL / 'sites/package-check/wp-includes/load.php'), 'read_scope': 'wp_maintenance/wp_is_maintenance_mode; 10-minute timestamp expiry verified'}],
            'read_paths': [{'path': path, 'sha256': sha256(ROOT / path)} for path in DOCS],
            'changed_files': ['tools/recovery.py', 'tests/core/recovery.php', 'docs/implementation/recovery-evidence.json'],
            'file_fingerprints': {path: sha256(ROOT / path) for path in ['tools/recovery.py', 'tests/core/recovery.php']},
            'limits': ['Only a coordinated idle, synthetic third database is supported; source stores are refused.',
                       'No production RPO/RTO, encrypted/offsite backups, full disaster recovery or finance/privacy signoff is claimed.',
                       'No real payment, external provider reconciliation or post-snapshot customer transaction is generated.',
                       'Database only; files/media, secrets rotation, remote storage, erasure replay after a recovery-point gap and full incident drills remain unverified.',
                       '128 MiB dataset/dump, 200 InnoDB base tables, 200000 rows and 500 native orders bound this drill.'],
            'commands': []}


def safe_paths():
    for path in [LOCAL, LOCAL / 'sites', LOCAL / 'sites/package-check', ROOT / 'compose.yaml', LOCAL / 'compose.env']:
        if path.is_symlink() or not path.resolve().is_relative_to(ROOT):
            raise RecoveryError('Refused development path symlink or workspace escape.')
        if not path.exists():
            raise RecoveryError('Required existing local package-check environment is absent; this tool never creates databases.')
    if '.local/' not in (ROOT / '.gitignore').read_text().splitlines():
        raise RecoveryError('Ignored local backup directory is not configured.')
    if stat.S_IMODE((LOCAL / 'compose.env').stat().st_mode) & 0o077:
        raise RecoveryError('Local compose credentials need owner-only permissions.')
    if BACKUPS.is_symlink() or not BACKUPS.resolve().is_relative_to(LOCAL.resolve()):
        raise RecoveryError('Refused backup directory escape.')
    BACKUPS.mkdir(mode=0o700, exist_ok=True)
    BACKUPS.chmod(0o700)


def command(arguments, *, env=None, stdin=None, stdout=None, timeout=45, allowed=(0,)):
    started = time.monotonic()
    receipt = {'argv': ['docker', 'compose', '--profile', 'verification', '--env-file', '.local/compose.env', '-f', 'compose.yaml', *arguments]}
    COMMANDS.append(receipt)
    try:
        result = subprocess.run(COMPOSE + arguments, cwd=ROOT, env=env, stdin=stdin,
                                stdout=stdout if stdout is not None else subprocess.PIPE, stderr=subprocess.PIPE, timeout=timeout)
    except subprocess.TimeoutExpired:
        receipt.update(result='TIMEOUT', elapsed_seconds=round(time.monotonic() - started, 6))
        raise RecoveryError('Bounded local container command timed out; private output omitted.') from None
    receipt.update(result=result.returncode, elapsed_seconds=round(time.monotonic() - started, 6))
    if result.returncode not in allowed:
        raise RecoveryError('Local container command failed with exit ' + str(result.returncode) + '; private output and arguments omitted.')
    return result


def wp(arguments, allowed=(0,)):
    return command(['exec', '-T', SITE, 'php', '/workspace/.local/cache/wp-cli.phar', '--allow-root', '--path=/var/www/html', *arguments], allowed=allowed)


def observe(mode, run):
    result = wp(['eval-file', '/workspace/tests/core/recovery.php', mode, run])
    lines = result.stdout.decode('utf-8', 'strict').splitlines()
    matches = [line[len(PREFIX):] for line in lines if line.startswith(PREFIX)]
    if len(matches) != 1:
        raise RecoveryError('Guarded WordPress observation was missing or ambiguous; private output omitted.')
    try:
        return json.loads(matches[0])
    except (ValueError, TypeError):
        raise RecoveryError('Guarded WordPress observation was not valid JSON.') from None


def observe_raw(run):
    result = command(['exec', '-T', SITE, 'php', '/workspace/tests/core/recovery.php', 'raw', run])
    prefix = 'WOOSHOP_RECOVERY_RAW '
    matches = [line[len(prefix):] for line in result.stdout.decode().splitlines() if line.startswith(prefix)]
    if len(matches) != 1:
        raise RecoveryError('Raw pre-bootstrap restore observation was missing or ambiguous.')
    return json.loads(matches[0])


def database_environment():
    if os.environ.get('DOCKER_HOST') and not os.environ['DOCKER_HOST'].startswith('unix://'):
        raise RecoveryError('Only a local Unix-socket Docker engine is permitted.')
    context = subprocess.run(['docker', 'context', 'inspect'], cwd=ROOT, stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=10)
    if context.returncode or not json.loads(context.stdout)[0].get('Endpoints', {}).get('docker', {}).get('Host', '').startswith('unix://'):
        raise RecoveryError('Docker context must resolve to a local Unix-socket engine.')
    config = json.loads(command(['config', '--format', 'json']).stdout)
    services = config.get('services', {})
    site = services.get(SITE, {})
    environment = site.get('environment', {})
    if config.get('name') != 'wooshop-development' or environment.get('WORDPRESS_DB_NAME') != DATABASE or environment.get('WORDPRESS_DB_USER') != USER or environment.get('WORDPRESS_DB_HOST') != 'db':
        raise RecoveryError('Compose project/database/user scope mismatch.')
    ports = site.get('ports', [])
    if len(ports) != 1 or str(ports[0].get('published')) != '18882' or ports[0].get('host_ip') != '127.0.0.1' or int(ports[0].get('target', 0)) != 80:
        raise RecoveryError('Package-check must expose only loopback port18882.')
    expected_site = str((LOCAL / 'sites/package-check').resolve())
    mounts = site.get('volumes', [])
    if not any(x.get('target') == '/var/www/html' and x.get('source') == expected_site for x in mounts):
        raise RecoveryError('Third-site volume is not the expected isolated local directory.')
    if any('/workspace/packages' == x.get('target') or '/var/www/html/wp-content/plugins/wooshop-commerce' == x.get('target') for x in mounts):
        raise RecoveryError('Recovery target must be the installed-package site, without commerce source mounts.')
    image = services.get('db', {}).get('image', '')
    if not re.fullmatch(r'mariadb:10\.11@sha256:[a-f0-9]{64}', image):
        raise RecoveryError('Pinned official MariaDB service required.')
    password = environment.get('WORDPRESS_DB_PASSWORD')
    if not isinstance(password, str) or len(password) < 24:
        raise RecoveryError('Existing restricted third-database credential is unavailable.')
    private_environment = os.environ.copy()
    private_environment['MYSQL_PWD'] = password
    return private_environment, image


def db(arguments, environment, *, stdin=None, stdout=None, timeout=45):
    # The argv contains an environment VARIABLE NAME, never the password value.
    return command(['exec', '-T', '--env', 'MYSQL_PWD', 'db', *arguments], env=environment, stdin=stdin, stdout=stdout, timeout=timeout)


def maintenance_active():
    return wp(['maintenance-mode', 'is-active'], allowed=(0, 1)).returncode == 0


def private_bytes(path, value):
    descriptor = os.open(path, os.O_WRONLY | os.O_CREAT | os.O_EXCL, 0o600)
    with os.fdopen(descriptor, 'wb') as target:
        target.write(value)
        target.flush()
        os.fsync(target.fileno())


def maintenance_snapshot(backup):
    path = LOCAL / 'sites/package-check/.maintenance'
    if path.is_symlink():
        raise RecoveryError('Refused preexisting maintenance symlink.')
    if not path.exists():
        return None, None, None, {'existed': False}
    if path.stat().st_size > 8192:
        raise RecoveryError('Preexisting maintenance file exceeds supported native scope.')
    contents = path.read_bytes()
    match = re.search(rb'upgrading\s*=\s*(\d+)\s*;', contents, re.I)
    if not match:
        raise RecoveryError('A nonstandard preexisting maintenance file needs separate operator review.')
    original_mode = stat.S_IMODE(path.stat().st_mode)
    retained = backup.with_suffix('.maintenance.before')
    private_bytes(retained, contents)
    return contents, original_mode, int(match.group(1)), {'existed': True, 'sha256': hashlib.sha256(contents).hexdigest(), 'mode': oct(original_mode), 'private_copy': str(retained.relative_to(ROOT))}


def restore_maintenance(contents, original_mode, original_timestamp, prior_active, run):
    path = LOCAL / 'sites/package-check/.maintenance'
    if contents is None:
        wp(['maintenance-mode', 'deactivate'])
        if path.exists():
            raise RecoveryError('Native maintenance file remained after validated deactivation.')
        return
    # Preserve both the previous bytes and their original expiry policy. If its
    # active hold would expire during restoration, retain our closed hold instead.
    if prior_active and time.time() - original_timestamp >= 590:
        raise RecoveryError('Original active maintenance hold expired or is too near expiry; reviewed reopening is required.')
    if path.is_symlink():
        raise RecoveryError('Refused maintenance path change during restoration.')
    temporary = path.with_name('.maintenance.restore-' + run)
    descriptor = os.open(temporary, os.O_WRONLY | os.O_CREAT | os.O_EXCL, original_mode)
    os.fchmod(descriptor, original_mode)
    with os.fdopen(descriptor, 'wb') as target:
        target.write(contents)
        target.flush()
        os.fsync(target.fileno())
    os.replace(temporary, path)
    if path.read_bytes() != contents or stat.S_IMODE(path.stat().st_mode) != original_mode:
        raise RecoveryError('Original native maintenance bytes/mode did not restore exactly.')


def persistent_native_hold():
    # Both core and WP-CLI recognize this native numeric timestamp. The extended
    # timestamp prevents the ordinary ten-minute expiry during failed recovery.
    # Normal success restores the prior logical state through native WP-CLI.
    code = '''if (getenv("WORDPRESS_DB_NAME")!=="wooshop_package_check" || getenv("WORDPRESS_DB_USER")!=="wooshop_package" || PHP_INT_SIZE!==8) {exit(2);} $p="/var/www/html/.maintenance"; $temporary=$p.".wooshop-recovery"; if(is_link($p)||is_link($temporary)){exit(3);} $contents='<?php $upgrading = ' . (PHP_INT_MAX - 600) . ';' . PHP_EOL; if(file_put_contents($temporary,$contents,LOCK_EX)===false || !rename($temporary,$p)){exit(4);}'''
    command(['exec', '-T', SITE, 'php', '-r', code])


def fail_closed(report):
    try:
        persistent_native_hold()
        report['failure_containment'] = 'Extended numeric native WordPress maintenance hold retained on package-check only; explicit reviewed recovery/reopening is required.'
    except Exception:
        try:
            command(['stop', SITE], timeout=30)
            report['failure_containment'] = 'Package-check container stopped because native maintenance hold could not be confirmed.'
        except Exception:
            report['failure_containment'] = 'UNCONFIRMED: root must stop only package-check and retain the backup before investigation.'


def verify_snapshot(before, after):
    if not isinstance(before, dict) or not isinstance(after, dict) or not isinstance(before.get('tables'), dict) or not isinstance(after.get('tables'), dict):
        raise RecoveryError('Restored observation has an invalid table snapshot shape.')
    keys = ['tables', 'database_rows_and_schema_sha256', 'native_orders', 'ledger_and_extension_sha256', 'settings_sha256', 'settings_option_count', 'table_count', 'total_rows']
    mismatches = [key for key in keys if before.get(key) != after.get(key)]
    changed_tables = sorted(name for name in set(before.get('tables', {})) | set(after.get('tables', {})) if before.get('tables', {}).get(name) != after.get('tables', {}).get(name))
    if mismatches:
        raise RecoveryError('Restored invariant mismatch: ' + ','.join(mismatches) + '; tables=' + ','.join(changed_tables))
    return {key: True for key in ['all_table_rows_and_schema_match', 'native_order_ids_totals_and_data_match', 'native_item_snapshots_match', 'ledger_and_extension_match', 'settings_match']}


def restore_verified_stream(backup, checksum, environment):
    started = time.monotonic()
    if backup.is_symlink() or stat.S_IMODE(backup.stat().st_mode) != 0o600 or sha256(backup) != checksum:
        raise RecoveryError('Original private backup permission/checksum verification failed.')
    prefix = b'USE `wooshop_package_check`;\n'
    with open(backup, 'rb') as source, tempfile.TemporaryFile(dir=BACKUPS) as stream:
        info = os.fstat(stream.fileno())
        if stat.S_IMODE(info.st_mode) != 0o600 or info.st_nlink != 0:
            raise RecoveryError('Restore stream must be private and unlinked.')
        stream.write(prefix)
        shutil.copyfileobj(source, stream, 65536)
        stream.seek(0)
        db(['mariadb', '--user=' + USER, '--database=' + DATABASE, '--one-database', '--default-character-set=utf8mb4'], environment, stdin=stream, timeout=60)
    if sha256(backup) != checksum:
        raise RecoveryError('Original retained backup checksum changed after restore.')
    return {'hardcoded_use_database': DATABASE, 'source_sha256': checksum, 'source_bytes': backup.stat().st_size, 'prefix_bytes': len(prefix), 'temporary_mode': '0600', 'temporary_unlinked': True, 'elapsed_seconds': round(time.monotonic() - started, 6)}


def marker_only_difference(before, current):
    options = [name for name in before['tables'] if name.endswith('_options')]
    if len(options) != 1:
        raise RecoveryError('One native options table is required for owned-marker diagnosis.')
    option = options[0]
    if not re.fullmatch(r'[A-Za-z0-9_]+', option):
        raise RecoveryError('Invalid scoped native options identifier.')
    changes = [name for name in set(before['tables']) | set(current['tables']) if before['tables'].get(name) != current['tables'].get(name)]
    diagnostic = current.get('own_marker_diagnostic', {})
    checks = {'only_options_table_differs': changes == [option], 'native_orders_unchanged': before['native_orders'] == current['native_orders'],
              'ledger_and_extension_unchanged': before['ledger_and_extension_sha256'] == current['ledger_and_extension_sha256'],
              'own_marker_only_added_option': diagnostic.get('option_rows') == 1 and diagnostic.get('other_option_rows') == before['tables'][option]['rows'] and diagnostic.get('other_option_rows_sha256') == before['tables'][option]['rows_sha256'],
              'original_settings_unchanged': diagnostic.get('settings_without_marker_sha256') == before['settings_sha256'],
              'only_one_owned_row_added': current['total_rows'] == before['total_rows'] + 1}
    if not all(checks.values()):
        raise RecoveryError('Unexpected post-backup state change; reviewed restore refused.')
    return checks


def audit_original_options(environment, backup, checksum, baseline, current, run_id, allow_marker):
    """Official SQL parser; ONLY the proven cron-lock value may refresh after bootstrap."""
    data = backup.read_bytes()
    if hashlib.sha256(data).hexdigest() != checksum:
        raise RecoveryError('Original snapshot checksum changed before option audit.')
    options = [name for name in baseline['tables'] if name.endswith('_options')]
    if len(options) != 1:
        raise RecoveryError('Exactly one native options table is required.')
    option = options[0]
    name = option.encode()
    lines = data.split(b'\n'); inserts = []; create = None
    for index, line in enumerate(lines):
        if line == b'INSERT INTO `' + name + b'` VALUES':
            block = [line]
            for row in lines[index + 1:]:
                if not row.startswith(b'(') or not (row.endswith(b'),') or row.endswith(b');')):
                    raise RecoveryError('Unrecognized native dump INSERT layout; audit refused.')
                block.append(row)
                if row.endswith(b');'):
                    break
            inserts.append(b'\n'.join(block) + b'\n')
        if line == b'CREATE TABLE `' + name + b'` (':
            block = [line]
            for row in lines[index + 1:]:
                block.append(row)
                if row.startswith(b') ENGINE=') and row.endswith(b';'):
                    break
            create = b'\n'.join(block)[:-1].decode()
    if not inserts or create is None:
        raise RecoveryError('Recognized original options table SQL is required.')
    original_schema = {'Create Table': create, 'Table': option}
    schema_hash = hashlib.sha256(json.dumps(original_schema, sort_keys=True, ensure_ascii=True, separators=(',', ':')).encode()).hexdigest()
    if schema_hash != baseline['tables'][option]['schema_sha256']:
        raise RecoveryError('Original SQL schema did not reproduce baseline schema hash.')
    actual_schema = current.get('own_marker_diagnostic', {}).get('options_schema')
    if not isinstance(actual_schema, str) or re.sub(r' AUTO_INCREMENT=\d+', '', actual_schema) != re.sub(r' AUTO_INCREMENT=\d+', '', create):
        raise RecoveryError('Unexpected options schema change beyond its owned marker counter.')
    before_counter = re.search(r' AUTO_INCREMENT=(\d+)', create)
    after_counter = re.search(r' AUTO_INCREMENT=(\d+)', actual_schema)
    if not before_counter or not after_counter or not int(before_counter[1]) + int(allow_marker) <= int(after_counter[1]) <= int(before_counter[1]) + 16:
        raise RecoveryError('Unexpected native options identity counter change.')
    target = b'`wooshop_recovery_options_proof`'
    sql = b'USE `wooshop_package_check`;\nCREATE TEMPORARY TABLE ' + target + b' LIKE `' + name + b'`;\n'
    sql += b''.join(statement.replace(b'INSERT INTO `' + name + b'`', b'INSERT INTO ' + target, 1) for statement in inserts)
    for label, table in [(b'ORIGINAL', target), (b'CURRENT', b'`' + name + b'`')]:
        sql += b"SELECT '" + label + b"',option_id,HEX(option_name),HEX(option_value),HEX(autoload) FROM " + table + b' ORDER BY option_id;\n'
    sql += b'DROP TEMPORARY TABLE ' + target + b';\n'
    with tempfile.TemporaryFile(dir=BACKUPS) as stream:
        stream.write(sql); stream.seek(0)
        result = db(['mariadb', '--user=' + USER, '--database=' + DATABASE, '--one-database', '--default-character-set=utf8mb4', '--batch', '--skip-column-names'], environment, stdin=stream)
    sets = {b'ORIGINAL': {}, b'CURRENT': {}}
    for line in result.stdout.splitlines():
        fields = line.split(b'\t')
        if len(fields) != 5 or fields[0] not in sets:
            raise RecoveryError('Unrecognized private option audit result.')
        values = [fields[1]] + [bytes.fromhex(value.decode()) for value in fields[2:]]
        sets[fields[0]][values[1]] = dict(zip(['option_id', 'option_name', 'option_value', 'autoload'], values))
    def serialized_string(value):
        return b's:' + str(len(value)).encode() + b':"' + value + b'";'
    hashes = [hashlib.sha256(b'a:4:{' + b''.join(serialized_string(key.encode()) + serialized_string(value) for key, value in sorted(row.items())) + b'}').hexdigest() for row in sets[b'ORIGINAL'].values()]
    if len(hashes) != baseline['tables'][option]['rows'] or hashlib.sha256(''.join(sorted(hashes)).encode()).hexdigest() != baseline['tables'][option]['rows_sha256']:
        raise RecoveryError('Official parser option rows did not reproduce original full-row checksum.')
    marker = ('wooshop_recovery_probe_' + run_id).encode(); cron = b'_transient_doing_cron'; cron_changed = False
    pattern = b'_site_transient_wp_theme_files_patterns-c0d967daddc9fb20d6981e1cd29d28cc'
    pattern_timeout = b'_site_transient_timeout_wp_theme_files_patterns-c0d967daddc9fb20d6981e1cd29d28cc'
    cache_recreations = []
    for key in set(sets[b'ORIGINAL']) | set(sets[b'CURRENT']):
        old = sets[b'ORIGINAL'].get(key); new = sets[b'CURRENT'].get(key)
        if key == marker:
            expected = hashlib.sha256(('wooshop-package-recovery:' + run_id).encode()).hexdigest().encode()
            if not allow_marker or old is not None or not new or new['option_value'] != expected or int(new['option_id']) != int(before_counter[1]):
                raise RecoveryError('Marker identity/value does not match the one owned test insertion.')
        elif old != new:
            if old is None or new is None or old['option_name'] != new['option_name'] or old['autoload'] != new['autoload']:
                raise RecoveryError('Unexpected option identity/name/autoload change.')
            if key == cron and old['option_id'] == new['option_id'] and re.fullmatch(rb'\d+(?:\.\d+)?', new['option_value']):
                cron_changed = True
            elif key == pattern and old['option_value'] == new['option_value'] and int(before_counter[1]) <= int(new['option_id']) < int(after_counter[1]):
                cache_recreations.append({'option_name': key.decode(), 'original_id': old['option_id'].decode(), 'current_id': new['option_id'].decode(), 'payload_bytes_unchanged': True})
            elif key == pattern_timeout and new['option_value'].isdigit() and old['option_value'].isdigit() and int(new['option_value']) >= int(old['option_value']) and int(before_counter[1]) <= int(new['option_id']) < int(after_counter[1]):
                cache_recreations.append({'option_name': key.decode(), 'original_id': old['option_id'].decode(), 'current_id': new['option_id'].decode(), 'numeric_expiry_refreshed': True, 'native_ttl_seconds': 1800})
            else:
                raise RecoveryError('Unexpected option change outside the exact proven cron/pattern-cache lifecycle.')
    if allow_marker != (marker in sets[b'CURRENT']):
        raise RecoveryError('Owned marker presence differs from the reviewed phase.')
    return {'original_full_option_rows_sha256_verified': True, 'original_schema_sha256_verified': True, 'only_expected_marker_counter_change': True,
            'only_allowed_cache_option': '_transient_doing_cron', 'cron_lock_value_refreshed': cron_changed, 'other_option_fields_exact': True, 'temporary_table_dropped': True,
            'exact_native_pattern_cache_recreations': cache_recreations,
            'diagnostic_sequence': {'original_next_id': int(before_counter[1]), 'observed_next_id': int(after_counter[1]), 'owned_marker_id': int(before_counter[1]) if allow_marker else None,
                                    'non_marker_sequence_allocations': int(after_counter[1]) - int(before_counter[1]) - int(allow_marker),
                                    'allocations_accounted_for_by_pattern_cache_rows': len(cache_recreations), 'bounded_upper_increment': 16, 'duplicate_caller_claimed': False}}


def verify_business_snapshot(before, current):
    if before['native_orders'] != current['native_orders'] or before['ledger_and_extension_sha256'] != current['ledger_and_extension_sha256'] or before['settings_sha256'] != current['settings_sha256'] or before['settings_option_count'] != current['settings_option_count']:
        raise RecoveryError('Native order/item/ledger/settings invariant changed after bootstrap.')
    options = [name for name in before['tables'] if name.endswith('_options')]
    for name in set(before['tables']) | set(current['tables']):
        if name not in options and before['tables'].get(name) != current['tables'].get(name):
            raise RecoveryError('A non-cache table changed after bootstrap.')
    if before['total_rows'] != current['total_rows'] or before['table_count'] != current['table_count']:
        raise RecoveryError('Unexpected row/table count after normal bootstrap.')
    return {'native_order_ids_data_totals_items_exact': True, 'ledger_and_extensions_exact': True, 'business_settings_exact': True, 'all_other_tables_exact': True}


def resume_original_restore(hold_token, run_id):
    safe_paths()
    with open(BACKUPS / '.package-check-recovery.lock', 'a+b') as lock:
        fcntl.flock(lock.fileno(), fcntl.LOCK_EX | fcntl.LOCK_NB)
        state_file = BACKUPS / 'package-check-recovery-state.json'
        if state_file.is_symlink() or stat.S_IMODE(state_file.stat().st_mode) != 0o600:
            raise RecoveryError('Private failed-run state is required for reviewed restore.')
        previous = json.loads(state_file.read_text())
        report = json.loads(EVIDENCE.read_text())
        review = report.get('reviewed_restore', {})
        if not re.fullmatch(r'[a-f0-9]{32}', run_id or '') or previous.get('run_id') != run_id or report.get('run_id') != run_id or previous.get('status') != 'FAILED' or previous.get('failed_stage') not in ['restored_application_verification', 'post_backup_foreign_write_guard']:
            raise RecoveryError('Reviewed restore requires the exact failed post-client run.')
        if previous.get('scope', {}).get('database') != DATABASE or previous.get('coordinated_hold_id') != hold_token or previous.get('maintenance_prior_active') is not False or previous.get('maintenance_prior_file') != {'existed': False}:
            raise RecoveryError('Reviewed restore cannot change the original scope or maintenance snapshot.')
        if review.get('run_id') != run_id or review.get('hold_id') != hold_token or review.get('root_reviewed') is not True:
            raise RecoveryError('Reviewed original-backup restore has no matching parent review.')
        backup = ROOT / previous['backup_path']
        checksum = previous.get('backup', {}).get('sha256')
        if not backup.resolve().is_relative_to(BACKUPS.resolve()) or backup.is_symlink() or not backup.is_file() or review.get('backup_sha256') != checksum or sha256(backup) != checksum or stat.S_IMODE(backup.stat().st_mode) != 0o600:
            raise RecoveryError('Only the verified original private snapshot may be restored.')
        if report.get('baseline') != previous.get('baseline') or previous.get('marker_added') != {'post_backup_marker_present': True}:
            raise RecoveryError('Original baseline/owned marker evidence changed; no rebaseline permitted.')
        flag = LOCAL / 'sites/package-check/.maintenance'
        if flag.is_symlink() or flag.read_bytes() != ('<?php $upgrading = ' + str(2**63 - 1 - 600) + ';\n').encode():
            raise RecoveryError('Reviewed restore requires the exact existing owned maintenance hold.')
        retained = backup.with_suffix('.failed-before-reviewed-restore-' + secrets.token_hex(8) + '.report.json')
        private_bytes(retained, (json.dumps(report, indent=2, ensure_ascii=False) + '\n').encode())
        report.setdefault('prior_attempts', []).append({'kind': 'runtime_attempt', 'run_id': run_id, 'status': 'FAILED', 'failed_stage': previous['failed_stage'], 'elapsed_seconds': previous['elapsed_seconds'], 'private_full_receipt': str(retained.relative_to(ROOT)), 'receipt_sha256': sha256(retained), 'restore_client_was_noop': True})
        report['file_fingerprints'] = {path: sha256(ROOT / path) for path in ['tools/recovery.py', 'tests/core/recovery.php']}
        COMMANDS.clear(); report['commands'] = COMMANDS
        report['status'] = 'RUNNING'; report.pop('failed_stage', None); report.pop('failure', None)
        report['reviewed_restore_started_at'] = datetime.datetime.now(datetime.timezone.utc).isoformat()
        started = time.monotonic(); stage = 'reviewed_restore_scope_preflight'
        try:
            environment, _ = database_environment()
            observe('preflight', run_id)
            if not maintenance_active():
                raise RecoveryError('Owned maintenance is inactive; reviewed restore refused.')
            stage = 'post_backup_foreign_write_guard'
            current = observe('snapshot', run_id)
            # Every non-options table and all business projections remain exact.
            without_marker = dict(current); without_marker['total_rows'] = current['total_rows'] - 1
            without_marker['settings_sha256'] = current['own_marker_diagnostic']['settings_without_marker_sha256']
            without_marker['settings_option_count'] = current['settings_option_count'] - 1
            report['post_backup_business_guard'] = verify_business_snapshot(report['baseline'], without_marker)
            report['post_backup_foreign_write_guard'] = audit_original_options(environment, backup, checksum, report['baseline'], current, run_id, True)
            stage = 'reviewed_original_backup_restore'
            report['restore_stream'] = restore_verified_stream(backup, checksum, environment)
            report['previous_noop_client_seconds'] = report.get('restore_elapsed_seconds')
            report['restore_elapsed_seconds'] = report['restore_stream']['elapsed_seconds']
            stage = 'restored_application_verification'
            raw = observe_raw(run_id)
            if raw.get('marker_absent') is not True or any(raw.get(key) != report['baseline'].get(key) for key in ['tables', 'database_rows_and_schema_sha256', 'table_count', 'total_rows']):
                raise RecoveryError('Unmodified full raw rows/schema or marker absence did not match before normal bootstrap.')
            report['raw_restored_before_bootstrap'] = raw
            report['marker_restored_absent'] = {'post_backup_marker_absent': True}
            after = observe('snapshot', run_id)
            report['verification'] = verify_business_snapshot(report['baseline'], after)
            report['post_bootstrap_cache_audit'] = audit_original_options(environment, backup, checksum, report['baseline'], after, run_id, False)
            report['verification']['all_unmodified_raw_rows_and_schema_exact_before_bootstrap'] = True
            report['restored'] = after
            stage = 'validated_maintenance_restoration'
            restore_maintenance(None, None, None, False, run_id)
            if maintenance_active():
                raise RecoveryError('Original inactive maintenance state did not restore.')
            report['maintenance_restored_to_prior_state'] = True
            report['maintenance_original_bytes_and_mode_restored'] = True
            report['status'] = 'PASS'
            report['conclusion'] = 'Original third-site snapshot restored with explicit USE; full invariants match. Broader recovery requirements remain PARTIAL.'
            report.pop('failure_containment', None)
        except BaseException as failure:
            report['status'] = 'FAILED'; report['failed_stage'] = stage
            report['failure'] = str(failure) if isinstance(failure, RecoveryError) else 'Redacted unexpected ' + type(failure).__name__
            fail_closed(report)
        report['safe_to_retry_preflight'] = False
        report['finished_at'] = datetime.datetime.now(datetime.timezone.utc).isoformat()
        report['reviewed_restore_elapsed_seconds'] = round(time.monotonic() - started, 6)
        report['elapsed_seconds'] = round((datetime.datetime.fromisoformat(report['finished_at']) - datetime.datetime.fromisoformat(report['started_at'])).total_seconds(), 6)
        report['elapsed_seconds_includes_review_pause'] = True
        report['production_rpo_rto_claimed'] = False
        json_write(state_file, report, True); json_write(backup.with_suffix('.report.json'), report, True); json_write(EVIDENCE, report)
        print(json.dumps({'status': report['status'], 'run_id': run_id, 'evidence': str(EVIDENCE.relative_to(ROOT)), 'reviewed_restore_elapsed_seconds': report['reviewed_restore_elapsed_seconds'], 'failed_stage': report.get('failed_stage')}, separators=(',', ':')))
        return 0 if report['status'] == 'PASS' else 1


def reviewed_resume(previous, run_id, hold_token):
    if not re.fullmatch(r'[a-f0-9]{32}', run_id or '') or previous.get('run_id') != run_id or previous.get('status') != 'FAILED' or previous.get('failed_stage') != 'baseline_snapshot':
        raise RecoveryError('Reviewed resume requires the exact failed pre-dump baseline run.')
    if previous.get('scope', {}).get('database') != DATABASE or previous.get('coordinated_hold_id') != hold_token or previous.get('maintenance_prior_active') is not False or previous.get('maintenance_prior_file') != {'existed': False}:
        raise RecoveryError('Reviewed resume cannot reinterpret another site, hold or original maintenance snapshot.')
    if previous.get('backup') is not None or previous.get('marker_added') is not None or previous.get('restore_elapsed_seconds') is not None:
        raise RecoveryError('Reviewed resume refuses a run that reached dump, marker or restore.')
    backup_path = ROOT / previous.get('backup_path', '')
    if not backup_path.resolve().is_relative_to(BACKUPS.resolve()) or backup_path.exists() or backup_path.is_symlink():
        raise RecoveryError('Reviewed resume requires no prior SQL dump file.')
    review = json.loads(EVIDENCE.read_text()).get('reviewed_resume', {})
    if review.get('run_id') != run_id or review.get('hold_id') != hold_token or review.get('root_reviewed') is not True:
        raise RecoveryError('Exact resumed run has no recorded parent review.')
    flag = LOCAL / 'sites/package-check/.maintenance'
    expected = ('<?php $upgrading = ' + str(2**63 - 1 - 600) + ';\n').encode()
    if flag.is_symlink() or not flag.is_file() or flag.read_bytes() != expected:
        raise RecoveryError('Reviewed resume requires this drill’s exact owned maintenance flag.')
    return previous


def run_drill(hold_token, resume_run=None):
    if not re.fullmatch(r'[a-z0-9][a-z0-9-]{11,79}', hold_token or ''):
        raise RecoveryError('A separately coordinated third-site idle hold identifier is required.')
    safe_paths()
    lock_path = BACKUPS / '.package-check-recovery.lock'
    if lock_path.is_symlink():
        raise RecoveryError('Refused recovery lock symlink.')
    with open(lock_path, 'a+b') as lock:
        os.chmod(lock_path, 0o600)
        try:
            fcntl.flock(lock.fileno(), fcntl.LOCK_EX | fcntl.LOCK_NB)
        except BlockingIOError:
            raise RecoveryError('Another package-check recovery drill holds the local lock.') from None
        state_file = BACKUPS / 'package-check-recovery-state.json'
        if state_file.is_symlink():
            raise RecoveryError('Refused recovery state symlink.')
        resume_state = None
        if state_file.exists():
            if stat.S_IMODE(state_file.stat().st_mode) != 0o600:
                raise RecoveryError('Private recovery state needs owner-only permissions.')
            previous = json.loads(state_file.read_text())
            if resume_run:
                resume_state = reviewed_resume(previous, resume_run, hold_token)
            elif previous.get('status') != 'PASS' and not previous.get('safe_to_retry_preflight', False):
                raise RecoveryError('A prior unfinished drill requires reviewed recovery; no new snapshot/restore is permitted.')
        elif resume_run:
            raise RecoveryError('No matching private run exists for reviewed resume.')
        previous_evidence = json.loads(EVIDENCE.read_text()) if EVIDENCE.exists() else {}
        report = base_evidence()
        history = list(previous_evidence.get('prior_attempts', []))
        previous_run = previous_evidence.get('run_id')
        if previous_run and not any(entry.get('run_id') == previous_run for entry in history):
            history.append({key: previous_evidence.get(key) for key in ['run_id', 'status', 'started_at', 'finished_at', 'failed_stage', 'failure', 'elapsed_seconds', 'safe_to_retry_preflight', 'backup_path']})
        report['prior_attempts'] = history
        for key in ['approval_received', 'preparation_checks', 'peer_review', 'reviewed_preflight_fix', 'reviewed_resume', 'reviewed_snapshot_fix']:
            if key in previous_evidence:
                report[key] = previous_evidence[key]
        COMMANDS.clear()
        report['commands'] = COMMANDS
        run = secrets.token_hex(16)
        start = time.monotonic()
        timestamp = datetime.datetime.now(datetime.timezone.utc)
        backup = BACKUPS / ('package-check-' + timestamp.strftime('%Y%m%dT%H%M%SZ') + '-' + run + '.sql')
        report.update(status='RUNNING', run_id=run, coordinated_hold_id=hold_token, started_at=timestamp.isoformat(), backup_path=str(backup.relative_to(ROOT)))
        if resume_state:
            report['resumed_from_run'] = resume_state['run_id']
        stage = 'scope_preflight'
        containment_required = False
        try:
            environment, image = database_environment()
            report['database_image'] = image
            report['preflight'] = observe('preflight', run)
            identity = db(['mariadb', '--user=' + USER, '--database=' + DATABASE, '--batch', '--skip-column-names', '--execute=SELECT DATABASE(),CURRENT_USER()'], environment).stdout.decode().strip().split('\t')
            if len(identity) != 2 or identity[0] != DATABASE or not identity[1].startswith(USER + '@'):
                raise RecoveryError('Restricted database client identity did not match the third site.')
            report['restricted_database_identity_verified'] = True
            current_maintenance = maintenance_active()
            if resume_state:
                if not current_maintenance:
                    raise RecoveryError('Owned reviewed maintenance hold is no longer active.')
                stage = 'resume_old_marker_absence'
                observe('marker-absent', resume_state['run_id'])
                original_maintenance, original_mode, original_timestamp = None, None, None
                maintenance_file = resume_state['maintenance_prior_file']
                prior_maintenance = False
            else:
                original_maintenance, original_mode, original_timestamp, maintenance_file = maintenance_snapshot(backup)
                prior_maintenance = current_maintenance
                expected_prior = original_timestamp is not None and time.time() - original_timestamp < 600
                if prior_maintenance != expected_prior:
                    raise RecoveryError('Maintenance changed before the drill; coordinated hold is not exclusive.')
            report['maintenance_prior_active'] = prior_maintenance
            report['maintenance_prior_file'] = maintenance_file
            stage = 'maintenance_hold'
            containment_required = True
            if not current_maintenance:
                wp(['maintenance-mode', 'activate'])
            persistent_native_hold()
            if not maintenance_active():
                raise RecoveryError('Native maintenance hold could not be confirmed.')
            json_write(state_file, report, True)
            stage = 'baseline_snapshot'
            before = observe('snapshot', run)
            report['baseline'] = before
            stage = 'consistent_database_dump'
            dump_start = time.monotonic()
            descriptor = os.open(backup, os.O_WRONLY | os.O_CREAT | os.O_EXCL, 0o600)
            with os.fdopen(descriptor, 'wb') as target:
                db(['mariadb-dump', '--user=' + USER, '--single-transaction', '--quick', '--hex-blob', '--skip-comments', '--skip-add-locks', '--skip-lock-tables', '--no-tablespaces', '--default-character-set=utf8mb4', DATABASE], environment, stdout=target, timeout=60)
                target.flush()
                os.fsync(target.fileno())
            size = backup.stat().st_size
            if size < 128 or size > MAX_BYTES or stat.S_IMODE(backup.stat().st_mode) != 0o600:
                raise RecoveryError('Dump size or owner-only permission check failed.')
            checksum = sha256(backup)
            report['backup'] = {'bytes': size, 'sha256': checksum, 'mode': '0600', 'single_transaction': True, 'elapsed_seconds': round(time.monotonic() - dump_start, 6)}
            # No write occurred between baseline and backup. Verify the snapshot itself
            # was not taken while another actor was changing the held dataset.
            verify_snapshot(before, observe('snapshot', run))
            report['baseline_stable_through_backup'] = True
            stage = 'unique_post_backup_marker'
            report['marker_added'] = observe('marker', run)
            json_write(state_file, report, True)
            stage = 'verified_database_restore'
            report['restore_stream'] = restore_verified_stream(backup, checksum, environment)
            report['restore_elapsed_seconds'] = report['restore_stream']['elapsed_seconds']
            stage = 'restored_application_verification'
            raw = observe_raw(run)
            if raw.get('marker_absent') is not True or any(raw.get(key) != before.get(key) for key in ['tables', 'database_rows_and_schema_sha256', 'table_count', 'total_rows']):
                raise RecoveryError('Unmodified full raw rows/schema or marker absence did not match before normal bootstrap.')
            report['raw_restored_before_bootstrap'] = raw
            report['marker_restored_absent'] = {'post_backup_marker_absent': True}
            after = observe('snapshot', run)
            report['verification'] = verify_business_snapshot(before, after)
            report['post_bootstrap_cache_audit'] = audit_original_options(environment, backup, checksum, before, after, run, False)
            report['verification']['all_unmodified_raw_rows_and_schema_exact_before_bootstrap'] = True
            report['restored'] = after
            if sha256(backup) != checksum:
                raise RecoveryError('Retained backup checksum changed after restore.')
            stage = 'validated_maintenance_restoration'
            restore_maintenance(original_maintenance, original_mode, original_timestamp, prior_maintenance, run)
            if maintenance_active() != prior_maintenance:
                raise RecoveryError('Prior maintenance state was not restored.')
            report['maintenance_restored_to_prior_state'] = True
            report['maintenance_original_bytes_and_mode_restored'] = True
            report['status'] = 'PASS'
            report['conclusion'] = 'Bounded third-site local DB restore verified; broader recovery requirements remain PARTIAL.'
        except BaseException as failure:
            report['status'] = 'FAILED'
            report['failed_stage'] = stage
            report['failure'] = str(failure) if isinstance(failure, RecoveryError) else 'Redacted unexpected ' + type(failure).__name__
            if containment_required:
                fail_closed(report)
            elif resume_state:
                report['failure_containment'] = 'Existing exact owned third-site maintenance flag retained; no further mutation after failed resume preflight.'
            report['safe_to_retry_preflight'] = not containment_required and resume_state is None
            report['recovery_action'] = 'Keep package-check closed. Retain this backup/report; coordinate root review of the failed stage and verified checksum before any further restore or reopening. Never restore either source database.'
        report['finished_at'] = datetime.datetime.now(datetime.timezone.utc).isoformat()
        report['elapsed_seconds'] = round(time.monotonic() - start, 6)
        report['production_rpo_rto_claimed'] = False
        json_write(state_file, report, True)
        json_write(backup.with_suffix('.report.json'), report, True)
        json_write(EVIDENCE, report)
        print(json.dumps({'status': report['status'], 'run_id': run, 'evidence': str(EVIDENCE.relative_to(ROOT)), 'backup': report['backup_path'], 'elapsed_seconds': report['elapsed_seconds'], 'failed_stage': report.get('failed_stage')}, separators=(',', ':')))
        return 0 if report['status'] == 'PASS' else 1


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('command', choices=['plan', 'run', 'resume-restore'])
    parser.add_argument('--hold-token', help='Nonsecret root-coordinated package-check idle hold identifier; never infer approval from elapsed time.')
    parser.add_argument('--resume-reviewed-run', help='Exact parent-reviewed pre-dump baseline failure; original prior-inactive maintenance state is carried forward.')
    args = parser.parse_args()
    try:
        if args.command == 'plan':
            if EVIDENCE.exists() and json.loads(EVIDENCE.read_text()).get('status') not in ['NOT_RUN', None]:
                raise RecoveryError('Execution evidence already exists; plan will not overwrite a completed/failed recovery receipt.')
            evidence = base_evidence()
            evidence['prepared_at'] = datetime.datetime.now(datetime.timezone.utc).isoformat()
            evidence['execution_gate'] = 'NOT RUN. Root must explicitly confirm package-check is idle after other workers finish, then supply its nonsecret hold identifier. No database or maintenance operation was performed by plan.'
            json_write(EVIDENCE, evidence)
            print('Recovery drill prepared: NOT RUN; package-check idle hold required.')
            return 0
        if args.command == 'resume-restore':
            return resume_original_restore(args.hold_token, args.resume_reviewed_run)
        return run_drill(args.hold_token, args.resume_reviewed_run)
    except RecoveryError as failure:
        print('Recovery refused: ' + str(failure), file=sys.stderr)
        return 2
    except Exception as failure:
        print('Recovery refused: redacted ' + type(failure).__name__, file=sys.stderr)
        return 2


if __name__ == '__main__':
    raise SystemExit(main())
