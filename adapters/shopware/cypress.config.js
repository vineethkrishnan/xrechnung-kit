// Cypress config for the xrechnung-kit-shopware admin SPA tests.
//
// Run against a Shopware project that has the plugin installed and
// activated. Set CYPRESS_BASE_URL, CYPRESS_ADMIN_USER, and
// CYPRESS_ADMIN_PASSWORD before npx cypress run.
//
// See tests/README.md for the recommended setup.

const { defineConfig } = require('cypress')

module.exports = defineConfig({
    e2e: {
        baseUrl: process.env.CYPRESS_BASE_URL || 'http://localhost:8000',
        specPattern: 'cypress/e2e/**/*.cy.{js,ts}',
        supportFile: 'cypress/support/e2e.js',
        fixturesFolder: 'cypress/fixtures',
        screenshotsFolder: 'cypress/screenshots',
        videosFolder: 'cypress/videos',
        video: false,
        defaultCommandTimeout: 8000,
        requestTimeout: 15000,
        viewportWidth: 1440,
        viewportHeight: 900,
    },
    env: {
        adminUser: process.env.CYPRESS_ADMIN_USER || 'admin',
        adminPassword: process.env.CYPRESS_ADMIN_PASSWORD || 'shopware',
    },
})
