#!/usr/bin/env python3
"""Bounded installed-package independence assurance, localhost:18882 only."""
import argparse
import datetime
import hashlib
import json
from pathlib import Path
import subprocess
import sys
import uuid

ROOT = Path(__file__).resolve().parents[1]
EVIDENCE = ROOT / 'docs/implementation/independence-evidence.json'
PREFIX = 'WOOSHOP_INDEPENDENCE='
SITE = 'package-check'

# Actual native browser operations; no login, checkout, screenshots, trace or storage-state export.
BROWSER = r'''
const {chromium,expect}=require('@playwright/test');
let step='launch';
(async()=>{
 const fixture=JSON.parse(process.argv[1]); const phase=process.argv[2];
 const browser=await chromium.launch({channel:'chrome',headless:true});
 const context=await browser.newContext({baseURL:'http://localhost:18882'});
 const page=await context.newPage(); const errors=[];
 page.on('pageerror',()=>errors.push('browser script error'));
 let checks=0;
 const visit=async url=>{if(new URL(url,'http://localhost:18882').origin!=='http://localhost:18882')throw Error('Origin refused'); const response=await page.goto(url,{waitUntil:'networkidle'}); expect(response.status()).toBe(200);checks++;};
 try {
  if(phase==='plugin'){
   await visit(fixture.probe_page_url);
   await expect(page.getByRole('region',{name:'Wishlist',exact:true})).toBeVisible();checks++;
   await page.getByLabel('Product ID',{exact:true}).fill(String(fixture.probe_product_id));
   await page.getByRole('button',{name:'Save product',exact:true}).click();
   await expect(page.getByRole('status').filter({hasText:'Your change was saved.'})).toBeVisible();checks++;
   await expect(page.getByRole('link',{name:fixture.probe_product_name,exact:true})).toBeVisible();checks++;
   await page.reload({waitUntil:'networkidle'});
   await expect(page.getByRole('link',{name:fixture.probe_product_name,exact:true})).toBeVisible();checks++;
   await expect(page.getByRole('button',{name:'Remove',exact:true})).toBeVisible();checks++;
  }else if(phase==='theme'){
   step='theme home';
   await visit('/');await expect(page.getByRole('searchbox')).toBeVisible();checks++;
   step='theme shop';
   await visit(fixture.shop_url);await expect(page.getByRole('main')).toHaveCount(1);checks++;
   await expect(page.getByRole('main')).not.toContainText(fixture.probe_product_name);checks++;
   step='theme product';
   await visit(fixture.native_product_url);
   await expect(page.getByRole('heading',{level:1,name:fixture.native_product_name,exact:true})).toBeVisible();checks++;
   step='native add to cart';
   await page.locator('button.single_add_to_cart_button').click();
   await expect(page.getByRole('alert').filter({hasText:/added to your cart/i})).toContainText(fixture.native_product_name);checks++;
   step='native cart';
   await visit(fixture.cart_url);
   await expect(page.getByRole('main')).toContainText(fixture.native_product_name);checks++;
   const cart=await(await page.request.get(fixture.cart_api_url)).json();
   expect(cart.items.some(item=>item.id===fixture.native_product_id&&item.quantity===1)).toBe(true);checks++;
   step='native account';
   await visit(fixture.account_url);await expect(page.locator('form.woocommerce-form-login')).toBeVisible();checks++;
  }else if(phase==='wordpress'){
   await visit('/');await expect(page.getByRole('searchbox')).toBeVisible();checks++;
   await expect(page.locator('form[role="search"] input[name="post_type"]')).toHaveCount(0);checks++;
   await visit(fixture.help_url);await expect(page.getByRole('main')).toContainText('Synthetic local environment');checks++;
   await visit('/?s=synthetic');await expect(page.getByRole('main')).toHaveCount(1);checks++;
  }else throw Error('Unknown phase');
  expect(errors).toEqual([]);checks++;
  console.log('WOOSHOP_INDEPENDENCE_BROWSER='+JSON.stringify({result:'PASS',phase,checks,privacy:'No screenshots, trace, credentials, cookies or order keys exported.'}));
 }finally{await context.close();await browser.close();}
})().catch(()=>{console.log('WOOSHOP_INDEPENDENCE_BROWSER='+JSON.stringify({result:'FAIL',step,privacy:'Private browser state omitted.'}));process.exit(1);});
'''


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--run', action='store_true', help='Run only after root confirms fresh ZIP installation and package-check readiness.')
    parser.add_argument('--package-manifest-sha256', help='Explicitly bind the run to the freshly installed archive manifest.')
    args = parser.parse_args()
    report = {'schema_version': 1, 'result': 'PREPARING', 'human_approval': 'NOT_PROVIDED',
              'target': 'http://localhost:18882', 'commands': [], 'matrix': [], 'restoration': [],
              'limits': ['Bounded normal supported activation matrix, not forced dependency loss or critical obligation handoff proof.',
                         'No order/payment/provider/messaging/fulfillment work; browser guest cart and wishlist probes only.',
                         'Accessible native controls/landmarks tested; not a complete WCAG or assistive-technology audit.',
                         'Tagged customer/domain records and draft/hidden native probes are retained; no destructive cleanup.',
                         'Watched existing page/style content, native assignments/profile/currency, 13 commerce-option digests and typed settings revision are compared; native caches/cron timestamps are not frozen.'],
              'private_guest_data_scope': 'Dedicated WP-CLI guest wishlist/customer-command and a separate real-browser guest wishlist remain private server records under native plugin expiry. Browser context closes without exporting cookies; cleanup removes the run option recovery cookie. No user accounts, passwords or order data are created by this matrix.'}

    def command(arguments, label, prefix=PREFIX, timeout=90):
        process = subprocess.run(['rtk', 'proxy', *arguments], cwd=ROOT, capture_output=True, text=True, timeout=timeout)
        recorded = ['rtk', 'proxy', *arguments]
        if arguments[:2] == ['node', '-e']:
            recorded = ['rtk', 'proxy', 'node', '-e', '<BROWSER constant in tools/independence.py>', '<public synthetic fixture JSON>', arguments[-1]]
        report['commands'].append({'action': label, 'argv': recorded, 'exit_code': process.returncode})
        line = next((line for line in process.stdout.splitlines() if line.startswith(prefix)), None) if prefix else None
        if process.returncode != 0 or (prefix and not line):
            safe = json.loads(line[len(prefix):]) if line and prefix == 'WOOSHOP_INDEPENDENCE_BROWSER=' else {}
            raise RuntimeError(label + ' failed' + (' at ' + safe['step'] if 'step' in safe else '') + '; arbitrary subprocess output/private state omitted.')
        return json.loads(line[len(prefix):]) if prefix else None

    def wp(arguments, label, structured=False):
        return command([sys.executable, 'tools/dev.py', 'wp', SITE, *arguments], label, PREFIX if structured else None)

    def probe(operation, run=''):
        return wp(['eval-file', '/workspace/tests/core/independence.php', operation, *([run] if run else [])], 'probe:' + operation, True)

    def browser(phase, fixture):
        return command(['node', '-e', BROWSER, json.dumps(fixture), phase], 'browser:' + phase, 'WOOSHOP_INDEPENDENCE_BROWSER=', 90)

    def installed_manifest(inspected):
        manifest_path = ROOT / 'artifacts/releases/manifest.json'
        digest = hashlib.sha256(manifest_path.read_bytes()).hexdigest()
        if args.run and args.package_manifest_sha256 != digest:
            raise RuntimeError('Run requires the exact freshly installed manifest SHA256.')
        manifest = json.loads(manifest_path.read_text())
        installed = inspected.pop('runtime_files')
        matches = {}
        for package in manifest['packages']:
            expected = {file['path']: file['sha256'] for file in package['source_files']}
            if installed[package['kind']] != expected:
                raise RuntimeError('Installed package tree differs from archive manifest: ' + package['kind'])
            matches[package['kind']] = {'file_count': len(expected), 'archive_sha256': package['sha256'], 'installed_files_equal_manifest': True}
        report['package_manifest_sha256'] = digest
        report['installed_packages'] = matches

    def save():
        report['updated_at'] = datetime.datetime.now(datetime.timezone.utc).isoformat()
        report['read_paths'] = []
        paths = ['tools/seed.php', 'profiles/neutral.json', 'tools/dev.py', 'tools/dev-guard.php',
                 'tests/core/package-smoke.php', 'packages/plugin/wooshop-commerce/src/Customer/CustomerService.php',
                 'packages/plugin/wooshop-commerce/src/Customer/Session.php', 'packages/plugin/wooshop-commerce/src/Customer/Records.php',
                 'packages/plugin/wooshop-commerce/src/Catalog/CatalogModule.php', 'packages/plugin/wooshop-commerce/src/Catalog/Products.php',
                 'packages/plugin/wooshop-commerce/src/Catalog/Views.php', 'packages/plugin/wooshop-commerce/src/Infrastructure/EntityStore.php',
                 'packages/plugin/wooshop-commerce/wooshop-commerce.php', 'packages/theme/wooshop/inc/presentation.php',
                 '.local/sites/package-check/wp-content/themes/twentytwentyfour/functions.php', '.local/sites/package-check/wp-content/themes/twentytwentyfour/style.css']
        scopes = {'packages/plugin/wooshop-commerce/src/Customer/CustomerService.php': 'lines1-92 only; wishlist methods',
                  'packages/plugin/wooshop-commerce/src/Catalog/Views.php': 'lines1-150 only; native shortcode/forms',
                  'tools/dev.py': 'lines1-170; scoped local CLI and package site',
                  'tools/seed.php': 'lines1-55 plus prior theme task implementation familiarity; actual neutral allowlist',
                  'packages/theme/wooshop/inc/presentation.php': 'lines1-140 plus prior theme implementation familiarity',
                  '.local/sites/package-check/wp-content/themes/twentytwentyfour/functions.php': 'complete195lines inspected in bounded reads',
                  '.local/sites/package-check/wp-content/themes/twentytwentyfour/style.css': 'header lines1-38'}
        for name in paths:
            path = ROOT / name
            if path.is_file(): report['read_paths'].append({'path': name, 'sha256': hashlib.sha256(path.read_bytes()).hexdigest(), 'scope': scopes.get(name, 'full file')})
        report['changed_files'] = [{'path': name, 'sha256': hashlib.sha256((ROOT/name).read_bytes()).hexdigest()} for name in ['tests/core/independence.php', 'tools/independence.py']]
        report['graph_verification'] = {'project': 'Users-shahinilderemi-Documents-tmp-iranbroker-wp', 'generation': '2026-10-28T10:32:29Z',
            'tier': 'Verify', 'findings': 'CustomerService,Session,CatalogModule,EntityStore positively found by name; bounded first15/25mixed results only, no completeness claim.',
            'coverage': 'Exact service paths no recorded issues; installed default themes/vendor/runtime .local excluded and read directly. New assurance files authored/read directly.'}
        EVIDENCE.write_text(json.dumps(report, indent=2) + '\n')

    fixture = None
    original = None
    setup_started = False
    run = 'independence-' + str(uuid.uuid4())
    try:
        inspected = probe('inspect')
        installed_manifest(inspected)
        report['inspection'] = inspected
        if not args.run:
            report['result'] = 'READ_ONLY_INSPECTION_PASS'
            return
        candidate = inspected['state']
        if candidate['theme'] != 'wooshop' or not candidate['woocommerce'] or not candidate['companion']:
            raise RuntimeError('Expected normal active packages at matrix start.')
        original = candidate
        if inspected['profile'] not in [None, 'neutral']:
            raise RuntimeError('Foreign third-site profile must not be overwritten.')
        if inspected['profile'] is None:
            wp(['eval-file', '/workspace/tools/seed.php', 'neutral', 'configure-profile'], 'additive neutral synthetic fixture initialization')
            report['neutral_seed'] = 'Created once using existing guarded additive importer; retained as intended third-profile fixture.'
        setup_started = True
        fixture = probe('setup', run)
        wp(['theme', 'activate', 'twentytwentyfour'], 'normal unrelated theme activation')
        report['matrix'].append({'phase': 'companion under unrelated native theme', 'server': probe('plugin', run), 'browser': browser('plugin', fixture)})
        wp(['theme', 'activate', 'wooshop'], 'normal custom theme activation')
        wp(['plugin', 'deactivate', 'wooshop-commerce'], 'normal companion deactivation')
        report['matrix'].append({'phase': 'custom theme plus WooCommerce, companion inactive', 'server': probe('theme', run), 'browser': browser('theme', fixture)})
        wp(['plugin', 'deactivate', 'woocommerce'], 'normal WooCommerce deactivation after dependent companion')
        report['matrix'].append({'phase': 'custom theme plus native WordPress, both plugins inactive', 'server': probe('wordpress', run), 'browser': browser('wordpress', fixture)})
        report['result'] = 'PASS'
    except Exception as failure:
        report['result'] = 'FAIL'
        report['error'] = str(failure)
    finally:
        if args.run and original:
            # Always attempt all supported restoration steps, even after a failed assertion.
            for arguments, label in [(['plugin', 'activate', 'woocommerce'], 'restore WooCommerce first'),
                                     (['plugin', 'activate', 'wooshop-commerce'], 'restore companion second'),
                                     (['theme', 'activate', original['theme']], 'restore original theme')]:
                try:
                    wp(arguments, label)
                    report['restoration'].append({'action': label, 'result': 'PASS'})
                except Exception as failure:
                    report['restoration'].append({'action': label, 'result': 'FAIL', 'error': str(failure)})
                    report['result'] = 'FAIL'
            if setup_started:
                for operation in (['restore-options', 'restored', 'cleanup'] if fixture else ['restore-options', 'cleanup']):
                    try: report['restoration'].append(probe(operation, run))
                    except Exception as failure:
                        report['restoration'].append({'operation': operation, 'result': 'FAIL', 'error': str(failure)})
                        report['result'] = 'FAIL'
        save()
        print(json.dumps({'result': report['result'], 'evidence': str(EVIDENCE.relative_to(ROOT)),
                          'matrix_phases': len(report['matrix']), 'restoration_checks': len(report['restoration'])}))
    if report['result'] == 'FAIL':
        raise SystemExit(1)


if __name__ == '__main__':
    main()
