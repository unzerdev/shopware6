import Plugin from 'src/plugin-system/plugin.class';
import ElementLoadingIndicatorUtil from 'src/utility/loading-indicator/element-loading-indicator.util';

export default class UnzerPaymentPaylaterDirectDebitSecuredPlugin extends Plugin {
    static options = {
        formLoadingIndicatorElementId: 'element-loader',
        birthdateInputIdSelector: 'unzerPaymentBirthday',
        birthdateContainerIdSelector: 'unzerPaymentBirthdayContainer',
        paylaterDirectDebitSecuredAmount: 0.0,
        paylaterDirectDebitSecuredCurrency: '',
        currencyIso: 'EUR',
        countryIso: 'DE',
        threatMetrixId: '',
    };

    /**
     * @type {Object}
     *
     * @public
     */
    static paylaterDirectDebitSecured;

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
     * @type {UnzerPaymentBasePlugin}
     *
     * @private
     */
    static _unzerPaymentPlugin = null;

    init() {
        this._unzerPaymentPlugin = window.PluginManager.getPluginInstances('UnzerPaymentBase')[0];
        this.paylaterDirectDebitSecured = this._unzerPaymentPlugin.unzerInstance.PaylaterDirectDebit();
        this._unzerPaymentPlugin.setSubmitButtonActive(false);
        this.birthdateContainer = document.getElementById(this.options.birthdateContainerIdSelector);
        this.birthdateInput = document.getElementById(this.options.birthdateInputIdSelector);

        this._createForm();
        this._registerEvents();
    }

    /**
     * @private
     */
    _createForm() {
        this.paylaterDirectDebitSecured.create('paylater-direct-debit', {
            containerId: 'unzer-payment-paylater-direct-debit-secured-container',
            amount: this.options.paylaterDirectDebitSecuredAmount.toFixed(4),
            currency: this.options.paylaterDirectDebitSecuredCurrency,
            country: this.options.countryIso,
            threatMetrixId: this.options.threatMetrixId,
        });
    }

    /**
     * @private
     */
    _registerEvents() {
        this._unzerPaymentPlugin.$emitter.subscribe('unzerBase_createResource', () => this._onCreateResource(), {
            scope: this
        });

        this.birthdateInput.addEventListener('change', this._onBirthdateInputChange.bind(this))
    }

    /**
     * @private
     */
    _onCreateResource() {
        this._unzerPaymentPlugin.setSubmitButtonActive(false);
        const loadingIndicatorElement = document.getElementById(this.options.formLoadingIndicatorElementId);

        ElementLoadingIndicatorUtil.create(loadingIndicatorElement);

        console.log('test');
        this.paylaterDirectDebitSecured.createResource()
            .then(function(resource) {
                this._submitPayment(resource);
            }.bind(this))
            .catch(function(error) {
                this._unzerPaymentPlugin.renderErrorToElement(error, loadingIndicatorElement);

                ElementLoadingIndicatorUtil.remove(loadingIndicatorElement);
            }.bind(this));
    }

    /**
     * @param {Object} resource
     *
     * @private
     */
    _submitPayment(resource) {
        this._unzerPaymentPlugin.submitResource(resource);
    }

    _formatCurrency(value) {
        return value.toLocaleString(this.options.currencyFormatLocale, {
            style: 'currency',
            currency: this.options.currencyIso
        });
    }

    _onBirthdateInputChange() {
        if (this._validateBirthdate()) {
            this._unzerPaymentPlugin.setSubmitButtonActive(true);
        } else {
            this._unzerPaymentPlugin.setSubmitButtonActive(false);
        }
    }

    _validateBirthdate() {
        console.log('validating birthdate');
        if (this.birthdateInput.value === '') {
            return false;
        }

        const birthdate = new Date(this.birthdateInput.value);
        const maxDate = new Date();
        const minAge = new Date();

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
