(() => {
  'use strict';
  const config = window.WooShopMarketplace;
  if (!config) return;
  document.querySelectorAll('form[data-marketplace-route]').forEach(form => {
    form.addEventListener('submit', async event => {
      event.preventDefault();
      const output = form.querySelector('[data-result]');
      const button = form.querySelector('button[type="submit"]');
      const body = {};
      new FormData(form).forEach((value, key) => {
        body[key] = form.elements[key]?.hasAttribute('data-integer') ? Number(value) : value;
      });
      const key = form.dataset.operationKey || crypto.randomUUID();
      form.dataset.operationKey = key;
      button.disabled = true;
      output.textContent = config.working;
      output.dataset.error = 'false';
      try {
        const headers = { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce, 'Idempotency-Key': key };
        if (form.dataset.version) headers['If-Match'] = `"${form.dataset.version}"`;
        const response = await fetch(config.root + form.dataset.marketplaceRoute, { method: form.dataset.method, credentials: 'same-origin', headers, body: JSON.stringify(body) });
        const result = await response.json();
        if (!response.ok) {
          if (response.status < 500) delete form.dataset.operationKey;
          throw new Error(result.message || `Request failed (${response.status}).`);
        }
        if (result.version) form.dataset.version = String(result.version);
        delete form.dataset.operationKey;
        output.textContent = config.saved;
      } catch (error) {
        output.dataset.error = 'true';
        output.textContent = error.message;
      } finally { button.disabled = false; }
    });
  });
})();
