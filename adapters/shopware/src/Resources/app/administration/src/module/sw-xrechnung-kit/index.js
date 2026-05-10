/**
 * Module registration for sw-xrechnung-kit.
 *
 * Type "plugin" - the module does not register a top-level navigation
 * entry. It contributes:
 *  - A new sub-route on the order detail page (sw.order.detail.xrechnung)
 *    that renders the XRechnung tab content
 *  - An override on sw-order-detail-base that adds a tab item linking
 *    to that sub-route
 *  - ACL privileges scoped under the orders permission category
 *
 * Snippet keys live under sw-xrechnung-kit.* in both en-GB and de-DE.
 */
import './extension/sw-order-detail-base';
import './page/sw-order-detail-xrechnung';
import './acl';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

const { Module } = Shopware;

Module.register('sw-xrechnung-kit', {
    type: 'plugin',
    name: 'XrechnungKit',
    title: 'sw-xrechnung-kit.general.mainMenuItemGeneral',
    description: 'sw-xrechnung-kit.general.descriptionTextModule',
    color: '#8a1c1c',
    icon: 'regular-document-pdf',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB,
    },
});

// Register the order-detail sub-route. Hooking into the existing
// sw-order module's route registry means the tab item in
// sw-order-detail-base resolves to a real route, not a manual modal.
const orderModule = Module.getModuleRegistry().get('sw-order');
if (orderModule && orderModule.routes) {
    orderModule.routes.set('sw.order.detail.xrechnung', {
        component: 'sw-order-detail-xrechnung',
        path: '/sw/order/detail/:id/xrechnung',
        name: 'sw.order.detail.xrechnung',
        isChildren: true,
        meta: {
            parentPath: 'sw.order.index',
            privilege: 'order.viewer',
        },
        props: {
            default: ($route) => ({ orderId: $route.params.id }),
        },
    });
}
