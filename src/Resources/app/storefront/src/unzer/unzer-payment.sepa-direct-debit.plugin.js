import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';

export default class UnzerPaymentSepaDirectDebitPlugin extends Plugin {
    static options = {
        acceptMandateId: 'acceptSepaMandate',
        elementWrapperSelector: '.unzer-payment-sepa-wrapper-elements',
        radioButtonSelector: '*[name="savedDirectDebitDevice"]',
        radioButtonNewAccountId: 'device-new',
        selectedRadioButtonSelector: '*[name="savedDirectDebitDevice"]:checked',
        hasSepaDevices: false
    };

    /**
     * @type {Object}
     *
     * @public
     */
    static sepa;

    /**
     * @type {UnzerPaymentBasePlugin}
     *
     * @private
     */
    static _unzerPaymentPlugin = null;

    init() {
        this._unzerPaymentPlugin = window.PluginManager.getPluginInstances('UnzerPaymentBase')[0];
        this.sepa = this._unzerPaymentPlugin.unzerInstance.SepaDirectDebit();
        this.mandateAcceptedCheckbox = document.getElementById(this.options.acceptMandateId);

        this._createForm();
        this._registerEvents();


        if (!this.options.hasSepaDevices) {
            this._unzerPaymentPlugin.setSubmitButtonActive(false);
        }
    }

    /**
     * @private
     */
    _createForm() {
        this.sepa.create('sepa-direct-debit', {
            containerId: 'unzer-payment-sepa-container'
        });
    }

    /**
     * @private
     */
    _registerEvents() {
        if (this.options.hasSepaDevices) {
            const radioButtons = DomAccess.querySelectorAll(this.el, this.options.radioButtonSelector);

            for (let $i = 0; $i < radioButtons.length; $i++) {
                radioButtons[$i].addEventListener('change', (event) => this._onRadioButtonChange(event));
            }

            document.querySelector(this.options.selectedRadioButtonSelector).dispatchEvent(new Event('change'));
        }

        this.sepa.addEventListener('change', (event) => this._onFormChange(event));

        this._unzerPaymentPlugin.$emitter.subscribe('unzerBase_createResource', () => this._onCreateResource(), {
            scope: this
        });
    }

    _onRadioButtonChange(event) {
        const targetElement = event.target;
        const unzerElementWrapper = DomAccess.querySelector(this.el, this.options.elementWrapperSelector);

        unzerElementWrapper.hidden = targetElement.id !== this.options.radioButtonNewAccountId;

        if (!targetElement || targetElement.id === this.options.radioButtonNewAccountId) {
            this._unzerPaymentPlugin.setSubmitButtonActive(this.sepa.validated);
            this.mandateAcceptedCheckbox.required = true;
        } else {
            this._unzerPaymentPlugin.setSubmitButtonActive(true);
            this.mandateAcceptedCheckbox.required = false;
        }
    }

    _onFormChange(event) {
        this._unzerPaymentPlugin.setSubmitButtonActive(event.success);
    }

    /**
     * @private
     */
    _onCreateResource() {
        const selectedDevice = document.querySelector(this.options.selectedRadioButtonSelector);

        this._unzerPaymentPlugin.setSubmitButtonActive(false);

        if (!selectedDevice || selectedDevice.id === this.options.radioButtonNewAccountId) {
            this.sepa.createResource()
                .then((resource) => this._submitPayment(resource))
                .catch((error) => this._handleError(error));
        } else {
            this._submitDevicePayment(selectedDevice.value);
        }
    }

    /**
     * @param {Object} resource
     *
     * @private
     */
    _submitPayment(resource) {
        this._unzerPaymentPlugin.submitResource(resource);
    }

    /**
     * @param {Object} device
     *
     * @private
     */
    _submitDevicePayment(device) {
        this._unzerPaymentPlugin.submitTypeId(device);
    }

    /**
     * @param {Object} error
     *
     * @private
     */
    _handleError(error) {
        this._unzerPaymentPlugin.showError(error);
    }
}
