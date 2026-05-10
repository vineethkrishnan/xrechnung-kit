// E2E tests for the quarantine list view.

describe('Quarantine list', () => {
    beforeEach(() => {
        cy.loginAdmin()
    })

    it('navigates to the quarantine route and renders without errors', () => {
        cy.visitAdmin('/sw/xrechnung-kit/quarantine')
        cy.contains('h2, .sw-page__smart-bar-content', /quarantine/i, { timeout: 12000 })
    })

    it('quarantine list reflects xrechnung_kit_invoice rows in invalid or failed status', () => {
        cy.apiAdmin('POST', 'search/xrechnung-kit-invoice', {
            filter: [{
                type: 'multi',
                operator: 'or',
                queries: [
                    { type: 'equals', field: 'status', value: 'invalid' },
                    { type: 'equals', field: 'status', value: 'failed' },
                ],
            }],
            limit: 25,
            includes: { 'xrechnung_kit_invoice': ['id', 'status'] },
        }).then((response) => {
            expect(response.status).to.eq(200)
            const data = response.body.data || []
            cy.visitAdmin('/sw/xrechnung-kit/quarantine')
            if (data.length === 0) {
                cy.contains(/no xrechnung in quarantine|keine xrechnungen/i, { timeout: 12000 })
            } else {
                cy.get('.sw-data-grid', { timeout: 12000 }).should('be.visible')
            }
        })
    })
})
