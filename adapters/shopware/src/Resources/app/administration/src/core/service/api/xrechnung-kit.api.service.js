/**
 * Admin API service for the plugin's custom HTTP endpoints.
 *
 * Wraps the bearer-auth http client and handles blob responses for
 * the XML download endpoint at /api/_action/xrechnung-kit/download/{id}.
 *
 * Listing and per-record reads use the standard DAL repository
 * (xrechnung_kit_invoice.repository) and do not need a custom service.
 */
const ApiService = Shopware.Classes.ApiService;
const { Application } = Shopware;

class XrechnungKitApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'xrechnung-kit') {
        super(httpClient, loginService, apiEndpoint);
        this.name = 'xrechnungKitApiService';
    }

    /**
     * Streams the XML file behind a given xrechnung_kit_invoice id.
     * Resolves with the raw response body (Blob) so the caller can
     * trigger an a-tag download or hand it off to a viewer.
     */
    download(invoiceId) {
        const headers = this.getBasicHeaders({ Accept: 'application/xml' });

        return this.httpClient
            .get(`_action/${this.getApiBasePath()}/download/${invoiceId}`, {
                headers,
                responseType: 'blob',
            })
            .then((response) => response.data);
    }

    /**
     * Triggers a fresh generation for an order. Resolves with the
     * updated invoice payload (id, status, errors, generatedPath,
     * kositResult, attemptCount, triggeredVia, triggeredBy).
     */
    regenerate(orderId) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/regenerate/${orderId}`, null, { headers })
            .then((response) => response.data);
    }
}

Application.addServiceProvider('XrechnungKitApiService', (container) => {
    const initContainer = Application.getContainer('init');
    return new XrechnungKitApiService(initContainer.httpClient, container.loginService);
});

export default XrechnungKitApiService;
