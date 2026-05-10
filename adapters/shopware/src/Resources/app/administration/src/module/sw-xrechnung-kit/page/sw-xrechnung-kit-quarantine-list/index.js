/**
 * Quarantine list view. Lists xrechnung_kit_invoice rows whose status
 * is "invalid" or "failed" so an operator can triage them in one
 * place. Clicking a row navigates to the order's XRechnung tab where
 * the regenerate button lives.
 */
import template from './sw-xrechnung-kit-quarantine-list.html.twig';
import './sw-xrechnung-kit-quarantine-list.scss';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('sw-xrechnung-kit-quarantine-list', {
    template,

    inject: ['repositoryFactory', 'acl'],

    mixins: [
        Mixin.getByName('listing'),
        Mixin.getByName('notification'),
    ],

    data() {
        return {
            invoices: null,
            isLoading: true,
            sortBy: 'generatedAt',
            sortDirection: 'DESC',
        };
    },

    computed: {
        invoiceRepository() {
            return this.repositoryFactory.create('xrechnung_kit_invoice');
        },

        listCriteria() {
            const criteria = new Criteria(this.page, this.limit);
            criteria.addFilter(Criteria.equalsAny('status', ['invalid', 'failed']));
            criteria.addAssociation('order');
            criteria.addSorting(Criteria.sort(this.sortBy, this.sortDirection));
            return criteria;
        },

        columns() {
            return [
                {
                    property: 'order.orderNumber',
                    label: this.$tc('sw-xrechnung-kit.quarantine.column.orderNumber'),
                    routerLink: 'sw.order.detail.xrechnung',
                    primary: true,
                },
                {
                    property: 'status',
                    label: this.$tc('sw-xrechnung-kit.quarantine.column.status'),
                },
                {
                    property: 'attemptCount',
                    label: this.$tc('sw-xrechnung-kit.quarantine.column.attempts'),
                },
                {
                    property: 'kositResult',
                    label: this.$tc('sw-xrechnung-kit.quarantine.column.kositResult'),
                },
                {
                    property: 'triggeredVia',
                    label: this.$tc('sw-xrechnung-kit.quarantine.column.triggeredVia'),
                },
                {
                    property: 'generatedAt',
                    label: this.$tc('sw-xrechnung-kit.quarantine.column.generatedAt'),
                },
            ];
        },
    },

    created() {
        this.getList();
    },

    methods: {
        async getList() {
            this.isLoading = true;
            try {
                const result = await this.invoiceRepository.search(this.listCriteria, Shopware.Context.api);
                this.invoices = result;
                this.total = result.total;
            } catch (err) {
                this.createNotificationError({
                    message: err.message,
                });
            } finally {
                this.isLoading = false;
            }
        },

        statusVariantOf(invoice) {
            return (invoice && invoice.status === 'failed') ? 'danger' : 'warning';
        },

        formatDate(value) {
            if (!value) {
                return '-';
            }
            return Shopware.Utils.format.date(value, {
                year: 'numeric',
                month: 'short',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
            });
        },

        rowRoute(invoice) {
            const orderId = invoice && invoice.orderId;
            if (!orderId) {
                return null;
            }
            return { name: 'sw.order.detail.xrechnung', params: { id: orderId } };
        },
    },
});
