import Plugin from 'src/plugin-system/plugin.class';
import ElementLoadingIndicatorUtil from 'src/utility/loading-indicator/element-loading-indicator.util';

export default class UnzerPaymentHirePurchasePlugin extends Plugin {
    static options = {
        hirePurchaseAmount: 0.0,
        hirePurchaseCurrency: '',
        hirePurchaseEffectiveInterest: 0.0,
        hirePurchaseOrderDate: '',
        installmentsTotalValueElementId: 'unzer-installments-total',
        installmentsInterestValueElementId: 'unzer-installments-interest',
        formLoadingIndicatorElementId: 'element-loader',
        currencyIso: 'EUR',
        currencyFormatLocale: 'en-GB',
        starSymbol: '*'
    };

    /**
     * @type {Object}
     *
     * @public
     */
    static hirePurchase;

    /**
     * @type {UnzerPaymentBasePlugin}
     *
     * @private
     */
    static _unzerPaymentPlugin = null;

    init() {
        this._unzerPaymentPlugin = window.PluginManager.getPluginInstances('UnzerPaymentBase')[0];
        this.hirePurchase = this._unzerPaymentPlugin.unzerInstance.HirePurchase();
        this._unzerPaymentPlugin.setSubmitButtonActive(false);

        this._createForm();
        this._registerEvents();
    }

    /**
     * @private
     */
    _createForm() {
        const loadingIndicatorElement = document.getElementById(this.options.formLoadingIndicatorElementId);

        ElementLoadingIndicatorUtil.create(loadingIndicatorElement);

        this.hirePurchase.create({
            containerId: 'unzer-hire-purchase-container',
            amount: this.options.hirePurchaseAmount.toFixed(4),
            currency: this.options.hirePurchaseCurrency,
            effectiveInterest: this.options.hirePurchaseEffectiveInterest,
            orderDate: this.options.hirePurchaseOrderDate
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

        this.hirePurchase.addEventListener('hirePurchaseEvent', (event) => this._onChangeHirePurchaseForm(event));
    }

    /**
     * @private
     */
    _onCreateResource() {
        this._unzerPaymentPlugin.setSubmitButtonActive(false);

        this.hirePurchase.createResource()
            .then((resource) => this._unzerPaymentPlugin.submitResource(resource))
            .catch((error) => this._unzerPaymentPlugin.showError(error));
    }

    /**
     * @param {Object} event
     *
     * @private
     */
    _onChangeHirePurchaseForm(event) {
        if (event.action === 'validate') {
            if (event.success) {
                this._unzerPaymentPlugin.setSubmitButtonActive(true);
            } else {
                this._unzerPaymentPlugin.setSubmitButtonActive(false);
            }
        }

        if (event.currentStep === 'plan-detail') {
            const installmentAmountTotalElement = document.getElementById(this.options.installmentsTotalValueElementId);
            const installmentInterestElement = document.getElementById(this.options.installmentsInterestValueElementId);

            installmentAmountTotalElement.innerText = this._formatCurrency(this.hirePurchase.selectedInstallmentPlan.totalAmount) + this.options.starSymbol;
            installmentInterestElement.innerText = this._formatCurrency(this.hirePurchase.selectedInstallmentPlan.totalInterestAmount) + this.options.starSymbol;
        }
    }

    _formatCurrency(value) {
        return value.toLocaleString(this.options.currencyFormatLocale, {
            style: 'currency',
            currency: this.options.currencyIso
        });
    }
}
