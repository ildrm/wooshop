#!/usr/bin/env python3
"""Project-scoped, local-only WordPress development commands. Never deploys."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import secrets
import shutil
import subprocess
import sys
import time

ROOT = Path(__file__).resolve().parents[1]
LOCAL = ROOT / '.local'
SITES = {'outdoor': 18880, 'smart-home': 18881}
ALL_SITES = {**SITES, 'package-check': 18882}


def prepare():
    for relative in ['cache', 'db-init', 'database', 'sites/outdoor', 'sites/smart-home', 'sites/package-check']:
        destination = LOCAL / relative
        if destination.is_symlink() or not destination.resolve().is_relative_to(ROOT):
            raise SystemExit('Unsafe development path: ' + str(destination))
        destination.mkdir(parents=True, exist_ok=True)
    credential_path = LOCAL / 'credentials.json'
    fresh_credentials = not credential_path.exists()
    if fresh_credentials:
        credentials = {key: secrets.token_hex(24) for key in ['root', 'database', 'admin']}
        credential_path.write_text(json.dumps(credentials))
        credential_path.chmod(0o600)
    credentials = json.loads(credential_path.read_text())
    for name in ['outdoor_database', 'smart_database', 'package_database']:
        credentials.setdefault(name, secrets.token_hex(24))
    for name in ['outdoor_admin', 'smart_admin', 'package_admin']:
        credentials.setdefault(name, secrets.token_hex(24) if fresh_credentials else credentials['admin'])
    credentials['admin'] = credentials['outdoor_admin']  # Backward-compatible Outdoor test alias.
    credential_path.write_text(json.dumps(credentials))
    credential_path.chmod(0o600)
    env_path = LOCAL / 'compose.env'
    env_path.write_text('WOOSHOP_DB_ROOT_PASSWORD=' + credentials['root'] + '\nWOOSHOP_OUTDOOR_DB_PASSWORD=' + credentials['outdoor_database'] + '\nWOOSHOP_SMART_DB_PASSWORD=' + credentials['smart_database'] + '\nWOOSHOP_PACKAGE_DB_PASSWORD=' + credentials['package_database'] + '\n')
    env_path.chmod(0o600)
    init = LOCAL / 'db-init/01-independent-sites.sql'
    init.write_text('CREATE DATABASE IF NOT EXISTS wooshop_outdoor;\nCREATE DATABASE IF NOT EXISTS wooshop_smart_home;\n')
    init.chmod(0o644)
    phar = LOCAL / 'cache/wp-cli.phar'
    if not phar.exists():
        installed = shutil.which('wp')
        if not installed or Path(installed).resolve().stat().st_size < 1000000:
            raise SystemExit('An existing official WP-CLI PHAR is required; no remote shell installer is used.')
        shutil.copyfile(Path(installed).resolve(), phar)
    return credentials


def compose(args, capture=False, check=True, input_text=None):
    command = ['docker', 'compose', '--env-file', str(LOCAL / 'compose.env'), '-f', str(ROOT / 'compose.yaml'), *args]
    result = subprocess.run(command, cwd=ROOT, check=False, text=True, capture_output=capture, input=input_text)
    if check and result.returncode:
        # Never include argv: WP-CLI installation arguments can carry local credentials.
        raise SystemExit('Local container command failed (exit ' + str(result.returncode) + '). Review the redacted service output; command arguments omitted.')
    return result


def wp(site, arguments, capture=False, check=True):
    if site not in ALL_SITES:
        raise SystemExit('Unknown isolated site.')
    return compose(['exec', '-T', site, 'php', '/workspace/.local/cache/wp-cli.phar', '--allow-root', '--path=/var/www/html', *arguments], capture, check)


def admin_key(site):
    return {'outdoor': 'outdoor_admin', 'smart-home': 'smart_admin', 'package-check': 'package_admin'}[site]


def up():
    credentials = prepare()
    compose(['up', '-d', '--wait', '--wait-timeout', '150'])
    sql = ''
    for database, user, key in [('wooshop_outdoor', 'wooshop_outdoor', 'outdoor_database'), ('wooshop_smart_home', 'wooshop_smart', 'smart_database')]:
        sql += "CREATE DATABASE IF NOT EXISTS " + database + ";CREATE USER IF NOT EXISTS '" + user + "'@'%' IDENTIFIED BY '" + credentials[key] + "';GRANT ALL ON " + database + ".* TO '" + user + "'@'%';\n"
    compose(['exec', '-T', 'db', 'sh', '-c', 'exec mariadb --user=root --password="$MARIADB_ROOT_PASSWORD"'], capture=True, input_text=sql)
    for site, port in SITES.items():
        ready = False
        for _ in range(60):
            if (LOCAL / 'sites' / site / 'wp-includes/version.php').is_file() and (LOCAL / 'sites' / site / 'wp-config.php').is_file():
                ready = True
                break
            time.sleep(1)
        if not ready:
            raise SystemExit('WordPress entrypoint did not finish initializing ' + site + '; no installation attempted.')
        # A separate database and credential scope exists for each independent installation.
        installed = wp(site, ['core', 'is-installed'], capture=True, check=False)
        if installed.returncode:
            wp(site, ['core', 'install', '--url=http://localhost:' + str(port), '--title=Wooshop synthetic ' + site,
                      '--admin_user=fixture-admin', '--admin_password=' + credentials[admin_key(site)],
                      '--admin_email=admin@example.invalid', '--skip-email'])
        wp(site, ['rewrite', 'structure', '/%postname%/', '--hard'])
        wp(site, ['option', 'update', 'blog_public', '0'])
    print('Isolated sites: http://localhost:18880 and http://localhost:18881. Credentials remain only in ignored .local/credentials.json.')


def install_woocommerce(site):
    archive = LOCAL / 'cache/woocommerce.11.2.1.zip'
    if not archive.is_file() or archive.is_symlink():
        raise SystemExit('Download the official pinned WooCommerce archive into .local/cache first; see command reference.')
    if hashlib.sha256(archive.read_bytes()).hexdigest() != '5317434cb381b5fdc7c1dd6ee258324f08c390976aaf8d1199f2a0e9a3ab8586':
        raise SystemExit('WooCommerce input integrity verification failed; no installation attempted.')
    installed = wp(site, ['plugin', 'get', 'woocommerce', '--field=version'], capture=True, check=False)
    if installed.returncode == 0:
        if installed.stdout.strip() != '11.2.1':
            raise SystemExit('Existing WooCommerce version differs from this reviewed local matrix; no replacement attempted.')
        wp(site, ['plugin', 'activate', 'woocommerce'])
    else:
        wp(site, ['plugin', 'install', '/workspace/.local/cache/' + archive.name, '--activate'])


def install():
    for site in SITES:
        install_woocommerce(site)
        if (ROOT / 'packages/plugin/wooshop-commerce/wooshop-commerce.php').exists():
            wp(site, ['plugin', 'activate', 'wooshop-commerce'])
        if (ROOT / 'packages/theme/wooshop/style.css').exists():
            wp(site, ['theme', 'activate', 'wooshop'])
        # These ports bind loopback only. Exercise anonymous native purchase screens.
        wp(site, ['option', 'update', 'woocommerce_coming_soon', 'no'])


def package_site():
    credentials = prepare()
    sql = "CREATE DATABASE IF NOT EXISTS wooshop_package_check;CREATE USER IF NOT EXISTS 'wooshop_package'@'%' IDENTIFIED BY '" + credentials['package_database'] + "';GRANT ALL ON wooshop_package_check.* TO 'wooshop_package'@'%';"
    compose(['exec', '-T', 'db', 'sh', '-c', 'exec mariadb --user=root --password="$MARIADB_ROOT_PASSWORD"'], capture=True, input_text=sql)
    compose(['up', '-d', '--wait', '--wait-timeout', '150', 'package-check'])
    for _ in range(60):
        if (LOCAL / 'sites/package-check/wp-config.php').is_file() and (LOCAL / 'sites/package-check/wp-includes/version.php').is_file():
            break
        time.sleep(1)
    else:
        raise SystemExit('Package-check WordPress initialization incomplete.')
    if wp('package-check', ['core', 'is-installed'], capture=True, check=False).returncode:
        wp('package-check', ['core', 'install', '--url=http://localhost:18882', '--title=Synthetic clean-package verification', '--admin_user=fixture-admin', '--admin_password=' + credentials[admin_key('package-check')], '--admin_email=admin@example.invalid', '--skip-email'])
    install_woocommerce('package-check')
    manifest = json.loads((ROOT / 'artifacts/releases/manifest.json').read_text())
    for package in manifest['packages']:
        artifact = '/workspace/releases/' + Path(package['artifact']).name
        wp('package-check', [package['kind'], 'install', artifact, '--activate', '--force'])
    wp('package-check', ['option', 'update', 'blog_public', '0'])
    wp('package-check', ['option', 'update', 'woocommerce_coming_soon', 'no'])
    print('Clean-package installation complete at localhost:18882. No theme/plugin source bind mounts are used.')


def rotate_admin():
    credentials = prepare()
    rotated = []
    # Deliver the replacement through stdin, never command arguments or output.
    for site in ALL_SITES:
        if wp(site, ['core', 'is-installed'], capture=True, check=False).returncode == 0:
            replacement = secrets.token_hex(24)
            code = '$u = get_user_by("login", "fixture-admin"); if (!$u) { throw new Exception("Fixture administrator missing"); } wp_set_password(trim(stream_get_contents(STDIN)), $u->ID);'
            compose(['exec', '-T', site, 'php', '/workspace/.local/cache/wp-cli.phar', '--allow-root', '--path=/var/www/html', 'eval', code], capture=True, input_text=replacement)
            credentials[admin_key(site)] = replacement
            credentials['admin'] = credentials['outdoor_admin']  # Legacy test-runner alias, only Outdoor.
            destination = LOCAL / 'credentials.json'
            destination.write_text(json.dumps(credentials))
            destination.chmod(0o600)
            rotated.append(site)
    if not rotated:
        raise SystemExit('No running installed local site was verified; no administrator credential was rotated.')
    print('Distinct fixture administrator passwords rotated for ' + ', '.join(rotated) + '; old sessions invalidated. No credential values printed.')


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('command', choices=['prepare', 'up', 'install', 'package-site', 'rotate-admin', 'stop', 'status', 'wp'])
    parser.add_argument('arguments', nargs=argparse.REMAINDER)
    args = parser.parse_args()
    if args.command == 'prepare':
        prepare()
        print('Local paths and credentials prepared; no credentials printed.')
    elif args.command == 'up':
        up()
    elif args.command == 'install':
        prepare()
        install()
    elif args.command == 'package-site':
        package_site()
    elif args.command == 'rotate-admin':
        rotate_admin()
    elif args.command == 'stop':
        compose(['stop'])
    elif args.command == 'status':
        compose(['ps'])
    else:
        if len(args.arguments) < 2:
            raise SystemExit('Usage: tools/dev.py wp outdoor|smart-home <WP-CLI arguments>')
        wp(args.arguments[0], args.arguments[1:])


if __name__ == '__main__':
    main()
