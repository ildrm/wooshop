#!/usr/bin/env python3
"""Build development ZIPs from the two custom products, excluding local/test data."""
from pathlib import Path
import hashlib
import json
import re
import zipfile

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / 'artifacts/releases'
OUT.mkdir(parents=True, exist_ok=True)
if OUT.is_symlink() or not OUT.resolve().is_relative_to(ROOT):
    raise SystemExit('Unsafe artifact destination.')
entries = [('theme', ROOT / 'packages/theme/wooshop', 'wooshop', 'style.css'), ('plugin', ROOT / 'packages/plugin/wooshop-commerce', 'wooshop-commerce', 'wooshop-commerce.php')]
manifest = {'schema_version': 1, 'delivery_status': 'development implementation incomplete;not a verified release candidate', 'packages': []}
for kind, source, slug, header in entries:
    version_match = re.search(r'Version:\s*([0-9.]+)', (source / header).read_text())
    if not version_match:
        raise SystemExit('Missing package version: ' + slug)
    version = version_match.group(1)
    output = OUT / (slug + '-' + kind + '-' + version + '.zip')
    file_records = []
    files = sorted(path for path in source.rglob('*') if path.is_file())
    for path in files:
        if path.is_symlink() or not path.resolve().is_relative_to(source.resolve()):
            raise SystemExit('Unsafe package path.')
        if any(part in ['node_modules', 'vendor', '.local', '.git', 'tests'] for part in path.relative_to(source).parts) or path.name.startswith('.env') or path.name == 'credentials.json':
            raise SystemExit('Development/private file in package: ' + str(path.relative_to(source)))
        file_records.append({'path': str(path.relative_to(source)), 'sha256': hashlib.sha256(path.read_bytes()).hexdigest()})
    with zipfile.ZipFile(output, 'w', compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for path in files:
            info = zipfile.ZipInfo(slug + '/' + str(path.relative_to(source)), date_time=(2026, 10, 28, 0, 0, 0))
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = 0o100644 << 16
            archive.writestr(info, path.read_bytes())
    with zipfile.ZipFile(output) as archive:
        if archive.testzip() is not None or slug + '/' + header not in archive.namelist() or slug + '/LICENSE' not in archive.namelist():
            raise SystemExit('Invalid installable package.')
    manifest['packages'].append({'kind': kind, 'slug': slug, 'version': version, 'artifact': str(output.relative_to(ROOT)), 'sha256': hashlib.sha256(output.read_bytes()).hexdigest(), 'file_count': len(files), 'source_files': file_records, 'runtime_dependencies_bundled': False})
(OUT / 'manifest.json').write_text(json.dumps(manifest, indent=2) + '\n')
print(json.dumps({'packages': [{key: value for key, value in package.items() if key != 'source_files'} for package in manifest['packages']], 'status': manifest['delivery_status']}, indent=2))
