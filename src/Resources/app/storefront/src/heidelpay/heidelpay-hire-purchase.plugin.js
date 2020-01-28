import Plugin from 'src/plugin-system/plugin.class';
import ElementLoadingIndicatorUtil from 'src/utility/loading-indicator/element-loading-indicator.util';
export default class HeidelpayHirePurchasePlugin extends Plugin {
    static options = {
        hirePurchaseAmount: 0.0,
        hirePurchaseCurrency: '',
        hirePurchaseEffectiveInterest: 0.0,
        hirePurchaseOrderDate: '',
        installmentsTotalValueElementId: 'heidelpay-installments-total',
        installmentsInterestValueElementId: 'heidelpay-installments-interest',
        formLoadingIndicatorSelector: '#element-loader',
        currencyIso: 'EUR',
        currencyFormatLocale: 'en-GB',
    };

    /**
     * @type {Object}
     *
     * @public
     */
    static hirePurchase;

    /**
     * @type {HeidelpayBasePlugin}
     *
     * @private
     */
    static heidelpayPlugin = null;

    init() {
        this.heidelpayPlugin = window.PluginManager.getPluginInstances('HeidelpayBase')[0];
        this.hirePurchase = this.heidelpayPlugin.heidelpayInstance.HirePurchase();
        this.heidelpayPlugin.setSubmitButtonActive(false);

        this._createForm();
        this._registerEvents();
    }

    /**
     * @private
     */
    _createForm() {
        const loadingIndicatorElement = this.el.querySelector(this.options.formLoadingIndicatorSelector);

        ElementLoadingIndicatorUtil.create(loadingIndicatorElement);

        this.hirePurchase.create({
            containerId: 'heidelpay-hire-purchase-container',
            amount: this.options.hirePurchaseAmount.toFixed(4),
            currency: this.options.hirePurchaseCurrency,
            effectiveInterest: this.options.hirePurchaseEffectiveInterest,
            orderDate: this.options.hirePurchaseOrderDate,
        }).then(() => {
            //Hide the loading indicator
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
        this.heidelpayPlugin.$emitter.subscribe('heidelpayBase_createResource', () => this._onCreateResource(), {
            scope: this,
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
            const installmentAmountTotalElement = document.getElementById(this.options.installmentsTotalValueElementId),
                installmentInterestElement = document.getElementById(this.options.installmentsInterestValueElementId);

            installmentAmountTotalElement.innerText = this._formatCurrency(this.hirePurchase.selectedInstallmentPlan.totalAmount) + '*';
            installmentInterestElement.innerText = this._formatCurrency(this.hirePurchase.selectedInstallmentPlan.totalInterestAmount);
        }
    }

    _formatCurrency(value) {
        return value.toLocaleString(this.options.currencyFormatLocale, {
            style: 'currency',
            currency: this.options.currencyIso,
        });
    }
}
