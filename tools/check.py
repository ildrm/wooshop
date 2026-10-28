#!/usr/bin/env python3
"""Static and pure-domain checks. Integration/browser evidence is recorded separately."""
from pathlib import Path
import json
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[1]
failures = []
counts = {'php_syntax': 0, 'javascript_syntax': 0, 'json': 0, 'domain_suites': 0}


def run(command, label):
    result = subprocess.run(command, cwd=ROOT, text=True, capture_output=True)
    if result.returncode:
        failures.append({'check': label, 'output': (result.stdout + result.stderr)[-5000:]})
    elif label.startswith('domain:'):
        print(result.stdout.strip())


for path in sorted((ROOT / 'packages').rglob('*')):
    if path.is_symlink():
        failures.append({'check': 'distribution path', 'output': 'Symlink: ' + str(path.relative_to(ROOT))})
    if not path.is_file():
        continue
    if path.suffix == '.php':
        counts['php_syntax'] += 1
        run(['php', '-l', str(path)], 'PHP syntax: ' + str(path.relative_to(ROOT)))
    elif path.suffix == '.js':
        counts['javascript_syntax'] += 1
        run(['node', '--check', str(path)], 'JavaScript syntax: ' + str(path.relative_to(ROOT)))
    elif path.suffix == '.json':
        counts['json'] += 1
        try:
            json.loads(path.read_text())
        except Exception as error:
            failures.append({'check': 'JSON syntax', 'output': str(path.relative_to(ROOT)) + ': ' + str(error)})
for relative in ['tests/core/domain.php', 'tests/catalog/domain.php', 'tests/marketplace/domain.php']:
    if (ROOT / relative).is_file():
        counts['domain_suites'] += 1
        run(['php', relative], 'domain:' + relative)
run(['python3', 'tests/theme/check.py'], 'Theme static contracts')
print(json.dumps({'scope': 'static and pure domain;host PHP,not WordPress integration', 'counts': counts, 'result': 'FAIL' if failures else 'PASS', 'failures': failures}, indent=2))
sys.exit(bool(failures))
