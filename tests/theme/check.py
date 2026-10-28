#!/usr/bin/env python3
"""Theme package integrity and contrast checks, independent of a WordPress runtime."""
from pathlib import Path
import json
import re
import subprocess

ROOT = Path(__file__).resolve().parents[2]
THEME = ROOT / 'packages/theme/wooshop'
checks = 0

def check(condition, message):
    global checks
    if not condition:
        raise AssertionError(message)
    checks += 1

def luminance(hex_color):
    channels = [int(hex_color[i:i+2], 16) / 255 for i in (1, 3, 5)]
    linear = [v / 12.92 if v <= .04045 else ((v + .055) / 1.055) ** 2.4 for v in channels]
    return .2126 * linear[0] + .7152 * linear[1] + .0722 * linear[2]

def contrast(a, b):
    high, low = sorted([luminance(a), luminance(b)], reverse=True)
    return (high + .05) / (low + .05)

base = json.loads((THEME / 'theme.json').read_text())
check(base['version'] == 3, 'Native theme.json v3 is required')
check((THEME / 'templates/index.html').is_file(), 'Block theme recognition requires templates/index.html')
check('Theme Name: Wooshop' in (THEME / 'style.css').read_text(), 'Installable theme metadata required')
check(len(list((THEME / 'styles').glob('*.json'))) == 3, 'Three independent style variations required')
for path in [THEME / 'theme.json', *sorted((THEME / 'styles').glob('*.json'))]:
    config = json.loads(path.read_text())
    palette = {c['slug']: c['color'] for c in config['settings']['color']['palette']}
    for text, background in [('text', 'surface'), ('secondary', 'surface'), ('action', 'surface'), ('success', 'surface'), ('warning', 'surface'), ('danger', 'surface'), ('info', 'surface')]:
        measured = contrast(palette[text], palette[background])
        check(measured >= 4.5, f'{path.name}: {text}/{background} contrast {measured:.2f} below 4.5')
    check(contrast('#005FCC', palette['surface']) >= 3, 'Protected focus ring needs 3:1 on surface')
    check('fontFace' not in json.dumps(config), 'No undeclared remote or unlicensed font')

# Gutenberg comments must have balanced names, including independently nested same-name blocks.
comment = re.compile(r'<!--\s*(/?)wp:([\w/-]+)(.*?)-->', re.S)
for path in [*sorted((THEME / 'templates').glob('*.html')), *sorted((THEME / 'parts').glob('*.html')), *sorted((THEME / 'patterns').glob('*.php'))]:
    stack = []
    for closing, name, remainder in comment.findall(path.read_text()):
        if closing:
            check(bool(stack) and stack[-1] == name, f'{path}: mismatched close {name} after {stack}')
            stack.pop()
        elif not remainder.rstrip().endswith('/'):
            stack.append(name)
    check(not stack, f'{path}: unclosed blocks {stack}')
    if path.parent.name == 'templates':
        check('"tagName":"main"' in path.read_text(), f'{path}: main landmark required')
        check('"anchor":"main-content"' in path.read_text(), f'{path}: stable skip-link target required')

for path in THEME.rglob('*.php'):
    result = subprocess.run(['php', '-l', str(path)], capture_output=True, text=True)
    check(result.returncode == 0, result.stdout + result.stderr)
for filename in ['store.js', 'editor.js']:
    result = subprocess.run(['node', '--check', str(THEME / 'assets' / filename)], capture_output=True, text=True)
    check(result.returncode == 0, result.stderr)

source = '\n'.join(path.read_text() for path in THEME.rglob('*.php'))
check(not re.search(r'\$wpdb|update_post_meta\(|wc_create_order\(|set_stock_quantity\(|register_rest_route\(', source), 'Persistent commerce mutation or API leaked into theme')
css = (THEME / 'assets/store.css').read_text()
check('prefers-reduced-motion:reduce' in css, 'Reduced motion support required')
check('outline:3px solid #005fcc!important' in css, 'Focus invariant cannot be removed by supported style controls')
check('inset-inline' in css and 'padding-inline' in css, 'Logical layout required')
check('max-width:359px' in css, 'Narrow cards need a linear layout')
seed = ROOT / 'tools/seed.php'
if seed.exists():
    result = subprocess.run(['php', str(seed)], capture_output=True, text=True)
    check(result.returncode != 0 and 'Fixture import refused' in result.stderr, 'Fixture importer must refuse execution outside the local WordPress guard')
for key in ['outdoor', 'smart-home', 'neutral']:
    profile = json.loads((ROOT / 'profiles' / (key + '.json')).read_text())
    check(profile['classification'] == 'SYNTHETIC LOCAL FIXTURE ONLY', 'Profile must be explicitly synthetic')
    check(profile['theme'] == 'wooshop' and profile['plugin'] == 'wooshop-commerce', 'Profiles must use the same released packages')
    check(profile['legal_approval'] is False, 'Fixture profile cannot claim business approval')
    check(not any(name in profile for name in ['users', 'orders', 'api_key', 'password', 'customer_id', 'seller_id']), 'Portable profile must exclude private identities and secrets')
print(json.dumps({'status': 'PASS', 'checks': checks, 'scope': 'package structure, syntax, palette contrast, block delimiters; browser and native runtime separate'}))
