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
    static _unzerPlugin = null;

    init() {
        this._unzerPlugin = window.PluginManager.getPluginInstances('UnzerPaymentBase')[0];

        this._registerEvents();
    }

    /**
     * @private
     */
    _registerEvents() {
        this._unzerPlugin.$emitter.subscribe('unzerBase_createResource', () => this._onCreateResource(), {
            scope: this
        });
    }

    /**
     * @private
     */
    _onCreateResource() {
        let checkedRadioButton = null;

        this._unzerPlugin.setSubmitButtonActive(false);

        if (this.options.hasSavedAccounts) {
            /** @type {Element} */
            checkedRadioButton = document.querySelectorAll(this.options.selectedRadioButtonSelector)[0];
        }

        if (checkedRadioButton !== null && checkedRadioButton.value !== 'new') {
            this._unzerPlugin.submitTypeId(checkedRadioButton.value);

            return;
        }

        document.getElementById(this._unzerPlugin.options.confirmFormId).submit();
    }
}
