#!/usr/bin/env python3
"""Capture bounded, redacted evidence for fixed local verification commands."""
from pathlib import Path
from datetime import datetime, timezone
import argparse
import hashlib
import json
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[1]
COMMANDS = {
    'static': [sys.executable, 'tools/check.py'],
    'hygiene': [sys.executable, 'tools/security.py'],
    'package': [sys.executable, 'tools/package.py'],
    'integration-outdoor': [sys.executable, 'tools/integration.py', '--site', 'outdoor'],
    'integration-smart-home': [sys.executable, 'tools/integration.py', '--site', 'smart-home'],
    'integration-package': [sys.executable, 'tools/integration.py', '--site', 'package-check'],
    'browser': ['npx', 'playwright', 'test', 'storefront.spec.js'],
    'browser-settings': ['npx', 'playwright', 'test', 'settings.spec.js', '--output', 'artifacts/settings-browser'],
}
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('suite', choices=COMMANDS)
args = parser.parse_args()
started = datetime.now(timezone.utc)
fingerprints = {str(p.relative_to(ROOT)): hashlib.sha256(p.read_bytes()).hexdigest() for directory in ['packages', 'tests', 'tools', 'profiles'] for p in sorted((ROOT / directory).rglob('*')) if p.is_file() and '__pycache__' not in p.parts}
source_digest = hashlib.sha256(json.dumps(fingerprints, sort_keys=True).encode()).hexdigest()
result = subprocess.run(COMMANDS[args.suite], cwd=ROOT, text=True, capture_output=True)
output = (result.stdout + result.stderr)[-18000:]
credentials = ROOT / '.local/credentials.json'
if credentials.exists():
    for value in json.loads(credentials.read_text()).values():
        if isinstance(value, str) and len(value) >= 24:
            output = output.replace(value, '[REDACTED LOCAL FIXTURE SECRET]')
report = {'suite': args.suite, 'started_at': started.isoformat(), 'finished_at': datetime.now(timezone.utc).isoformat(), 'command': COMMANDS[args.suite], 'cwd': str(ROOT), 'exit_status': result.returncode, 'result': 'PASS' if result.returncode == 0 else 'FAIL', 'source_fingerprint': source_digest, 'source_files': fingerprints, 'output': output, 'environment_scope': 'isolated synthetic local environment; no external provider success or production approval'}
destination = ROOT / 'artifacts/verification' / (started.strftime('%Y%m%dT%H%M%SZ') + '-' + args.suite + '.json')
destination.parent.mkdir(parents=True, exist_ok=True)
destination.write_text(json.dumps(report, indent=2) + '\n')
print(output)
print(json.dumps({'result': report['result'], 'exit_status': result.returncode, 'source_fingerprint': source_digest, 'evidence': str(destination.relative_to(ROOT))}))
sys.exit(result.returncode)
