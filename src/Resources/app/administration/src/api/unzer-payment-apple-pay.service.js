const { Application } = Shopware;
const ApiService = Shopware.Classes.ApiService;

class UnzerPaymentApplePayService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'unzer-payment') {
        super(httpClient, loginService, apiEndpoint);
    }

    checkCertificates(salesChannelId) {
        let url = `_action/${this.getApiBasePath()}/apple-pay/certificates`;

        if (salesChannelId) {
            url += `/${salesChannelId}`;
        }

        return this.httpClient
            .get(
                url,
                {
                    headers: this.getBasicHeaders()
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    async updateCertificates(salesChannelId, files, inheritMerchantIdentification, inheritPaymentProcessing) {
        let url = `_action/${this.getApiBasePath()}/apple-pay/certificates`;

        if (salesChannelId) {
            url += `/${salesChannelId}`;
        }

        const data = {};

        for (let key in files) {
            if (files[key]) {
                const file = files[key];

                data[key] = await file.text();
            }
        }

        if (inheritMerchantIdentification) {
            data.inheritMerchantIdentification = true;
        }
        if (inheritPaymentProcessing) {
            data.inheritPaymentProcessing = true;
        }

        if (data.length === 0) {
            return new Promise();
        }

        return this.httpClient
            .post(
                url,
                data,
                {
                    headers: this.getBasicHeaders()
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

Application.addServiceProvider('UnzerPaymentApplePayService', (container) => {
    const initContainer = Application.getContainer('init');

    return new UnzerPaymentApplePayService(initContainer.httpClient, container.loginService);
});

