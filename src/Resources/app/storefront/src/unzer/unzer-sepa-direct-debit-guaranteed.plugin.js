import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';

export default class UnzerPaymentSepaDirectDebitGuaranteedPlugin extends Plugin {
    static options = {
        birthDateFieldId: 'unzerPaymentBirthday',
        acceptMandateId: 'acceptSepaMandate',
        mandateNotAcceptedError: 'Please accept the SEPA direct debit mandate in order to continue.',
        elementWrapperSelector: '.unzer-payment-sepa-wrapper-elements',
        radioButtonSelector: '*[name="savedDirectDebitDevice"]',
        radioButtonNewAccountId: 'device-new',
        selectedRadioButtonSelector: '*[name="savedDirectDebitDevice"]:checked'
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
        const birthDate = document.getElementById(this.options.birthDateFieldId);
        this.unzerPlugin = window.PluginManager.getPluginInstances('UnzerPaymentBase')[0];
        this.sepa = this.unzerPlugin.unzerInstance.SepaDirectDebitGuaranteed();

        this._createForm();
        this._registerEvents();

        if (this.options.hasSepaDevices) {
            const unzerElementWrapper = DomAccess.querySelector(this.el, this.options.elementWrapperSelector);

            unzerElementWrapper.hidden = true;
            birthDate.required = false;
        } else {
            birthDate.required = true;
            this.unzerPlugin.setSubmitButtonActive(false);
        }
    }

    /**
     * @private
     */
    _createForm() {
        this.sepa.create('sepa-direct-debit-guaranteed', {
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
        }

        this.sepa.addEventListener('change', (event) => this._onFormChange(event));

        this.unzerPlugin.$emitter.subscribe('unzerBase_createResource', () => this._onCreateResource(), {
            scope: this
        });
    }

    _onRadioButtonChange(event) {
        const targetElement = event.target;
        const unzerElementWrapper = DomAccess.querySelector(this.el, this.options.elementWrapperSelector);
        const birthDate = document.getElementById(this.options.birthDateFieldId);

        unzerElementWrapper.hidden = targetElement.id !== this.options.radioButtonNewAccountId;

        if (!targetElement || targetElement.id === this.options.radioButtonNewAccountId) {
            this.unzerPlugin.setSubmitButtonActive(this.sepa.validated);
            birthDate.required = true;
        } else {
            this.unzerPlugin.setSubmitButtonActive(true);
            birthDate.required = false;
        }
    }

    _onFormChange(event) {
        this.unzerPlugin.setSubmitButtonActive(event.success);
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

            this.unzerPlugin.setSubmitButtonActive(false);

            this.sepa.createResource()
                .then((resource) => this._submitPayment(resource))
                .catch((error) => this._handleError(error));
        } else {
            this._unzerPaymentPlugin.setSubmitButtonActive(false);
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
