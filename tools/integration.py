#!/usr/bin/env python3
"""Run the installed WordPress integration suites on one guarded local site."""
from pathlib import Path
import argparse
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[1]
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--site', choices=['outdoor', 'smart-home', 'package-check'], default='outdoor')
args = parser.parse_args()
suites = ['core/wordpress.php', 'core/settings-history.php', 'catalog/lifecycle.php', 'catalog/wordpress.php', 'catalog/loyalty-wordpress.php', 'catalog/incentives-wordpress.php', 'marketplace/integration.php', 'marketplace/store-api.php']
for suite in suites:
    if not (ROOT / 'tests' / suite).is_file():
        raise SystemExit('Missing integration suite: ' + suite)
    # eval(require) supports strict_types source files; every test enforces local fixtures.
    code = "require '/workspace/tests/" + suite + "';"
    result = subprocess.run([sys.executable, 'tools/dev.py', 'wp', args.site, 'eval', code], cwd=ROOT)
    if result.returncode:
        raise SystemExit('Integration failed: ' + suite)
print('PASS: installed WordPress integration suite on ' + args.site)
