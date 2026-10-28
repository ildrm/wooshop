#!/usr/bin/env python3
"""Read-only HTTP crawl of importer/theme-owned public navigation on local fixtures."""
from concurrent.futures import ThreadPoolExecutor
from html.parser import HTMLParser
import json
import sys
from urllib.parse import urljoin, urlsplit, parse_qs
from urllib.request import Request, build_opener, HTTPRedirectHandler

PORTS = {'outdoor': 18880, 'smart-home': 18881}
site = sys.argv[1] if len(sys.argv) > 1 else ''
if site not in PORTS:
    raise SystemExit('Choose the isolated outdoor or smart-home fixture site.')
base = f'http://localhost:{PORTS[site]}/'
origin = urlsplit(base).netloc

class LocalRedirect(HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        if urlsplit(newurl).netloc != origin:
            raise RuntimeError('Refusing to follow a navigation redirect outside the local fixture origin.')
        return super().redirect_request(req, fp, code, msg, headers, newurl)

class OwnedNavigation(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.stack = []
        self.links = set()
        self.utility_labels = {}
        self.hero = []

    def handle_starttag(self, tag, attributes):
        attrs = dict(attributes)
        classes = set(attrs.get('class', '').split())
        ancestors = [entry[1] for entry in self.stack]
        is_hero = 'wooshop-hero' in classes or any('wooshop-hero' in item for item in ancestors)
        is_owned = tag in ('header', 'footer') or any(entry[0] in ('header', 'footer') for entry in self.stack) or is_hero or 'wooshop-category-shortcuts' in classes or any('wooshop-category-shortcuts' in item for item in ancestors)
        if tag == 'a' and attrs.get('href') and is_owned:
            url = urljoin(base, attrs['href'])
            parsed = urlsplit(url)
            query = parse_qs(parsed.query)
            if parsed.netloc == origin and parsed.scheme == 'http' and not any(key in query for key in ['add-to-cart', 'remove_item', 'customer-logout', '_wpnonce', 'action']):
                self.links.add(url.split('#', 1)[0])
            if is_hero and 'wp-block-button__link' in classes:
                self.hero.append(url)
        if tag == 'a':
            for name in ('wooshop-account', 'wooshop-cart-link'):
                if name in classes:
                    self.utility_labels[name] = attrs.get('aria-label', '')
        if tag not in {'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'}:
            self.stack.append((tag, classes))

    def handle_endtag(self, tag):
        for index in range(len(self.stack)-1, -1, -1):
            if self.stack[index][0] == tag:
                del self.stack[index:]
                break


def fetch(url):
    opener = build_opener(LocalRedirect())
    with opener.open(Request(url, headers={'User-Agent': 'Wooshop-local-navigation-test/0.1'}), timeout=20) as response:
        body = response.read(2 * 1024 * 1024 + 1)
        if len(body) > 2 * 1024 * 1024:
            raise RuntimeError('Bounded local navigation response exceeded2MiB.')
        return response.status, body.decode('utf-8', errors='replace'), response.url

status, html, url = fetch(base)
assert status == 200
navigation = OwnedNavigation()
navigation.feed(html)
assert navigation.hero, 'No owned hero CTA was found.'
assert all(navigation.utility_labels.get(key) for key in ('wooshop-account', 'wooshop-cart-link')), 'Account/cart accessible labels are missing from real rendered HTML.'
assert 0 < len(navigation.links) <= 40, 'Unexpected navigation scope; crawl is intentionally bounded.'

def inspect(target):
    try:
        status, _, final = fetch(target)
        return {'url': target, 'status': status, 'final_url': final}
    except Exception as error:
        return {'url': target, 'error': type(error).__name__ + ': ' + str(error)}

with ThreadPoolExecutor(max_workers=4) as workers:
    results = list(workers.map(inspect, sorted(navigation.links)))
failures = [result for result in results if result.get('status') != 200]
print(json.dumps({'status': 'PASS' if not failures else 'FAIL', 'site': site, 'checked': len(results), 'hero_cta': navigation.hero, 'utility_labels': navigation.utility_labels, 'links': results, 'limitations': 'HTTP public navigation checks only; browser accessibility/reflow/interaction evidence is separate.'}, ensure_ascii=False))
if failures:
    raise SystemExit(1)
