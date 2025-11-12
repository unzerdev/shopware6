const Plugin = window.PluginBaseClass;

import UnzerPaymentBaseParent from './unzer-payment.base-parent';

export default class UnzerPaymentSavedDeviceParent extends UnzerPaymentBaseParent {
    static options = {
        elementWrapperSelector: '.unzer-payment-create-component-container',
        radioButtonSelector: '*[name="savedPaymentDevice"]',
        radioButtonNewAccountId: 'device-new',
        selectedRadioButtonSelector: '*[name="savedPaymentDevice"]:checked',
        hasSavedDevices: false,
    };

    init() {
        super.init();
        this._registerEvents();
    }

    /**
     * @private
     */
    _registerEvents() {
        if (this.options.hasSavedDevices) {
            const radioButtons = this.el.querySelectorAll(
                this.options.radioButtonSelector
            );

            for (let $i = 0; $i < radioButtons.length; $i++) {
                radioButtons[$i].addEventListener('change', (event) =>
                    this._onRadioButtonChange(event)
                );
            }

            document
                .querySelector(this.options.selectedRadioButtonSelector)
                .dispatchEvent(new Event('change'));
        }
    }

    _onRadioButtonChange(event) {
        const targetElement = event.target;
        const unzerElementWrapper = this.el.querySelector(
            this.options.elementWrapperSelector
        );
        unzerElementWrapper.hidden =
            targetElement.id !== this.options.radioButtonNewAccountId;
    }
}
