const Plugin = window.PluginBaseClass;


export default class UnzerPaymentPayPalPlugin extends Plugin {
    static options = {
        radioButtonSelector: 'input[name="savedPayPalAccount"]',
        selectedRadioButtonSelector: 'input[name="savedPayPalAccount"]:checked',
        radioButtonNewId: 'account-new',
        elementWrapperSelector:
            '.unzer-payment-saved-accounts-wrapper-elements',
        hasSavedAccounts: false,
    };

    /**
     * @type {Boolean}
     */
    static submitting = false;

    /**
     * @type {UnzerPaymentBasePlugin}
     *
     * @private
     */
    static _unzerPaymentPlugin = null;

    init() {
        this._unzerPaymentPlugin =
            window.PluginManager.getPluginInstances('UnzerPaymentBase')[0];

        this._registerEvents();

        if (this.options.hasSavedAccounts) {
            const unzerPaymentElementWrapper = this.el.querySelector(this.options.elementWrapperSelector);

            unzerPaymentElementWrapper.hidden = true;
        }
    }

    /**
     * @private
     */
    _registerEvents() {
        if (this.options.hasSavedAccounts) {
            const radioButtons = this.el.querySelectorAll(this.options.radioButtonSelector);

            for (let $i = 0; $i < radioButtons.length; $i++) {
                radioButtons[$i].addEventListener('change', (event) =>
                    this._onRadioButtonChange(event)
                );
            }
        }

        this._unzerPaymentPlugin.$emitter.subscribe(
            'unzerBase_createResource',
            () => this._onCreateResource()
        );
    }

    /**
     * @param {Object} event
     *
     * @private
     */
    _onRadioButtonChange(event) {
        const targetElement = event.target;
        const unzerPaymentElementWrapper = this.el.querySelector(this.options.elementWrapperSelector);

        unzerPaymentElementWrapper.hidden =
            targetElement.id !== this.options.radioButtonNewId;
    }

    /**
     * @private
     */
    _onCreateResource() {
        /** @type {Element} */
        const checkedRadioButton = document.querySelector(
            this.options.selectedRadioButtonSelector
        );

        if (
            checkedRadioButton !== null &&
            checkedRadioButton.id !== this.options.radioButtonNewId
        ) {
            this._unzerPaymentPlugin.submitTypeId(checkedRadioButton.value);

            return;
        }

        this._unzerPaymentPlugin.confirmForm.submit();
    }
}
