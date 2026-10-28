(function () {
    'use strict';
    document.documentElement.classList.add('wooshop-js');
    const mobile = window.matchMedia('(max-width: 767px)');
    const focusable = 'a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),summary,[tabindex]:not([tabindex="-1"])';
    const messages = window.wooshopMessages || {};
    document.querySelectorAll('.wooshop-categories').forEach(function (details) {
        const summary = details.querySelector(':scope > summary');
        const panel = details.querySelector('.wooshop-category-panel');
        const heading = panel.querySelector('h2');
        let inerted = [];
        let modal = false;
        function release() {
            inerted.forEach(function (element) { element.inert = false; });
            inerted = [];
            panel.removeAttribute('role');
            panel.removeAttribute('aria-modal');
            panel.removeAttribute('aria-labelledby');
            document.documentElement.classList.remove('wooshop-drawer-open');
            modal = false;
        }
        function setModal() {
            if (modal || !mobile.matches || !details.open) return;
            modal = true;
            panel.setAttribute('role', 'dialog');
            panel.setAttribute('aria-modal', 'true');
            panel.setAttribute('aria-labelledby', heading.id);
            let branch = panel;
            while (branch.parentElement && branch.parentElement !== document.documentElement) {
                Array.from(branch.parentElement.children).forEach(function (sibling) {
                    if (sibling !== branch && !sibling.inert && !['SCRIPT', 'STYLE', 'LINK'].includes(sibling.tagName)) {
                        sibling.inert = true;
                        inerted.push(sibling);
                    }
                });
                branch = branch.parentElement;
            }
            document.documentElement.classList.add('wooshop-drawer-open');
            heading.focus();
        }
        function close() {
            const restore = panel.contains(document.activeElement) || modal;
            details.open = false;
            summary.setAttribute('aria-expanded', 'false');
            release();
            if (restore) summary.focus();
        }
        summary.setAttribute('aria-expanded', String(details.open));
        details.addEventListener('toggle', function () {
            summary.setAttribute('aria-expanded', String(details.open));
            if (details.open) setModal();
            else {
                const restore = modal;
                release();
                if (restore) summary.focus();
            }
        });
        panel.querySelector('.wooshop-category-close').addEventListener('click', close);
        details.addEventListener('keydown', function (event) {
            if (!details.open) return;
            if (event.key === 'Escape') { event.preventDefault(); close(); }
            if (event.key === 'Tab' && modal) {
                const targets = Array.from(panel.querySelectorAll(focusable)).filter(function (element) { return element.getClientRects().length && !element.closest('[inert]'); });
                const first = targets[0];
                const last = targets[targets.length - 1];
                if (!first) { event.preventDefault(); heading.focus(); return; }
                if (event.shiftKey && (document.activeElement === first || document.activeElement === heading)) { event.preventDefault(); last.focus(); }
                else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
            }
        });
        document.addEventListener('click', function (event) { if (details.open && !details.contains(event.target)) close(); });
        mobile.addEventListener('change', function () {
            if (!details.open) return;
            if (mobile.matches) setModal();
            else { release(); summary.focus(); }
        });
    });
    document.querySelectorAll('.wooshop-subcategory').forEach(function (details) {
        const summary = details.querySelector('summary');
        summary.setAttribute('aria-expanded', String(details.open));
        details.addEventListener('toggle', function () { summary.setAttribute('aria-expanded', String(details.open)); });
    });
    // Classic WordPress menu storage remains usable without this enhancement.
    document.querySelectorAll('.wooshop-primary-nav .menu-item-has-children').forEach(function (item, index) {
        const submenu = item.querySelector(':scope > .sub-menu');
        const link = item.querySelector(':scope > a');
        if (!submenu || !link) return;
        submenu.id = 'wooshop-navigation-submenu-' + index;
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'wooshop-submenu-toggle';
        button.textContent = '+';
        button.setAttribute('aria-label', (messages.subcategories || 'Subcategories of') + ' ' + link.textContent);
        button.setAttribute('aria-expanded', 'false');
        button.setAttribute('aria-controls', submenu.id);
        link.after(button);
        submenu.hidden = true;
        button.addEventListener('click', function () {
            submenu.hidden = !submenu.hidden;
            button.setAttribute('aria-expanded', String(!submenu.hidden));
        });
        item.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !submenu.hidden) { event.stopPropagation(); submenu.hidden = true; button.setAttribute('aria-expanded', 'false'); button.focus(); }
        });
    });
    document.querySelectorAll('[data-notice]').forEach(function (notice) {
        const close = notice.querySelector('.wooshop-notice-close');
        if (!close) return;
        const key = 'wooshop-dismissed-' + notice.dataset.notice;
        try { if (sessionStorage.getItem(key) === '1') notice.hidden = true; } catch (error) { /* Storage is optional. */ }
        close.addEventListener('click', function () {
            notice.hidden = true;
            try { sessionStorage.setItem(key, '1'); } catch (error) { /* Dismissal still works. */ }
            const next = document.querySelector('.wooshop-brand a');
            if (next) next.focus();
        });
    });
    let connectionNotice;
    function connectionChanged() {
        if (!connectionNotice) {
            connectionNotice = document.createElement('p');
            connectionNotice.className = 'wooshop-connection-notice';
            connectionNotice.setAttribute('role', 'status');
            const main = document.querySelector('main');
            if (main) main.prepend(connectionNotice);
            else return;
        }
        connectionNotice.hidden = navigator.onLine;
        connectionNotice.textContent = messages.offline || 'You appear to be offline. Reconnect and check the cart or order status before retrying.';
    }
    window.addEventListener('offline', connectionChanged);
    window.addEventListener('online', connectionChanged);
    if (!navigator.onLine) connectionChanged();
})();
