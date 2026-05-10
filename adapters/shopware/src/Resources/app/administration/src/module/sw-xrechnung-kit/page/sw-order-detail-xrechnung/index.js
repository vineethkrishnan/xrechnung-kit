/**
 * Order detail tab content. Loads the xrechnung_kit_invoice row
 * tied to the current order, renders status / generation metadata /
 * errors, and exposes the download action.
 *
 * Re-generate-now and audit views land in Phase D and re-use this
 * shell: extra cards mount under the same routed component so the
 * UX stays consistent.
 */
import template from './sw-order-detail-xrechnung.html.twig';
import './sw-order-detail-xrechnung.scss';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('sw-order-detail-xrechnung', {
    template,

    inject: ['repositoryFactory', 'XrechnungKitApiService', 'acl'],

    mixins: [
        Mixin.getByName('notification'),
    ],

    props: {
        orderId: {
            type: String,
            required: true,
        },
    },

    data() {
        return {
            invoice: null,
            isLoading: true,
            isDownloading: false,
        };
    },

    computed: {
        invoiceRepository() {
            return this.repositoryFactory.create('xrechnung_kit_invoice');
        },

        statusVariant() {
            const map = {
                generated: 'success',
                pending: 'neutral',
                invalid: 'danger',
                failed: 'danger',
            };
            return map[this.invoice && this.invoice.status] || 'neutral';
        },

        statusLabel() {
            const status = (this.invoice && this.invoice.status) || 'pending';
            return this.$tc('sw-xrechnung-kit.detail.status.' + status);
        },

        kositLabel() {
            const result = (this.invoice && this.invoice.kositResult) || 'skipped';
            return this.$tc('sw-xrechnung-kit.detail.kosit.' + result);
        },

        canDownload() {
            if (!this.invoice || !this.invoice.id) {
                return false;
            }
            if (!this.invoice.generatedPath) {
                return false;
            }
            return this.invoice.status === 'generated' || this.invoice.status === 'invalid';
        },

        hasErrors() {
            return Array.isArray(this.invoice && this.invoice.errors) && this.invoice.errors.length > 0;
        },

        generatedAtDisplay() {
            if (!this.invoice || !this.invoice.generatedAt) {
                return '-';
            }
            // Shopware.Utils.format.date is the project-standard date formatter
            // shared with the rest of the admin SPA.
            return Shopware.Utils.format.date(this.invoice.generatedAt, {
                year: 'numeric',
                month: 'short',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });
        },
    },

    watch: {
        orderId() {
            this.loadInvoice();
        },
    },

    created() {
        this.loadInvoice();
    },

    methods: {
        async loadInvoice() {
            this.isLoading = true;
            try {
                const criteria = new Criteria(1, 1);
                criteria.addFilter(Criteria.equals('orderId', this.orderId));
                const result = await this.invoiceRepository.search(criteria, Shopware.Context.api);
                this.invoice = result.first();
            } catch (err) {
                this.createNotificationError({
                    message: err.message,
                });
            } finally {
                this.isLoading = false;
            }
        },

        async downloadXml() {
            if (!this.canDownload || !this.invoice) {
                return;
            }
            this.isDownloading = true;
            try {
                const blob = await this.XrechnungKitApiService.download(this.invoice.id);
                const url = window.URL.createObjectURL(new Blob([blob], { type: 'application/xml' }));
                const a = document.createElement('a');
                a.href = url;
                a.download = (this.invoice.id) + '.xml';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                a.remove();
            } catch (err) {
                this.createNotificationError({
                    message: err.message,
                });
            } finally {
                this.isDownloading = false;
            }
        },
    },
});
