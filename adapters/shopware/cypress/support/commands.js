// Reusable Cypress commands for the Shopware admin.
//
// Login is done via the admin OAuth endpoint, which mirrors how the
// admin SPA authenticates and skips the brittle UI login flow.

Cypress.Commands.add('loginAdmin', () => {
    cy.request({
        method: 'POST',
        url: '/api/oauth/token',
        body: {
            grant_type: 'password',
            client_id: 'administration',
            scopes: 'write',
            username: Cypress.env('adminUser'),
            password: Cypress.env('adminPassword'),
        },
    }).then((response) => {
        const token = response.body.access_token
        Cypress.env('adminToken', token)
        window.localStorage.setItem('bearerAuth', JSON.stringify({ access: token }))
    })
})

Cypress.Commands.add('apiAdmin', (method, path, body = null) => {
    return cy.request({
        method,
        url: `/api/${path.replace(/^\//, '')}`,
        headers: {
            Authorization: `Bearer ${Cypress.env('adminToken')}`,
            'Content-Type': 'application/json',
        },
        body,
        failOnStatusCode: false,
    })
})

Cypress.Commands.add('visitAdmin', (path) => {
    const cleanPath = path.startsWith('/') ? path : `/${path}`
    cy.visit(`/admin#${cleanPath}`)
})
