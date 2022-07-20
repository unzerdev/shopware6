const { Application } = Shopware;
const ApiService = Shopware.Classes.ApiService;

class UnzerPaymentApplePayService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'unzer-payment') {
        super(httpClient, loginService, apiEndpoint);
    }

    checkCertificates() {
        return this.httpClient
            .get(
                `_action/${this.getApiBasePath()}/apple-pay/certificates`,
                {
                    headers: this.getBasicHeaders()
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    updateCertificates(data) {
        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/apple-pay/certificates`,
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

