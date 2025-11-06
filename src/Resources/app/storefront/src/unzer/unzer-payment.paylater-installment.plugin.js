import UnzerPaymentBaseParent from './parents/unzer-payment.base-parent';

export default class UnzerPaymentPaylaterInstallmentPlugin extends UnzerPaymentBaseParent {
    static options = {
        countryIso: '',
        paylaterInstallmentAmount: '',
        paylaterInstallmentCurrency: '',
    };
    init() {
        super.init();
        Promise.all([customElements.whenDefined('unzer-payment')]).then(() => {
            const unzerPaymentElement = document.getElementById(
                'unzer-payment-component'
            );
            if (unzerPaymentElement) {
                unzerPaymentElement.setBasketData({
                    amount: this.options.paylaterInstallmentAmount,
                    currencyType: this.options.paylaterInstallmentCurrency,
                    country: this.options.countryIso,
                });
            }
        });
    }
}
