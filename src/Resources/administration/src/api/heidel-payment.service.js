import { Application } from 'src/core/shopware';
import ApiService from 'src/core/service/api.service';

class HeidelPaymentService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'heidelpay') {
        super(httpClient, loginService, apiEndpoint);
    }

    fetchPaymentHistory(transaction) {
        const apiRoute = `_action/${this.getApiBasePath()}/transaction/${transaction}/history`;

        return this.httpClient.get(
            apiRoute,
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
}

Application.addServiceProvider('HeidelPaymentService', (container) => {
    const initContainer = Application.getContainer('init');

    return new HeidelPaymentService(initContainer.httpClient, container.loginService);
});

