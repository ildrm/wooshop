#!/usr/bin/env python3
"""Fetch only the two reviewed, hash-pinned local development inputs."""
from pathlib import Path
import argparse
import hashlib
import json
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
INPUTS = [
    ('wp-cli.phar', 'https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar', 'ce34ddd838f7351d6759068d09793f26755463b4a4610a5a5c0a97b68220d85c', 20000000),
    ('woocommerce.11.2.1.zip', 'https://downloads.wordpress.org/plugin/woocommerce.11.2.1.zip', '5317434cb381b5fdc7c1dd6ee258324f08c390976aaf8d1199f2a0e9a3ab8586', 100000000),
]
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--verify-only', action='store_true')
args = parser.parse_args()
cache = ROOT / '.local/cache'
cache.mkdir(parents=True, exist_ok=True)
if cache.is_symlink() or not cache.resolve().is_relative_to(ROOT):
    raise SystemExit('Unsafe cache path.')
results = []
for name, url, expected, maximum in INPUTS:
    destination = cache / name
    if destination.is_symlink():
        raise SystemExit('Unsafe input path.')
    if not destination.exists():
        if args.verify_only:
            raise SystemExit('Missing reviewed input: ' + name)
        with urllib.request.urlopen(url, timeout=90) as response:
            payload = response.read(maximum + 1)
        if len(payload) > maximum or hashlib.sha256(payload).hexdigest() != expected:
            raise SystemExit('Input size/integrity verification failed: ' + name)
        destination.write_bytes(payload)
    if hashlib.sha256(destination.read_bytes()).hexdigest() != expected:
        raise SystemExit('Input integrity verification failed: ' + name)
    results.append({'name': name, 'sha256': expected, 'result': 'PASS'})
print(json.dumps(results, indent=2))
