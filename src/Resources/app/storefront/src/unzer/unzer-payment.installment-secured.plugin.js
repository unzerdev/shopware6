import Plugin from 'src/plugin-system/plugin.class';
import ElementLoadingIndicatorUtil from 'src/utility/loading-indicator/element-loading-indicator.util';

export default class UnzerPaymentInstallmentSecuredPlugin extends Plugin {
    static options = {
        installmentSecuredAmount: 0.0,
        installmentSecuredCurrency: '',
        installmentSecuredEffectiveInterest: 0.0,
        installmentSecuredOrderDate: '',
        installmentsTotalValueElementId: 'unzer-payment-installments-total',
        installmentsInterestValueElementId: 'unzer-payment-installments-interest',
        formLoadingIndicatorElementId: 'element-loader',
        currencyIso: 'EUR',
        currencyFormatLocale: 'en-GB',
        starSymbol: '*',
        birthdateInputSelector: 'unzerPaymentBirthday',
        birthdateContainerSelector: 'unzerPaymentBirthdayContainer'
    };

    /**
     * @type {Object}
     *
     * @public
     */
    static installmentSecured;

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
        this.installmentSecured = this._unzerPaymentPlugin.unzerInstance.InstallmentSecured();
        this._unzerPaymentPlugin.setSubmitButtonActive(false);
        this.birthdateContainer = document.getElementById(this.options.birthdateContainerSelector);
        this.birthdateInput = document.getElementById(this.options.birthdateInputSelector);
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

        this.installmentSecured.create({
            containerId: 'unzer-payment-installment-secured-container',
            amount: this.options.installmentSecuredAmount.toFixed(4),
            currency: this.options.installmentSecuredCurrency,
            effectiveInterest: this.options.installmentSecuredEffectiveInterest,
            orderDate: this.options.installmentSecuredOrderDate
        }).then(() => {
            // Hide the loading indicator
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

        this.installmentSecured.addEventListener('installmentSecuredEvent', (event) => this._onChangeInstallmentSecuredForm(event));
        this.birthdateInput.addEventListener('change', this._onBirthdateInputChange.bind(this))
    }

    /**
     * @private
     */
    _onCreateResource() {
        this._unzerPaymentPlugin.setSubmitButtonActive(false);

        this.installmentSecured.createResource()
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

        if (event.currentStep === 'plan-detail') {
            const installmentAmountTotalElement = document.getElementById(this.options.installmentsTotalValueElementId);
            const installmentInterestElement = document.getElementById(this.options.installmentsInterestValueElementId);

            installmentAmountTotalElement.innerText = this._formatCurrency(this.installmentSecured.selectedInstallmentPlan.totalAmount) + this.options.starSymbol;
            installmentInterestElement.innerText = this._formatCurrency(this.installmentSecured.selectedInstallmentPlan.totalInterestAmount) + this.options.starSymbol;
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

        const birthdate = new Date(this.birthdateInput.value),
            maxDate = new Date(),
            minAge = new Date()
        ;

        //normalize times
        birthdate.setHours(0,0,0,0);
        maxDate.setHours(0,0,0,0);
        minAge.setHours(0,0,0,0);

        //update maxDate and minAge to relevant values
        maxDate.setDate(maxDate.getDate() + 1);
        minAge.setFullYear(minAge.getFullYear() - 18);

        let isValid = birthdate <= minAge && birthdate < maxDate;

        if (isValid) {
            this.birthdateContainer.classList.remove('error');
        } else {
            this.birthdateContainer.classList.add('error');
        }

        return isValid;
    }
}
