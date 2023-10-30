const { Application } = Shopware;
const ApiService = Shopware.Classes.ApiService;

class UnzerPaymentService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'unzer-payment') {
        super(httpClient, loginService, apiEndpoint);
    }

    fetchPaymentDetails(transaction) {
        const apiRoute = `_action/${this.getApiBasePath()}/transaction/${transaction}/details`;

        return this.httpClient.get(
            apiRoute,
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    chargeTransaction(transaction, payment, amount) {
        const apiRoute = `_action/${this.getApiBasePath()}/transaction/${transaction}/charge/${amount}`;

        return this.httpClient.get(
            apiRoute,
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    refundTransaction(transaction, charge, amount, reasonCode = null) {
        let apiRoute = `_action/${this.getApiBasePath()}/transaction/${transaction}/refund/${charge}/${amount}`;

        if (reasonCode !== null) {
            apiRoute = `${apiRoute}/${reasonCode}`;
        }

        return this.httpClient.get(
            apiRoute,
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    cancelTransaction(transaction, authorize, amount) {
        const apiRoute = `_action/${this.getApiBasePath()}/transaction/${transaction}/cancel/${authorize}/${amount}`;

        return this.httpClient.get(
            apiRoute,
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    ship(transaction) {
        const apiRoute = `_action/${this.getApiBasePath()}/transaction/${transaction}/ship`;

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

Application.addServiceProvider('UnzerPaymentService', (container) => {
    const initContainer = Application.getContainer('init');

    return new UnzerPaymentService(initContainer.httpClient, container.loginService);
});

