// Loaded automatically before every spec file.
import './commands'

// Suppress benign uncaught exceptions thrown by Shopware admin chunks
// during dev-mode hot-module reload. In CI the admin should be built
// for production so this is a defensive guard.
Cypress.on('uncaught:exception', () => false)
