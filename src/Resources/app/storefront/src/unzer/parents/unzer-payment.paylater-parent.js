import UnzerPaymentBaseParent from './unzer-payment.base-parent';

export default class UnzerPaymentPaylaterParent extends UnzerPaymentBaseParent {
    init() {
        super.init();

        customElements.whenDefined('unzer-payment').then(() => {
            const component = this.el.querySelector('unzer-payment');
            if (component) {
                component.setBasketData({
                    amount: this.options.amount,
                    currencyType: this.options.currency,
                    country: this.options.countryIso,
                });
            }
        });
    }
}
