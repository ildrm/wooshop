'use strict';
// Exercise the actual shipped browser code with a minimal native DOM/storage boundary.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('packages/plugin/wooshop-commerce/assets/catalog.js', 'utf8');
const storage = new Map();
const product = {product_id: '17', name: '<Synthetic product>', url: 'http://localhost:18881/product/synthetic/'};
let checks = 0;
function same(actual, expected) { checks++; assert.deepEqual(actual, expected); }
function page({enabled = true, record = true, consent = 1, current = product, module = 1, readable = true} = {}) {
    const elements = Object.fromEntries(['items', 'empty', 'status', 'clear'].map(key => [key, {hidden: false, children: [], textContent: '', replaceChildren() { this.children = []; }, append(value) { this.children.push(value); }, addEventListener(type, callback) { this[type] = callback; }}]));
    const rail = {hidden: false, dataset: {key: 'synthetic-owner', enabled: enabled ? '1' : '0', recordEnabled: record ? '1' : '0', consentVersion: String(consent), moduleVersion: String(module), moduleReadEnabled: readable ? '1' : '0', product: current ? JSON.stringify(current) : '', days: '30', limit: '20', clearedMessage: 'Cleared'}, querySelector(selector) { return elements[selector.replace('[data-history-', '').replace(']', '')]; }};
    const handlers = {};
    const context = {URL, Date, Number, JSON, Error, location: {origin: 'http://localhost:18881', href: 'http://localhost:18881/product/synthetic/', hash: ''}, document: {querySelector: () => null, querySelectorAll: () => [rail], createElement: () => ({children: [], append(child) { this.children.push(child); }})}, localStorage: {getItem: key => storage.get(key) || null, setItem: (key, value) => storage.set(key, value)}, window: {addEventListener: (event, callback) => { handlers[event] = callback; }}};
    vm.runInNewContext(source, context);
    return {rail, elements, handlers};
}
const stored = () => JSON.parse(storage.get('wooshop.history.synthetic-owner'));
const active = page();
same(stored().items.length, 1);
same(active.elements.items.children[0].children[0].textContent, '<Synthetic product>');
same(stored().consent_version, 1);
const initial = storage.get('wooshop.history.synthetic-owner');
const draining = page({record: false, current: {...product, product_id: '18'}});
same(storage.get('wooshop.history.synthetic-owner'), initial);
same(draining.elements.items.children.length, 1);
same(typeof draining.elements.clear.click, 'function');
page({enabled: false, consent: 2});
same(stored().items, []);
same(stored().disabled, true);
same(stored().deletion_revision, 2);
const withdrawn = storage.get('wooshop.history.synthetic-owner');
const stale = page({consent: 1});
same(stale.rail.hidden, true);
same(storage.get('wooshop.history.synthetic-owner'), withdrawn);
active.handlers.storage({key: 'wooshop.history.synthetic-owner'});
same(active.elements.items.children.length, 0);
active.elements.clear.click();
same(storage.get('wooshop.history.synthetic-owner'), withdrawn);
const optedIn = page({consent: 3});
same(stored().disabled, false);
same(stored().items.length, 1);
same(stored().deletion_revision, 2);
// A stale withdrawal page cannot erase a later explicit opt-in.
const renewed = storage.get('wooshop.history.synthetic-owner');
page({enabled: false, consent: 2});
same(storage.get('wooshop.history.synthetic-owner'), renewed);
optedIn.elements.clear.click();
same(stored().items, []);
same(stored().deletion_revision, 3);
same(stored().consent_version, 3);
page({consent: 3, current: {...product, product_id: '19', url: 'https://foreign.example.invalid/product'}});
same(page({consent: 3, record: false}).elements.items.children.length, 0);
// Full module disable clears local cache, retains consent, and blocks an older page.
page({consent: 3, module: 2, readable: false, record: false});
same(stored().items, []);
same(stored().module_disabled, true);
same(stored().disabled, false);
const stopped = storage.get('wooshop.history.synthetic-owner');
same(page({consent: 3, module: 1}).rail.hidden, true);
same(storage.get('wooshop.history.synthetic-owner'), stopped);
page({consent: 3, module: 3});
same(stored().module_disabled, false);
same(stored().consent_version, 3);
same(stored().items.length, 1);
same(stored().module_version, 3);
console.log(`Catalog device history checks passed: ${checks}`);
