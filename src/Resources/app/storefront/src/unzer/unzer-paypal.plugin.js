import Plugin from 'src/plugin-system/plugin.class';

export default class UnzerPaymentPayPalPlugin extends Plugin {
    static options = {
        radioButtonSelector: 'input[name="savedPayPalAccount"]',
        selectedRadioButtonSelector: 'input[name="savedPayPalAccount"]:checked',
        radioButtonNewSelector: '#new',
        hasSavedAccounts: false
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
    static __unzerPaymentPlugin = null;

    init() {
        this.__unzerPaymentPlugin = window.PluginManager.getPluginInstances('UnzerPaymentBase')[0];

        this._registerEvents();
    }

    /**
     * @private
     */
    _registerEvents() {
        this.__unzerPaymentPlugin.$emitter.subscribe('unzerBase_createResource', () => this._onCreateResource(), {
            scope: this
        });
    }

    /**
     * @private
     */
    _onCreateResource() {
        let checkedRadioButton = null;

        this.__unzerPaymentPlugin.setSubmitButtonActive(false);

        if (this.options.hasSavedAccounts) {
            /** @type {Element} */
            checkedRadioButton = document.querySelectorAll(this.options.selectedRadioButtonSelector)[0];
        }

        if (checkedRadioButton !== null && checkedRadioButton.value !== 'new') {
            this.__unzerPaymentPlugin.submitTypeId(checkedRadioButton.value);

            return;
        }

        document.getElementById(this.__unzerPaymentPlugin.options.confirmFormId).submit();
    }
}
