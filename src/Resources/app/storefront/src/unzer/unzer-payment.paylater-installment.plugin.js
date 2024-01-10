import Plugin from 'src/plugin-system/plugin.class';
import ElementLoadingIndicatorUtil from 'src/utility/loading-indicator/element-loading-indicator.util';

export default class UnzerPaymentPaylaterInstallmentPlugin extends Plugin {
    static options = {
        formLoadingIndicatorElementId: 'element-loader',
        birthdateInputIdSelector: 'unzerPaymentBirthday',
        birthdateContainerIdSelector: 'unzerPaymentBirthdayContainer',
        paylaterInstallmentAmount: 0.0,
        paylaterInstallmentCurrency: '',
        currencyIso: 'EUR',
        countryIso: 'DE',
        threatMetrixId: '',
    };

    /**
     * @type {Object}
     *
     * @public
     */
    static paylaterInstallment;

    /**
     * @type {Object}
     *
     * @public
     */
    static birthdateContainer;

    /**
     * @type {Object}
     *
     * @public
     */
    static birthdateInput;

    /**
     * @type {boolean}
     *
     * @public
     */
    static unzerInputsValid;

    /**
     * @type {UnzerPaymentBasePlugin}
     *
     * @private
     */
    static _unzerPaymentPlugin = null;

    init() {
        this._unzerPaymentPlugin = window.PluginManager.getPluginInstances('UnzerPaymentBase')[0];
        this.paylaterInstallment = this._unzerPaymentPlugin.unzerInstance.PaylaterInstallment();
        this._unzerPaymentPlugin.setSubmitButtonActive(false);
        this.birthdateContainer = document.getElementById(this.options.birthdateContainerIdSelector);
        this.birthdateInput = document.getElementById(this.options.birthdateInputIdSelector);
        this.unzerInputsValid = false;

        this._createForm();
        this._registerEvents();
    }

    /**
     * @private
     */
    _createForm() {
        const loadingIndicatorElement = document.getElementById(this.options.formLoadingIndicatorElementId);

        ElementLoadingIndicatorUtil.create(loadingIndicatorElement);

        this.paylaterInstallment.create({
            containerId: 'unzer-payment-paylater-installment-container',
            amount: this.options.paylaterInstallmentAmount.toFixed(4),
            currency: this.options.paylaterInstallmentCurrency,
            country: this.options.countryIso,
            threatMetrixId: this.options.threatMetrixId,
        }).then(() => {
            loadingIndicatorElement.hidden = true;
        }).catch((error) => {
            this._unzerPaymentPlugin.renderErrorToElement(error, loadingIndicatorElement);
            this._unzerPaymentPlugin.setSubmitButtonActive(false);
        }).finally(() => {
            ElementLoadingIndicatorUtil.remove(loadingIndicatorElement);
        });
    }

    /**
     * @private
     */
    _registerEvents() {
        this._unzerPaymentPlugin.$emitter.subscribe('unzerBase_createResource', () => this._onCreateResource(), {
            scope: this
        });

        this.paylaterInstallment.addEventListener('paylaterInstallmentEvent', (event) => this._onChangeInstallmentSecuredForm(event));
        this.birthdateInput.addEventListener('change', this._onBirthdateInputChange.bind(this))
    }

    /**
     * @private
     */
    _onCreateResource() {
        this._unzerPaymentPlugin.setSubmitButtonActive(false);

        this.paylaterInstallment.createResource()
            .then((resource) => this._unzerPaymentPlugin.submitResource(resource))
            .catch((error) => this._unzerPaymentPlugin.showError(error));
    }

    /**
     * @param {Object} event
     *
     * @private
     */
    _onChangeInstallmentSecuredForm(event) {
        if (event.action === 'validate') {
            this.unzerInputsValid = event.success;
            if (event.success && this._validateBirthdate()) {
                this._unzerPaymentPlugin.setSubmitButtonActive(true);
            } else {
                this._unzerPaymentPlugin.setSubmitButtonActive(false);
            }
        }

        switch (event.currentStep) {
            case 'plan-list':
                this._unzerPaymentPlugin.setSubmitButtonActive(false);
                break;

            case 'plan-detail':
                this._unzerPaymentPlugin.setSubmitButtonActive(true);
                break;
        }
    }

    _formatCurrency(value) {
        return value.toLocaleString(this.options.currencyFormatLocale, {
            style: 'currency',
            currency: this.options.currencyIso
        });
    }

    _onBirthdateInputChange() {
        if (this._validateBirthdate() && this.unzerInputsValid) {
            this._unzerPaymentPlugin.setSubmitButtonActive(true);
        } else {
            this._unzerPaymentPlugin.setSubmitButtonActive(false);
        }
    }

    _validateBirthdate() {
        if (this.birthdateInput.value === '') {
            return false;
        }

        const birthdate = new Date(this.birthdateInput.value);
        const maxDate = new Date();
        const minAge = new Date()
        ;

        //normalize times
        birthdate.setHours(0, 0, 0, 0);
        maxDate.setHours(0, 0, 0, 0);
        minAge.setHours(0, 0, 0, 0);

        //update maxDate and minAge to relevant values
        maxDate.setDate(maxDate.getDate() + 1);
        minAge.setFullYear(minAge.getFullYear() - 18);

        const isValid = birthdate <= minAge && birthdate < maxDate;

        if (isValid) {
            this.birthdateContainer.classList.remove('error');
        } else {
            this.birthdateContainer.classList.add('error');
        }

        return isValid;
    }
}
