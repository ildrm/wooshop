#!/usr/bin/env python3
"""Prove two local ZIP builds agree while custom product source stays unchanged."""
from pathlib import Path
from datetime import datetime, timezone
import hashlib
import json
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[1]
started = datetime.now(timezone.utc).isoformat()
def source():
    return {str(p.relative_to(ROOT)): hashlib.sha256(p.read_bytes()).hexdigest() for p in sorted((ROOT / 'packages').rglob('*')) if p.is_file()}
before = source()
builds = []
for attempt in [1, 2]:
    process = subprocess.run([sys.executable, 'tools/package.py'], cwd=ROOT, text=True, capture_output=True)
    if process.returncode:
        raise SystemExit('Package build failed: ' + process.stdout + process.stderr)
    manifest = json.loads((ROOT / 'artifacts/releases/manifest.json').read_text())
    builds.append({p['artifact']: p['sha256'] for p in manifest['packages']})
after = source()
ok = before == after and builds[0] == builds[1]
report = {'result': 'PASS' if ok else 'FAIL', 'started_at': started, 'finished_at': datetime.now(timezone.utc).isoformat(), 'source_unchanged': before == after, 'builds': builds, 'source_files': before, 'scope': 'Two consecutive local ZIP builds of frozen custom product source on this Python/zlib environment; not a cross-platform reproducibility or release approval claim'}
path = ROOT / 'artifacts/releases/reproducibility.json'
path.write_text(json.dumps(report, indent=2) + '\n')
print(json.dumps({k:v for k,v in report.items() if k != 'source_files'}, indent=2))
sys.exit(not ok)
