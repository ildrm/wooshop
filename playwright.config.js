const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
  testDir: './tests/browser',
  outputDir: 'artifacts/browser',
  workers: 1,
  fullyParallel: false,
  timeout: 90000,
  expect: { timeout: 15000 },
  reporter: [['list'], ['json', { outputFile: 'artifacts/browser-results.json' }]],
  use: {
    baseURL: 'http://localhost:18880',
    headless: true,
    channel: 'chrome',
    actionTimeout: 15000,
    viewport: { width: 1440, height: 1000 },
    screenshot: 'only-on-failure',
    trace: 'off',
    video: 'off'
  }
});
