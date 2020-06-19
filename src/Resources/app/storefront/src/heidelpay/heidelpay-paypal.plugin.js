import Plugin from 'src/plugin-system/plugin.class';

export default class HeidelpayPayPalPlugin extends Plugin {
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
     * @type {HeidelpayBasePlugin}
     *
     * @private
     */
    static _heidelpayPlugin = null;

    init() {
        this._heidelpayPlugin = window.PluginManager.getPluginInstances('HeidelpayBase')[0];

        this._registerEvents();
    }

    /**
     * @private
     */
    _registerEvents() {
        this._heidelpayPlugin.$emitter.subscribe('heidelpayBase_createResource', () => this._onCreateResource(), {
            scope: this
        });
    }

    /**
     * @private
     */
    _onCreateResource() {
        let checkedRadioButton = null;

        this._heidelpayPlugin.setSubmitButtonActive(false);

        if (this.options.hasSavedAccounts) {
            /** @type {Element}*/
            checkedRadioButton = document.querySelectorAll(this.options.selectedRadioButtonSelector)[0];
        }

        if(null !== checkedRadioButton && 'new' !== checkedRadioButton.value) {
            this._heidelpayPlugin.submitTypeId(checkedRadioButton.value);

            return;
        }

        document.getElementById(this._heidelpayPlugin.options.confirmFormId).submit();
    }
}
