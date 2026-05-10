/**
 * Override of sw-order-detail-base that adds an XRechnung tab to the
 * order detail tab strip. The tab routes to sw.order.detail.xrechnung,
 * which is registered by ../../index.js in the order module's route
 * registry.
 */
import template from './sw-order-detail-base.html.twig';

Shopware.Component.override('sw-order-detail-base', {
    template,
});
