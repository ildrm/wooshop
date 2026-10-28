#!/usr/bin/env python3
"""Check synthetic per-site native password hashes without logging credentials."""
from pathlib import Path
from datetime import datetime, timezone
import json
import sys
from dev import ALL_SITES, LOCAL, ROOT, admin_key, compose

credentials = json.loads((LOCAL / 'credentials.json').read_text())
probes = {site: credentials[admin_key(site)] for site in ALL_SITES}
if len(set(probes.values())) != len(ALL_SITES):
    raise SystemExit('Synthetic administrator credentials must differ across sites.')
code = 'if (wp_get_environment_type() !== "local" || !defined("WOOSHOP_LOCAL_FIXTURES") || !WOOSHOP_LOCAL_FIXTURES) {throw new Exception("Local fixtures required");} $u=get_user_by("login","fixture-admin"); if (!$u) {throw new Exception("Fixture identity missing");} $probes=json_decode(stream_get_contents(STDIN),true,8,JSON_THROW_ON_ERROR); $result=[]; foreach($probes as $site=>$value){$result[$site]=wp_check_password($value,$u->user_pass,$u->ID);} echo json_encode($result);'
results = {}
for site in ALL_SITES:
    command = ['exec', '-T', site, 'php', '/workspace/.local/cache/wp-cli.phar', '--allow-root', '--path=/var/www/html', 'eval', code]
    result = compose(command, capture=True, input_text=json.dumps(probes))
    try:
        matches = json.loads(result.stdout)
    except ValueError:
        raise SystemExit('Native password check returned an invalid redacted result.') from None
    results[site] = {'own_matches': matches.get(site) is True, 'foreign_passwords_denied': all(matches.get(other) is False for other in ALL_SITES if other != site)}
ok = all(all(result.values()) for result in results.values())
report = {'result': 'PASS' if ok else 'FAIL', 'executed_at': datetime.now(timezone.utc).isoformat(), 'native_hash_checks': len(ALL_SITES) ** 2, 'results': results, 'scope': 'Synthetic WordPress native password hash isolation only; not cookie/MFA/provider/production authentication certification', 'credential_values_printed': False}
destination = ROOT / 'docs/implementation/credential-isolation-evidence.json'
destination.write_text(json.dumps(report, indent=2) + '\n')
print(json.dumps(report))
sys.exit(not ok)
