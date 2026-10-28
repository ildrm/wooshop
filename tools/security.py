#!/usr/bin/env python3
"""Bounded source/release hygiene checks, not a penetration test or certification."""
from pathlib import Path
import hashlib
import json
import re
import sys
import zipfile

ROOT = Path(__file__).resolve().parents[1]
failures = []
secrets = []
credentials = ROOT / '.local/credentials.json'
if credentials.exists():
    secrets = [value.encode() for value in json.loads(credentials.read_text()).values() if isinstance(value, str) and len(value) >= 24]
pattern = re.compile(rb'-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----|\b(?:AKIA|ASIA)[A-Z0-9]{16}\b')
files = []
for directory in ['packages', 'tools', 'tests', 'profiles', 'docs/implementation', '.github']:
    files.extend(p for p in (ROOT / directory).rglob('*') if p.is_file() and '__pycache__' not in p.parts)
for path in files:
    payload = path.read_bytes()
    if pattern.search(payload) or any(secret in payload for secret in secrets):
        failures.append({'path': str(path.relative_to(ROOT)), 'finding': 'possible secret; value withheld'})
archive_members = 0
for path in (ROOT / 'artifacts/releases').glob('*.zip'):
    with zipfile.ZipFile(path) as archive:
        for name in archive.namelist():
            archive_members += 1
            parts = Path(name).parts
            payload = archive.read(name)
            if Path(name).is_absolute() or '..' in parts or any(p in ['.local', 'node_modules', '.git', 'tests', 'credentials.json'] or p.startswith('.env') for p in parts):
                failures.append({'path': path.name + ':' + name, 'finding': 'private/development or unsafe archive member'})
            if pattern.search(payload) or any(secret in payload for secret in secrets):
                failures.append({'path': path.name + ':' + name, 'finding': 'possible secret; value withheld'})
report = {'scope': 'exact current local credential values plus bounded key patterns and archive member policy', 'limitations': 'No claim of comprehensive secret, SAST, vulnerability or exploit coverage. Rotated secrets cannot be rediscovered by exact-value scan.', 'source_files': len(files), 'archive_members': archive_members, 'result': 'FAIL' if failures else 'PASS', 'findings': failures}
destination = ROOT / 'artifacts/security-hygiene.json'
destination.parent.mkdir(parents=True, exist_ok=True)
destination.write_text(json.dumps(report, indent=2) + '\n')
print(json.dumps(report, indent=2))
sys.exit(bool(failures))
