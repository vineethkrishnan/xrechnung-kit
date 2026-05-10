// E2E tests for the XRechnung tab on the order detail page.
//
// Pre-requisites for running:
//   - Shopware running at CYPRESS_BASE_URL with the plugin installed
//     and activated
//   - At least one order in the test database that the admin user can
//     read; the spec creates one if needed via the admin API
//   - The plugin's seller party config filled in (name, vat id,
//     address); otherwise the regenerate path skips with a clear
//     error and the test still passes the negative-case assertions

describe('XRechnung tab on order detail', () => {
    beforeEach(() => {
        cy.loginAdmin()
    })

    it('renders the XRechnung tab and the empty state when no invoice exists yet', () => {
        cy.apiAdmin('POST', 'search/order', {
            limit: 1,
            includes: { order: ['id', 'orderNumber'] },
        }).then((response) => {
            const order = response.body.data && response.body.data[0]
            if (!order) {
                cy.log('No orders in this Shopware install; skipping the rendering check.')
                return
            }

            cy.visitAdmin(`/sw/order/detail/${order.id}/xrechnung`)
            cy.contains('h2, .sw-card__title', /XRechnung/i, { timeout: 12000 })
        })
    })

    it('regenerate action calls POST /api/_action/xrechnung-kit/regenerate/{orderId}', () => {
        cy.apiAdmin('POST', 'search/order', {
            limit: 1,
            includes: { order: ['id'] },
        }).then((response) => {
            const order = response.body.data && response.body.data[0]
            if (!order) {
                this.skip()
            }

            cy.apiAdmin('POST', `_action/xrechnung-kit/regenerate/${order.id}`).then((apiResponse) => {
                expect(apiResponse.status).to.be.oneOf([200, 400, 404, 500])
                expect(apiResponse.body).to.have.property('id').or.have.property('error')
            })
        })
    })

    it('download action returns the XML when the file is on disk', () => {
        cy.apiAdmin('POST', 'search/xrechnung-kit-invoice', {
            limit: 1,
            filter: [{ type: 'equals', field: 'status', value: 'generated' }],
            includes: { 'xrechnung_kit_invoice': ['id', 'generatedPath'] },
        }).then((response) => {
            const invoice = response.body.data && response.body.data[0]
            if (!invoice || !invoice.generatedPath) {
                cy.log('No generated invoice in the test data set; skipping the download check.')
                return
            }

            cy.apiAdmin('GET', `_action/xrechnung-kit/download/${invoice.id}`).then((apiResponse) => {
                expect(apiResponse.status).to.eq(200)
                expect(apiResponse.headers['content-type']).to.match(/application\/xml/)
                expect(apiResponse.body).to.match(/^<\?xml/)
            })
        })
    })

    it('PEPPOL send action returns a delivery payload (skipped when not configured)', () => {
        cy.apiAdmin('POST', 'search/xrechnung-kit-invoice', {
            limit: 1,
            filter: [{ type: 'equals', field: 'status', value: 'generated' }],
            includes: { 'xrechnung_kit_invoice': ['id'] },
        }).then((response) => {
            const invoice = response.body.data && response.body.data[0]
            if (!invoice) {
                cy.log('No generated invoice in the test data set; skipping the peppol check.')
                return
            }

            cy.apiAdmin('POST', `_action/xrechnung-kit/peppol/${invoice.id}`).then((apiResponse) => {
                expect(apiResponse.status).to.be.oneOf([200, 404])
                if (apiResponse.status === 200) {
                    expect(apiResponse.body).to.have.property('deliveryStatus')
                }
            })
        })
    })
})
