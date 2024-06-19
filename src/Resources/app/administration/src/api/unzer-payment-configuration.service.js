const { Application } = Shopware;
const ApiService = Shopware.Classes.ApiService;

class UnzerPaymentConfigurationService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'unzer-payment') {
        super(httpClient, loginService, apiEndpoint);
    }

    validateCredentials(credentials) {
        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/validate-credentials`,
                credentials,
                {
                    headers: this.getBasicHeaders()
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    registerWebhooks(data) {
        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/register-webhooks`,
                data,
                {
                    headers: this.getBasicHeaders()
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    clearWebhooks(data) {
        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/clear-webhooks`,
                data,
                {
                    headers: this.getBasicHeaders()
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    getWebhooks(privateKey) {
        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/get-webhooks`,
                {'privateKey': privateKey},
                {
                    headers: this.getBasicHeaders()
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    getGooglePayGatewayMerchantId(salesChannelId) {
        return this.httpClient
            .get(
                `_action/${this.getApiBasePath()}/get-google-pay-gateway-merchant-id?salesChannelId=${salesChannelId||''}`,
                {
                    headers: this.getBasicHeaders()
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

Application.addServiceProvider('UnzerPaymentConfigurationService', (container) => {
    const initContainer = Application.getContainer('init');

    return new UnzerPaymentConfigurationService(initContainer.httpClient, container.loginService);
});

