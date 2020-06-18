import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';

export default class HeidelpayPayPalPlugin extends Plugin {
    static options = {
        heidelpayCreatePaymentUrl: '',
        radioButtonSelector: 'input:radio[name="savedPayPalAccount"]',
        selectedRadioButtonSelector: 'input:radio[name="savedPayPalAccount"]:checked',
        radioButtonNewSelector: '#new',
        hasSavedAccounts: false,
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

        // this._registerEvents();
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
            checkedRadioButton = DomAccess.querySelector(this.el, this.options.selectedRadioButtonSelector);
        }

        this.submitting = true;

        if (checkedRadioButton === null || checkedRadioButton.id === this.options.radioButtonNewId) {
            this.payPalAccount.createResource()
                .then((resource) => this._submitPayment(resource))
                .catch((error) => this._handleError(error));
        } else {
            this._heidelpayPlugin.submitTypeId(checkedRadioButton.value);
        }
    }

    /**
     * @param {Object} resource
     *
     * @private
     */
    _submitPayment(resource) {
        this._heidelpayPlugin.submitResource(resource);
    }

    /**
     * @param {Object} error
     *
     * @private
     */
    _handleError(error) {
        this._heidelpayPlugin.showError(error);
    }
}
