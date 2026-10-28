/* The optional-message token travels in a URL fragment, never a query or referrer. GET only displays this confirmation. */
(() => {
    'use strict';
    const form = document.querySelector('[data-commerce-unsubscribe]');
    if (form && location.hash) {
        const input = form.querySelector('input[name="token"]');
        const token = location.hash.slice(1);
        if (input && /^[a-f0-9-]{36}\.[a-f0-9]{64}\.[a-f0-9]{64}$/.test(token)) input.value = token;
        history.replaceState(history.state, '', location.pathname + location.search);
    }
    document.querySelectorAll('[data-commerce-history]').forEach(rail => {
        const key = 'wooshop.history.' + rail.dataset.key;
        const list = rail.querySelector('[data-history-items]');
        const empty = rail.querySelector('[data-history-empty]');
        const status = rail.querySelector('[data-history-status]');
        const consentVersion = Number(rail.dataset.consentVersion);
        const moduleVersion = Number(rail.dataset.moduleVersion);
        const read = () => {
            try {
                const raw = localStorage.getItem(key) || '{"deletion_revision":1,"items":[]}';
                if (raw.length > 65536) throw new Error('Oversized local history');
                const value = JSON.parse(raw);
                if (!Number.isSafeInteger(value.deletion_revision) || value.deletion_revision < 1 || !Array.isArray(value.items)) throw new Error('Invalid local history');
                const consent = value.consent_version === undefined ? 0 : value.consent_version;
                const module = value.module_version === undefined ? 0 : value.module_version;
                if (!Number.isSafeInteger(consent) || consent < 0 || !Number.isSafeInteger(module) || module < 0 || (value.disabled !== undefined && typeof value.disabled !== 'boolean') || (value.module_disabled !== undefined && typeof value.module_disabled !== 'boolean')) throw new Error('Invalid local consent');
                return {...value, consent_version: consent, module_version: module, module_disabled: value.module_disabled === true, disabled: value.disabled === true};
            } catch (_) { return {deletion_revision: 1, consent_version: 0, module_version: 0, module_disabled: false, disabled: false, items: []}; }
        };
        const safeItems = state => state.items.slice(0, 50).filter(item => {
            if (!item || typeof item.name !== 'string' || item.name.length > 500 || !/^[1-9][0-9]*$/.test(item.product_id) || !Number.isSafeInteger(item.viewed_at) || item.viewed_at < Date.now() - Number(rail.dataset.days) * 86400000) return false;
            try { return new URL(item.url, location.href).origin === location.origin; } catch (_) { return false; }
        }).slice(0, Number(rail.dataset.limit));
        const render = state => {
            list.replaceChildren();
            const items = state.disabled || state.module_disabled || state.consent_version > consentVersion || state.module_version > moduleVersion ? [] : safeItems(state);
            items.forEach(item => {
                const li = document.createElement('li');
                const anchor = document.createElement('a');
                anchor.href = new URL(item.url, location.href).href;
                anchor.textContent = item.name;
                li.append(anchor); list.append(li);
            });
            empty.hidden = items.length > 0;
        };
        try {
            if (!Number.isSafeInteger(consentVersion) || consentVersion < 1 || !Number.isSafeInteger(moduleVersion) || moduleVersion < 1) { rail.hidden = true; return; }
            let state = read();
            if (state.consent_version > consentVersion || state.module_version > moduleVersion) { rail.hidden = true; return; }
            if (rail.dataset.moduleReadEnabled !== '1') {
                if (!state.module_disabled || state.module_version < moduleVersion || (rail.dataset.enabled !== '1' && !state.disabled)) {
                    localStorage.setItem(key, JSON.stringify({deletion_revision: state.deletion_revision + 1, consent_version: consentVersion, module_version: moduleVersion, module_disabled: true, disabled: rail.dataset.enabled !== '1' || (state.consent_version === consentVersion && state.disabled), items: []}));
                }
                rail.hidden = true; return;
            }
            if (rail.dataset.enabled !== '1') {
                // Keep a withdrawal tombstone so an older page cannot resurrect deleted views.
                if (!state.disabled || state.consent_version < consentVersion) {
                    localStorage.setItem(key, JSON.stringify({deletion_revision: state.deletion_revision + 1, consent_version: consentVersion, module_version: moduleVersion, module_disabled: false, disabled: true, items: []}));
                }
                return;
            }
            if (state.consent_version < consentVersion || state.module_version < moduleVersion) {
                state = {...state, consent_version: consentVersion, module_version: moduleVersion, module_disabled: false, disabled: state.consent_version < consentVersion ? false : state.disabled};
                localStorage.setItem(key, JSON.stringify(state));
            }
            if (state.disabled || state.module_disabled) { rail.hidden = true; return; }
            if (rail.dataset.product && rail.dataset.recordEnabled === '1') {
                const product = JSON.parse(rail.dataset.product);
                const current = read();
                if (!current.disabled && !current.module_disabled && current.consent_version === consentVersion && current.module_version === moduleVersion && current.deletion_revision === state.deletion_revision) {
                    current.items = safeItems(current).filter(item => item.product_id !== product.product_id);
                    current.items.unshift({...product, viewed_at: Date.now()});
                    current.items = current.items.slice(0, Number(rail.dataset.limit));
                    localStorage.setItem(key, JSON.stringify(current));
                }
            }
            render(read());
            rail.querySelector('[data-history-clear]').addEventListener('click', () => {
                const current = read();
                if (current.disabled || current.module_disabled || current.consent_version > consentVersion || current.module_version > moduleVersion) { rail.hidden = true; return; }
                const cleared = {...current, deletion_revision: current.deletion_revision + 1, items: []};
                localStorage.setItem(key, JSON.stringify(cleared));
                render(cleared); status.textContent = rail.dataset.clearedMessage;
            });
            window.addEventListener('storage', event => { if (event.key === key) render(read()); });
        } catch (_) { rail.hidden = true; }
    });
})();
