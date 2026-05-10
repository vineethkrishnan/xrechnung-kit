/**
 * Privilege mapping for the xrechnung_kit_invoice entity. Hangs under
 * the orders category in the role admin so reviewers find it next to
 * the order privileges that drive its lifecycle.
 *
 * viewer: read invoice rows, call the download endpoint
 * editor: update / create invoice rows (used by the upcoming Phase D
 *         retry button)
 * deleter: delete invoice rows (cleanup, audit purges)
 */
Shopware.Service('privileges').addPrivilegeMappingEntry({
    category: 'permissions',
    parent: 'orders',
    key: 'xrechnung_kit_invoice',
    roles: {
        viewer: {
            privileges: [
                'xrechnung_kit_invoice:read',
                'xrechnung-kit-api-action.download',
            ],
            dependencies: [],
        },
        editor: {
            privileges: [
                'xrechnung_kit_invoice:update',
                'xrechnung_kit_invoice:create',
            ],
            dependencies: ['xrechnung_kit_invoice.viewer'],
        },
        deleter: {
            privileges: [
                'xrechnung_kit_invoice:delete',
            ],
            dependencies: ['xrechnung_kit_invoice.viewer'],
        },
    },
});
