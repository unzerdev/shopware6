import Plugin from 'src/plugin-system/plugin.class';
import ElementLoadingIndicatorUtil from 'src/utility/loading-indicator/element-loading-indicator.util';

export default class UnzerHirePurchasePlugin extends Plugin {
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
    static heidelpayPlugin = null;

    init() {
        this.unzerPlugin = window.PluginManager.getPluginInstances('UnzerPaymentBase')[0];
        this.hirePurchase = this.heidelpayPlugin.heidelpayInstance.HirePurchase();
        this.heidelpayPlugin.setSubmitButtonActive(false);

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
            this.heidelpayPlugin.renderErrorToElement(error, loadingIndicatorElement);
            this.heidelpayPlugin.setSubmitButtonActive(false);
        }).finally(() => {
            ElementLoadingIndicatorUtil.remove(loadingIndicatorElement);
        });
    }

    /**
     * @private
     */
    _registerEvents() {
        this.heidelpayPlugin.$emitter.subscribe('unzerBase_createResource', () => this._onCreateResource(), {
            scope: this
        });

        this.hirePurchase.addEventListener('hirePurchaseEvent', (event) => this._onChangeHirePurchaseForm(event));
    }

    /**
     * @private
     */
    _onCreateResource() {
        this.heidelpayPlugin.setSubmitButtonActive(false);

        this.hirePurchase.createResource()
            .then((resource) => this.heidelpayPlugin.submitResource(resource))
            .catch((error) => this.heidelpayPlugin.showError(error));
    }

    /**
     * @param {Object} event
     *
     * @private
     */
    _onChangeHirePurchaseForm(event) {
        if (event.action === 'validate') {
            if (event.success) {
                this.heidelpayPlugin.setSubmitButtonActive(true);
            } else {
                this.heidelpayPlugin.setSubmitButtonActive(false);
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
