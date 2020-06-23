import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';

export default class HeidelpaySepaDirectDebitPlugin extends Plugin {
    static options = {
        acceptMandateId: 'acceptSepaMandate',
        mandateNotAcceptedError: 'Please accept the SEPA direct debit mandate in order to continue.',
        elementWrapperSelector: '.heidelpay-sepa-wrapper-elements',
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
     * @type {HeidelpayBasePlugin}
     *
     * @private
     */
    static heidelpayPlugin = null;

    init() {
        this.heidelpayPlugin = window.PluginManager.getPluginInstances('HeidelpayBase')[0];
        this.sepa = this.heidelpayPlugin.heidelpayInstance.SepaDirectDebit();

        this._createForm();
        this._registerEvents();


        if (this.options.hasSepaDevices) {
            const heidelpayElementWrapper = DomAccess.querySelector(this.el, this.options.elementWrapperSelector);

            heidelpayElementWrapper.hidden = true;
        } else {
            this.heidelpayPlugin.setSubmitButtonActive(false);
        }
    }

    /**
     * @private
     */
    _createForm() {
        this.sepa.create('sepa-direct-debit', {
            containerId: 'heidelpay-sepa-container'
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

        this.heidelpayPlugin.$emitter.subscribe('heidelpayBase_createResource', () => this._onCreateResource(), {
            scope: this
        });
    }

    _onRadioButtonChange(event) {
        const targetElement = event.target;
        const heidelpayElementWrapper = DomAccess.querySelector(this.el, this.options.elementWrapperSelector);


        heidelpayElementWrapper.hidden = targetElement.id !== this.options.radioButtonNewAccountId;

        if (!targetElement || targetElement.id === this.options.radioButtonNewAccountId) {
            this.heidelpayPlugin.setSubmitButtonActive(this.sepa.validated);
        } else {
            this.heidelpayPlugin.setSubmitButtonActive(true);
        }
    }

    _onFormChange(event) {
        this.heidelpayPlugin.setSubmitButtonActive(event.success);
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

            this.heidelpayPlugin.setSubmitButtonActive(false);

            this.sepa.createResource()
                .then((resource) => this._submitPayment(resource))
                .catch((error) => this._handleError(error));
        } else {
            this.heidelpayPlugin.setSubmitButtonActive(false);
            this._submitDevicePayment(selectedDevice.value);
        }
    }

    /**
     * @param {Object} resource
     *
     * @private
     */
    _submitPayment(resource) {
        this.heidelpayPlugin.submitResource(resource);
    }

    /**
     * @param {Object} device
     *
     * @private
     */
    _submitDevicePayment(device) {
        this.heidelpayPlugin.submitTypeId(device);
    }

    /**
     * @param {Object} error
     *
     * @private
     */
    _handleError(error) {
        this.heidelpayPlugin.showError(error);
    }
}
