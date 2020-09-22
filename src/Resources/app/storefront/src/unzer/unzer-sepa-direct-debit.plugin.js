import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';

export default class UnzerSepaDirectDebitPlugin extends Plugin {
    static options = {
        acceptMandateId: 'acceptSepaMandate',
        mandateNotAcceptedError: 'Please accept the SEPA direct debit mandate in order to continue.',
        elementWrapperSelector: '.unzer-sepa-wrapper-elements',
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
    static unzerPlugin = null;

    init() {
        this.unzerPaymentPlugin = window.PluginManager.getPluginInstances('UnzerPaymentBase')[0];
        this.sepa = this.unzerPaymentPlugin.unzerPaymentInstance.SepaDirectDebit();

        this._createForm();
        this._registerEvents();


        if (this.options.hasSepaDevices) {
            const unzerElementWrapper = DomAccess.querySelector(this.el, this.options.elementWrapperSelector);

            unzerElementWrapper.hidden = true;
        } else {
            this.unzerPaymentPlugin.setSubmitButtonActive(false);
        }
    }

    /**
     * @private
     */
    _createForm() {
        this.sepa.create('sepa-direct-debit', {
            containerId: 'unzer-sepa-container'
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
        }

        this.sepa.addEventListener('change', (event) => this._onFormChange(event));

        this.unzerPaymentPlugin.$emitter.subscribe('unzerBase_createResource', () => this._onCreateResource(), {
            scope: this
        });
    }

    _onRadioButtonChange(event) {
        const targetElement = event.target;
        const unzerElementWrapper = DomAccess.querySelector(this.el, this.options.elementWrapperSelector);


        unzerElementWrapper.hidden = targetElement.id !== this.options.radioButtonNewAccountId;

        if (!targetElement || targetElement.id === this.options.radioButtonNewAccountId) {
            this.unzerPaymentPlugin.setSubmitButtonActive(this.sepa.validated);
        } else {
            this.unzerPaymentPlugin.setSubmitButtonActive(true);
        }
    }

    _onFormChange(event) {
        this.unzerPaymentPlugin.setSubmitButtonActive(event.success);
    }

    /**
     * @private
     */
    _onCreateResource() {
        const mandateAcceptedCheckbox = document.getElementById(this.options.acceptMandateId);
        const selectedDevice = document.querySelector(this.options.selectedRadioButtonSelector);

        if (!this.options.hasSepaDevices || !selectedDevice || selectedDevice.id === this.options.radioButtonNewAccountId) {
            if (!mandateAcceptedCheckbox.checked) {
                this._handleError({
                    message: this.options.mandateNotAcceptedError
                });

                mandateAcceptedCheckbox.classList.add('is-invalid');

                return;
            }

            this.unzerPaymentPlugin.setSubmitButtonActive(false);

            this.sepa.createResource()
                .then((resource) => this._submitPayment(resource))
                .catch((error) => this._handleError(error));
        } else {
            this.unzerPaymentPlugin.setSubmitButtonActive(false);
            this._submitDevicePayment(selectedDevice.value);
        }
    }

    /**
     * @param {Object} resource
     *
     * @private
     */
    _submitPayment(resource) {
        this.unzerPaymentPlugin.submitResource(resource);
    }

    /**
     * @param {Object} device
     *
     * @private
     */
    _submitDevicePayment(device) {
        this.unzerPaymentPlugin.submitTypeId(device);
    }

    /**
     * @param {Object} error
     *
     * @private
     */
    _handleError(error) {
        this.unzerPaymentPlugin.showError(error);
    }
}
