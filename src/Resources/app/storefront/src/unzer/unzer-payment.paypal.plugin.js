import Plugin from 'src/plugin-system/plugin.class';

export default class UnzerPaymentPayPalPlugin extends Plugin {
    static options = {
        radioButtonSelector: 'input[name="savedPayPalAccount"]',
        selectedRadioButtonSelector: 'input[name="savedPayPalAccount"]:checked',
        radioButtonNewSelector: '#new'
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
        this._unzerPaymentPlugin = window.PluginManager.getPluginInstances('UnzerPaymentBase')[0];

        this._registerEvents();
    }

    /**
     * @private
     */
    _registerEvents() {
        this._unzerPaymentPlugin.$emitter.subscribe('unzerBase_createResource', () => this._onCreateResource());
    }

    /**
     * @private
     */
    _onCreateResource() {
        /** @type {Element} */
        const checkedRadioButton = document.querySelector(this.options.selectedRadioButtonSelector);

        if (checkedRadioButton !== null && checkedRadioButton.value !== 'new') {
            this._unzerPaymentPlugin.submitTypeId(checkedRadioButton.value);

            return;
        }

        this._unzerPaymentPlugin.confirmForm.submit();
    }
}
