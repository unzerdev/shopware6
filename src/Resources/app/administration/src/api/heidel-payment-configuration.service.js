const { Application } = Shopware;
const ApiService = Shopware.Classes.ApiService;

class HeidelPaymentConfigurationService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'heidel_payment') {
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
}

Application.addServiceProvider('HeidelPaymentConfigurationService', (container) => {
    const initContainer = Application.getContainer('init');

    return new HeidelPaymentConfigurationService(initContainer.httpClient, container.loginService);
});

